<?php

class Migration133ConsultantTechnicalReviews
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'programme_tasks', 'consultant_review_status', "VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER notes");
        $this->addColumn($pdo, 'programme_tasks', 'consultant_review_note', 'TEXT NULL AFTER consultant_review_status');
        $this->addColumn($pdo, 'programme_tasks', 'consultant_reviewed_by', 'INT UNSIGNED NULL AFTER consultant_review_note');
        $this->addColumn($pdo, 'programme_tasks', 'consultant_reviewed_at', 'DATETIME NULL AFTER consultant_reviewed_by');
        $this->addColumn($pdo, 'shop_drawings', 'review_note', 'TEXT NULL AFTER review_date');
        $this->addColumn($pdo, 'shop_drawings', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_consultant_review', 'project_id, consultant_review_status, planned_end');
        $this->addIndex($pdo, 'material_approvals', 'idx_material_status_date', 'status, submitted_date');
        $this->addIndex($pdo, 'shop_drawings', 'idx_shop_drawings_status_date', 'status, submitted_date');

        $this->seedTechnicalRecords($pdo);
    }

    public function down(PDO $pdo): void
    {
    }

    private function seedTechnicalRecords(PDO $pdo): void
    {
        $consultantId = $this->userIdByRole($pdo, 'consultant');
        $contractorId = $this->userIdByRole($pdo, 'contractor');
        if ($consultantId <= 0 || $contractorId <= 0) {
            return;
        }

        $projects = $pdo->query('SELECT id, name FROM projects ORDER BY id ASC LIMIT 4')->fetchAll(PDO::FETCH_ASSOC);
        if ($projects === []) {
            return;
        }

        $materialInsert = $pdo->prepare("
            INSERT INTO material_approvals (project_id, material, specification, submitted_by, submitted_date, status, notes)
            SELECT ?, ?, ?, ?, ?, ?, ?
            WHERE NOT EXISTS (
                SELECT 1 FROM material_approvals WHERE project_id = ? AND material = ? LIMIT 1
            )
        ");
        $drawingInsert = $pdo->prepare("
            INSERT INTO shop_drawings (project_id, drawing_no, title, submitted_by, submitted_date, revision, status, document_path)
            SELECT ?, ?, ?, ?, ?, ?, ?, ?
            WHERE NOT EXISTS (
                SELECT 1 FROM shop_drawings WHERE project_id = ? AND drawing_no = ? LIMIT 1
            )
        ");
        $taskInsert = $pdo->prepare("
            INSERT INTO programme_tasks
                (project_id, task_name, planned_start, planned_end, pct_complete, status, sort_order, critical_path, notes, assigned_to, consultant_review_status)
            SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            WHERE NOT EXISTS (
                SELECT 1 FROM programme_tasks WHERE project_id = ? AND task_name = ? LIMIT 1
            )
        ");

        $materials = [
            ['Structural concrete mix C25/30', 'Cube strength results, mix design certificate and delivery batch records.', 'pending'],
            ['Roofing sheets and accessories', 'Gauge certificate, manufacturer warranty and colour schedule.', 'pending'],
            ['UPVC drainage fittings', 'Material data sheet, sample approval and supplier certificate.', 'approved'],
            ['External door ironmongery', 'Schedule, sample photos and lockset certificate.', 'rejected'],
        ];
        $drawings = [
            ['SD-STR-021', 'Foundation reinforcement layout', 'B', 'under-review'],
            ['SD-ARC-014', 'Typical wet area tiling details', 'A', 'resubmit'],
            ['SD-MEP-009', 'Electrical conduit coordination plan', 'A', 'under-review'],
            ['SD-EXT-006', 'External works drainage falls', 'C', 'approved'],
        ];
        $tasks = [
            ['Foundation inspection hold point', '-21 days', '+10 days', 45, 'in_progress', 1, 1, 'Awaiting consultant review of planned hold point sequence.', 'pending'],
            ['Roof truss installation review', '-7 days', '+25 days', 18, 'pending', 2, 0, 'Confirm material approvals before work starts.', 'pending'],
            ['Internal finishes sample room', '-30 days', '-5 days', 70, 'delayed', 3, 0, 'Sample room completion needs follow-up.', 'needs-revision'],
            ['External drainage inspection', '+5 days', '+40 days', 0, 'not_started', 4, 1, 'Review dependencies before field execution.', 'pending'],
        ];

        foreach ($projects as $index => $project) {
            $projectId = (int)$project['id'];
            $material = $materials[$index % count($materials)];
            $drawing = $drawings[$index % count($drawings)];
            $task = $tasks[$index % count($tasks)];

            $materialInsert->execute([
                $projectId,
                $material[0],
                $material[1],
                $contractorId,
                date('Y-m-d', strtotime('-' . (3 + $index) . ' days')),
                $material[2],
                $material[2] === 'rejected' ? 'Revise specification and resubmit for review.' : null,
                $projectId,
                $material[0],
            ]);
            $drawingInsert->execute([
                $projectId,
                $drawing[0],
                $drawing[1],
                $contractorId,
                date('Y-m-d', strtotime('-' . (5 + $index) . ' days')),
                $drawing[2],
                $drawing[3],
                null,
                $projectId,
                $drawing[0],
            ]);
            $taskInsert->execute([
                $projectId,
                $task[0],
                date('Y-m-d', strtotime($task[1])),
                date('Y-m-d', strtotime($task[2])),
                $task[3],
                $task[4],
                $task[5],
                $task[6],
                $task[7],
                $consultantId,
                $task[8],
                $projectId,
                $task[0],
            ]);
        }

        $projectIds = array_map('intval', $pdo->query('SELECT id FROM projects ORDER BY id ASC LIMIT 8')->fetchAll(PDO::FETCH_COLUMN));
        if ($projectIds !== []) {
            $in = implode(',', $projectIds);
            $pdo->exec("
                UPDATE boq_items
                SET review_status = CASE
                        WHEN certified_qty > quantity OR paid_qty > certified_qty THEN 'needs-review'
                        WHEN review_status IS NULL OR review_status = '' THEN 'pending'
                        ELSE review_status
                    END,
                    risk_status = CASE
                        WHEN certified_qty > quantity THEN 'high'
                        WHEN paid_qty > certified_qty THEN 'critical'
                        WHEN risk_status IS NULL OR risk_status = '' THEN 'normal'
                        ELSE risk_status
                    END
                WHERE project_id IN ({$in})
            ");
        }
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            return;
        }
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function addIndex(PDO $pdo, string $table, string $index, string $columns): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            return;
        }
        $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');
        $stmt->execute([$column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?');
        $stmt->execute([$index]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function userIdByRole(PDO $pdo, string $role): int
    {
        $stmt = $pdo->prepare("
            SELECT u.id
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE r.slug = ? AND u.status = 'active'
            ORDER BY u.id ASC
            LIMIT 1
        ");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }
}
