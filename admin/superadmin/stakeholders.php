<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_stakeholders';

function sa_stakeholders_redirect(string $fragment = ''): void
{
    Response::redirect(Url::to('admin/superadmin/stakeholders.php' . $fragment));
}

function sa_stakeholders_page(array $rows, string $param, int $perPage = 10): array
{
    $total = count($rows);
    $totalPages = max(1, (int)ceil($total / max(1, $perPage)));
    $page = max(1, min($totalPages, (int)($_GET[$param] ?? 1)));
    $offset = ($page - 1) * $perPage;
    return [array_slice($rows, $offset, $perPage), $page, $totalPages, $total, $offset];
}

function sa_stakeholders_pagination(int $page, int $totalPages, int $total, int $perPage, string $param, string $fragment, string $label): string
{
    if ($total <= 0) {
        return '';
    }
    $from = (($page - 1) * $perPage) + 1;
    $to = min($total, $page * $perPage);
    $query = $_GET;
    $prev = $query; $prev[$param] = max(1, $page - 1);
    $next = $query; $next[$param] = min($totalPages, $page + 1);
    $base = 'admin/superadmin/stakeholders.php';
    ob_start();
    ?>
  <nav class="sa-project-pagination pagination" aria-label="<?= Security::e($label) ?>">
    <span>Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> (10 per page)</span>
    <div class="pagination__links">
      <a class="btn btn--sm btn--outline <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(Url::to($base . '?' . http_build_query($prev) . $fragment)) ?>">Previous</a>
      <span class="pagination__current">Page <?= Security::e(format_number($page)) ?> of <?= Security::e(format_number($totalPages)) ?></span>
      <a class="btn btn--sm btn--outline <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(Url::to($base . '?' . http_build_query($next) . $fragment)) ?>">Next</a>
    </div>
  </nav>
    <?php
    return (string)ob_get_clean();
}

function sa_stakeholder_selected(mixed $left, mixed $right): string
{
    return (string)$left === (string)$right ? 'selected' : '';
}

function sa_stakeholder_checked(array $row, string $key): string
{
    return (int)($row[$key] ?? 0) === 1 ? 'checked' : '';
}

function sa_stakeholder_lines(array $row, string $field, callable $reader): string
{
    return implode("\n", $reader($row));
}

function sa_stakeholder_asset_control(string $field, string $value, string $folder, string $hint): void
{
    $path = trim($value);
    $url = $path !== '' ? Url::asset($path) : '';
    $filename = $path !== '' ? basename($path) : 'No image selected';
    ?>
    <div class="sa-cms-asset-control<?= $path !== '' ? ' has-asset' : '' ?>" data-cms-upload data-upload-folder="<?= Security::e($folder) ?>">
      <div class="sa-cms-asset-control__preview" data-cms-asset-preview>
<?php if ($url !== ''): ?>
        <img src="<?= Security::e($url) ?>" alt="">
<?php else: ?>
        <span><i class="fa-solid fa-image" aria-hidden="true"></i></span>
<?php endif; ?>
      </div>
      <div class="sa-cms-asset-control__body">
        <div class="sa-cms-asset-control__meta">
          <span><i class="fa-solid fa-folder-open" aria-hidden="true"></i> <?= Security::e(status_label($folder)) ?></span>
          <strong data-cms-asset-name><?= Security::e($filename) ?></strong>
          <small><?= Security::e($hint) ?></small>
        </div>
        <input class="form-input" name="<?= Security::e($field) ?>" data-cms-upload-target value="<?= Security::e($path) ?>" placeholder="Choose an image from the media library">
        <div class="sa-cms-asset-control__actions">
          <button class="btn btn--primary btn--sm" type="button" data-media-picker-open data-media-picker-folder="<?= Security::e($folder) ?>">
            <i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose from Library
          </button>
        </div>
      </div>
    </div>
    <?php
}

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        sa_stakeholders_redirect();
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));

    try {
        if ($action === 'save_group') {
            $id = Security::cleanInt($_POST['group_id'] ?? 0) ?: null;
            if (trim((string)($_POST['name'] ?? '')) === '') {
                throw new RuntimeException('Stakeholder group name is required.');
            }
            $savedId = StakeholderGroup::saveGroup($_POST, $id);
            Logger::log($id ? 'update' : 'create', 'stakeholder_groups', $savedId);
            Session::flash('status', 'Stakeholder group saved.');
            sa_stakeholders_redirect('#groups');
        }

        if ($action === 'save_stakeholder') {
            $id = Security::cleanInt($_POST['stakeholder_id'] ?? 0) ?: null;
            if (trim((string)($_POST['organisation'] ?? '')) === '') {
                throw new RuntimeException('Organisation name is required.');
            }
            $savedId = Stakeholder::saveStakeholder($_POST, $id);
            Logger::log($id ? 'update' : 'create', 'stakeholders', $savedId);
            Session::flash('status', 'Stakeholder organisation saved.');
            sa_stakeholders_redirect('#organisations');
        }

        if ($action === 'delete_stakeholder') {
            $id = Security::cleanInt($_POST['stakeholder_id'] ?? 0);
            if ($id <= 0 || !Stakeholder::find($id)) {
                throw new RuntimeException('Stakeholder organisation could not be found.');
            }
            Stakeholder::delete($id);
            Logger::log('delete', 'stakeholders', $id);
            Session::flash('status', 'Stakeholder organisation removed.');
            sa_stakeholders_redirect('#organisations');
        }

        if ($action === 'save_milestone') {
            $id = Security::cleanInt($_POST['milestone_id'] ?? 0) ?: null;
            if (trim((string)($_POST['title'] ?? '')) === '') {
                throw new RuntimeException('Milestone title is required.');
            }
            $savedId = StakeholderMilestone::saveMilestone($_POST, $id);
            Logger::log($id ? 'update' : 'create', 'stakeholder_milestones', $savedId);
            Session::flash('status', 'Stakeholder milestone saved.');
            sa_stakeholders_redirect('#milestones');
        }

        if ($action === 'delete_milestone') {
            $id = Security::cleanInt($_POST['milestone_id'] ?? 0);
            if ($id <= 0 || !StakeholderMilestone::find($id)) {
                throw new RuntimeException('Milestone could not be found.');
            }
            StakeholderMilestone::delete($id);
            Logger::log('delete', 'stakeholder_milestones', $id);
            Session::flash('status', 'Stakeholder milestone removed.');
            sa_stakeholders_redirect('#milestones');
        }

        if ($action === 'save_testimonial') {
            $id = Security::cleanInt($_POST['testimonial_id'] ?? 0) ?: null;
            if (trim((string)($_POST['name'] ?? '')) === '' || trim((string)($_POST['quote'] ?? '')) === '') {
                throw new RuntimeException('Voice name and quote are required.');
            }
            $savedId = StakeholderTestimonial::saveTestimonial($_POST, $id);
            Logger::log($id ? 'update' : 'create', 'stakeholder_testimonials', $savedId);
            Session::flash('status', 'Community voice saved.');
            sa_stakeholders_redirect('#voices');
        }

        if ($action === 'delete_testimonial') {
            $id = Security::cleanInt($_POST['testimonial_id'] ?? 0);
            if ($id <= 0 || !StakeholderTestimonial::find($id)) {
                throw new RuntimeException('Community voice could not be found.');
            }
            StakeholderTestimonial::delete($id);
            Logger::log('delete', 'stakeholder_testimonials', $id);
            Session::flash('status', 'Community voice removed.');
            sa_stakeholders_redirect('#voices');
        }

    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
        sa_stakeholders_redirect();
    }

    sa_stakeholders_redirect();
}

$groupsAll = StakeholderGroup::ordered();
$stakeholdersAll = Stakeholder::ordered();
$milestonesAll = StakeholderMilestone::ordered();
$voicesAll = StakeholderTestimonial::ordered();

$groupStats = StakeholderGroup::stats();
$stakeholderStats = Stakeholder::stats();
$milestoneStats = StakeholderMilestone::stats();
$voiceStats = StakeholderTestimonial::stats();

$perPage = 10;
[$groups, $groupPage, $groupPages, $groupTotal, $groupOffset] = sa_stakeholders_page($groupsAll, 'group_page', $perPage);
[$stakeholders, $orgPage, $orgPages, $orgTotal, $orgOffset] = sa_stakeholders_page($stakeholdersAll, 'org_page', $perPage);
[$milestones, $msPage, $msPages, $msTotal, $msOffset] = sa_stakeholders_page($milestonesAll, 'ms_page', $perPage);
[$voices, $voicePage, $voicePages, $voiceTotal, $voiceOffset] = sa_stakeholders_page($voicesAll, 'voice_page', $perPage);

$editGroup = isset($_GET['edit_group']) ? StakeholderGroup::findDetailed(Security::cleanInt($_GET['edit_group'])) : null;
$editStakeholder = isset($_GET['edit_stakeholder']) ? Stakeholder::findDetailed(Security::cleanInt($_GET['edit_stakeholder'])) : null;
$editMilestone = isset($_GET['edit_milestone']) ? StakeholderMilestone::findDetailed(Security::cleanInt($_GET['edit_milestone'])) : null;
$editVoice = isset($_GET['edit_voice']) ? StakeholderTestimonial::findDetailed(Security::cleanInt($_GET['edit_voice'])) : null;

$blankGroup = ['id' => 0, 'name' => '', 'slug' => '', 'icon' => 'fa-landmark', 'count_label' => '', 'summary' => '', 'description' => '', 'tags_json' => '[]', 'cta_label' => 'See entities', 'cta_url' => '#', 'legal_basis' => '', 'responsibilities_json' => '[]', 'reporting_lines' => '', 'sort_order' => count($groupsAll) + 1, 'status' => 'published'];
$blankStakeholder = ['id' => 0, 'group_id' => '', 'organisation' => '', 'slug' => '', 'role' => '', 'category' => 'partner', 'partner_type' => '', 'agreement_type' => '', 'icon' => 'fa-handshake', 'description' => '', 'mandate' => '', 'logo_path' => '', 'website' => '', 'sort_order' => count($stakeholdersAll) + 1, 'status' => 'published', 'featured_on_leadership' => 0, 'is_formal_partner' => 1, 'featured_on_stakeholders' => 1];
$blankMilestone = ['id' => 0, 'group_id' => '', 'milestone_date' => '', 'date_label' => '', 'title' => '', 'summary' => '', 'icon' => 'fa-flag', 'badge_label' => '', 'sort_order' => count($milestonesAll) + 1, 'status' => 'published'];
$blankVoice = ['id' => 0, 'group_id' => '', 'name' => '', 'role' => '', 'initials' => '', 'photo_path' => '', 'rating' => 5, 'quote' => '', 'tags_json' => '[]', 'sort_order' => count($voicesAll) + 1, 'status' => 'published'];

$groupForm = array_merge($blankGroup, $editGroup ?: []);
$stakeholderForm = array_merge($blankStakeholder, $editStakeholder ?: []);
$milestoneForm = array_merge($blankMilestone, $editMilestone ?: []);
$voiceForm = array_merge($blankVoice, $editVoice ?: []);

$pageTitle = 'Stakeholders';
$pageDescription = 'Manage programme ecosystem groups, partner organisations, mandates, milestones and community voices.';
$adminRole = 'superadmin';
$contentClass = 'sa-stakeholders-admin-page';
$componentCss = ['cms-editor', 'media-library', 'stakeholders-admin'];
$pageScripts = ['media-picker', 'stakeholders-admin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Stakeholders'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-stakeholders-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-network-wired" aria-hidden="true"></i> Stakeholder Ecosystem</span>
    <h2>Programme partners, mandates and community channels</h2>
    <p>Control the ecosystem map, formal institutions, accountability pillars, engagement milestones and community voices used by the public stakeholders page. Use Page Text / SEO for hero wording and section headings.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--primary" href="#organisation-form"><i class="fa-solid fa-building-circle-arrow-right" aria-hidden="true"></i> Add Organisation</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms-page-editor.php?slug=stakeholders')) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Page Text / SEO</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('stakeholders.php')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Preview Page</a>
  </div>
</section>

<section class="card sa-cms-module-callout">
  <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i></div>
  <div>
    <h3>How this connects to the public page</h3>
    <p>Groups feed the Programme Web and mandate accordion. Organisations feed ecosystem details and formal partners. Milestones feed the timeline. Community voices feed the public testimony cards. Page Text / SEO controls only wording, images and section titles.</p>
  </div>
  <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/cms-page-editor.php?slug=stakeholders')) ?>"><i class="fa-solid fa-file-pen" aria-hidden="true"></i> Edit Page Text / SEO</a>
</section>

<section class="stat-grid stat-grid--4 sa-stakeholders-stats" aria-label="Stakeholder summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($groupStats['published'] ?? 0)) ?></strong><span class="stat-widget__label">Groups</span><small class="stat-widget__trend">Ecosystem pillars</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-handshake" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stakeholderStats['published'] ?? 0)) ?></strong><span class="stat-widget__label">Organisations</span><small class="stat-widget__trend"><?= Security::e(format_number($stakeholderStats['formal_partners'] ?? 0)) ?> formal partners</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-timeline" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($milestoneStats['published'] ?? 0)) ?></strong><span class="stat-widget__label">Milestones</span><small class="stat-widget__trend">Engagement journey</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-comments" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($voiceStats['published'] ?? 0)) ?></strong><span class="stat-widget__label">Community Voices</span><small class="stat-widget__trend">Published public voices</small></span></article>
</section>

<nav class="card sa-stakeholders-tabs" aria-label="Stakeholders workspace">
  <a href="#groups"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Groups</a>
  <a href="#organisations"><i class="fa-solid fa-building" aria-hidden="true"></i> Organisations</a>
  <a href="#milestones"><i class="fa-solid fa-timeline" aria-hidden="true"></i> Milestones</a>
  <a href="#voices"><i class="fa-solid fa-quote-left" aria-hidden="true"></i> Voices</a>
</nav>

<section class="sa-stakeholders-layout" id="groups">
  <article class="card sa-stakeholders-card">
    <div class="card__header"><div><h2 class="card__title">Ecosystem Groups</h2><p class="card__subtitle">These six pillars drive the map, mandate accordion and group cards.</p></div><span class="badge badge--lime"><?= Security::e(format_number($groupTotal)) ?> groups</span></div>
    <div class="table-wrap">
      <table class="table sa-stakeholder-table" data-stakeholder-table>
        <thead><tr><th>#</th><th>Group</th><th>Signal</th><th>Records</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
<?php foreach ($groups as $index => $group): ?>
          <tr>
            <td><?= Security::e((string)($groupOffset + $index + 1)) ?></td>
            <td><div class="sa-stakeholder-cell"><span class="sa-stakeholder-icon"><i class="fa-solid <?= Security::e($group['icon'] ?: 'fa-layer-group') ?>" aria-hidden="true"></i></span><span><strong><?= Security::e($group['name']) ?></strong><small><?= Security::e($group['summary']) ?></small></span></div></td>
            <td><strong><?= Security::e($group['count_label'] ?: 'Not set') ?></strong><small><?= Security::e($group['cta_label'] ?: 'No CTA') ?></small></td>
            <td><span class="sa-chip-row"><em><?= Security::e(format_number($group['stakeholder_count'] ?? 0)) ?> orgs</em><em><?= Security::e(format_number($group['milestone_count'] ?? 0)) ?> milestones</em></span></td>
            <td><span class="badge <?= Security::e(status_badge_class($group['status'])) ?>"><?= Security::e(status_label($group['status'])) ?></span></td>
            <td><div class="table-actions"><a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/stakeholders.php?edit_group=' . (int)$group['id'] . '#group-form')) ?>" aria-label="Edit group"><i class="fa-solid fa-pen" aria-hidden="true"></i></a></div></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= sa_stakeholders_pagination($groupPage, $groupPages, $groupTotal, $perPage, 'group_page', '#groups', 'Stakeholder groups pagination') ?>
  </article>

  <aside class="card sa-stakeholders-form-card" id="group-form">
    <h3><?= $editGroup ? 'Edit Ecosystem Group' : 'Add Ecosystem Group' ?></h3>
    <p>Use concise labels. These names appear in map nodes, pillars and mandates.</p>
    <form class="sa-record-form" method="post">
      <?= Csrf::field($csrfForm) ?>
      <input type="hidden" name="action" value="save_group">
      <input type="hidden" name="group_id" value="<?= (int)$groupForm['id'] ?>">
      <div class="form-grid form-grid--2">
        <label class="form-field"><span>Name</span><input class="form-input" name="name" required value="<?= Security::e($groupForm['name']) ?>"></label>
        <label class="form-field"><span>Slug</span><input class="form-input" name="slug" data-slug-source="name" value="<?= Security::e($groupForm['slug']) ?>"></label>
        <label class="form-field"><span>Font Awesome icon</span><input class="form-input" name="icon" value="<?= Security::e($groupForm['icon']) ?>"></label>
        <label class="form-field"><span>Count label</span><input class="form-input" name="count_label" value="<?= Security::e($groupForm['count_label']) ?>"></label>
        <label class="form-field form-field--full"><span>Summary</span><textarea class="form-textarea" name="summary"><?= Security::e($groupForm['summary']) ?></textarea></label>
        <label class="form-field form-field--full"><span>Description</span><textarea class="form-textarea" name="description"><?= Security::e($groupForm['description']) ?></textarea></label>
        <label class="form-field form-field--full"><span>Tags, one per line</span><textarea class="form-textarea" name="tags"><?= Security::e(sa_stakeholder_lines($groupForm, 'tags', [StakeholderGroup::class, 'tags'])) ?></textarea></label>
        <label class="form-field form-field--full"><span>Legal basis</span><textarea class="form-textarea" name="legal_basis"><?= Security::e($groupForm['legal_basis']) ?></textarea></label>
        <label class="form-field form-field--full"><span>Responsibilities, one per line</span><textarea class="form-textarea" name="responsibilities"><?= Security::e(sa_stakeholder_lines($groupForm, 'responsibilities', [StakeholderGroup::class, 'responsibilities'])) ?></textarea></label>
        <label class="form-field form-field--full"><span>Reporting lines</span><textarea class="form-textarea" name="reporting_lines"><?= Security::e($groupForm['reporting_lines']) ?></textarea></label>
        <label class="form-field"><span>CTA label</span><input class="form-input" name="cta_label" value="<?= Security::e($groupForm['cta_label']) ?>"></label>
        <label class="form-field"><span>CTA URL</span><input class="form-input" name="cta_url" value="<?= Security::e($groupForm['cta_url']) ?>"></label>
        <label class="form-field"><span>Sort order</span><input class="form-input" name="sort_order" type="number" min="0" value="<?= Security::e((string)$groupForm['sort_order']) ?>"></label>
        <label class="form-field"><span>Status</span><select class="form-select" name="status"><option value="published" <?= sa_stakeholder_selected($groupForm['status'], 'published') ?>>Published</option><option value="draft" <?= sa_stakeholder_selected($groupForm['status'], 'draft') ?>>Draft</option></select></label>
      </div>
      <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Group</button></div>
    </form>
  </aside>
</section>

<section class="card sa-stakeholders-card" id="organisations">
  <div class="card__header"><div><h2 class="card__title">Organisations & Partners</h2><p class="card__subtitle">Formal partners, contractors, regulators, county offices and community institutions.</p></div><span class="badge badge--lime"><?= Security::e(format_number($orgTotal)) ?> records</span></div>
  <div class="table-wrap">
    <table class="table sa-stakeholder-table" data-stakeholder-table>
      <thead><tr><th>#</th><th>Organisation</th><th>Group</th><th>Role</th><th>Flags</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
<?php foreach ($stakeholders as $index => $stakeholder): ?>
        <tr>
          <td><?= Security::e((string)($orgOffset + $index + 1)) ?></td>
          <td><div class="sa-stakeholder-cell"><span class="sa-stakeholder-icon"><i class="fa-solid <?= Security::e($stakeholder['icon'] ?: $stakeholder['group_icon'] ?: 'fa-handshake') ?>" aria-hidden="true"></i></span><span><strong><?= Security::e($stakeholder['organisation']) ?></strong><small><?= Security::e($stakeholder['website'] ?: $stakeholder['slug']) ?></small></span></div></td>
          <td><?= Security::e($stakeholder['group_name'] ?: 'Unassigned') ?></td>
          <td><strong><?= Security::e($stakeholder['role'] ?: 'Role not set') ?></strong><small><?= Security::e($stakeholder['partner_type'] ?: $stakeholder['category']) ?></small></td>
          <td><span class="sa-chip-row"><?= (int)$stakeholder['is_formal_partner'] === 1 ? '<em>Formal</em>' : '' ?><?= (int)$stakeholder['featured_on_leadership'] === 1 ? '<em>Leadership</em>' : '' ?><?= (int)$stakeholder['featured_on_stakeholders'] === 1 ? '<em>Page</em>' : '' ?></span></td>
          <td><span class="badge <?= Security::e(status_badge_class($stakeholder['status'])) ?>"><?= Security::e(status_label($stakeholder['status'])) ?></span></td>
          <td>
            <div class="table-actions">
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/stakeholders.php?edit_stakeholder=' . (int)$stakeholder['id'] . '#organisation-form')) ?>" aria-label="Edit organisation"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
              <form method="post" data-confirm="Remove this organisation?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="delete_stakeholder"><input type="hidden" name="stakeholder_id" value="<?= (int)$stakeholder['id'] ?>"><button class="btn btn--icon btn--danger" type="submit" aria-label="Delete organisation"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form>
            </div>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= sa_stakeholders_pagination($orgPage, $orgPages, $orgTotal, $perPage, 'org_page', '#organisations', 'Organisations pagination') ?>
</section>

<section class="card sa-stakeholders-form-card" id="organisation-form">
  <h3><?= $editStakeholder ? 'Edit Organisation' : 'Add Organisation' ?></h3>
  <p>These records power formal partner cards, ecosystem details and leadership-page implementing partner signals.</p>
  <form class="sa-record-form" method="post">
    <?= Csrf::field($csrfForm) ?>
    <input type="hidden" name="action" value="save_stakeholder">
    <input type="hidden" name="stakeholder_id" value="<?= (int)$stakeholderForm['id'] ?>">
    <div class="form-grid form-grid--3">
      <label class="form-field"><span>Organisation</span><input class="form-input" name="organisation" required value="<?= Security::e($stakeholderForm['organisation']) ?>"></label>
      <label class="form-field"><span>Slug</span><input class="form-input" name="slug" data-slug-source="organisation" value="<?= Security::e($stakeholderForm['slug']) ?>"></label>
      <label class="form-field"><span>Group</span><select class="form-select" name="group_id"><option value="">Choose group</option><?php foreach ($groupsAll as $group): ?><option value="<?= (int)$group['id'] ?>" <?= sa_stakeholder_selected($stakeholderForm['group_id'], $group['id']) ?>><?= Security::e($group['name']) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span>Role</span><input class="form-input" name="role" value="<?= Security::e($stakeholderForm['role']) ?>"></label>
      <label class="form-field"><span>Category</span><input class="form-input" name="category" value="<?= Security::e($stakeholderForm['category']) ?>"></label>
      <label class="form-field"><span>Partner type</span><input class="form-input" name="partner_type" value="<?= Security::e($stakeholderForm['partner_type']) ?>"></label>
      <label class="form-field"><span>Agreement type</span><input class="form-input" name="agreement_type" value="<?= Security::e($stakeholderForm['agreement_type']) ?>"></label>
      <label class="form-field"><span>Icon</span><input class="form-input" name="icon" value="<?= Security::e($stakeholderForm['icon']) ?>"></label>
      <label class="form-field"><span>Website</span><input class="form-input" name="website" value="<?= Security::e($stakeholderForm['website']) ?>"></label>
      <label class="form-field form-field--full"><span>Description</span><textarea class="form-textarea" name="description"><?= Security::e($stakeholderForm['description']) ?></textarea></label>
      <label class="form-field form-field--full"><span>Mandate</span><textarea class="form-textarea" name="mandate"><?= Security::e($stakeholderForm['mandate']) ?></textarea></label>
      <div class="form-field form-field--full"><span>Logo / image</span><?php sa_stakeholder_asset_control('logo_path', (string)$stakeholderForm['logo_path'], 'partners', 'Recommended for formal partner cards and future profile surfaces.'); ?></div>
      <label class="form-field"><span>Sort order</span><input class="form-input" name="sort_order" type="number" min="0" value="<?= Security::e((string)$stakeholderForm['sort_order']) ?>"></label>
      <label class="form-field"><span>Status</span><select class="form-select" name="status"><option value="published" <?= sa_stakeholder_selected($stakeholderForm['status'], 'published') ?>>Published</option><option value="draft" <?= sa_stakeholder_selected($stakeholderForm['status'], 'draft') ?>>Draft</option></select></label>
      <div class="sa-placement-row form-field--full">
        <label><input type="checkbox" name="is_formal_partner" value="1" <?= sa_stakeholder_checked($stakeholderForm, 'is_formal_partner') ?>> Formal partner</label>
        <label><input type="checkbox" name="featured_on_stakeholders" value="1" <?= sa_stakeholder_checked($stakeholderForm, 'featured_on_stakeholders') ?>> Stakeholders page</label>
        <label><input type="checkbox" name="featured_on_leadership" value="1" <?= sa_stakeholder_checked($stakeholderForm, 'featured_on_leadership') ?>> Leadership page</label>
      </div>
    </div>
    <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Organisation</button></div>
  </form>
</section>

<section class="sa-stakeholders-split">
  <article class="card sa-stakeholders-card" id="milestones">
    <div class="card__header"><div><h2 class="card__title">Engagement Milestones</h2><p class="card__subtitle">Timeline events from presidential directive to active construction.</p></div><span class="badge badge--lime"><?= Security::e(format_number($msTotal)) ?> events</span></div>
    <div class="sa-compact-list" data-stakeholder-table>
<?php foreach ($milestones as $index => $milestone): ?>
      <div class="sa-compact-row" data-table-row><span class="sa-row-number"><?= Security::e((string)($msOffset + $index + 1)) ?></span><span class="sa-stakeholder-icon"><i class="fa-solid <?= Security::e($milestone['icon'] ?: $milestone['group_icon'] ?: 'fa-flag') ?>" aria-hidden="true"></i></span><span><strong><?= Security::e($milestone['title']) ?></strong><small><?= Security::e(($milestone['date_label'] ?: (!empty($milestone['milestone_date']) ? format_date($milestone['milestone_date']) : 'Date pending')) . ' - ' . ($milestone['group_name'] ?: 'No group')) ?></small></span><a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/stakeholders.php?edit_milestone=' . (int)$milestone['id'] . '#milestone-form')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></a><form method="post" data-confirm="Remove this milestone?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="delete_milestone"><input type="hidden" name="milestone_id" value="<?= (int)$milestone['id'] ?>"><button class="btn btn--icon btn--danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form></div>
<?php endforeach; ?>
    </div>
    <?= sa_stakeholders_pagination($msPage, $msPages, $msTotal, $perPage, 'ms_page', '#milestones', 'Milestones pagination') ?>
  </article>
  <article class="card sa-stakeholders-form-card" id="milestone-form">
    <h3><?= $editMilestone ? 'Edit Milestone' : 'Add Milestone' ?></h3>
    <form class="sa-record-form" method="post">
      <?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="save_milestone"><input type="hidden" name="milestone_id" value="<?= (int)$milestoneForm['id'] ?>">
      <div class="form-grid form-grid--2">
        <label class="form-field form-field--full"><span>Title</span><input class="form-input" name="title" required value="<?= Security::e($milestoneForm['title']) ?>"></label>
        <label class="form-field"><span>Group</span><select class="form-select" name="group_id"><option value="">No group</option><?php foreach ($groupsAll as $group): ?><option value="<?= (int)$group['id'] ?>" <?= sa_stakeholder_selected($milestoneForm['group_id'], $group['id']) ?>><?= Security::e($group['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span>Date</span><input class="form-input" type="date" name="milestone_date" value="<?= Security::e($milestoneForm['milestone_date']) ?>"></label>
        <label class="form-field"><span>Date label</span><input class="form-input" name="date_label" value="<?= Security::e($milestoneForm['date_label']) ?>"></label>
        <label class="form-field"><span>Icon</span><input class="form-input" name="icon" value="<?= Security::e($milestoneForm['icon']) ?>"></label>
        <label class="form-field form-field--full"><span>Summary</span><textarea class="form-textarea" name="summary"><?= Security::e($milestoneForm['summary']) ?></textarea></label>
        <label class="form-field"><span>Badge</span><input class="form-input" name="badge_label" value="<?= Security::e($milestoneForm['badge_label']) ?>"></label>
        <label class="form-field"><span>Sort</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e((string)$milestoneForm['sort_order']) ?>"></label>
        <label class="form-field"><span>Status</span><select class="form-select" name="status"><option value="published" <?= sa_stakeholder_selected($milestoneForm['status'], 'published') ?>>Published</option><option value="draft" <?= sa_stakeholder_selected($milestoneForm['status'], 'draft') ?>>Draft</option></select></label>
      </div>
      <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Milestone</button></div>
    </form>
  </article>
</section>

<section class="sa-stakeholders-split">
  <article class="card sa-stakeholders-card" id="voices">
    <div class="card__header"><div><h2 class="card__title">Community Voices</h2><p class="card__subtitle">Beneficiary, ward representative and community advocate quotes.</p></div><span class="badge badge--lime"><?= Security::e(format_number($voiceTotal)) ?> voices</span></div>
    <div class="sa-quote-list" data-stakeholder-table>
<?php foreach ($voices as $index => $voice): ?>
      <blockquote data-table-row><header><span class="sa-row-number"><?= Security::e((string)($voiceOffset + $index + 1)) ?></span><strong><?= Security::e($voice['name']) ?></strong><small><?= Security::e($voice['role']) ?></small></header><p><?= Security::e($voice['quote']) ?></p><footer><span><?= Security::e(str_repeat('*', (int)$voice['rating'])) ?> - <?= Security::e($voice['group_name'] ?: 'Community') ?></span><span class="table-actions"><a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/stakeholders.php?edit_voice=' . (int)$voice['id'] . '#voice-form')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></a><form method="post" data-confirm="Remove this community voice?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="delete_testimonial"><input type="hidden" name="testimonial_id" value="<?= (int)$voice['id'] ?>"><button class="btn btn--icon btn--danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form></span></footer></blockquote>
<?php endforeach; ?>
    </div>
    <?= sa_stakeholders_pagination($voicePage, $voicePages, $voiceTotal, $perPage, 'voice_page', '#voices', 'Community voices pagination') ?>
  </article>
  <article class="card sa-stakeholders-form-card" id="voice-form">
    <h3><?= $editVoice ? 'Edit Community Voice' : 'Add Community Voice' ?></h3>
    <form class="sa-record-form" method="post">
      <?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="save_testimonial"><input type="hidden" name="testimonial_id" value="<?= (int)$voiceForm['id'] ?>">
      <div class="form-grid form-grid--2">
        <label class="form-field"><span>Name</span><input class="form-input" name="name" required value="<?= Security::e($voiceForm['name']) ?>"></label>
        <label class="form-field"><span>Initials</span><input class="form-input" name="initials" value="<?= Security::e($voiceForm['initials']) ?>"></label>
        <label class="form-field form-field--full"><span>Role</span><input class="form-input" name="role" value="<?= Security::e($voiceForm['role']) ?>"></label>
        <label class="form-field"><span>Group</span><select class="form-select" name="group_id"><option value="">Community</option><?php foreach ($groupsAll as $group): ?><option value="<?= (int)$group['id'] ?>" <?= sa_stakeholder_selected($voiceForm['group_id'], $group['id']) ?>><?= Security::e($group['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span>Rating</span><input class="form-input" type="number" min="1" max="5" name="rating" value="<?= Security::e((string)$voiceForm['rating']) ?>"></label>
        <div class="form-field form-field--full"><span>Photo</span><?php sa_stakeholder_asset_control('photo_path', (string)$voiceForm['photo_path'], 'profiles', 'Optional profile image for future public profile surfaces.'); ?></div>
        <label class="form-field form-field--full"><span>Quote</span><textarea class="form-textarea" name="quote" required><?= Security::e($voiceForm['quote']) ?></textarea></label>
        <label class="form-field form-field--full"><span>Tags, one per line</span><textarea class="form-textarea" name="tags"><?= Security::e(sa_stakeholder_lines($voiceForm, 'tags', [StakeholderTestimonial::class, 'tags'])) ?></textarea></label>
        <label class="form-field"><span>Sort</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e((string)$voiceForm['sort_order']) ?>"></label>
        <label class="form-field"><span>Status</span><select class="form-select" name="status"><option value="published" <?= sa_stakeholder_selected($voiceForm['status'], 'published') ?>>Published</option><option value="draft" <?= sa_stakeholder_selected($voiceForm['status'], 'draft') ?>>Draft</option></select></label>
      </div>
      <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Voice</button></div>
    </form>
  </article>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
