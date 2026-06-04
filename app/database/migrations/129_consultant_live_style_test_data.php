<?php

class Migration129ConsultantLiveStyleTestData
{
    public function up(PDO $pdo): void
    {
        $superadminId = $this->userIdByRole($pdo, 'superadmin') ?: 1;
        $managerId = $this->ensureUser($pdo, 'manager', 'patrick.manager@transnzoia.go.ke', 'Patrick', 'Wekesa', 'Project Manager', 'Delivery Unit');
        $contractorId = $this->ensureUser($pdo, 'contractor', 'contractor@kitalebuild.co.ke', 'David', 'Barasa', 'Contractor Representative', 'Construction');
        $clerkId = $this->ensureUser($pdo, 'clerk', 'clerk.works@transnzoia.go.ke', 'Lilian', 'Naliaka', 'Clerk of Works', 'Site Operations');
        $financeId = $this->ensureUser($pdo, 'finance', 'finance@transnzoia.go.ke', 'Grace', 'Kiptoo', 'Finance Officer', 'Finance');
        $internId = $this->ensureUser($pdo, 'intern', 'intern.site@transnzoia.go.ke', 'Brian', 'Wafula', 'Site Intern', 'Technical Support');
        $consultantId = $this->userIdByRole($pdo, 'consultant');

        $this->normaliseProjects($pdo, $consultantId, $managerId, $contractorId, $superadminId);
        $this->normaliseBoqItems($pdo);
        $this->normaliseIpcs($pdo, $contractorId, $consultantId);
        $this->normaliseApprovalHistory($pdo, $clerkId, $consultantId, $managerId, $financeId);
    }

    public function down(PDO $pdo): void
    {
    }

    private function ensureUser(PDO $pdo, string $roleSlug, string $email, string $firstName, string $lastName, string $jobTitle, string $department): int
    {
        $roleId = $this->roleId($pdo, $roleSlug);
        if ($roleId <= 0) {
            return 0;
        }

        $existing = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $existing->execute([$email]);
        $id = (int)$existing->fetchColumn();

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET first_name = ?, last_name = ?, job_title = ?, department = ?, role_id = ?, status = 'active'
                WHERE id = ?
            ");
            $stmt->execute([$firstName, $lastName, $jobTitle, $department, $roleId, $id]);
            return $id;
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password_hash, job_title, department, role_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            password_hash('Password123!', PASSWORD_DEFAULT),
            $jobTitle,
            $department,
            $roleId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function roleId(PDO $pdo, string $slug): int
    {
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return (int)$stmt->fetchColumn();
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

    private function normaliseProjects(PDO $pdo, int $consultantId, int $managerId, int $contractorId, int $superadminId): void
    {
        $wardStmt = $pdo->prepare('SELECT id FROM wards WHERE constituency_id = ? ORDER BY id ASC LIMIT 1');
        $projects = $pdo->query('SELECT id, constituency_id FROM projects ORDER BY id ASC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
        $projectUpdate = $pdo->prepare("
            UPDATE projects
            SET consultant_id = ?,
                contractor_id = ?,
                contractor_name = 'Kitale Build Contractors Ltd',
                ward_id = COALESCE(ward_id, ?),
                status = CASE WHEN status = 'planning' THEN 'active' ELSE status END
            WHERE id = ?
        ");
        $assign = $pdo->prepare("
            INSERT INTO project_assignments
                (project_id, user_id, role, assignment_type, scope, status, start_date, is_primary, notes, assigned_by, updated_by)
            VALUES
                (?, ?, ?, 'project', ?, 'active', CURDATE(), ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                role = VALUES(role),
                scope = VALUES(scope),
                status = 'active',
                is_primary = VALUES(is_primary),
                notes = VALUES(notes),
                updated_by = VALUES(updated_by)
        ");

        foreach ($projects as $index => $project) {
            $wardStmt->execute([(int)$project['constituency_id']]);
            $wardId = (int)$wardStmt->fetchColumn();
            $projectUpdate->execute([$consultantId, $contractorId, $wardId > 0 ? $wardId : null, (int)$project['id']]);

            if ($consultantId > 0) {
                $assign->execute([(int)$project['id'], $consultantId, 'consultant', 'certification-review', $index === 0 ? 1 : 0, 'Consultant assigned for technical and IPC certification review.', $superadminId, $superadminId]);
            }
            if ($managerId > 0) {
                $assign->execute([(int)$project['id'], $managerId, 'manager', 'delivery-management', $index === 0 ? 1 : 0, 'Project manager assigned for delivery oversight.', $superadminId, $superadminId]);
            }
        }
    }

    private function normaliseBoqItems(PDO $pdo): void
    {
        $replacements = [
            'Demo seed: site establishment and temporary works' => 'Site establishment and temporary works',
            'Demo seed: reinforced concrete strip foundations' => 'Reinforced concrete strip foundations',
            'Demo seed: masonry walling to housing blocks' => 'Masonry walling to housing blocks',
            'Foundation excavation and cart away' => 'Foundation excavation and cart away',
            'Reinforced concrete columns and beams' => 'Reinforced concrete columns and beams',
            'Block walling and plaster preparation' => 'Block walling and plaster preparation',
        ];
        $stmt = $pdo->prepare('UPDATE boq_items SET description = ? WHERE description = ?');
        foreach ($replacements as $old => $new) {
            $stmt->execute([$new, $old]);
        }

        $pdo->exec("UPDATE project_assignments SET notes = 'Consultant assigned for technical and IPC certification review.' WHERE notes LIKE '%demo%' OR notes LIKE '%Phase 48%'");
    }

    private function normaliseIpcs(PDO $pdo, int $contractorId, int $consultantId): void
    {
        if ($contractorId > 0) {
            $pdo->prepare('UPDATE ipcs SET contractor_id = ? WHERE ipc_number BETWEEN 41 AND 43')->execute([$contractorId]);
        }
        if ($consultantId > 0) {
            $pdo->prepare("
                UPDATE ipcs
                SET certified_by = CASE WHEN status = 'certified' THEN ? ELSE certified_by END,
                    certification_comment = CASE
                        WHEN certification_comment LIKE '%demo%' OR certification_comment LIKE '%Phase 48%' THEN 'Certified after review of quantities, amounts and supporting records.'
                        ELSE certification_comment
                    END,
                    rejection_reason = CASE
                        WHEN rejection_reason LIKE '%demo%' OR rejection_reason LIKE '%Phase 48%' THEN 'Returned for correction after quantity review.'
                        ELSE rejection_reason
                    END
                WHERE ipc_number BETWEEN 41 AND 43
            ")->execute([$consultantId]);
        }
    }

    private function normaliseApprovalHistory(PDO $pdo, int $clerkId, int $consultantId, int $managerId, int $financeId): void
    {
        $updates = [
            ['endorsed', 1, $clerkId, 'Site verification completed and quantities checked against site records.'],
            ['certified', 2, $consultantId, 'Certified after review of quantities, amounts and supporting records.'],
            ['approved', 4, $managerId, 'Approved for onward processing.'],
            ['paid', 5, $financeId, 'Payment processing completed.'],
        ];

        $stmt = $pdo->prepare("
            UPDATE ipc_approvals ia
            JOIN ipcs i ON i.id = ia.ipc_id
            SET ia.action_by = ?,
                ia.comments = ?
            WHERE i.ipc_number BETWEEN 41 AND 43
              AND ia.action = ?
              AND ia.step = ?
        ");

        foreach ($updates as [$action, $step, $actorId, $comment]) {
            if ($actorId > 0) {
                $stmt->execute([$actorId, $comment, $action, $step]);
            }
        }

        $cleanup = $pdo->prepare("
            UPDATE ipc_approvals
            SET comments = REPLACE(REPLACE(comments, 'Phase 48 demo ', ''), 'demo ', '')
            WHERE comments LIKE '%demo%' OR comments LIKE '%Phase 48%'
        ");
        $cleanup->execute();
    }
}
