<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('consultant'));

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => trim((string)($_GET['status'] ?? '')),
    'risk' => trim((string)($_GET['risk'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$projects = ConsultantTechnicalReview::projects($userId, $role);
$summary = ConsultantTechnicalReview::boqSummary($userId, $role, $filters);
$items = ConsultantTechnicalReview::boqItems($userId, $role, $filters, $limit, $offset);
$total = ConsultantTechnicalReview::boqCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));

$pageTitle = 'BOQ Review';
$pageDescription = 'Review BOQ quantities, certification signals and cost risks for assigned projects.';
$adminRole = 'consultant';
$contentClass = 'consultant-technical-page';
$componentCss = ['consultant-technical'];
$pageScripts = ['consultant-technical'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant'],
    ['label' => 'BOQ Review'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="technical-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Quantity review</span>
    <h2>BOQ Review</h2>
    <p>Check measured quantities, certified values, paid quantities and items needing consultant attention.</p>
  </div>
  <div class="technical-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php')) ?>"><i class="fa-solid fa-inbox" aria-hidden="true"></i> IPC Inbox</a>
  </div>
</section>

<section class="technical-stats" aria-label="BOQ review summary">
  <?php technical_stat('fa-layer-group', $summary['total'], 'BOQ Items', 'Assigned portfolio'); ?>
  <?php technical_stat('fa-hourglass-half', $summary['pending'], 'Pending', 'Awaiting review'); ?>
  <?php technical_stat('fa-circle-check', $summary['reviewed'], 'Reviewed', 'Checked items'); ?>
  <?php technical_stat('fa-triangle-exclamation', $summary['risks'], 'Risk Items', 'Needs attention'); ?>
  <?php technical_stat('fa-coins', format_money($summary['certified_value']), 'Certified Value', 'Current exposure'); ?>
</section>

<section class="technical-layout">
  <article class="technical-card card">
    <div class="technical-card__head">
      <div>
        <h2>BOQ register</h2>
        <p>Filter items, record review decisions and send clear signals to the delivery team.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="technical-filter-grid" method="get">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Item, section or project..."></label>
      <label>Project
        <select name="project_id">
          <option value="0">All assigned projects</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>" <?= (int)$filters['project_id'] === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Review
        <select name="status">
          <option value="">All statuses</option>
          <?php foreach (['pending', 'reviewed', 'needs-review', 'needs-revision', 'escalated'] as $status): ?>
            <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Risk
        <select name="risk">
          <option value="">All risk levels</option>
          <?php foreach (['normal', 'watch', 'high', 'critical'] as $risk): ?>
            <option value="<?= Security::e($risk) ?>" <?= $filters['risk'] === $risk ? 'selected' : '' ?>><?= Security::e(status_label($risk)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/boq-review.php')) ?>">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table technical-table">
        <thead>
          <tr><th>Item</th><th>Project</th><th>Quantities</th><th>Values</th><th>Status</th><th>Last Review</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if ($items === []): ?>
            <tr><td colspan="7"><div class="technical-empty">No BOQ items found.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($items as $item): ?>
            <?php
              $amount = (float)($item['amount'] ?: ((float)$item['quantity'] * (float)$item['rate']));
              $certifiedValue = (float)$item['certified_qty'] * (float)$item['rate'];
            ?>
            <tr>
              <td>
                <span class="technical-primary"><?= Security::e($item['item_no']) ?></span>
                <span class="technical-secondary"><?= Security::e($item['section']) ?></span>
                <span class="technical-secondary"><?= Security::e(safe_truncate($item['description'], 110)) ?></span>
              </td>
              <td>
                <span class="technical-primary"><?= Security::e($item['project_name']) ?></span>
                <span class="technical-secondary"><?= Security::e($item['constituency_name'] ?? '-') ?></span>
              </td>
              <td>
                <span><?= format_number($item['quantity']) ?> <?= Security::e($item['unit']) ?></span>
                <span class="technical-secondary">Certified <?= format_number($item['certified_qty']) ?> / Paid <?= format_number($item['paid_qty']) ?></span>
              </td>
              <td>
                <span class="technical-primary"><?= format_money($amount) ?></span>
                <span class="technical-secondary">Certified <?= format_money($certifiedValue) ?></span>
              </td>
              <td>
                <span class="badge <?= Security::e(ConsultantTechnicalReview::statusClass((string)$item['review_status'])) ?>"><?= Security::e(status_label($item['review_status'] ?? 'pending')) ?></span>
                <span class="technical-secondary"><?= Security::e(status_label($item['risk_status'] ?? 'normal')) ?> risk</span>
              </td>
              <td>
                <span><?= Security::e($item['reviewed_by_name'] ?: '-') ?></span>
                <span class="technical-secondary"><?= Security::e(format_datetime($item['last_reviewed_at'] ?? null)) ?></span>
              </td>
              <td>
                <div class="technical-row-actions">
                  <?php technical_action('boq', (int)$item['id'], 'review', 'Review', 'fa-check', $item['item_no'] . ' - ' . $item['project_name']); ?>
                  <?php technical_action('boq', (int)$item['id'], 'return', 'Return', 'fa-rotate-left', $item['item_no'] . ' - ' . $item['project_name']); ?>
                  <?php technical_action('boq', (int)$item['id'], 'flag', 'Flag', 'fa-flag', $item['item_no'] . ' - ' . $item['project_name']); ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php technical_pagination($page, $pages, $total, $limit); ?>
  </article>

  <aside class="technical-card card">
    <h2>Attention list</h2>
    <p>Items with quantity or payment risk appear first.</p>
    <div class="technical-side-list">
      <?php foreach (array_slice(array_filter($items, static fn ($item): bool => in_array((string)($item['risk_status'] ?? 'normal'), ['watch', 'high', 'critical'], true)), 0, 6) as $item): ?>
        <div class="technical-side-item">
          <strong><?= Security::e($item['item_no']) ?> - <?= Security::e($item['section']) ?></strong>
          <span><?= Security::e($item['project_name']) ?> / <?= Security::e(status_label($item['risk_status'])) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if ($summary['risks'] <= 0): ?>
        <div class="technical-empty">No risk items in the current view.</div>
      <?php endif; ?>
    </div>
  </aside>
</section>

<?php technical_modal(); ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function technical_stat(string $icon, mixed $value, string $label, string $hint): void
{
    echo '<article class="technical-stat card"><span class="technical-stat__icon"><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e((string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></article>';
}

function technical_action(string $type, int $id, string $action, string $label, string $icon, string $item): void
{
    echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-technical-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label . ' item') . '" data-item="' . Security::e($item) . '"><i class="fa-solid ' . Security::e($icon) . '"></i><span class="sr-only">' . Security::e($label) . '</span></button>';
}

function technical_pagination(int $page, int $pages, int $total, int $limit): void
{
    echo '<div class="pagination"><span>Showing ' . format_number(min($total, (($page - 1) * $limit) + 1)) . '-' . format_number(min($total, $page * $limit)) . ' of ' . format_number($total) . '</span><div>';
    $query = $_GET;
    $query['page'] = max(1, $page - 1);
    echo '<a class="btn btn--sm btn--outline" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left"></i></a>';
    echo '<span class="btn btn--sm btn--primary">' . $page . '</span>';
    $query['page'] = min($pages, $page + 1);
    echo '<a class="btn btn--sm btn--outline" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right"></i></a></div></div>';
}

function technical_modal(): void
{
    ?>
    <div class="technical-modal" data-technical-modal hidden>
      <div class="technical-modal__panel">
        <div class="technical-modal__head">
          <div><strong data-modal-title>Review item</strong><span class="technical-secondary" data-modal-item></span></div>
          <button class="btn btn--sm btn--ghost" type="button" data-modal-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form>
          <input type="hidden" name="type">
          <input type="hidden" name="id">
          <input type="hidden" name="action">
          <div class="technical-modal__body">
            <label>Review note <textarea name="note" placeholder="Add a clear note for the project team."></textarea></label>
          </div>
          <div class="technical-modal__foot">
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Review</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
