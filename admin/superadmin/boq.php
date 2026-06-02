<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$projects = BOQItem::projectOptions();
$defaultProjectId = 0;
foreach ($projects as $projectOption) {
    if ((int)($projectOption['boq_items'] ?? 0) > 0) {
        $defaultProjectId = (int)$projectOption['id'];
        break;
    }
}
if ($defaultProjectId === 0 && isset($projects[0])) {
    $defaultProjectId = (int)$projects[0]['id'];
}

$filters = [
    'project_id' => Security::cleanInt($_GET['project_id'] ?? $defaultProjectId),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'section' => Security::cleanString((string)($_GET['section'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'risk' => Security::cleanString((string)($_GET['risk'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0);

$perPage = 20;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalItems = BOQItem::countItems($filters);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$items = array_map([BOQItem::class, 'payload'], BOQItem::items($filters, $perPage, $offset));
$summary = BOQItem::summary($filters);
$sections = BOQItem::sections((int)($filters['project_id'] ?? 0) ?: null);
$showingFrom = $totalItems > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($items), $totalItems);
$selectedProject = null;
foreach ($projects as $projectOption) {
    if ((int)$projectOption['id'] === (int)($filters['project_id'] ?? 0)) {
        $selectedProject = $projectOption;
        break;
    }
}

$contractValue = (float)($summary['contract_value'] ?? 0);
$certifiedValue = (float)($summary['certified_value'] ?? 0);
$paidValue = (float)($summary['paid_value'] ?? 0);
$certifiedPercent = $contractValue > 0 ? percentage(($certifiedValue / $contractValue) * 100) : 0;
$paidPercent = $certifiedValue > 0 ? percentage(($paidValue / $certifiedValue) * 100) : 0;

$pageTitle = 'BOQ Centre';
$pageDescription = 'Project BOQ overview, certified quantities, paid quantities and quantity risk controls.';
$adminRole = 'superadmin';
$csrfForm = 'superadmin_boq';
$contentClass = 'sa-boq-page';
$componentCss = ['boq-centre'];
$pageScripts = ['boq-centre'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'BOQ Centre'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="boq-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Contract quantities</span>
    <h2>BOQ Centre</h2>
    <p>Track contract quantities, certified quantities, paid quantities and risk flags by project.</p>
  </div>
  <div class="boq-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/ipcs.php')) ?>"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> IPC Centre</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Projects</a>
  </div>
</section>

<section class="card boq-project-strip">
  <form class="boq-project-form" method="get" action="<?= Security::e(Url::to('admin/superadmin/boq.php')) ?>">
    <label class="filter-group boq-project-picker" for="project_id">
      <span class="filter-label">Project</span>
      <select class="form-select" id="project_id" name="project_id" data-boq-project>
<?php if ($projects === []): ?>
        <option value="">No projects available</option>
<?php else: ?>
<?php foreach ($projects as $projectOption): ?>
        <option value="<?= (int)$projectOption['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$projectOption['id'] ? 'selected' : '' ?>>
          <?= Security::e($projectOption['name']) ?><?= (int)$projectOption['boq_items'] > 0 ? ' - ' . Security::e(format_number($projectOption['boq_items'])) . ' items' : ' - no BOQ yet' ?>
        </option>
<?php endforeach; ?>
<?php endif; ?>
      </select>
    </label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Open</button>
  </form>
<?php if ($selectedProject): ?>
  <div class="boq-project-meta">
    <strong><?= Security::e($selectedProject['name']) ?></strong>
    <span><?= Security::e($selectedProject['constituency_name'] ?: 'No constituency') ?></span>
    <span><?= Security::e($selectedProject['contractor_name'] ?: 'No contractor recorded') ?></span>
  </div>
<?php endif; ?>
</section>

<section class="stat-grid stat-grid--4 boq-stats" aria-label="BOQ summary">
  <?php boq_stat('fa-file-contract', $summary['total_items'] ?? 0, 'BOQ Items', 'Loaded contract lines'); ?>
  <?php boq_stat('fa-sack-dollar', $contractValue, 'Contract BOQ Value', 'Original measured value', true); ?>
  <?php boq_stat('fa-circle-check', $certifiedValue, 'Certified Value', format_percentage($certifiedPercent) . ' of contract', true); ?>
  <?php boq_stat('fa-credit-card', $paidValue, 'Paid Value', format_percentage($paidPercent) . ' of certified', true); ?>
  <?php boq_stat('fa-triangle-exclamation', $summary['over_certified'] ?? 0, 'Over-certified', 'Quantity exceeds contract'); ?>
  <?php boq_stat('fa-money-bill-wave', $summary['overpaid'] ?? 0, 'Overpaid', 'Paid exceeds certified'); ?>
  <?php boq_stat('fa-hourglass-half', $summary['unpaid_certified'] ?? 0, 'Unpaid Certified', 'Certified but not fully paid'); ?>
  <?php boq_stat('fa-calculator', $summary['amount_mismatch'] ?? 0, 'Amount Mismatch', 'Amount differs from qty x rate'); ?>
</section>

<section class="card boq-registry">
  <div class="card__header">
    <div>
      <h2 class="card__title">BOQ Registry</h2>
      <p class="card__subtitle">Search, filter and update certified or paid quantities.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalItems)) ?> records</span>
  </div>

  <form class="filter-bar boq-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/boq.php')) ?>">
    <input type="hidden" name="project_id" value="<?= Security::e((string)($filters['project_id'] ?? '')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Item, section, description..."></div>
    <div class="filter-group"><label class="filter-label" for="section">Section</label><select class="form-select" id="section" name="section"><option value="">All sections</option><?php foreach ($sections as $section): ?><option value="<?= Security::e($section['section']) ?>" <?= (($filters['section'] ?? '') === $section['section']) ? 'selected' : '' ?>><?= Security::e($section['section']) ?> (<?= Security::e(format_number($section['total'])) ?>)</option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (BOQItem::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="risk">Risk</label><select class="form-select" id="risk" name="risk"><option value="">Any risk</option><?php foreach (BOQItem::riskOptions() as $risk): ?><option value="<?= Security::e($risk) ?>" <?= (($filters['risk'] ?? '') === $risk) ? 'selected' : '' ?>><?= Security::e(status_label($risk)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/boq.php?project_id=' . (int)($filters['project_id'] ?? 0))) ?>">Reset</a></div>
  </form>

  <div class="table-wrap boq-table-wrap">
    <table class="data-table boq-table">
      <thead><tr><th>Item</th><th>Description</th><th>Unit</th><th class="is-number">Qty</th><th class="is-money">Rate</th><th class="is-money">Amount</th><th class="is-number">Certified</th><th class="is-number">Paid</th><th>Progress</th><th>Risk</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($items === []): ?>
        <tr><td colspan="11"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span><strong class="empty-state__title">No BOQ items found</strong><span class="empty-state__text">Choose another project or add BOQ records for this project.</span></div></td></tr>
<?php else: ?>
<?php foreach ($items as $item): ?>
<?php $riskLabels = $item['risks'] ?: ['clear']; ?>
        <tr>
          <td><strong><?= Security::e($item['item_no']) ?></strong><small><?= Security::e($item['section']) ?></small></td>
          <td class="boq-description"><?= Security::e(safe_truncate((string)$item['description'], 120)) ?></td>
          <td><?= Security::e($item['unit']) ?></td>
          <td class="is-number"><?= Security::e(format_number($item['quantity'], 3)) ?></td>
          <td class="is-money"><?= Security::e(format_money($item['rate'])) ?></td>
          <td class="is-money"><strong><?= Security::e(format_money($item['amount'])) ?></strong></td>
          <td class="is-number"><strong><?= Security::e(format_number($item['certified_qty'], 3)) ?></strong><small><?= Security::e(format_money($item['certified_value'])) ?></small></td>
          <td class="is-number"><strong><?= Security::e(format_number($item['paid_qty'], 3)) ?></strong><small><?= Security::e(format_money($item['paid_value'])) ?></small></td>
          <td>
            <div class="boq-progress"><span style="width: <?= Security::e((string)$item['certified_percent']) ?>%"></span></div>
            <small><?= Security::e(format_percentage($item['certified_percent'])) ?> certified</small>
          </td>
          <td><div class="boq-risk-list"><?php foreach ($riskLabels as $risk): ?><span class="badge <?= Security::e(boq_risk_badge($risk)) ?>"><?= Security::e(status_label($risk)) ?></span><?php endforeach; ?></div></td>
          <td>
            <button class="btn btn--icon btn--primary" type="button"
                    data-boq-edit
                    data-id="<?= (int)$item['id'] ?>"
                    data-item-no="<?= Security::e($item['item_no']) ?>"
                    data-description="<?= Security::e($item['description']) ?>"
                    data-quantity="<?= Security::e((string)$item['quantity']) ?>"
                    data-certified="<?= Security::e((string)$item['certified_qty']) ?>"
                    data-paid="<?= Security::e((string)$item['paid_qty']) ?>"
                    data-status="<?= Security::e($item['status']) ?>"
                    data-notes="<?= Security::e($item['notes'] ?? '') ?>"
                    title="Update BOQ item" aria-label="Update BOQ item"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
          </td>
        </tr>
<?php endforeach; ?>
<?php endif; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="BOQ pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalItems)) ?> items</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(boq_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(boq_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
<?php endif; ?>
</section>

<div class="modal" data-boq-modal hidden>
  <div class="modal__backdrop" data-boq-close></div>
  <div class="modal__dialog boq-modal" role="dialog" aria-modal="true" aria-labelledby="boqModalTitle">
    <div class="modal__header">
      <h2 class="modal__title" id="boqModalTitle">Update BOQ item</h2>
      <button class="modal__close" type="button" data-boq-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <form data-boq-form>
      <input type="hidden" name="id" data-boq-field="id">
      <div class="modal__body">
        <div class="boq-modal-summary">
          <strong data-boq-item-no></strong>
          <span data-boq-description></span>
          <small data-boq-contract></small>
        </div>
        <div class="form-grid form-grid--2">
          <label class="form-field"><span>Certified quantity</span><input class="form-input" type="number" name="certified_qty" min="0" step="0.001" data-boq-field="certified_qty"></label>
          <label class="form-field"><span>Paid quantity</span><input class="form-input" type="number" name="paid_qty" min="0" step="0.001" data-boq-field="paid_qty"></label>
          <label class="form-field"><span>Status</span><select class="form-select" name="status" data-boq-field="status"><?php foreach (BOQItem::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
          <label class="form-field"><span>Reason / note</span><input class="form-input" type="text" name="notes" maxlength="500" data-boq-field="notes" placeholder="Optional update note"></label>
        </div>
        <label class="checkbox-card"><input type="checkbox" name="force" value="1"> <span>Allow over-certification or overpayment after review</span></label>
        <div class="alert alert--warning" data-boq-warning hidden></div>
        <div class="alert alert--danger" data-boq-error hidden></div>
      </div>
      <div class="modal__footer">
        <button class="btn btn--outline" type="button" data-boq-close>Cancel</button>
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save BOQ Item</button>
      </div>
    </form>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function boq_stat(string $icon, mixed $value, string $label, string $trend, bool $money = false): void
{
    $display = $money ? format_money($value) : format_number($value);
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e($display) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function boq_risk_badge(string $risk): string
{
    return match ($risk) {
        'over-certified', 'overpaid', 'amount-mismatch' => 'badge--danger',
        'unpaid-certified' => 'badge--warning',
        'not-certified' => 'badge--neutral',
        default => 'badge--success',
    };
}

function boq_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
    return Url::to('admin/superadmin/boq.php?' . http_build_query($query));
}
