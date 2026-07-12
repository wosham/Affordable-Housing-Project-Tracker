<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'visibility' => Security::cleanString((string)($_GET['visibility'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '');

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalConstituencies = Constituency::countWithFilters($filters);
$totalPages = max(1, (int)ceil($totalConstituencies / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$constituencies = Constituency::adminList($filters, $perPage, $offset);
$stats = Constituency::adminStats();
$showingFrom = $totalConstituencies > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($constituencies), $totalConstituencies);

$pageTitle = 'Constituencies';
$pageDescription = 'Manage public constituency profiles, wards and housing programme coverage.';
$adminRole = 'superadmin';
$contentClass = 'sa-constituencies-page';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Constituencies'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-projects-hero sa-constituency-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Constituency Registry</span>
    <h2>County coverage CMS</h2>
    <p>Control every public constituency page, its wards, hero image, population details and the programme statistics calculated from project records.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/constituency-edit.php')) ?>">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> New Constituency
    </a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('constituencies.php')) ?>" target="_blank" rel="noopener noreferrer">
      <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Public Page
    </a>
  </div>
</section>

<section class="stat-grid stat-grid--4 sa-project-stat-row" aria-label="Constituency summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-map" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['total_constituencies'] ?? 0)) ?></strong><span class="stat-widget__label">Constituencies</span><small class="stat-widget__trend"><?= Security::e(format_number($stats['public_constituencies'] ?? 0)) ?> visible publicly</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['total_wards'] ?? 0)) ?></strong><span class="stat-widget__label">Wards</span><small class="stat-widget__trend">Managed from this CMS</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-building" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['total_projects'] ?? 0)) ?></strong><span class="stat-widget__label">Linked Projects</span><small class="stat-widget__trend"><?= Security::e(format_number($stats['total_outputs'] ?? 0)) ?> tracked outputs</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_percentage($stats['avg_completion'] ?? 0)) ?></strong><span class="stat-widget__label">Avg. Progress</span><small class="stat-widget__trend">Calculated from projects</small></span></article>
</section>

<section class="card sa-projects-card sa-constituency-card">
  <div class="card__header">
    <div><h2 class="card__title">All Constituencies</h2><p class="card__subtitle">Edit frontend constituency pages and the wards used by project forms.</p></div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalConstituencies)) ?> records</span>
  </div>

  <form class="filter-bar sa-project-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/constituencies.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Name, slug, MP, description..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (['planning', 'active', 'completed'] as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="visibility">Visibility</label><select class="form-select" id="visibility" name="visibility"><option value="">All visibility</option><option value="public" <?= (($filters['visibility'] ?? '') === 'public') ? 'selected' : '' ?>>Public</option><option value="hidden" <?= (($filters['visibility'] ?? '') === 'hidden') ? 'selected' : '' ?>>Hidden</option></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/constituencies.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table sa-constituency-table">
      <thead><tr><th>#</th><th>Constituency</th><th>Wards</th><th>Projects</th><th>Outputs</th><th>Progress</th><th>Visibility</th><th>Updated</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($constituencies === []): ?>
        <tr><td colspan="9"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-map" aria-hidden="true"></i></span><strong class="empty-state__title">No constituencies found</strong><span class="empty-state__text">Create a constituency or adjust your filters.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($constituencies as $index => $constituency): ?>
<?php $thumb = (string)($constituency['hero_image'] ?? ''); $progress = percentage($constituency['live_avg_completion'] ?? $constituency['avg_completion'] ?? 0); ?>
        <tr>
          <td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td>
          <td><div class="sa-project-cell"><span class="sa-project-thumb" <?php if ($thumb !== ''): ?>style="background-image:url('<?= Security::e(Url::asset($thumb)) ?>')"<?php endif; ?>><?php if ($thumb === ''): ?><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><?php endif; ?></span><span><strong><?= Security::e($constituency['name']) ?></strong><small><?= Security::e($constituency['slug']) ?> &bull; <?= Security::e(format_number($constituency['population'] ?? 0)) ?> residents</small></span></div></td>
          <td><?= Security::e(format_number($constituency['ward_count'] ?? 0)) ?></td>
          <td><?= Security::e(format_number($constituency['live_project_count'] ?? 0)) ?></td>
          <td><?= Security::e(format_number($constituency['live_total_units'] ?? 0)) ?></td>
          <td><div class="sa-progress-cell"><span class="progress progress--sm"><span class="progress__bar" style="width: <?= Security::e((string)$progress) ?>%"></span></span><strong><?= Security::e($progress) ?>%</strong></div></td>
          <td><span class="badge <?= ((int)($constituency['is_public'] ?? 1) === 1) ? 'badge--success' : 'badge--warning' ?>"><?= ((int)($constituency['is_public'] ?? 1) === 1) ? 'Public' : 'Hidden' ?></span></td>
          <td><time datetime="<?= Security::e($constituency['updated_at'] ?? '') ?>"><?= Security::e(time_ago($constituency['updated_at'] ?? null)) ?></time></td>
          <td><div class="data-table__actions"><a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('constituency-detail.php?id=' . urlencode((string)$constituency['slug']))) ?>" target="_blank" rel="noopener noreferrer" title="View public page" aria-label="View public page"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a><a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/constituency-edit.php?id=' . (int)$constituency['id'])) ?>" title="Edit constituency" aria-label="Edit constituency"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a></div></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination sa-project-pagination" aria-label="Constituency pagination"><p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalConstituencies)) ?> constituencies</p><div class="pagination__list"><a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(constituency_page_url(max(1, $page - 1), $filters)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?><a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(constituency_page_url($i, $filters)) ?>"><?= Security::e((string)$i) ?></a><?php endfor; ?><a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(constituency_page_url(min($totalPages, $page + 1), $filters)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div></nav>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function constituency_page_url(int $page, array $filters): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => (string)$value !== '');
    return Url::to('admin/superadmin/constituencies.php' . ($query ? '?' . http_build_query($query) : ''));
}