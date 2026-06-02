<?php

class Migration_096_AnnouncementsStatusDefault
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE announcements MODIFY status ENUM('draft','published','archived') DEFAULT 'draft'");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE announcements MODIFY status ENUM('draft','published','archived') DEFAULT 'published'");
    }
}
