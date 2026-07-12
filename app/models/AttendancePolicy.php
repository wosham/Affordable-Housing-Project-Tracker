<?php

/**
 * County Director attendance policy + automatic gateway window.
 * Clerks do not set times; they confirm site open within the policy window.
 */
class AttendancePolicy
{
    public static function timezone(): string
    {
        return 'Africa/Nairobi';
    }

    public static function now(): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable('now', new DateTimeZone(self::timezone()));
        } catch (Throwable) {
            return new DateTimeImmutable('now');
        }
    }

    public static function settings(): array
    {
        $open = self::normaliseTime(SystemConfig::text('attendance.signin_start', '07:00'), '07:00');
        $close = self::normaliseTime(SystemConfig::text('attendance.signin_end', '08:30'), '08:30');
        $expected = self::normaliseTime(SystemConfig::text('attendance.expected_time', SystemConfig::text('attendance.late_after', '08:00')), '08:00');
        $lateAfter = self::normaliseTime(SystemConfig::text('attendance.late_after', $expected), $expected);
        $weekdays = self::parseWeekdays(SystemConfig::text('attendance.active_weekdays', '1,2,3,4,5'));

        return [
            'open_time' => $open,
            'close_time' => $close,
            'expected_time' => $expected,
            'late_after' => $lateAfter,
            'active_weekdays' => $weekdays,
            'auto_open' => SystemConfig::bool('attendance.auto_open', true),
            'escalation_minutes' => max(5, SystemConfig::int('attendance.confirm_escalation_minutes', 30)),
            'gps_required' => SystemConfig::bool('attendance.gps_required', true),
            'label' => sprintf('%s–%s · expected %s · late after %s · Mon–Fri', $open, $close, $expected, $lateAfter),
        ];
    }

    public static function isActiveWeekday(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?: self::now();
        $settings = self::settings();
        // PHP: 1=Mon … 7=Sun
        $dow = (int)$now->format('N');
        return in_array($dow, $settings['active_weekdays'], true);
    }

    public static function isWithinWindow(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?: self::now();
        if (!self::isActiveWeekday($now)) {
            return false;
        }
        $settings = self::settings();
        $t = $now->format('H:i');
        return $t >= $settings['open_time'] && $t <= $settings['close_time'];
    }

    public static function isBeforeOpen(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?: self::now();
        if (!self::isActiveWeekday($now)) {
            return false;
        }
        return $now->format('H:i') < self::settings()['open_time'];
    }

    public static function isAfterClose(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?: self::now();
        if (!self::isActiveWeekday($now)) {
            return true;
        }
        return $now->format('H:i') > self::settings()['close_time'];
    }

    public static function statusForSignInTime(?DateTimeImmutable $now = null): string
    {
        $now = $now ?: self::now();
        if (!self::isActiveWeekday($now) || self::isAfterClose($now) || self::isBeforeOpen($now)) {
            return 'outside-window';
        }
        $settings = self::settings();
        if ($now->format('H:i') > $settings['late_after']) {
            return 'late';
        }
        return 'present';
    }

    /**
     * Ensure project gateway is open for the policy window (auto).
     * Returns gateway row or null when not an active attendance day / outside window.
     */
    public static function ensureProjectWindow(int $projectId, ?string $date = null): ?array
    {
        if ($projectId <= 0) {
            return null;
        }
        $now = self::now();
        $date = $date ?: $now->format('Y-m-d');
        $settings = self::settings();

        // Only auto-manage "today" in Nairobi.
        if ($date !== $now->format('Y-m-d')) {
            return AttendanceGateway::forProjectDate($projectId, $date);
        }

        if (!$settings['auto_open'] || !self::isActiveWeekday($now)) {
            return AttendanceGateway::forProjectDate($projectId, $date);
        }

        // Before open: do not create open gate.
        if (self::isBeforeOpen($now)) {
            return AttendanceGateway::forProjectDate($projectId, $date);
        }

        // After close: force closed for policy window.
        if (self::isAfterClose($now)) {
            $existing = AttendanceGateway::forProjectDate($projectId, $date);
            if ($existing && (int)$existing['is_open'] === 1) {
                // Only auto-close if still using policy close (not emergency early close already done).
                $closesAt = strtotime((string)$existing['closes_at']);
                if ($closesAt !== false && $closesAt <= time()) {
                    Database::query(
                        'UPDATE attendance_gateways SET is_open = 0 WHERE id = ? AND is_open = 1',
                        [(int)$existing['id']]
                    );
                    return AttendanceGateway::forProjectDate($projectId, $date);
                }
            }
            return $existing;
        }

        // Inside window: ensure open with policy close time.
        $closesAt = $date . ' ' . $settings['close_time'] . ':00';
        $existing = AttendanceGateway::forProjectDate($projectId, $date);
        $systemUserId = self::systemActorId();

        if ($existing) {
            Database::query(
                'UPDATE attendance_gateways
                 SET is_open = 1,
                     closes_at = ?,
                     policy_open_time = ?,
                     policy_close_time = ?,
                     open_mode = CASE
                        WHEN clerk_confirmed_at IS NOT NULL THEN COALESCE(NULLIF(open_mode, ""), "clerk_confirmed")
                        ELSE "system_auto"
                     END,
                     opened_at = COALESCE(opened_at, NOW()),
                     opened_by = COALESCE(opened_by, ?)
                 WHERE id = ?',
                [$closesAt, $settings['open_time'], $settings['close_time'], $systemUserId, (int)$existing['id']]
            );
        } else {
            Database::query(
                'INSERT INTO attendance_gateways
                    (project_id, work_location_id, date, opened_by, opened_at, closes_at, is_open, notes, open_mode, policy_open_time, policy_close_time)
                 VALUES (?, NULL, ?, ?, NOW(), ?, 1, ?, "system_auto", ?, ?)',
                [
                    $projectId,
                    $date,
                    $systemUserId,
                    $closesAt,
                    'Auto-opened by attendance policy window.',
                    $settings['open_time'],
                    $settings['close_time'],
                ]
            );
        }

        $gateway = AttendanceGateway::forProjectDate($projectId, $date);
        if ($gateway) {
            self::maybeEscalateClerkConfirm($gateway, $settings);
        }
        return AttendanceGateway::forProjectDate($projectId, $date);
    }

    public static function ensureWorkLocationWindow(int $workLocationId, ?string $date = null): ?array
    {
        if ($workLocationId <= 0) {
            return null;
        }
        $now = self::now();
        $date = $date ?: $now->format('Y-m-d');
        $settings = self::settings();
        if ($date !== $now->format('Y-m-d') || !$settings['auto_open'] || !self::isActiveWeekday($now) || self::isBeforeOpen($now)) {
            return AttendanceGateway::forWorkLocationDate($workLocationId, $date);
        }
        if (self::isAfterClose($now)) {
            $existing = AttendanceGateway::forWorkLocationDate($workLocationId, $date);
            if ($existing && (int)$existing['is_open'] === 1 && strtotime((string)$existing['closes_at']) <= time()) {
                Database::query('UPDATE attendance_gateways SET is_open = 0 WHERE id = ?', [(int)$existing['id']]);
            }
            return AttendanceGateway::forWorkLocationDate($workLocationId, $date);
        }

        $closesAt = $date . ' ' . $settings['close_time'] . ':00';
        $existing = AttendanceGateway::forWorkLocationDate($workLocationId, $date);
        $systemUserId = self::systemActorId();
        if ($existing) {
            Database::query(
                'UPDATE attendance_gateways SET is_open = 1, closes_at = ?, policy_open_time = ?, policy_close_time = ?,
                 open_mode = COALESCE(NULLIF(open_mode, ""), "system_auto"), opened_at = COALESCE(opened_at, NOW()), opened_by = COALESCE(opened_by, ?)
                 WHERE id = ?',
                [$closesAt, $settings['open_time'], $settings['close_time'], $systemUserId, (int)$existing['id']]
            );
        } else {
            Database::query(
                'INSERT INTO attendance_gateways
                    (project_id, work_location_id, date, opened_by, opened_at, closes_at, is_open, notes, open_mode, policy_open_time, policy_close_time)
                 VALUES (NULL, ?, ?, ?, NOW(), ?, 1, ?, "system_auto", ?, ?)',
                [$workLocationId, $date, $systemUserId, $closesAt, 'Auto-opened by attendance policy window.', $settings['open_time'], $settings['close_time']]
            );
        }
        return AttendanceGateway::forWorkLocationDate($workLocationId, $date);
    }

    public static function isEffectivelyOpen(?array $gateway, ?DateTimeImmutable $now = null): bool
    {
        if (!$gateway) {
            return false;
        }
        $now = $now ?: self::now();
        if ((int)($gateway['is_open'] ?? 0) !== 1) {
            return false;
        }
        $closes = strtotime((string)($gateway['closes_at'] ?? ''));
        if ($closes === false || $closes < $now->getTimestamp()) {
            return false;
        }
        // Policy also requires active weekday + within configured times for normal sign-in.
        return self::isWithinWindow($now);
    }

    public static function clerkConfirm(int $clerkId, int $projectId, string $date, ?string $notes = null): int
    {
        if ($projectId <= 0) {
            throw new RuntimeException('Choose an assigned project.');
        }
        $now = self::now();
        if ($date !== $now->format('Y-m-d')) {
            throw new RuntimeException('Clerk confirmation is only available for today.');
        }
        if (!self::isActiveWeekday($now)) {
            throw new RuntimeException('Attendance is not scheduled for today (weekdays only).');
        }
        if (self::isBeforeOpen($now)) {
            $settings = self::settings();
            throw new RuntimeException('Policy window has not opened yet. Opens at ' . $settings['open_time'] . '.');
        }
        if (self::isAfterClose($now)) {
            throw new RuntimeException('Policy window has already closed for today.');
        }

        $gateway = self::ensureProjectWindow($projectId, $date);
        if (!$gateway) {
            throw new RuntimeException('Attendance gateway could not be prepared for this project.');
        }

        Database::query(
            'UPDATE attendance_gateways
             SET is_open = 1,
                 clerk_confirmed_at = NOW(),
                 clerk_confirmed_by = ?,
                 open_mode = "clerk_confirmed",
                 notes = COALESCE(NULLIF(?, ""), notes),
                 closes_at = ?
             WHERE id = ?',
            [
                $clerkId,
                $notes,
                $date . ' ' . self::settings()['close_time'] . ':00',
                (int)$gateway['id'],
            ]
        );

        return (int)$gateway['id'];
    }

    public static function windowPayload(): array
    {
        $settings = self::settings();
        $now = self::now();
        return [
            'open_time' => $settings['open_time'],
            'close_time' => $settings['close_time'],
            'expected_time' => $settings['expected_time'],
            'late_after' => $settings['late_after'],
            'active_weekdays' => $settings['active_weekdays'],
            'auto_open' => $settings['auto_open'],
            'is_active_day' => self::isActiveWeekday($now),
            'is_within_window' => self::isWithinWindow($now),
            'is_before_open' => self::isBeforeOpen($now),
            'is_after_close' => self::isAfterClose($now),
            'label' => $settings['label'],
            'server_time' => $now->format('Y-m-d H:i:s'),
            'timezone' => self::timezone(),
        ];
    }

    private static function maybeEscalateClerkConfirm(array $gateway, array $settings): void
    {
        if (!empty($gateway['clerk_confirmed_at']) || !empty($gateway['escalation_notified_at'])) {
            return;
        }
        $openedAt = strtotime((string)($gateway['opened_at'] ?? ''));
        if ($openedAt === false) {
            return;
        }
        $minutes = (int)$settings['escalation_minutes'];
        if ((time() - $openedAt) < ($minutes * 60)) {
            return;
        }

        $projectName = (string)($gateway['project_name'] ?? 'a project');
        try {
            Notification::pushRole(
                'superadmin',
                'attendance',
                'Clerk has not confirmed site open',
                'Attendance auto-opened for ' . $projectName . ' but the site clerk has not confirmed site open after ' . $minutes . ' minutes. Interns can still sign in within the policy window.',
                'admin/superadmin/attendance.php'
            );
            // Soft in-app warning for clerks on that project only.
            $clerks = Database::fetchAll(
                "SELECT DISTINCT u.id
                 FROM project_assignments pa
                 JOIN users u ON u.id = pa.user_id AND u.status = 'active'
                 JOIN roles r ON r.id = u.role_id
                 WHERE pa.project_id = ? AND pa.status = 'active' AND r.slug = 'clerk'",
                [(int)$gateway['project_id']]
            );
            foreach ($clerks as $clerk) {
                Notification::push(
                    (int)$clerk['id'],
                    'attendance',
                    'Confirm site open',
                    'Attendance is open for ' . $projectName . ' under County Director policy. Please confirm site open on the Attendance Gateway page.',
                    'admin/clerk/attendance-gateway.php?project_id=' . (int)$gateway['project_id']
                );
            }
            Database::query(
                'UPDATE attendance_gateways SET escalation_notified_at = NOW() WHERE id = ?',
                [(int)$gateway['id']]
            );
        } catch (Throwable) {
        }
    }

    private static function systemActorId(): int
    {
        static $id = null;
        if ($id !== null) {
            return $id;
        }
        $row = Database::fetch(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'superadmin' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
        );
        $id = (int)($row['id'] ?? 1);
        return $id;
    }

    private static function normaliseTime(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^\d{2}:\d{2}/', $value, $m)) {
            return substr($m[0], 0, 5);
        }
        return $fallback;
    }

    private static function parseWeekdays(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', strtolower(trim($raw))) ?: [];
        $map = [
            'mon' => 1, 'monday' => 1, '1' => 1,
            'tue' => 2, 'tues' => 2, 'tuesday' => 2, '2' => 2,
            'wed' => 3, 'wednesday' => 3, '3' => 3,
            'thu' => 4, 'thur' => 4, 'thurs' => 4, 'thursday' => 4, '4' => 4,
            'fri' => 5, 'friday' => 5, '5' => 5,
            'sat' => 6, 'saturday' => 6, '6' => 6,
            'sun' => 7, 'sunday' => 7, '7' => 7, '0' => 7,
        ];
        $days = [];
        foreach ($parts as $part) {
            if (isset($map[$part])) {
                $days[] = $map[$part];
            }
        }
        $days = array_values(array_unique($days));
        return $days !== [] ? $days : [1, 2, 3, 4, 5];
    }
}
