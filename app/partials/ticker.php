<?php
$tickerItems = [];
$tickerTypeIcons = [
    'approval' => 'fa-circle-check',
    'deadline' => 'fa-calendar-days',
    'info' => 'fa-bullhorn',
    'milestone' => 'fa-flag-checkered',
    'notice' => 'fa-bullhorn',
    'progress' => 'fa-helmet-safety',
    'report' => 'fa-file-lines',
    'tender' => 'fa-gavel',
];
$normaliseTickerItems = static function (mixed $items) use ($tickerTypeIcons): array {
    if (!is_array($items)) {
        return [];
    }

    $normalised = [];
    foreach ($items as $item) {
        if (is_string($item)) {
            $item = ['text' => $item];
        }
        if (!is_array($item)) {
            continue;
        }

        $text = trim((string)($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
        if ($text === '') {
            continue;
        }

        $type = strtolower(trim((string)($item['type'] ?? 'info')));
        $icon = trim((string)($item['icon'] ?? ($tickerTypeIcons[$type] ?? 'fa-bullhorn')));
        $icon = preg_match('/^fa-[a-z0-9-]+$/', $icon) ? $icon : 'fa-bullhorn';

        $normalised[] = [
            'icon' => $icon,
            'text' => $text,
            'url' => trim((string)($item['url'] ?? $item['href'] ?? $item['link'] ?? '')),
        ];
    }

    return $normalised;
};

try {
    $cmsTickerItems = $normaliseTickerItems(class_exists('CmsLoader') ? CmsLoader::settingJson('ticker_items_json', []) : []);

    if (class_exists('Database')) {
        $tickerRows = Database::fetchAll(
            "SELECT title, slug, is_visible, ticker_text, ticker_url
             FROM news_articles
             WHERE status = 'published'
               AND deleted_at IS NULL
               AND show_in_ticker = 1
               AND (published_at IS NULL OR published_at <= NOW())
               AND (ticker_expires_at IS NULL OR ticker_expires_at >= NOW())
             ORDER BY ticker_priority DESC, COALESCE(published_at, created_at) DESC, id DESC
             LIMIT 10"
        );

        foreach ($tickerRows as $item) {
            $customUrl = trim((string)($item['ticker_url'] ?? ''));
            $tickerArticleUrl = !empty($item['slug']) && (int)($item['is_visible'] ?? 0) === 1
                ? 'news-article.php?id=' . rawurlencode((string)$item['slug'])
                : '';
            $tickerItems[] = [
                'icon' => 'fa-bullhorn',
                'text' => (string)($item['ticker_text'] ?: ($item['title'] ?? '')),
                'url' => $customUrl !== '' ? $customUrl : $tickerArticleUrl,
            ];
        }

        if ($tickerItems === []) {
            $tickerItems = $cmsTickerItems;
        }

        if (count($tickerItems) < 4) {
            $projects = Database::fetchAll(
                "SELECT name, pct_complete
                 FROM projects
                 WHERE status IN ('active', 'ongoing', 'in_progress', 'planning')
                 ORDER BY COALESCE(updated_at, created_at) DESC, id DESC
                 LIMIT 4"
            );
            foreach ($projects as $project) {
                $progress = (int)($project['pct_complete'] ?? 0);
                $tickerItems[] = [
                    'icon' => 'fa-helmet-safety',
                    'text' => trim((string)($project['name'] ?? 'Project update') . ($progress > 0 ? ' - ' . $progress . '% complete' : '')),
                    'url' => 'projects.php',
                ];
            }
        }

        if (count($tickerItems) < 4) {
            $news = Database::fetchAll(
                "SELECT title, slug
                 FROM news_articles
                 WHERE status = 'published'
                   AND is_visible = 1
                   AND deleted_at IS NULL
                   AND COALESCE(post_format, 'article') <> 'announcement'
                   AND (published_at IS NULL OR published_at <= NOW())
                 ORDER BY COALESCE(published_at, created_at) DESC, id DESC
                 LIMIT 4"
            );
            foreach ($news as $item) {
                $tickerItems[] = [
                    'icon' => 'fa-newspaper',
                    'text' => (string)($item['title'] ?? ''),
                    'url' => !empty($item['slug']) ? 'news-article.php?id=' . rawurlencode((string)$item['slug']) : 'news.php',
                ];
            }
        }
    }
} catch (Throwable) {
    $tickerItems = [];
}

$tickerItems = array_values(array_filter($tickerItems, static fn (array $item): bool => trim((string)($item['text'] ?? '')) !== ''));

$tickerItems = array_slice($tickerItems, 0, 10);
if ($tickerItems === []) {
    return;
}

$tickerLoop = array_merge($tickerItems, $tickerItems);
$toUrl = static fn (string $path): string => function_exists('public_url') ? public_url($path) : $path;
?>
<div class="ticker-bar" aria-label="Live programme field updates" role="region">
  <div class="ticker-label" aria-hidden="true"><i class="fa-solid fa-tower-broadcast"></i> LIVE</div>
  <div class="ticker-track-wrap">
    <div class="ticker-track" id="tickerTrack" tabindex="0">
<?php foreach ($tickerLoop as $index => $item): ?>
<?php if ($index > 0): ?>
      <span class="ticker-sep" aria-hidden="true">&bull;</span>
<?php endif; ?>
<?php $url = trim((string)($item['url'] ?? '')); ?>
<?php if ($url !== ''): ?>
      <a class="ticker-item" href="<?= htmlspecialchars($toUrl($url), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid <?= htmlspecialchars((string)($item['icon'] ?? 'fa-bullhorn'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i> <?= htmlspecialchars((string)$item['text'], ENT_QUOTES, 'UTF-8') ?></a>
<?php else: ?>
      <span class="ticker-item"><i class="fa-solid <?= htmlspecialchars((string)($item['icon'] ?? 'fa-bullhorn'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i> <?= htmlspecialchars((string)$item['text'], ENT_QUOTES, 'UTF-8') ?></span>
<?php endif; ?>
<?php endforeach; ?>
    </div>
  </div>
</div>
