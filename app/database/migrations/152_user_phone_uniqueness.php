<?php

class Migration152UserPhoneUniqueness
{
    public function up(PDO $pdo): void
    {
        if (!$this->tableExists($pdo, 'users') || !$this->columnExists($pdo, 'users', 'phone')) {
            return;
        }

        $users = $pdo->query('SELECT id, phone FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
        $seen = [];
        foreach ($users as $user) {
            $id = (int)$user['id'];
            $normalised = $this->normalisePhone((string)($user['phone'] ?? ''));
            if ($normalised === '') {
                $stmt = $pdo->prepare('UPDATE users SET phone = NULL WHERE id = ?');
                $stmt->execute([$id]);
                continue;
            }

            if (isset($seen[$normalised])) {
                throw new RuntimeException("Duplicate user phone number found: {$normalised}. Resolve duplicate users before adding the unique index.");
            }

            $seen[$normalised] = $id;
            $stmt = $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?');
            $stmt->execute([$normalised, $id]);
        }

        if (!$this->indexExists($pdo, 'users', 'uniq_users_phone')) {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_users_phone (phone)');
        }
    }

    public function down(PDO $pdo): void
    {
        if ($this->indexExists($pdo, 'users', 'uniq_users_phone')) {
            $pdo->exec('ALTER TABLE users DROP INDEX uniq_users_phone');
        }
    }

    private function normalisePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (preg_match('/^0([17]\d{8})$/', $digits, $match)) {
            return '+254' . $match[1];
        }
        if (preg_match('/^254([17]\d{8})$/', $digits, $match)) {
            return '+254' . $match[1];
        }
        if (preg_match('/^([17]\d{8})$/', $digits, $match)) {
            return '+254' . $match[1];
        }

        return $phone;
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
