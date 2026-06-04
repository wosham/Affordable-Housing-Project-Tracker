<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('consultant'));

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'review_status' => trim((string)($_GET['review_status'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'cost_impact' => trim((string)($_GET['cost_impact'] ?? '')),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;
$projects = ConsultantContractDecision::projects($userId, $role);
$summary = ConsultantContractDecision::variationSummary($userId, $role, $filters);
$items = ConsultantContractDecision::variations($userId, $role, $filters, $limit, $offset);
$total = ConsultantContractDecision::variationCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));

$pageTitle = 'Variations';
$pageDescription = 'Review variation requests, cost impact and time impact recommendations.';
$adminRole = 'consultant';
$contentClass = 'consultant-contract-page';
$componentCss = ['consultant-contract'];
$pageScripts = ['consultant-contract'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant'],
    ['label' => 'Variations'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contract-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-code-branch" aria-hidden="true"></i> Contract scope review</span>
    <h2>Variations</h2>
    <p>Assess requested scope changes, cost impact, time impact and consultant recommendations.</p>
  </div>
  <div class="contract-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/eot-review.php')) ?>"><i class="fa-solid fa-clock-rotate-left"></i> EOT Review</a>
  </div>
</section>

<section class="contract-stats">
  <?php contract_stat('fa-file-contract', $summary['total'], 'Variations', 'Assigned projects'); ?>
  <?php contract_stat('fa-hourglass-half', $summary['pending_review'], 'Pending Review', 'Awaiting action'); ?>
  <?php contract_stat('fa-circle-check', $summary['recommended'], 'Recommended', 'Consultant reviewed'); ?>
  <?php contract_stat('fa-rotate-left', $summary['returned_count'], 'Returned/Rejected', 'Needs correction'); ?>
  <?php contract_stat('fa-coins', format_money($summary['recommended_value']), 'Recommended Value', 'Current recommendation'); ?>
</section>

<section class="contract-layout">
  <article class="contract-card card">
    <div class="contract-card__head">
      <div>
        <h2>Variation register</h2>
        <p>Filter variation requests and record consultant cost and time recommendations.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="contract-filter-grid" method="get">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Variation, project or reason..."></label>
      <label>Project
        <select name="project_id">
          <option value="0">All assigned projects</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>" <?= (int)$filters['project_id'] === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Review
        <select name="review_status">
          <option value="">All reviews</option>
          <?php foreach (ConsultantContractDecision::REVIEW_STATUSES as $status): ?>
            <option value="<?= Security::e($status) ?>" <?= $filters['review_status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Cost impact
        <select name="cost_impact">
          <option value="">All levels</option>
          <?php foreach (ConsultantContractDecision::COST_IMPACTS as $impact): ?>
            <option value="<?= Security::e($impact) ?>" <?= $filters['cost_impact'] === $impact ? 'selected' : '' ?>><?= Security::e(status_label($impact)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/variations.php')) ?>">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table contract-table">
        <thead><tr><th>Variation</th><th>Project</th><th>Amounts</th><th>Time</th><th>Status</th><th>Review</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="7"><div class="contract-empty">No variation requests found.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <?php $reviewStatus = (string)($item['consultant_review_status'] ?? 'pending'); ?>
          <tr>
            <td><span class="contract-primary">VO #<?= (int)$item['vo_number'] ?></span><span class="contract-secondary"><?= Security::e(safe_truncate($item['description'], 90)) ?></span></td>
            <td><span class="contract-primary"><?= Security::e($item['project_name']) ?></span><span class="contract-secondary"><?= Security::e($item['submitter_name'] ?: 'Project team') ?></span></td>
            <td><span class="contract-primary"><?= format_money($item['amount']) ?></span><span class="contract-secondary">Recommended <?= format_money($item['consultant_recommended_amount'] ?? 0) ?></span></td>
            <td><span><?= format_number($item['impact_on_time_days']) ?> requested</span><span class="contract-secondary"><?= format_number($item['consultant_time_impact_days'] ?? 0) ?> recommended</span></td>
            <td><span class="badge <?= Security::e(ConsultantContractDecision::statusClass((string)$item['status'])) ?>"><?= Security::e(status_label($item['status'])) ?></span><span class="contract-secondary"><?= Security::e(status_label($item['consultant_cost_impact_status'] ?: 'moderate')) ?> cost impact</span></td>
            <td><span class="badge <?= Security::e(ConsultantContractDecision::statusClass($reviewStatus)) ?>"><?= Security::e(status_label($reviewStatus)) ?></span><span class="contract-secondary"><?= Security::e($item['consultant_reviewer_name'] ?: '-') ?></span></td>
            <td><div class="contract-row-actions">
              <?php contract_action('variation', (int)$item['id'], 'recommend', 'Recommend', 'fa-check', 'VO #' . (int)$item['vo_number'] . ' - ' . $item['project_name'], 0, (float)$item['amount'], (int)$item['impact_on_time_days']); ?>
              <?php contract_action('variation', (int)$item['id'], 'return', 'Return', 'fa-rotate-left', 'VO #' . (int)$item['vo_number'] . ' - ' . $item['project_name'], 0, (float)$item['amount'], (int)$item['impact_on_time_days']); ?>
              <?php contract_action('variation', (int)$item['id'], 'flag', 'Flag', 'fa-flag', 'VO #' . (int)$item['vo_number'] . ' - ' . $item['project_name'], 0, (float)$item['amount'], (int)$item['impact_on_time_days']); ?>
              <?php contract_action('variation', (int)$item['id'], 'reject', 'Reject', 'fa-ban', 'VO #' . (int)$item['vo_number'] . ' - ' . $item['project_name'], 0, 0, 0); ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php contract_pagination($page, $pages, $total, $limit); ?>
  </article>

  <aside class="contract-card card">
    <h2>Pending scope decisions</h2>
    <p>Variation requests waiting for consultant action.</p>
    <div class="contract-side-list">
      <?php foreach (array_slice(array_filter($items, static fn ($item): bool => (string)($item['consultant_review_status'] ?? 'pending') === 'pending'), 0, 7) as $item): ?>
        <div class="contract-side-item"><strong>VO #<?= (int)$item['vo_number'] ?> / <?= Security::e($item['project_name']) ?></strong><span><?= format_money($item['amount']) ?> requested</span></div>
      <?php endforeach; ?>
      <?php if ((int)$summary['pending_review'] <= 0): ?><div class="contract-empty">No pending variation requests in this view.</div><?php endif; ?>
    </div>
  </aside>
</section>

<?php contract_modal(); ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function contract_stat(string $icon, mixed $value, string $label, string $hint): void { echo '<article class="contract-stat card"><span class="contract-stat__icon"><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e((string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></article>'; }
function contract_action(string $type, int $id, string $action, string $label, string $icon, string $item, int $days, float $amount, int $time): void { echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-contract-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label . ' contract item') . '" data-item="' . Security::e($item) . '" data-days="' . $days . '" data-amount="' . Security::e((string)$amount) . '" data-time="' . $time . '"><i class="fa-solid ' . Security::e($icon) . '"></i><span class="sr-only">' . Security::e($label) . '</span></button>'; }
function contract_pagination(int $page, int $pages, int $total, int $limit): void { $query = $_GET; echo '<div class="pagination"><span>Showing ' . format_number($total === 0 ? 0 : (($page - 1) * $limit) + 1) . '-' . format_number(min($total, $page * $limit)) . ' of ' . format_number($total) . '</span><div>'; $query['page'] = max(1, $page - 1); echo '<a class="btn btn--sm btn--outline" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left"></i></a><span class="btn btn--sm btn--primary">' . $page . '</span>'; $query['page'] = min($pages, $page + 1); echo '<a class="btn btn--sm btn--outline" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right"></i></a></div></div>'; }
function contract_modal(): void { ?><div class="contract-modal" data-contract-modal hidden><div class="contract-modal__panel"><div class="contract-modal__head"><div><strong data-modal-title>Review contract item</strong><span class="contract-secondary" data-modal-item></span></div><button class="btn btn--sm btn--ghost" type="button" data-modal-close><i class="fa-solid fa-xmark"></i></button></div><form><input type="hidden" name="type"><input type="hidden" name="id"><input type="hidden" name="action"><div class="contract-modal__body"><label data-eot-field>Recommended days <input type="number" name="recommended_days" min="0" max="365"></label><label data-eot-field>Delay cause <select name="delay_category"><?php foreach (ConsultantContractDecision::DELAY_CATEGORIES as $category): ?><option value="<?= Security::e($category) ?>"><?= Security::e(status_label($category)) ?></option><?php endforeach; ?></select></label><label data-variation-field>Recommended amount <input type="number" name="recommended_amount" min="0" step="0.01"></label><label data-variation-field>Time impact days <input type="number" name="time_impact_days" min="0" max="365"></label><label data-variation-field>Cost impact <select name="cost_impact_status"><?php foreach (ConsultantContractDecision::COST_IMPACTS as $impact): ?><option value="<?= Security::e($impact) ?>"><?= Security::e(status_label($impact)) ?></option><?php endforeach; ?></select></label><label class="span-2">Review note <textarea name="note" placeholder="Add a clear note for the project team."></textarea></label><label class="span-2"><span><input type="checkbox" name="documents_checked" value="1"> Supporting documents checked</span></label></div><div class="contract-modal__foot"><button class="btn btn--outline" type="button" data-modal-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Review</button></div></form></div></div><?php }
