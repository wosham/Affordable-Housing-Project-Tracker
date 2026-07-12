<?php

$recordType = (string)($recordType ?? '');
$recordConfig = ContractorSiteRecord::config($recordType);
$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$requestedProjectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectId = ContractorSiteRecord::defaultProjectId($userId, $role, $requestedProjectId);
$projects = ContractorSiteRecord::projects($userId, $role);
$project = $projectId > 0 ? ContractorProject::detail($projectId, $userId, $role) : null;

$bucket = strtolower(trim((string)($_GET['bucket'] ?? '')));
if (!in_array($bucket, ['active', 'today', 'month', 'risk', 'all'], true)) {
    $bucket = '';
}
$filterStatuses = ContractorSiteRecord::filterStatuses($recordType);
$statusFilter = in_array((string)($_GET['status'] ?? ''), $filterStatuses, true) ? (string)$_GET['status'] : '';

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => $statusFilter,
    'bucket' => $bucket,
    'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['date_from'] ?? '')) ? (string)$_GET['date_from'] : '',
    'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['date_to'] ?? '')) ? (string)$_GET['date_to'] : '',
];

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1, 1));
$total = $project ? ContractorSiteRecord::count($recordType, $projectId, $userId, $role, $filters) : 0;
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$records = $project
    ? ContractorSiteRecord::list($recordType, $projectId, $userId, $role, $filters, $perPage, ($page - 1) * $perPage)
    : [];
$stats = $project
    ? ContractorSiteRecord::stats($recordType, $projectId, $userId, $role)
    : ['total' => 0, 'active' => 0, 'today' => 0, 'this_month' => 0, 'value' => 0, 'risk' => 0];
$fields = ContractorSiteRecord::fields($recordType);
$peerTypes = ContractorSiteRecord::siteTypes();
$filterLabel = (string)($recordConfig['filter_label'] ?? 'Status');
?>

<section class="csr-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid <?= Security::e($recordConfig['icon']) ?>" aria-hidden="true"></i> <?= Security::e($recordConfig['label']) ?></span>
    <h2><?= Security::e($recordConfig['title']) ?></h2>
    <p><?= Security::e(csr_intro($recordType)) ?></p>
  </div>
  <div class="csr-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/my-project.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> My Project</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
  </div>
</section>

<nav class="csr-peers card" aria-label="Site record types">
  <?php foreach ($peerTypes as $peerKey => $peerConfig): ?>
    <?php
      $peerQuery = $projectId ? ('?project_id=' . $projectId) : '';
      $isActive = $peerKey === $recordType;
    ?>
    <a class="csr-peer<?= $isActive ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($peerConfig['page'] . $peerQuery)) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid <?= Security::e($peerConfig['icon']) ?>" aria-hidden="true"></i>
      <span><?= Security::e($peerConfig['title']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if ($projects !== []): ?>
<form class="csr-selector card" method="get">
  <label for="project_id">Project</label>
  <select id="project_id" name="project_id" onchange="this.form.submit()">
    <?php foreach ($projects as $option): ?>
      <option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php endif; ?>

<?php if (!$project): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Records will appear here once a project is assigned.</span></div>
<?php else: ?>

<section class="csr-stats" aria-label="<?= Security::e($recordConfig['title']) ?> summary">
  <?php csr_stat($recordConfig['icon'], $stats['total'], 'Total', 'Visible records', csr_url($recordConfig['page'], $projectId, ['bucket' => ''])); ?>
  <?php csr_stat('fa-circle-check', csr_active_value($recordType, $stats), csr_active_label($recordType), csr_active_hint($recordType), csr_url($recordConfig['page'], $projectId, ['bucket' => 'active'])); ?>
  <?php csr_stat('fa-calendar-day', $stats['today'], 'Today', 'Current date', csr_url($recordConfig['page'], $projectId, ['bucket' => 'today'])); ?>
  <?php csr_stat('fa-calendar-days', $stats['this_month'], 'This Month', 'Recent records', csr_url($recordConfig['page'], $projectId, ['bucket' => 'month'])); ?>
  <?php csr_stat(csr_value_icon($recordType), csr_value($recordType, $stats), csr_value_label($recordType), csr_value_hint($recordType), ''); ?>
  <?php csr_stat('fa-triangle-exclamation', $stats['risk'], 'Attention', 'Needs review', csr_url($recordConfig['page'], $projectId, ['bucket' => 'risk'])); ?>
</section>

<section class="csr-grid">
  <?php if (!empty($recordConfig['can_create'])): ?>
  <main class="card csr-panel">
    <div class="card__header">
      <div><h2 class="card__title" data-csr-form-title>New <?= Security::e($recordConfig['title']) ?></h2><p class="card__subtitle"><?= Security::e(csr_form_hint($recordType)) ?></p></div>
    </div>
    <form class="csr-form" data-csr-form data-csr-create-form data-csr-type="<?= Security::e($recordType) ?>" action="<?= Security::e(Url::to($recordConfig['api'])) ?>">
      <input type="hidden" name="id" value="">
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
      <div class="csr-fields">
        <?php foreach ($fields as $field): ?>
          <?= csr_field($field) ?>
        <?php endforeach; ?>
      </div>
      <div class="csr-form__footer">
        <span data-csr-status></span>
        <div class="csr-form__actions">
          <button class="btn btn--outline" type="button" data-csr-reset>Clear</button>
          <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Record</button>
        </div>
      </div>
    </form>
  </main>
  <?php endif; ?>

  <aside class="card csr-panel">
    <h2>Project Context</h2>
    <div class="csr-context">
      <span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?></small></span>
      <span><strong><?= Security::e(status_label((string)($project['status'] ?? ''))) ?></strong><small>Project status</small></span>
      <span><strong><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</strong><small>Project progress</small></span>
      <span><strong><?= Security::e(format_date($project['est_delivery'] ?? null)) ?></strong><small>Target delivery</small></span>
    </div>
  </aside>
</section>

<section class="card csr-panel">
  <div class="card__header">
    <div><h2 class="card__title"><?= Security::e($recordConfig['title']) ?> Register</h2><p class="card__subtitle">Search, filter and review current project records.</p></div>
    <span class="badge badge--info"><?= format_number($total) ?> records</span>
  </div>

  <form class="csr-filter" method="get">
    <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
    <?php if ($bucket !== ''): ?><input type="hidden" name="bucket" value="<?= Security::e($bucket) ?>"><?php endif; ?>
    <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Search records..."></label>
    <label><span><?= Security::e($filterLabel) ?></span>
      <select name="status">
        <option value="">All</option>
        <?php foreach ($filterStatuses as $status): ?>
          <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><span>From</span><input type="date" name="date_from" value="<?= Security::e($filters['date_from']) ?>"></label>
    <label><span>To</span><input type="date" name="date_to" value="<?= Security::e($filters['date_to']) ?>"></label>
    <div class="csr-filter__actions">
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(csr_url($recordConfig['page'], $projectId, [])) ?>">Reset</a>
    </div>
  </form>

  <div class="csr-table-wrap">
    <table class="csr-table">
      <thead><tr><?php foreach (csr_columns($recordType) as $column): ?><th><?= Security::e($column) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php if ($records === []): ?>
        <tr><td colspan="<?= count(csr_columns($recordType)) ?>"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No records found</strong><span class="empty-state__text">Create a record or adjust the current filters.</span></div></td></tr>
      <?php else: foreach ($records as $record): ?>
        <tr><?= csr_row($recordType, $record) ?></tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php csr_pagination($page, $pages, $total, $perPage); ?>
</section>

<div class="csr-overlay" data-csr-detail-modal hidden aria-hidden="true">
  <div class="csr-modal" role="dialog" aria-modal="true" aria-labelledby="csrDetailTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid <?= Security::e($recordConfig['icon']) ?>" aria-hidden="true"></i> Record detail</span>
        <h2 id="csrDetailTitle" data-csr-detail-title><?= Security::e($recordConfig['title']) ?></h2>
        <p data-csr-detail-sub></p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-csr-detail-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </header>
    <div class="csr-modal__body" data-csr-detail-body>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">Select a record</strong></div>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-csr-detail-close>Close</button>
      <button class="btn btn--primary" type="button" data-csr-detail-edit hidden><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</button>
    </footer>
  </div>
</div>

<div class="csr-overlay" data-csr-edit-modal hidden aria-hidden="true">
  <div class="csr-modal csr-modal--edit" role="dialog" aria-modal="true" aria-labelledby="csrEditTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit record</span>
        <h2 id="csrEditTitle" data-csr-edit-title>Edit <?= Security::e($recordConfig['title']) ?></h2>
        <p data-csr-edit-sub>Update the selected project record and save changes.</p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-csr-edit-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </header>
    <div class="csr-modal__body">
      <form class="csr-form csr-form--modal" data-csr-edit-form data-csr-type="<?= Security::e($recordType) ?>" action="<?= Security::e(Url::to($recordConfig['api'])) ?>">
        <input type="hidden" name="id" value="" data-csr-edit-id>
        <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
        <div class="csr-fields">
          <?php foreach ($fields as $field): ?>
            <?= csr_field($field) ?>
          <?php endforeach; ?>
        </div>
        <div class="csr-form__footer csr-form__footer--modal">
          <span data-csr-status></span>
        </div>
      </form>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-csr-edit-close>Cancel</button>
      <button class="btn btn--primary" type="button" data-csr-edit-save><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save changes</button>
    </footer>
  </div>
</div>

<?php endif; ?>

<?php
function csr_intro(string $type): string
{
    return match ($type) {
        'documents' => 'Keep project documents, references and supporting records available to the site team.',
        'equipment' => 'Track plant and equipment currently on site, off site or requiring attention.',
        'incidents' => 'Record safety incidents, corrective actions and follow-up dates clearly.',
        'labour' => 'Submit daily workforce numbers and review labour movement by project date.',
        'materials' => 'Record material deliveries, suppliers, quantities and delivery note details.',
        'subcontractors' => 'Maintain subcontractor contacts, scopes, compliance and risk status.',
        default => 'Track contractor site records.',
    };
}

function csr_form_hint(string $type): string
{
    return match ($type) {
        'documents' => 'Add a document title and choose a supporting file from the media library.',
        'labour' => 'Daily totals are calculated from skilled, unskilled and supervisor counts.',
        'incidents' => 'Capture what happened, severity and any evidence photo or report.',
        'materials' => 'Log delivery quantities. Clerk verification happens after submit.',
        default => 'Complete the required details and save the project record.',
    };
}

function csr_url(string $page, int $projectId, array $extra = []): string
{
    $query = array_filter(array_merge([
        'project_id' => $projectId > 0 ? $projectId : '',
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => trim((string)($_GET['status'] ?? '')),
        'date_from' => trim((string)($_GET['date_from'] ?? '')),
        'date_to' => trim((string)($_GET['date_to'] ?? '')),
    ], $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    if (array_key_exists('bucket', $extra) && ($extra['bucket'] === '' || $extra['bucket'] === null)) {
        unset($query['bucket']);
    }
    return Url::to($page . ($query ? '?' . http_build_query($query) : ''));
}

function csr_field(array $field): string
{
    $name = Security::e($field['name']);
    $label = Security::e($field['label'] . (!empty($field['required']) ? ' *' : ''));
    $required = !empty($field['required']) ? ' required' : '';
    $placeholder = Security::e($field['placeholder'] ?? '');
    $type = (string)($field['type'] ?? 'text');
    $wide = in_array($type, ['textarea', 'media'], true) ? ' is-wide' : '';
    $html = '<label class="csr-field' . $wide . '"><span>' . $label . '</span>';
    if ($type === 'textarea') {
        return $html . '<textarea name="' . $name . '" rows="4" placeholder="' . $placeholder . '"' . $required . '></textarea></label>';
    }
    if ($type === 'select') {
        $options = '';
        foreach ((array)($field['options'] ?? []) as $value => $text) {
            if (is_int($value)) {
                $value = $text;
            }
            $options .= '<option value="' . Security::e((string)$value) . '">' . Security::e(status_label((string)$text)) . '</option>';
        }
        return $html . '<select name="' . $name . '"' . $required . '>' . $options . '</select></label>';
    }
    if ($type === 'media') {
        $help = Security::e((string)($field['help'] ?? 'Optional. Choose a library file or upload supporting evidence.'));
        $reqAttr = !empty($field['required']) ? ' data-media-required="1"' : '';
        return $html
            . '<div class="csr-media-card" data-cms-upload data-upload-folder="contractor-documents" data-media-kind="document" data-csr-media' . $reqAttr . '>'
            . '<div class="csr-media-card__preview" data-cms-asset-preview><span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span></div>'
            . '<div class="csr-media-card__body">'
            . '<strong data-cms-asset-name>No file selected</strong>'
            . '<small>' . $help . '</small>'
            . '<input type="hidden" name="media_id" value="" data-media-id-target>'
            . '<input type="hidden" name="' . $name . '" value="" data-cms-upload-target data-media-picker-value="path"' . $required . '>'
            . '<div class="csr-media-card__actions">'
            . '<button class="btn btn--outline btn--sm" type="button" data-media-picker-open data-media-picker-folder="contractor-documents" data-media-picker-type="document" data-media-picker-accept="image/*,application/pdf,.pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp" data-media-picker-title="Choose supporting file"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose file</button>'
            . '<button class="btn btn--outline btn--sm" type="button" data-media-picker-open data-media-picker-folder="site-photos" data-media-picker-type="image" data-media-picker-accept="image/*" data-media-picker-title="Choose site photo"><i class="fa-solid fa-camera" aria-hidden="true"></i> Site photo</button>'
            . '<button class="btn btn--outline btn--sm" type="button" data-csr-media-clear><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</button>'
            . '</div></div></div></label>';
    }
    $step = isset($field['step']) ? ' step="' . Security::e((string)$field['step']) . '"' : '';
    $min = isset($field['min']) ? ' min="' . Security::e((string)$field['min']) . '"' : '';
    return $html . '<input type="' . Security::e($type) . '" name="' . $name . '" placeholder="' . $placeholder . '"' . $required . $step . $min . '></label>';
}

function csr_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="csr-stat card"' . $href . '><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><b>' . Security::e($label) . '</b><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function csr_columns(string $type): array
{
    $columns = match ($type) {
        'documents' => ['Document', 'Category', 'Review', 'Version', 'Date', 'Reference'],
        'equipment' => ['Equipment', 'Owner', 'Dates', 'Condition', 'Status'],
        'incidents' => ['Incident', 'Severity', 'Action', 'Status', 'Reported'],
        'labour' => ['Date', 'Skilled', 'Unskilled', 'Supervisors', 'Total', 'Status'],
        'materials' => ['Material', 'Supplier', 'Quantity', 'Condition', 'Status', 'Date'],
        'subcontractors' => ['Company', 'Scope', 'Contact', 'Value', 'Compliance', 'Status'],
        default => ['Record', 'Status', 'Date'],
    };
    $columns[] = 'Actions';
    return $columns;
}

function csr_row(string $type, array $record): string
{
    $id = (int)($record['id'] ?? 0);
    $cells = match ($type) {
        'documents' => '<td><strong>' . Security::e($record['original_name'] ?? '-') . '</strong><small>' . Security::e(safe_truncate((string)($record['description'] ?? ''), 80)) . '</small></td><td><span class="badge badge--info">' . Security::e(status_label((string)($record['category'] ?? 'other'))) . '</span></td><td><span class="badge ' . Security::e(status_badge_class((string)($record['consultant_review_status'] ?? 'pending'))) . '">' . Security::e(status_label((string)($record['consultant_review_status'] ?? 'pending'))) . '</span></td><td>' . Security::e((string)($record['version'] ?? '1.0')) . '</td><td>' . Security::e(format_date($record['created_at'] ?? null)) . '</td><td><strong title="' . Security::e((string)($record['filename'] ?? '')) . '">' . Security::e(safe_truncate(basename((string)($record['filename'] ?? '-')), 40)) . '</strong></td>',
        'equipment' => '<td><strong>' . Security::e($record['equipment_type'] ?? '-') . '</strong><small>' . Security::e($record['registration'] ?? '') . '</small></td><td>' . Security::e($record['owner'] ?? '-') . '</td><td>' . Security::e(format_date($record['date_on_site'] ?? null)) . '<small>Off: ' . Security::e(format_date($record['date_off_site'] ?? null)) . '</small></td><td>' . Security::e(status_label((string)($record['condition'] ?? 'good'))) . '</td><td><span class="badge ' . Security::e(status_badge_class((string)($record['status'] ?? 'on-site'))) . '">' . Security::e(status_label((string)($record['status'] ?? 'on-site'))) . '</span></td>',
        'incidents' => '<td><strong>' . Security::e(format_date($record['incident_date'] ?? null)) . ' / ' . Security::e(status_label((string)($record['incident_type'] ?? ''))) . '</strong><small>' . Security::e(safe_truncate((string)($record['description'] ?? ''), 85)) . '</small></td><td><span class="badge ' . Security::e(status_badge_class((string)($record['severity'] ?? 'medium'))) . '">' . Security::e(status_label((string)($record['severity'] ?? 'medium'))) . '</span></td><td>' . Security::e(safe_truncate((string)($record['corrective_action'] ?? '-'), 80)) . '</td><td><span class="badge ' . Security::e(status_badge_class((string)($record['status'] ?? 'open'))) . '">' . Security::e(status_label((string)($record['status'] ?? 'open'))) . '</span><small>Follow-up: ' . Security::e(format_date($record['follow_up_date'] ?? null)) . '</small></td><td>' . Security::e(trim((string)($record['reported_by_name'] ?? ''))) . '</td>',
        'labour' => '<td><strong>' . Security::e(format_date($record['diary_date'] ?? null)) . '</strong></td><td>' . (int)($record['skilled_count'] ?? 0) . '</td><td>' . (int)($record['unskilled_count'] ?? 0) . '</td><td>' . (int)($record['supervisor_count'] ?? 0) . '</td><td><strong>' . (int)($record['total'] ?? 0) . '</strong></td><td><span class="badge badge--info">' . Security::e(status_label((string)($record['status'] ?? 'submitted'))) . '</span></td>',
        'materials' => '<td><strong>' . Security::e($record['material'] ?? '-') . '</strong><small>' . Security::e($record['delivery_note_no'] ?? '') . '</small></td><td>' . Security::e($record['supplier'] ?? '-') . '</td><td><strong>' . Security::e(number_format((float)($record['quantity'] ?? 0), 3)) . '</strong><small>' . Security::e($record['unit'] ?? '') . '</small></td><td>' . Security::e(status_label((string)($record['condition'] ?? 'good'))) . '</td><td><span class="badge ' . Security::e(status_badge_class((string)($record['status'] ?? 'submitted'))) . '">' . Security::e(status_label((string)($record['status'] ?? 'submitted'))) . '</span></td><td>' . Security::e(format_date($record['delivery_date'] ?? null)) . '</td>',
        'subcontractors' => '<td><strong>' . Security::e($record['company'] ?? '-') . '</strong><small>' . Security::e($record['contact_person'] ?? '') . '</small></td><td>' . Security::e(safe_truncate((string)($record['scope_of_work'] ?? '-'), 80)) . '</td><td>' . Security::e($record['phone'] ?? '-') . '<small>' . Security::e($record['email'] ?? '') . '</small></td><td><strong>' . Security::e(format_money($record['contract_value'] ?? 0)) . '</strong></td><td><span class="badge badge--info">' . Security::e(status_label((string)($record['compliance_status'] ?? 'pending'))) . '</span><small>' . Security::e(status_label((string)($record['risk_status'] ?? 'normal'))) . ' risk</small></td><td><span class="badge ' . Security::e(status_badge_class((string)($record['status'] ?? 'active'))) . '">' . Security::e(status_label((string)($record['status'] ?? 'active'))) . '</span></td>',
        default => '<td>Record</td>',
    };

    return $cells . '<td><div class="csr-actions">'
        . '<button class="btn btn--sm btn--outline" type="button" title="View details" aria-label="View details" data-csr-open="' . $id . '" data-csr-type="' . Security::e($type) . '"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>'
        . '<button class="btn btn--sm btn--outline" type="button" title="Edit record" aria-label="Edit record" data-csr-edit="' . $id . '" data-csr-type="' . Security::e($type) . '"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>'
        . '</div></td>';
}

function csr_active_value(string $type, array $stats): string
{
    return format_number($stats['active'] ?? 0);
}

function csr_active_label(string $type): string
{
    return match ($type) {
        'documents' => 'In Review',
        'equipment' => 'On Site',
        'incidents' => 'Open',
        'labour' => 'Submitted',
        'materials' => 'Pending',
        'subcontractors' => 'Active',
        default => 'Active',
    };
}

function csr_active_hint(string $type): string
{
    return match ($type) {
        'documents' => 'Awaiting consultant',
        'equipment' => 'Currently mobilised',
        'incidents' => 'Needs follow-up',
        'labour' => 'Daily entries',
        'materials' => 'Awaiting check',
        'subcontractors' => 'Engaged partners',
        default => 'Current records',
    };
}

function csr_value_icon(string $type): string
{
    return match ($type) {
        'subcontractors' => 'fa-coins',
        'labour' => 'fa-users',
        'materials' => 'fa-boxes-stacked',
        'documents' => 'fa-hourglass-half',
        'incidents' => 'fa-bolt',
        'equipment' => 'fa-truck',
        default => 'fa-chart-simple',
    };
}

function csr_value(string $type, array $stats): string
{
    return match ($type) {
        'subcontractors' => format_money($stats['value'] ?? 0),
        'materials' => format_number($stats['value'] ?? 0),
        default => format_number($stats['value'] ?? 0),
    };
}

function csr_value_label(string $type): string
{
    return match ($type) {
        'subcontractors' => 'Value',
        'labour' => 'Labour Total',
        'materials' => 'Quantity',
        'documents' => 'Pending Review',
        'incidents' => 'High Severity',
        'equipment' => 'On Site Units',
        default => 'Signal',
    };
}

function csr_value_hint(string $type): string
{
    return match ($type) {
        'subcontractors' => 'Recorded contracts',
        'labour' => 'Headcount total',
        'materials' => 'Visible quantity',
        'documents' => 'Consultant queue',
        'incidents' => 'High / critical',
        'equipment' => 'Mobilised plant',
        default => 'Current view',
    };
}

function csr_pagination(int $page, int $pages, int $total, int $limit): void
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
