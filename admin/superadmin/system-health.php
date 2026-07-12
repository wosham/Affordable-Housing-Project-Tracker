<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$snapshot = SystemHealth::snapshot(false);
$recentSnapshots = SystemHealth::recentSnapshots(8);
$checks = $snapshot['checks'];
$counts = $snapshot['counts'];

$pageTitle = 'System Health';
$pageDescription = 'Monitor database, migrations, storage, logs, security, PHP extensions and stalled workflows.';
$adminRole = 'superadmin';
$contentClass = 'sa-health-page';
$componentCss = ['system-health'];
$pageScripts = ['system-health'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'System Health'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="health-hero card health-hero--<?= Security::e($snapshot['status']) ?>">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i> System diagnostics</span>
    <h2>System health</h2>
    <p>Live checks for database integrity, migrations, storage permissions, logs, security posture, PHP runtime and stalled workflows.</p>
  </div>
  <div class="health-score" aria-label="Health score">
    <strong><?= Security::e(format_number($snapshot['score'])) ?></strong>
    <span><?= Security::e(status_label($snapshot['status'])) ?></span>
    <small><?= Security::e($snapshot['duration_ms']) ?>ms</small>
  </div>
  <div class="health-hero__actions">
    <button class="btn btn--primary" type="button" data-health-refresh><i class="fa-solid fa-rotate" aria-hidden="true"></i> Refresh</button>
    <button class="btn btn--outline" type="button" data-health-copy><i class="fa-solid fa-copy" aria-hidden="true"></i> Copy Summary</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/audit-log.php?quick=critical')) ?>"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Audit</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 health-stats" aria-label="Health summary">
  <?php health_stat('fa-list-check', $counts['checks'] ?? 0, 'Checks', 'Total diagnostics'); ?>
  <?php health_stat('fa-circle-check', $counts['healthy'] ?? 0, 'Healthy', 'Passing checks'); ?>
  <?php health_stat('fa-triangle-exclamation', $counts['warning'] ?? 0, 'Warnings', 'Needs review'); ?>
  <?php health_stat('fa-circle-xmark', $counts['critical'] ?? 0, 'Critical', 'Fix first'); ?>
</section>

<?php if (($snapshot['recommendations'] ?? []) !== []): ?>
<section class="card health-recommendations">
  <div class="card__header"><div><h2 class="card__title">Recommended Actions</h2><p class="card__subtitle">Highest-priority issues detected by the health engine.</p></div><span class="badge badge--warning"><?= Security::e(format_number(count($snapshot['recommendations']))) ?> actions</span></div>
  <div class="health-recommendation-list">
<?php foreach ($snapshot['recommendations'] as $recommendation): ?>
    <article class="health-recommendation health-recommendation--<?= Security::e($recommendation['status']) ?>">
      <span><i class="fa-solid <?= $recommendation['status'] === 'critical' ? 'fa-circle-xmark' : 'fa-triangle-exclamation' ?>" aria-hidden="true"></i></span>
      <div><strong><?= Security::e($recommendation['title']) ?></strong><small><?= Security::e(status_label($recommendation['group']) . ' Â· ' . $recommendation['message']) ?></small></div>
    </article>
<?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="health-grid">
  <?php health_panel('database', 'Database Health', 'fa-database', $checks['database']); ?>
  <?php health_panel('migrations', 'Migration Health', 'fa-code-branch', $checks['migrations']); ?>
  <?php health_panel('security', 'Security Health', 'fa-shield-halved', $checks['security']); ?>
  <?php health_panel('php', 'PHP Runtime', 'fa-code', $checks['php']); ?>
</section>

<section class="card health-storage">
  <div class="card__header"><div><h2 class="card__title"><i class="fa-solid fa-folder-open" aria-hidden="true"></i> Storage Health</h2><p class="card__subtitle">Directory existence, permissions, file counts and size.</p></div></div>
  <div class="table-wrap">
    <table class="data-table health-table">
      <thead><tr><th>Directory</th><th>Status</th><th>Permissions</th><th>Files</th><th>Size</th><th>Modified</th></tr></thead>
      <tbody>
<?php foreach (($checks['storage']['meta']['directories'] ?? []) as $dir): ?>
        <tr><td><strong><?= Security::e($dir['label']) ?></strong><small><?= Security::e($dir['path']) ?></small></td><td><span class="badge <?= Security::e(health_badge($dir['status'])) ?>"><?= Security::e(status_label($dir['status'])) ?></span></td><td><?= $dir['exists'] ? ($dir['readable'] ? 'Readable' : 'Not readable') . ' Â· ' . ($dir['writable'] ? 'Writable' : 'Not writable') : 'Missing' ?></td><td><?= Security::e(format_number($dir['stats']['files'] ?? 0)) ?></td><td><?= Security::e(SystemHealth::formatBytes($dir['stats']['size'] ?? 0)) ?></td><td><?= Security::e(format_datetime($dir['stats']['modified_at'] ?? null)) ?></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="health-grid health-grid--wide">
  <section class="card health-workflows">
    <div class="card__header"><div><h2 class="card__title"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Workflow Health</h2><p class="card__subtitle">Operational queues that can stall delivery.</p></div></div>
    <div class="health-check-list">
      <?php foreach (($checks['workflows']['checks'] ?? []) as $check): ?>
        <?php health_check_row($check); ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="card health-logs">
    <div class="card__header"><div><h2 class="card__title"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Logs</h2><p class="card__subtitle">Recent application and error log lines.</p></div><div class="health-log-tabs"><button class="is-active" type="button" data-log-file="error.log">Error</button><button type="button" data-log-file="app.log">App</button></div></div>
    <pre class="health-log-tail" data-log-tail><?php foreach ((SystemHealth::logTail('error.log', 20)['lines'] ?? []) as $line): ?><?= Security::e($line) . "\n" ?><?php endforeach; ?></pre>
  </section>
</section>

<section class="card health-db-meta">
  <div class="card__header"><div><h2 class="card__title">Database Snapshot</h2><p class="card__subtitle">Core table counts and migration state.</p></div><span class="badge badge--info"><?= Security::e($checks['database']['meta']['database'] ?? '-') ?></span></div>
  <div class="health-meta-grid">
    <div><span>Version</span><strong><?= Security::e($checks['database']['meta']['version'] ?? '-') ?></strong></div>
    <div><span>Tables</span><strong><?= Security::e(format_number($checks['database']['meta']['tables'] ?? 0)) ?></strong></div>
    <div><span>Migration files</span><strong><?= Security::e(format_number($checks['migrations']['meta']['files'] ?? 0)) ?></strong></div>
    <div><span>Migrations run</span><strong><?= Security::e(format_number($checks['migrations']['meta']['ran'] ?? 0)) ?></strong></div>
  </div>
  <div class="health-row-counts">
<?php foreach (($checks['database']['meta']['rows'] ?? []) as $table => $count): ?>
    <span><strong><?= Security::e(format_number($count)) ?></strong><?= Security::e($table) ?></span>
<?php endforeach; ?>
  </div>
</section>

<section class="card health-history">
  <div class="card__header"><div><h2 class="card__title">Recent Snapshots</h2><p class="card__subtitle">Saved checks from manual refresh actions.</p></div></div>
<?php if ($recentSnapshots === []): ?>
  <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><strong class="empty-state__title">No saved health snapshots yet</strong><span class="empty-state__text">Use Refresh to save the first snapshot.</span></div>
<?php else: ?>
  <div class="health-snapshot-list">
<?php foreach ($recentSnapshots as $row): ?>
    <article><span class="badge <?= Security::e(health_badge($row['status'])) ?>"><?= Security::e(status_label($row['status'])) ?></span><strong><?= Security::e(format_number($row['score'])) ?></strong><small><?= Security::e(format_datetime($row['created_at'])) ?> by <?= Security::e(trim((string)$row['created_by_name']) ?: 'System') ?></small></article>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>

<script type="application/json" id="healthSummaryJson"><?= json_encode([
    'status' => $snapshot['status'],
    'score' => $snapshot['score'],
    'checked_at' => $snapshot['checked_at'],
    'counts' => $snapshot['counts'],
    'recommendations' => $snapshot['recommendations'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function health_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget"><span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number((float)$value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span></article>
<?php
}

function health_panel(string $key, string $title, string $icon, array $payload): void
{
?>
  <section class="card health-panel health-panel--<?= Security::e($key) ?>">
    <div class="card__header"><div><h2 class="card__title"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i> <?= Security::e($title) ?></h2><p class="card__subtitle"><?= Security::e(format_number(count($payload['checks'] ?? []))) ?> checks</p></div></div>
    <div class="health-check-list">
<?php foreach (($payload['checks'] ?? []) as $check): ?>
      <?php health_check_row($check); ?>
<?php endforeach; ?>
    </div>
  </section>
<?php
}

function health_check_row(array $check): void
{
    $link = $check['meta']['link'] ?? '';
?>
  <article class="health-check health-check--<?= Security::e($check['status']) ?>">
    <span><i class="fa-solid <?= Security::e($check['icon']) ?>" aria-hidden="true"></i></span>
    <div><strong><?= Security::e($check['label']) ?></strong><small><?= Security::e($check['message']) ?></small></div>
<?php if ($link): ?>
    <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to($link)) ?>"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
<?php endif; ?>
  </article>
<?php
}

function health_badge(string $status): string
{
    return match ($status) {
        'healthy' => 'badge--success',
        'critical' => 'badge--danger',
        'warning' => 'badge--warning',
        default => 'badge--neutral',
    };
}
