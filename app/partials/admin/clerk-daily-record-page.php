<?php
$recordType = $recordType ?? 'diary';
$recordConfig = ClerkDailyRecord::config($recordType);
$recordPage = (string)$recordConfig['page'];
$recordApi = (string)$recordConfig['api'];
$userId = (int)Auth::id();
$date = Security::cleanString((string)($_GET['date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$projects = ClerkDailyRecord::projects($userId);
$projectId = ClerkDailyRecord::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$project = $projectId ? ClerkDailyRecord::project($userId, $projectId) : null;
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
];
$stats = ClerkDailyRecord::stats($recordType, $userId, $projectId);
$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1, 1));
$total = ClerkDailyRecord::count($recordType, $userId, $projectId, $filters);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$records = ClerkDailyRecord::list($recordType, $userId, $projectId, $filters, $perPage, ($page - 1) * $perPage);
$defaults = ClerkDailyRecord::formDefaults($recordType, $projectId, $date);
$peerTypes = array_intersect_key(ClerkDailyRecord::types(), array_flip(['diary', 'weather', 'labour', 'materials', 'equipment']));
$siteHub = ClerkDailyRecord::siteHubPeers();
$materialOptions = $recordType === 'materials' && $projectId > 0 ? ClerkDailyRecord::materialOptions($userId, $projectId) : [];
$equipmentOptions = $recordType === 'equipment' && $projectId > 0 ? ClerkDailyRecord::equipmentOptions($userId, $projectId) : [];
$detailUrl = Url::to('api/clerk/daily-record-detail.php');

$pageTitle = $recordConfig['title'];
$pageDescription = $recordConfig['label'] . ' for assigned clerk sites.';
$adminRole = 'clerk';
$contentClass = 'clerk-records-page';
$csrfForm = 'clerk_daily_records';
$componentCss = array_values(array_unique(array_merge((array)($componentCss ?? []), ['clerk-records'])));
$pageScripts = array_values(array_unique(array_merge((array)($pageScripts ?? []), ['clerk-records'])));
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Clerk of Works', 'url' => Url::to('admin/clerk/dashboard.php')],
    ['label' => $recordConfig['title']],
];

include __DIR__ . '/shell-start.php';
?>

<section class="clerk-record-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid <?= Security::e($recordConfig['icon']) ?>" aria-hidden="true"></i> Site records</span>
    <h2><?= Security::e($recordConfig['title']) ?></h2>
    <p><?= Security::e(clerk_record_intro($recordType)) ?></p>
  </div>
  <div class="clerk-record-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/clerk/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/clerk/messages.php')) ?>"><i class="fa-solid fa-comments"></i> Messages</a>
  </div>
</section>

<nav class="clerk-record-hub card" aria-label="Site records hub">
  <?php foreach ($siteHub as $hubKey => $hub): ?>
    <?php
      $hubQuery = $projectId ? ('?project_id=' . $projectId) : '';
      $isHubActive = $hubKey === $recordType;
    ?>
    <a class="clerk-record-hub__link<?= $isHubActive ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($hub['page'] . $hubQuery)) ?>"<?= $isHubActive ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid <?= Security::e($hub['icon']) ?>" aria-hidden="true"></i>
      <span><?= Security::e($hub['title']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<nav class="clerk-record-peers card" aria-label="Daily record types">
  <?php foreach ($peerTypes as $peerKey => $peerConfig): ?>
    <?php
      $peerQuery = $projectId ? ('?project_id=' . $projectId) : '';
      $isActive = $peerKey === $recordType;
    ?>
    <a class="clerk-record-peer<?= $isActive ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($peerConfig['page'] . $peerQuery)) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid <?= Security::e($peerConfig['icon']) ?>" aria-hidden="true"></i>
      <span><?= Security::e($peerConfig['title']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if ($projects === []): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Daily records appear once a site is assigned to you.</span></div>
<?php else: ?>

<section class="clerk-record-stats">
  <?php clerk_record_stat('fa-folder-open', $stats['total'], 'Records', 'Current project'); ?>
  <?php clerk_record_stat('fa-calendar-day', $stats['today'], 'Today', 'Logged today'); ?>
  <?php clerk_record_stat('fa-hourglass-half', $stats['open'], 'Pending', 'Needs follow-up'); ?>
  <?php clerk_record_stat('fa-triangle-exclamation', $stats['risk'], 'Flags', 'Needs attention'); ?>
  <?php clerk_record_stat('fa-chart-simple', clerk_record_value($recordType, $stats['value']), clerk_record_value_label($recordType), 'Measured total'); ?>
</section>

<form class="card clerk-record-filter" method="get">
  <label><span>Project</span><select name="project_id"><?php foreach ($projects as $option): ?><option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option><?php endforeach; ?></select></label>
  <label><span>Record date</span><input type="date" name="date" value="<?= Security::e($date) ?>"></label>
  <label><span>From</span><input type="date" name="date_from" value="<?= Security::e($filters['date_from']) ?>"></label>
  <label><span>To</span><input type="date" name="date_to" value="<?= Security::e($filters['date_to']) ?>"></label>
  <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Search records..."></label>
  <label><span>Status</span><?= clerk_record_status_select($recordType, $filters['status']) ?></label>
  <div class="clerk-record-filter__actions">
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to($recordPage)) ?>">Reset</a>
  </div>
</form>

<section class="clerk-record-layout">
  <main class="card clerk-record-panel">
    <div class="card__header">
      <div>
        <h2 class="card__title"><?= Security::e(clerk_record_form_title($recordType)) ?></h2>
        <p class="card__subtitle"><?= Security::e($project['name'] ?? 'Assigned project') ?></p>
      </div>
      <span class="badge badge--info"><?= Security::e(format_date($date)) ?></span>
    </div>
    <form class="clerk-record-form"
          data-clerk-record-form
          data-record-type="<?= Security::e($recordType) ?>"
          data-detail-url="<?= Security::e($detailUrl) ?>"
          action="<?= Security::e(Url::to($recordApi)) ?>"
          method="post">
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
      <?= clerk_record_form($recordType, $date, $defaults, $materialOptions, $equipmentOptions) ?>
      <div class="clerk-record-form__actions">
        <button class="btn btn--outline" type="button" data-form-reset>Reset</button>
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Record</button>
      </div>
      <p class="clerk-record-form-status" data-form-status hidden></p>
    </form>
  </main>

  <aside class="card clerk-record-panel">
    <h2>Site Context</h2>
    <div class="clerk-record-side-list">
      <span><strong><?= Security::e($project['name'] ?? '-') ?></strong><small>Assigned site</small></span>
      <span><strong><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /') ?: 'Project location') ?></strong><small>Location</small></span>
      <span><strong><?= Security::e(status_label((string)($project['status'] ?? '-'))) ?></strong><small>Project status</small></span>
      <span><strong><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</strong><small>Progress</small></span>
      <?php if ($recordType === 'labour'): ?>
        <?php
          $cSkilled = (int)($defaults['skilled_count'] ?? 0);
          $cUnskilled = (int)($defaults['unskilled_count'] ?? 0);
          $cSup = (int)($defaults['supervisor_count'] ?? 0);
          $cTotal = (int)($defaults['total'] ?? 0);
          $hasContractor = $cTotal > 0 || $cSkilled > 0 || $cUnskilled > 0;
        ?>
        <span>
          <strong><?= $hasContractor ? format_number($cTotal) : 'None' ?></strong>
          <small><?= $hasContractor
            ? ('Contractor submitted · S ' . $cSkilled . ' / U ' . $cUnskilled . ' / Sup ' . $cSup)
            : 'No contractor labour for this date' ?></small>
        </span>
      <?php endif; ?>
    </div>
  </aside>
</section>

<section class="card clerk-record-panel">
  <div class="card__header">
    <div>
      <h2 class="card__title"><?= Security::e($recordConfig['title']) ?> Registry</h2>
      <p class="card__subtitle">Recent records for the selected project. Use View / Edit to open details.</p>
    </div>
    <span class="badge badge--info"><?= format_number($total) ?> records</span>
  </div>
  <div class="clerk-record-table-wrap">
    <table class="clerk-record-table">
      <?= clerk_record_table($recordType, $records, $recordConfig['empty']) ?>
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

<div class="cr-overlay" data-cr-view-modal hidden aria-hidden="true">
  <div class="cr-modal" role="dialog" aria-modal="true" aria-labelledby="crViewTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-eye" aria-hidden="true"></i> Record detail</span>
        <h2 id="crViewTitle" data-cr-view-title><?= Security::e($recordConfig['title']) ?></h2>
        <p data-cr-view-sub></p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-cr-view-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </header>
    <div class="cr-modal__body" data-cr-view-body>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">Select a record</strong></div>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-cr-view-close>Close</button>
      <button class="btn btn--primary" type="button" data-cr-view-edit><i class="fa-solid fa-pen"></i> Edit</button>
    </footer>
  </div>
</div>

<div class="cr-overlay" data-cr-edit-modal hidden aria-hidden="true">
  <div class="cr-modal cr-modal--edit" role="dialog" aria-modal="true" aria-labelledby="crEditTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit record</span>
        <h2 id="crEditTitle">Edit <?= Security::e($recordConfig['label']) ?></h2>
        <p data-cr-edit-sub>Update the selected record and save changes.</p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-cr-edit-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </header>
    <div class="cr-modal__body">
      <form class="clerk-record-form clerk-record-form--modal"
            data-cr-edit-form
            data-record-type="<?= Security::e($recordType) ?>"
            action="<?= Security::e(Url::to($recordApi)) ?>"
            method="post">
        <input type="hidden" name="id" value="" data-cr-edit-id>
        <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
        <?= clerk_record_form($recordType, $date, [], $materialOptions, $equipmentOptions, true) ?>
        <p class="clerk-record-form-status" data-form-status hidden></p>
      </form>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-cr-edit-close>Cancel</button>
      <button class="btn btn--primary" type="button" data-cr-edit-save><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
    </footer>
  </div>
</div>

<?php endif; ?>

<?php
include __DIR__ . '/shell-end.php';

function clerk_record_intro(string $type): string
{
    return match ($type) {
        'diary' => 'Capture daily work done, site instructions, safety observations, issues and next-day plans.',
        'weather' => 'Record site weather conditions, rainfall and working time impact for the selected project.',
        'labour' => 'Verify contractor workforce numbers against the people observed on site.',
        'materials' => 'Log new deliveries or verify existing ones against quantities and condition.',
        'equipment' => 'Register plant or check existing equipment condition and site status.',
        default => 'Capture daily site records for the selected project.',
    };
}

function clerk_record_stat(string $icon, mixed $value, string $label, string $hint): void
{
    echo '<article class="clerk-record-stat card"><span><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></article>';
}

function clerk_record_value(string $type, mixed $value): string
{
    return match ($type) {
        'weather' => number_format((float)$value, 1) . ' hrs',
        'materials' => number_format((float)$value, 3),
        default => format_number((float)$value),
    };
}

function clerk_record_value_label(string $type): string
{
    return match ($type) {
        'weather' => 'Hours Lost',
        'labour' => 'Verified Labour',
        'materials' => 'Quantity',
        'equipment' => 'On Site',
        default => 'Total',
    };
}

function clerk_record_status_select(string $type, string $current): string
{
    $options = match ($type) {
        'diary' => ['draft' => 'Draft', 'submitted' => 'Submitted', 'reviewed' => 'Reviewed'],
        'weather' => ['none' => 'No impact', 'minor' => 'Minor', 'moderate' => 'Moderate', 'severe' => 'Severe'],
        'labour' => ['pending' => 'Pending', 'verified' => 'Verified', 'queried' => 'Queried'],
        'materials' => ['pending' => 'Pending', 'accepted' => 'Accepted', 'queried' => 'Queried', 'rejected' => 'Rejected'],
        'equipment' => ['pending' => 'Pending', 'present' => 'Present', 'missing' => 'Missing', 'off-site' => 'Off site', 'maintenance' => 'Maintenance', 'queried' => 'Queried'],
        default => [],
    };
    $html = '<select name="status"><option value="">All statuses</option>';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . Security::e($value) . '"' . ($current === $value ? ' selected' : '') . '>' . Security::e($label) . '</option>';
    }
    return $html . '</select>';
}

function clerk_record_form_title(string $type): string
{
    return match ($type) {
        'diary' => 'Write Today\'s Diary',
        'weather' => 'Record Weather',
        'labour' => 'Verify Labour',
        'materials' => 'Log / Verify Delivery',
        'equipment' => 'Register / Check Equipment',
        default => 'Record Details',
    };
}

function clerk_record_form(string $type, string $date, array $defaults, array $materialOptions = [], array $equipmentOptions = [], bool $editMode = false): string
{
    return match ($type) {
        'diary' => clerk_record_diary_form($date, $defaults),
        'weather' => clerk_record_weather_form($date, $defaults),
        'labour' => clerk_record_labour_form($date, $defaults),
        'materials' => clerk_record_material_form($date, $materialOptions, $editMode),
        'equipment' => clerk_record_equipment_form($date, $equipmentOptions, $editMode),
        default => '',
    };
}

function clerk_input(string $label, string $name, mixed $value = '', string $type = 'text', string $attrs = ''): string
{
    return '<label><span>' . Security::e($label) . '</span><input type="' . Security::e($type) . '" name="' . Security::e($name) . '" value="' . Security::e((string)$value) . '" ' . $attrs . '></label>';
}

function clerk_textarea(string $label, string $name, mixed $value = '', string $attrs = ''): string
{
    return '<label class="is-wide"><span>' . Security::e($label) . '</span><textarea name="' . Security::e($name) . '" rows="4" ' . $attrs . '>' . Security::e((string)$value) . '</textarea></label>';
}

function clerk_select(string $label, string $name, array $options, string $current = '', string $attrs = ''): string
{
    $html = '<label><span>' . Security::e($label) . '</span><select name="' . Security::e($name) . '" ' . $attrs . '>';
    foreach ($options as $value => $text) {
        $html .= '<option value="' . Security::e((string)$value) . '"' . ($current === (string)$value ? ' selected' : '') . '>' . Security::e($text) . '</option>';
    }
    return $html . '</select></label>';
}

function clerk_record_diary_form(string $date, array $d): string
{
    return clerk_input('Diary date', 'diary_date', $d['diary_date'] ?? $date, 'date', 'required')
        . clerk_input('Report title', 'report_title', $d['report_title'] ?? 'Daily site diary', 'text', 'maxlength="180"')
        . clerk_input('Weather summary', 'weather_summary', $d['weather_summary'] ?? '', 'text', 'maxlength="180"')
        . clerk_select('Status', 'status', ['draft' => 'Draft', 'submitted' => 'Submitted'], (string)($d['status'] ?? 'submitted'))
        . clerk_textarea('Work done', 'work_done', $d['work_done'] ?? '', 'required placeholder="Summarise completed work and active areas."')
        . clerk_textarea('Issues raised', 'issues_raised', $d['issues_raised'] ?? '', 'placeholder="Record blockers, access issues or instructions needing follow-up."')
        . clerk_textarea('Safety observations', 'safety_observations', $d['safety_observations'] ?? '', 'placeholder="Record safety observations or controls checked."')
        . clerk_textarea('Visitors / instructions', 'visitors_instructions', $d['visitors_instructions'] ?? '', 'placeholder="Record visitors, site instructions or directions received."')
        . clerk_textarea('Next day plan', 'next_day_plan', $d['next_day_plan'] ?? '', 'placeholder="Planned work and follow-up actions."');
}

function clerk_record_weather_form(string $date, array $d): string
{
    $conditions = ['clear' => 'Clear', 'cloudy' => 'Cloudy', 'light-rain' => 'Light rain', 'heavy-rain' => 'Heavy rain', 'windy' => 'Windy', 'hot' => 'Hot', 'cold' => 'Cold', 'storm' => 'Storm'];
    return clerk_input('Log date', 'log_date', $d['log_date'] ?? $date, 'date', 'required')
        . clerk_select('Morning condition', 'morning_condition', $conditions, (string)($d['morning_condition'] ?? 'clear'))
        . clerk_select('Afternoon condition', 'afternoon_condition', $conditions, (string)($d['afternoon_condition'] ?? 'clear'))
        . clerk_select('Impact level', 'impact_level', ['none' => 'No impact', 'minor' => 'Minor', 'moderate' => 'Moderate', 'severe' => 'Severe'], (string)($d['impact_level'] ?? 'none'))
        . clerk_input('Rainfall mm', 'rainfall_mm', $d['rainfall_mm'] ?? '0', 'number', 'min="0" step="0.1"')
        . clerk_input('Min temperature', 'temperature_min', $d['temperature_min'] ?? '', 'number', 'step="0.1"')
        . clerk_input('Max temperature', 'temperature_max', $d['temperature_max'] ?? '', 'number', 'step="0.1"')
        . clerk_input('Working hours', 'working_hours', $d['working_hours'] ?? '8', 'number', 'min="0" max="24" step="0.1"')
        . clerk_input('Hours lost', 'working_hours_lost', $d['working_hours_lost'] ?? '0', 'number', 'min="0" max="24" step="0.1"')
        . clerk_textarea('Remarks', 'remarks', $d['remarks'] ?? '', 'placeholder="Describe weather impact on site operations."');
}

function clerk_record_labour_form(string $date, array $d = []): string
{
    $cTotal = (int)($d['total'] ?? 0);
    $banner = $cTotal > 0 || (int)($d['skilled_count'] ?? 0) > 0
        ? '<div class="clerk-record-banner is-wide"><strong>Contractor submitted: ' . (int)$cTotal . '</strong><span>Skilled ' . (int)($d['skilled_count'] ?? 0) . ' · Unskilled ' . (int)($d['unskilled_count'] ?? 0) . ' · Supervisors ' . (int)($d['supervisor_count'] ?? 0) . '. Variance updates after save.</span></div>'
        : '<div class="clerk-record-banner is-wide is-warn"><strong>No contractor labour for this date</strong><span>You can still record a clerk-only count. Add a short note if contractor has not submitted.</span></div>';

    return $banner
        . clerk_input('Verification date', 'diary_date', $d['diary_date'] ?? $date, 'date', 'required')
        . clerk_input('Skilled workers counted', 'clerk_skilled_count', (string)(int)($d['clerk_skilled_count'] ?? 0), 'number', 'min="0" data-labour-count')
        . clerk_input('Unskilled workers counted', 'clerk_unskilled_count', (string)(int)($d['clerk_unskilled_count'] ?? 0), 'number', 'min="0" data-labour-count')
        . clerk_input('Supervisors counted', 'clerk_supervisor_count', (string)(int)($d['clerk_supervisor_count'] ?? 0), 'number', 'min="0" data-labour-count')
        . clerk_select('Verification status', 'verification_status', ['verified' => 'Verified', 'queried' => 'Queried', 'pending' => 'Pending'], (string)($d['verification_status'] ?? 'verified'))
        . '<div class="clerk-record-total"><strong data-labour-total>' . (int)($d['clerk_total'] ?? 0) . '</strong><span>Clerk counted total</span></div>'
        . clerk_textarea('Verification notes', 'verification_notes', $d['verification_notes'] ?? '', 'placeholder="Explain any variance or site observation."');
}

function clerk_record_material_form(string $date, array $options = [], bool $editMode = false): string
{
    $picker = '';
    if (!$editMode) {
        $picker = '<label class="is-wide"><span>Verify existing delivery (optional)</span><select name="id" data-material-picker><option value="">— New delivery —</option>';
        foreach ($options as $opt) {
            $label = '#' . (int)$opt['id'] . ' · ' . ($opt['material'] ?? 'Material')
                . ' · ' . format_date($opt['delivery_date'] ?? null)
                . ' · ' . number_format((float)($opt['quantity'] ?? 0), 3) . ' ' . ($opt['unit'] ?? '')
                . ' · ' . status_label((string)($opt['verification_status'] ?? 'pending'));
            $picker .= '<option value="' . (int)$opt['id'] . '"'
                . ' data-material="' . Security::e((string)($opt['material'] ?? '')) . '"'
                . ' data-supplier="' . Security::e((string)($opt['supplier'] ?? '')) . '"'
                . ' data-delivery-date="' . Security::e((string)($opt['delivery_date'] ?? '')) . '"'
                . ' data-quantity="' . Security::e((string)($opt['quantity'] ?? '0')) . '"'
                . ' data-unit="' . Security::e((string)($opt['unit'] ?? '')) . '"'
                . ' data-delivery-note="' . Security::e((string)($opt['delivery_note_no'] ?? '')) . '"'
                . ' data-verified-qty="' . Security::e((string)($opt['verified_quantity'] ?? $opt['quantity'] ?? '0')) . '"'
                . '>' . Security::e($label) . '</option>';
        }
        $picker .= '</select></label>';
    }

    return $picker
        . clerk_input('Delivery date', 'delivery_date', $date, 'date', 'required')
        . clerk_input('Material', 'material', '', 'text', 'maxlength="150" required')
        . clerk_input('Supplier', 'supplier', '', 'text', 'maxlength="150"')
        . clerk_input('Quantity delivered', 'quantity', '0', 'number', 'min="0" step="0.001"')
        . clerk_input('Verified quantity', 'verified_quantity', '0', 'number', 'min="0" step="0.001"')
        . clerk_input('Unit', 'unit', '', 'text', 'maxlength="30" required')
        . clerk_input('Delivery note no.', 'delivery_note_no', '', 'text', 'maxlength="60"')
        . clerk_select('Condition', 'condition', ['good' => 'Good', 'damaged' => 'Damaged', 'short-delivered' => 'Short delivered', 'pending-check' => 'Pending check'], 'good')
        . clerk_select('Verification status', 'verification_status', ['accepted' => 'Accepted', 'queried' => 'Queried', 'rejected' => 'Rejected', 'pending' => 'Pending'], 'accepted')
        . clerk_textarea('Verification notes', 'verification_notes', '', 'placeholder="Record quantity, condition or document concerns."');
}

function clerk_record_equipment_form(string $date, array $options = [], bool $editMode = false): string
{
    $picker = '';
    if (!$editMode) {
        $picker = '<label class="is-wide"><span>Check existing equipment (optional)</span><select name="id" data-equipment-picker><option value="">— New equipment —</option>';
        foreach ($options as $opt) {
            $label = '#' . (int)$opt['id'] . ' · ' . ($opt['equipment_type'] ?? 'Equipment')
                . ($opt['registration'] ? ' · ' . $opt['registration'] : '')
                . ' · ' . status_label((string)($opt['check_status'] ?? 'pending'));
            $picker .= '<option value="' . (int)$opt['id'] . '"'
                . ' data-equipment-type="' . Security::e((string)($opt['equipment_type'] ?? '')) . '"'
                . ' data-registration="' . Security::e((string)($opt['registration'] ?? '')) . '"'
                . ' data-owner="' . Security::e((string)($opt['owner'] ?? '')) . '"'
                . ' data-date-on-site="' . Security::e((string)($opt['date_on_site'] ?? '')) . '"'
                . ' data-condition="' . Security::e((string)($opt['condition'] ?? 'good')) . '"'
                . ' data-status="' . Security::e((string)($opt['status'] ?? 'on-site')) . '"'
                . ' data-check-status="' . Security::e((string)($opt['check_status'] ?? 'pending')) . '"'
                . '>' . Security::e($label) . '</option>';
        }
        $picker .= '</select></label>';
    }

    return $picker
        . clerk_input('Equipment type', 'equipment_type', '', 'text', 'maxlength="120" required')
        . clerk_input('Registration / serial', 'registration', '', 'text', 'maxlength="60"')
        . clerk_input('Owner', 'owner', '', 'text', 'maxlength="150"')
        . clerk_input('Date on site', 'date_on_site', $date, 'date')
        . clerk_input('Date off site', 'date_off_site', '', 'date')
        . clerk_select('Condition', 'condition', ['good' => 'Good', 'serviceable' => 'Serviceable', 'maintenance' => 'Maintenance', 'poor' => 'Poor'], 'good')
        . clerk_select('Site status', 'status', ['on-site' => 'On site', 'off-site' => 'Off site', 'maintenance' => 'Maintenance'], 'on-site')
        . clerk_select('Check status', 'check_status', ['present' => 'Present', 'missing' => 'Missing', 'off-site' => 'Off site', 'maintenance' => 'Maintenance', 'queried' => 'Queried', 'pending' => 'Pending'], 'present')
        . clerk_textarea('Check notes', 'check_notes', '', 'placeholder="Record site condition, missing items or maintenance concerns."');
}

function clerk_record_table(string $type, array $records, string $empty): string
{
    $headers = match ($type) {
        'diary' => ['Date', 'Title', 'Work done', 'Status', 'Actions'],
        'weather' => ['Date', 'Conditions', 'Rainfall', 'Impact', 'Actions'],
        'labour' => ['Date', 'Contractor', 'Clerk Count', 'Status', 'Actions'],
        'materials' => ['Date', 'Material', 'Quantity', 'Status', 'Actions'],
        'equipment' => ['Equipment', 'Owner', 'Condition', 'Check', 'Actions'],
        default => ['Record', 'Detail', 'Status', 'Date', 'Actions'],
    };
    $html = '<thead><tr>' . implode('', array_map(static fn ($h): string => '<th>' . Security::e($h) . '</th>', $headers)) . '</tr></thead><tbody>';
    if ($records === []) {
        return $html . '<tr><td colspan="' . count($headers) . '"><div class="empty-state empty-state--compact"><strong class="empty-state__title">' . Security::e($empty) . '</strong><span class="empty-state__text">Records will appear here after saving.</span></div></td></tr></tbody>';
    }
    foreach ($records as $row) {
        $html .= clerk_record_row($type, $row);
    }
    return $html . '</tbody>';
}

function clerk_record_actions(int $id): string
{
    return '<td class="cr-actions-cell"><div class="cr-row-actions">'
        . '<button class="btn btn--outline btn--sm" type="button" data-view-record data-id="' . $id . '"><i class="fa-solid fa-eye"></i> View</button>'
        . '<button class="btn btn--primary btn--sm" type="button" data-edit-record data-id="' . $id . '"><i class="fa-solid fa-pen"></i> Edit</button>'
        . '</div></td>';
}

function clerk_record_row(string $type, array $r): string
{
    $id = (int)($r['id'] ?? 0);
    $actions = clerk_record_actions($id);
    return match ($type) {
        'diary' => '<tr data-record-id="' . $id . '"><td><strong>' . Security::e(format_date($r['diary_date'] ?? null)) . '</strong><small>' . Security::e($r['recorded_by_name'] ?? '') . '</small></td><td>' . Security::e($r['report_title'] ?? '-') . '</td><td><small>' . Security::e(substr((string)($r['work_done'] ?? '-'), 0, 120)) . '</small></td><td><span class="badge ' . Security::e(status_badge_class((string)($r['status'] ?? 'submitted'))) . '">' . Security::e(status_label((string)($r['status'] ?? 'submitted'))) . '</span></td>' . $actions . '</tr>',
        'weather' => '<tr data-record-id="' . $id . '"><td><strong>' . Security::e(format_date($r['log_date'] ?? null)) . '</strong></td><td>' . Security::e(status_label((string)($r['morning_condition'] ?? '-'))) . '<small>Afternoon: ' . Security::e(status_label((string)($r['afternoon_condition'] ?? '-'))) . '</small></td><td><strong>' . Security::e(number_format((float)($r['rainfall_mm'] ?? 0), 1)) . ' mm</strong><small>Lost ' . Security::e(number_format((float)($r['working_hours_lost'] ?? 0), 1)) . ' hrs</small></td><td><span class="badge ' . Security::e(status_badge_class((string)($r['impact_level'] ?? 'none'))) . '">' . Security::e(status_label((string)($r['impact_level'] ?? 'none'))) . '</span></td>' . $actions . '</tr>',
        'labour' => '<tr data-record-id="' . $id . '"><td><strong>' . Security::e(format_date($r['diary_date'] ?? null)) . '</strong></td><td>' . (int)($r['total'] ?? 0) . '<small>Submitted count</small></td><td><strong>' . (int)($r['clerk_total'] ?? 0) . '</strong><small>Variance: ' . (int)($r['variance_total'] ?? 0) . '</small></td><td><span class="badge ' . Security::e(status_badge_class((string)($r['verification_status'] ?? 'pending'))) . '">' . Security::e(status_label((string)($r['verification_status'] ?? 'pending'))) . '</span></td>' . $actions . '</tr>',
        'materials' => '<tr data-record-id="' . $id . '"><td><strong>' . Security::e(format_date($r['delivery_date'] ?? null)) . '</strong><small>' . Security::e($r['delivery_note_no'] ?? '') . '</small></td><td>' . Security::e($r['material'] ?? '-') . '<small>' . Security::e($r['supplier'] ?? '') . '</small></td><td><strong>' . Security::e(number_format((float)($r['verified_quantity'] ?? $r['quantity'] ?? 0), 3)) . ' ' . Security::e($r['unit'] ?? '') . '</strong><small>Delivered: ' . Security::e(number_format((float)($r['quantity'] ?? 0), 3)) . '</small></td><td><span class="badge ' . Security::e(status_badge_class((string)($r['verification_status'] ?? 'pending'))) . '">' . Security::e(status_label((string)($r['verification_status'] ?? 'pending'))) . '</span></td>' . $actions . '</tr>',
        'equipment' => '<tr data-record-id="' . $id . '"><td><strong>' . Security::e($r['equipment_type'] ?? '-') . '</strong><small>' . Security::e($r['registration'] ?? '') . '</small></td><td>' . Security::e($r['owner'] ?? '-') . '</td><td>' . Security::e(status_label((string)($r['condition'] ?? 'good'))) . '<small>' . Security::e(status_label((string)($r['status'] ?? 'on-site'))) . '</small></td><td><span class="badge ' . Security::e(status_badge_class((string)($r['check_status'] ?? 'pending'))) . '">' . Security::e(status_label((string)($r['check_status'] ?? 'pending'))) . '</span></td>' . $actions . '</tr>',
        default => '',
    };
}
