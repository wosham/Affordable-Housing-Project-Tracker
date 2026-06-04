<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_project_delete';

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/projects.php'));
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));
    $projectId = Security::cleanInt($_POST['project_id'] ?? 0);

    if ($action === 'delete' && $projectId > 0) {
        $project = Project::find($projectId);
        if (!$project) {
            Session::flash('error', 'Project could not be found.');
        } elseif (Project::safeDelete($projectId)) {
            Logger::log('delete', 'projects', $projectId, ['name' => $project['name'] ?? '']);
            Session::flash('status', 'Project deleted successfully.');
        } else {
            Database::query("UPDATE projects SET status = 'cancelled' WHERE id = ?", [$projectId]);
            Logger::log('cancel', 'projects', $projectId, ['reason' => 'Delete blocked by related records']);
            Session::flash('status', 'Project had linked work, so it was marked as cancelled instead.');
        }
    }

    Response::redirect(Url::to('admin/superadmin/projects.php'));
}

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency' => Security::cleanString((string)($_GET['constituency'] ?? '')),
    'category' => Security::cleanString((string)($_GET['category'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '');

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalProjects = Project::countWithFilters($filters);
$totalPages = max(1, (int)ceil($totalProjects / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$projects = Project::withRelations($filters, $perPage, $offset);
$showingFrom = $totalProjects > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($projects), $totalProjects);
$stats = Project::stats();
$constituencies = Constituency::findAll([], 'name ASC');
$categories = ProjectCategory::findAll([], 'name ASC');
$statusOptions = Project::statusOptions();

$pageTitle = 'Projects';
$pageDescription = 'Manage all Trans-Nzoia Affordable Housing Programme projects.';
$adminRole = 'superadmin';
$contentClass = 'sa-projects-page';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Projects'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-projects-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-building-circle-check" aria-hidden="true"></i> Project Registry</span>
    <h2>County delivery portfolio</h2>
    <p>Track every site, constituency allocation, contractor, milestone and delivery risk from one operational register.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/project-create.php')) ?>">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> New Project
    </a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('api/projects/get-all.php')) ?>" target="_blank" rel="noopener noreferrer">
      <i class="fa-solid fa-eye" aria-hidden="true"></i> Project Data
    </a>
  </div>
</section>

<section class="stat-grid stat-grid--4 sa-project-stat-row" aria-label="Project summary">
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_number($stats['total_projects'] ?? 0)) ?></strong>
      <span class="stat-widget__label">Total Projects</span>
      <small class="stat-widget__trend"><?= Security::e(format_number($stats['active_projects'] ?? 0)) ?> active right now</small>
    </span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_number($stats['total_units'] ?? 0)) ?></strong>
      <span class="stat-widget__label">Units Targeted</span>
      <small class="stat-widget__trend">Across <?= Security::e(format_number(count($constituencies))) ?> constituencies</small>
    </span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_number($stats['watch_projects'] ?? 0)) ?></strong>
      <span class="stat-widget__label">Needs Attention</span>
      <small class="stat-widget__trend">Stalled or on hold</small>
    </span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_money($stats['contract_value'] ?? 0)) ?></strong>
      <span class="stat-widget__label">Contract Value</span>
      <small class="stat-widget__trend"><?= Security::e(format_percentage($stats['avg_completion'] ?? 0)) ?> average completion</small>
    </span>
  </article>
</section>

<section class="card sa-projects-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">All Projects</h2>
      <p class="card__subtitle">Filter, inspect and manage all registered programme projects.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalProjects)) ?> projects</span>
  </div>

  <form class="filter-bar sa-project-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>">
    <div class="filter-group">
      <label class="filter-label" for="q">Search</label>
      <input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project, contractor, site engineer...">
    </div>
    <div class="filter-group">
      <label class="filter-label" for="status">Status</label>
      <select class="form-select" id="status" name="status">
        <option value="">All statuses</option>
<?php foreach ($statusOptions as $status): ?>
        <option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label" for="constituency">Constituency</label>
      <select class="form-select" id="constituency" name="constituency">
        <option value="">All constituencies</option>
<?php foreach ($constituencies as $constituency): ?>
        <option value="<?= Security::e($constituency['slug']) ?>" <?= (($filters['constituency'] ?? '') === $constituency['slug']) ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label" for="category">Category</label>
      <select class="form-select" id="category" name="category">
        <option value="">All categories</option>
<?php foreach ($categories as $category): ?>
        <option value="<?= Security::e($category['slug']) ?>" <?= (($filters['category'] ?? '') === $category['slug']) ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>">Reset</a>
    </div>
  </form>

  <div class="table-wrap">
    <table class="data-table sa-project-table">
      <thead>
        <tr>
          <th class="sa-table-number">#</th>
          <th>Project</th>
          <th>Location</th>
          <th>Status</th>
          <th>Progress</th>
          <th>Units</th>
          <th>Contractor</th>
          <th>Contract Value</th>
          <th>Updated</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
<?php if ($projects === []): ?>
        <tr>
          <td colspan="10">
            <div class="empty-state">
              <span class="empty-state__icon"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></span>
              <strong class="empty-state__title">No projects match your filters</strong>
              <span class="empty-state__text">Create a project or adjust your filters to widen the registry view.</span>
            </div>
          </td>
        </tr>
<?php endif; ?>
<?php foreach ($projects as $index => $project): ?>
<?php
    $images = json_decode((string)($project['images_json'] ?? '[]'), true);
    $images = is_array($images) ? $images : [];
    $thumb = $project['hero_image'] ?: ($images[0] ?? '');
    $progress = percentage($project['pct_complete'] ?? 0);
?>
        <tr>
          <td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td>
          <td>
            <div class="sa-project-cell">
              <span class="sa-project-thumb" <?php if ($thumb !== ''): ?>style="background-image:url('<?= Security::e(Url::asset($thumb)) ?>')"<?php endif; ?>>
                <?php if ($thumb === ''): ?><i class="fa-solid fa-building" aria-hidden="true"></i><?php endif; ?>
              </span>
              <span>
                <strong><?= Security::e($project['name']) ?></strong>
                <small><?= Security::e($project['slug']) ?></small>
              </span>
            </div>
          </td>
          <td>
            <strong><?= Security::e($project['constituency_name'] ?? '-') ?></strong>
            <small><?= Security::e($project['ward_name'] ?: ($project['location_label'] ?? '-')) ?></small>
          </td>
          <td><span class="badge <?= Security::e(status_badge_class($project['status'])) ?>"><?= Security::e(status_label($project['status'])) ?></span></td>
          <td>
            <div class="sa-progress-cell">
              <span class="progress progress--sm"><span class="progress__bar" style="width: <?= Security::e((string)$progress) ?>%"></span></span>
              <strong><?= Security::e($progress) ?>%</strong>
            </div>
          </td>
          <td><?= Security::e(format_number($project['units'] ?? 0)) ?></td>
          <td>
            <strong><?= Security::e($project['contractor_name'] ?: 'Not assigned') ?></strong>
            <small><?= Security::e($project['site_engineer'] ?: 'No site engineer') ?></small>
          </td>
          <td><?= Security::e(format_money($project['contract_sum'] ?? 0)) ?></td>
          <td><time datetime="<?= Security::e($project['updated_at'] ?? '') ?>"><?= Security::e(time_ago($project['updated_at'] ?? null)) ?></time></td>
          <td>
            <div class="data-table__actions">
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('project-detail.php?id=' . urlencode((string)$project['slug']))) ?>" target="_blank" rel="noopener noreferrer" title="View public page" aria-label="View public page">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
              </a>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('api/projects/get-project.php?slug=' . urlencode((string)$project['slug']))) ?>" target="_blank" rel="noopener noreferrer" title="View data" aria-label="View data">
                <i class="fa-solid fa-code" aria-hidden="true"></i>
              </a>
              <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/project-edit.php?id=' . (int)$project['id'])) ?>" title="Edit project" aria-label="Edit project">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
              </a>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>" data-confirm="Delete this project and its linked work? This cannot be undone.">
                <?= Csrf::field($csrfForm) ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="project_id" value="<?= Security::e((string)$project['id']) ?>">
                <button class="btn btn--icon btn--danger" type="submit" title="Delete project" aria-label="Delete project">
                  <i class="fa-solid fa-trash" aria-hidden="true"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination sa-project-pagination" aria-label="Project pagination">
    <p class="pagination__info">
      Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?>
      of <?= Security::e(format_number($totalProjects)) ?> projects
    </p>
    <div class="pagination__list">
      <a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(project_page_url(max(1, $page - 1), $filters)) ?>" aria-label="Previous page">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
      </a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(project_page_url($i, $filters)) ?>"><?= Security::e((string)$i) ?></a>
<?php endfor; ?>
      <a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(project_page_url(min($totalPages, $page + 1), $filters)) ?>" aria-label="Next page">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </a>
    </div>
  </nav>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function project_page_url(int $page, array $filters): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => (string)$value !== '');
    return Url::to('admin/superadmin/projects.php' . ($query ? '?' . http_build_query($query) : ''));
}
