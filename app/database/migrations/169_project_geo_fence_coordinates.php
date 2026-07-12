<?php

class Migration169ProjectGeoFenceCoordinates
{
    public function up(PDO $pdo): void
    {
        $actorId = $this->actorId($pdo);
        $rows = [
            'maili-tatu-affordable-housing-estate' => ['Maili Tatu Affordable Housing Estate Main Site', 0.99000000, 34.99000000, 250, 'Configured from supplied project coordinate list. Source note: public record indicated W longitude; corrected to E because Trans Nzoia is in the Eastern Hemisphere.'],
            'suam-border-post-affordable-housing-estate' => ['Suam Border Post Affordable Housing Estate Main Site', 1.21582500, 34.73417200, 250, 'Configured from supplied project coordinate list.'],
            'suam-modern-market' => ['Suam Modern Market Main Site', 1.21582500, 34.73417200, 200, 'Configured from supplied project coordinate list.'],
            'kiminini-modern-market' => ['Kiminini Modern Market Main Site', 0.89313000, 34.92408000, 200, 'Configured from supplied project coordinate list.'],
            'birunda-esp-site' => ['Birunda ESP Site Main Site', 0.95882170, 34.93889170, 200, 'Configured from supplied project coordinate list.'],
            'kolongei-esp-site' => ['Kolongei ESP Site Main Site', 1.07000000, 34.86000000, 200, 'Configured from supplied project coordinate list. Source note: public record indicated W longitude; corrected to E because Trans Nzoia is in the Eastern Hemisphere.'],
            'kitale-polytechnic-institutional-housing' => ['Kitale Polytechnic Institutional Housing Main Site', 1.01570000, 35.00620000, 200, 'Configured from supplied project coordinate list.'],
            'kiminini-tvc-institutional-housing' => ['Kiminini TVC Institutional Housing Main Site', 0.89313000, 34.92408000, 200, 'Configured from supplied project coordinate list.'],
            'kwanza-tvc-institutional-housing' => ['Kwanza TVC Institutional Housing Main Site', 1.16284700, 34.99924300, 200, 'Configured from supplied project coordinate list.'],
        ];

        $findProject = $pdo->prepare('SELECT id FROM projects WHERE slug = ? LIMIT 1');
        $findFence = $pdo->prepare('SELECT id FROM geo_fences WHERE project_id = ? ORDER BY id ASC LIMIT 1');
        $update = $pdo->prepare("UPDATE geo_fences
            SET site_name = ?, latitude = ?, longitude = ?, radius_meters = ?, status = 'configured', verified_by = ?, verified_at = NOW(), notes = ?
            WHERE id = ?");
        $insert = $pdo->prepare("INSERT INTO geo_fences
            (project_id, site_name, latitude, longitude, radius_meters, status, created_by, verified_by, verified_at, notes)
            VALUES (?, ?, ?, ?, ?, 'configured', ?, ?, NOW(), ?)");

        foreach ($rows as $slug => [$siteName, $latitude, $longitude, $radius, $notes]) {
            $findProject->execute([$slug]);
            $projectId = (int)$findProject->fetchColumn();
            if ($projectId <= 0) {
                continue;
            }

            $findFence->execute([$projectId]);
            $fenceId = (int)$findFence->fetchColumn();
            if ($fenceId > 0) {
                $update->execute([$siteName, $latitude, $longitude, $radius, $actorId, $notes, $fenceId]);
                continue;
            }

            $insert->execute([$projectId, $siteName, $latitude, $longitude, $radius, $actorId, $actorId, $notes]);
        }
    }

    public function down(PDO $pdo): void
    {
        // Intentionally non-destructive: coordinate records may be manually refined after seeding.
    }

    private function actorId(PDO $pdo): int
    {
        $id = (int)$pdo->query("SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.slug = 'superadmin' ORDER BY u.id ASC LIMIT 1")->fetchColumn();
        if ($id > 0) {
            return $id;
        }
        return (int)$pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 1;
    }
}