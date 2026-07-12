<?php

$submissionType = (string)($submissionType ?? '');
$submissionConfig = ContractorSubmission::config($submissionType);
$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$requestedProjectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectId = ContractorProject::defaultProjectId($userId, $role, $requestedProjectId);
$projects = ContractorProject::projects($userId, $role);
$project = $projectId > 0 ? ContractorProject::detail($projectId, $userId, $role) : null;

$bucket = strtolower(trim((string)($_GET['bucket'] ?? '')));
if (!in_array($bucket, ['pending', 'accepted', 'returned', 'closed', 'month'], true)) {
    $bucket = '';
}
$statusFilter = in_array((string)($_GET['status'] ?? ''), $submissionConfig['statuses'], true) ? (string)$_GET['status'] : '';

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => $statusFilter,
    'bucket' => $bucket,
    'project_id' => $projectId,
];

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1, 1));
$total = ContractorSubmission::count($submissionType, $userId, $role, $filters);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$items = ContractorSubmission::list($submissionType, $userId, $role, $filters, $perPage, ($page - 1) * $perPage);
$stats = ContractorSubmission::stats($submissionType, $userId, $role, ['project_id' => $projectId]);
$fields = ContractorSubmission::fields($submissionType);
$peerTypes = ContractorSubmission::types();
?>

<section class="contractor-submission-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid <?= Security::e($submissionConfig['icon']) ?>" aria-hidden="true"></i> <?= Security::e($submissionConfig['label']) ?></span>
    <h2><?= Security::e($submissionConfig['plural']) ?></h2>
    <p><?= Security::e(contractor_submission_intro($submissionType)) ?></p>
  </div>
  <div class="contractor-submission-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/my-project.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> My Project</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
  </div>
</section>

<nav class="contractor-submission-peers card" aria-label="Submission types">
  <?php foreach ($peerTypes as $peerKey => $peerConfig): ?>
    <?php
      $peerQuery = $projectId ? ('?project_id=' . $projectId) : '';
      $isActive = $peerKey === $submissionType;
    ?>
    <a class="contractor-submission-peer<?= $isActive ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($peerConfig['page'] . $peerQuery)) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid <?= Security::e($peerConfig['icon']) ?>" aria-hidden="true"></i>
      <span><?= Security::e($peerConfig['plural']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if ($projects !== []): ?>
<form class="contractor-submission-selector card" method="get">
  <label for="project_id">Project</label>
  <select id="project_id" name="project_id" onchange="this.form.submit()">
    <?php foreach ($projects as $option): ?>
      <option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php endif; ?>

<?php if (!$project): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Assigned project records will appear here once configured.</span></div>
<?php else: ?>

<section class="contractor-submission-stats" aria-label="<?= Security::e($submissionConfig['plural']) ?> summary">
  <?php contractor_submission_stat('fa-layer-group', $stats['total'], 'Total', 'All records', contractor_submission_url($submissionConfig['page'], $projectId, ['bucket' => ''])); ?>
  <?php contractor_submission_stat('fa-hourglass-half', $stats['pending'], 'Under Review', 'Awaiting response', contractor_submission_url($submissionConfig['page'], $projectId, ['bucket' => 'pending'])); ?>
  <?php contractor_submission_stat('fa-circle-check', $stats['accepted'], 'Accepted', 'Completed records', contractor_submission_url($submissionConfig['page'], $projectId, ['bucket' => 'accepted'])); ?>
  <?php if ($submissionType === 'rfi'): ?>
    <?php contractor_submission_stat('fa-box-archive', $stats['closed'], 'Closed', 'Closed RFIs', contractor_submission_url($submissionConfig['page'], $projectId, ['bucket' => 'closed'])); ?>
  <?php else: ?>
    <?php contractor_submission_stat('fa-rotate-left', $stats['returned'], 'Returned', 'Needs attention', contractor_submission_url($submissionConfig['page'], $projectId, ['bucket' => 'returned'])); ?>
  <?php endif; ?>
  <?php contractor_submission_stat('fa-calendar-day', $stats['this_month'], 'This Month', 'Recent submissions', contractor_submission_url($submissionConfig['page'], $projectId, ['bucket' => 'month'])); ?>
  <?php contractor_submission_stat(
      contractor_submission_impact_icon($submissionType),
      contractor_submission_impact_value($submissionType, $stats),
      contractor_submission_impact_label($submissionType),
      contractor_submission_impact_hint($submissionType),
      ''
  ); ?>
</section>

<section class="contractor-submission-grid">
  <main class="card contractor-submission-panel">
    <div class="card__header">
      <div>
        <h2 class="card__title">New <?= Security::e($submissionConfig['title']) ?></h2>
        <p class="card__subtitle">Complete the required details and submit the record for review.</p>
      </div>
    </div>

    <form class="contractor-submission-form" data-contractor-submission action="<?= Security::e(Url::to($submissionConfig['api'])) ?>">
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
      <div class="contractor-submission-fields">
        <?php foreach ($fields as $field): ?>
          <?= contractor_submission_field($field) ?>
        <?php endforeach; ?>
      </div>
      <p class="contractor-submission-help">Add clear references for drawings, letters, photos or site records where they support the submission.</p>
      <div class="contractor-submission-form__footer">
        <span data-submission-status></span>
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit</button>
      </div>
    </form>
  </main>

  <aside class="card contractor-submission-panel">
    <h2>Project Context</h2>
    <div class="contractor-submission-context">
      <span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?></small></span>
      <span><strong><?= Security::e(status_label((string)($project['status'] ?? ''))) ?></strong><small>Project status</small></span>
      <span><strong><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</strong><small>Project progress</small></span>
      <span><strong><?= Security::e(format_date($project['est_delivery'] ?? null)) ?></strong><small>Target delivery</small></span>
    </div>
  </aside>
</section>

<section class="card contractor-submission-panel">
  <div class="card__header">
    <div>
      <h2 class="card__title"><?= Security::e($submissionConfig['plural']) ?> Register</h2>
      <p class="card__subtitle">Search submitted records and track their current status.</p>
    </div>
    <span class="badge badge--info"><?= format_number($total) ?> records</span>
  </div>

  <form class="contractor-submission-filter" method="get">
    <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
    <?php if ($bucket !== ''): ?><input type="hidden" name="bucket" value="<?= Security::e($bucket) ?>"><?php endif; ?>
    <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Search records..."></label>
    <label><span>Status</span>
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach ($submissionConfig['statuses'] as $status): ?>
          <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(contractor_submission_status_label($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="contractor-submission-filter__actions">
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(contractor_submission_url($submissionConfig['page'], $projectId, [])) ?>">Reset</a>
    </div>
  </form>

  <?php if ($bucket !== '' || $statusFilter !== '' || $filters['q'] !== ''): ?>
    <p class="contractor-submission-filter-note">
      Showing filtered results
      <?php if ($bucket !== ''): ?> · bucket: <strong><?= Security::e(status_label($bucket === 'month' ? 'this-month' : $bucket)) ?></strong><?php endif; ?>
      <?php if ($statusFilter !== ''): ?> · status: <strong><?= Security::e(contractor_submission_status_label($statusFilter)) ?></strong><?php endif; ?>
      <?php if ($filters['q'] !== ''): ?> · search: <strong><?= Security::e($filters['q']) ?></strong><?php endif; ?>
    </p>
  <?php endif; ?>

  <div class="contractor-submission-table-wrap">
    <table class="contractor-submission-table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Project</th>
          <th>Details</th>
          <th>Impact</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($items === []): ?>
        <tr><td colspan="7"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No records found</strong><span class="empty-state__text">Create a new submission or adjust the current filters.</span></div></td></tr>
      <?php endif; ?>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><strong><?= Security::e(contractor_submission_reference($submissionType, $item)) ?></strong><small><?= Security::e(contractor_submission_secondary($submissionType, $item)) ?></small></td>
          <td><?= Security::e($item['project_name']) ?><small><?= Security::e(trim(($item['constituency_name'] ?? '') . ' / ' . ($item['ward_name'] ?? ''), ' /')) ?></small></td>
          <td><strong><?= Security::e(contractor_submission_title($submissionType, $item)) ?></strong><small><?= Security::e(safe_truncate(contractor_submission_description($submissionType, $item), 95)) ?></small><?= contractor_submission_support_label($item) ?></td>
          <td><?= Security::e(contractor_submission_impact($submissionType, $item)) ?></td>
          <td><span class="badge <?= Security::e(status_badge_class((string)$item['status'])) ?>"><?= Security::e(contractor_submission_status_label((string)$item['status'])) ?></span></td>
          <td><?= Security::e(format_date($item[$submissionConfig['date']] ?? null)) ?><small><?= Security::e(time_ago($item[$submissionConfig['date']] ?? null)) ?></small></td>
          <td>
            <button class="btn btn--sm btn--outline" type="button" data-submission-open="<?= (int)$item['id'] ?>" data-submission-type="<?= Security::e($submissionType) ?>" title="View details">
              <i class="fa-solid fa-eye" aria-hidden="true"></i><span class="sr-only">View</span>
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php contractor_submission_pagination($page, $pages, $total, $perPage); ?>
</section>

<div class="contractor-submission-overlay" data-submission-detail-modal hidden aria-hidden="true">
  <div class="contractor-submission-modal" role="dialog" aria-modal="true" aria-labelledby="submissionDetailTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid <?= Security::e($submissionConfig['icon']) ?>" aria-hidden="true"></i> Submission detail</span>
        <h2 id="submissionDetailTitle" data-submission-detail-title><?= Security::e($submissionConfig['title']) ?></h2>
        <p data-submission-detail-sub></p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-submission-detail-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </header>
    <div class="contractor-submission-modal__body" data-submission-detail-body>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">Select a record</strong></div>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-submission-detail-close>Close</button>
    </footer>
  </div>
</div>

<?php endif; ?>

<?php
function contractor_submission_intro(string $type): string
{
    return match ($type) {
        'eot' => 'Submit time-related project events with clear reasons and supporting references.',
        'variation' => 'Record project change requests with cost and time impact details.',
        'rfi' => 'Raise project questions and track responses in one place.',
        'shop_drawing' => 'Submit drawing records, revisions and document references for review.',
        'material' => 'Submit material details, specifications and supporting notes for review.',
        default => 'Submit and track formal project records.',
    };
}

function contractor_submission_url(string $page, int $projectId, array $extra = []): string
{
    $query = array_filter(array_merge([
        'project_id' => $projectId > 0 ? $projectId : '',
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => trim((string)($_GET['status'] ?? '')),
    ], $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    if (array_key_exists('bucket', $extra) && ($extra['bucket'] === '' || $extra['bucket'] === null)) {
        unset($query['bucket']);
    }
    return Url::to($page . ($query ? '?' . http_build_query($query) : ''));
}

function contractor_submission_field(array $field): string
{
    $name = Security::e($field['name']);
    $label = Security::e($field['label'] . (!empty($field['required']) ? ' *' : ''));
    $required = !empty($field['required']) ? ' required' : '';
    $placeholder = Security::e($field['placeholder'] ?? '');
    $fieldType = (string)($field['type'] ?? 'text');
    $isWide = in_array($fieldType, ['textarea', 'media'], true)
        || in_array((string)($field['name'] ?? ''), ['attachment_reference', 'supporting_evidence', 'document_path', 'attachment_title'], true);
    $html = '<label class="' . ($isWide ? 'is-wide' : '') . '"><span>' . $label . '</span>';
    if ($fieldType === 'textarea') {
        return $html . '<textarea name="' . $name . '" rows="4" placeholder="' . $placeholder . '"' . $required . '></textarea></label>';
    }
    if ($fieldType === 'select') {
        $options = '';
        foreach (($field['options'] ?? []) as $value => $text) {
            if (is_int($value)) {
                $value = $text;
            }
            $options .= '<option value="' . Security::e((string)$value) . '">' . Security::e((string)$text) . '</option>';
        }
        return $html . '<select name="' . $name . '"' . $required . '>' . $options . '</select></label>';
    }
    if ($fieldType === 'media') {
        $help = Security::e((string)($field['help'] ?? 'Optional. Choose a library file or upload supporting evidence.'));
        return $html
            . '<div class="contractor-submission-media" data-cms-upload data-upload-folder="contractor-documents" data-media-kind="document" data-submission-media>'
            . '<div class="contractor-submission-media__preview" data-cms-asset-preview><span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span></div>'
            . '<div class="contractor-submission-media__body">'
            . '<strong data-cms-asset-name>No file selected</strong>'
            . '<small>' . $help . '</small>'
            . '<input type="hidden" name="media_id" value="" data-media-id-target>'
            . '<input type="hidden" name="' . $name . '" value="" data-cms-upload-target data-media-picker-value="path">'
            . '<div class="contractor-submission-media__actions">'
            . '<button class="btn btn--outline btn--sm" type="button" data-media-picker-open data-media-picker-folder="contractor-documents" data-media-picker-type="document" data-media-picker-accept="image/*,application/pdf,.pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp" data-media-picker-title="Choose supporting file"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose file</button>'
            . '<button class="btn btn--outline btn--sm" type="button" data-media-picker-open data-media-picker-folder="site-photos" data-media-picker-type="image" data-media-picker-accept="image/*" data-media-picker-title="Choose site photo"><i class="fa-solid fa-camera" aria-hidden="true"></i> Site photo</button>'
            . '<button class="btn btn--outline btn--sm" type="button" data-submission-media-clear><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</button>'
            . '</div></div></div></label>';
    }
    $type = Security::e($fieldType);
    $step = isset($field['step']) ? ' step="' . Security::e((string)$field['step']) . '"' : '';
    $min = isset($field['min']) ? ' min="' . Security::e((string)$field['min']) . '"' : '';
    $inputMode = $type === 'number' ? ' inputmode="decimal"' : '';
    return $html . '<input type="' . $type . '" name="' . $name . '" placeholder="' . $placeholder . '"' . $required . $step . $min . $inputMode . '></label>';
}

function contractor_submission_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="contractor-submission-stat card"' . $href . '><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><b>' . Security::e($label) . '</b><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function contractor_submission_reference(string $type, array $item): string
{
    return match ($type) {
        'eot' => 'EOT #' . (int)$item['eot_number'],
        'variation' => 'VAR #' . (int)$item['vo_number'],
        'rfi' => 'RFI #' . (int)$item['rfi_number'],
        'shop_drawing' => (string)($item['drawing_no'] ?? 'Drawing'),
        'material' => 'MAT #' . (int)($item['id'] ?? 0),
        default => '#' . (int)($item['id'] ?? 0),
    };
}

function contractor_submission_secondary(string $type, array $item): string
{
    return match ($type) {
        'shop_drawing' => 'Revision ' . ($item['revision'] ?? 'A'),
        'material' => $item['material'] ?? '',
        'rfi' => contractor_submission_status_label((string)($item['urgency'] ?? 'normal')),
        default => contractor_submission_status_label((string)($item['status'] ?? '')),
    };
}

function contractor_submission_title(string $type, array $item): string
{
    return match ($type) {
        'eot' => status_label((string)($item['delay_category'] ?? 'other')),
        'variation' => safe_truncate((string)($item['description'] ?? 'Variation request'), 60),
        'rfi' => $item['subject'] ?? 'RFI',
        'shop_drawing' => $item['title'] ?? 'Shop drawing',
        'material' => $item['material'] ?? 'Material',
        default => 'Submission',
    };
}

function contractor_submission_description(string $type, array $item): string
{
    return match ($type) {
        'eot' => $item['impact_summary'] ?: ($item['reason'] ?? ''),
        'variation' => $item['reason'] ?? '',
        'rfi' => $item['description'] ?? '',
        'shop_drawing' => $item['document_path'] ?? 'Drawing record',
        'material' => $item['specification'] ?: ($item['notes'] ?? ''),
        default => '',
    };
}

function contractor_submission_impact(string $type, array $item): string
{
    return match ($type) {
        'eot' => format_number($item['days_requested'] ?? 0) . ' days',
        'variation' => format_money($item['amount'] ?? 0) . ' / ' . format_number($item['impact_on_time_days'] ?? 0) . ' days',
        'rfi' => contractor_submission_status_label((string)($item['urgency'] ?? 'normal')),
        'shop_drawing' => 'Rev ' . ($item['revision'] ?? 'A'),
        'material' => safe_truncate((string)($item['specification'] ?? '-'), 34),
        default => '-',
    };
}

function contractor_submission_status_label(string $status): string
{
    return match ($status) {
        'pending', 'under-review', 'open' => 'Under Review',
        'approved', 'granted', 'partially-granted', 'answered' => 'Accepted',
        'rejected', 'resubmit' => 'Returned',
        'closed' => 'Closed',
        'low', 'normal', 'urgent' => status_label($status),
        default => status_label($status),
    };
}

function contractor_submission_impact_icon(string $type): string
{
    return match ($type) {
        'eot' => 'fa-calendar-plus',
        'variation' => 'fa-coins',
        'rfi' => 'fa-reply',
        'shop_drawing' => 'fa-stamp',
        'material' => 'fa-stamp',
        default => 'fa-chart-simple',
    };
}

function contractor_submission_impact_label(string $type): string
{
    return match ($type) {
        'eot' => 'Days Impact',
        'variation' => 'Cost Impact',
        'rfi' => 'Answered',
        'shop_drawing', 'material' => 'Approved',
        default => 'Impact',
    };
}

function contractor_submission_impact_value(string $type, array $stats): string
{
    return match ($type) {
        'eot' => format_number($stats['impact'] ?? 0) . ' days',
        'variation' => format_money($stats['impact'] ?? 0),
        // Avoid duplicating the Accepted KPI: show answered-only for RFI.
        'rfi' => format_number(max(0, (float)($stats['accepted'] ?? 0) - (float)($stats['closed'] ?? 0))),
        default => format_number($stats['accepted'] ?? 0),
    };
}

function contractor_submission_impact_hint(string $type): string
{
    return match ($type) {
        'eot' => 'Days requested',
        'variation' => 'Submitted value',
        'rfi' => 'Consultant responses',
        'shop_drawing', 'material' => 'Approved records',
        default => 'Current signal',
    };
}

function contractor_submission_support_label(array $item): string
{
    $count = (int)($item['attachment_count'] ?? 0);
    if ($count <= 0) {
        return '';
    }

    return '<small class="contractor-submission-support"><i class="fa-solid fa-paperclip" aria-hidden="true"></i> ' . Security::e(format_number($count)) . ' support record' . ($count === 1 ? '' : 's') . '</small>';
}

function contractor_submission_pagination(int $page, int $pages, int $total, int $limit): void
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
