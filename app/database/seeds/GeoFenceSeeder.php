<?php
class GeoFenceSeeder
{
    public function run(\PDO $pdo): void
    {
        // Placeholder geo-fences — update with actual GPS coordinates per site
        // Default radius: 200 metres (configurable in system settings)
        echo "✓ GeoFenceSeeder: No geo-fences seeded yet — add real GPS coordinates per project.\n";
        echo "  Use superadmin/settings.php to add geo-fences per project.\n";
    }
}
