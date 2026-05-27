<?php
class Migration_058_NewsArticleTags
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS news_article_tags (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            article_id  INT UNSIGNED NOT NULL,
            tag_id      INT UNSIGNED NOT NULL,
            FOREIGN KEY (article_id) REFERENCES news_articles(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id)     REFERENCES news_tags(id)     ON DELETE CASCADE,
            UNIQUE KEY uq_article_tag (article_id, tag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS news_article_tags;"); }
}
