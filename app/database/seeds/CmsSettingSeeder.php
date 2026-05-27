<?php
class CmsSettingSeeder
{
    public function run(\PDO $pdo): void
    {
        $settings = [
            // Global
            ['site_name',           'Trans-Nzoia County Affordable Housing Programme Tracker', 'text',   'Site Name',         'global'],
            ['site_tagline',        'Building Better Communities, One Home at a Time',          'text',   'Site Tagline',      'global'],
            ['maintenance_mode',    '0',                                                         'boolean','Maintenance Mode',  'global'],
            // Contact
            ['contact_email',       'housing@transnzoia.go.ke', 'text',  'Contact Email',  'contact'],
            ['contact_phone',       '+254 53 000 0000',          'text',  'Contact Phone',  'contact'],
            ['contact_address',     'Trans-Nzoia County Government, Kitale', 'text', 'Address', 'contact'],
            // Stats (shown in hero/about sections)
            ['stat_projects',       '47',   'number', 'Total Projects',   'stats'],
            ['stat_units',          '5600', 'number', 'Housing Units',    'stats'],
            ['stat_constituencies', '7',    'number', 'Constituencies',   'stats'],
            ['stat_budget',         '2.1B', 'text',   'Total Budget',     'stats'],
            // Social
            ['social_facebook',  '', 'text', 'Facebook URL',  'social'],
            ['social_twitter',   '', 'text', 'Twitter URL',   'social'],
            ['social_youtube',   '', 'text', 'YouTube URL',   'social'],
            ['social_instagram', '', 'text', 'Instagram URL', 'social'],
            // Attendance
            ['attendance_window_open',  '08:00', 'text', 'Attendance Window Open',  'attendance'],
            ['attendance_window_close', '08:40', 'text', 'Attendance Window Close', 'attendance'],
            ['geo_fence_radius',        '200',   'number', 'Geo-fence Radius (metres)', 'attendance'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO cms_settings (`key`, value, type, label, `group`) VALUES (?, ?, ?, ?, ?)");
        foreach ($settings as $s) { $stmt->execute($s); }
        echo "✓ CMS settings seeded.\n";
    }
}
