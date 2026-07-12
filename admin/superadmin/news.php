<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_news';

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/news.php'));
    }

    $id = Security::cleanInt($_POST['article_id'] ?? 0);
    $action = Security::cleanString((string)($_POST['action'] ?? ''));

    try {
        if ($id <= 0 || !NewsArticle::findAdmin($id)) {
            throw new RuntimeException('News post could not be found.');
        }

        match ($action) {
            'publish' => NewsArticle::quickStatus($id, 'published'),
            'draft' => NewsArticle::quickStatus($id, 'draft'),
            'archive' => NewsArticle::quickStatus($id, 'archived'),
            'feature' => NewsArticle::toggleFeatured($id),
            'delete' => NewsArticle::softDelete($id),
            default => throw new RuntimeException('Unknown news action.'),
        };

        Logger::log($action, 'news_articles', $id);
        Session::flash('status', 'News post updated successfully.');
    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
    }

    Response::redirect(Url::to('admin/superadmin/news.php'));
}

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'category' => Security::cleanString((string)($_GET['category'] ?? '')),
    'post_format' => Security::cleanString((string)($_GET['post_format'] ?? '')),
    'featured' => Security::cleanString((string)($_GET['featured'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '');

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalPosts = NewsArticle::adminCount($filters);
$totalPages = max(1, (int)ceil($totalPosts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$posts = NewsArticle::adminList($filters, $perPage, $offset);
$showingFrom = $totalPosts > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($posts), $totalPosts);
$stats = NewsArticle::adminStats();
$categories = NewsArticle::categories();

$pageTitle = 'News Posts';
$pageDescription = 'Manage public news articles, announcements, reports, drafts and scheduled posts.';
$adminRole = 'superadmin';
$contentClass = 'sa-news-page';
$componentCss = ['news-admin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'News'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-news-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-newspaper" aria-hidden="true"></i> Public Publishing</span>
    <h2>News, announcements and reports</h2>
    <p>Create and manage the posts that feed the public News page. Draft safely, schedule updates, attach reports and control featured stories.</p>
  </div>
  <div class="sa-news-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('news.php')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Public News</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/news-format.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Post</a>
  </div>
</section>

<section class="stat-grid stat-grid--4" aria-label="News publishing summary">
  <?php sa_news_stat('fa-layer-group', $stats['total'] ?? 0, 'Total Posts', 'All active records'); ?>
  <?php sa_news_stat('fa-circle-check', $stats['published'] ?? 0, 'Published', 'Visible public posts'); ?>
  <?php sa_news_stat('fa-pen', $stats['drafts'] ?? 0, 'Drafts', 'Work in progress'); ?>
  <?php sa_news_stat('fa-calendar-clock', $stats['scheduled'] ?? 0, 'Scheduled', 'Timed publishing'); ?>
</section>

<section class="card sa-news-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Post Registry</h2>
      <p class="card__subtitle">Search by headline, filter by format/category/status, then edit or publish directly.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalPosts)) ?> records</span>
  </div>

  <form class="filter-bar sa-news-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Title, slug or excerpt..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (NewsArticle::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="post_format">Format</label><select class="form-select" id="post_format" name="post_format"><option value="">All formats</option><?php foreach (NewsArticle::FORMATS as $format => $meta): ?><option value="<?= Security::e($format) ?>" <?= (($filters['post_format'] ?? '') === $format) ? 'selected' : '' ?>><?= Security::e($meta['label']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="category">Category</label><select class="form-select" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= Security::e($category['slug']) ?>" <?= (($filters['category'] ?? '') === $category['slug']) ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table sa-news-table">
      <thead><tr><th>Post</th><th>Format</th><th>Status</th><th>Public Date</th><th>Views</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($posts === []): ?>
        <tr><td colspan="6"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span><strong class="empty-state__title">No posts match these filters</strong><span class="empty-state__text">Create a new post or clear the current filters.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($posts as $post): ?>
<?php
    $image = sa_news_asset_url($post['featured_image_path'] ?? $post['featured_image_url'] ?? '');
    $status = (string)($post['status'] ?? 'draft');
    $format = (string)($post['post_format'] ?? 'article');
?>
        <tr>
          <td>
            <div class="sa-news-post-cell">
              <span class="sa-news-thumb"><?php if ($image !== ''): ?><img src="<?= Security::e($image) ?>" alt=""><?php else: ?><i class="fa-solid <?= Security::e(NewsArticle::formatIcon($format)) ?>" aria-hidden="true"></i><?php endif; ?></span>
              <span><strong><?= Security::e($post['title']) ?></strong><small><?= Security::e($post['category_name'] ?: 'No category') ?><?php if ((int)($post['is_featured'] ?? 0) === 1): ?> Â· Featured<?php endif; ?></small></span>
            </div>
          </td>
          <td><span class="badge badge--info"><i class="fa-solid <?= Security::e(NewsArticle::formatIcon($format)) ?>" aria-hidden="true"></i> <?= Security::e(NewsArticle::formatLabel($format)) ?></span></td>
          <td><span class="badge <?= Security::e(status_badge_class($status)) ?>"><?= Security::e(status_label($status)) ?></span></td>
          <td><?= Security::e(format_datetime($post['published_at'] ?: $post['scheduled_for'] ?: $post['created_at'])) ?></td>
          <td><?= Security::e(format_number($post['views'] ?? 0)) ?></td>
          <td>
            <div class="data-table__actions">
              <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/news-editor.php?id=' . (int)$post['id'])) ?>" title="Edit post" aria-label="Edit post"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
<?php if ((int)($post['is_visible'] ?? 0) === 1): ?>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('news-article.php?id=' . rawurlencode((string)$post['slug']))) ?>" target="_blank" rel="noopener noreferrer" title="Preview public post" aria-label="Preview public post"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if ($format !== 'announcement'): ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>" data-confirm="Toggle featured status for this post?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="article_id" value="<?= Security::e((string)$post['id']) ?>"><input type="hidden" name="action" value="feature"><button class="btn btn--icon btn--outline" type="submit" title="Feature" aria-label="Feature"><i class="fa-solid fa-star" aria-hidden="true"></i></button></form>
<?php endif; ?>
<?php if ($status !== 'published'): ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>" data-confirm="Publish this post now?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="article_id" value="<?= Security::e((string)$post['id']) ?>"><input type="hidden" name="action" value="publish"><button class="btn btn--icon btn--success" type="submit" title="Publish" aria-label="Publish"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button></form>
<?php endif; ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>" data-confirm="Archive this post?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="article_id" value="<?= Security::e((string)$post['id']) ?>"><input type="hidden" name="action" value="archive"><button class="btn btn--icon btn--outline" type="submit" title="Archive" aria-label="Archive"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button></form>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>" data-confirm="Remove this post from the registry?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="article_id" value="<?= Security::e((string)$post['id']) ?>"><input type="hidden" name="action" value="delete"><button class="btn btn--icon btn--danger" type="submit" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form>
            </div>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination sa-news-pagination" aria-label="News pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalPosts)) ?> posts</p>
    <div class="pagination__list">
      <a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(sa_news_page_url(max(1, $page - 1), $filters)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(sa_news_page_url($i, $filters)) ?>"><?= Security::e((string)$i) ?></a>
<?php endfor; ?>
      <a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(sa_news_page_url(min($totalPages, $page + 1), $filters)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function sa_news_stat(string $icon, int $value, string $label, string $sub): void
{
    ?>
    <article class="stat-widget">
      <span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
      <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($sub) ?></small></span>
    </article>
    <?php
}

function sa_news_page_url(int $page, array $filters): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => (string)$value !== '');
    return Url::to('admin/superadmin/news.php' . ($query ? '?' . http_build_query($query) : ''));
}

function sa_news_asset_url(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    return preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) ? $path : Url::asset($path);
}
