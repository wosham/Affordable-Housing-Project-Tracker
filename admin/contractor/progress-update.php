<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$requestedProjectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectId = ContractorProject::defaultProjectId($userId, $role, $requestedProjectId);
$projects = ContractorProject::projects($userId, $role);
$project = $projectId > 0 ? ContractorProject::detail($projectId, $userId, $role) : null;
$history = $project ? ContractorProject::progressHistory($projectId, $userId, $role, 8) : [];
$tasks = $project ? ContractorProject::programmeFocusItems($projectId, $userId, $role, 6) : [];

$pageTitle = 'Progress Update';
$pageDescription = 'Submit project progress updates with site evidence and notes.';
$adminRole = 'contractor';
$contentClass = 'contractor-project-page';
$componentCss = ['contractor-project'];
$pageScripts = ['contractor-progress', 'contractor-progress-history'];
$csrfForm = 'contractor_progress';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Progress Update'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-project-hero card"
  data-progress-page
  data-endpoint="<?= Security::e(Url::to('api/projects/update-progress.php')) ?>"
  data-media-list-endpoint="<?= Security::e(Url::to('api/media/list.php')) ?>"
  data-media-upload-endpoint="<?= Security::e(Url::to('api/media/upload.php')) ?>"
  data-my-project-url="<?= Security::e(Url::to('admin/contractor/my-project.php')) ?>">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Progress submission</span>
    <h2>Progress Update</h2>
    <p>Submit measured project progress with a work summary, milestone note and site photo. Managers and consultants are notified automatically.</p>
  </div>
  <div class="contractor-project-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/my-project.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> My Project</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/programme-of-works.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Programme</a>
  </div>
</section>

<?php if (!$project): ?>
  <div class="card empty-state">
    <strong class="empty-state__title">No assigned project found</strong>
    <span class="empty-state__text">Assigned project records will appear here once configured.</span>
  </div>
<?php else: ?>

<section class="contractor-project-grid contractor-project-grid--main">
  <article class="card contractor-progress-form-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Submit Progress</h2>
        <p class="card__subtitle">Current progress is <?= (int)percentage($project['pct_complete'] ?? 0) ?>%. Add supporting site evidence before saving.</p>
      </div>
      <span class="badge badge--info"><?= Security::e(safe_truncate($project['name'], 40)) ?></span>
    </div>

    <form class="contractor-progress-form" data-progress-form enctype="multipart/form-data" data-original-progress="<?= (int)percentage($project['pct_complete'] ?? 0) ?>">
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">

      <label><span>Project</span>
        <select name="project_selector" data-project-jump>
          <?php foreach ($projects as $option): ?>
            <option value="<?= Security::e(Url::to('admin/contractor/progress-update.php?project_id=' . (int)$option['id'])) ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="contractor-progress-control" aria-label="Progress percentage control">
        <label><span>New progress %</span>
          <input type="number" name="pct_complete" min="0" max="100" step="1" value="<?= (int)percentage($project['pct_complete'] ?? 0) ?>" data-progress-number required>
        </label>
        <input type="range" min="0" max="100" step="1" value="<?= (int)percentage($project['pct_complete'] ?? 0) ?>" data-progress-range aria-label="Progress percentage">
      </div>

      <label><span>Current milestone</span>
        <input type="text" name="current_milestone" maxlength="255" value="<?= Security::e($project['current_milestone'] ?? '') ?>" placeholder="Example: Ground-floor columns">
      </label>

      <label><span>Work summary</span>
        <textarea name="work_summary" rows="4" placeholder="Summarise completed work, measured progress and active areas." required></textarea>
      </label>

      <label><span>Progress note</span>
        <textarea name="note" rows="3" placeholder="Add any important progress note for the project team." required></textarea>
      </label>

      <label><span>Blockers / support needed</span>
        <textarea name="blockers" rows="3" placeholder="Mention blockers, access issues, delayed materials or dependencies."></textarea>
      </label>

      <label><span>Weather / site condition</span>
        <input type="text" name="weather_note" maxlength="180" placeholder="Example: Clear morning, light afternoon showers">
      </label>

      <section class="contractor-evidence-picker" data-evidence-picker>
        <input type="hidden" name="progress_media_id" value="" data-progress-media-id>
        <input type="file" name="progress_photo" accept="image/jpeg,image/png,image/webp" hidden data-progress-photo>
        <div>
          <span class="contractor-evidence-picker__label"><i class="fa-solid fa-camera" aria-hidden="true"></i> Site photo <em>(required)</em></span>
          <p>Attach clear site evidence from the project media library or upload a new photo.</p>
        </div>
        <div class="contractor-evidence-picker__actions">
          <button class="btn btn--outline" type="button" data-evidence-library-open><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose from Media Library</button>
          <button class="btn btn--primary" type="button" data-evidence-upload-trigger><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload New Evidence</button>
        </div>
        <div class="contractor-evidence-preview" data-evidence-preview hidden>
          <img src="" alt="" data-evidence-preview-image>
          <span>
            <strong data-evidence-preview-title>Selected evidence</strong>
            <small data-upload-label>JPG, PNG or WebP evidence up to the configured limit.</small>
          </span>
          <button class="btn btn--outline btn--sm" type="button" data-evidence-clear><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</button>
        </div>
      </section>

      <div class="contractor-progress-warning" data-progress-warning hidden></div>

      <div class="contractor-form-actions">
        <button class="btn btn--outline" type="reset">Reset</button>
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit Update</button>
      </div>
    </form>
  </article>

  <aside class="card">
    <div class="card__header">
      <div><h2 class="card__title">Current Programme</h2><p class="card__subtitle">Use active tasks to support your update.</p></div>
      <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/contractor/programme-of-works.php?project_id=' . $projectId)) ?>">Full programme</a>
    </div>
    <div class="contractor-project-list">
      <?php if ($tasks === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No open programme tasks</strong></div>
      <?php endif; ?>
      <?php foreach ($tasks as $task): ?>
        <a class="contractor-project-list-item" href="<?= Security::e(Url::to('admin/contractor/programme-of-works.php?project_id=' . $projectId . '&q=' . rawurlencode((string)$task['task_name']))) ?>">
          <span><strong><?= Security::e($task['task_name']) ?></strong><small><?= Security::e(status_label($task['status'])) ?> / <?= Security::e(format_date($task['planned_end'] ?? $task['end_date'] ?? null)) ?></small></span>
          <span><em><?= (int)percentage($task['pct_complete'] ?? 0) ?>%</em><small><?= ((int)($task['critical_path'] ?? 0) === 1) ? 'Critical path' : 'Task' ?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>
</section>

<section class="card">
  <div class="card__header"><div><h2 class="card__title">Recent Progress Updates</h2><p class="card__subtitle">Latest submitted project progress notes.</p></div></div>
  <?php
    $wide = true;
    include __DIR__ . '/../../app/partials/admin/contractor-progress-timeline.php';
  ?>
</section>

<?php endif; ?>

<div class="contractor-media-overlay" data-evidence-modal hidden aria-hidden="true">
  <div class="contractor-media-modal" role="dialog" aria-modal="true" aria-labelledby="contractorEvidenceTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Media library</span>
        <h2 id="contractorEvidenceTitle">Choose Site Evidence</h2>
        <p>Select an existing project photo or upload a new evidence image.</p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-evidence-modal-close aria-label="Close media library"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </header>
    <div class="contractor-media-toolbar">
      <label><span>Search</span><input type="search" placeholder="Search evidence..." data-evidence-search></label>
      <button class="btn btn--primary" type="button" data-evidence-modal-upload><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload Evidence</button>
    </div>
    <div class="contractor-media-grid" data-evidence-grid>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">Loading media...</strong></div>
    </div>
    <footer>
      <span data-evidence-status>Only your site evidence is shown here.</span>
      <button class="btn btn--outline" type="button" data-evidence-modal-close>Close</button>
    </footer>
  </div>
</div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
