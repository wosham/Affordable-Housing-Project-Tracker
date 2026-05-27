<?php
class UserSeeder
{
    public function run(\PDO $pdo): void
    {
        $superAdminRoleId = $pdo->query("SELECT id FROM roles WHERE slug='superadmin'")->fetchColumn();

        $stmt = $pdo->prepare("INSERT IGNORE INTO users
            (first_name, last_name, email, phone, password_hash, role_id, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')");

        // Super Admin — Moses Awuor (County Director)
        $stmt->execute([
            'Moses', 'Awuor',
            'director@transnzoia.go.ke',
            '+254530000001',
            password_hash('Admin@1234', PASSWORD_DEFAULT),
            $superAdminRoleId
        ]);

        echo "✓ Default Super Admin seeded: director@transnzoia.go.ke / Admin@1234\n";
        echo "  ⚠ CHANGE THIS PASSWORD IMMEDIATELY IN PRODUCTION.\n";
    }
}
