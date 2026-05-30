<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_leadership';

function sa_leadership_redirect(): void
{
    Response::redirect(Url::to('admin/superadmin/leadership.php'));
}

function sa_lines(array $row, string $field): string
{
    if ($field === 'responsibilities') {
        return implode("\n", LeadershipProfile::responsibilities($row));
    }

    return '';
}

function sa_bool(array $row, string $key, bool $default = false): bool
{
    return array_key_exists($key, $row) ? (int)$row[$key] === 1 : $default;
}

function sa_leadership_asset_control(string $field, string $value, string $folder, string $hint): void
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
        <input class="form-input" name="<?= Security::e($field) ?>" data-cms-upload-target value="<?= Security::e($path) ?>" placeholder="No image selected yet">
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
        sa_leadership_redirect();
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));

    try {
        if ($action === 'save_profile') {
            $id = Security::cleanInt($_POST['profile_id'] ?? 0) ?: null;
            if (trim((string)($_POST['name'] ?? '')) === '') {
                throw new RuntimeException('Leadership profile name is required.');
            }

            $savedId = LeadershipProfile::saveProfile($_POST, $id);
            Logger::log($id ? 'update' : 'create', 'leadership_profiles', $savedId);
            Session::flash('status', 'Leadership profile saved.');
        } elseif ($action === 'delete_profile') {
            $id = Security::cleanInt($_POST['profile_id'] ?? 0);
            if ($id <= 0 || !LeadershipProfile::find($id)) {
                throw new RuntimeException('Leadership profile could not be found.');
            }
            LeadershipProfile::delete($id);
            Logger::log('delete', 'leadership_profiles', $id);
            Session::flash('status', 'Leadership profile removed.');
        } elseif ($action === 'save_contractor') {
            $id = Security::cleanInt($_POST['contractor_id'] ?? 0) ?: null;
            if (trim((string)($_POST['company_name'] ?? '')) === '') {
                throw new RuntimeException('Contractor company name is required.');
            }

            $savedId = Contractor::saveContractor($_POST, $id);
            Logger::log($id ? 'update' : 'create', 'contractors', $savedId);
            Session::flash('status', 'Contractor record saved.');
        } elseif ($action === 'delete_contractor') {
            $id = Security::cleanInt($_POST['contractor_id'] ?? 0);
            if ($id <= 0 || !Contractor::find($id)) {
                throw new RuntimeException('Contractor record could not be found.');
            }
            Contractor::delete($id);
            Logger::log('delete', 'contractors', $id);
            Session::flash('status', 'Contractor record removed.');
        } elseif ($action === 'save_quote') {
            $id = Security::cleanInt($_POST['quote_id'] ?? 0) ?: null;
            if (trim((string)($_POST['quote_text'] ?? '')) === '' || trim((string)($_POST['author_name'] ?? '')) === '') {
                throw new RuntimeException('Quote text and author name are required.');
            }

            $savedId = LeadershipQuote::saveQuote($_POST, $id);
            Logger::log($id ? 'update' : 'create', 'leadership_quotes', $savedId);
            Session::flash('status', 'Leadership quote saved.');
        } elseif ($action === 'delete_quote') {
            $id = Security::cleanInt($_POST['quote_id'] ?? 0);
            if ($id <= 0 || !LeadershipQuote::find($id)) {
                throw new RuntimeException('Quote could not be found.');
            }
            LeadershipQuote::delete($id);
            Logger::log('delete', 'leadership_quotes', $id);
            Session::flash('status', 'Leadership quote removed.');
        }
    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
    }

    sa_leadership_redirect();
}

$profileFilters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'profile_type' => Security::cleanString((string)($_GET['profile_type'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
];
$profileFilters = array_filter($profileFilters, static fn ($value): bool => $value !== '');

$profiles = LeadershipProfile::ordered($profileFilters);
$contractors = Contractor::ordered();
$quotes = LeadershipQuote::ordered();
$partners = Stakeholder::leadershipPartners();
$projects = Project::withRelations([], 100);
$constituencies = Constituency::findAll([], 'name ASC');
$stats = LeadershipProfile::stats();
$contractorStats = Contractor::stats();
$quoteStats = LeadershipQuote::stats();
$stakeholderStats = Stakeholder::stats();
$spotlight = LeadershipProfile::featuredSpotlight();

$editProfile = null;
$editContractor = null;
$editQuote = null;
if (isset($_GET['edit_profile'])) {
    $editProfile = LeadershipProfile::findDetailed(Security::cleanInt($_GET['edit_profile']));
}
if (isset($_GET['edit_contractor'])) {
    $editContractor = Contractor::findDetailed(Security::cleanInt($_GET['edit_contractor']));
}
if (isset($_GET['edit_quote'])) {
    $editQuote = LeadershipQuote::find(Security::cleanInt($_GET['edit_quote']));
}

$profileTypes = [
    'national' => 'National Government',
    'board' => 'Board / Statutory',
    'county' => 'County Field Office',
    'field' => 'Field Team',
    'partner' => 'Partner Representative',
];

$pageTitle = 'Leadership';
$pageDescription = 'Manage leadership hierarchy, county director profile, contractors, quotes and implementing partner signals.';
$adminRole = 'superadmin';
$contentClass = 'sa-leadership-admin-page';
$componentCss = ['cms-editor', 'media-library', 'leadership-admin'];
$pageScripts = ['media-picker', 'leadership-admin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Leadership'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-leadership-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-user-tie" aria-hidden="true"></i> Leadership Registry</span>
    <h2>Programme leadership and delivery partners</h2>
    <p>Control the command chain, county director spotlight, contractor cards, partner rail and leadership quotes used by the leadership page.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--primary" href="#profile-form"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Add Profile</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms-page-editor.php?slug=leadership')) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Page Copy</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('leadership.php')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Preview Page</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 sa-leadership-stats" aria-label="Leadership summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-sitemap" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['org_nodes'] ?? 0)) ?></strong><span class="stat-widget__label">Command Nodes</span><small class="stat-widget__trend">Published hierarchy records</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['cards'] ?? 0)) ?></strong><span class="stat-widget__label">Profile Cards</span><small class="stat-widget__trend">Senior officials and field teams</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($contractorStats['active'] ?? 0)) ?></strong><span class="stat-widget__label">Active Contractors</span><small class="stat-widget__trend">Building across sites</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-handshake-angle" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stakeholderStats['leadership_partners'] ?? 0)) ?></strong><span class="stat-widget__label">Partners</span><small class="stat-widget__trend">Featured organisations</small></span></article>
</section>

<section class="sa-leadership-layout">
  <article class="card sa-leadership-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Leadership Profiles</h2>
        <p class="card__subtitle">Manage the command hierarchy, display cards and county director spotlight.</p>
      </div>
      <span class="badge badge--lime"><?= Security::e(format_number(count($profiles))) ?> records</span>
    </div>

    <form class="filter-bar sa-leadership-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/leadership.php')) ?>">
      <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" id="q" name="q" value="<?= Security::e($profileFilters['q'] ?? '') ?>" placeholder="Name, title, organisation"></div>
      <div class="filter-group"><label class="filter-label" for="profile_type">Type</label><select class="form-select" id="profile_type" name="profile_type"><option value="">All profile types</option><?php foreach ($profileTypes as $type => $label): ?><option value="<?= Security::e($type) ?>" <?= (($profileFilters['profile_type'] ?? '') === $type) ? 'selected' : '' ?>><?= Security::e($label) ?></option><?php endforeach; ?></select></div>
      <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="published" <?= (($profileFilters['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option><option value="draft" <?= (($profileFilters['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option></select></div>
      <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/leadership.php')) ?>">Reset</a></div>
    </form>

    <div class="table-wrap">
      <table class="table sa-leadership-table">
        <thead><tr><th>#</th><th>Profile</th><th>Role</th><th>Placement</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
<?php foreach ($profiles as $index => $profile): ?>
          <tr>
            <td><?= Security::e((string)($index + 1)) ?></td>
            <td>
              <div class="sa-leader-cell">
                <span class="sa-leader-avatar"><?= Security::e($profile['initials'] ?: LeadershipProfile::initials((string)$profile['name'])) ?></span>
                <span><strong><?= Security::e($profile['name']) ?></strong><small><?= Security::e($profile['organisation'] ?: 'Organisation not set') ?></small></span>
              </div>
            </td>
            <td><strong><?= Security::e($profile['title'] ?: 'Title not set') ?></strong><small><?= Security::e($profileTypes[$profile['profile_type']] ?? status_label($profile['profile_type'])) ?></small></td>
            <td><span class="sa-mini-tags"><?= (int)$profile['show_in_org_chart'] === 1 ? '<em>Chart</em>' : '' ?><?= (int)$profile['show_in_cards'] === 1 ? '<em>Card</em>' : '' ?><?= (int)$profile['show_in_spotlight'] === 1 ? '<em>Spotlight</em>' : '' ?></span></td>
            <td><span class="badge <?= Security::e(status_badge_class($profile['status'])) ?>"><?= Security::e(status_label($profile['status'])) ?></span></td>
            <td>
              <div class="table-actions">
                <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/leadership.php?edit_profile=' . (int)$profile['id'] . '#profile-form')) ?>" aria-label="Edit profile"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                <form method="post" data-confirm="Remove this leadership profile?">
                  <?= Csrf::field($csrfForm) ?>
                  <input type="hidden" name="action" value="delete_profile">
                  <input type="hidden" name="profile_id" value="<?= (int)$profile['id'] ?>">
                  <button class="btn btn--icon btn--danger" type="submit" aria-label="Delete profile"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </form>
              </div>
            </td>
          </tr>
<?php endforeach; ?>
<?php if (!$profiles): ?>
          <tr><td colspan="6"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-user-tie" aria-hidden="true"></i></span><strong class="empty-state__title">No leadership profiles found</strong><span class="empty-state__text">Add the national, county and field delivery records for this page.</span></div></td></tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <aside class="sa-leadership-side">
    <article class="card sa-spotlight-card">
      <h3>Spotlight Profile</h3>
<?php if ($spotlight): ?>
      <div class="sa-leader-cell sa-leader-cell--large">
        <span class="sa-leader-avatar"><?= Security::e($spotlight['initials'] ?: LeadershipProfile::initials((string)$spotlight['name'])) ?></span>
        <span><strong><?= Security::e($spotlight['name']) ?></strong><small><?= Security::e($spotlight['title']) ?></small></span>
      </div>
      <p><?= Security::e($spotlight['bio'] ?: 'No spotlight summary recorded yet.') ?></p>
<?php else: ?>
      <div class="empty-state empty-state--compact"><span class="empty-state__icon"><i class="fa-solid fa-id-badge" aria-hidden="true"></i></span><strong class="empty-state__title">No spotlight selected</strong><span class="empty-state__text">Mark one published profile as spotlight.</span></div>
<?php endif; ?>
    </article>

    <article class="card sa-partner-card">
      <h3>Partner Source</h3>
      <p>Partner cards are sourced from the stakeholder registry so logos, websites and roles stay consistent.</p>
      <div class="sa-partner-list">
<?php foreach (array_slice($partners, 0, 6) as $partner): ?>
        <span><i class="fa-solid <?= Security::e($partner['icon'] ?: 'fa-handshake') ?>" aria-hidden="true"></i><?= Security::e($partner['organisation']) ?></span>
<?php endforeach; ?>
      </div>
      <a class="btn btn--outline btn--block" href="<?= Security::e(Url::to('admin/superadmin/stakeholders.php')) ?>">Manage Stakeholders</a>
    </article>
  </aside>
</section>

<section class="card sa-leadership-form-card" id="profile-form">
  <div class="card__header">
    <div>
      <h2 class="card__title"><?= $editProfile ? 'Edit Leadership Profile' : 'Create Leadership Profile' ?></h2>
      <p class="card__subtitle">Use published or draft status, then choose where the profile appears on the leadership page.</p>
    </div>
    <?php if ($editProfile): ?><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/leadership.php#profile-form')) ?>">New Profile</a><?php endif; ?>
  </div>

  <form class="sa-record-form" method="post">
    <?= Csrf::field($csrfForm) ?>
    <input type="hidden" name="action" value="save_profile">
    <input type="hidden" name="profile_id" value="<?= (int)($editProfile['id'] ?? 0) ?>">
    <div class="form-grid form-grid--4">
      <label class="form-field form-field--span-2"><span class="form-label">Full name</span><input class="form-input" name="name" required value="<?= Security::e($editProfile['name'] ?? '') ?>"></label>
      <label class="form-field"><span class="form-label">Slug</span><input class="form-input" name="slug" data-slug-source="name" value="<?= Security::e($editProfile['slug'] ?? '') ?>" placeholder="auto-generated if empty"></label>
      <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><option value="published" <?= (($editProfile['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published</option><option value="draft" <?= (($editProfile['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option></select></label>
      <label class="form-field"><span class="form-label">Profile type</span><select class="form-select" name="profile_type"><?php foreach ($profileTypes as $type => $label): ?><option value="<?= Security::e($type) ?>" <?= (($editProfile['profile_type'] ?? 'national') === $type) ? 'selected' : '' ?>><?= Security::e($label) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Tier</span><input class="form-input" type="number" min="1" name="tier" value="<?= Security::e($editProfile['tier'] ?? '1') ?>"></label>
      <label class="form-field"><span class="form-label">Sort order</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e($editProfile['sort_order'] ?? '10') ?>"></label>
      <label class="form-field"><span class="form-label">Initials</span><input class="form-input" name="initials" value="<?= Security::e($editProfile['initials'] ?? '') ?>" placeholder="MO"></label>
      <label class="form-field form-field--span-2"><span class="form-label">Title</span><input class="form-input" name="title" value="<?= Security::e($editProfile['title'] ?? '') ?>"></label>
      <label class="form-field form-field--span-2"><span class="form-label">Organisation</span><input class="form-input" name="organisation" value="<?= Security::e($editProfile['organisation'] ?? '') ?>"></label>
      <label class="form-field"><span class="form-label">Icon</span><input class="form-input" name="icon" value="<?= Security::e($editProfile['icon'] ?? 'fa-user-tie') ?>"></label>
      <label class="form-field"><span class="form-label">Appointment label</span><input class="form-input" name="appointment_label" value="<?= Security::e($editProfile['appointment_label'] ?? '') ?>"></label>
      <label class="form-field form-field--span-2"><span class="form-label">Appointment source</span><input class="form-input" name="appointment_source" value="<?= Security::e($editProfile['appointment_source'] ?? '') ?>"></label>
      <div class="form-field form-field--span-2"><span class="form-label">Profile image</span><?php sa_leadership_asset_control('photo_path', (string)($editProfile['photo_path'] ?? ''), 'leadership', 'Choose an official portrait or approved leadership image.'); ?></div>
      <label class="form-field form-field--span-2"><span class="form-label">Bio</span><textarea class="form-textarea" rows="5" name="bio"><?= Security::e($editProfile['bio'] ?? '') ?></textarea></label>
      <label class="form-field form-field--span-2"><span class="form-label">Quote</span><textarea class="form-textarea" rows="5" name="quote"><?= Security::e($editProfile['quote'] ?? '') ?></textarea></label>
      <label class="form-field form-field--span-2"><span class="form-label">Responsibilities</span><textarea class="form-textarea" rows="5" name="responsibilities" placeholder="One responsibility per line"><?= Security::e($editProfile ? sa_lines($editProfile, 'responsibilities') : '') ?></textarea></label>
      <label class="form-field"><span class="form-label">Email</span><input class="form-input" name="email" value="<?= Security::e($editProfile['email'] ?? '') ?>"></label>
      <label class="form-field"><span class="form-label">Phone</span><input class="form-input" name="phone" value="<?= Security::e($editProfile['phone'] ?? '') ?>"></label>
      <label class="form-field form-field--span-2"><span class="form-label">Office location</span><input class="form-input" name="office_location" value="<?= Security::e($editProfile['office_location'] ?? '') ?>"></label>
    </div>
    <div class="sa-placement-row">
      <label><input type="checkbox" name="show_in_org_chart" value="1" <?= sa_bool($editProfile ?? [], 'show_in_org_chart', true) ? 'checked' : '' ?>> Command chart</label>
      <label><input type="checkbox" name="show_in_cards" value="1" <?= sa_bool($editProfile ?? [], 'show_in_cards', true) ? 'checked' : '' ?>> Profile cards</label>
      <label><input type="checkbox" name="show_in_spotlight" value="1" <?= sa_bool($editProfile ?? [], 'show_in_spotlight') ? 'checked' : '' ?>> County spotlight</label>
    </div>
    <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Profile</button></div>
  </form>
</section>

<section class="sa-leadership-split">
  <article class="card sa-leadership-card">
    <div class="card__header"><div><h2 class="card__title">Contractor Cards</h2><p class="card__subtitle">Companies shown in the builders section.</p></div><span class="badge badge--lime"><?= Security::e(format_number(count($contractors))) ?> records</span></div>
    <div class="sa-compact-list">
<?php foreach ($contractors as $contractor): ?>
      <div class="sa-compact-row">
        <span class="sa-leader-avatar"><?= Security::e($contractor['initials'] ?: Contractor::initials((string)$contractor['company_name'])) ?></span>
        <span><strong><?= Security::e($contractor['company_name']) ?></strong><small><?= Security::e(($contractor['project_name'] ?? '') ?: 'No linked project') ?></small></span>
        <span class="badge <?= Security::e(status_badge_class($contractor['status'])) ?>"><?= Security::e(status_label($contractor['status'])) ?></span>
        <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/leadership.php?edit_contractor=' . (int)$contractor['id'] . '#contractor-form')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
      </div>
<?php endforeach; ?>
    </div>
  </article>

  <article class="card sa-leadership-card" id="contractor-form">
    <div class="card__header"><div><h2 class="card__title"><?= $editContractor ? 'Edit Contractor' : 'Add Contractor' ?></h2><p class="card__subtitle">Link contractor records to project and constituency delivery data.</p></div></div>
    <form class="sa-record-form" method="post">
      <?= Csrf::field($csrfForm) ?>
      <input type="hidden" name="action" value="save_contractor">
      <input type="hidden" name="contractor_id" value="<?= (int)($editContractor['id'] ?? 0) ?>">
      <div class="form-grid form-grid--2">
        <label class="form-field"><span class="form-label">Company name</span><input class="form-input" name="company_name" required value="<?= Security::e($editContractor['company_name'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">NCA grade</span><input class="form-input" name="nca_grade" value="<?= Security::e($editContractor['nca_grade'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id"><option value="">No project link</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= ((int)($editContractor['project_id'] ?? 0) === (int)$project['id']) ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Constituency</span><select class="form-select" name="constituency_id"><option value="">No constituency link</option><?php foreach ($constituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= ((int)($editContractor['constituency_id'] ?? 0) === (int)$constituency['id']) ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><?php foreach (['active', 'tendering', 'inactive', 'completed'] as $status): ?><option value="<?= Security::e($status) ?>" <?= (($editContractor['status'] ?? 'active') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Progress %</span><input class="form-input" type="number" min="0" max="100" name="progress_pct" value="<?= Security::e($editContractor['progress_pct'] ?? '0') ?>"></label>
        <label class="form-field"><span class="form-label">Sort order</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e($editContractor['sort_order'] ?? '10') ?>"></label>
        <label class="form-field"><span class="form-label">Website</span><input class="form-input" name="website" value="<?= Security::e($editContractor['website'] ?? '') ?>"></label>
        <label class="form-field form-field--full"><span class="form-label">Quote / delivery note</span><textarea class="form-textarea" rows="3" name="quote"><?= Security::e($editContractor['quote'] ?? '') ?></textarea></label>
      </div>
      <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Contractor</button></div>
    </form>
  </article>
</section>

<section class="sa-leadership-split">
  <article class="card sa-leadership-card">
    <div class="card__header"><div><h2 class="card__title">Leadership Quotes</h2><p class="card__subtitle">Short statements used in the quote carousel.</p></div><span class="badge badge--lime"><?= Security::e(format_number($quoteStats['featured'] ?? 0)) ?> featured</span></div>
    <div class="sa-quote-list">
<?php foreach ($quotes as $quote): ?>
      <blockquote>
        <p><?= Security::e($quote['quote_text']) ?></p>
        <footer><strong><?= Security::e($quote['author_name']) ?></strong><span><?= Security::e($quote['author_title']) ?></span><a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/leadership.php?edit_quote=' . (int)$quote['id'] . '#quote-form')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></a></footer>
      </blockquote>
<?php endforeach; ?>
    </div>
  </article>

  <article class="card sa-leadership-card" id="quote-form">
    <div class="card__header"><div><h2 class="card__title"><?= $editQuote ? 'Edit Quote' : 'Add Quote' ?></h2><p class="card__subtitle">Keep carousel entries concise and attributable.</p></div></div>
    <form class="sa-record-form" method="post">
      <?= Csrf::field($csrfForm) ?>
      <input type="hidden" name="action" value="save_quote">
      <input type="hidden" name="quote_id" value="<?= (int)($editQuote['id'] ?? 0) ?>">
      <div class="form-grid form-grid--2">
        <label class="form-field form-field--full"><span class="form-label">Quote text</span><textarea class="form-textarea" rows="4" name="quote_text" required><?= Security::e($editQuote['quote_text'] ?? '') ?></textarea></label>
        <label class="form-field"><span class="form-label">Author name</span><input class="form-input" name="author_name" required value="<?= Security::e($editQuote['author_name'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">Author title</span><input class="form-input" name="author_title" value="<?= Security::e($editQuote['author_title'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">Avatar label</span><input class="form-input" name="avatar_label" value="<?= Security::e($editQuote['avatar_label'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><option value="published" <?= (($editQuote['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published</option><option value="draft" <?= (($editQuote['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option></select></label>
      </div>
      <div class="sa-placement-row"><label><input type="checkbox" name="is_featured" value="1" <?= sa_bool($editQuote ?? [], 'is_featured', true) ? 'checked' : '' ?>> Feature in carousel</label></div>
      <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Quote</button></div>
    </form>
  </article>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
