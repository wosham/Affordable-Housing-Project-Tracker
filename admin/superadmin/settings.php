<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'System Settings';
$pageDescription = 'Configure operational controls for attendance, IPCs, BOQ, reports, public forms and security.';
$adminRole = 'superadmin';
$csrfForm = 'settings';
$contentClass = 'sa-settings-page';
$componentCss = ['settings'];
$pageScripts = ['settings'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Settings'],
];

$groups = SystemSetting::grouped();
$summary = SystemSetting::summary();

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="settings-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-gear" aria-hidden="true"></i> Operational controls</span>
    <h2>System settings</h2>
    <p>Manage live controls for public forms, attendance windows, geo-fence validation, IPC approvals, BOQ safeguards, reports and security thresholds.</p>
  </div>
  <div class="settings-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/audit-log.php')) ?>"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Audit Log</a>
    <button class="btn btn--primary" type="button" data-settings-save-visible><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Visible</button>
  </div>
</section>

<section class="stat-grid stat-grid--4 settings-stats" aria-label="Settings summary">
  <?php settings_stat('fa-sliders', $summary['total'] ?? 0, 'Total Settings', 'Operational keys'); ?>
  <?php settings_stat('fa-pen-to-square', $summary['customised'] ?? 0, 'Customised', 'Changed from default'); ?>
  <?php settings_stat('fa-globe', $summary['public_settings'] ?? 0, 'Public-facing', 'Available to frontend'); ?>
  <?php settings_stat('fa-calendar-week', $summary['changed_week'] ?? 0, 'Changed This Week', 'Recent edits'); ?>
</section>

<section class="settings-layout">
  <aside class="settings-nav card" aria-label="Settings groups">
    <div class="settings-nav__head">
      <strong>Groups</strong>
      <small><?= Security::e(format_number(count($groups))) ?> active groups</small>
    </div>
<?php $first = true; ?>
<?php foreach ($groups as $groupKey => $group): ?>
    <button class="settings-nav__item<?= $first ? ' is-active' : '' ?>" type="button" data-settings-tab="<?= Security::e($groupKey) ?>" aria-pressed="<?= $first ? 'true' : 'false' ?>">
      <span><i class="fa-solid <?= Security::e($group['meta']['icon'] ?? 'fa-sliders') ?>" aria-hidden="true"></i></span>
      <strong><?= Security::e($group['meta']['label'] ?? status_label($groupKey)) ?></strong>
      <em><?= Security::e(format_number(count($group['items']))) ?></em>
    </button>
<?php $first = false; ?>
<?php endforeach; ?>
  </aside>

  <div class="settings-panels">
<?php $firstPanel = true; ?>
<?php foreach ($groups as $groupKey => $group): ?>
    <section class="settings-panel card<?= $firstPanel ? ' is-active' : '' ?>" data-settings-panel="<?= Security::e($groupKey) ?>">
      <div class="card__header">
        <div>
          <h2 class="card__title"><i class="fa-solid <?= Security::e($group['meta']['icon'] ?? 'fa-sliders') ?>" aria-hidden="true"></i> <?= Security::e($group['meta']['label'] ?? status_label($groupKey)) ?></h2>
          <p class="card__subtitle"><?= Security::e(settings_group_description($groupKey)) ?></p>
        </div>
        <button class="btn btn--outline" type="button" data-settings-reset-group="<?= Security::e($groupKey) ?>"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset Group</button>
      </div>

      <div class="settings-grid">
<?php foreach ($group['items'] as $setting): ?>
        <article class="setting-row<?= $setting['is_custom'] ? ' is-custom' : '' ?>" data-setting-row data-setting-key="<?= Security::e($setting['key']) ?>" data-setting-type="<?= Security::e($setting['type']) ?>">
          <div class="setting-row__copy">
            <div class="setting-row__title">
              <strong><?= Security::e($setting['label']) ?></strong>
<?php if ($setting['is_public']): ?>
              <span class="badge badge--neutral">Public</span>
<?php endif; ?>
<?php if ($setting['is_custom']): ?>
              <span class="badge badge--lime" data-custom-badge>Custom</span>
<?php else: ?>
              <span class="badge badge--neutral" data-custom-badge hidden>Custom</span>
<?php endif; ?>
            </div>
            <p><?= Security::e($setting['description']) ?></p>
            <small>Default: <code><?= Security::e(settings_display_value($setting['default'], $setting['type'], (bool)$setting['is_sensitive'])) ?></code><?= $setting['updated_at'] ? ' - Updated ' . Security::e(time_ago($setting['updated_at'])) : '' ?></small>
          </div>
          <div class="setting-row__control">
            <?= settings_control($setting) ?>
            <div class="setting-row__actions">
              <button class="btn btn--primary btn--sm" type="button" data-settings-save><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save</button>
              <button class="btn btn--outline btn--sm" type="button" data-settings-reset><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</button>
            </div>
            <span class="setting-row__state" data-settings-state></span>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </section>
<?php $firstPanel = false; ?>
<?php endforeach; ?>
  </div>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function settings_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number((float)$value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function settings_group_description(string $group): string
{
    return [
        'general' => 'System identity and broad operational switches.',
        'attendance' => 'GPS, geo-fence, sign-in window and attendance gateway rules.',
        'ipc' => 'IPC approval, rejection and retention workflow thresholds.',
        'finance' => 'Payment and retention review timing controls.',
        'boq' => 'Certified and paid quantity safeguards for BOQ updates.',
        'programme' => 'Programme task alert and due-soon controls.',
        'public' => 'Public contact, newsletter and upload intake limits.',
        'reports' => 'Report defaults, export behavior and print footer text.',
        'security' => 'Upload and authentication policy thresholds.',
        'advanced' => 'JSON preferences for future integrations and configurable behavior.',
    ][$group] ?? 'Operational settings for this group.';
}

function settings_control(array $setting): string
{
    $key = Security::e($setting['key']);
    $value = (string)$setting['value'];
    $type = (string)$setting['type'];
    $options = $setting['options'] ?? [];
    $isSensitive = (bool)($setting['is_sensitive'] ?? false);

    if ($type === 'boolean') {
        $checked = in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true) ? ' checked' : '';
        return '<label class="settings-toggle"><input type="checkbox" data-setting-input name="' . $key . '"' . $checked . '><span></span><em>Enabled</em></label>';
    }

    if ($type === 'select') {
        $html = '<select class="form-select" data-setting-input name="' . $key . '">';
        foreach (($options['choices'] ?? []) as $choiceValue => $choiceLabel) {
            $selected = (string)$choiceValue === $value ? ' selected' : '';
            $html .= '<option value="' . Security::e((string)$choiceValue) . '"' . $selected . '>' . Security::e((string)$choiceLabel) . '</option>';
        }
        return $html . '</select>';
    }

    if ($type === 'json') {
        return '<textarea class="form-textarea settings-json" data-setting-input name="' . $key . '" rows="5">' . Security::e($value) . '</textarea>';
    }

    $inputType = $isSensitive ? 'password' : match ($type) {
        'number' => 'number',
        'time' => 'time',
        'email' => 'email',
        'url' => 'url',
        default => 'text',
    };
    $attrs = '';
    foreach (['min', 'max', 'step'] as $attr) {
        if (isset($options[$attr])) {
            $attrs .= ' ' . $attr . '="' . Security::e((string)$options[$attr]) . '"';
        }
    }
    $suffix = isset($options['suffix']) ? '<span class="settings-suffix">' . Security::e((string)$options['suffix']) . '</span>' : '';
    $sensitiveAttrs = $isSensitive
        ? ' data-setting-sensitive="1" autocomplete="new-password" placeholder="' . Security::e(!empty($setting['has_value']) ? 'Saved value is hidden. Leave blank to keep it.' : 'Not set. Paste a key to save.') . '"'
        : '';
    return '<div class="settings-input-wrap"><input class="form-input" type="' . $inputType . '" data-setting-input name="' . $key . '" value="' . Security::e($value) . '"' . $attrs . $sensitiveAttrs . '>' . $suffix . '</div>';
}

function settings_display_value(string $value, string $type, bool $isSensitive = false): string
{
    if ($isSensitive) {
        return $value !== '' ? 'Saved value hidden' : 'No default';
    }

    if ($type === 'boolean') {
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true) ? 'Enabled' : 'Disabled';
    }
    return $value;
}
