<?php

class Migration_173_AttendancePolicyControls
{
    public function up(\PDO $pdo): void
    {
        $cols = $pdo->query('SHOW COLUMNS FROM attendance_gateways')->fetchAll(PDO::FETCH_COLUMN);
        $add = static function (string $sql) use ($pdo): void {
            try {
                $pdo->exec($sql);
            } catch (Throwable) {
            }
        };

        if (!in_array('open_mode', $cols, true)) {
            $add("ALTER TABLE attendance_gateways ADD COLUMN open_mode VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER is_open");
        }
        if (!in_array('clerk_confirmed_at', $cols, true)) {
            $add('ALTER TABLE attendance_gateways ADD COLUMN clerk_confirmed_at DATETIME NULL AFTER open_mode');
        }
        if (!in_array('clerk_confirmed_by', $cols, true)) {
            $add('ALTER TABLE attendance_gateways ADD COLUMN clerk_confirmed_by INT UNSIGNED NULL AFTER clerk_confirmed_at');
        }
        if (!in_array('escalation_notified_at', $cols, true)) {
            $add('ALTER TABLE attendance_gateways ADD COLUMN escalation_notified_at DATETIME NULL AFTER clerk_confirmed_by');
        }
        if (!in_array('policy_open_time', $cols, true)) {
            $add("ALTER TABLE attendance_gateways ADD COLUMN policy_open_time CHAR(5) NULL AFTER escalation_notified_at");
        }
        if (!in_array('policy_close_time', $cols, true)) {
            $add("ALTER TABLE attendance_gateways ADD COLUMN policy_close_time CHAR(5) NULL AFTER policy_open_time");
        }

        // Seed / update attendance policy defaults (County Director controls).
        $settings = [
            ['attendance.signin_start', 'attendance', 'Sign-in opens', 'Earliest time interns/clerks may sign in (Mon–Fri).', '07:00', 'time', 40],
            ['attendance.signin_end', 'attendance', 'Sign-in closes', 'Attendance window closes; no normal sign-in after this time.', '08:30', 'time', 50],
            ['attendance.expected_time', 'attendance', 'Expected report time', 'Target report time. Later sign-ins (until close) are marked late.', '08:00', 'time', 55],
            ['attendance.late_after', 'attendance', 'Late after', 'Sign-ins after this time are marked late (within the open window).', '08:00', 'time', 60],
            ['attendance.active_weekdays', 'attendance', 'Active weekdays', 'Comma-separated weekdays when attendance is allowed (1=Mon … 7=Sun).', '1,2,3,4,5', 'text', 65],
            ['attendance.auto_open', 'attendance', 'Auto-open gateway', 'Automatically open policy window so early arrivals are not blocked.', '1', 'boolean', 68],
            ['attendance.confirm_escalation_minutes', 'attendance', 'Clerk confirm escalation (minutes)', 'Minutes after auto-open before County Director is notified if clerk has not confirmed.', '30', 'number', 72],
        ];

        foreach ($settings as [$key, $group, $label, $desc, $default, $type, $sort]) {
            $stmt = $pdo->prepare(
                "INSERT INTO system_settings
                    (setting_key, setting_group, label, description, value, default_value, type, options_json, is_sensitive, is_public, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 0, 0, ?)
                 ON DUPLICATE KEY UPDATE
                    setting_group = VALUES(setting_group),
                    label = VALUES(label),
                    description = VALUES(description),
                    default_value = VALUES(default_value),
                    type = VALUES(type),
                    sort_order = VALUES(sort_order),
                    value = IF(setting_key IN ('attendance.signin_start','attendance.signin_end','attendance.late_after','attendance.expected_time')
                        AND (value IN ('06:00','18:00','08:30') OR value = default_value OR value IS NULL OR value = ''),
                        VALUES(value), value)"
            );
            $stmt->execute([$key, $group, $label, $desc, $default, $default, $type, $sort]);
        }

        // Force production policy values for the agreed design.
        foreach ([
            'attendance.signin_start' => '07:00',
            'attendance.signin_end' => '08:30',
            'attendance.expected_time' => '08:00',
            'attendance.late_after' => '08:00',
            'attendance.active_weekdays' => '1,2,3,4,5',
            'attendance.auto_open' => '1',
            'attendance.confirm_escalation_minutes' => '30',
        ] as $key => $value) {
            $pdo->prepare('UPDATE system_settings SET value = ?, default_value = ? WHERE setting_key = ?')
                ->execute([$value, $value, $key]);
        }
    }

    public function down(\PDO $pdo): void
    {
        // Non-destructive down.
    }
}
