<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';
echo "=== Phase 14 final verify ===\n";
$rows = Database::fetchAll(
    "SELECT u.id, u.email,
            (SELECT COUNT(*) FROM project_assignments pa WHERE pa.user_id = u.id AND pa.status = 'active') AS ass
     FROM users u
     JOIN roles r ON r.id = u.role_id AND r.slug = 'intern'
     WHERE u.email LIKE '%.ahp@gmail.com'
     ORDER BY u.id"
);
foreach ($rows as $x) {
    echo $x['id'] . ' ' . $x['email'] . ' ass=' . $x['ass'] . "\n";
}
$ann = Database::fetchAll(
    "SELECT id, title, status FROM announcements
     WHERE title LIKE '%Confirm%' OR body LIKE '%gateway%' OR body LIKE '%Director%' OR title LIKE '%Clerks:%'
     ORDER BY id DESC LIMIT 10"
);
echo "---announcements---\n";
foreach ($ann as $x) {
    echo $x['id'] . ' [' . $x['status'] . '] ' . $x['title'] . "\n";
}
$seeded = Database::fetchAll(
    "SELECT u.id, u.email FROM users u WHERE u.email IN (
        'amina.wanjiru.ahp@gmail.com','brian.kipchoge.ahp@gmail.com','cynthia.achieng.ahp@gmail.com',
        'daniel.mutiso.ahp@gmail.com','esther.njeri.ahp@gmail.com','felix.odhiambo.ahp@gmail.com',
        'grace.chebet.ahp@gmail.com','hassan.ali.ahp@gmail.com','irene.wambui.ahp@gmail.com',
        'joseph.kamau.ahp@gmail.com'
     )"
);
echo 'phase14_named_interns=' . count($seeded) . "\n";
$withAss = 0;
foreach ($seeded as $s) {
    $c = (int)(Database::fetch(
        'SELECT COUNT(*) AS c FROM project_assignments WHERE user_id = ? AND status = ?',
        [(int)$s['id'], 'active']
    )['c'] ?? 0);
    if ($c > 0) {
        $withAss++;
    }
}
echo "phase14_named_with_assignment={$withAss}\n";
echo ($withAss >= 10 ? "FINAL VERIFY OK\n" : "FINAL VERIFY FAIL\n");
exit($withAss >= 10 ? 0 : 1);
