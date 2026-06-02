<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'date_from' => subscribers_date($_GET['date_from'] ?? ''),
    'date_to' => subscribers_date($_GET['date_to'] ?? ''),
    'source_state' => Security::cleanString((string)($_GET['source_state'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== null);

$perPage = 20;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalSubscribers = Subscriber::countItems($filters);
$totalPages = max(1, (int)ceil($totalSubscribers / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$subscribers = array_map([Subscriber::class, 'payload'], Subscriber::items($filters, $perPage, $offset));
$summary = Subscriber::summary([]);
$showingFrom = $totalSubscribers > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($subscribers), $totalSubscribers);

$pageTitle = 'Subscribers';
$pageDescription = 'Manage newsletter subscribers, status controls and CSV export.';
$adminRole = 'superadmin';
$csrfForm = 'subscribers';
$contentClass = 'sa-subscribers-page';
$componentCss = ['subscribers'];
$pageScripts = ['subscribers'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Subscribers'],
];
$exportUrl = Url::to('api/subscribers/export.php?' . http_build_query($filters));

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="subscribers-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Newsletter audience</span>
    <h2>Subscribers</h2>
    <p>Manage newsletter subscriptions, unsubscribe/reactivate records and export filtered mailing lists.</p>
  </div>
  <div class="subscribers-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('news.php')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> News Page</a>
    <a class="btn btn--primary" href="<?= Security::e($exportUrl) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 subscribers-stats" aria-label="Subscriber summary">
  <?php subscribers_stat('fa-users', $summary['total'] ?? 0, 'Total Subscribers', 'All records'); ?>
  <?php subscribers_stat('fa-user-check', $summary['active'] ?? 0, 'Active', 'Receiving updates'); ?>
  <?php subscribers_stat('fa-user-xmark', $summary['unsubscribed'] ?? 0, 'Unsubscribed', 'Opted out'); ?>
  <?php subscribers_stat('fa-calendar-day', $summary['today'] ?? 0, 'Today', 'New today'); ?>
  <?php subscribers_stat('fa-calendar-week', $summary['this_week'] ?? 0, 'This Week', 'Last 7 days'); ?>
  <?php subscribers_stat('fa-calendar', $summary['this_month'] ?? 0, 'This Month', 'Last 30 days'); ?>
  <?php subscribers_stat('fa-link', $summary['with_source'] ?? 0, 'With Source', 'Captured source URL'); ?>
  <?php subscribers_stat('fa-envelope-open-text', ($summary['active'] ?? 0) - ($summary['today'] ?? 0), 'Existing Active', 'Before today'); ?>
</section>

<section class="card subscribers-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Subscriber Registry</h2>
      <p class="card__subtitle">Filter subscribers, export mailing lists and manage opt-in status.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalSubscribers)) ?> records</span>
  </div>

  <form class="filter-bar subscribers-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/subscribers.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Email, name, IP, source..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (Subscriber::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="source_state">Source</label><select class="form-select" id="source_state" name="source_state"><option value="">Any source</option><option value="with" <?= (($filters['source_state'] ?? '') === 'with') ? 'selected' : '' ?>>With source</option><option value="without" <?= (($filters['source_state'] ?? '') === 'without') ? 'selected' : '' ?>>No source</option></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/subscribers.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table subscribers-table">
      <thead><tr><th>Email</th><th>Name</th><th>Status</th><th>IP / Source</th><th>Subscribed</th><th>Status Dates</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($subscribers === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-envelope-open" aria-hidden="true"></i></span><strong class="empty-state__title">No subscribers found</strong><span class="empty-state__text">Newsletter subscribers will appear here after public signups.</span></div></td></tr>
<?php else: ?>
<?php foreach ($subscribers as $subscriber): ?>
        <tr>
          <td><strong><?= Security::e($subscriber['email']) ?></strong><small>ID #<?= (int)$subscriber['id'] ?></small></td>
          <td><?= $subscriber['name'] ? Security::e($subscriber['name']) : '<span class="text-muted">No name</span>' ?></td>
          <td><span class="badge <?= Security::e(status_badge_class($subscriber['status'])) ?>"><?= Security::e(status_label($subscriber['status'])) ?></span></td>
          <td><strong><?= Security::e($subscriber['ip'] ?: '-') ?></strong><small><?= Security::e(safe_truncate((string)($subscriber['source_url'] ?? ''), 56) ?: 'No source') ?></small></td>
          <td><?= Security::e(format_datetime($subscriber['subscribed_at'])) ?><small><?= Security::e(time_ago($subscriber['subscribed_at'])) ?></small></td>
          <td>
            <small>Unsubscribed: <?= Security::e(format_datetime($subscriber['unsubscribed_at'] ?? null)) ?></small>
            <small>Reactivated: <?= Security::e(format_datetime($subscriber['reactivated_at'] ?? null)) ?></small>
          </td>
          <td>
<?php if ($subscriber['status'] === 'active'): ?>
            <button class="btn btn--icon btn--danger" type="button" data-subscriber-action="unsubscribe" data-id="<?= (int)$subscriber['id'] ?>" title="Unsubscribe" aria-label="Unsubscribe"><i class="fa-solid fa-user-xmark" aria-hidden="true"></i></button>
<?php else: ?>
            <button class="btn btn--icon btn--primary" type="button" data-subscriber-action="reactivate" data-id="<?= (int)$subscriber['id'] ?>" title="Reactivate" aria-label="Reactivate"><i class="fa-solid fa-user-check" aria-hidden="true"></i></button>
<?php endif; ?>
          </td>
        </tr>
<?php endforeach; ?>
<?php endif; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="Subscribers pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalSubscribers)) ?> subscribers</p>
    <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(subscribers_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(subscribers_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
  </nav>
<?php endif; ?>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function subscribers_date(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function subscribers_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number(max(0, (float)$value))) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function subscribers_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null);
    return Url::to('admin/superadmin/subscribers.php?' . http_build_query($query));
}
