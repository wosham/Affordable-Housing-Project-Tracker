<?php

class Migration136ConsultantContractDecisions
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'eot_requests', 'consultant_review_status', "VARCHAR(30) NOT NULL DEFAULT 'pending'");
        $this->addColumn($pdo, 'eot_requests', 'consultant_review_note', 'TEXT NULL');
        $this->addColumn($pdo, 'eot_requests', 'consultant_reviewed_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'eot_requests', 'consultant_reviewed_at', 'DATETIME NULL');
        $this->addColumn($pdo, 'eot_requests', 'consultant_recommended_days', 'SMALLINT UNSIGNED NULL');
        $this->addColumn($pdo, 'eot_requests', 'consultant_delay_category', 'VARCHAR(80) NULL');
        $this->addColumn($pdo, 'eot_requests', 'consultant_documents_checked', 'TINYINT(1) NOT NULL DEFAULT 0');

        $this->addColumn($pdo, 'variations', 'consultant_review_status', "VARCHAR(30) NOT NULL DEFAULT 'pending'");
        $this->addColumn($pdo, 'variations', 'consultant_review_note', 'TEXT NULL');
        $this->addColumn($pdo, 'variations', 'consultant_reviewed_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'variations', 'consultant_reviewed_at', 'DATETIME NULL');
        $this->addColumn($pdo, 'variations', 'consultant_recommended_amount', 'DECIMAL(15,2) NULL');
        $this->addColumn($pdo, 'variations', 'consultant_time_impact_days', 'SMALLINT UNSIGNED NULL');
        $this->addColumn($pdo, 'variations', 'consultant_cost_impact_status', "VARCHAR(30) NOT NULL DEFAULT 'moderate'");
        $this->addColumn($pdo, 'variations', 'consultant_documents_checked', 'TINYINT(1) NOT NULL DEFAULT 0');
        $this->addColumn($pdo, 'variations', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->addIndex($pdo, 'eot_requests', 'idx_eot_consultant_review', 'project_id, consultant_review_status, created_at');
        $this->addIndex($pdo, 'variations', 'idx_variations_consultant_review', 'project_id, consultant_review_status, created_at');

        $this->seedContractRecords($pdo);
    }

    public function down(PDO $pdo): void
    {
    }

    private function seedContractRecords(PDO $pdo): void
    {
        $consultantId = $this->userIdByRole($pdo, 'consultant');
        $contractorId = $this->userIdByRole($pdo, 'contractor');
        if ($consultantId <= 0 || $contractorId <= 0) {
            return;
        }

        $projects = $pdo->query('SELECT id FROM projects ORDER BY id ASC LIMIT 4')->fetchAll(PDO::FETCH_COLUMN);
        if ($projects === []) {
            return;
        }

        $eotInsert = $pdo->prepare("
            INSERT INTO eot_requests (project_id, submitted_by, eot_number, days_requested, reason, supporting_evidence, status, consultant_delay_category)
            SELECT ?, ?, ?, ?, ?, NULL, 'pending', ?
            WHERE NOT EXISTS (SELECT 1 FROM eot_requests WHERE project_id = ? AND eot_number = ? LIMIT 1)
        ");
        $variationInsert = $pdo->prepare("
            INSERT INTO variations (project_id, submitted_by, vo_number, description, reason, amount, impact_on_time_days, status, consultant_cost_impact_status)
            SELECT ?, ?, ?, ?, ?, ?, ?, 'pending', ?
            WHERE NOT EXISTS (SELECT 1 FROM variations WHERE project_id = ? AND vo_number = ? LIMIT 1)
        ");

        $eots = [
            [31, 14, 'Heavy rainfall affected access road compaction and delayed site deliveries.', 'weather'],
            [32, 10, 'Approved utility relocation works affected trench excavation sequence.', 'utilities'],
            [33, 21, 'Design clarification delayed reinforcement fabrication for foundation works.', 'design'],
            [34, 7, 'Material delivery delays affected roofing installation sequence.', 'materials'],
        ];
        $vars = [
            [41, 'Additional stormwater drain to protect lower access road.', 'Site drainage review identified extra protection works.', 1850000, 5, 'moderate'],
            [42, 'Revised boundary wall foundation detail.', 'Ground condition required adjustment to foundation depth.', 1250000, 3, 'moderate'],
            [43, 'Additional water storage plinths.', 'Community service requirements added storage points.', 940000, 0, 'low'],
            [44, 'Electrical intake cabinet relocation.', 'Utility provider position changed after site coordination.', 610000, 2, 'low'],
        ];

        foreach ($projects as $index => $projectId) {
            $projectId = (int)$projectId;
            $eot = $eots[$index % count($eots)];
            $var = $vars[$index % count($vars)];
            $eotInsert->execute([$projectId, $contractorId, $eot[0], $eot[1], $eot[2], $eot[3], $projectId, $eot[0]]);
            $variationInsert->execute([$projectId, $contractorId, $var[0], $var[1], $var[2], $var[3], $var[4], $var[5], $projectId, $var[0]]);
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
        $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = ? AND u.status = 'active' ORDER BY u.id ASC LIMIT 1");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }
}
