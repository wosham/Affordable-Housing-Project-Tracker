<?php

class Migration132NormalizeTestingUsersAndAccess
{
    private const PASSWORD = 'Password123!';

    private array $users = [
        ['manager', 'Patrick', 'Wekesa', 'patrick.wekesa.ahp@gmail.com', '+254712345101', 'Project Manager', 'Delivery Unit'],
        ['consultant', 'Teddy', 'Mwangi', 'consultant.teddy.ahp@gmail.com', '+254712345102', 'Supervising Consultant', 'Technical Review'],
        ['contractor', 'David', 'Barasa', 'david.barasa.ahp@gmail.com', '+254712345103', 'Contractor Representative', 'Construction'],
        ['clerk', 'Lilian', 'Naliaka', 'lilian.naliaka.ahp@gmail.com', '+254712345104', 'Clerk of Works', 'Site Operations'],
        ['finance', 'Grace', 'Kiptoo', 'grace.kiptoo.ahp@gmail.com', '+254712345105', 'Finance Officer', 'Finance'],
        ['intern', 'Brian', 'Wafula', 'brian.wafula.ahp@gmail.com', '+254712345106', 'Site Intern', 'Technical Support'],
        ['manager', 'Annet', 'Tasha', 'annet.tasha.ahp@gmail.com', '+254712345107', 'Programme Manager', 'Delivery Unit'],
        ['clerk', 'Samuel', 'Kibet', 'samuel.kibet.ahp@gmail.com', '+254712345108', 'Assistant Clerk of Works', 'Site Operations'],
        ['contractor', 'Mercy', 'Wanjala', 'mercy.wanjala.ahp@gmail.com', '+254712345109', 'Site Contractor Lead', 'Construction'],
        ['intern', 'Kevin', 'Otieno', 'kevin.otieno.ahp@gmail.com', '+254712345110', 'Site Data Intern', 'Technical Support'],
    ];

    public function up(PDO $pdo): void
    {
        $userIds = [];
        foreach ($this->users as $user) {
            [$role, $firstName, $lastName, $email, $phone, $jobTitle, $department] = $user;
            $userIds[$role][] = $this->ensureUser($pdo, $role, $firstName, $lastName, $email, $phone, $jobTitle, $department);
        }

        $this->wireProjectActors($pdo, $userIds);
    }

    public function down(PDO $pdo): void
    {
    }

    private function ensureUser(PDO $pdo, string $roleSlug, string $firstName, string $lastName, string $email, string $phone, string $jobTitle, string $department): int
    {
        $roleId = $this->roleId($pdo, $roleSlug);
        if ($roleId <= 0) {
            return 0;
        }

        $existingId = $this->findExistingUser($pdo, $roleSlug, $firstName, $lastName, $email);
        $passwordHash = password_hash(self::PASSWORD, PASSWORD_DEFAULT);

        if ($existingId > 0) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?,
                    password_hash = ?,
                    job_title = ?,
                    department = ?,
                    role_id = ?,
                    status = 'active',
                    is_public = 1
                WHERE id = ?
                  AND id NOT IN (SELECT protected.id FROM (SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'superadmin') protected)
            ");
            $stmt->execute([$firstName, $lastName, $email, $phone, $passwordHash, $jobTitle, $department, $roleId, $existingId]);
            return $existingId;
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, phone, password_hash, job_title, department, role_id, status, is_public)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1)
        ");
        $stmt->execute([$firstName, $lastName, $email, $phone, $passwordHash, $jobTitle, $department, $roleId]);

        return (int)$pdo->lastInsertId();
    }

    private function findExistingUser(PDO $pdo, string $roleSlug, string $firstName, string $lastName, string $email): int
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $id = (int)$stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }

        $legacyByRole = [
            'consultant' => ['mynetpaykenya@gmail.com'],
            'manager' => ['patrick.manager@transnzoia.go.ke', 'annettasha@gmail.com'],
            'contractor' => ['contractor@kitalebuild.co.ke'],
            'clerk' => ['clerk.works@transnzoia.go.ke'],
            'finance' => ['finance@transnzoia.go.ke'],
            'intern' => ['intern.site@transnzoia.go.ke'],
        ];

        foreach ($legacyByRole[$roleSlug] ?? [] as $legacyEmail) {
            $stmt->execute([$legacyEmail]);
            $id = (int)$stmt->fetchColumn();
            if ($id > 0) {
                return $id;
            }
        }

        $stmt = $pdo->prepare("
            SELECT u.id
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE r.slug = ?
              AND LOWER(u.first_name) = LOWER(?)
              AND LOWER(u.last_name) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$roleSlug, $firstName, $lastName]);
        return (int)$stmt->fetchColumn();
    }

    private function roleId(PDO $pdo, string $slug): int
    {
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return (int)$stmt->fetchColumn();
    }

    private function wireProjectActors(PDO $pdo, array $userIds): void
    {
        $projects = $pdo->query('SELECT id FROM projects ORDER BY id ASC LIMIT 8')->fetchAll(PDO::FETCH_COLUMN);
        if ($projects === []) {
            return;
        }

        $primaryManager = (int)($userIds['manager'][0] ?? 0);
        $secondaryManager = (int)($userIds['manager'][1] ?? $primaryManager);
        $consultant = (int)($userIds['consultant'][0] ?? 0);
        $primaryContractor = (int)($userIds['contractor'][0] ?? 0);
        $secondaryContractor = (int)($userIds['contractor'][1] ?? $primaryContractor);
        $primaryClerk = (int)($userIds['clerk'][0] ?? 0);
        $secondaryClerk = (int)($userIds['clerk'][1] ?? $primaryClerk);
        $primaryIntern = (int)($userIds['intern'][0] ?? 0);
        $secondaryIntern = (int)($userIds['intern'][1] ?? $primaryIntern);
        $superadmin = $this->superadminId($pdo);

        $projectUpdate = $pdo->prepare("
            UPDATE projects
            SET consultant_id = COALESCE(consultant_id, ?),
                contractor_id = COALESCE(contractor_id, ?),
                contractor_name = CASE WHEN contractor_name IS NULL OR contractor_name = '' THEN 'Kitale Build Contractors Ltd' ELSE contractor_name END
            WHERE id = ?
        ");

        foreach ($projects as $index => $projectId) {
            $projectId = (int)$projectId;
            $manager = $index % 2 === 0 ? $primaryManager : $secondaryManager;
            $contractor = $index % 2 === 0 ? $primaryContractor : $secondaryContractor;
            $clerk = $index % 2 === 0 ? $primaryClerk : $secondaryClerk;
            $intern = $index % 2 === 0 ? $primaryIntern : $secondaryIntern;

            if ($consultant > 0 || $contractor > 0) {
                $projectUpdate->execute([$consultant ?: null, $contractor ?: null, $projectId]);
            }

            $this->assign($pdo, $projectId, $manager, 'manager', 'delivery-management', 'Project manager assigned for delivery oversight.', $superadmin, true);
            $this->assign($pdo, $projectId, $consultant, 'consultant', 'certification-review', 'Consultant assigned for technical and IPC certification review.', $superadmin, true);
            $this->assign($pdo, $projectId, $contractor, 'contractor', 'site-delivery', 'Contractor representative assigned for project delivery records.', $superadmin, true);
            $this->assign($pdo, $projectId, $clerk, 'clerk', 'site-records', 'Clerk of Works assigned for site records and verification.', $superadmin, true);
            $this->assign($pdo, $projectId, $intern, 'intern', 'site-support', 'Site intern assigned for supervised data support.', $superadmin, false);
        }
    }

    private function assign(PDO $pdo, int $projectId, int $userId, string $role, string $scope, string $notes, int $assignedBy, bool $primary): void
    {
        if ($projectId <= 0 || $userId <= 0 || $assignedBy <= 0) {
            return;
        }

        $stmt = $pdo->prepare("
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
        $stmt->execute([$projectId, $userId, $role, $scope, $primary ? 1 : 0, $notes, $assignedBy, $assignedBy]);
    }

    private function superadminId(PDO $pdo): int
    {
        $id = (int)$pdo->query("
            SELECT u.id
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE r.slug = 'superadmin'
            ORDER BY u.id ASC
            LIMIT 1
        ")->fetchColumn();

        return $id;
    }
}
