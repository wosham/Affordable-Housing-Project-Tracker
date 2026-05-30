<?php

class Migration_084_NewsFormatBackfill
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE news_articles na
            LEFT JOIN news_categories nc ON nc.id = na.category_id
            SET na.post_format = CASE
                WHEN nc.slug = 'field-reports' THEN 'field_report'
                WHEN nc.slug = 'policy' THEN 'policy'
                WHEN nc.slug = 'official' THEN 'announcement'
                WHEN nc.slug = 'construction' THEN 'progress_report'
                ELSE 'article'
            END
            WHERE na.post_format IS NULL OR na.post_format = '' OR na.post_format = 'article'
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("UPDATE news_articles SET post_format = 'article'");
    }
}
