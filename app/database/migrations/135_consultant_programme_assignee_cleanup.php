<?php

class Migration135ConsultantProgrammeAssigneeCleanup
{
    public function up(PDO $pdo): void
    {
        $consultantId = $this->userIdByRole($pdo, 'consultant');
        if ($consultantId <= 0) {
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE programme_tasks pt
            JOIN users u ON u.id = pt.assigned_to
            JOIN roles r ON r.id = u.role_id
            SET pt.assigned_to = ?
            WHERE r.slug = 'superadmin'
              AND pt.project_id IN (
                SELECT project_id
                FROM project_assignments
                WHERE user_id = ? AND status = 'active'
              )
        ");
        $stmt->execute([$consultantId, $consultantId]);
    }

    public function down(PDO $pdo): void
    {
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
