<?php

class Migration_074_CmsHideLegacyPageAliases
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE cms_pages
            SET status = 'hidden', template = 'legacy'
            WHERE slug IN ('privacy-policy', 'terms-of-use')
              AND (route_path IS NULL OR route_path = '')
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE cms_pages
            SET status = 'published'
            WHERE slug IN ('privacy-policy', 'terms-of-use')
        ");
    }
}
