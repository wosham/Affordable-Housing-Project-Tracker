<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'review_status' => trim((string)($_GET['review_status'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'delay_category' => trim((string)($_GET['delay_category'] ?? '')),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$projects = ConsultantContractDecision::projects($userId, $role);
$summary = ConsultantContractDecision::eotSummary($userId, $role, $filters);
$items = ConsultantContractDecision::eots($userId, $role, $filters, $limit, $offset);
$total = ConsultantContractDecision::eotCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));
$pendingItems = ConsultantContractDecision::eotPendingItems($userId, $role, $filters, 8);

$pageTitle = 'EOT Review';
$pageDescription = 'Review extension of time requests and record consultant recommendations.';
$adminRole = 'consultant';
$contentClass = 'consultant-contract-page';
$componentCss = ['consultant-contract'];
$pageScripts = ['consultant-contract'];
$csrfForm = 'consultant_contract';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'EOT Review'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contract-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Contract time review</span>
    <h2>EOT Review</h2>
    <p>Assess extension requests, delay causes, supporting records and recommended days on assigned projects only.</p>
  </div>
  <div class="contract-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/variations.php')) ?>"><i class="fa-solid fa-code-branch" aria-hidden="true"></i> Variations</a>
  </div>
</section>

<section class="contract-stats" aria-label="EOT summary">
  <?php contract_stat('fa-file-circle-question', $summary['total'], 'EOT Requests', 'Assigned projects', eot_filter_url([])); ?>
  <?php contract_stat('fa-hourglass-half', $summary['pending_review'], 'Pending Review', 'Awaiting action', eot_filter_url(['review_status' => 'pending'])); ?>
  <?php contract_stat('fa-circle-check', $summary['recommended'], 'Recommended', 'Consultant reviewed', eot_filter_url(['review_status' => 'recommended'])); ?>
  <?php contract_stat('fa-rotate-left', $summary['returned_count'], 'Returned/Rejected', 'Needs correction', eot_filter_url(['review_status' => 'returned'])); ?>
  <?php contract_stat('fa-calendar-days', format_number($summary['recommended_days']), 'Days Recommended', 'Current recommendation', eot_filter_url([])); ?>
</section>

<section class="contract-layout">
  <article class="contract-card card">
    <div class="contract-card__head">
      <div>
        <h2>EOT register</h2>
        <p>Filter requests and record clear consultant recommendations for the delivery team.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="contract-filter-grid" method="get" action="<?= Security::e(Url::to('admin/consultant/eot-review.php')) ?>">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="EOT, project or reason..."></label>
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
      <label>Delay
        <select name="delay_category">
          <option value="">All causes</option>
          <?php foreach (ConsultantContractDecision::DELAY_CATEGORIES as $category): ?>
            <option value="<?= Security::e($category) ?>" <?= $filters['delay_category'] === $category ? 'selected' : '' ?>><?= Security::e(status_label($category)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/eot-review.php')) ?>">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table contract-table">
        <thead><tr><th>EOT</th><th>Project</th><th>Days</th><th>Reason</th><th>Status</th><th>Review</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($items === []): ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <strong class="empty-state__title">No EOT requests found</strong>
                <span class="empty-state__text">Adjust filters or wait for extension requests on assigned projects.</span>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <?php $reviewStatus = (string)($item['consultant_review_status'] ?? 'pending'); ?>
          <tr>
            <td><span class="contract-primary">EOT #<?= (int)$item['eot_number'] ?></span><span class="contract-secondary"><?= Security::e(format_datetime($item['created_at'])) ?></span></td>
            <td><span class="contract-primary"><?= Security::e($item['project_name']) ?></span><span class="contract-secondary"><?= Security::e($item['submitter_name'] ?: 'Project team') ?></span></td>
            <td><span class="contract-primary"><?= format_number($item['days_requested']) ?> requested</span><span class="contract-secondary"><?= format_number($item['consultant_recommended_days'] ?? 0) ?> recommended</span></td>
            <td><span><?= Security::e(safe_truncate($item['reason'], 120)) ?></span><span class="contract-secondary"><?= Security::e(status_label($item['consultant_delay_category'] ?: 'other')) ?></span></td>
            <td><span class="badge <?= Security::e(ConsultantContractDecision::statusClass((string)$item['status'])) ?>"><?= Security::e(status_label($item['status'])) ?></span></td>
            <td><span class="badge <?= Security::e(ConsultantContractDecision::statusClass($reviewStatus)) ?>"><?= Security::e(status_label($reviewStatus)) ?></span><span class="contract-secondary"><?= Security::e($item['consultant_reviewer_name'] ?: '-') ?></span></td>
            <td><div class="contract-row-actions">
              <?php contract_action('eot', (int)$item['id'], 'recommend', 'Recommend', 'fa-check', 'EOT #' . (int)$item['eot_number'] . ' - ' . $item['project_name'], (int)$item['days_requested'], 0, 0); ?>
              <?php contract_action('eot', (int)$item['id'], 'return', 'Return', 'fa-rotate-left', 'EOT #' . (int)$item['eot_number'] . ' - ' . $item['project_name'], (int)$item['days_requested'], 0, 0); ?>
              <?php contract_action('eot', (int)$item['id'], 'flag', 'Flag', 'fa-flag', 'EOT #' . (int)$item['eot_number'] . ' - ' . $item['project_name'], (int)$item['days_requested'], 0, 0); ?>
              <?php contract_action('eot', (int)$item['id'], 'reject', 'Reject', 'fa-ban', 'EOT #' . (int)$item['eot_number'] . ' - ' . $item['project_name'], 0, 0, 0); ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php contract_pagination($page, $pages, $total, $limit); ?>
  </article>

  <aside class="contract-card card">
    <h2>Pending time decisions</h2>
    <p>Requests waiting for consultant action across your portfolio (not limited to this page).</p>
    <div class="contract-side-list">
      <?php if ($pendingItems === []): ?>
        <div class="empty-state empty-state--compact">
          <strong class="empty-state__title">No pending EOT requests</strong>
          <span class="empty-state__text">New extension requests will appear here.</span>
        </div>
      <?php else: foreach ($pendingItems as $item): ?>
        <a class="contract-side-item contract-side-item--link" href="<?= Security::e(Url::to('admin/consultant/eot-review.php?project_id=' . (int)$item['project_id'] . '&review_status=pending&q=' . rawurlencode('EOT #' . (int)$item['eot_number']))) ?>">
          <strong>EOT #<?= (int)$item['eot_number'] ?> · <?= Security::e($item['project_name']) ?></strong>
          <span><?= format_number($item['days_requested']) ?> days requested</span>
        </a>
      <?php endforeach; endif; ?>
    </div>
    <?php if ((int)$summary['pending_review'] > 0): ?>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(eot_filter_url(['review_status' => 'pending'])) ?>">View pending only</a>
    <?php endif; ?>
  </aside>
</section>

<?php contract_modal(); ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function eot_filter_url(array $extra): string
{
    $query = array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/consultant/eot-review.php' . ($query ? '?' . http_build_query($query) : ''));
}

function contract_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="contract-stat card"' . $href . '><span class="contract-stat__icon"><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e((string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function contract_action(string $type, int $id, string $action, string $label, string $icon, string $item, int $days, float $amount, int $time): void
{
    echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-contract-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label . ' contract item') . '" data-item="' . Security::e($item) . '" data-days="' . $days . '" data-amount="' . Security::e((string)$amount) . '" data-time="' . $time . '"><i class="fa-solid ' . Security::e($icon) . '"></i><span class="sr-only">' . Security::e($label) . '</span></button>';
}

function contract_pagination(int $page, int $pages, int $total, int $limit): void
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

function contract_modal(): void
{
    ?>
    <div class="contract-modal" data-contract-modal hidden>
      <div class="contract-modal__panel">
        <div class="contract-modal__head">
          <div>
            <strong data-modal-title>Review contract item</strong>
            <span class="contract-secondary" data-modal-item></span>
          </div>
          <button class="btn btn--sm btn--ghost" type="button" data-modal-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form>
          <input type="hidden" name="type"><input type="hidden" name="id"><input type="hidden" name="action">
          <div class="contract-modal__body">
            <label data-eot-field>Recommended days <input type="number" name="recommended_days" min="0" max="365"></label>
            <label data-eot-field>Delay cause
              <select name="delay_category">
                <?php foreach (ConsultantContractDecision::DELAY_CATEGORIES as $category): ?>
                  <option value="<?= Security::e($category) ?>"><?= Security::e(status_label($category)) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label data-variation-field>Recommended amount <input type="number" name="recommended_amount" min="0" step="0.01"></label>
            <label data-variation-field>Time impact days <input type="number" name="time_impact_days" min="0" max="365"></label>
            <label data-variation-field>Cost impact
              <select name="cost_impact_status">
                <?php foreach (ConsultantContractDecision::COST_IMPACTS as $impact): ?>
                  <option value="<?= Security::e($impact) ?>"><?= Security::e(status_label($impact)) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="span-2">Review note <textarea name="note" placeholder="Add a clear note for the project team."></textarea></label>
            <label class="span-2"><span><input type="checkbox" name="documents_checked" value="1"> Supporting documents checked</span></label>
          </div>
          <div class="contract-modal__foot">
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Review</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
