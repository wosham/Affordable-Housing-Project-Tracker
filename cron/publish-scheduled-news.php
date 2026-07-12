<?php
/**
 * Cron: Publish Scheduled News Articles
 * ──────────────────────────────────────
 * Transitions news articles from status='scheduled' to status='published'
 * when their scheduled_for datetime has passed.
 *
 * Recommended cron schedule (every 5 minutes):
 *   *\/5 * * * * php /path/to/Trans-Nzoia-Affordable-Housing/cron/publish-scheduled-news.php
 *
 * XAMPP (Windows) Task Scheduler equivalent:
 *   Run: C:\xampp\php\php.exe C:\xampp\htdocs\Trans-Nzoia-Affordable-Housing\cron\publish-scheduled-news.php
 *   Trigger: Every 5 minutes
 */

declare(strict_types=1);

// This script must only be called from CLI or a privileged cron context.
if (PHP_SAPI !== 'cli' && !defined('RUNNING_CRON')) {
    http_response_code(403);
    exit('Forbidden');
}

define('APP_SKIP_SESSION', true);

require_once dirname(__DIR__) . '/app/core/bootstrap.php';

$now    = date('Y-m-d H:i:s');
$result = ['published' => 0, 'errors' => 0, 'skipped' => 0];

try {
    // Find all articles that are scheduled and whose publish time has arrived.
    $due = Database::fetchAll(
        "SELECT id, title, slug
           FROM news_articles
          WHERE status = 'scheduled'
            AND scheduled_for IS NOT NULL
            AND scheduled_for <= ?
            AND deleted_at IS NULL",
        [$now]
    );

    if ($due === []) {
        echo '[' . $now . '] No scheduled articles due for publishing.' . PHP_EOL;
        exit(0);
    }

    foreach ($due as $article) {
        $id = (int)$article['id'];
        try {
            Database::query(
                "UPDATE news_articles
                    SET status       = 'published',
                        published_at = scheduled_for,
                        updated_at   = NOW()
                  WHERE id     = ?
                    AND status = 'scheduled'",
                [$id]
            );

            Logger::log('publish-scheduled', 'news_articles', $id, [
                'title' => (string)($article['title'] ?? ''),
                'slug'  => (string)($article['slug']  ?? ''),
            ]);

            echo '[' . $now . '] Published article #' . $id . ': ' . ($article['title'] ?? '') . PHP_EOL;
            $result['published']++;
        } catch (Throwable $e) {
            Logger::error('Scheduled article publish failed', [
                'article_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            echo '[' . $now . '] ERROR publishing article #' . $id . ': ' . $e->getMessage() . PHP_EOL;
            $result['errors']++;
        }
    }
} catch (Throwable $e) {
    Logger::error('Scheduled news cron failed', ['error' => $e->getMessage()]);
    echo '[' . $now . '] FATAL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo '[' . $now . '] Done — published: ' . $result['published']
    . ', errors: ' . $result['errors'] . PHP_EOL;

exit($result['errors'] > 0 ? 1 : 0);
