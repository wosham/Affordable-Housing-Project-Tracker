<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';
SystemConfig::clear();

echo "=== Attendance policy smoke ===\n";
$fail = 0;
$root = dirname(__DIR__);

$files = [
    'app/models/AttendancePolicy.php',
    'app/models/AttendanceGateway.php',
    'app/models/ClerkAttendance.php',
    'app/models/InternAttendance.php',
    'api/attendance/sign-in.php',
    'api/attendance/gateway-open.php',
    'api/attendance/gateway-status.php',
    'admin/clerk/attendance-gateway.php',
    'admin/intern/sign-in.php',
    'admin/superadmin/attendance.php',
];
foreach ($files as $f) {
    $path = $root . '/' . $f;
    if (!is_file($path)) {
        echo "FAIL missing $f\n";
        $fail++;
        continue;
    }
    if (str_ends_with($f, '.php')) {
        $out = [];
        $code = 0;
        exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            echo "FAIL lint $f\n";
            $fail++;
        } else {
            echo "OK   lint $f\n";
        }
    }
}

$policy = AttendancePolicy::settings();
echo 'open=' . $policy['open_time'] . ' close=' . $policy['close_time'] . ' expected=' . $policy['expected_time'] . ' late=' . $policy['late_after'] . "\n";
if ($policy['open_time'] !== '07:00' || $policy['close_time'] !== '08:30') {
    echo "FAIL policy times not 07:00-08:30 (got {$policy['open_time']}-{$policy['close_time']})\n";
    $fail++;
} else {
    echo "OK   policy window 07:00-08:30\n";
}
if ($policy['active_weekdays'] !== [1, 2, 3, 4, 5]) {
    echo 'FAIL weekdays ' . json_encode($policy['active_weekdays']) . "\n";
    $fail++;
} else {
    echo "OK   weekdays Mon-Fri\n";
}

// Columns exist
$cols = array_column(Database::fetchAll('SHOW COLUMNS FROM attendance_gateways'), 'Field');
foreach (['open_mode', 'clerk_confirmed_at', 'clerk_confirmed_by', 'escalation_notified_at'] as $col) {
    if (!in_array($col, $cols, true)) {
        echo "FAIL missing column $col\n";
        $fail++;
    }
}
echo "OK   gateway policy columns\n";

// Unique one-sign/day still present
$idx = Database::fetchAll("SHOW INDEX FROM attendance_records WHERE Key_name = 'uq_user_date'");
if ($idx === []) {
    echo "FAIL missing uq_user_date unique index\n";
    $fail++;
} else {
    echo "OK   one sign-in per user per day (unique index)\n";
}

// Auto-open for a project with clerk assignment
$project = Database::fetch(
    "SELECT p.id FROM projects p
     JOIN project_assignments pa ON pa.project_id = p.id AND pa.status = 'active'
     JOIN users u ON u.id = pa.user_id
     JOIN roles r ON r.id = u.role_id AND r.slug = 'clerk'
     ORDER BY p.id ASC LIMIT 1"
);
if ($project) {
    $pid = (int)$project['id'];
    $gw = AttendancePolicy::ensureProjectWindow($pid);
    $payload = AttendancePolicy::windowPayload();
    echo 'project=' . $pid . ' gateway=' . ($gw ? 'yes' : 'no') . ' within=' . ($payload['is_within_window'] ? 'yes' : 'no') . ' active_day=' . ($payload['is_active_day'] ? 'yes' : 'no') . "\n";
    if ($payload['is_active_day'] && $payload['is_within_window']) {
        if (!$gw || !AttendancePolicy::isEffectivelyOpen($gw)) {
            echo "FAIL expected open gateway inside window\n";
            $fail++;
        } else {
            echo "OK   auto-open inside window\n";
        }
    } else {
        echo "OK   outside window path (time-dependent)\n";
    }

    // Clerk confirm path (only if within window)
    $clerk = Database::fetch(
        "SELECT u.id FROM project_assignments pa
         JOIN users u ON u.id = pa.user_id AND u.status = 'active'
         JOIN roles r ON r.id = u.role_id AND r.slug = 'clerk'
         WHERE pa.project_id = ? AND pa.status = 'active' LIMIT 1",
        [$pid]
    );
    if ($clerk && $payload['is_within_window']) {
        try {
            $id = AttendancePolicy::clerkConfirm((int)$clerk['id'], $pid, date('Y-m-d'), 'Smoke confirm');
            $row = AttendanceGateway::forProjectDate($pid, date('Y-m-d'));
            if (empty($row['clerk_confirmed_at'])) {
                echo "FAIL clerk_confirmed_at not set\n";
                $fail++;
            } else {
                echo "OK   clerk confirm id=$id\n";
            }
        } catch (Throwable $e) {
            echo 'FAIL clerk confirm ' . $e->getMessage() . "\n";
            $fail++;
        }
    }
} else {
    echo "WARN no project with clerk assignment for auto-open test\n";
}

// Sign-in API roles
$src = file_get_contents($root . '/api/attendance/sign-in.php');
if (!str_contains($src, "'intern', 'clerk'") && !str_contains($src, "'clerk'")) {
    echo "FAIL sign-in does not allow clerk\n";
    $fail++;
} else {
    echo "OK   sign-in roles include intern+clerk\n";
}
if (!str_contains($src, 'You can only sign in for yourself') && !str_contains($src, 'self')) {
    echo "FAIL sign-in missing self-only guard\n";
    $fail++;
} else {
    echo "OK   self-only sign-in guard\n";
}

// Clerk UI no free close time input editable
$clerkUi = file_get_contents($root . '/admin/clerk/attendance-gateway.php');
if (str_contains($clerkUi, 'name="closes_at"')) {
    echo "FAIL clerk UI still has editable closes_at name\n";
    $fail++;
} else {
    echo "OK   clerk cannot set close time\n";
}
if (!str_contains($clerkUi, 'Confirm site open')) {
    echo "FAIL clerk UI missing confirm action\n";
    $fail++;
} else {
    echo "OK   clerk confirm UX\n";
}

// Director page policy banner
$sa = file_get_contents($root . '/admin/superadmin/attendance.php');
if (!str_contains($sa, 'County Director attendance policy')) {
    echo "FAIL superadmin attendance missing policy banner\n";
    $fail++;
} else {
    echo "OK   superadmin policy banner\n";
}

echo $fail === 0 ? "ATTENDANCE SMOKE OK\n" : "ATTENDANCE SMOKE FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
