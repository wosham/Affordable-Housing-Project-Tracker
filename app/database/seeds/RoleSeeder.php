<?php
class RoleSeeder
{
    public function run(\PDO $pdo): void
    {
        $roles = [
            ['Super Admin',        'superadmin',  '#163300'],
            ['Programme Manager',  'manager',     '#1a4a00'],
            ['Consultant',         'consultant',  '#0a3d62'],
            ['Contractor',         'contractor',  '#6a1a00'],
            ['Clerk of Works',     'clerk',       '#4a3500'],
            ['Finance Officer',    'finance',     '#1a3a4a'],
            ['Intern',             'intern',      '#2a2a2a'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO roles (name, slug, color) VALUES (?, ?, ?)");
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
        echo "✓ Roles seeded.\n";
    }
}
