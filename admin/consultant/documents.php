<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'category' => trim((string)($_GET['category'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$projects = ConsultantDocumentCentre::projects($userId, $role);
$summary = ConsultantDocumentCentre::documentSummary($userId, $role, $filters);
$documents = ConsultantDocumentCentre::documents($userId, $role, $filters, $limit, $offset);
$total = ConsultantDocumentCentre::documentCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));
$attention = ConsultantDocumentCentre::documentAttentionItems($userId, $role, $filters, 8);

$pageTitle = 'Documents';
$pageDescription = 'Review project documents, technical records and uploaded contract controls.';
$adminRole = 'consultant';
$contentClass = 'consultant-documents-page doc-page';
$componentCss = ['consultant-documents'];
$pageScripts = ['consultant-documents'];
$csrfForm = 'consultant_documents';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'Documents'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="doc-hero card" data-documents-app data-endpoint="<?= Security::e(Url::to('api/consultant/document-action.php')) ?>">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-folder-open" aria-hidden="true"></i> Document review</span>
    <h2>Documents</h2>
    <p>Browse assigned project files, check current versions and record consultant review actions on your portfolio only.</p>
  </div>
  <div class="doc-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/site-reports.php')) ?>"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Site Reports</a>
  </div>
</section>

<section class="doc-stats" aria-label="Document summary">
  <?php doc_stat('fa-folder-open', $summary['total'], 'Documents', 'Assigned portfolio', doc_filter_url([])); ?>
  <?php doc_stat('fa-clock', $summary['new_week'], 'This Week', 'Recently uploaded', doc_filter_url([])); ?>
  <?php doc_stat('fa-drafting-compass', $summary['drawings'], 'Drawings', 'Technical submissions', doc_filter_url(['category' => 'drawing'])); ?>
  <?php doc_stat('fa-file-lines', $summary['reports'], 'Reports', 'Field records', doc_filter_url(['category' => 'report'])); ?>
  <?php doc_stat('fa-scale-balanced', $summary['controls'], 'Controls', 'Contracts and specs', doc_filter_url(['category' => 'contract'])); ?>
  <?php doc_stat('fa-hourglass-half', $summary['pending'], 'Pending Review', 'Needs consultant action', doc_filter_url(['status' => 'pending'])); ?>
</section>

<section class="doc-layout">
  <article class="doc-card card">
    <div class="doc-card__head">
      <div>
        <h2>Document register</h2>
        <p>Filter, review, return or flag documents from assigned project records.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="doc-filter-grid" method="get" action="<?= Security::e(Url::to('admin/consultant/documents.php')) ?>">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Document, project or description..."></label>
      <label>Project
        <select name="project_id">
          <option value="0">All assigned projects</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>" <?= (int)$filters['project_id'] === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Category
        <select name="category">
          <option value="">All categories</option>
          <?php foreach (ConsultantDocumentCentre::DOCUMENT_CATEGORIES as $category): ?>
            <option value="<?= Security::e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= Security::e(status_label($category)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Review
        <select name="status">
          <option value="">All statuses</option>
          <?php foreach (ConsultantDocumentCentre::REVIEW_STATUSES as $status): ?>
            <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>From <input type="date" name="from" value="<?= Security::e($filters['from']) ?>"></label>
      <label>To <input type="date" name="to" value="<?= Security::e($filters['to']) ?>"></label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/documents.php')) ?>">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table doc-table">
        <thead>
          <tr><th>Document</th><th>Project</th><th>Category</th><th>Uploaded</th><th>Review</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if ($documents === []): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <strong class="empty-state__title">No documents found</strong>
                  <span class="empty-state__text">Adjust filters or wait for project files on your assigned portfolio.</span>
                </div>
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($documents as $document): ?>
            <?php $fileAvailable = ConsultantDocumentCentre::fileAvailable($document); ?>
            <tr>
              <td>
                <span class="doc-primary"><?= Security::e($document['original_name']) ?></span>
                <span class="doc-secondary"><?= Security::e(safe_truncate($document['description'] ?? 'No description added.', 120)) ?></span>
                <span class="doc-secondary">Version <?= Security::e($document['version'] ?? '1.0') ?> / <?= Security::e(doc_size((int)($document['size'] ?? 0))) ?></span>
              </td>
              <td>
                <span class="doc-primary"><?= Security::e($document['project_name']) ?></span>
                <span class="doc-secondary"><?= Security::e($document['constituency_name'] ?? '-') ?></span>
              </td>
              <td>
                <span class="badge badge--info"><?= Security::e(status_label($document['category'] ?? 'other')) ?></span>
                <?php if ((int)($document['is_confidential'] ?? 0) === 1): ?><span class="doc-secondary">Restricted file</span><?php endif; ?>
              </td>
              <td>
                <span><?= Security::e(trim((string)$document['uploaded_by_name']) ?: 'System') ?></span>
                <span class="doc-secondary"><?= Security::e(format_datetime($document['created_at'] ?? null)) ?></span>
              </td>
              <td>
                <span class="badge <?= Security::e(ConsultantDocumentCentre::statusClass($document['consultant_review_status'] ?? 'pending')) ?>"><?= Security::e(status_label($document['consultant_review_status'] ?? 'pending')) ?></span>
                <span class="doc-secondary doc-note-preview"><?= Security::e(safe_truncate($document['consultant_review_note'] ?? '', 80)) ?></span>
              </td>
              <td>
                <div class="doc-row-actions">
                  <?php if ($fileAvailable): ?>
                    <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to($document['filename'])) ?>" target="_blank" rel="noopener" aria-label="Open document" title="Open document"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i></a>
                    <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to($document['filename'])) ?>" download aria-label="Download document" title="Download document"><i class="fa-solid fa-download" aria-hidden="true"></i></a>
                    <button class="btn btn--sm btn--outline" type="button" data-copy-link="<?= Security::e(Url::to($document['filename'])) ?>" aria-label="Copy document link" title="Copy document link"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
                  <?php else: ?>
                    <button class="btn btn--sm btn--outline" type="button" aria-disabled="true" aria-label="File pending upload" title="File pending upload"><i class="fa-solid fa-file-circle-exclamation" aria-hidden="true"></i></button>
                  <?php endif; ?>
                  <?php doc_action('document', (int)$document['id'], 'review', 'Mark reviewed', 'fa-check', $document['original_name']); ?>
                  <?php doc_action('document', (int)$document['id'], 'return', 'Return document', 'fa-rotate-left', $document['original_name']); ?>
                  <?php doc_action('document', (int)$document['id'], 'flag', 'Flag document', 'fa-flag', $document['original_name']); ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php doc_pagination($page, $pages, $total, $limit); ?>
  </article>

  <aside class="doc-card card">
    <h2>Review focus</h2>
    <p>Pending, returned and flagged files across your portfolio (not limited to this page).</p>
    <div class="doc-side-list">
      <?php if ($attention === []): ?>
        <div class="empty-state empty-state--compact">
          <strong class="empty-state__title">No open document reviews</strong>
          <span class="empty-state__text">Items needing action will appear here.</span>
        </div>
      <?php else: foreach ($attention as $document): ?>
        <a class="doc-side-item doc-side-item--link" href="<?= Security::e(Url::to('admin/consultant/documents.php?project_id=' . (int)$document['project_id'] . '&status=' . rawurlencode((string)($document['consultant_review_status'] ?? 'pending')) . '&q=' . rawurlencode((string)$document['original_name']))) ?>">
          <strong><?= Security::e($document['original_name']) ?></strong>
          <span><?= Security::e($document['project_name']) ?> · <?= Security::e(status_label($document['consultant_review_status'] ?? 'pending')) ?></span>
        </a>
      <?php endforeach; endif; ?>
    </div>
    <?php if ((int)$summary['pending'] > 0): ?>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(doc_filter_url(['status' => 'pending'])) ?>">View pending only</a>
    <?php endif; ?>
  </aside>
</section>

<?php doc_modal(); ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function doc_filter_url(array $extra): string
{
    $query = array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/consultant/documents.php' . ($query ? '?' . http_build_query($query) : ''));
}

function doc_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="doc-stat card"' . $href . '><span class="doc-stat__icon"><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e(is_numeric($value) ? format_number($value) : (string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function doc_action(string $type, int $id, string $action, string $label, string $icon, string $item): void
{
    echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-doc-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label) . '" data-item="' . Security::e($item) . '"><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i><span class="sr-only">' . Security::e($label) . '</span></button>';
}

function doc_pagination(int $page, int $pages, int $total, int $limit): void
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
    echo '<a class="btn btn--sm btn--outline' . $prevDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>';
    echo '<span class="btn btn--sm btn--primary">' . format_number($page) . ' / ' . format_number($pages) . '</span>';
    $query['page'] = min($pages, $page + 1);
    $nextDis = $page >= $pages ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $nextDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div></div>';
}

function doc_size(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 KB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    return number_format(max(1, $bytes / 1024), 0) . ' KB';
}

function doc_modal(): void
{
    ?>
    <div class="doc-modal" data-doc-modal hidden>
      <div class="doc-modal__panel" role="dialog" aria-modal="true" aria-labelledby="docModalTitle">
        <div class="doc-modal__head">
          <div>
            <strong id="docModalTitle" data-modal-title>Review</strong>
            <span class="doc-secondary" data-modal-item></span>
          </div>
          <button class="btn btn--sm btn--ghost" type="button" data-modal-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <form>
          <input type="hidden" name="type">
          <input type="hidden" name="id">
          <input type="hidden" name="action">
          <div class="doc-modal__body">
            <label>Review note <textarea name="note" placeholder="Add a clear note for the project team."></textarea></label>
          </div>
          <div class="doc-modal__foot">
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Review</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
