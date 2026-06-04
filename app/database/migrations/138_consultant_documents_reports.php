<?php

class Migration138ConsultantDocumentsReports
{
    public function up(PDO $pdo): void
    {
        foreach (['documents', 'site_diaries'] as $table) {
            $this->addColumn($pdo, $table, 'consultant_review_status', "VARCHAR(30) NOT NULL DEFAULT 'pending'");
            $this->addColumn($pdo, $table, 'consultant_review_note', 'TEXT NULL');
            $this->addColumn($pdo, $table, 'consultant_reviewed_by', 'INT UNSIGNED NULL');
            $this->addColumn($pdo, $table, 'consultant_reviewed_at', 'DATETIME NULL');
            $this->addColumn($pdo, $table, 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

        $this->addColumn($pdo, 'site_diaries', 'report_title', 'VARCHAR(180) NULL');
        $this->addColumn($pdo, 'site_diaries', 'weather_summary', 'VARCHAR(180) NULL');

        $this->addIndex($pdo, 'documents', 'idx_documents_consultant_review', 'project_id, consultant_review_status, created_at');
        $this->addIndex($pdo, 'documents', 'idx_documents_category_review', 'category, consultant_review_status');
        $this->addIndex($pdo, 'site_diaries', 'idx_site_diaries_consultant_review', 'project_id, consultant_review_status, diary_date');

        $this->seedRecords($pdo);
    }

    public function down(PDO $pdo): void
    {
    }

    private function seedRecords(PDO $pdo): void
    {
        $consultantId = $this->userIdByRole($pdo, 'consultant');
        $clerkId = $this->userIdByRole($pdo, 'clerk');
        $managerId = $this->userIdByRole($pdo, 'manager');
        if ($consultantId <= 0) {
            return;
        }

        $projects = $pdo->query('SELECT id, name FROM projects ORDER BY id ASC LIMIT 4')->fetchAll(PDO::FETCH_ASSOC);
        if ($projects === []) {
            return;
        }

        $document = $pdo->prepare(
            "INSERT INTO documents (project_id, uploaded_by, category, filename, original_name, size, version, description, is_confidential)
             SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?
             WHERE NOT EXISTS (SELECT 1 FROM documents WHERE project_id = ? AND original_name = ? LIMIT 1)"
        );
        $diary = $pdo->prepare(
            "INSERT INTO site_diaries (project_id, diary_date, report_title, weather_summary, work_done, issues_raised, next_day_plan, recorded_by)
             SELECT ?, ?, ?, ?, ?, ?, ?, ?
             WHERE NOT EXISTS (SELECT 1 FROM site_diaries WHERE project_id = ? AND diary_date = ? LIMIT 1)"
        );

        $documentRows = [
            ['drawing', 'Structural ground floor column layout.pdf', 'Current structural layout for ground floor column setting out.', '1.2', 684240],
            ['shop-drawing', 'Roof truss fabrication submission.pdf', 'Contractor shop drawing package for consultant review.', '1.0', 512880],
            ['spec', 'Finishes and material specification.pdf', 'Specification extract covering finishes, samples and workmanship tolerances.', '2.0', 438900],
            ['report', 'Monthly progress narrative.pdf', 'Progress narrative covering programme, quality and site coordination items.', '1.0', 360420],
        ];
        $diaryRows = [
            ['Foundation and substructure works', 'Cloudy morning with light afternoon showers', 'Excavation trimming, blinding preparation and reinforcement checks continued at active blocks.', 'Access route softened after rain; contractor instructed to maintain safe access.', 'Complete reinforcement inspection request and prepare concrete delivery plan.'],
            ['Walling and services coordination', 'Clear and dry conditions', 'Masonry walling continued alongside conduit routing and setting out checks.', 'Minor rework required where conduit conflicted with wall chase depth.', 'Close rework points and submit updated inspection request.'],
            ['Drainage and external works', 'Warm afternoon with no rainfall', 'Drainage trench excavation and pipe bedding preparation advanced near the inspection chamber line.', 'Bedding thickness needs confirmation before backfilling.', 'Hold backfill until levels and bedding are jointly checked.'],
            ['Finishes sample room', 'Dry conditions', 'Sample room skim coat, door frame adjustment and paint sample comparison were reviewed.', 'Finish sample board needs final consultant sign-off.', 'Submit updated finish board and protect completed samples.'],
        ];

        foreach ($projects as $index => $project) {
            $projectId = (int)$project['id'];
            $doc = $documentRows[$index % count($documentRows)];
            $report = $diaryRows[$index % count($diaryRows)];
            $safeName = strtolower(preg_replace('/[^a-z0-9]+/i', '-', pathinfo($doc[1], PATHINFO_FILENAME))) . '.pdf';
            $document->execute([$projectId, $managerId > 0 ? $managerId : $consultantId, $doc[0], 'uploads/documents/' . $safeName, $doc[1], $doc[4], $doc[3], $doc[2], 0, $projectId, $doc[1]]);
            $date = date('Y-m-d', strtotime('-' . (2 + $index) . ' days'));
            $diary->execute([$projectId, $date, $report[0], $report[1], $report[2], $report[3], $report[4], $clerkId > 0 ? $clerkId : $consultantId, $projectId, $date]);
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
