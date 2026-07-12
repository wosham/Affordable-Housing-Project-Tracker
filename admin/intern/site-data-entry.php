<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$userId = (int)Auth::id();
$projectId = InternProjectWork::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$projects = InternProjectWork::projects($userId);
$project = InternProjectWork::projectOverview($userId, $projectId);
$tasks = $projectId > 0 ? InternProjectWork::currentTasks($projectId, 20) : [];
$entries = $projectId > 0 ? InternProjectWork::entries($userId, $projectId, 10) : [];

$pageTitle = 'Site Data Entry';
$pageDescription = 'Submit daily site observations for your assigned project.';
$adminRole = 'intern';
$csrfForm = 'intern_work';
$contentClass = 'intern-page intern-work-page';
$componentCss = ['intern-work'];
$pageScripts = ['intern-work'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern', 'url' => Url::to('admin/intern/dashboard.php')],
    ['label' => 'Site Data Entry'],
];

$activeInternHub = 'data-entry';
include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<?php include __DIR__ . '/../../app/partials/admin/intern-hub.php'; ?>

<section class="intern-work-hero">
  <div>
    <span class="intern-work-label"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Daily site note</span>
    <h1>Site Data Entry</h1>
    <p>Record field observations, progress notes, site conditions and issues for your assigned project.</p>
  </div>
  <div class="intern-work-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/my-project.php?project_id=' . $projectId)) ?>"><i class="fa-solid fa-building"></i> My Project</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/upload-photos.php?project_id=' . $projectId)) ?>"><i class="fa-solid fa-camera"></i> Photos</a>
  </div>
</section>

<section class="intern-work-grid">
  <div class="intern-work-main">
    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>New Site Note</h2><p><?= Security::e($project['name'] ?? 'Select an assigned project') ?></p></div><span class="intern-work-pill"><?= Security::e(format_date(date('Y-m-d'))) ?></span></div>
      <form class="intern-work-form" action="<?= Security::e(Url::to('api/intern/site-data-save.php')) ?>" method="post" data-intern-entry-form>
        <input type="hidden" name="<?= Security::e(Csrf::tokenName()) ?>" value="<?= Security::e(Csrf::token('intern_work')) ?>">
        <div class="intern-work-form-grid">
          <label><span>Project</span>
<?php if (count($projects) <= 1): ?>
            <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
            <input class="form-input" type="text" value="<?= Security::e($project['name'] ?? 'No assigned project') ?>" readonly>
<?php else: ?>
            <select class="form-select" name="project_id" required><?php foreach ($projects as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$item['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($item['name']) ?></option><?php endforeach; ?></select>
<?php endif; ?>
          </label>
          <label><span>Date</span><input class="form-input" type="date" name="entry_date" value="<?= Security::e(date('Y-m-d')) ?>" required></label>
        </div>
        <label><span>Title</span><input class="form-input" name="entry_title" value="Daily site observation" maxlength="180"></label>
        <label><span>Related programme task</span><select class="form-select" name="programme_task_id"><option value="">No task link</option><?php foreach ($tasks as $task): ?><option value="<?= (int)$task['id'] ?>"><?= Security::e($task['task_name']) ?></option><?php endforeach; ?></select></label>
        <label><span>Work observed *</span><textarea class="form-textarea" name="work_observed" rows="4" placeholder="Summarise visible work progress, active areas and completed activities."></textarea></label>
        <div class="intern-work-form-grid">
          <label><span>Labour observed</span><textarea class="form-textarea" name="labour_observed" rows="3" placeholder="Teams or trades observed on site."></textarea></label>
          <label><span>Materials observed</span><textarea class="form-textarea" name="materials_observed" rows="3" placeholder="Materials delivered, used or awaiting use."></textarea></label>
        </div>
        <div class="intern-work-form-grid">
          <label><span>Equipment observed</span><textarea class="form-textarea" name="equipment_observed" rows="3" placeholder="Equipment present or in use."></textarea></label>
          <label><span>Weather / site condition</span><textarea class="form-textarea" name="weather_condition" rows="3" placeholder="Weather and access conditions."></textarea></label>
        </div>
        <label><span>Issues or blockers</span><textarea class="form-textarea" name="issues" rows="3" placeholder="Access issues, material delays or dependencies."></textarea></label>
        <label><span>Safety observations</span><textarea class="form-textarea" name="safety_observations" rows="3" placeholder="Safety observations, hazards or good practice."></textarea></label>
        <label><span>Progress note *</span><textarea class="form-textarea" name="progress_note" rows="4" placeholder="Add any important note for the project team."></textarea></label>
        <input type="hidden" name="status" value="submitted" data-entry-status>
        <p class="intern-work-status" data-intern-work-status></p>
        <div class="intern-work-actions intern-work-actions--end">
          <button class="btn btn--outline" type="submit" data-save-mode="draft"><i class="fa-solid fa-floppy-disk"></i> Save Draft</button>
          <button class="btn btn--primary" type="submit" data-save-mode="submitted"><i class="fa-solid fa-paper-plane"></i> Submit Note</button>
        </div>
      </form>
    </section>
  </div>

  <aside class="intern-work-side">
    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Recent Notes</h2><p>Your latest submitted observations.</p></div></div>
      <div class="intern-work-list" data-entry-list>
        <?php if ($entries === []): ?><div class="intern-work-empty">No site notes submitted yet.</div><?php endif; ?>
        <?php foreach ($entries as $entry): ?>
          <article class="intern-work-row">
            <span class="intern-work-row__icon"><i class="fa-solid fa-note-sticky"></i></span>
            <div><strong><?= Security::e($entry['entry_title'] ?: 'Site note') ?></strong><small><?= Security::e(format_date($entry['entry_date']) . ' / ' . status_label($entry['status'])) ?></small></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </aside>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
