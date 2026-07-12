<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$userId = (int)Auth::id();
$projectId = InternProjectWork::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$projects = InternProjectWork::projects($userId);
$project = InternProjectWork::projectOverview($userId, $projectId);
$summary = $projectId > 0 ? InternProjectWork::summary($userId, $projectId) : [];
$tasks = $projectId > 0 ? InternProjectWork::currentTasks($projectId) : [];
$documents = $projectId > 0 ? InternProjectWork::documents($projectId) : [];
$contacts = $projectId > 0 ? InternProjectWork::contacts($projectId) : [];
$activity = $projectId > 0 ? InternProjectWork::recentActivity($userId, $projectId) : [];

$pageTitle = 'My Project';
$pageDescription = 'Assigned project overview, contacts, documents and recent site activity.';
$adminRole = 'intern';
$csrfForm = 'intern_work';
$contentClass = 'intern-page intern-work-page';
$componentCss = ['intern-work'];
$pageScripts = ['intern-work'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern', 'url' => Url::to('admin/intern/dashboard.php')],
    ['label' => 'My Project'],
];

$activeInternHub = 'project';
include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<?php include __DIR__ . '/../../app/partials/admin/intern-hub.php'; ?>

<section class="intern-work-hero">
  <div>
    <span class="intern-work-label"><i class="fa-solid fa-building" aria-hidden="true"></i> Assigned project</span>
    <h1><?= Security::e($project['name'] ?? 'No assigned project') ?></h1>
    <p><?= Security::e($project['location_label'] ?? 'Your assigned project details will appear here.') ?></p>
  </div>
  <div class="intern-work-actions">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/intern/site-data-entry.php?project_id=' . $projectId)) ?>"><i class="fa-solid fa-pen-to-square"></i> Add Site Note</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/upload-photos.php?project_id=' . $projectId)) ?>"><i class="fa-solid fa-camera"></i> Upload Photo</a>
  </div>
</section>

<?php if (count($projects) > 1): ?>
<p class="intern-work-notice card">Policy is one site per intern. You appear on multiple sites — contact Superadmin to correct your assignment. Showing your primary site below.</p>
<?php endif; ?>

<section class="intern-work-stat-grid">
  <?php intern_work_stat('fa-chart-line', $project['progress_label'] ?? '0%', 'Progress', 'Current completion'); ?>
  <?php intern_work_stat('fa-list-check', $summary['tasks_total'] ?? 0, 'Tasks', ($summary['tasks_delayed'] ?? 0) . ' delayed'); ?>
  <?php intern_work_stat('fa-note-sticky', $summary['entries_total'] ?? 0, 'Site Notes', ($summary['entries_today'] ?? 0) . ' today'); ?>
  <?php intern_work_stat('fa-images', $summary['photos_total'] ?? 0, 'Photos', ($summary['photos_today'] ?? 0) . ' today'); ?>
</section>

<section class="intern-work-grid">
  <div class="intern-work-main">
    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Project Summary</h2><p>Key details for your assigned site.</p></div></div>
      <?php if ($project === []): ?>
        <div class="intern-work-empty">No active assigned project was found.</div>
      <?php else: ?>
      <div class="intern-work-detail-grid">
        <div><span>Status</span><strong><?= Security::e(status_label($project['status'] ?? 'active')) ?></strong></div>
        <div><span>Contractor</span><strong><?= Security::e($project['contractor_label'] ?: '-') ?></strong></div>
        <div><span>Consultant</span><strong><?= Security::e($project['consultant_label'] ?: '-') ?></strong></div>
        <div><span>Delivery</span><strong><?= Security::e(format_date($project['est_delivery'] ?? null)) ?></strong></div>
        <div><span>Milestone</span><strong><?= Security::e($project['current_milestone'] ?: '-') ?></strong></div>
        <div><span>Units</span><strong><?= Security::e(isset($project['units']) ? format_number((int)$project['units']) : '-') ?></strong></div>
      </div>
      <?php endif; ?>
    </section>

    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Current Programme</h2><p>Active and upcoming project tasks.</p></div><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/site-data-entry.php?project_id=' . $projectId)) ?>">Add note</a></div>
      <div class="intern-work-list">
        <?php if ($tasks === []): ?><div class="intern-work-empty">No programme tasks found.</div><?php endif; ?>
        <?php foreach ($tasks as $task): ?>
          <article class="intern-work-row">
            <span class="intern-work-row__icon"><i class="fa-solid fa-list-check"></i></span>
            <div><strong><?= Security::e($task['task_name']) ?></strong><small><?= Security::e(status_label($task['status'] ?? 'pending')) ?> / <?= Security::e(format_date($task['due_date'] ?? null)) ?></small></div>
            <b><?= Security::e(percentage((float)($task['pct_complete'] ?? 0)) . '%') ?></b>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Recent Activity</h2><p>Your latest site notes and photos.</p></div></div>
      <div class="intern-work-list">
        <?php if ($activity === []): ?><div class="intern-work-empty">No intern activity has been recorded yet.</div><?php endif; ?>
        <?php foreach ($activity as $item): ?>
          <article class="intern-work-row">
            <span class="intern-work-row__icon"><i class="fa-solid fa-circle-info"></i></span>
            <div><strong><?= Security::e($item['title']) ?></strong><small><?= Security::e($item['type'] . ' / ' . $item['meta']) ?></small></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <aside class="intern-work-side">
    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Project Contacts</h2><p>People linked to this site.</p></div></div>
      <div class="intern-work-list">
        <?php if ($contacts === []): ?><div class="intern-work-empty">No project contacts found.</div><?php endif; ?>
        <?php foreach ($contacts as $contact): ?>
          <article class="intern-contact-row">
            <span><?= Security::e(user_initials(trim($contact['first_name'] . ' ' . $contact['last_name']))) ?></span>
            <div><strong><?= Security::e(trim($contact['first_name'] . ' ' . $contact['last_name'])) ?></strong><small><?= Security::e($contact['job_title'] ?: $contact['role_name']) ?></small></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Documents</h2><p>Latest visible project files.</p></div></div>
      <div class="intern-work-list">
        <?php if ($documents === []): ?><div class="intern-work-empty">No documents found.</div><?php endif; ?>
        <?php foreach ($documents as $document): ?>
          <article class="intern-work-row">
            <span class="intern-work-row__icon"><i class="fa-solid fa-file-lines"></i></span>
            <div><strong><?= Security::e($document['original_name']) ?></strong><small><?= Security::e(status_label($document['category'])) ?> / <?= Security::e(format_datetime($document['created_at'])) ?></small></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </aside>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function intern_work_stat(string $icon, mixed $value, string $label, string $hint): void
{
?>
  <article class="intern-work-stat">
    <span><i class="fa-solid <?= Security::e($icon) ?>"></i></span>
    <div><strong><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong><em><?= Security::e($label) ?></em><small><?= Security::e($hint) ?></small></div>
  </article>
<?php
}
