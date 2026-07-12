<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('manager');

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerBOQ::projects($userId, $role);
$defaultProjectId = 0;
foreach ($projects as $project) {
    if ((int)($project['boq_items'] ?? 0) > 0) {
        $defaultProjectId = (int)$project['id'];
        break;
    }
}
if ($defaultProjectId === 0 && $projects !== []) {
    $defaultProjectId = (int)$projects[0]['id'];
}

$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? $defaultProjectId),
    'section' => Security::cleanString((string)($_GET['section'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'review_status' => Security::cleanString((string)($_GET['review_status'] ?? '')),
    'risk_status' => Security::cleanString((string)($_GET['risk_status'] ?? '')),
    'risk' => Security::cleanString((string)($_GET['risk'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerBOQ::count($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$items = array_map([ManagerBOQ::class, 'payload'], ManagerBOQ::list($userId, $role, $filters, $perPage, $offset));
$summary = ManagerBOQ::summary($userId, $role, $filters);
$sections = ManagerBOQ::sections($userId, $role, (int)($filters['project_id'] ?? 0));
$insights = ManagerBOQ::insights($userId, $role, $filters);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($items), $total);

$pageTitle = 'BOQ Review';
$pageDescription = 'Review quantities, certified work, payment exposure and item-level delivery risks.';
$adminRole = 'manager';
$csrfForm = 'manager_boq';
$contentClass = 'manager-boq-page';
$componentCss = ['manager-boq'];
$pageScripts = ['manager-boq'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'BOQ Review'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="manager-boq-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Claims and cost</span>
    <h2>BOQ Review</h2>
    <p>Review quantities, certified work, payment exposure and item-level risks on your assigned projects only.</p>
  </div>
  <div class="manager-boq-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Projects</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/ipc-queue.php')) ?>"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> IPC Queue</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/programme-of-works.php')) ?>"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Programme</a>
  </div>
</section>

<section class="manager-boq-projects" aria-label="Assigned BOQ projects">
<?php if ($projects === []): ?>
  <article class="card manager-boq-project is-empty">
    <strong>No assigned projects</strong>
    <span>Assignments will appear here once projects are allocated.</span>
  </article>
<?php else: foreach ($projects as $project): ?>
  <a class="card manager-boq-project<?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? ' is-active' : '' ?>" href="<?= Security::e(manager_boq_page_url(array_merge($filters, ['project_id' => (int)$project['id']]), 1)) ?>">
    <span>
      <strong><?= Security::e($project['name']) ?></strong>
      <small><?= Security::e($project['constituency_name'] ?: status_label($project['status'] ?? 'active')) ?></small>
    </span>
    <em><?= Security::e(format_number($project['boq_items'] ?? 0)) ?> items</em>
  </a>
<?php endforeach; endif; ?>
</section>

<section class="stat-grid stat-grid--4 manager-boq-stats" aria-label="BOQ summary">
  <?php manager_boq_stat('fa-list-check', $summary['total_items'], 'BOQ Items', 'Assigned portfolio', manager_boq_page_url(array_diff_key($filters, ['review_status' => true, 'risk' => true, 'page' => true]), 1)); ?>
  <?php manager_boq_stat('fa-file-contract', format_money($summary['contract_value']), 'Contract Value', 'Measured work'); ?>
  <?php manager_boq_stat('fa-circle-check', format_money($summary['certified_value']), 'Certified Value', (int)$summary['certified_percent'] . '% certified'); ?>
  <?php manager_boq_stat('fa-money-check-dollar', format_money($summary['paid_value']), 'Paid Value', (int)$summary['paid_percent'] . '% paid'); ?>
  <?php manager_boq_stat('fa-scale-balanced', format_money($summary['remaining_value']), 'Remaining Value', 'Not yet certified'); ?>
  <?php manager_boq_stat('fa-triangle-exclamation', $summary['over_certified'], 'Over-Certified', 'Needs confirmation', manager_boq_page_url(array_merge($filters, ['risk' => 'over-certified', 'page' => 1]), 1)); ?>
  <?php manager_boq_stat('fa-circle-exclamation', $summary['overpaid'], 'Paid Above Certified', 'Payment exposure', manager_boq_page_url(array_merge($filters, ['risk' => 'overpaid', 'page' => 1]), 1)); ?>
  <?php manager_boq_stat('fa-clipboard-question', $summary['needs_review'], 'Needs Review', 'Pending action', manager_boq_page_url(array_merge($filters, ['review_status' => 'pending', 'page' => 1]), 1)); ?>
</section>

<section class="manager-boq-layout">
  <div class="manager-boq-main">
    <section class="card manager-boq-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">BOQ Register</h2>
          <p class="card__subtitle">Filter items, check quantities and record review notes.</p>
        </div>
        <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> items</span>
      </div>

      <form class="filter-bar manager-boq-filter" method="get" action="<?= Security::e(Url::to('admin/manager/boq.php')) ?>">
        <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Item, section or project..."></div>
        <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="section">Section</label><select class="form-select" id="section" name="section"><option value="">All sections</option><?php foreach ($sections as $section): $sectionName = (string)($section['section'] ?? ''); ?><option value="<?= Security::e($sectionName) ?>" <?= (($filters['section'] ?? '') === $sectionName) ? 'selected' : '' ?>><?= Security::e($sectionName ?: 'Unsectioned') ?> (<?= Security::e(format_number($section['total'] ?? 0)) ?>)</option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="review_status">Review</label><select class="form-select" id="review_status" name="review_status"><option value="">All reviews</option><?php foreach (ManagerBOQ::REVIEW_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['review_status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="risk_status">Risk</label><select class="form-select" id="risk_status" name="risk_status"><option value="">All risk levels</option><?php foreach (ManagerBOQ::RISK_STATUSES as $risk): ?><option value="<?= Security::e($risk) ?>" <?= (($filters['risk_status'] ?? '') === $risk) ? 'selected' : '' ?>><?= Security::e(status_label($risk)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="risk">Signal</label><select class="form-select" id="risk" name="risk"><option value="">Any signal</option><?php foreach (BOQItem::riskOptions() as $risk): ?><option value="<?= Security::e($risk) ?>" <?= (($filters['risk'] ?? '') === $risk) ? 'selected' : '' ?>><?= Security::e(status_label($risk)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/boq.php')) ?>">Reset</a></div>
      </form>

      <div class="table-wrap">
        <table class="data-table manager-boq-table">
          <thead><tr><th>Item</th><th>Project</th><th>Quantity</th><th>Contract</th><th>Certified</th><th>Paid</th><th>Review</th><th>Actions</th></tr></thead>
          <tbody>
<?php if ($items === []): ?>
            <tr><td colspan="8"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span><strong class="empty-state__title">No BOQ items found</strong><span class="empty-state__text">Adjust your filters or check assigned projects.</span></div></td></tr>
<?php else: foreach ($items as $item): ?>
            <tr data-boq-row="<?= (int)$item['id'] ?>">
              <td><strong><?= Security::e($item['item_no'] ?: ('Item #' . $item['id'])) ?> - <?= Security::e(safe_truncate($item['description'], 80)) ?></strong><small><?= Security::e($item['section'] ?: 'General works') ?></small></td>
              <td><strong><?= Security::e($item['project_name']) ?></strong><small><?= Security::e($item['constituency_name'] ?: 'Assigned project') ?></small></td>
              <td class="is-num"><strong><?= Security::e(format_number($item['quantity'], 3)) ?></strong><small><?= Security::e($item['unit']) ?></small></td>
              <td class="is-num"><strong><?= Security::e(format_money($item['amount'])) ?></strong><small>Rate <?= Security::e(format_money($item['rate'])) ?></small></td>
              <td class="is-num"><strong><?= Security::e(format_number($item['certified_qty'], 3)) ?></strong><small><?= Security::e(format_money($item['certified_value'])) ?></small></td>
              <td class="is-num"><strong><?= Security::e(format_number($item['paid_qty'], 3)) ?></strong><small><?= Security::e(format_money($item['paid_value'])) ?></small></td>
              <td>
                <span class="manager-boq-pill manager-boq-pill--<?= Security::e($item['review_status']) ?>"><?= Security::e($item['review_status_label']) ?></span>
                <small class="manager-boq-risk manager-boq-risk--<?= Security::e($item['risk_status']) ?>"><?= Security::e($item['risk_status_label']) ?> risk</small>
                <?php if (($item['computed_risks'] ?? []) !== []): ?><small class="manager-boq-signal"><?= Security::e($item['risk_label']) ?></small><?php endif; ?>
              </td>
              <td>
                <div class="manager-table-actions">
                  <button class="btn btn--icon btn--primary" type="button" data-boq-review data-id="<?= (int)$item['id'] ?>" title="Review BOQ item" aria-label="Review BOQ item"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                  <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/manager/ipc-queue.php?project_id=' . (int)$item['project_id'])) ?>" title="IPC queue" aria-label="IPC queue"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></a>
                </div>
              </td>
            </tr>
<?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <nav class="pagination" aria-label="BOQ pagination">
        <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> BOQ items (<?= (int)$perPage ?> per page)</p>
        <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_boq_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?> / <?= Security::e(format_number($totalPages)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_boq_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
      </nav>
    </section>

    <section class="card manager-boq-followup">
      <div class="card__header"><div><h2 class="card__title">Review Signals</h2><p class="card__subtitle">Items needing the closest follow-up.</p></div></div>
      <div class="manager-boq-signal-grid">
        <?php manager_boq_signal_group('Needs Review', 'fa-clipboard-question', $insights['needsReview'], 'No review items'); ?>
        <?php manager_boq_signal_group('Paid Above Certified', 'fa-circle-exclamation', $insights['overpaid'], 'No payment exposure'); ?>
        <?php manager_boq_signal_group('Certified Above Contract', 'fa-triangle-exclamation', $insights['overCertified'], 'No over-certification'); ?>
      </div>
    </section>
  </div>

  <aside class="manager-boq-side" aria-label="BOQ side summary">
    <section class="card manager-boq-panel">
      <div class="card__header"><div><h2 class="card__title">Section Mix</h2><p class="card__subtitle">Current filtered project.</p></div></div>
      <div class="manager-boq-section-list">
<?php if ($sections === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No sections found</strong></div>
<?php else: foreach (array_slice($sections, 0, 8) as $section): ?>
        <a href="<?= Security::e(manager_boq_page_url(array_merge($filters, ['section' => (string)$section['section']]), 1)) ?>"><span><?= Security::e((string)$section['section'] ?: 'General works') ?></span><strong><?= Security::e(format_number($section['total'])) ?></strong></a>
<?php endforeach; endif; ?>
      </div>
    </section>
    <section class="card manager-boq-panel">
      <div class="card__header"><div><h2 class="card__title">Quick Actions</h2><p class="card__subtitle">Related manager workflows.</p></div></div>
      <div class="manager-boq-action-list">
        <a href="<?= Security::e(Url::to('admin/manager/ipc-queue.php')) ?>"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Review IPC queue</a>
        <a href="<?= Security::e(Url::to('admin/manager/milestones.php')) ?>"><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Update milestones</a>
        <a href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> View projects</a>
      </div>
    </section>
  </aside>
</section>

<div class="manager-boq-modal" data-boq-modal hidden>
  <form class="manager-boq-modal__panel" data-boq-form>
    <div class="manager-boq-modal__header">
      <div><span class="sa-panel-label">BOQ item review</span><h2 data-boq-title>Review BOQ item</h2><p data-boq-subtitle></p></div>
      <button class="btn btn--icon btn--ghost" type="button" data-boq-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <input type="hidden" name="id" data-field="id">
    <input type="hidden" name="force" data-field="force" value="0">
    <div class="manager-boq-modal__body">
      <section class="manager-boq-review-grid">
        <label class="form-field"><span class="form-label">Certified quantity</span><input class="form-input" type="number" min="0" step="0.001" name="certified_qty" data-field="certified_qty" required></label>
        <label class="form-field"><span class="form-label">Paid quantity</span><input class="form-input" type="number" min="0" step="0.001" name="paid_qty_display" data-field="paid_qty" disabled><small>Payments are updated by finance.</small></label>
        <label class="form-field"><span class="form-label">Review status</span><select class="form-select" name="review_status" data-field="review_status"><?php foreach (ManagerBOQ::REVIEW_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Risk level</span><select class="form-select" name="risk_status" data-field="risk_status"><?php foreach (ManagerBOQ::RISK_STATUSES as $risk): ?><option value="<?= Security::e($risk) ?>"><?= Security::e(status_label($risk)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field form-field--wide"><span class="form-label">Manager note</span><textarea class="form-textarea" name="manager_note" data-field="manager_note" rows="4" maxlength="2000"></textarea></label>
      </section>
      <div class="manager-boq-current" data-boq-current></div>
      <div class="manager-boq-warning" data-boq-warning hidden></div>
      <section class="manager-boq-detail-panels">
        <div><h3>IPC Usage</h3><div class="manager-boq-mini-list" data-boq-usage></div></div>
        <div><h3>Review History</h3><div class="manager-boq-mini-list" data-boq-history></div></div>
      </section>
      <p class="manager-boq-form-status" data-boq-status></p>
    </div>
    <div class="manager-boq-modal__footer">
      <button class="btn btn--outline" type="button" data-boq-close>Cancel</button>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Review</button>
    </div>
  </form>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function manager_boq_stat(string $icon, mixed $value, string $label, string $trend, ?string $href = null): void
{
    $tag = $href ? 'a' : 'article';
    $attr = $href ? ' href="' . Security::e($href) . '"' : '';
?>
  <<?= $tag ?> class="stat-widget<?= $href ? ' stat-widget--link' : '' ?>"<?= $attr ?>>
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number((float)$value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </<?= $tag ?>>
<?php
}

function manager_boq_signal_group(string $title, string $icon, array $items, string $empty): void
{
?>
  <div class="manager-boq-signal-card">
    <h3><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i> <?= Security::e($title) ?></h3>
<?php if ($items === []): ?>
    <p><?= Security::e($empty) ?></p>
<?php else: foreach ($items as $item): ?>
    <button type="button" data-boq-review data-id="<?= (int)$item['id'] ?>"><span><strong><?= Security::e($item['item_no'] ?: ('Item #' . $item['id'])) ?></strong><small><?= Security::e($item['project_name']) ?></small></span><em><?= Security::e($item['risk_status_label']) ?></em></button>
<?php endforeach; endif; ?>
  </div>
<?php
}

function manager_boq_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);
    return Url::to('admin/manager/boq.php' . ($query ? '?' . http_build_query($query) : ''));
}
