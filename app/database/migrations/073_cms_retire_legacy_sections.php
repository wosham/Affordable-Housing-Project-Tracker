<?php

class Migration_073_CmsRetireLegacySections
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE cms_sections cs
            INNER JOIN cms_pages cp ON cp.id = cs.page_id
            SET cs.is_visible = 0
            WHERE cp.template = 'legal'
              AND cs.section_key NOT IN ('legal_document', 'source_snapshot')
        ");

        $pdo->exec("
            UPDATE cms_sections cs
            INNER JOIN cms_pages cp ON cp.id = cs.page_id
            SET cs.is_visible = 0
            WHERE cp.slug = 'home'
              AND cs.section_key IN ('kpis', 'featured_projects', 'constituency_coverage', 'news_intro', 'apply_cta')
              AND (cs.content_json IS NULL OR cs.content_json = '' OR cs.content_json LIKE '%\"body\":\"\"%')
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE cms_sections cs
            INNER JOIN cms_pages cp ON cp.id = cs.page_id
            SET cs.is_visible = 1
            WHERE cp.template = 'legal'
              AND cs.section_key NOT IN ('legal_document', 'source_snapshot')
        ");
    }
}
