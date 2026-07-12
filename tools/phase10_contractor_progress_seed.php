<?php

/**
 * Phase 10 contractor progress demo seed (idempotent).
 * Creates progress updates + placeholder site photos for David (and Mercy if present).
 * Does NOT invent payment history.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 10 contractor progress seed ===\n";

$contractors = Database::fetchAll(
    "SELECT u.id, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS name
     FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'contractor' AND u.status = 'active'
     ORDER BY u.id ASC"
);

if ($contractors === []) {
    fwrite(STDERR, "No contractors found.\n");
    exit(1);
}

$uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'site-photos';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0775, true);
}

$created = 0;

try {
    Database::beginTransaction();

    foreach ($contractors as $contractor) {
        $userId = (int)$contractor['id'];
        $name = trim((string)$contractor['name']) ?: ('Contractor #' . $userId);
        echo "Contractor #{$userId} {$name}\n";

        $projects = Database::fetchAll(
            "SELECT p.id, p.name, p.pct_complete, p.current_milestone
             FROM projects p
             WHERE p.contractor_id = ?
                OR EXISTS (
                    SELECT 1 FROM project_assignments pa
                    WHERE pa.project_id = p.id AND pa.user_id = ? AND pa.status = 'active'
                )
             ORDER BY p.id ASC
             LIMIT 4",
            [$userId, $userId]
        );

        if ($projects === []) {
            echo "  no projects\n";
            continue;
        }

        foreach ($projects as $i => $project) {
            $projectId = (int)$project['id'];
            $base = (int)percentage($project['pct_complete'] ?? 0);
            $specs = [
                [
                    'delta' => max(2, min(8, 100 - $base)),
                    'milestone' => 'P10 demo — foundation / earthworks package',
                    'summary' => 'Phase 10 demo progress: earthworks and foundation prep advanced on site. Survey control points verified and formwork setup started on bay A.',
                    'note' => 'Demo contractor progress note for UAT. Site photo attached for evidence trail.',
                    'weather' => 'Clear morning, mild afternoon',
                    'blockers' => '',
                    'days_ago' => 12 + $i,
                ],
                [
                    'delta' => max(1, min(6, 100 - $base - 5)),
                    'milestone' => 'P10 demo — structural frame progress',
                    'summary' => 'Phase 10 demo progress: vertical elements and floor slab prep continued. Rebar inspection complete for columns C1-C6 with site photo evidence.',
                    'note' => 'Demo update submitted for consultant and manager visibility.',
                    'weather' => 'Partly cloudy, dry working window',
                    'blockers' => 'Awaiting material delivery for waterproofing membrane next week.',
                    'days_ago' => 5 + $i,
                ],
            ];

            foreach ($specs as $s => $spec) {
                $tag = 'P10 demo progress note for UAT';
                if ($s === 1) {
                    $tag = 'Demo update submitted for consultant';
                }
                $exists = Database::fetch(
                    "SELECT id FROM project_progress_updates
                     WHERE project_id = ? AND submitted_by = ? AND note LIKE ?
                     LIMIT 1",
                    [$projectId, $userId, '%' . substr($tag, 0, 28) . '%']
                );
                if ($exists) {
                    echo "  skip progress project {$projectId} slot {$s}\n";
                    continue;
                }

                $old = max(0, $base - ($s === 0 ? $spec['delta'] + 3 : 2));
                $new = min(100, $old + $spec['delta']);
                $relPath = 'uploads/site-photos/p10-demo-c' . $userId . '-p' . $projectId . '-s' . $s . '.txt';
                $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                if (!is_file($abs)) {
                    file_put_contents($abs, "Phase 10 demo site evidence\nProject {$projectId}\nContractor {$userId}\n");
                }
                $size = is_file($abs) ? (int)filesize($abs) : 256;

                // Optional media library row for evidence pickers
                $mediaId = null;
                try {
                    Database::query(
                        "INSERT INTO media_library
                            (filename, original_name, title, path, url, type, size, width, height, extension, alt_text, caption, uploaded_by, folder, source)
                         VALUES (?, ?, ?, ?, ?, 'text/plain', ?, NULL, NULL, 'txt', ?, '', ?, 'site-photos', 'contractor_progress')",
                        [
                            basename($relPath),
                            'P10 Demo Evidence ' . $projectId . '-' . $s . '.txt',
                            'P10 demo site evidence ' . $projectId,
                            $relPath,
                            $relPath,
                            $size,
                            'Demo progress evidence',
                            $userId,
                        ]
                    );
                    $mediaId = (int)Database::lastInsertId();
                } catch (Throwable) {
                    $mediaId = null;
                }

                Database::query(
                    "INSERT INTO project_progress_updates
                        (project_id, submitted_by, old_progress, new_progress, current_milestone, note, photo_path, media_id, weather_note, work_summary, blockers, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', DATE_SUB(NOW(), INTERVAL ? DAY))",
                    [
                        $projectId,
                        $userId,
                        $old,
                        $new,
                        $spec['milestone'],
                        $spec['note'],
                        $relPath,
                        $mediaId,
                        $spec['weather'],
                        $spec['summary'],
                        $spec['blockers'] !== '' ? $spec['blockers'] : null,
                        $spec['days_ago'],
                    ]
                );

                // Bring project progress to latest demo value without inventing payments
                if ($new > $base) {
                    Database::query(
                        'UPDATE projects SET pct_complete = ?, current_milestone = COALESCE(?, current_milestone) WHERE id = ?',
                        [$new, $spec['milestone'], $projectId]
                    );
                    $base = $new;
                }

                $created++;
                echo "  + progress project {$projectId}: {$old}% -> {$new}%\n";
            }
        }
    }

    Database::commit();
} catch (Throwable $e) {
    Database::rollBack();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\nCreated progress updates: {$created}\nDone.\n";
