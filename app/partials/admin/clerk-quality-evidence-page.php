<?php
$qualityType = $qualityType ?? 'quality';
$qualityConfig = ClerkQualityEvidence::config($qualityType);
$userId = (int)Auth::id();
$date = Security::cleanString((string)($_GET['date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$projects = ClerkQualityEvidence::projects($userId);
$projectId = ClerkQualityEvidence::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$project = $projectId ? ClerkQualityEvidence::project($userId, $projectId) : null;
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
];
$stats = ClerkQualityEvidence::stats($qualityType, $userId, $projectId);
$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1, 1));
$total = ClerkQualityEvidence::count($qualityType, $userId, $projectId, $filters);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$records = ClerkQualityEvidence::list($qualityType, $userId, $projectId, $filters, $perPage, ($page - 1) * $perPage);
$pendingIpcs = $qualityType === 'ipc' && $projectId > 0
    ? ClerkQualityEvidence::pendingIpcs($userId, $projectId)
    : [];
$allTypes = ClerkQualityEvidence::types();
$peerTypes = array_intersect_key($allTypes, array_flip(ClerkQualityEvidence::peerKeys()));

$pageTitle = $qualityConfig['title'];
$pageDescription = $qualityConfig['label'] . ' records for assigned clerk sites.';
$adminRole = 'clerk';
$contentClass = 'clerk-quality-page';
$csrfForm = 'clerk_quality_evidence';
$componentCss = array_values(array_unique(array_merge((array)($componentCss ?? []), ['clerk-quality', 'media-library'])));
$pageScripts = array_values(array_unique(array_merge((array)($pageScripts ?? []), ['media-picker', 'clerk-quality'])));
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Clerk of Works', 'url' => Url::to('admin/clerk/dashboard.php')],
    ['label' => $qualityConfig['title']],
];
$detailUrl = Url::to('api/clerk/quality-evidence-detail.php');

include __DIR__ . '/shell-start.php';
?>

<section class="clerk-quality-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid <?= Security::e($qualityConfig['icon']) ?>" aria-hidden="true"></i> Quality &amp; evidence</span>
    <h2><?= Security::e($qualityConfig['title']) ?></h2>
    <p><?= Security::e(clerk_quality_intro($qualityType)) ?></p>
  </div>
  <div class="clerk-quality-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/clerk/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <?php if ($qualityType !== 'ipc'): ?>
      <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/clerk/ipc-verify.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> IPC Verify</a>
    <?php else: ?>
      <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/clerk/photos.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-camera"></i> Photos</a>
    <?php endif; ?>
  </div>
</section>

<?php
  $siteHub = method_exists('ClerkDailyRecord', 'siteHubPeers') ? ClerkDailyRecord::siteHubPeers() : [];
  $hubActiveMap = [
    'document' => 'document',
    'hs' => 'hs',
    'meeting' => 'meeting',
  ];
?>
<?php if ($siteHub !== []): ?>
<nav class="clerk-quality-hub card" aria-label="Site records hub">
  <?php foreach ($siteHub as $hubKey => $hub): ?>
    <?php
      $hubQuery = $projectId ? ('?project_id=' . $projectId) : '';
      $isHubActive = isset($hubActiveMap[$qualityType]) && $hubActiveMap[$qualityType] === $hubKey;
    ?>
    <a class="clerk-quality-hub__link<?= $isHubActive ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($hub['page'] . $hubQuery)) ?>"<?= $isHubActive ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid <?= Security::e($hub['icon']) ?>" aria-hidden="true"></i>
      <span><?= Security::e($hub['title']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<nav class="clerk-quality-peers card" aria-label="Quality and evidence pages">
  <?php foreach ($peerTypes as $peerKey => $peerConfig): ?>
    <?php
      $peerQuery = $projectId ? ('?project_id=' . $projectId) : '';
      $isActive = $peerKey === $qualityType;
    ?>
    <a class="clerk-quality-peer<?= $isActive ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($peerConfig['page'] . $peerQuery)) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid <?= Security::e($peerConfig['icon']) ?>" aria-hidden="true"></i>
      <span><?= Security::e($peerConfig['title']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if ($projects === []): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Quality and evidence records appear once a site is assigned to you.</span></div>
<?php else: ?>

<section class="clerk-quality-stats">
  <?php clerk_quality_stat('fa-folder-open', $stats['total'], 'Records', 'Current project'); ?>
  <?php clerk_quality_stat('fa-calendar-day', $stats['today'], 'Today', 'Logged today'); ?>
  <?php clerk_quality_stat('fa-hourglass-half', $stats['open'], 'Pending', 'Needs follow-up'); ?>
  <?php clerk_quality_stat('fa-triangle-exclamation', $stats['risk'], 'Flags', 'Needs attention'); ?>
  <?php clerk_quality_stat('fa-chart-simple', clerk_quality_value($qualityType, $stats['value']), clerk_quality_value_label($qualityType), 'Measured signal'); ?>
</section>

<form class="card clerk-quality-filter" method="get">
  <label><span>Project</span><select name="project_id"><?php foreach ($projects as $option): ?><option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option><?php endforeach; ?></select></label>
  <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Search records..."></label>
  <label><span>Status</span><?= clerk_quality_status_select($qualityType, $filters['status']) ?></label>
  <label><span>From</span><input type="date" name="date_from" value="<?= Security::e($filters['date_from']) ?>"></label>
  <label><span>To</span><input type="date" name="date_to" value="<?= Security::e($filters['date_to']) ?>"></label>
  <div class="clerk-quality-filter__actions">
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to($qualityConfig['page'])) ?>">Reset</a>
  </div>
</form>

<section class="clerk-quality-layout">
  <main class="card clerk-quality-panel">
    <div class="card__header">
      <div>
        <h2 class="card__title" data-form-title><?= Security::e(clerk_quality_form_title($qualityType)) ?></h2>
        <p class="card__subtitle"><?= Security::e($project['name'] ?? 'Assigned project') ?></p>
      </div>
      <span class="badge badge--info"><?= Security::e(format_date($date)) ?></span>
    </div>
    <form class="clerk-quality-form"
          data-clerk-quality-form
          data-quality-type="<?= Security::e($qualityType) ?>"
          data-detail-url="<?= Security::e($detailUrl) ?>"
          data-create-title="<?= Security::e(clerk_quality_form_title($qualityType)) ?>"
          action="<?= Security::e(Url::to($qualityConfig['api'])) ?>"
          method="post">
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
      <?= clerk_quality_form($qualityType, $date, $pendingIpcs) ?>
      <div class="clerk-quality-form__actions">
        <button class="btn btn--outline" type="button" data-form-reset>Reset</button>
        <?php if ($qualityType === 'ipc'): ?>
          <button class="btn btn--outline" type="submit" name="ipc_action" value="return" data-ipc-return><i class="fa-solid fa-rotate-left"></i> Return to contractor</button>
          <button class="btn btn--primary" type="submit" name="ipc_action" value="endorse" data-ipc-endorse><i class="fa-solid fa-clipboard-check"></i> Endorse IPC</button>
        <?php else: ?>
          <button class="btn btn--primary" type="submit" data-form-submit><i class="fa-solid fa-floppy-disk"></i> Save Record</button>
        <?php endif; ?>
      </div>
      <p class="clerk-quality-form-status" data-form-status hidden></p>
    </form>
  </main>

  <aside class="card clerk-quality-panel">
    <h2>Site Context</h2>
    <div class="clerk-quality-side-list">
      <span><strong><?= Security::e($project['name'] ?? '-') ?></strong><small>Assigned site</small></span>
      <span><strong><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /') ?: 'Project location') ?></strong><small>Location</small></span>
      <span><strong><?= Security::e(status_label((string)($project['status'] ?? '-'))) ?></strong><small>Project status</small></span>
      <span><strong><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</strong><small>Progress</small></span>
      <?php if ($qualityType === 'ipc'): ?>
        <span><strong><?= format_number(count($pendingIpcs)) ?></strong><small>Waiting for clerk verify</small></span>
      <?php endif; ?>
    </div>
  </aside>
</section>

<section class="card clerk-quality-panel">
  <div class="card__header">
    <div>
      <h2 class="card__title"><?= Security::e($qualityConfig['title']) ?> Register</h2>
      <p class="card__subtitle">Records for the selected project<?= $qualityType === 'ipc' ? ' (history — only submitted IPCs appear in the verify form)' : '' ?>.</p>
    </div>
    <span class="badge badge--info"><?= format_number($total) ?> records</span>
  </div>
  <div class="clerk-quality-table-wrap">
    <table class="clerk-quality-table">
      <?= clerk_quality_table($qualityType, $records, $qualityConfig['empty']) ?>
    </table>
  </div>
  <?php if ($total > 0): ?>
    <?php
      $from = min($total, (($page - 1) * $perPage) + 1);
      $to = min($total, $page * $perPage);
      $query = $_GET;
    ?>
    <div class="pagination">
      <span>Showing <?= format_number($from) ?>-<?= format_number($to) ?> of <?= format_number($total) ?></span>
      <div>
        <?php $query['page'] = max(1, $page - 1); ?>
        <a class="btn btn--sm btn--outline<?= $page <= 1 ? ' is-disabled' : '' ?>" href="?<?= Security::e(http_build_query($query)) ?>"><i class="fa-solid fa-chevron-left"></i></a>
        <span class="btn btn--sm btn--primary"><?= $page ?> / <?= $pages ?></span>
        <?php $query['page'] = min($pages, $page + 1); ?>
        <a class="btn btn--sm btn--outline<?= $page >= $pages ? ' is-disabled' : '' ?>" href="?<?= Security::e(http_build_query($query)) ?>"><i class="fa-solid fa-chevron-right"></i></a>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php if ($qualityType !== 'ipc'): ?>
<div class="cq-overlay" data-cq-view-modal hidden aria-hidden="true">
  <div class="cq-modal" role="dialog" aria-modal="true" aria-labelledby="cqViewTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-eye" aria-hidden="true"></i> Record detail</span>
        <h2 id="cqViewTitle" data-cq-view-title><?= Security::e($qualityConfig['title']) ?></h2>
        <p data-cq-view-sub></p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-cq-view-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </header>
    <div class="cq-modal__body" data-cq-view-body>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">Select a record</strong></div>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-cq-view-close>Close</button>
      <button class="btn btn--primary" type="button" data-cq-view-edit><i class="fa-solid fa-pen"></i> Edit</button>
    </footer>
  </div>
</div>

<div class="cq-overlay" data-cq-edit-modal hidden aria-hidden="true">
  <div class="cq-modal cq-modal--edit" role="dialog" aria-modal="true" aria-labelledby="cqEditTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit record</span>
        <h2 id="cqEditTitle">Edit <?= Security::e($qualityConfig['label']) ?></h2>
        <p data-cq-edit-sub>Update the selected record and save changes.</p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-cq-edit-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </header>
    <div class="cq-modal__body">
      <form class="clerk-quality-form clerk-quality-form--modal"
            data-cq-edit-form
            data-quality-type="<?= Security::e($qualityType) ?>"
            action="<?= Security::e(Url::to($qualityConfig['api'])) ?>"
            method="post">
        <input type="hidden" name="id" value="" data-cq-edit-id>
        <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
        <?= clerk_quality_form($qualityType, $date, []) ?>
        <p class="clerk-quality-form-status" data-form-status hidden></p>
      </form>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-cq-edit-close>Cancel</button>
      <button class="btn btn--primary" type="button" data-cq-edit-save><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
    </footer>
  </div>
</div>

<div class="cq-lightbox" data-cq-lightbox hidden aria-hidden="true">
  <button type="button" class="cq-lightbox__close" data-cq-lightbox-close aria-label="Close photo"><i class="fa-solid fa-xmark"></i></button>
  <img src="" alt="Evidence photo" data-cq-lightbox-img>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
include __DIR__ . '/shell-end.php';

function clerk_quality_intro(string $type): string
{
    return match ($type) {
        'quality' => 'Capture test results, site observations and supporting evidence for assigned projects.',
        'itp' => 'Record inspections, hold points, witness requirements and site outcomes.',
        'hs' => 'Log incidents, immediate actions, follow-up dates and safety status.',
        'ncr' => 'Raise and track non-conformance items with corrective action details.',
        'defect' => 'Record defects, locations, severity, due dates and rectification notes.',
        'meeting' => 'Capture minutes, attendees, action items and next meeting dates.',
        'document' => 'Register site documents with library files or uploads.',
        'photo' => 'Register photo evidence from assigned projects with captions.',
        'ipc' => 'Verify submitted payment claims against site records — endorse or return only.',
        default => 'Capture site evidence for the selected project.',
    };
}

function clerk_quality_stat(string $icon, mixed $value, string $label, string $hint): void
{
    echo '<article class="clerk-quality-stat card"><span><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></article>';
}

function clerk_quality_value(string $type, mixed $value): string
{
    if (in_array($type, ['document', 'photo'], true)) {
        $kb = ((float)$value) / 1024;
        return $kb >= 1024 ? number_format($kb / 1024, 1) . ' MB' : number_format($kb, 1) . ' KB';
    }
    return format_number((float)$value);
}

function clerk_quality_value_label(string $type): string
{
    return match ($type) {
        'document', 'photo' => 'File Size',
        'ipc' => 'Clerk verified',
        'quality', 'itp' => 'Passed',
        'ncr', 'defect' => 'Closed',
        'hs' => 'Lost-time cases',
        default => 'Closed/Passed',
    };
}

function clerk_quality_status_select(string $type, string $current): string
{
    $options = match ($type) {
        'quality' => ['pending' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed', 'queried' => 'Queried'],
        'itp' => ['pending' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed', 'rework-required' => 'Rework required'],
        'hs' => ['open' => 'Open', 'investigating' => 'Investigating', 'action-pending' => 'Action pending', 'resolved' => 'Resolved', 'closed' => 'Closed'],
        'ncr' => ['open' => 'Open', 'in-progress' => 'In progress', 'closed' => 'Closed'],
        'defect' => ['open' => 'Open', 'in-progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'],
        'meeting' => ['draft' => 'Draft', 'recorded' => 'Recorded', 'reviewed' => 'Reviewed', 'closed' => 'Closed'],
        'document', 'photo' => ['pending' => 'Pending review', 'accepted' => 'Accepted', 'flagged' => 'Flagged', 'returned' => 'Returned'],
        'ipc' => ['submitted' => 'Waiting check', 'clerk-endorsed' => 'Checked', 'rejected' => 'Returned', 'certified' => 'Certified', 'approved' => 'Approved'],
        default => [],
    };
    $html = '<select name="status"><option value="">All statuses</option>';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . Security::e($value) . '"' . ($current === $value ? ' selected' : '') . '>' . Security::e($label) . '</option>';
    }
    return $html . '</select>';
}

function clerk_quality_form_title(string $type): string
{
    return match ($type) {
        'quality' => 'Record Quality Test',
        'itp' => 'Record Inspection',
        'hs' => 'Report Incident',
        'ncr' => 'Raise Non-Conformance',
        'defect' => 'Record Defect',
        'meeting' => 'Record Meeting Minutes',
        'document' => 'Register Document',
        'photo' => 'Register Photo Evidence',
        'ipc' => 'Verify IPC',
        default => 'Record Details',
    };
}

function clerk_quality_form(string $type, string $date, array $pendingIpcs = []): string
{
    return match ($type) {
        'quality' => clerk_quality_quality_form($date),
        'itp' => clerk_quality_itp_form($date),
        'hs' => clerk_quality_hs_form($date),
        'ncr' => clerk_quality_ncr_form($date),
        'defect' => clerk_quality_defect_form($date),
        'meeting' => clerk_quality_meeting_form($date),
        'document' => clerk_quality_document_form($date, false),
        'photo' => clerk_quality_document_form($date, true),
        'ipc' => clerk_quality_ipc_form($pendingIpcs),
        default => '',
    };
}

function cq_input(string $label, string $name, mixed $value = '', string $type = 'text', string $attrs = ''): string
{
    return '<label><span>' . Security::e($label) . '</span><input type="' . Security::e($type) . '" name="' . Security::e($name) . '" value="' . Security::e((string)$value) . '" ' . $attrs . '></label>';
}

function cq_textarea(string $label, string $name, mixed $value = '', string $attrs = ''): string
{
    return '<label class="is-wide"><span>' . Security::e($label) . '</span><textarea name="' . Security::e($name) . '" rows="4" ' . $attrs . '>' . Security::e((string)$value) . '</textarea></label>';
}

function cq_select(string $label, string $name, array $options, string $current = ''): string
{
    $html = '<label><span>' . Security::e($label) . '</span><select name="' . Security::e($name) . '">';
    foreach ($options as $value => $text) {
        $html .= '<option value="' . Security::e((string)$value) . '"' . ($current === (string)$value ? ' selected' : '') . '>' . Security::e($text) . '</option>';
    }
    return $html . '</select></label>';
}

function cq_media(string $label, string $pathName, string $mediaName, bool $image = false, bool $required = false): string
{
    $folder = $image ? 'site-photos' : 'clerk-documents';
    $kind = $image ? 'image' : 'document';
    $accept = $image ? 'image/*' : 'image/*,application/pdf,.pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp';
    $title = $image ? 'Choose or upload site photo' : 'Choose or upload supporting file';
    $req = $required ? ' data-media-required="1"' : '';
    $reqInput = $required ? ' required' : '';
    return '<label class="is-wide"><span>' . Security::e($label) . '</span>'
        . '<div class="cq-media-card" data-cms-upload data-upload-folder="' . Security::e($folder) . '" data-media-kind="' . Security::e($kind) . '" data-cq-media' . $req . '>'
        . '<div class="cq-media-card__preview" data-cms-asset-preview><span><i class="fa-solid ' . ($image ? 'fa-camera' : 'fa-file-lines') . '" aria-hidden="true"></i></span></div>'
        . '<div class="cq-media-card__body">'
        . '<strong data-cms-asset-name>No file selected</strong>'
        . '<small>Choose from the media library or upload a new file.</small>'
        . '<input type="hidden" name="' . Security::e($mediaName) . '" value="" data-media-id-target>'
        . '<input type="hidden" name="' . Security::e($pathName) . '" value="" data-cms-upload-target data-media-picker-value="path"' . $reqInput . '>'
        . '<div class="cq-media-card__actions">'
        . '<button class="btn btn--outline btn--sm" type="button" data-media-picker-open data-media-picker-folder="' . Security::e($folder) . '" data-media-picker-type="' . Security::e($kind) . '" data-media-picker-accept="' . Security::e($accept) . '" data-media-picker-title="' . Security::e($title) . '"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose / upload</button>'
        . '<button class="btn btn--outline btn--sm" type="button" data-cq-media-clear><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</button>'
        . '</div></div></div></label>';
}

function clerk_quality_quality_form(string $date): string
{
    return cq_input('Test date', 'test_date', $date, 'date', 'required')
        . cq_input('Test type', 'test_type', '', 'text', 'required maxlength="100" placeholder="Concrete cube, soil compaction..."')
        . cq_input('Location on site', 'location_on_site', '', 'text', 'maxlength="200"')
        . cq_input('Lab / site reference', 'lab_ref', '', 'text', 'maxlength="80"')
        . cq_input('Required result', 'required_result', '', 'text', 'maxlength="180"')
        . cq_input('Actual result', 'actual_result', '', 'text', 'maxlength="180"')
        . cq_select('Verification', 'verification_status', ['pending' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed', 'queried' => 'Queried'], 'pending')
        . cq_media('Evidence file', 'document_path', 'evidence_media_id', false, false)
        . cq_textarea('Observation', 'clerk_observation', '', 'placeholder="Summarise the test result and any issue observed."');
}

function clerk_quality_itp_form(string $date): string
{
    return cq_input('Inspection date', 'inspection_date', $date, 'date')
        . cq_input('Activity', 'activity', '', 'text', 'required maxlength="200"')
        . cq_input('Inspection area', 'inspection_area', '', 'text', 'maxlength="180"')
        . cq_input('Hold point', 'hold_point', '', 'text', 'maxlength="100"')
        . cq_input('Outcome', 'outcome', '', 'text', 'maxlength="100"')
        . cq_select('Inspection status', 'inspection_status', ['pending' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed', 'rework-required' => 'Rework required'], 'pending')
        . '<label class="clerk-quality-check"><input type="checkbox" name="witness_required" value="1"><span>Witness required</span></label>'
        . cq_media('Evidence file', 'document_path', 'evidence_media_id', false, false)
        . cq_textarea('Inspection notes', 'clerk_notes', '', 'placeholder="Record what was checked and any required action."');
}

function clerk_quality_hs_form(string $date): string
{
    return cq_input('Incident date', 'incident_date', $date, 'date', 'required')
        . cq_select('Incident type', 'incident_type', ['near-miss' => 'Near miss', 'first-aid' => 'First aid', 'medical' => 'Medical', 'fatality' => 'Fatality'], 'near-miss')
        . cq_select('Severity', 'severity', ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'], 'medium')
        . cq_select('Status', 'status', ['open' => 'Open', 'investigating' => 'Investigating', 'action-pending' => 'Action pending', 'resolved' => 'Resolved', 'closed' => 'Closed'], 'open')
        . cq_input('Follow-up date', 'follow_up_date', '', 'date')
        . cq_input('Lost time hours', 'lost_time_hours', '0', 'number', 'min="0" step="0.25"')
        . cq_textarea('Description', 'description', '', 'required placeholder="Describe what happened."')
        . cq_textarea('Persons involved', 'persons_involved')
        . cq_textarea('Cause', 'cause', '', 'placeholder="Root or immediate cause if known."')
        . cq_textarea('Immediate action', 'immediate_action')
        . cq_textarea('Corrective action', 'corrective_action')
        . cq_media('Evidence attachment', 'attachment_path', 'evidence_media_id', false, false);
}

function clerk_quality_ncr_form(string $date): string
{
    return cq_input('Raised date', 'raised_date', $date, 'date', 'required')
        . cq_input('Reference', 'ncr_reference', '', 'text', 'maxlength="80" placeholder="Leave blank to auto-generate"')
        . cq_input('Location', 'location_on_site', '', 'text', 'maxlength="180"')
        . cq_select('Severity', 'severity', ['minor' => 'Minor', 'major' => 'Major', 'critical' => 'Critical'], 'minor')
        . cq_select('Status', 'status', ['open' => 'Open', 'in-progress' => 'In progress', 'closed' => 'Closed'], 'open')
        . cq_input('Target close date', 'target_close_date', '', 'date')
        . cq_textarea('Description', 'description', '', 'required placeholder="Describe the non-conformance."')
        . cq_textarea('Root cause', 'root_cause')
        . cq_textarea('Corrective action', 'corrective_action')
        . cq_media('Evidence file', 'document_path', 'evidence_media_id', false, false);
}

function clerk_quality_defect_form(string $date): string
{
    return cq_input('Raised date', 'raised_date', $date, 'date', 'required')
        . cq_input('Reference', 'defect_reference', '', 'text', 'maxlength="80" placeholder="Leave blank to auto-generate"')
        . cq_input('Location', 'location', '', 'text', 'maxlength="200"')
        . cq_select('Severity', 'severity', ['minor' => 'Minor', 'major' => 'Major', 'critical' => 'Critical'], 'minor')
        . cq_select('Status', 'status', ['open' => 'Open', 'in-progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'], 'open')
        . cq_input('Due date', 'due_date', '', 'date')
        . cq_media('Photo evidence', 'photo_path', 'evidence_media_id', true, false)
        . cq_textarea('Description', 'description', '', 'required placeholder="Describe the defect."')
        . cq_textarea('Rectification notes', 'rectification_notes');
}

function clerk_quality_meeting_form(string $date): string
{
    return cq_input('Meeting date', 'meeting_date', $date, 'date', 'required')
        . cq_select('Meeting type', 'meeting_type', ['site' => 'Site', 'progress' => 'Progress', 'safety' => 'Safety', 'quality' => 'Quality', 'community' => 'Community'], 'site')
        . cq_input('Venue', 'venue', '', 'text', 'maxlength="200"')
        . cq_input('Chairperson', 'chairperson', '', 'text', 'maxlength="150"')
        . cq_select('Status', 'status', ['draft' => 'Draft', 'recorded' => 'Recorded', 'reviewed' => 'Reviewed', 'closed' => 'Closed'], 'recorded')
        . cq_select('Action status', 'action_status', ['none' => 'No actions', 'open' => 'Open', 'in-progress' => 'In progress', 'completed' => 'Completed', 'overdue' => 'Overdue'], 'open')
        . cq_input('Next meeting', 'next_meeting_date', '', 'date')
        . cq_textarea('Attendees', 'attendees', '', 'placeholder="One attendee per line."')
        . cq_textarea('Agenda', 'agenda')
        . cq_textarea('Minutes', 'minutes_text', '', 'required placeholder="Record agreed minutes clearly."')
        . cq_textarea('Action items', 'action_items', '', 'placeholder="One action item per line."')
        . cq_media('Minutes attachment', 'document_path', 'media_id', false, false);
}

function clerk_quality_document_form(string $date, bool $photo): string
{
    if ($photo) {
        return cq_input('Photo date', 'site_record_date', $date, 'date', 'required')
            . cq_input('Photo title', 'original_name', '', 'text', 'required maxlength="255"')
            . cq_media('Photo file', 'filename', 'media_id', true, true)
            . cq_textarea('Caption', 'description', '', 'placeholder="Describe what this photo shows."');
    }
    return cq_input('Document date', 'site_record_date', $date, 'date', 'required')
        . cq_input('Document title', 'original_name', '', 'text', 'required maxlength="255"')
        . cq_media('Document file', 'filename', 'media_id', false, true)
        . cq_select('Document type', 'clerk_document_type', ['daily' => 'Daily record', 'quality' => 'Quality', 'safety' => 'Safety', 'meeting' => 'Meeting', 'ipc' => 'IPC', 'other' => 'Other'], 'other')
        . cq_input('Version', 'version', '1.0', 'text', 'maxlength="20"')
        . '<label class="clerk-quality-check"><input type="checkbox" name="review_required" value="1"><span>Needs review</span></label>'
        . cq_textarea('Description', 'description');
}

function clerk_quality_ipc_form(array $pendingIpcs): string
{
    if ($pendingIpcs === []) {
        return '<div class="empty-state empty-state--compact is-wide" data-ipc-empty><strong class="empty-state__title">No IPCs waiting for verification</strong><span class="empty-state__text">Only contractor claims with status Submitted appear here. Historical IPCs remain in the register below.</span></div>';
    }
    $html = '<label class="is-wide"><span>IPC to verify (submitted only)</span><select name="ipc_id" required data-ipc-select><option value="">Choose IPC</option>';
    foreach ($pendingIpcs as $record) {
        $label = 'IPC #' . (int)$record['ipc_number']
            . ' · ' . Security::e(money((float)($record['net_amount'] ?? 0)))
            . ' · ' . Security::e(format_date((string)($record['period_from'] ?? '')) . ' – ' . format_date((string)($record['period_to'] ?? '')))
            . ' · ' . (int)($record['line_count'] ?? 0) . ' lines';
        $html .= '<option value="' . (int)$record['id'] . '">' . $label . '</option>';
    }
    $html .= '</select></label>';
    $checks = [
        'site_records_checked' => 'Site records checked',
        'line_quantities_checked' => 'Line quantities checked',
        'supporting_documents_checked' => 'Supporting documents checked',
        'exceptions_noted' => 'Exceptions noted where needed',
    ];
    foreach ($checks as $name => $label) {
        $html .= '<label class="clerk-quality-check"><input type="checkbox" name="' . Security::e($name) . '" value="1"><span>' . Security::e($label) . '</span></label>';
    }
    return $html . cq_textarea('Verification comment', 'clerk_verification_comment', '', 'required placeholder="Record your site verification notes. Required for endorse and return."');
}

function clerk_quality_table(string $type, array $records, string $empty): string
{
    $hasActions = $type !== 'ipc';
    $headers = match ($type) {
        'quality' => ['Record', 'Result', 'Status', 'Date', 'By'],
        'itp' => ['Activity', 'Area', 'Status', 'Date', 'By'],
        'hs' => ['Incident', 'Severity', 'Status', 'Date', 'By'],
        'ncr', 'defect' => ['Reference', 'Severity', 'Status', 'Date', 'By'],
        'meeting' => ['Meeting', 'Venue', 'Status', 'Date', 'By'],
        'document' => ['Title', 'File', 'Review', 'Date', 'By'],
        'photo' => ['Photo', 'Caption / file', 'Review', 'Date', 'By'],
        'ipc' => ['IPC', 'Amount', 'Status', 'Submitted', 'Contractor'],
        default => ['Record', 'Detail', 'Status', 'Date', 'By'],
    };
    if ($hasActions) {
        $headers[] = 'Actions';
    }
    $html = '<thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . Security::e($h) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    if ($records === []) {
        return $html . '<tr><td colspan="' . count($headers) . '"><div class="empty-state"><strong>' . Security::e($empty) . '</strong><span>Try adjusting filters or add a new record.</span></div></td></tr></tbody>';
    }
    foreach ($records as $record) {
        $id = (int)($record['id'] ?? 0);
        $status = (string)clerk_quality_status($type, $record);
        $html .= '<tr data-record-id="' . $id . '">';
        $html .= '<td>' . clerk_quality_record_cell($type, $record) . '</td>';
        $html .= '<td>' . clerk_quality_extra_cell($type, $record) . '</td>';
        $html .= '<td><span class="badge ' . Security::e(status_badge_class($status)) . '">' . Security::e(status_label($status)) . '</span></td>';
        $html .= '<td>' . Security::e(format_date((string)clerk_quality_date($type, $record))) . '</td>';
        $html .= '<td>' . Security::e($record['actor_name'] ?? '-') . '</td>';
        if ($hasActions) {
            $html .= '<td class="cq-actions-cell">'
                . '<div class="cq-row-actions">'
                . '<button class="btn btn--outline btn--sm" type="button" data-view-record data-id="' . $id . '"><i class="fa-solid fa-eye"></i> View</button>'
                . '<button class="btn btn--primary btn--sm" type="button" data-edit-record data-id="' . $id . '"><i class="fa-solid fa-pen"></i> Edit</button>'
                . '</div></td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody>';
}

function clerk_quality_media_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = ltrim(str_replace('\\', '/', $path), '/');
    return Url::asset($path);
}

function clerk_quality_is_image_path(?string $path): bool
{
    return (bool)preg_match('/\.(jpe?g|png|webp|gif)$/i', (string)$path);
}

function clerk_quality_extra_cell(string $type, array $record): string
{
    if ($type === 'photo' || $type === 'document') {
        $path = (string)($record['filename'] ?? $record['document_path'] ?? $record['photo_path'] ?? '');
        $url = clerk_quality_media_url($path);
        $name = basename($path !== '' ? $path : ((string)($record['original_name'] ?? 'file')));
        $size = number_format(((float)($record['size'] ?? 0)) / 1024, 1) . ' KB';
        if ($type === 'photo' && $url !== '' && clerk_quality_is_image_path($path)) {
            return '<div class="cq-file-cell">'
                . '<button type="button" class="cq-thumb-btn" data-cq-open-photo data-src="' . Security::e($url) . '" data-title="' . Security::e((string)($record['original_name'] ?? $name)) . '" title="View photo">'
                . '<img src="' . Security::e($url) . '" alt="' . Security::e((string)($record['original_name'] ?? 'Photo')) . '">'
                . '</button>'
                . '<div><strong>' . Security::e($name) . '</strong><small>' . Security::e($size) . '</small></div></div>';
        }
        if ($url !== '') {
            return '<div class="cq-file-cell"><span class="cq-file-icon"><i class="fa-solid fa-file-lines"></i></span>'
                . '<div><a href="' . Security::e($url) . '" target="_blank" rel="noopener"><strong>' . Security::e($name) . '</strong></a>'
                . '<small>' . Security::e($size) . '</small></div></div>';
        }
        return '<strong>' . Security::e($name) . '</strong><small>No file path stored</small>';
    }

    return match ($type) {
        'quality' => '<strong>' . Security::e((string)($record['actual_result'] ?? $record['result'] ?? '-')) . '</strong><small>' . Security::e((string)($record['required_result'] ?? '')) . '</small>',
        'itp' => '<strong>' . Security::e((string)($record['inspection_area'] ?? $record['hold_point'] ?? '-')) . '</strong><small>' . Security::e((string)($record['outcome'] ?? '')) . '</small>',
        'hs', 'ncr', 'defect' => '<span class="badge ' . Security::e(status_badge_class((string)($record['severity'] ?? ''))) . '">' . Security::e(status_label((string)($record['severity'] ?? '-'))) . '</span>',
        'meeting' => '<strong>' . Security::e((string)($record['venue'] ?? '-')) . '</strong><small>' . Security::e(status_label((string)($record['action_status'] ?? ''))) . '</small>',
        'ipc' => '<strong>' . Security::e(money((float)($record['net_amount'] ?? 0))) . '</strong><small>' . (int)($record['line_count'] ?? 0) . ' lines</small>',
        default => '<strong>' . Security::e((string)($record['project_name'] ?? '-')) . '</strong>',
    };
}

function clerk_quality_record_cell(string $type, array $record): string
{
    $title = match ($type) {
        'quality' => $record['test_type'] ?? 'Quality test',
        'itp' => $record['activity'] ?? 'Inspection',
        'hs' => status_label((string)($record['incident_type'] ?? 'Incident')),
        'ncr' => $record['ncr_reference'] ?: 'Non-conformance',
        'defect' => $record['defect_reference'] ?: 'Defect',
        'meeting' => status_label((string)($record['meeting_type'] ?? 'Meeting')) . ' meeting',
        'document', 'photo' => $record['original_name'] ?? 'Evidence',
        'ipc' => 'IPC #' . (int)($record['ipc_number'] ?? 0),
        default => 'Record',
    };
    $sub = match ($type) {
        'quality' => trim((string)($record['location_on_site'] ?? '')),
        'itp' => trim((string)($record['hold_point'] ?? '')),
        'hs', 'ncr', 'defect' => trim(substr((string)($record['description'] ?? ''), 0, 100)),
        'meeting' => trim((string)($record['chairperson'] ?? '')),
        'document', 'photo' => trim((string)($record['description'] ?? '')),
        'ipc' => (string)($record['project_name'] ?? ''),
        default => '',
    };

    if ($type === 'photo') {
        $path = (string)($record['filename'] ?? '');
        $url = clerk_quality_media_url($path);
        $thumb = '';
        if ($url !== '' && clerk_quality_is_image_path($path)) {
            $thumb = '<button type="button" class="cq-thumb-btn cq-thumb-btn--inline" data-cq-open-photo data-src="' . Security::e($url) . '" data-title="' . Security::e((string)$title) . '" title="View photo">'
                . '<img src="' . Security::e($url) . '" alt="' . Security::e((string)$title) . '"></button>';
        }
        return '<div class="cq-photo-title">' . $thumb . '<div><strong>' . Security::e((string)$title) . '</strong><small>' . Security::e($sub !== '' ? $sub : 'No caption') . '</small></div></div>';
    }

    return '<strong>' . Security::e((string)$title) . '</strong><small>' . Security::e($sub !== '' ? $sub : 'No extra details') . '</small>';
}

function clerk_quality_status(string $type, array $record): string
{
    return match ($type) {
        'quality' => (string)($record['verification_status'] ?? 'pending'),
        'itp' => (string)($record['inspection_status'] ?? 'pending'),
        'document', 'photo' => (string)($record['consultant_review_status'] ?? 'pending'),
        default => (string)($record['status'] ?? 'pending'),
    };
}

function clerk_quality_date(string $type, array $record): string
{
    return match ($type) {
        'quality' => (string)($record['test_date'] ?? ''),
        'itp' => (string)($record['inspection_date'] ?? ''),
        'hs' => (string)($record['incident_date'] ?? ''),
        'ncr', 'defect' => (string)($record['raised_date'] ?? ''),
        'meeting' => (string)($record['meeting_date'] ?? ''),
        'document', 'photo' => (string)($record['site_record_date'] ?? $record['created_at'] ?? ''),
        'ipc' => (string)($record['submitted_at'] ?? $record['created_at'] ?? ''),
        default => '',
    };
}
