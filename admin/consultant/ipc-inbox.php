<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ConsultantIPC::projects($userId, $role);
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'date_from' => consultant_ipc_date($_GET['date_from'] ?? ''),
    'date_to' => consultant_ipc_date($_GET['date_to'] ?? ''),
    'amount_min' => Security::cleanFloat($_GET['amount_min'] ?? 0),
    'amount_max' => Security::cleanFloat($_GET['amount_max'] ?? 0),
    'warnings' => !empty($_GET['warnings']) ? 1 : 0,
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

if (!empty($filters['project_id']) && !ConsultantIPC::canAccessProject($userId, $role, (int)$filters['project_id'])) {
    Response::abort(403, 'You do not have access to this project IPC inbox.');
}

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ConsultantIPC::count($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$items = ConsultantIPC::items($userId, $role, $filters, $perPage, $offset);
$summary = ConsultantIPC::summary($userId, $role, $filters);
$tabs = ConsultantIPC::statusTabs($userId, $role, $filters);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($items), $total);

$pageTitle = 'IPC Inbox';
$pageDescription = 'Review verified interim payment claims and certify or reject assigned project IPCs.';
$adminRole = 'consultant';
$csrfForm = 'consultant_ipc_inbox';
$contentClass = 'consultant-ipc-page';
$componentCss = ['consultant-ipc'];
$pageScripts = ['consultant-ipc'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'IPC Inbox'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="consultant-ipc-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i> IPC certification</span>
    <h2>IPC Inbox</h2>
    <p>Review interim payment claims for assigned projects and move verified IPCs forward.</p>
  </div>
  <div class="consultant-ipc-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/boq-review.php')) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i> BOQ Review</a>
  </div>
</section>

<section class="consultant-ipc-stat-grid" aria-label="IPC inbox summary">
  <?php consultant_ipc_stat('fa-file-invoice', $summary['total'], 'Total IPCs', 'All assigned claims', 'admin/consultant/ipc-inbox.php'); ?>
  <?php consultant_ipc_stat('fa-stamp', $summary['certifiable'], 'To Certify', 'Clerk-endorsed waiting', 'admin/consultant/ipc-inbox.php?status=clerk-endorsed'); ?>
  <?php consultant_ipc_stat('fa-clock', $summary['submitted'], 'Submitted', 'Awaiting site verification', 'admin/consultant/ipc-inbox.php?status=submitted'); ?>
  <?php consultant_ipc_stat('fa-circle-check', $summary['certified_this_month'], 'Certified This Month', 'Your recent output', 'admin/consultant/ipc-inbox.php?status=certified'); ?>
  <?php consultant_ipc_stat('fa-ban', $summary['rejected'], 'Rejected', 'Returned claims', 'admin/consultant/ipc-inbox.php?status=rejected'); ?>
  <?php consultant_ipc_stat('fa-money-bill-transfer', format_money($summary['certifiable_value']), 'Value Waiting', 'Net amount to review', 'admin/consultant/ipc-inbox.php?status=clerk-endorsed'); ?>
</section>

<section class="card consultant-ipc-registry">
  <div class="card__header">
    <div>
      <h2 class="card__title">Certification Registry</h2>
      <p class="card__subtitle">Filter claims, open the detail workspace, certify or return with reasons.</p>
    </div>
    <span class="badge badge--info"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <nav class="consultant-ipc-tabs" aria-label="IPC status filters">
    <?php
    $tabItems = [
        '' => ['label' => 'All IPCs', 'count' => $tabs['all'] ?? 0],
        'submitted' => ['label' => 'Submitted', 'count' => $tabs['submitted'] ?? 0],
        'clerk-endorsed' => ['label' => 'Ready to Certify', 'count' => $tabs['clerk-endorsed'] ?? 0],
        'certified' => ['label' => 'Certified', 'count' => $tabs['certified'] ?? 0],
        'rejected' => ['label' => 'Rejected', 'count' => $tabs['rejected'] ?? 0],
    ];
    foreach ($tabItems as $statusValue => $tab):
        $tabFilters = $filters;
        if ($statusValue === '') {
            unset($tabFilters['status']);
        } else {
            $tabFilters['status'] = $statusValue;
        }
        $isActive = (string)($filters['status'] ?? '') === $statusValue;
    ?>
    <a class="consultant-ipc-tab<?= $isActive ? ' is-active' : '' ?>" href="<?= Security::e(consultant_ipc_page_url($tabFilters, 1)) ?>">
      <?= Security::e($tab['label']) ?> <span><?= Security::e(format_number($tab['count'])) ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <form class="filter-bar consultant-ipc-filter" method="get" action="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-control" type="text" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="IPC, project or contractor..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (IPC::statuses() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-control" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-control" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="amount_min">Min value</label><input class="form-control" type="number" min="0" step="0.01" id="amount_min" name="amount_min" value="<?= Security::e($filters['amount_min'] ?? '') ?>" placeholder="0.00"></div>
    <div class="filter-group"><label class="filter-label" for="amount_max">Max value</label><input class="form-control" type="number" min="0" step="0.01" id="amount_max" name="amount_max" value="<?= Security::e($filters['amount_max'] ?? '') ?>" placeholder="Any"></div>
    <label class="filter-check"><input type="checkbox" name="warnings" value="1" <?= !empty($filters['warnings']) ? 'checked' : '' ?>> Has warnings</label>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table consultant-ipc-table">
      <thead><tr><th>IPC</th><th>Project</th><th>Period</th><th class="is-num">Amounts</th><th>Status</th><th>Last Action</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($items === []): ?>
        <tr><td colspan="7"><div class="empty-state"><strong class="empty-state__title">No IPCs found</strong><span class="empty-state__text">Try adjusting filters or wait for assigned project IPCs to be verified.</span></div></td></tr>
<?php else: foreach ($items as $ipc): ?>
        <tr>
          <td><strong>IPC #<?= Security::e(format_number($ipc['ipc_number'])) ?></strong><small><?= Security::e(format_date($ipc['submitted_at'] ?? null)) ?> / <?= Security::e(format_number($ipc['line_count'])) ?> lines</small></td>
          <td><strong><?= Security::e($ipc['project_name']) ?></strong><small><?= Security::e($ipc['contractor_name']) ?></small></td>
          <td><?= Security::e(format_date($ipc['period_from'] ?? null)) ?><small>to <?= Security::e(format_date($ipc['period_to'] ?? null)) ?></small></td>
          <td class="is-num"><strong><?= Security::e(format_money($ipc['net_amount'])) ?></strong><small>Gross <?= Security::e(format_money($ipc['gross_amount'])) ?></small></td>
          <td><span class="badge <?= Security::e(status_badge_class($ipc['status'])) ?>"><?= Security::e(status_label($ipc['status'])) ?></span><?php if ((int)$ipc['warnings_count'] > 0 || (float)$ipc['line_total'] <= 0): ?><small class="consultant-ipc-warning"><i class="fa-solid fa-triangle-exclamation"></i> Review needed</small><?php endif; ?></td>
          <td><strong><?= Security::e(status_label($ipc['last_action'] ?: $ipc['status'])) ?></strong><small><?= Security::e($ipc['last_action_by'] ?: 'System') ?> / <?= Security::e(time_ago($ipc['last_actioned_at'] ?? $ipc['updated_at'] ?? null)) ?></small></td>
          <td class="consultant-ipc-actions">
            <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/consultant/ipc-certify.php?id=' . (int)$ipc['id'])) ?>" title="Open review workspace" aria-label="Open review workspace"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
<?php if (!empty($ipc['can_certify'])): ?>
            <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/consultant/ipc-certify.php?id=' . (int)$ipc['id'])) ?>" title="Certify IPC" aria-label="Certify IPC"><i class="fa-solid fa-stamp" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if (in_array((string)$ipc['status'], ['submitted', 'clerk-endorsed'], true)): ?>
            <button class="btn btn--icon btn--danger" type="button" data-consultant-ipc-reject data-ipc-id="<?= (int)$ipc['id'] ?>" data-ipc-label="IPC #<?= Security::e(format_number($ipc['ipc_number'])) ?>" title="Return / reject" aria-label="Return / reject"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>
<?php endif; ?>
          </td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> IPCs</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(consultant_ipc_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(consultant_ipc_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<div class="consultant-ipc-modal" data-consultant-ipc-modal hidden>
  <form class="consultant-ipc-modal__panel" data-consultant-ipc-reject-form>
    <div class="consultant-ipc-modal__header">
      <div><span class="sa-panel-label">Return IPC</span><h2>Reject IPC</h2></div>
      <button class="btn btn--icon btn--ghost" type="button" data-consultant-ipc-close><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="consultant-ipc-modal__body">
      <input type="hidden" name="ipc_id" data-consultant-ipc-reject-id>
      <p data-consultant-ipc-reject-label>Record why this IPC is being returned.</p>
      <label class="form-field"><span>Reason *</span><textarea class="form-control" name="reason" rows="5" required placeholder="Explain what must be corrected before certification."></textarea></label>
    </div>
    <div class="consultant-ipc-modal__footer">
      <button class="btn btn--outline" type="button" data-consultant-ipc-close>Cancel</button>
      <button class="btn btn--danger" type="submit"><i class="fa-solid fa-ban" aria-hidden="true"></i> Reject IPC</button>
    </div>
  </form>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function consultant_ipc_date(mixed $value): string
{
    $timestamp = strtotime((string)$value);
    return $timestamp ? date('Y-m-d', $timestamp) : '';
}

function consultant_ipc_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
    return Url::to('admin/consultant/ipc-inbox.php?' . http_build_query($query));
}

function consultant_ipc_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e(Url::to($path)) . '"' : '';
?>
  <<?= $tag ?> class="consultant-ipc-stat card"<?= $href ?>>
    <span class="consultant-ipc-stat__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong><em><?= Security::e($label) ?></em><small><?= Security::e($hint) ?></small></span>
  </<?= $tag ?>>
<?php
}
