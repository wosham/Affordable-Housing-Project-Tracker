<?php

class Migration137ConsultantQualityCentre
{
    public function up(PDO $pdo): void
    {
        foreach (['quality_tests', 'inspection_test_plans', 'non_conformance_reports', 'defects'] as $table) {
            $this->addColumn($pdo, $table, 'consultant_review_status', "VARCHAR(30) NOT NULL DEFAULT 'pending'");
            $this->addColumn($pdo, $table, 'consultant_review_note', 'TEXT NULL');
            $this->addColumn($pdo, $table, 'consultant_reviewed_by', 'INT UNSIGNED NULL');
            $this->addColumn($pdo, $table, 'consultant_reviewed_at', 'DATETIME NULL');
            $this->addColumn($pdo, $table, 'consultant_severity', "VARCHAR(30) NOT NULL DEFAULT 'minor'");
            $this->addColumn($pdo, $table, 'consultant_documents_checked', 'TINYINT(1) NOT NULL DEFAULT 0');
            $this->addColumn($pdo, $table, 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

        $this->addIndex($pdo, 'quality_tests', 'idx_quality_consultant_review', 'project_id, consultant_review_status, test_date');
        $this->addIndex($pdo, 'inspection_test_plans', 'idx_itp_consultant_review', 'project_id, consultant_review_status, inspection_date');
        $this->addIndex($pdo, 'non_conformance_reports', 'idx_ncr_consultant_review', 'project_id, consultant_review_status, raised_date');
        $this->addIndex($pdo, 'defects', 'idx_defects_consultant_review', 'project_id, consultant_review_status, raised_date');

        $this->seedQualityRecords($pdo);
    }

    public function down(PDO $pdo): void
    {
    }

    private function seedQualityRecords(PDO $pdo): void
    {
        $consultantId = $this->userIdByRole($pdo, 'consultant');
        $clerkId = $this->userIdByRole($pdo, 'clerk');
        $contractorId = $this->userIdByRole($pdo, 'contractor');
        if ($consultantId <= 0 || $clerkId <= 0) {
            return;
        }

        $projects = $pdo->query('SELECT id FROM projects ORDER BY id ASC LIMIT 4')->fetchAll(PDO::FETCH_COLUMN);
        if ($projects === []) {
            return;
        }

        $quality = $pdo->prepare("INSERT INTO quality_tests (project_id, test_type, test_date, location_on_site, result, pass_fail, lab_ref, tested_by) SELECT ?, ?, ?, ?, ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM quality_tests WHERE project_id = ? AND lab_ref = ? LIMIT 1)");
        $inspection = $pdo->prepare("INSERT INTO inspection_test_plans (project_id, activity, hold_point, inspection_date, inspected_by, outcome, witness_required) SELECT ?, ?, ?, ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM inspection_test_plans WHERE project_id = ? AND activity = ? LIMIT 1)");
        $ncr = $pdo->prepare("INSERT INTO non_conformance_reports (project_id, raised_by, raised_date, description, severity, root_cause, corrective_action, status) SELECT ?, ?, ?, ?, ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM non_conformance_reports WHERE project_id = ? AND description = ? LIMIT 1)");
        $defect = $pdo->prepare("INSERT INTO defects (project_id, raised_by, raised_date, location, description, severity, assigned_to, due_date, status) SELECT ?, ?, ?, ?, ?, ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM defects WHERE project_id = ? AND description = ? LIMIT 1)");

        $qualityRows = [
            ['Concrete cube strength', 'Ground floor columns', '28-day strength within specification', 'pass', 'QT-STR-101'],
            ['Compaction density', 'Access road subbase', 'Density below target in one section', 'fail', 'QT-CIV-102'],
            ['Slump test', 'Block A slab pour', 'Awaiting laboratory confirmation', 'pending', 'QT-CON-103'],
            ['Waterproofing test', 'Wet areas sample room', 'No leakage observed', 'pass', 'QT-FIN-104'],
        ];
        $inspectionRows = [
            ['Foundation reinforcement inspection', 'Hold before concrete pour', 'accepted', 1],
            ['Roof truss alignment inspection', 'Witness installation setting out', '', 1],
            ['Drainage trench inspection', 'Check invert levels before backfill', 'returned', 1],
            ['Internal finishes sample room', 'Review workmanship benchmark', 'accepted', 0],
        ];
        $ncrRows = [
            ['Honeycombing observed on column face requiring repair method statement.', 'major', 'Poor vibration during concrete placement.', 'Submit repair method and inspect before cover-up.', 'open'],
            ['Incorrect block bond observed in sample walling bay.', 'minor', 'Workmanship control gap.', 'Rework affected bay and brief masonry crew.', 'in-progress'],
            ['Drainage pipe bedding below specified thickness.', 'major', 'Material placement not verified before pipe laying.', 'Expose section and reinstate bedding.', 'open'],
            ['Unapproved paint sample used in one room.', 'minor', 'Material control issue.', 'Remove sample and submit approved finish board.', 'closed'],
        ];
        $defectRows = [
            ['Block A corridor', 'Cracked plaster at window reveal.', 'minor', 'open'],
            ['External drainage line', 'Ponding near inspection chamber.', 'major', 'in-progress'],
            ['Sample room', 'Door frame alignment requires adjustment.', 'minor', 'resolved'],
            ['Roof edge', 'Incomplete flashing termination.', 'major', 'open'],
        ];

        foreach ($projects as $index => $projectId) {
            $projectId = (int)$projectId;
            $q = $qualityRows[$index % count($qualityRows)];
            $i = $inspectionRows[$index % count($inspectionRows)];
            $n = $ncrRows[$index % count($ncrRows)];
            $d = $defectRows[$index % count($defectRows)];
            $quality->execute([$projectId, $q[0], date('Y-m-d', strtotime('-' . (3 + $index) . ' days')), $q[1], $q[2], $q[3], $q[4], $clerkId, $projectId, $q[4]]);
            $inspection->execute([$projectId, $i[0], $i[1], date('Y-m-d', strtotime('+' . ($index - 1) . ' days')), $consultantId, $i[2] ?: null, $i[3], $projectId, $i[0]]);
            $ncr->execute([$projectId, $clerkId, date('Y-m-d', strtotime('-' . (9 + $index) . ' days')), $n[0], $n[1], $n[2], $n[3], $n[4], $projectId, $n[0]]);
            $defect->execute([$projectId, $clerkId, date('Y-m-d', strtotime('-' . (5 + $index) . ' days')), $d[0], $d[1], $d[2], $contractorId > 0 ? $contractorId : null, date('Y-m-d', strtotime('+' . (7 + $index) . ' days')), $d[3], $projectId, $d[1]]);
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
        $stmt = $pdo->prepare('SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = ? AND u.status = "active" ORDER BY u.id ASC LIMIT 1');
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }
}
