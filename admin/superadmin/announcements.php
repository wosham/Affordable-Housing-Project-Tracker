<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_announcements';

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/announcements.php'));
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));
    $id = max(0, Security::cleanInt($_POST['announcement_id'] ?? 0));

    try {
        if ($id <= 0 || !Announcement::findAdmin($id)) {
            throw new RuntimeException('Announcement could not be found.');
        }

        match ($action) {
            'publish' => Announcement::quickStatus($id, 'published'),
            'draft' => Announcement::quickStatus($id, 'draft'),
            'archive' => Announcement::quickStatus($id, 'archived'),
            'pin' => Announcement::togglePinned($id),
            default => throw new RuntimeException('Unknown announcement action.'),
        };

        Logger::log($action, 'announcements', $id);
        Session::flash('status', 'Announcement updated successfully.');
    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
    }

    Response::redirect(Url::to('admin/superadmin/announcements.php'));
}

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'type' => Security::cleanString((string)($_GET['type'] ?? '')),
    'priority' => Security::cleanString((string)($_GET['priority'] ?? '')),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'pinned' => Security::cleanString((string)($_GET['pinned'] ?? '')),
    'visibility' => Security::cleanString((string)($_GET['visibility'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '');

$perPage = 12;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalAnnouncements = Announcement::adminCount($filters);
$totalPages = max(1, (int)ceil($totalAnnouncements / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$announcements = Announcement::adminList($filters, $perPage, $offset);
$stats = Announcement::adminStats();
$showingFrom = $totalAnnouncements > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($announcements), $totalAnnouncements);

$pageTitle = 'Announcements';
$pageDescription = 'Manage staff announcements, pinned notices and role-targeted broadcasts.';
$adminRole = 'superadmin';
$contentClass = 'sa-announcements-page';
$componentCss = ['announcements'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Announcements'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-announcement-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Staff Broadcasts</span>
    <h2>Announcements centre</h2>
    <p>Manage operational updates, pinned notices, role broadcasts and archived messages.</p>
  </div>
  <div class="sa-news-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/dashboard.php')) ?>"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/announcement-create.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> New Announcement</a>
  </div>
</section>

<section class="stat-grid stat-grid--4" aria-label="Announcements summary">
  <?php announcement_stat('fa-bullhorn', $stats['total'] ?? 0, 'Total', 'All announcements'); ?>
  <?php announcement_stat('fa-circle-check', $stats['published'] ?? 0, 'Published', 'Visible to staff'); ?>
  <?php announcement_stat('fa-thumbtack', $stats['pinned'] ?? 0, 'Pinned', 'Priority placement'); ?>
  <?php announcement_stat('fa-triangle-exclamation', $stats['urgent'] ?? 0, 'Urgent', 'Critical or urgent'); ?>
</section>

<section class="card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Announcement Registry</h2>
      <p class="card__subtitle">Search, filter, publish, pin or archive staff broadcasts.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalAnnouncements)) ?> announcements</span>
  </div>

  <form class="filter-bar" method="get" action="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Title or message..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (Announcement::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="type">Type</label><select class="form-select" id="type" name="type"><option value="">All types</option><?php foreach (Announcement::TYPES as $type => $meta): ?><option value="<?= Security::e($type) ?>" <?= (($filters['type'] ?? '') === $type) ? 'selected' : '' ?>><?= Security::e($meta['label']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="role">Role</label><select class="form-select" id="role" name="role"><option value="">All roles</option><?php foreach (Announcement::ROLES as $role): ?><option value="<?= Security::e($role) ?>" <?= (($filters['role'] ?? '') === $role) ? 'selected' : '' ?>><?= Security::e(role_label($role)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="visibility">Visibility</label><select class="form-select" id="visibility" name="visibility"><option value="">Any</option><option value="active" <?= (($filters['visibility'] ?? '') === 'active') ? 'selected' : '' ?>>Active only</option><option value="expired" <?= (($filters['visibility'] ?? '') === 'expired') ? 'selected' : '' ?>>Expired only</option></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Announcement</th><th>Audience</th><th>Status</th><th>Timing</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($announcements === []): ?>
        <tr><td colspan="5"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span><strong class="empty-state__title">No announcements match these filters</strong><span class="empty-state__text">Create a notice or clear the filters.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($announcements as $announcement): ?>
<?php
    $type = (string)($announcement['type'] ?? 'info');
    $status = (string)($announcement['status'] ?? 'draft');
    $priority = (string)($announcement['priority'] ?? 'normal');
?>
        <tr>
          <td>
            <div class="stacked-cell">
              <strong><i class="fa-solid <?= Security::e(Announcement::typeIcon($type)) ?>" aria-hidden="true"></i> <?= Security::e($announcement['title']) ?></strong>
              <small><?= Security::e(safe_truncate($announcement['body'], 120)) ?></small>
              <small><?= ((int)($announcement['is_pinned'] ?? 0) === 1) ? 'Pinned · ' : '' ?><?= Security::e(Announcement::typeLabel($type)) ?> · <?= Security::e(status_label($priority)) ?> priority</small>
            </div>
          </td>
          <td><?= Security::e(implode(', ', Announcement::roleLabels($announcement['target_roles_json'] ?? null))) ?></td>
          <td><span class="badge <?= Security::e(status_badge_class($status)) ?>"><?= Security::e(status_label($status)) ?></span></td>
          <td><span class="stacked-cell"><small>Published: <?= Security::e(format_datetime($announcement['published_at'] ?: $announcement['created_at'])) ?></small><small>Expires: <?= Security::e(format_datetime($announcement['expires_at'] ?? null)) ?></small></span></td>
          <td>
            <div class="data-table__actions">
              <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/announcement-edit.php?id=' . (int)$announcement['id'])) ?>" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>" data-confirm="Toggle pinned state?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="announcement_id" value="<?= Security::e((string)$announcement['id']) ?>"><input type="hidden" name="action" value="pin"><button class="btn btn--icon btn--outline" type="submit" title="Pin or unpin" aria-label="Pin or unpin"><i class="fa-solid fa-thumbtack" aria-hidden="true"></i></button></form>
<?php if ($status !== 'published'): ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>" data-confirm="Publish this announcement now?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="announcement_id" value="<?= Security::e((string)$announcement['id']) ?>"><input type="hidden" name="action" value="publish"><button class="btn btn--icon btn--success" type="submit" title="Publish" aria-label="Publish"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button></form>
<?php endif; ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>" data-confirm="Archive this announcement?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="announcement_id" value="<?= Security::e((string)$announcement['id']) ?>"><input type="hidden" name="action" value="archive"><button class="btn btn--icon btn--danger" type="submit" title="Archive" aria-label="Archive"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button></form>
            </div>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination" aria-label="Announcements pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalAnnouncements)) ?> announcements</p>
    <div class="pagination__list">
      <a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(announcement_page_url(max(1, $page - 1), $filters)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(announcement_page_url($i, $filters)) ?>"><?= Security::e((string)$i) ?></a>
<?php endfor; ?>
      <a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(announcement_page_url(min($totalPages, $page + 1), $filters)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function announcement_stat(string $icon, int $value, string $label, string $sub): void
{
    ?>
    <article class="stat-widget">
      <span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
      <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($sub) ?></small></span>
    </article>
    <?php
}

function announcement_page_url(int $page, array $filters): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => (string)$value !== '');
    return Url::to('admin/superadmin/announcements.php' . ($query ? '?' . http_build_query($query) : ''));
}
