<?php
// AHPTC Database Seed Runner
// Run from CLI: php app/database/seed.php
// Run from browser: /app/database/seed.php (localhost only)

require_once __DIR__ . '/DatabaseConfig.php';

if (php_sapi_name() !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'])) {
        http_response_code(403); die('Seed runner only accessible from localhost.');
    }
}

$db = DatabaseConfig::pdo();

$seedFiles = [
    'RoleSeeder', 'UserSeeder', 'ConstituencySeeder', 'ProjectCategorySeeder',
    'GeoFenceSeeder', 'ProjectSeeder', 'MilestoneSeeder', 'BOQSeeder',
    'CmsPageSeeder', 'CmsSectionSeeder', 'CmsSettingSeeder',
    'NewsCategorySeeder', 'FAQSeeder', 'LeadershipSeeder', 'StakeholderSeeder',
    'FrontendContentSeeder',
];

foreach ($seedFiles as $seeder) {
    $file = __DIR__ . '/seeds/' . $seeder . '.php';
    if (file_exists($file)) {
        require_once $file;
        (new $seeder())->run($db);
    } else {
        echo "  ⚠ Missing seeder file: {$seeder}.php\n";
    }
}
echo "\nAll seeders complete.\n";
