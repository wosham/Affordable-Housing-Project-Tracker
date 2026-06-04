<?php

class Migration125ConsultantDashboardIndexes
{
    public function up(PDO $pdo): void
    {
        $this->addIndex($pdo, 'projects', 'idx_projects_consultant_status', 'consultant_id, status');
        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_user_project_status', 'user_id, project_id, status');
        $this->addIndex($pdo, 'material_approvals', 'idx_material_approvals_project_status_date', 'project_id, status, submitted_date');
        $this->addIndex($pdo, 'shop_drawings', 'idx_shop_drawings_project_status_date', 'project_id, status, submitted_date');
        $this->addIndex($pdo, 'variations', 'idx_variations_project_status_created', 'project_id, status, created_at');
        $this->addIndex($pdo, 'eot_requests', 'idx_eot_project_status_created', 'project_id, status, created_at');
        $this->addIndex($pdo, 'defects', 'idx_defects_project_status_date', 'project_id, status, raised_date');
        $this->addIndex($pdo, 'quality_tests', 'idx_quality_tests_project_result_date', 'project_id, pass_fail, test_date');
        $this->addIndex($pdo, 'inspection_test_plans', 'idx_itp_project_date', 'project_id, inspection_date');
        $this->addIndex($pdo, 'non_conformance_reports', 'idx_ncr_project_status_date', 'project_id, status, raised_date');
        $this->addIndex($pdo, 'documents', 'idx_documents_project_created', 'project_id, created_at');
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            ['documents', 'idx_documents_project_created'],
            ['non_conformance_reports', 'idx_ncr_project_status_date'],
            ['inspection_test_plans', 'idx_itp_project_date'],
            ['quality_tests', 'idx_quality_tests_project_result_date'],
            ['defects', 'idx_defects_project_status_date'],
            ['eot_requests', 'idx_eot_project_status_created'],
            ['variations', 'idx_variations_project_status_created'],
            ['shop_drawings', 'idx_shop_drawings_project_status_date'],
            ['material_approvals', 'idx_material_approvals_project_status_date'],
            ['project_assignments', 'idx_project_assignments_user_project_status'],
            ['projects', 'idx_projects_consultant_status'],
        ] as [$table, $index]) {
            $this->dropIndex($pdo, $table, $index);
        }
    }

    private function addIndex(PDO $pdo, string $table, string $index, string $columns): void
    {
        if (!$this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
        }
    }

    private function dropIndex(PDO $pdo, string $table, string $index): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
