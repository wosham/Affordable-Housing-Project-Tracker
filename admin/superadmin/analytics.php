<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$filters = [
    'range' => Security::cleanString((string)($_GET['range'] ?? '30_days')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
];

$analytics = Analytics::dashboard($filters);
$range = $analytics['range'];
$summary = $analytics['summary'];
$projects = $analytics['projects'];
$finance = $analytics['finance'];
$attendance = $analytics['attendance'];
$content = $analytics['content'];
$risks = $analytics['risks'];

$paymentRatio = ((float)$summary['contract_value'] > 0)
    ? percentage(((float)$summary['paid_to_date'] / (float)$summary['contract_value']) * 100)
    : 0;
$completedRatio = ((int)$summary['project_total'] > 0)
    ? percentage(((int)$summary['completed_projects'] / (int)$summary['project_total']) * 100)
    : 0;

$chartData = [
    'projectStatus' => analytics_status_chart($projects['statuses'] ?? [], 'status'),
    'constituencyUnits' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['name'], $projects['by_constituency'] ?? []),
        'values' => array_map(static fn (array $row): int => (int)$row['units'], $projects['by_constituency'] ?? []),
    ],
    'completion' => [
        'labels' => array_map(static fn (array $row): string => safe_truncate((string)$row['name'], 26), $projects['completion'] ?? []),
        'values' => array_map(static fn (array $row): float => (float)$row['pct_complete'], $projects['completion'] ?? []),
    ],
    'ipcPipeline' => analytics_status_chart($finance['ipc_statuses'] ?? [], 'status'),
    'paymentMonths' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['month'], $finance['payment_months'] ?? []),
        'values' => array_map(static fn (array $row): float => (float)$row['total'], $finance['payment_months'] ?? []),
    ],
    'budgetBurn' => [
        'labels' => array_map(static fn (array $row): string => safe_truncate((string)$row['name'], 24), $finance['budget_burn'] ?? []),
        'contract' => array_map(static fn (array $row): float => (float)$row['contract_sum'], $finance['budget_burn'] ?? []),
        'paid' => array_map(static fn (array $row): float => (float)$row['paid'], $finance['budget_burn'] ?? []),
    ],
    'attendanceDaily' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['date'], $attendance['daily'] ?? []),
        'total' => array_map(static fn (array $row): int => (int)$row['total'], $attendance['daily'] ?? []),
        'geoFail' => array_map(static fn (array $row): int => (int)$row['geo_fail'], $attendance['daily'] ?? []),
    ],
    'attendanceProject' => [
        'labels' => array_map(static fn (array $row): string => safe_truncate((string)$row['name'], 24), $attendance['by_project'] ?? []),
        'values' => array_map(static fn (array $row): int => (int)$row['total'], $attendance['by_project'] ?? []),
    ],
    'attendanceRole' => analytics_status_chart($attendance['by_role'] ?? [], 'role'),
    'newsStatus' => analytics_status_chart($content['news_statuses'] ?? [], 'status'),
    'subscribersDaily' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['date'], $content['subscribers_daily'] ?? []),
        'values' => array_map(static fn (array $row): int => (int)$row['total'], $content['subscribers_daily'] ?? []),
    ],
    'contactStatus' => analytics_status_chart($content['contact_statuses'] ?? [], 'status'),
    'galleryStatus' => analytics_status_chart($content['gallery_statuses'] ?? [], 'status'),
];

$chartJson = json_encode($chartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$pageTitle = 'Analytics';
$pageDescription = 'Programme analytics across projects, finance, attendance and public content.';
$adminRole = 'superadmin';
$componentCss = ['cards', 'tables', 'charts'];
$pageScripts = ['https://cdn.jsdelivr.net/npm/chart.js', 'charts'];
$contentClass = 'sa-analytics-page';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Analytics'],
];

include dirname(__DIR__, 2) . '/app/partials/admin/shell-start.php';
?>

<section class="analytics-hero card">
  <div>
    <span class="eyebrow"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Intelligence</span>
    <h2>Analytics centre</h2>
    <p>Track delivery, finance, site attendance and public content performance from one command view.</p>
  </div>
  <div class="analytics-hero__meta">
    <span><strong><?= Security::e($range['start_label']) ?></strong><small>Start date</small></span>
    <span><strong><?= Security::e($range['end_label']) ?></strong><small>End date</small></span>
  </div>
</section>

<form class="card analytics-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/analytics.php')) ?>">
  <div class="filter-group">
    <label class="filter-label" for="range">Date range</label>
    <select class="form-select" id="range" name="range">
<?php foreach (Analytics::rangeOptions() as $value => $label): ?>
      <option value="<?= Security::e($value) ?>" <?= $range['preset'] === $value ? 'selected' : '' ?>><?= Security::e($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
  <div class="filter-group">
    <label class="filter-label" for="date_from">From</label>
    <input class="form-control" id="date_from" name="date_from" type="date" value="<?= Security::e($filters['date_from']) ?>">
  </div>
  <div class="filter-group">
    <label class="filter-label" for="date_to">To</label>
    <input class="form-control" id="date_to" name="date_to" type="date" value="<?= Security::e($filters['date_to']) ?>">
  </div>
  <div class="filter-actions">
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/analytics.php')) ?>">Reset</a>
  </div>
</form>

<section class="stat-grid stat-grid--4 analytics-kpis" aria-label="Analytics summary">
  <?php analytics_stat('fa-building', format_number($summary['project_total']), 'Projects', format_number($summary['active_projects']) . ' active'); ?>
  <?php analytics_stat('fa-gauge-high', format_percentage($summary['average_progress']), 'Average Completion', format_percentage($completedRatio) . ' completed'); ?>
  <?php analytics_stat('fa-coins', format_money($summary['contract_value']), 'Contract Value', format_percentage($paymentRatio) . ' paid'); ?>
  <?php analytics_stat('fa-money-bill-transfer', format_money($summary['range_payments']), 'Range Payments', 'Within selected dates'); ?>
  <?php analytics_stat('fa-user-check', format_number($summary['attendance_range']), 'Sign-ins', 'Selected range'); ?>
  <?php analytics_stat('fa-newspaper', format_number($summary['content_views']), 'News Views', 'Published articles'); ?>
  <?php analytics_stat('fa-file-invoice', format_money($finance['ipc_totals']['net'] ?? 0), 'IPC Net Value', 'All certificates'); ?>
  <?php analytics_stat('fa-list-check', format_number($finance['boq_exposure']['risk_items'] ?? 0), 'BOQ Risks', 'Quantity exposure'); ?>
</section>

<section class="analytics-section">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Delivery</span>
      <h2>Project analytics</h2>
    </div>
    <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>">Open Projects</a>
  </div>
  <div class="analytics-chart-grid">
    <?php analytics_chart_card('projectStatusChart', 'Projects by Status', 'Distribution across planning, active, stalled and completed states.', 'fa-chart-pie', analytics_has_values($chartData['projectStatus']['values'])); ?>
    <?php analytics_chart_card('constituencyUnitsChart', 'Units by Constituency', 'Targeted housing units across Trans-Nzoia constituencies.', 'fa-house-chimney', analytics_has_values($chartData['constituencyUnits']['values'])); ?>
    <?php analytics_chart_card('completionChart', 'Top Project Completion', 'Highest completion percentages across active project records.', 'fa-bars-progress', analytics_has_values($chartData['completion']['values']), true); ?>
  </div>
</section>

<section class="analytics-section">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Money</span>
      <h2>Finance analytics</h2>
    </div>
    <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/financials.php')) ?>">Open Financials</a>
  </div>
  <div class="analytics-chart-grid">
    <?php analytics_chart_card('ipcPipelineChart', 'IPC Pipeline', 'Payment certificates by workflow state.', 'fa-file-invoice', analytics_has_values($chartData['ipcPipeline']['values'])); ?>
    <?php analytics_chart_card('paymentMonthsChart', 'Payments by Month', 'Payment totals inside the selected range.', 'fa-chart-column', analytics_has_values($chartData['paymentMonths']['values'])); ?>
    <?php analytics_chart_card('budgetBurnChart', 'Budget Burn by Project', 'Contract value compared with paid amount.', 'fa-money-check-dollar', analytics_has_values($chartData['budgetBurn']['contract']) || analytics_has_values($chartData['budgetBurn']['paid']), true); ?>
  </div>
  <div class="analytics-mini-grid">
    <?php analytics_metric('Gross IPCs', format_money($finance['ipc_totals']['gross'] ?? 0), 'fa-receipt'); ?>
    <?php analytics_metric('Retention', format_money($finance['ipc_totals']['retention'] ?? 0), 'fa-lock'); ?>
    <?php analytics_metric('BOQ planned', format_money($finance['boq_exposure']['planned'] ?? 0), 'fa-list'); ?>
    <?php analytics_metric('BOQ certified', format_money($finance['boq_exposure']['certified'] ?? 0), 'fa-check-double'); ?>
  </div>
</section>

<section class="analytics-section">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Site control</span>
      <h2>Attendance analytics</h2>
    </div>
    <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/attendance.php')) ?>">Open Attendance</a>
  </div>
  <div class="analytics-chart-grid">
    <?php analytics_chart_card('attendanceDailyChart', 'Daily Sign-ins', 'Total sign-ins and GPS flags by day.', 'fa-calendar-days', analytics_has_values($chartData['attendanceDaily']['total']), true); ?>
    <?php analytics_chart_card('attendanceProjectChart', 'Attendance by Project', 'Projects with the highest sign-in volume.', 'fa-building-user', analytics_has_values($chartData['attendanceProject']['values'])); ?>
    <?php analytics_chart_card('attendanceRoleChart', 'Attendance by Role', 'Role distribution for site sign-ins.', 'fa-users', analytics_has_values($chartData['attendanceRole']['values'])); ?>
  </div>
  <div class="analytics-mini-grid">
    <?php analytics_metric('GPS flags', format_number($attendance['exceptions']['geo_fail'] ?? 0), 'fa-location-dot'); ?>
    <?php analytics_metric('Pending review', format_number($attendance['exceptions']['pending_review'] ?? 0), 'fa-user-clock'); ?>
  </div>
</section>

<section class="analytics-section">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Public response</span>
      <h2>Content analytics</h2>
    </div>
    <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>">Open News</a>
  </div>
  <div class="analytics-chart-grid">
    <?php analytics_chart_card('newsStatusChart', 'News Status', 'Published, draft and scheduled article distribution.', 'fa-newspaper', analytics_has_values($chartData['newsStatus']['values'])); ?>
    <?php analytics_chart_card('subscribersDailyChart', 'Subscriber Growth', 'Newsletter subscriptions inside the selected range.', 'fa-envelope', analytics_has_values($chartData['subscribersDaily']['values'])); ?>
    <?php analytics_chart_card('contactStatusChart', 'Contact Inbox Status', 'Public submissions by response state.', 'fa-inbox', analytics_has_values($chartData['contactStatus']['values'])); ?>
    <?php analytics_chart_card('galleryStatusChart', 'Gallery Status', 'Public gallery media by status.', 'fa-images', analytics_has_values($chartData['galleryStatus']['values'])); ?>
  </div>
  <div class="analytics-mini-grid">
    <?php analytics_metric('CMS visible sections', format_number($content['cms_visibility']['visible'] ?? 0), 'fa-eye'); ?>
    <?php analytics_metric('CMS hidden sections', format_number($content['cms_visibility']['hidden'] ?? 0), 'fa-eye-slash'); ?>
  </div>
</section>

<section class="analytics-bottom-grid">
  <article class="card analytics-risk-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Priority Signals</h2>
        <p class="card__subtitle">Operational risks detected from IPCs, programme tasks, BOQ, attendance and contact data.</p>
      </div>
    </div>
<?php if ($risks === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><strong class="empty-state__title">No priority signals</strong><span class="empty-state__text">The current analytics range has no major operational alerts.</span></div>
<?php else: ?>
    <div class="analytics-signal-list">
<?php foreach ($risks as $risk): ?>
      <a class="analytics-signal analytics-signal--<?= Security::e($risk['severity']) ?>" href="<?= Security::e($risk['href']) ?>">
        <span><i class="fa-solid <?= Security::e($risk['icon']) ?>" aria-hidden="true"></i></span>
        <strong><?= Security::e($risk['title']) ?></strong>
        <small><?= Security::e($risk['text']) ?></small>
      </a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </article>

  <article class="card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Top Viewed News</h2>
        <p class="card__subtitle">Published articles getting the most public attention.</p>
      </div>
    </div>
    <?php analytics_table_news($content['top_news'] ?? []); ?>
  </article>

  <article class="card analytics-bottom-grid__wide">
    <div class="card__header">
      <div>
        <h2 class="card__title">Overdue Projects</h2>
        <p class="card__subtitle">Projects past estimated delivery and not marked complete.</p>
      </div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>">Review</a>
    </div>
    <?php analytics_table_projects($projects['overdue'] ?? []); ?>
  </article>
</section>

<script>
window.AHPTC_ANALYTICS = <?= $chartJson ?: '{}' ?>;
</script>

<?php include dirname(__DIR__, 2) . '/app/partials/admin/shell-end.php'; ?>

<?php
function analytics_status_chart(array $rows, string $labelKey): array
{
    return [
        'labels' => array_map(static fn (array $row): string => status_label((string)($row[$labelKey] ?? 'Unknown')), $rows),
        'values' => array_map(static fn (array $row): int => (int)($row['total'] ?? 0), $rows),
    ];
}

function analytics_has_values(array $values): bool
{
    return array_sum(array_map(static fn ($value): float => (float)$value, $values)) > 0;
}

function analytics_stat(string $icon, string $value, string $label, string $trend): void
{
    ?>
  <article class="stat-widget analytics-stat">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e($value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><span class="stat-widget__trend"><?= Security::e($trend) ?></span></span>
  </article>
    <?php
}

function analytics_chart_card(string $id, string $title, string $subtitle, string $icon, bool $hasData, bool $wide = false): void
{
    ?>
    <article class="card chart-card analytics-chart-card<?= $wide ? ' analytics-chart-card--wide' : '' ?>">
      <div class="chart-card__header">
        <div>
          <h3 class="chart-card__title"><?= Security::e($title) ?></h3>
          <p class="chart-card__subtitle"><?= Security::e($subtitle) ?></p>
        </div>
      </div>
      <div class="chart-container<?= !$hasData ? ' is-empty' : '' ?>">
        <canvas id="<?= Security::e($id) ?>"></canvas>
<?php if (!$hasData): ?>
        <div class="sa-chart-empty"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i><strong>No data yet</strong><span>This chart will populate as records are added.</span></div>
<?php endif; ?>
      </div>
    </article>
    <?php
}

function analytics_metric(string $label, string $value, string $icon): void
{
    ?>
  <article class="analytics-mini-card">
    <span><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <strong><?= Security::e($value) ?></strong>
    <small><?= Security::e($label) ?></small>
  </article>
    <?php
}

function analytics_table_news(array $rows): void
{
    if ($rows === []) {
        echo '<div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span><strong class="empty-state__title">No published views yet</strong><span class="empty-state__text">Published news article views will appear here.</span></div>';
        return;
    }
    ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Article</th><th>Views</th><th>Published</th><th>Action</th></tr></thead>
        <tbody>
<?php foreach ($rows as $row): ?>
          <tr>
            <td><?= Security::e(safe_truncate((string)$row['title'], 58)) ?></td>
            <td><?= Security::e(format_number($row['views'])) ?></td>
            <td><?= Security::e(format_date($row['published_at'] ?? null)) ?></td>
            <td><a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/news-editor.php?id=' . (int)$row['id'])) ?>">Open</a></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}

function analytics_table_projects(array $rows): void
{
    if ($rows === []) {
        echo '<div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><strong class="empty-state__title">No overdue projects</strong><span class="empty-state__text">Delivery dates are clear for non-completed projects.</span></div>';
        return;
    }
    ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Delivery</th><th>Action</th></tr></thead>
        <tbody>
<?php foreach ($rows as $row): ?>
          <tr>
            <td><?= Security::e($row['name']) ?></td>
            <td><span class="badge <?= Security::e(status_badge_class($row['status'])) ?>"><?= Security::e(status_label($row['status'])) ?></span></td>
            <td><?= Security::e(format_percentage($row['pct_complete'])) ?></td>
            <td><?= Security::e(format_date($row['est_delivery'])) ?></td>
            <td><a class="btn btn--primary btn--sm" href="<?= Security::e(Url::to('admin/superadmin/project-edit.php?id=' . (int)$row['id'])) ?>">Review</a></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}
