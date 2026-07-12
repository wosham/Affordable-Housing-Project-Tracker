<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'Backups & Recovery';
$pageDescription = 'Manage database snapshots and recovery points for the Affordable Housing Tracker.';
$adminRole = 'superadmin';
$contentClass = 'sa-backups-page';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Backups & Recovery'],
];

$summary = DatabaseBackup::summary();
$backups = DatabaseBackup::recent(50);
$isRunning = DatabaseBackup::running();

// We need an anti-CSRF token for the manual backup creation
$csrfToken = Csrf::token('backup');

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card">
  <div class="card__header">
    <div>
      <span class="sa-panel-label"><i class="fa-solid fa-database" aria-hidden="true"></i> System Protection</span>
      <h2 class="card__title">Database Backups & Recovery</h2>
      <p class="card__subtitle">Secure, timestamped snapshots of all system data. Backups are stored safely outside the public web root.</p>
    </div>
    <div class="card__actions">
        <?php if ($isRunning): ?>
            <button class="btn btn--primary" disabled><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Backup Running...</button>
        <?php else: ?>
            <button type="button" class="btn btn--primary" id="btn-create-backup"><i class="fa-solid fa-download" aria-hidden="true"></i> Create Backup Now</button>
        <?php endif; ?>
    </div>
  </div>
</section>

<section class="stat-grid stat-grid--4" aria-label="Backup summary">
  <article class="stat-widget stat-widget--success">
    <span class="stat-widget__icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
        <strong class="stat-widget__value"><?= Security::e(format_number((float)($summary['completed'] ?? 0))) ?></strong>
        <span class="stat-widget__label">Successful Backups</span>
        <span class="stat-widget__trend">Stored locally</span>
    </span>
  </article>
  
  <article class="stat-widget stat-widget--danger">
    <span class="stat-widget__icon"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
        <strong class="stat-widget__value"><?= Security::e(format_number((float)($summary['failed'] ?? 0))) ?></strong>
        <span class="stat-widget__label">Failed Attempts</span>
        <span class="stat-widget__trend">Check audit logs</span>
    </span>
  </article>
  
  <article class="stat-widget stat-widget--info">
    <span class="stat-widget__icon"><i class="fa-solid fa-hard-drive" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
        <strong class="stat-widget__value"><?= Security::e(format_bytes((float)($summary['size_bytes'] ?? 0))) ?></strong>
        <span class="stat-widget__label">Storage Used</span>
        <span class="stat-widget__trend">Retention: 14 days</span>
    </span>
  </article>
  
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
        <strong class="stat-widget__value" style="font-size: 1.1rem; padding-top: 0.2rem;"><?= Security::e(($summary['last_completed_at'] ?? null) ? time_ago($summary['last_completed_at']) : 'Never') ?></strong>
        <span class="stat-widget__label">Last Successful Backup</span>
        <span class="stat-widget__trend"><?= Security::e(($summary['last_completed_at'] ?? null) ? format_datetime($summary['last_completed_at']) : '-') ?></span>
    </span>
  </article>
</section>

<section class="card" style="margin-top: 2rem;">
  <div class="card__header">
    <div>
      <h2 class="card__title">Backup History</h2>
      <p class="card__subtitle">Recent snapshots available for secure download and restoration.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
            <th>Date / Time</th>
            <th>Filename</th>
            <th>Size</th>
            <th>Status / Origin</th>
            <th>Integrity Checksum</th>
            <th>Action</th>
        </tr>
      </thead>
      <tbody>
<?php if ($backups === []): ?>
        <tr><td colspan="6"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span><strong class="empty-state__title">No backups found</strong><span class="empty-state__text">Generate a manual backup to secure your data.</span></div></td></tr>
<?php else: ?>
<?php foreach ($backups as $backup): ?>
        <tr>
          <td><strong><?= Security::e(format_datetime($backup['created_at'])) ?></strong><small><?= Security::e(time_ago($backup['created_at'])) ?></small></td>
          <td><code><?= Security::e($backup['filename']) ?></code></td>
          <td><?= $backup['size_bytes'] > 0 ? Security::e(format_bytes((float)$backup['size_bytes'])) : '-' ?></td>
          <td>
            <span class="badge <?= $backup['status'] === 'completed' ? 'badge--success' : ($backup['status'] === 'failed' ? 'badge--danger' : ($backup['status'] === 'running' ? 'badge--warning' : 'badge--info')) ?>">
                <?= Security::e(ucfirst($backup['status'])) ?>
            </span>
            <small><?= Security::e(ucfirst($backup['origin'])) ?></small>
          </td>
          <td>
            <?php if ($backup['checksum_sha256']): ?>
                <code title="<?= Security::e($backup['checksum_sha256']) ?>" style="font-size: 0.75rem; color: var(--color-gray-500);"><?= Security::e(substr($backup['checksum_sha256'], 0, 16)) ?>...</code>
            <?php else: ?>
                -
            <?php endif; ?>
          </td>
          <td class="table-actions">
            <?php if ($backup['status'] === 'completed'): ?>
                <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('api/superadmin/backup-download.php?id=' . (int)$backup['id'] . '&token=' . $csrfToken)) ?>"><i class="fa-solid fa-download" aria-hidden="true"></i> Download</a>
            <?php elseif ($backup['status'] === 'failed' && $backup['error_message']): ?>
                <button type="button" class="btn btn--sm btn--outline" onclick="alert(<?= htmlspecialchars(json_encode((string)$backup['error_message'])) ?>)"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Error</button>
            <?php endif; ?>
            <button type="button" class="btn btn--sm btn--outline" onclick="deleteBackup(<?= (int)$backup['id'] ?>)" title="Delete Backup"><i class="fa-solid fa-trash" aria-hidden="true" style="color: var(--color-danger);"></i></button>
          </td>
        </tr>
<?php endforeach; ?>
<?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="alert alert--info" style="margin-top: 2rem;">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    <div class="alert__content">
        <strong>Important Restore Protocol</strong>
        <p>To prevent accidental data destruction, this portal does not support one-click restoration. Restoring a database overwrites all IPCs, attendance logs, and project progress. To restore, download a verified backup file and use a secure MySQL administration tool.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnCreate = document.getElementById('btn-create-backup');
    if (!btnCreate) return;

    btnCreate.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to create a database backup now? This may take a moment and will lock tables briefly.')) {
            return;
        }

        btnCreate.disabled = true;
        btnCreate.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';

        try {
            const formData = new FormData();
            formData.append('csrf_token', <?= json_encode($csrfToken) ?>);

            const response = await fetch('<?= Url::to('api/superadmin/backup-create.php') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert('Backup failed: ' + (result.error || 'Unknown error'));
                btnCreate.disabled = false;
                btnCreate.innerHTML = '<i class="fa-solid fa-download"></i> Create Backup Now';
            }
        } catch (err) {
            alert('A network error occurred. Check the audit logs.');
            btnCreate.disabled = false;
            btnCreate.innerHTML = '<i class="fa-solid fa-download"></i> Create Backup Now';
        }
    });
});

async function deleteBackup(id) {
    if (!confirm('Are you sure you want to permanently delete this backup file? This cannot be undone.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', <?= json_encode($csrfToken) ?>);
        formData.append('id', id);

        const response = await fetch('<?= Url::to('api/superadmin/backup-delete.php') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });

        const result = await response.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert('Delete failed: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        alert('A network error occurred.');
    }
}
</script>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
