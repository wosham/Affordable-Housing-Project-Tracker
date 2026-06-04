<?php

class Migration139ContractorDashboardIndexes
{
    public function up(PDO $pdo): void
    {
        $this->addIndex($pdo, 'projects', 'idx_projects_contractor_status', 'contractor_id, status');
        $this->addIndex($pdo, 'payments', 'idx_payments_project_date', 'project_id, payment_date');
        $this->addIndex($pdo, 'rfis', 'idx_rfis_project_user_status', 'project_id, raised_by, status');
        $this->addIndex($pdo, 'material_approvals', 'idx_material_project_user_status', 'project_id, submitted_by, status');
        $this->addIndex($pdo, 'shop_drawings', 'idx_shop_project_user_status', 'project_id, submitted_by, status');
        $this->addIndex($pdo, 'eot_requests', 'idx_eot_project_user_status', 'project_id, submitted_by, status');
        $this->addIndex($pdo, 'variations', 'idx_variations_project_user_status', 'project_id, submitted_by, status');
        $this->addIndex($pdo, 'material_deliveries', 'idx_material_deliveries_project_date', 'project_id, delivery_date');
        $this->addIndex($pdo, 'equipment_register', 'idx_equipment_project_dates', 'project_id, date_on_site, date_off_site');
        $this->addIndex($pdo, 'labour_register', 'idx_labour_project_date', 'project_id, diary_date');
    }

    public function down(PDO $pdo): void
    {
    }

    private function addIndex(PDO $pdo, string $table, string $index, string $columns): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            return;
        }
        $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?');
        $stmt->execute([$index]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }
}
