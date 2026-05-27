<?php

class Migration_069_ApprovalWorkflowStatuses
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE ipcs MODIFY status ENUM('draft','submitted','clerk-endorsed','certified','endorsed','approved','rejected','paid') DEFAULT 'draft'");
        $pdo->exec("CREATE INDEX idx_ipcs_status ON ipcs (status)");
        $pdo->exec("CREATE INDEX idx_variations_status ON variations (status)");
        $pdo->exec("CREATE INDEX idx_eot_status ON eot_requests (status)");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE ipcs MODIFY status ENUM('draft','submitted','clerk-endorsed','certified','approved','paid') DEFAULT 'draft'");
        $pdo->exec("DROP INDEX idx_ipcs_status ON ipcs");
        $pdo->exec("DROP INDEX idx_variations_status ON variations");
        $pdo->exec("DROP INDEX idx_eot_status ON eot_requests");
    }
}
