<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');

$summary = ConsultantDashboard::summary($userId, $role);
$projects = ConsultantDashboard::projects($userId, $role, 10);
$ipcs = ConsultantDashboard::ipcQueue($userId, $role, 6);
$programme = ConsultantDashboard::programmeSnapshot($userId, $role);
$technicalQueue = ConsultantDashboard::technicalQueue($userId, $role, 8);
$qualityAlerts = ConsultantDashboard::qualityAlerts($userId, $role, 8);
$documents = ConsultantDashboard::recentDocuments($userId, $role, 6);
$portalAnnouncements = Announcement::activeForRole($role, 5, $userId);
$hasAssignments = (int)($summary['assigned_projects'] ?? 0) > 0;

$pageTitle = 'Consultant Dashboard';
$pageDescription = 'Technical oversight for assigned projects, IPCs, BOQ, programme and quality reviews.';
$adminRole = 'consultant';
$contentClass = 'consultant-dashboard-page';
$componentCss = [];
$pageScripts = [];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant'],
    ['label' => 'Dashboard'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<?php if (!$hasAssignments): ?>
  <section class="alert alert--warning">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    <div>
      <strong>No projects assigned to you yet</strong>
      <span>Ask the County Director or Project Manager to assign you as consultant (or project assignment) so IPC, BOQ and programme work can appear here.</span>
    </div>
  </section>
<?php endif; ?>

<section class="consultant-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Consultant overview</span>
    <h2>Welcome, <?= Security::e(current_user_name()) ?></h2>
    <p><?= Security::e(date('l, d F Y')) ?>. Certify verified IPCs, review BOQ risk items, and track technical and quality queues for your portfolio.</p>
  </div>
  <div class="consultant-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php?status=clerk-endorsed')) ?>"><i class="fa-solid fa-stamp" aria-hidden="true"></i> Ready to certify</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/boq-review.php')) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i> BOQ Review</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/consultant/messages.php')) ?>"><i class="fa-solid fa-comments" aria-hidden="true"></i> Messages</a>
  </div>
</section>

<?php include __DIR__ . '/../../app/partials/admin/dashboard-announcements.php'; ?>

<section class="consultant-stat-grid consultant-stat-grid--primary" aria-label="Primary consultant metrics">
  <?php consultant_stat('fa-building-circle-check', $summary['assigned_projects'], 'Assigned Projects', 'Your technical portfolio', 'admin/consultant/documents.php'); ?>
  <?php consultant_stat('fa-file-signature', $summary['certification_queue'], 'IPC To Certify', 'Clerk-endorsed claims', 'admin/consultant/ipc-inbox.php?status=clerk-endorsed'); ?>
  <?php consultant_stat('fa-list-check', $summary['boq_risks'], 'BOQ Risk Items', 'Watch / high / critical', 'admin/consultant/boq-review.php'); ?>
  <?php consultant_stat('fa-compass-drafting', $summary['technical_pending'], 'Technical Queue', 'Submissions waiting', 'admin/consultant/material-approvals.php'); ?>
</section>

<details class="consultant-stats-more card">
  <summary>More metrics</summary>
  <section class="consultant-stat-grid consultant-stat-grid--secondary" aria-label="Secondary consultant metrics">
    <?php consultant_stat('fa-clock', $summary['awaiting_verification'], 'Awaiting Site Verify', 'Submitted IPCs', 'admin/consultant/ipc-inbox.php?status=submitted'); ?>
    <?php consultant_stat('fa-circle-check', $summary['certified_waiting_manager'], 'Certified (pipeline)', 'With manager/finance', 'admin/consultant/ipc-inbox.php?status=certified'); ?>
    <?php consultant_stat('fa-vial-circle-check', $summary['quality_alerts'], 'Quality Alerts', 'Open tests & defects', 'admin/consultant/quality-register.php'); ?>
    <?php consultant_stat('fa-bars-progress', $summary['programme_overdue'], 'Overdue Tasks', 'Programme attention', 'admin/consultant/programme-review.php'); ?>
    <?php consultant_stat('fa-bell', $summary['unread_notifications'], 'Unread Alerts', 'Notifications', 'admin/consultant/messages.php'); ?>
    <?php consultant_stat('fa-comments', $summary['unread_messages'], 'Unread Messages', 'Team conversations', 'admin/consultant/messages.php'); ?>
  </section>
</details>

<section class="consultant-grid consultant-grid--main">
  <article class="card consultant-projects-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Assigned Projects</h2>
        <p class="card__subtitle">Progress, contractor and open signals — use Actions to jump into work.</p>
      </div>
      <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/consultant/documents.php')) ?>">Documents</a>
    </div>
    <div class="table-wrap">
      <table class="data-table consultant-project-table">
        <thead>
          <tr>
            <th>Project</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Next Milestone</th>
            <th>Signals</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
<?php if ($projects === []): ?>
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <strong class="empty-state__title">No assigned projects yet</strong>
                <span class="empty-state__text">Projects appear when you are named as consultant or given an active project assignment.</span>
              </div>
            </td>
          </tr>
<?php else: foreach ($projects as $project): ?>
<?php $pid = (int)$project['id']; ?>
          <tr>
            <td>
              <strong><?= Security::e($project['name']) ?></strong>
              <small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?><?= trim((string)($project['contractor_name'] ?? '')) !== '' ? ' · ' . Security::e($project['contractor_name']) : '' ?></small>
            </td>
            <td><span class="badge <?= Security::e(status_badge_class($project['status'])) ?>"><?= Security::e(status_label($project['status'])) ?></span></td>
            <td>
              <div class="consultant-progress"><span style="width: <?= percentage($project['pct_complete'] ?? 0) ?>%"></span></div>
              <small><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</small>
            </td>
            <td>
              <strong><?= Security::e($project['next_milestone'] ?: ($project['current_milestone'] ?: 'Not set')) ?></strong>
              <small><?= Security::e(format_date($project['next_milestone_date'] ?? null)) ?></small>
            </td>
            <td class="consultant-signals">
              <span class="consultant-signal" title="IPCs ready or in queue"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i><?= Security::e(format_number($project['certification_queue'] ?? 0)) ?></span>
              <span class="consultant-signal" title="Quality items"><i class="fa-solid fa-vial" aria-hidden="true"></i><?= Security::e(format_number($project['open_defects'] ?? 0)) ?></span>
              <span class="consultant-signal" title="Overdue programme tasks"><i class="fa-solid fa-clock" aria-hidden="true"></i><?= Security::e(format_number($project['overdue_tasks'] ?? 0)) ?></span>
            </td>
            <td class="consultant-row-actions">
              <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php?project_id=' . $pid)) ?>" title="Project IPCs" aria-label="Project IPCs"><i class="fa-solid fa-inbox" aria-hidden="true"></i></a>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/consultant/boq-review.php?project_id=' . $pid)) ?>" title="BOQ review" aria-label="BOQ review"><i class="fa-solid fa-list-check" aria-hidden="true"></i></a>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/consultant/documents.php?project_id=' . $pid)) ?>" title="Documents" aria-label="Documents"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></a>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/consultant/programme-review.php?project_id=' . $pid)) ?>" title="Programme" aria-label="Programme"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i></a>
            </td>
          </tr>
<?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <aside class="consultant-side-stack">
    <article class="card consultant-programme-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">Programme snapshot</h2>
          <p class="card__subtitle">Task health across assigned projects.</p>
        </div>
        <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/consultant/programme-review.php')) ?>">Open</a>
      </div>
      <div class="consultant-programme-ring">
        <strong><?= (int)$programme['avg_progress'] ?>%</strong>
        <span>average task progress</span>
      </div>
      <div class="consultant-mini-grid">
        <span><strong><?= Security::e(format_number($programme['total'])) ?></strong>Total</span>
        <span><strong><?= Security::e(format_number($programme['critical'])) ?></strong>Critical</span>
        <span><strong><?= Security::e(format_number($programme['completed'])) ?></strong>Completed</span>
        <span><strong><?= Security::e(format_number($programme['overdue'])) ?></strong>Overdue</span>
      </div>
    </article>

    <article class="card consultant-quick-card">
      <div class="card__header"><div><h2 class="card__title">Quick actions</h2><p class="card__subtitle">Common consultant workflows.</p></div></div>
      <div class="consultant-action-list">
        <a href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php?status=clerk-endorsed')) ?>"><i class="fa-solid fa-stamp"></i> Certify ready IPCs</a>
        <a href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php')) ?>"><i class="fa-solid fa-inbox"></i> Full IPC inbox</a>
        <a href="<?= Security::e(Url::to('admin/consultant/boq-review.php')) ?>"><i class="fa-solid fa-list-check"></i> BOQ review</a>
        <a href="<?= Security::e(Url::to('admin/consultant/shop-drawings.php')) ?>"><i class="fa-solid fa-compass-drafting"></i> Shop drawings</a>
        <a href="<?= Security::e(Url::to('admin/consultant/quality-register.php')) ?>"><i class="fa-solid fa-vial-circle-check"></i> Quality register</a>
        <a href="<?= Security::e(Url::to('admin/consultant/messages.php')) ?>"><i class="fa-solid fa-comments"></i> Message team</a>
      </div>
    </article>
  </aside>
</section>

<section class="consultant-grid consultant-grid--three">
  <article class="card">
    <div class="card__header">
      <div><h2 class="card__title">IPC certification</h2><p class="card__subtitle">Claims in your portfolio.</p></div>
      <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php')) ?>">Inbox</a>
    </div>
    <div class="consultant-list">
<?php if ($ipcs === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No IPCs in queue</strong><span class="empty-state__text">Submitted and ready-to-certify claims will list here.</span></div>
<?php else: foreach ($ipcs as $ipc): ?>
      <a class="consultant-list-item" href="<?= Security::e(Url::to('admin/consultant/ipc-certify.php?id=' . (int)$ipc['id'])) ?>">
        <span>
          <strong><?= Security::e($ipc['project_name']) ?></strong>
          <small>IPC #<?= Security::e(format_number($ipc['ipc_number'])) ?> · <?= Security::e(trim($ipc['contractor_name']) ?: 'Contractor') ?></small>
        </span>
        <span>
          <em><?= Security::e(format_money($ipc['net_amount'] ?? 0)) ?></em>
          <small><?= Security::e(status_label($ipc['status'])) ?></small>
        </span>
      </a>
<?php endforeach; endif; ?>
    </div>
  </article>

  <article class="card">
    <div class="card__header">
      <div><h2 class="card__title">Technical queue</h2><p class="card__subtitle">Submissions waiting for technical action.</p></div>
    </div>
    <div class="consultant-list">
<?php if ($technicalQueue === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No technical items</strong></div>
<?php else: foreach ($technicalQueue as $item): ?>
      <a class="consultant-list-item" href="<?= Security::e(Url::to($item['link'])) ?>">
        <span><strong><?= Security::e(safe_truncate($item['title'] ?? '', 64)) ?></strong><small><?= Security::e($item['item_type']) ?> / <?= Security::e($item['project_name']) ?></small></span>
        <span><em><?= Security::e(status_label($item['status'] ?? 'pending')) ?></em><small><?= Security::e(format_date($item['due_date'] ?? null)) ?></small></span>
      </a>
<?php endforeach; endif; ?>
    </div>
  </article>

  <article class="card">
    <div class="card__header">
      <div><h2 class="card__title">Quality alerts</h2><p class="card__subtitle">Defects, tests, inspections and NCRs.</p></div>
      <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/consultant/quality-register.php')) ?>">Register</a>
    </div>
    <div class="consultant-list">
<?php if ($qualityAlerts === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No quality alerts</strong></div>
<?php else: foreach ($qualityAlerts as $alert): ?>
      <a class="consultant-list-item" href="<?= Security::e(Url::to($alert['link'])) ?>">
        <span><strong><?= Security::e(safe_truncate($alert['title'] ?? '', 64)) ?></strong><small><?= Security::e($alert['item_type']) ?> / <?= Security::e($alert['project_name']) ?></small></span>
        <span><em><?= Security::e(status_label($alert['status'] ?? 'open')) ?></em><small><?= Security::e(format_date($alert['item_date'] ?? null)) ?></small></span>
      </a>
<?php endforeach; endif; ?>
    </div>
  </article>
</section>

<section class="card consultant-documents-card">
  <div class="card__header">
    <div><h2 class="card__title">Recent documents</h2><p class="card__subtitle">Latest project documents available for review.</p></div>
    <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/consultant/documents.php')) ?>">View all</a>
  </div>
  <div class="consultant-document-grid">
<?php if ($documents === []): ?>
    <div class="empty-state empty-state--compact"><strong class="empty-state__title">No documents yet</strong><span class="empty-state__text">Project documents will appear here when uploaded.</span></div>
<?php else: foreach ($documents as $document): ?>
    <article class="consultant-document">
      <span class="consultant-document__icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>
      <span>
        <strong><?= Security::e(safe_truncate($document['original_name'] ?? '', 72)) ?></strong>
        <small><?= Security::e(status_label($document['category'] ?? 'document')) ?> / <?= Security::e($document['project_name'] ?? '') ?> / v<?= Security::e($document['version'] ?? '1.0') ?></small>
      </span>
      <em><?= Security::e(time_ago($document['created_at'] ?? null)) ?></em>
    </article>
<?php endforeach; endif; ?>
  </div>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function consultant_stat(string $icon, mixed $value, string $label, string $hint, string $path): void
{
?>
  <a class="consultant-stat card" href="<?= Security::e(Url::to($path)) ?>">
    <span class="consultant-stat__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="consultant-stat__copy">
      <strong><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong>
      <span><?= Security::e($label) ?></span>
      <small><?= Security::e($hint) ?></small>
    </span>
  </a>
<?php
}
