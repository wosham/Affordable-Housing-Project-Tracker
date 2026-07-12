<?php

class Migration146ClerkQualityEvidence
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'quality_tests', 'required_result', 'VARCHAR(180) NULL AFTER result');
        $this->addColumn($pdo, 'quality_tests', 'actual_result', 'VARCHAR(180) NULL AFTER required_result');
        $this->addColumn($pdo, 'quality_tests', 'clerk_observation', 'TEXT NULL AFTER actual_result');
        $this->addColumn($pdo, 'quality_tests', 'evidence_media_id', 'INT UNSIGNED NULL AFTER document_path');
        $this->addColumn($pdo, 'quality_tests', 'verification_status', "ENUM('pending','passed','failed','queried') NOT NULL DEFAULT 'pending' AFTER evidence_media_id");
        $this->addColumn($pdo, 'quality_tests', 'verified_by', 'INT UNSIGNED NULL AFTER verification_status');
        $this->addColumn($pdo, 'quality_tests', 'verified_at', 'DATETIME NULL AFTER verified_by');

        $this->addColumn($pdo, 'inspection_test_plans', 'inspection_area', 'VARCHAR(180) NULL AFTER activity');
        $this->addColumn($pdo, 'inspection_test_plans', 'clerk_notes', 'TEXT NULL AFTER outcome');
        $this->addColumn($pdo, 'inspection_test_plans', 'evidence_media_id', 'INT UNSIGNED NULL AFTER document_path');
        $this->addColumn($pdo, 'inspection_test_plans', 'inspection_status', "ENUM('pending','passed','failed','rework-required') NOT NULL DEFAULT 'pending' AFTER evidence_media_id");
        $this->addColumn($pdo, 'inspection_test_plans', 'verified_by', 'INT UNSIGNED NULL AFTER inspection_status');
        $this->addColumn($pdo, 'inspection_test_plans', 'verified_at', 'DATETIME NULL AFTER verified_by');

        $this->addColumn($pdo, 'hs_incidents', 'immediate_action', 'TEXT NULL AFTER corrective_action');
        $this->addColumn($pdo, 'hs_incidents', 'lost_time_hours', 'DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER immediate_action');
        $this->addColumn($pdo, 'hs_incidents', 'evidence_media_id', 'INT UNSIGNED NULL AFTER attachment_path');

        $this->addColumn($pdo, 'non_conformance_reports', 'ncr_reference', 'VARCHAR(80) NULL AFTER id');
        $this->addColumn($pdo, 'non_conformance_reports', 'location_on_site', 'VARCHAR(180) NULL AFTER raised_date');
        $this->addColumn($pdo, 'non_conformance_reports', 'target_close_date', 'DATE NULL AFTER corrective_action');
        $this->addColumn($pdo, 'non_conformance_reports', 'evidence_media_id', 'INT UNSIGNED NULL AFTER target_close_date');
        $this->addColumn($pdo, 'non_conformance_reports', 'updated_by', 'INT UNSIGNED NULL AFTER consultant_documents_checked');

        $this->addColumn($pdo, 'defects', 'defect_reference', 'VARCHAR(80) NULL AFTER id');
        $this->addColumn($pdo, 'defects', 'evidence_media_id', 'INT UNSIGNED NULL AFTER photo_path');
        $this->addColumn($pdo, 'defects', 'rectification_notes', 'TEXT NULL AFTER status');
        $this->addColumn($pdo, 'defects', 'verified_fixed_by', 'INT UNSIGNED NULL AFTER rectification_notes');
        $this->addColumn($pdo, 'defects', 'verified_fixed_at', 'DATETIME NULL AFTER verified_fixed_by');

        $this->addColumn($pdo, 'site_meeting_minutes', 'meeting_type', 'VARCHAR(60) NOT NULL DEFAULT "site" AFTER project_id');
        $this->addColumn($pdo, 'site_meeting_minutes', 'chairperson', 'VARCHAR(150) NULL AFTER venue');
        $this->addColumn($pdo, 'site_meeting_minutes', 'next_meeting_date', 'DATE NULL AFTER action_status');

        $this->addColumn($pdo, 'documents', 'clerk_document_type', 'VARCHAR(60) NULL AFTER category');
        $this->addColumn($pdo, 'documents', 'site_record_date', 'DATE NULL AFTER version');
        $this->addColumn($pdo, 'documents', 'linked_record_type', 'VARCHAR(60) NULL AFTER site_record_date');
        $this->addColumn($pdo, 'documents', 'linked_record_id', 'INT UNSIGNED NULL AFTER linked_record_type');
        $this->addColumn($pdo, 'documents', 'review_required', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_confidential');

        $this->addColumn($pdo, 'ipcs', 'clerk_verification_comment', 'TEXT NULL AFTER certification_comment');
        $this->addColumn($pdo, 'ipcs', 'clerk_checklist_json', 'TEXT NULL AFTER clerk_verification_comment');
        $this->addColumn($pdo, 'ipcs', 'clerk_verified_by', 'INT UNSIGNED NULL AFTER clerk_checklist_json');
        $this->addColumn($pdo, 'ipcs', 'clerk_verified_at', 'DATETIME NULL AFTER clerk_verified_by');

        $this->addColumn($pdo, 'ipc_lines', 'clerk_verified_qty', 'DECIMAL(12,3) NULL AFTER cumulative_qty');
        $this->addColumn($pdo, 'ipc_lines', 'clerk_variance_qty', 'DECIMAL(12,3) NULL AFTER clerk_verified_qty');
        $this->addColumn($pdo, 'ipc_lines', 'clerk_note', 'TEXT NULL AFTER amount');
        $this->addColumn($pdo, 'ipc_lines', 'clerk_verified_by', 'INT UNSIGNED NULL AFTER clerk_note');
        $this->addColumn($pdo, 'ipc_lines', 'clerk_verified_at', 'DATETIME NULL AFTER clerk_verified_by');

        $this->addIndex($pdo, 'quality_tests', 'idx_quality_tests_clerk_verification', 'project_id, verification_status, test_date');
        $this->addIndex($pdo, 'inspection_test_plans', 'idx_itp_clerk_status', 'project_id, inspection_status, inspection_date');
        $this->addIndex($pdo, 'documents', 'idx_documents_clerk_type', 'project_id, clerk_document_type, site_record_date');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_clerk_verified', 'project_id, status, clerk_verified_at');
    }

    public function down(PDO $pdo): void
    {
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        $stmt->execute([$table, $column]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndex(PDO $pdo, string $table, string $name, string $columns): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
        $stmt->execute([$table, $name]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$name} ({$columns})");
        }
    }
}
