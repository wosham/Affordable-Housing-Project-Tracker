<?php

class Migration_166_NewsTickerControls
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'news_articles', 'show_in_ticker', "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_visible");
        $this->addColumn($pdo, 'news_articles', 'ticker_text', "VARCHAR(255) NULL AFTER show_in_ticker");
        $this->addColumn($pdo, 'news_articles', 'ticker_url', "VARCHAR(500) NULL AFTER ticker_text");
        $this->addColumn($pdo, 'news_articles', 'ticker_expires_at', "DATETIME NULL AFTER ticker_url");
        $this->addColumn($pdo, 'news_articles', 'ticker_priority', "INT NOT NULL DEFAULT 0 AFTER ticker_expires_at");

        $this->addIndex($pdo, 'news_articles', 'idx_news_ticker', 'show_in_ticker, ticker_priority, published_at');

        $pdo->exec("
            UPDATE news_articles
            SET show_in_ticker = 1,
                is_visible = 0,
                is_featured = 0,
                ticker_text = COALESCE(NULLIF(ticker_text, ''), title)
            WHERE post_format = 'announcement'
              AND deleted_at IS NULL
        ");
    }

    public function down(\PDO $pdo): void
    {
        $this->dropIndex($pdo, 'news_articles', 'idx_news_ticker');
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function addIndex(\PDO $pdo, string $table, string $index, string $columns): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
        }
    }

    private function dropIndex(\PDO $pdo, string $table, string $index): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->exec("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
}
