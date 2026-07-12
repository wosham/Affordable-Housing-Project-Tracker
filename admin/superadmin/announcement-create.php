<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_announcement_create';
$values = announcement_form_values();
$errors = [];

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/announcement-create.php'));
    }

    $errors = announcement_validate($_POST);
    if ($errors === []) {
        try {
            $id = Announcement::saveFromAdmin($_POST);
            Logger::log('create', 'announcements', $id, [
                'title' => $_POST['title'] ?? '',
                'status' => $_POST['status'] ?? '',
                'audience' => $_POST['audience_mode'] ?? '',
            ]);
            Session::flash('status', 'Announcement created successfully.');
            Response::redirect(Url::to('admin/superadmin/announcements.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    $values = announcement_form_values($_POST);
}

$pageTitle = 'Create Staff Announcement';
$pageDescription = 'Create a role-targeted staff announcement.';
$adminRole = 'superadmin';
$contentClass = 'sa-announcements-page';
$componentCss = ['announcements'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Staff Announcements', 'url' => Url::to('admin/superadmin/announcements.php')],
    ['label' => 'Create'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-announcement-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-plus" aria-hidden="true"></i> New staff broadcast</span>
    <h2>Create staff announcement</h2>
    <p>Target <strong>all staff</strong> or <strong>selected roles only</strong>. Role-only notices never appear on other dashboards.</p>
  </div>
  <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to registry</a>
</section>

<?php if ($errors !== []): ?>
  <div class="alert alert--danger"><strong>Fix this first:</strong> <?= Security::e(implode(' ', $errors)) ?></div>
<?php endif; ?>

<section class="card sa-announcement-form-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Staff announcement details</h2>
      <p class="card__subtitle">Audience is required before publishing. Drafts may be incomplete.</p>
    </div>
  </div>
<?php
$csrfForm = $csrfForm;
$formAction = Url::to('admin/superadmin/announcement-create.php');
$submitLabel = 'Create staff announcement';
include __DIR__ . '/../../app/partials/admin/announcement-form.php';
?>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function announcement_validate(array $input): array
{
    $errors = [];
    if (trim((string)($input['title'] ?? '')) === '') {
        $errors[] = 'Title is required.';
    }
    if (trim((string)($input['body'] ?? '')) === '') {
        $errors[] = 'Message body is required.';
    }
    if (!array_key_exists((string)($input['type'] ?? ''), Announcement::TYPES)) {
        $errors[] = 'Choose a valid type.';
    }
    if (!in_array((string)($input['status'] ?? ''), Announcement::STATUSES, true)) {
        $errors[] = 'Choose a valid status.';
    }
    if (!in_array((string)($input['priority'] ?? ''), Announcement::PRIORITIES, true)) {
        $errors[] = 'Choose a valid priority.';
    }
    $mode = strtolower(trim((string)($input['audience_mode'] ?? '')));
    if (!in_array($mode, ['all', 'roles'], true)) {
        $errors[] = 'Choose an audience: All staff or Selected roles.';
    }
    if ($mode === 'roles') {
        $roles = Announcement::normalizeAudienceFromInput($input);
        if ($roles === [] && (string)($input['status'] ?? '') === 'published') {
            $errors[] = 'Select at least one role, or choose All staff.';
        }
    }
    $ctaUrl = trim((string)($input['cta_url'] ?? ''));
    if ($ctaUrl !== '' && !announcement_valid_url($ctaUrl)) {
        $errors[] = 'CTA URL must be a valid http, https, mailto, tel or internal portal path.';
    }
    return $errors;
}

function announcement_form_values(array $source = []): array
{
    $roles = $source['target_roles'] ?? [];
    if (!is_array($roles)) {
        $roles = json_decode((string)($source['target_roles_json'] ?? ''), true) ?: [];
    }
    $roles = array_values(array_filter(array_map('strval', $roles), static fn ($r) => $r !== 'all'));

    $mode = strtolower(trim((string)($source['audience_mode'] ?? '')));
    if ($mode === '' && isset($source['target_roles_json'])) {
        $mode = Announcement::audienceModeFromJson((string)$source['target_roles_json']);
    }
    if ($mode === '' && $roles === []) {
        $mode = 'all';
    }
    if ($mode === '') {
        $mode = 'roles';
    }

    return [
        'title' => (string)($source['title'] ?? ''),
        'body' => (string)($source['body'] ?? ''),
        'type' => (string)($source['type'] ?? 'info'),
        'status' => (string)($source['status'] ?? 'draft'),
        'priority' => (string)($source['priority'] ?? 'normal'),
        'audience_mode' => $mode,
        'target_roles' => $roles,
        'is_pinned' => !empty($source['is_pinned']),
        'cta_label' => (string)($source['cta_label'] ?? ''),
        'cta_url' => (string)($source['cta_url'] ?? ''),
        'published_at' => announcement_datetime_local($source['published_at'] ?? null),
        'expires_at' => announcement_datetime_local($source['expires_at'] ?? null),
    ];
}

function announcement_datetime_local(mixed $date): string
{
    if (!$date) {
        return '';
    }
    $timestamp = strtotime((string)$date);
    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
}

function announcement_valid_url(string $url): bool
{
    return preg_match('#^(?:https?://|mailto:|tel:)#i', $url) === 1
        || preg_match('#^(?:/|admin/|api/|[a-z0-9][a-z0-9._/-]*\.php(?:[?#].*)?)#i', $url) === 1;
}
