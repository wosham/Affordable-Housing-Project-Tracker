<?php
/** @var string $activeInternHub One of: dashboard, sign-in, attendance, project, data-entry, photos, enquiries, messages */
$activeInternHub = $activeInternHub ?? '';
$internHubLinks = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/intern/dashboard.php'],
    'sign-in' => ['label' => 'Sign In', 'icon' => 'fa-location-dot', 'path' => 'admin/intern/sign-in.php'],
    'attendance' => ['label' => 'My Attendance', 'icon' => 'fa-calendar-check', 'path' => 'admin/intern/my-attendance.php'],
    'project' => ['label' => 'My Project', 'icon' => 'fa-building', 'path' => 'admin/intern/my-project.php'],
    'data-entry' => ['label' => 'Data Entry', 'icon' => 'fa-pen-to-square', 'path' => 'admin/intern/site-data-entry.php'],
    'photos' => ['label' => 'Photos', 'icon' => 'fa-camera', 'path' => 'admin/intern/upload-photos.php'],
    'enquiries' => ['label' => 'Enquiries', 'icon' => 'fa-inbox', 'path' => 'admin/intern/assigned-enquiries.php'],
    'messages' => ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/intern/messages.php'],
];
?>
<nav class="intern-hub card" aria-label="Intern modules">
<?php foreach ($internHubLinks as $key => $link): ?>
  <a class="intern-hub__link<?= $activeInternHub === $key ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($link['path'])) ?>">
    <i class="fa-solid <?= Security::e($link['icon']) ?>" aria-hidden="true"></i>
    <span><?= Security::e($link['label']) ?></span>
  </a>
<?php endforeach; ?>
</nav>
