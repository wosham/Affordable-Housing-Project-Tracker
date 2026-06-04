<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('consultant'));

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => trim((string)($_GET['status'] ?? '')),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$projects = ConsultantTechnicalReview::projects($userId, $role);
$summary = ConsultantTechnicalReview::drawingSummary($userId, $role, $filters);
$items = ConsultantTechnicalReview::drawingItems($userId, $role, $filters, $limit, $offset);
$total = ConsultantTechnicalReview::drawingCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));

$pageTitle = 'Shop Drawings';
$pageDescription = 'Review submitted shop drawings, revisions and coordination status.';
$adminRole = 'consultant';
$contentClass = 'consultant-technical-page';
$componentCss = ['consultant-technical'];
$pageScripts = ['consultant-technical'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant'],
    ['label' => 'Shop Drawings'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="technical-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-compass-drafting" aria-hidden="true"></i> Drawing review</span>
    <h2>Shop Drawings</h2>
    <p>Review submitted drawings, manage revisions and return items that need correction before site use.</p>
  </div>
  <div class="technical-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/material-approvals.php')) ?>"><i class="fa-solid fa-cubes-stacked"></i> Materials</a>
  </div>
</section>

<section class="technical-stats">
  <?php technical_stat('fa-drafting-compass', $summary['total'], 'Drawings', 'Assigned projects'); ?>
  <?php technical_stat('fa-hourglass-half', $summary['under_review'], 'Under Review', 'Awaiting action'); ?>
  <?php technical_stat('fa-circle-check', $summary['approved'], 'Approved', 'Accepted drawings'); ?>
  <?php technical_stat('fa-rotate-left', $summary['resubmit'], 'Resubmit', 'Needs revision'); ?>
  <?php technical_stat('fa-ban', $summary['rejected'], 'Rejected', 'Not accepted'); ?>
</section>

<section class="technical-layout">
  <article class="technical-card card">
    <div class="technical-card__head">
      <div>
        <h2>Drawing register</h2>
        <p>Review drawing submissions and keep the project team aligned on revision status.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="technical-filter-grid" method="get">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Drawing number, title or project..."></label>
      <label>Project
        <select name="project_id">
          <option value="0">All assigned projects</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>" <?= (int)$filters['project_id'] === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Status
        <select name="status">
          <option value="">All statuses</option>
          <?php foreach (['under-review', 'approved', 'resubmit', 'rejected'] as $status): ?>
            <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>From <input type="date" name="from" value="<?= Security::e($filters['from']) ?>"></label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/shop-drawings.php')) ?>">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table technical-table">
        <thead><tr><th>Drawing</th><th>Project</th><th>Revision</th><th>Submitted</th><th>Status</th><th>Review</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="7"><div class="technical-empty">No shop drawings found.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <span class="technical-primary"><?= Security::e($item['drawing_no']) ?></span>
              <span class="technical-secondary"><?= Security::e($item['title']) ?></span>
            </td>
            <td>
              <span class="technical-primary"><?= Security::e($item['project_name']) ?></span>
              <span class="technical-secondary"><?= Security::e($item['constituency_name'] ?? '-') ?></span>
            </td>
            <td><span class="badge badge--info">Rev <?= Security::e($item['revision']) ?></span></td>
            <td>
              <span><?= Security::e(format_date($item['submitted_date'])) ?></span>
              <span class="technical-secondary">By <?= Security::e($item['submitted_by_name'] ?: 'Project team') ?></span>
            </td>
            <td><span class="badge <?= Security::e(ConsultantTechnicalReview::statusClass((string)$item['status'])) ?>"><?= Security::e(status_label($item['status'])) ?></span></td>
            <td>
              <span><?= Security::e($item['reviewed_by_name'] ?: '-') ?></span>
              <span class="technical-secondary"><?= Security::e(format_date($item['review_date'] ?? null)) ?></span>
              <span class="technical-secondary"><?= Security::e($item['review_note'] ? safe_truncate($item['review_note'], 70) : '') ?></span>
            </td>
            <td><div class="technical-row-actions">
              <?php technical_action('drawing', (int)$item['id'], 'approve', 'Approve', 'fa-check', $item['drawing_no'] . ' - ' . $item['project_name']); ?>
              <?php technical_action('drawing', (int)$item['id'], 'return', 'Resubmit', 'fa-rotate-left', $item['drawing_no'] . ' - ' . $item['project_name']); ?>
              <?php technical_action('drawing', (int)$item['id'], 'reject', 'Reject', 'fa-ban', $item['drawing_no'] . ' - ' . $item['project_name']); ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php technical_pagination($page, $pages, $total, $limit); ?>
  </article>

  <aside class="technical-card card">
    <h2>Revision queue</h2>
    <p>Drawings needing consultant action or resubmission.</p>
    <div class="technical-side-list">
      <?php foreach (array_slice(array_filter($items, static fn ($item): bool => in_array((string)$item['status'], ['under-review', 'resubmit'], true)), 0, 7) as $item): ?>
        <div class="technical-side-item"><strong><?= Security::e($item['drawing_no']) ?> / Rev <?= Security::e($item['revision']) ?></strong><span><?= Security::e($item['project_name']) ?> / <?= Security::e(status_label($item['status'])) ?></span></div>
      <?php endforeach; ?>
      <?php if ((int)$summary['under_review'] + (int)$summary['resubmit'] <= 0): ?><div class="technical-empty">No drawings waiting in this view.</div><?php endif; ?>
    </div>
  </aside>
</section>

<?php technical_modal(); ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function technical_stat(string $icon, mixed $value, string $label, string $hint): void { echo '<article class="technical-stat card"><span class="technical-stat__icon"><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e((string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></article>'; }
function technical_action(string $type, int $id, string $action, string $label, string $icon, string $item): void { echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-technical-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label . ' drawing') . '" data-item="' . Security::e($item) . '"><i class="fa-solid ' . Security::e($icon) . '"></i><span class="sr-only">' . Security::e($label) . '</span></button>'; }
function technical_pagination(int $page, int $pages, int $total, int $limit): void { $query = $_GET; echo '<div class="pagination"><span>Showing ' . format_number($total === 0 ? 0 : (($page - 1) * $limit) + 1) . '-' . format_number(min($total, $page * $limit)) . ' of ' . format_number($total) . '</span><div>'; $query['page'] = max(1, $page - 1); echo '<a class="btn btn--sm btn--outline" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left"></i></a><span class="btn btn--sm btn--primary">' . $page . '</span>'; $query['page'] = min($pages, $page + 1); echo '<a class="btn btn--sm btn--outline" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right"></i></a></div></div>'; }
function technical_modal(): void { ?><div class="technical-modal" data-technical-modal hidden><div class="technical-modal__panel"><div class="technical-modal__head"><div><strong data-modal-title>Review item</strong><span class="technical-secondary" data-modal-item></span></div><button class="btn btn--sm btn--ghost" type="button" data-modal-close><i class="fa-solid fa-xmark"></i></button></div><form><input type="hidden" name="type"><input type="hidden" name="id"><input type="hidden" name="action"><div class="technical-modal__body"><label>Review note <textarea name="note" placeholder="Add a clear note for the project team."></textarea></label></div><div class="technical-modal__foot"><button class="btn btn--outline" type="button" data-modal-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Review</button></div></form></div></div><?php }
