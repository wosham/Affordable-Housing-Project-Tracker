<?php

/**
 * Phase 10 production reseed:
 * - Replace demo-labelled progress with professional site records
 * - Use real site photos (copied from gallery assets)
 * - Seed light open submissions (RFI, materials, drawings, EOT, VO)
 * - No invented payment history
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 10 production reseed ===\n";
$root = dirname(__DIR__);
$photoDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'site-photos';
if (!is_dir($photoDir)) {
    mkdir($photoDir, 0775, true);
}

$gallerySources = [
    $root . '/uploads/gallery/maili-tatu-1.jpg',
    $root . '/uploads/gallery/maili-tatu-2.jpg',
    $root . '/uploads/gallery/maili-tatu-3.jpg',
    $root . '/uploads/gallery/modern-market.jpg',
    $root . '/uploads/gallery/suam-ahp.jpg',
    $root . '/uploads/gallery/matunda-ahp-1.jpg',
    $root . '/uploads/gallery/kitale-ex-prison.jpeg',
    $root . '/uploads/news/cherangany-progress.jpg',
    $root . '/uploads/news/community-engagement.jpg',
];
$gallerySources = array_values(array_filter($gallerySources, 'is_file'));
if ($gallerySources === []) {
    fwrite(STDERR, "No source gallery images found.\n");
    exit(1);
}

function copy_site_photo(string $source, string $destRel, string $photoDir, string $root): string
{
    $destAbs = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $destRel);
    $destFolder = dirname($destAbs);
    if (!is_dir($destFolder)) {
        mkdir($destFolder, 0775, true);
    }
    if (!is_file($destAbs)) {
        copy($source, $destAbs);
    }
    return $destRel;
}

$progressTemplates = [
    [
        'milestone' => 'Foundation and ground beams — Block A',
        'summary' => 'Foundation excavation completed for Block A bays 1–3. Ground beams cast to level, with curing blankets in place. Setting-out points re-checked against the survey control network.',
        'note' => 'Request consultant inspection of ground beam reinforcement before the next pour window.',
        'weather' => 'Dry morning, light cloud afternoon',
        'blockers' => '',
    ],
    [
        'milestone' => 'Structural frame and floor slab prep',
        'summary' => 'Column formwork and reinforcement advanced for ground floor. Slab edge formwork prepared on the north wing. Site access routes maintained clear for concrete delivery.',
        'note' => 'Progress supports the current programme window for vertical elements.',
        'weather' => 'Clear and suitable for concrete works',
        'blockers' => 'Waterproofing membrane delivery expected next week for basement interfaces.',
    ],
    [
        'milestone' => 'Superstructure walls and openings',
        'summary' => 'Blockwork raised to lintel level on units A1–A4. Window openings formed to approved schedule. Temporary propping inspected daily.',
        'note' => 'Coordination with M&E first-fix will commence once wall lines are signed off.',
        'weather' => 'Partly cloudy, dry working conditions',
        'blockers' => '',
    ],
];

$createdProgress = 0;
$updatedProgress = 0;
$seededSubmissions = 0;

try {
    Database::beginTransaction();

    // Remove old placeholder txt evidence progress notes (demo-labelled)
    $old = Database::fetchAll(
        "SELECT id, photo_path FROM project_progress_updates
         WHERE note LIKE '%demo%' OR note LIKE '%UAT%' OR note LIKE '%P10%'
            OR current_milestone LIKE '%P10%' OR current_milestone LIKE '%demo%'
            OR work_summary LIKE '%Phase 10 demo%' OR work_summary LIKE '%P10 demo%'
            OR photo_path LIKE '%.txt' OR photo_path LIKE '%p10-demo%'"
    );
    foreach ($old as $row) {
        Database::query('DELETE FROM project_progress_updates WHERE id = ?', [(int)$row['id']]);
        $path = (string)($row['photo_path'] ?? '');
        if ($path !== '' && str_ends_with(strtolower($path), '.txt')) {
            $abs = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }
    echo 'Removed old demo progress rows: ' . count($old) . "\n";

    // Clean orphan demo media text files entries if any
    try {
        Database::query(
            "DELETE FROM media_library WHERE path LIKE '%p10-demo%' OR original_name LIKE 'P10 Demo%' OR source = 'contractor_progress' AND extension = 'txt'"
        );
    } catch (Throwable) {
    }

    $contractors = Database::fetchAll(
        "SELECT u.id, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS name
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE r.slug = 'contractor' AND u.status = 'active' ORDER BY u.id ASC"
    );

    $photoIndex = 0;
    foreach ($contractors as $contractor) {
        $userId = (int)$contractor['id'];
        $name = trim((string)$contractor['name']) ?: ('Contractor #' . $userId);
        echo "Contractor #{$userId} {$name}\n";

        $projects = Database::fetchAll(
            "SELECT p.id, p.name, p.pct_complete, p.current_milestone
             FROM projects p
             WHERE p.contractor_id = ?
                OR EXISTS (SELECT 1 FROM project_assignments pa WHERE pa.project_id = p.id AND pa.user_id = ? AND pa.status = 'active')
             ORDER BY p.id ASC LIMIT 4",
            [$userId, $userId]
        );

        foreach ($projects as $pi => $project) {
            $projectId = (int)$project['id'];
            $base = (int)percentage($project['pct_complete'] ?? 0);

            foreach ($progressTemplates as $ti => $tpl) {
                // Skip third template on last projects to keep volume sensible
                if ($ti === 2 && $pi > 1) {
                    continue;
                }
                $exists = Database::fetch(
                    "SELECT id FROM project_progress_updates
                     WHERE project_id = ? AND submitted_by = ? AND current_milestone = ?
                     LIMIT 1",
                    [$projectId, $userId, $tpl['milestone']]
                );
                if ($exists) {
                    echo "  skip progress: {$tpl['milestone']} (project {$projectId})\n";
                    continue;
                }

                $src = $gallerySources[$photoIndex % count($gallerySources)];
                $photoIndex++;
                $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION)) ?: 'jpg';
                $rel = 'uploads/site-photos/progress-p' . $projectId . '-u' . $userId . '-t' . $ti . '.' . $ext;
                copy_site_photo($src, $rel, $photoDir, $root);

                $oldPct = max(0, min(100, $base - (8 - $ti * 2)));
                $newPct = min(100, $oldPct + 5 + $ti);
                if ($newPct <= $oldPct) {
                    $newPct = min(100, $oldPct + 3);
                }

                $mediaId = null;
                try {
                    $size = is_file($root . '/' . $rel) ? (int)filesize($root . '/' . $rel) : 0;
                    Database::query(
                        "INSERT INTO media_library
                            (filename, original_name, title, path, url, type, size, width, height, extension, alt_text, caption, uploaded_by, folder, source)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, '', ?, 'site-photos', 'contractor_progress')",
                        [
                            basename($rel),
                            basename($rel),
                            $tpl['milestone'] . ' evidence',
                            $rel,
                            $rel,
                            $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg'),
                            $size,
                            $ext,
                            $tpl['milestone'] . ' site evidence',
                            $userId,
                        ]
                    );
                    $mediaId = (int)Database::lastInsertId();
                } catch (Throwable $e) {
                    echo '  media warn: ' . $e->getMessage() . "\n";
                }

                Database::query(
                    "INSERT INTO project_progress_updates
                        (project_id, submitted_by, old_progress, new_progress, current_milestone, note, photo_path, media_id, weather_note, work_summary, blockers, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', DATE_SUB(NOW(), INTERVAL ? DAY))",
                    [
                        $projectId,
                        $userId,
                        $oldPct,
                        $newPct,
                        $tpl['milestone'],
                        $tpl['note'],
                        $rel,
                        $mediaId,
                        $tpl['weather'],
                        $tpl['summary'],
                        $tpl['blockers'] !== '' ? $tpl['blockers'] : null,
                        4 + $ti * 5 + $pi,
                    ]
                );
                $createdProgress++;

                if ($newPct > $base) {
                    Database::query(
                        'UPDATE projects SET pct_complete = ?, current_milestone = ? WHERE id = ?',
                        [$newPct, $tpl['milestone'], $projectId]
                    );
                    $base = $newPct;
                }
                echo "  + progress project {$projectId}: {$oldPct}% -> {$newPct}%\n";
            }

            // --- Open submissions seed (light, professional) ---
            // RFI
            $rfiSubject = 'Clarification on lintel reinforcement — Block A openings';
            $rfiExists = Database::fetch(
                'SELECT id FROM rfis WHERE project_id = ? AND raised_by = ? AND subject = ? LIMIT 1',
                [$projectId, $userId, $rfiSubject]
            );
            if (!$rfiExists) {
                try {
                    Database::query(
                        "INSERT INTO rfis (project_id, raised_by, subject, description, raised_date, status, created_at)
                         VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'open', DATE_SUB(NOW(), INTERVAL 3 DAY))",
                        [
                            $projectId,
                            $userId,
                            $rfiSubject,
                            'Please confirm bar schedule at lintel level for window openings A2–A4 against the latest IFC set.',
                        ]
                    );
                    $seededSubmissions++;
                    echo "  + RFI project {$projectId}\n";
                } catch (Throwable $e) {
                    // try alternate columns if schema differs
                    try {
                        Database::query(
                            "INSERT INTO rfis (project_id, raised_by, subject, question, raised_date, status)
                             VALUES (?, ?, ?, ?, CURDATE(), 'open')",
                            [$projectId, $userId, $rfiSubject, 'Please confirm bar schedule at lintel level for window openings A2–A4.']
                        );
                        $seededSubmissions++;
                        echo "  + RFI (alt) project {$projectId}\n";
                    } catch (Throwable $e2) {
                        echo '  RFI skip: ' . $e2->getMessage() . "\n";
                    }
                }
            }

            // Material pending
            $matName = 'OPC 42.5N cement — Block A package';
            if (!Database::fetch('SELECT id FROM material_approvals WHERE project_id = ? AND material = ? LIMIT 1', [$projectId, $matName])) {
                try {
                    Database::query(
                        "INSERT INTO material_approvals (project_id, material, specification, submitted_by, submitted_date, status, notes)
                         VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'pending', ?)",
                        [
                            $projectId,
                            $matName,
                            'KS EAS 18-1 Ordinary Portland Cement 42.5N, bagged, current batch certificates attached.',
                            $userId,
                            'Submitted for consultant review prior to bulk pour.',
                        ]
                    );
                    $seededSubmissions++;
                    echo "  + material project {$projectId}\n";
                } catch (Throwable $e) {
                    echo '  material skip: ' . $e->getMessage() . "\n";
                }
            }

            // Shop drawing under review
            $drawNo = 'SD-STR-' . str_pad((string)$projectId, 3, '0', STR_PAD_LEFT);
            if (!Database::fetch('SELECT id FROM shop_drawings WHERE project_id = ? AND drawing_no = ? LIMIT 1', [$projectId, $drawNo])) {
                try {
                    Database::query(
                        "INSERT INTO shop_drawings (project_id, drawing_no, title, submitted_by, submitted_date, revision, status)
                         VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'A', 'under-review')",
                        [
                            $projectId,
                            $drawNo,
                            'Ground beam reinforcement layout — Block A',
                            $userId,
                        ]
                    );
                    $seededSubmissions++;
                    echo "  + drawing project {$projectId}\n";
                } catch (Throwable $e) {
                    echo '  drawing skip: ' . $e->getMessage() . "\n";
                }
            }

            // One pending EOT per contractor (first project only)
            if ($pi === 0) {
                $eotReason = 'Prolonged rainfall delayed foundation excavation and temporary works installation on the east access.';
                if (!Database::fetch('SELECT id FROM eot_requests WHERE project_id = ? AND submitted_by = ? AND reason = ? LIMIT 1', [$projectId, $userId, $eotReason])) {
                    $maxEot = (int)(Database::fetch('SELECT COALESCE(MAX(eot_number),0) AS m FROM eot_requests WHERE project_id = ?', [$projectId])['m'] ?? 0);
                    try {
                        Database::query(
                            "INSERT INTO eot_requests (project_id, submitted_by, eot_number, days_requested, reason, status, consultant_review_status, created_at)
                             VALUES (?, ?, ?, 10, ?, 'pending', 'pending', DATE_SUB(NOW(), INTERVAL 6 DAY))",
                            [$projectId, $userId, $maxEot + 1, $eotReason]
                        );
                        $seededSubmissions++;
                        echo "  + EOT project {$projectId}\n";
                    } catch (Throwable $e) {
                        echo '  EOT skip: ' . $e->getMessage() . "\n";
                    }
                }

                $voDesc = 'Additional temporary works for deep excavation shoring at Block A east retaining line.';
                if (!Database::fetch('SELECT id FROM variations WHERE project_id = ? AND description = ? LIMIT 1', [$projectId, $voDesc])) {
                    $maxVo = (int)(Database::fetch('SELECT COALESCE(MAX(vo_number),0) AS m FROM variations WHERE project_id = ?', [$projectId])['m'] ?? 0);
                    try {
                        Database::query(
                            "INSERT INTO variations (project_id, submitted_by, vo_number, description, reason, amount, impact_on_time_days, status, consultant_review_status, created_at)
                             VALUES (?, ?, ?, ?, ?, 980000.00, 4, 'pending', 'pending', DATE_SUB(NOW(), INTERVAL 5 DAY))",
                            [
                                $projectId,
                                $userId,
                                $maxVo + 1,
                                $voDesc,
                                'Site condition and temporary works requirement confirmed on inspection.',
                            ]
                        );
                        $seededSubmissions++;
                        echo "  + variation project {$projectId}\n";
                    } catch (Throwable $e) {
                        echo '  VO skip: ' . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }

    Database::commit();
} catch (Throwable $e) {
    Database::rollBack();
    fwrite(STDERR, 'Reseed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\nSummary:\n";
echo "  progress created: {$createdProgress}\n";
echo "  submissions seeded: {$seededSubmissions}\n";
echo "Done.\n";
