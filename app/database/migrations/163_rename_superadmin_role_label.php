<?php

class Migration_163_RenameSuperadminRoleLabel
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("UPDATE `roles` SET `name` = 'County Director' WHERE `slug` = 'superadmin'");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("UPDATE `roles` SET `name` = 'Super Admin' WHERE `slug` = 'superadmin'");
    }
}
