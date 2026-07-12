<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$requestedProjectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectId = ContractorProject::defaultProjectId($userId, $role, $requestedProjectId);
$projects = ContractorProject::projects($userId, $role);
$project = $projectId > 0 ? ContractorProject::detail($projectId, $userId, $role) : null;
$summary = $project ? ContractorProject::summary($projectId, $userId, $role) : ContractorProject::summary(0, $userId, $role);
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'section' => Security::cleanString((string)($_GET['section'] ?? '')),
    'status' => in_array((string)($_GET['status'] ?? ''), ['remaining', 'claimed', 'paid', 'risk'], true) ? (string)$_GET['status'] : '',
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$sections = $project ? ContractorProject::boqSections($projectId, $userId, $role) : [];
$total = $project ? ContractorProject::boqCount($projectId, $userId, $role, $filters) : 0;
$pages = max(1, (int)ceil($total / $limit));
$items = $project ? ContractorProject::boqItems($projectId, $userId, $role, $filters, $limit, $offset) : [];
$totals = contractor_boq_totals($items);
$attentionItems = $project ? ContractorProject::boqAttentionItems($projectId, $userId, $role, 8) : [];

$pageTitle = 'BOQ';
$pageDescription = 'View project quantities, measured values and remaining claim balances.';
$adminRole = 'contractor';
$contentClass = 'contractor-phase-page';
$componentCss = ['contractor-phase'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'BOQ'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-phase-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Quantity register</span>
    <h2>BOQ</h2>
    <p>View project quantities, measured values, paid quantities and remaining claim balances. BOQ is read-only for contractors.</p>
  </div>
  <div class="contractor-phase-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/my-project.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> My Project</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/contractor/ipc-submit.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Submit IPC</a>
  </div>
</section>

<?php if ($projects !== []): ?>
<form class="contractor-phase-selector card" method="get" action="<?= Security::e(Url::to('admin/contractor/boq.php')) ?>">
  <label for="project_id">Project</label>
  <select id="project_id" name="project_id" onchange="this.form.submit()">
    <?php foreach ($projects as $option): ?>
      <option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php endif; ?>

<?php if (!$project): ?>
  <div class="card empty-state">
    <strong class="empty-state__title">No assigned project found</strong>
    <span class="empty-state__text">Assigned project records will appear here once configured.</span>
  </div>
<?php else: ?>

<section class="contractor-phase-stats" aria-label="BOQ summary">
  <?php contractor_phase_stat('fa-list-check', $summary['boq_items'], 'BOQ Items', 'Project quantity lines', boq_filter_url([])); ?>
  <?php contractor_phase_stat('fa-coins', format_money($summary['boq_value']), 'BOQ Value', 'Total measured value', boq_filter_url([])); ?>
  <?php contractor_phase_stat('fa-circle-check', format_money($summary['boq_certified']), 'Claimed Value', 'Measured to date', boq_filter_url(['status' => 'claimed'])); ?>
  <?php contractor_phase_stat('fa-credit-card', format_money($summary['boq_paid']), 'Paid Value', 'Paid quantities', boq_filter_url(['status' => 'paid'])); ?>
  <?php contractor_phase_stat('fa-chart-pie', format_money(max(0, (float)$summary['boq_value'] - (float)$summary['boq_certified'])), 'Remaining', 'Available balance', boq_filter_url(['status' => 'remaining'])); ?>
  <?php contractor_phase_stat('fa-triangle-exclamation', $summary['boq_risk_items'], 'Attention Items', 'Needs a closer look', boq_filter_url(['status' => 'risk'])); ?>
</section>

<section class="contractor-phase-grid">
  <main class="card contractor-phase-panel">
    <div class="card__header">
      <div>
        <h2 class="card__title">BOQ Register</h2>
        <p class="card__subtitle">Filter project items and confirm remaining quantities before preparing an IPC.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="contractor-phase-filter" method="get" action="<?= Security::e(Url::to('admin/contractor/boq.php')) ?>">
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
      <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Item, section or description..."></label>
      <label><span>Section</span>
        <select name="section">
          <option value="">All sections</option>
          <?php foreach ($sections as $section): ?>
            <option value="<?= Security::e($section['section']) ?>" <?= $filters['section'] === (string)$section['section'] ? 'selected' : '' ?>><?= Security::e($section['section']) ?> (<?= (int)$section['total'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><span>View</span>
        <select name="status">
          <option value="">All items</option>
          <option value="remaining" <?= $filters['status'] === 'remaining' ? 'selected' : '' ?>>Remaining quantity</option>
          <option value="claimed" <?= $filters['status'] === 'claimed' ? 'selected' : '' ?>>Claimed items</option>
          <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid items</option>
          <option value="risk" <?= $filters['status'] === 'risk' ? 'selected' : '' ?>>Attention items</option>
        </select>
      </label>
      <div class="contractor-phase-filter__actions">
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/boq.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>">Reset</a>
      </div>
    </form>

    <div class="contractor-phase-table-wrap">
      <table class="contractor-phase-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Description</th>
            <th>Quantity</th>
            <th>Rate</th>
            <th>Value</th>
            <th>Claimed</th>
            <th>Paid</th>
            <th>Remaining</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($items === []): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state empty-state--compact">
                <strong class="empty-state__title">No BOQ items found</strong>
                <span class="empty-state__text">Adjust filters or check the assigned project.</span>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><strong><?= Security::e($item['item_no'] ?? '-') ?></strong><small><?= Security::e($item['section'] ?? '-') ?></small></td>
            <td><strong><?= Security::e(safe_truncate((string)($item['description'] ?? ''), 95)) ?></strong><small><?= Security::e($item['unit'] ?? '') ?></small></td>
            <td><?= number_format((float)($item['quantity'] ?? 0), 3) ?></td>
            <td><?= Security::e(format_money($item['rate'] ?? 0)) ?></td>
            <td><strong><?= Security::e(format_money($item['line_amount'] ?? 0)) ?></strong></td>
            <td><?= number_format((float)($item['certified_qty'] ?? 0), 3) ?><small><?= Security::e(format_money($item['certified_value'] ?? 0)) ?></small></td>
            <td><?= number_format((float)($item['paid_qty'] ?? 0), 3) ?><small><?= Security::e(format_money($item['paid_value'] ?? 0)) ?></small></td>
            <td><strong><?= number_format((float)($item['remaining_qty'] ?? 0), 3) ?></strong><small><?= Security::e(format_money($item['remaining_value'] ?? 0)) ?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php contractor_phase_pagination($page, $pages, $total, $limit); ?>
  </main>

  <aside class="contractor-phase-side">
    <section class="card contractor-phase-panel">
      <h2>Project Summary</h2>
      <div class="contractor-phase-metric-list">
        <a class="contractor-phase-metric-link" href="<?= Security::e(Url::to('admin/contractor/my-project.php?project_id=' . $projectId)) ?>">
          <strong><?= Security::e(safe_truncate($project['name'], 48)) ?></strong>
          <small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?> · Open project</small>
        </a>
        <a class="contractor-phase-metric-link" href="<?= Security::e(boq_filter_url([])) ?>">
          <strong><?= Security::e(format_money($summary['boq_value'])) ?></strong>
          <small>Contract BOQ value</small>
        </a>
        <a class="contractor-phase-metric-link" href="<?= Security::e(boq_filter_url(['status' => 'claimed'])) ?>">
          <strong><?= Security::e(format_money($summary['boq_certified'])) ?></strong>
          <small>Claimed to date</small>
        </a>
        <a class="contractor-phase-metric-link" href="<?= Security::e(boq_filter_url(['status' => 'paid'])) ?>">
          <strong><?= Security::e(format_money($summary['boq_paid'])) ?></strong>
          <small>Paid to date</small>
        </a>
        <a class="contractor-phase-metric-link" href="<?= Security::e(boq_filter_url(['status' => 'remaining'])) ?>">
          <strong><?= Security::e(format_money(max(0, (float)$summary['boq_value'] - (float)$summary['boq_certified']))) ?></strong>
          <small>Remaining claim balance</small>
        </a>
        <a class="contractor-phase-metric-link" href="<?= Security::e(Url::to('admin/contractor/ipc-submit.php?project_id=' . $projectId)) ?>">
          <strong><?= format_number($total) ?></strong>
          <small>Lines in filter · Prepare IPC</small>
        </a>
      </div>
    </section>

    <section class="card contractor-phase-panel">
      <h2>Attention Items</h2>
      <p class="contractor-phase-panel__intro">High-value, risk-flagged or fully claimed lines (portfolio for this project, not page-bound).</p>
      <div class="contractor-phase-list">
        <?php if ($attentionItems === []): ?>
          <div class="empty-state empty-state--compact"><strong class="empty-state__title">No attention items</strong></div>
        <?php else: foreach ($attentionItems as $item): ?>
          <a class="contractor-phase-list-link" href="<?= Security::e(boq_filter_url(['q' => (string)($item['item_no'] ?? ''), 'status' => 'risk'])) ?>">
            <strong><?= Security::e(($item['item_no'] ?? '-') . ' - ' . safe_truncate((string)($item['description'] ?? ''), 48)) ?></strong>
            <small><?= Security::e(format_money($item['remaining_value'] ?? 0)) ?> remaining</small>
          </a>
        <?php endforeach; endif; ?>
      </div>
      <?php if ((int)$summary['boq_risk_items'] > 0): ?>
        <a class="btn btn--outline btn--sm" href="<?= Security::e(boq_filter_url(['status' => 'risk'])) ?>">View attention filter</a>
      <?php endif; ?>
    </section>
  </aside>
</section>

<?php endif; ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function boq_filter_url(array $extra): string
{
    $base = [
        'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
        'q' => trim((string)($_GET['q'] ?? '')),
        'section' => trim((string)($_GET['section'] ?? '')),
        'status' => trim((string)($_GET['status'] ?? '')),
    ];
    $query = array_filter(array_merge($base, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/contractor/boq.php' . ($query ? '?' . http_build_query($query) : ''));
}

function contractor_boq_totals(array $items): array
{
    $totals = ['line_amount' => 0.0, 'remaining_value' => 0.0, 'remaining_items' => 0];
    foreach ($items as $item) {
        $totals['line_amount'] += (float)($item['line_amount'] ?? 0);
        $totals['remaining_value'] += (float)($item['remaining_value'] ?? 0);
        if ((float)($item['remaining_qty'] ?? 0) > 0) {
            $totals['remaining_items']++;
        }
    }
    return $totals;
}

function contractor_phase_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="contractor-phase-stat card"' . $href . '><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></' . $tag . '>';
}

function contractor_phase_pagination(int $page, int $pages, int $total, int $limit): void
{
    if ($total <= 0) {
        return;
    }
    $from = min($total, (($page - 1) * $limit) + 1);
    $to = min($total, $page * $limit);
    $query = $_GET;
    echo '<div class="pagination"><span>Showing ' . format_number($from) . '-' . format_number($to) . ' of ' . format_number($total) . '</span><div>';
    $query['page'] = max(1, $page - 1);
    $prevDis = $page <= 1 ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $prevDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left"></i></a>';
    echo '<span class="btn btn--sm btn--primary">' . $page . ' / ' . $pages . '</span>';
    $query['page'] = min($pages, $page + 1);
    $nextDis = $page >= $pages ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $nextDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right"></i></a></div></div>';
}
