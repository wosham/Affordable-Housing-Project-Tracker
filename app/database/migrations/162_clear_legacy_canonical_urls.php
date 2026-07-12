<?php

class Migration_162_ClearLegacyCanonicalUrls
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            "UPDATE `cms_pages`
             SET `canonical_url` = NULL
             WHERE `canonical_url` LIKE '%housing.transnzoia.go.ke%'"
        );
    }

    public function down(\PDO $pdo): void
    {
        // Canonical URLs are derived from APP_CANONICAL_URL/APP_URL.
    }
}
