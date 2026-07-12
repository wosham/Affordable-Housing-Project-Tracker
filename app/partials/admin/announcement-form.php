<?php
/**
 * Shared staff announcement form fields.
 * Expects: $csrfForm, $values, $formAction, $submitLabel
 */
$values = is_array($values ?? null) ? $values : [];
$audienceMode = (string)($values['audience_mode'] ?? 'roles');
if (!in_array($audienceMode, ['all', 'roles'], true)) {
    $audienceMode = 'roles';
}
$targetRoles = is_array($values['target_roles'] ?? null) ? $values['target_roles'] : [];
$reachAll = Announcement::audienceReachCount(json_encode(['all']));
$reachSelected = Announcement::audienceReachCount(json_encode($targetRoles));
?>
<form class="form-grid sa-announcement-form" method="post" action="<?= Security::e($formAction) ?>" data-announcement-form>
  <?= Csrf::field($csrfForm) ?>
  <label class="form-field form-field--full"><span class="form-label">Title <strong>*</strong></span><input class="form-input" name="title" value="<?= Security::e((string)($values['title'] ?? '')) ?>" maxlength="255" required></label>
  <label class="form-field form-field--full"><span class="form-label">Message <strong>*</strong></span><textarea class="form-textarea" name="body" rows="8" required><?= Security::e((string)($values['body'] ?? '')) ?></textarea></label>
  <label class="form-field"><span class="form-label">Type</span><select class="form-select" name="type"><?php foreach (Announcement::TYPES as $type => $meta): ?><option value="<?= Security::e($type) ?>" <?= (($values['type'] ?? 'info') === $type) ? 'selected' : '' ?>><?= Security::e($meta['label']) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Priority</span><select class="form-select" name="priority"><?php foreach (Announcement::PRIORITIES as $priority): ?><option value="<?= Security::e($priority) ?>" <?= (($values['priority'] ?? 'normal') === $priority) ? 'selected' : '' ?>><?= Security::e(status_label($priority)) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><?php foreach (Announcement::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($values['status'] ?? 'draft') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Publish at</span><input class="form-input" type="datetime-local" name="published_at" value="<?= Security::e((string)($values['published_at'] ?? '')) ?>"></label>
  <label class="form-field"><span class="form-label">Expires at</span><input class="form-input" type="datetime-local" name="expires_at" value="<?= Security::e((string)($values['expires_at'] ?? '')) ?>"></label>
  <label class="form-field"><span class="form-label">CTA label</span><input class="form-input" name="cta_label" value="<?= Security::e((string)($values['cta_label'] ?? '')) ?>" maxlength="120"></label>
  <label class="form-field form-field--full"><span class="form-label">CTA URL</span><input class="form-input" name="cta_url" value="<?= Security::e((string)($values['cta_url'] ?? '')) ?>" maxlength="255" placeholder="admin/messages.php or https://example.com"></label>

  <fieldset class="form-field form-field--full sa-audience-fieldset">
    <legend class="form-label">Audience <strong>*</strong></legend>
    <p class="form-hint sa-audience-lead">Required for published notices. Staff only see announcements targeted to them (or All staff).</p>
    <div class="sa-audience-modes">
      <label class="sa-audience-mode">
        <input type="radio" name="audience_mode" value="all" data-audience-mode <?= $audienceMode === 'all' ? 'checked' : '' ?>>
        <span>
          <strong>All active staff</strong>
          <small>Every portal role · ~<?= (int)$reachAll ?> users</small>
        </span>
      </label>
      <label class="sa-audience-mode">
        <input type="radio" name="audience_mode" value="roles" data-audience-mode <?= $audienceMode === 'roles' ? 'checked' : '' ?>>
        <span>
          <strong>Selected roles only</strong>
          <small>Choose one or more roles below</small>
        </span>
      </label>
    </div>
    <div class="checkbox-grid sa-audience-roles" data-audience-roles <?= $audienceMode === 'all' ? 'hidden' : '' ?>>
<?php foreach (Announcement::ROLES as $role): ?>
      <label class="check-option"><input type="checkbox" name="target_roles[]" value="<?= Security::e($role) ?>" <?= in_array($role, $targetRoles, true) ? 'checked' : '' ?>> <span><?= Security::e(role_label($role)) ?></span></label>
<?php endforeach; ?>
    </div>
    <p class="form-hint" data-audience-reach>
<?php if ($audienceMode === 'all'): ?>
      Will reach approximately <strong><?= (int)$reachAll ?></strong> active staff when published.
<?php elseif ($targetRoles !== []): ?>
      Will reach approximately <strong><?= (int)$reachSelected ?></strong> active staff in selected roles.
<?php else: ?>
      Select at least one role, or switch to All active staff.
<?php endif; ?>
    </p>
  </fieldset>

  <label class="check-option form-field--full"><input type="checkbox" name="is_pinned" value="1" <?= !empty($values['is_pinned']) ? 'checked' : '' ?>> <span>Pin this announcement above regular notices</span></label>
  <label class="check-option form-field--full"><input type="checkbox" name="notify_again" value="1"> <span>Send / re-send in-app notifications to the target audience on save (published only)</span></label>
  <div class="form-actions form-field--full">
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= Security::e($submitLabel) ?></button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>">Cancel</a>
  </div>
</form>
<script>
(function () {
  var form = document.querySelector('[data-announcement-form]');
  if (!form) return;
  var rolesBox = form.querySelector('[data-audience-roles]');
  var reach = form.querySelector('[data-audience-reach]');
  var roleChecks = Array.prototype.slice.call(form.querySelectorAll('input[name="target_roles[]"]'));
  var reachAll = <?= (int)$reachAll ?>;
  function update() {
    var mode = (form.querySelector('input[name="audience_mode"]:checked') || {}).value || 'roles';
    if (rolesBox) rolesBox.hidden = mode === 'all';
    if (!reach) return;
    if (mode === 'all') {
      reach.innerHTML = 'Will reach approximately <strong>' + reachAll + '</strong> active staff when published.';
      return;
    }
    var n = roleChecks.filter(function (c) { return c.checked; }).length;
    if (n === 0) {
      reach.textContent = 'Select at least one role, or switch to All active staff.';
    } else {
      reach.innerHTML = 'Selected <strong>' + n + '</strong> role group(s). Exact headcount is calculated on the server when saved.';
    }
  }
  form.querySelectorAll('[data-audience-mode]').forEach(function (el) {
    el.addEventListener('change', update);
  });
  roleChecks.forEach(function (el) { el.addEventListener('change', update); });
  update();
})();
</script>
