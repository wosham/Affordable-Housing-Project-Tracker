<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('consultant'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$ipcId = Security::cleanInt($_GET['id'] ?? $_GET['ipc_id'] ?? 0);
if ($ipcId <= 0) {
    $candidate = ConsultantIPC::items($userId, $role, ['status' => ConsultantIPC::CERTIFIABLE_STATUS], 1, 0);
    if ($candidate === []) {
        $candidate = ConsultantIPC::items($userId, $role, [], 1, 0);
    }
    if ($candidate !== []) {
        Response::redirect(Url::to('admin/consultant/ipc-certify.php?id=' . (int)$candidate[0]['id']));
    }
    Response::redirect(Url::to('admin/consultant/ipc-inbox.php'));
}

$ipc = ConsultantIPC::findForUser($ipcId, $userId, $role);
if (!$ipc) {
    Response::redirect(Url::to('admin/consultant/ipc-inbox.php'));
}

$lines = ConsultantIPC::lineItems($ipcId);
$history = ConsultantIPC::approvalHistory($ipcId);
$warnings = ConsultantIPC::warnings($ipc, $lines);
$hasCriticalWarnings = ConsultantIPC::hasCriticalWarnings($warnings);
$reviewMetrics = ConsultantIPC::reviewMetrics($ipc, $lines, $warnings);
$checklist = ConsultantIPC::certificationChecklist();
$lineTotal = array_reduce($lines, static fn (float $sum, array $line): float => $sum + (float)($line['amount'] ?? 0), 0.0);
$canCertify = (string)$ipc['status'] === ConsultantIPC::CERTIFIABLE_STATUS && !$hasCriticalWarnings;
$canReject = in_array((string)$ipc['status'], ['submitted', 'clerk-endorsed'], true);

$pageTitle = 'Certify IPC';
$pageDescription = 'Review line items, warnings and certification history before taking action.';
$adminRole = 'consultant';
$csrfForm = 'consultant_ipc';
$contentClass = 'consultant-ipc-page consultant-ipc-detail-page';
$componentCss = ['consultant-ipc'];
$pageScripts = ['consultant-ipc'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'IPC Inbox', 'url' => Url::to('admin/consultant/ipc-inbox.php')],
    ['label' => 'IPC #' . format_number($ipc['ipc_number'])],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="consultant-ipc-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-stamp" aria-hidden="true"></i> Certification workspace</span>
    <h2>IPC #<?= Security::e(format_number($ipc['ipc_number'])) ?></h2>
    <p><?= Security::e($ipc['project_name']) ?> / <?= Security::e($ipc['contractor_name']) ?> / <?= Security::e(format_date($ipc['period_from'])) ?> to <?= Security::e(format_date($ipc['period_to'])) ?></p>
  </div>
  <div class="consultant-ipc-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/ipc-inbox.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Inbox</a>
    <button class="btn btn--outline" type="button" data-consultant-ipc-print><i class="fa-solid fa-print" aria-hidden="true"></i> Print</button>
  </div>
</section>

<section class="consultant-ipc-detail-grid">
  <article class="card consultant-ipc-certificate" data-consultant-ipc-print-area>
    <div class="card__header">
      <div>
        <h2 class="card__title">Certificate Review</h2>
        <p class="card__subtitle">Confirm quantities, amounts and approval history before certification.</p>
      </div>
      <span class="badge <?= Security::e(status_badge_class($ipc['status'])) ?>"><?= Security::e(status_label($ipc['status'])) ?></span>
    </div>

    <div class="consultant-ipc-money-grid">
      <span><small>Gross</small><strong><?= Security::e(format_money($ipc['gross_amount'])) ?></strong></span>
      <span><small>Retention</small><strong><?= Security::e(format_money($ipc['retention_amount'])) ?></strong></span>
      <span><small>Net</small><strong><?= Security::e(format_money($ipc['net_amount'])) ?></strong></span>
      <span><small>Line Total</small><strong><?= Security::e(format_money($lineTotal)) ?></strong></span>
      <span><small>Contract Sum</small><strong><?= Security::e(format_money($ipc['contract_sum'])) ?></strong></span>
      <span><small>Project Progress</small><strong><?= Security::e(format_number(percentage($ipc['pct_complete'] ?? 0))) ?>%</strong></span>
    </div>

    <div class="consultant-ipc-review-grid" aria-label="Certification review checks">
      <span><small>Review Lines</small><strong><?= Security::e(format_number($reviewMetrics['line_count'])) ?></strong></span>
      <span class="<?= (float)$reviewMetrics['gross_variance'] > 1 ? 'is-risk' : '' ?>"><small>Gross Variance</small><strong><?= Security::e(format_money($reviewMetrics['gross_variance'])) ?></strong></span>
      <span class="<?= (int)$reviewMetrics['over_boq'] > 0 ? 'is-risk' : '' ?>"><small>Over BOQ</small><strong><?= Security::e(format_number($reviewMetrics['over_boq'])) ?></strong></span>
      <span class="<?= (int)$reviewMetrics['warning_count'] > 0 ? 'is-risk' : '' ?>"><small>Warnings</small><strong><?= Security::e(format_number($reviewMetrics['warning_count'])) ?></strong></span>
    </div>

    <ol class="consultant-ipc-stepper">
<?php foreach (IPC::statusFlow() as $status => $step): ?>
      <li class="<?= (int)$ipc['workflow']['current_step'] >= (int)$step['step'] ? 'is-done' : '' ?>"><span><?= (int)$step['step'] ?></span><?= Security::e($step['label']) ?></li>
<?php endforeach; ?>
    </ol>

    <div class="consultant-ipc-meta-grid">
      <span><small>Submitted</small><strong><?= Security::e(format_date($ipc['submitted_at'] ?? null)) ?></strong></span>
      <span><small>Last action</small><strong><?= Security::e(status_label($ipc['last_action'] ?: $ipc['status'])) ?></strong></span>
      <span><small>Action by</small><strong><?= Security::e($ipc['last_action_by'] ?: 'System') ?></strong></span>
      <span><small>Ward</small><strong><?= Security::e($ipc['ward_name'] ?: '-') ?></strong></span>
    </div>

<?php if ($warnings !== []): ?>
    <div class="consultant-ipc-warning-panel <?= $hasCriticalWarnings ? 'is-critical' : '' ?>">
      <h3><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Review warnings</h3>
      <ul><?php foreach ($warnings as $warning): ?><li><?= Security::e($warning) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

    <div class="table-wrap">
      <table class="data-table consultant-ipc-lines">
        <thead><tr><th>BOQ Item</th><th>Description</th><th class="is-num">Previous</th><th class="is-num">This Period</th><th class="is-num">Cumulative</th><th class="is-num">BOQ Qty</th><th class="is-num">Remaining</th><th class="is-num">Rate</th><th class="is-num">Amount</th></tr></thead>
        <tbody>
<?php if ($lines === []): ?>
          <tr><td colspan="9"><div class="empty-state"><strong class="empty-state__title">No line items recorded</strong><span class="empty-state__text">This IPC cannot be certified until line items are available.</span></div></td></tr>
<?php else: foreach ($lines as $line): ?>
          <tr class="<?= !empty($line['over_boq']) || abs((float)($line['amount_variance'] ?? 0)) > 1 ? 'is-warning' : '' ?>">
            <td><strong><?= Security::e($line['item_no'] ?: '-') ?></strong><small><?= Security::e($line['section'] ?: 'General') ?></small></td>
            <td><?= Security::e(safe_truncate($line['description'] ?? '', 130)) ?></td>
            <td class="is-num"><?= Security::e(format_number($line['previous_certified_qty'] ?? 0)) ?></td>
            <td class="is-num"><?= Security::e(format_number($line['qty_this_period'])) ?></td>
            <td class="is-num"><?= Security::e(format_number($line['cumulative_qty'])) ?></td>
            <td class="is-num"><?= Security::e(format_number($line['boq_quantity'] ?? 0)) ?></td>
            <td class="is-num"><?= Security::e(format_number($line['remaining_qty'] ?? 0)) ?></td>
            <td class="is-num"><?= Security::e(format_money($line['rate'])) ?></td>
            <td class="is-num"><strong><?= Security::e(format_money($line['amount'])) ?></strong><?php if (abs((float)($line['amount_variance'] ?? 0)) > 1): ?><small class="consultant-ipc-warning">Variance <?= Security::e(format_money($line['amount_variance'])) ?></small><?php endif; ?></td>
          </tr>
<?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <aside class="consultant-ipc-action-stack">
    <article class="card consultant-ipc-action-card">
      <div class="card__header"><div><h2 class="card__title">Consultant Action</h2><p class="card__subtitle">Certify or return this IPC.</p></div></div>
      <form data-consultant-ipc-certify-form>
        <input type="hidden" name="ipc_id" value="<?= (int)$ipc['id'] ?>">
        <?php foreach ($checklist as $value => $label): ?>
        <label class="consultant-ipc-check"><input type="checkbox" name="checklist[]" value="<?= Security::e($value) ?>" required> <?= Security::e($label) ?></label>
        <?php endforeach; ?>
        <label class="form-field"><span>Certification comment</span><textarea class="form-control" name="comment" rows="5" placeholder="Add certification notes for the approval trail."></textarea></label>
        <?php if ((string)$ipc['status'] !== ConsultantIPC::CERTIFIABLE_STATUS): ?><p class="consultant-ipc-critical-note">This IPC is currently <?= Security::e(status_label($ipc['status'])) ?> and cannot be certified from this step.</p><?php endif; ?>
        <?php if ($hasCriticalWarnings): ?><p class="consultant-ipc-critical-note">Resolve critical warnings before certifying this IPC.</p><?php endif; ?>
        <div class="consultant-ipc-action-buttons">
          <button class="btn btn--success" type="submit" <?= $canCertify ? '' : 'disabled' ?>><i class="fa-solid fa-stamp" aria-hidden="true"></i> Certify IPC</button>
          <?php if ($canReject): ?><button class="btn btn--danger" type="button" data-consultant-ipc-reject data-ipc-id="<?= (int)$ipc['id'] ?>" data-ipc-label="IPC #<?= Security::e(format_number($ipc['ipc_number'])) ?>"><i class="fa-solid fa-ban" aria-hidden="true"></i> Reject</button><?php endif; ?>
        </div>
      </form>
    </article>

    <article class="card">
      <div class="card__header"><div><h2 class="card__title">Approval History</h2><p class="card__subtitle">Recorded actions for this IPC.</p></div></div>
      <div class="consultant-ipc-history">
<?php if ($history === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No actions recorded</strong></div>
<?php else: foreach ($history as $entry): ?>
        <div class="consultant-ipc-history__item">
          <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
          <div><strong><?= Security::e(status_label($entry['action'])) ?></strong><small><?= Security::e($entry['actor_name']) ?> / <?= Security::e($entry['actor_role'] ?: 'Staff') ?> / <?= Security::e(format_date($entry['actioned_at'])) ?></small><?php if (!empty($entry['comments'])): ?><p><?= Security::e($entry['comments']) ?></p><?php endif; ?></div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </article>
  </aside>
</section>

<div class="consultant-ipc-modal" data-consultant-ipc-modal hidden>
  <form class="consultant-ipc-modal__panel" data-consultant-ipc-reject-form>
    <div class="consultant-ipc-modal__header">
      <div><span class="sa-panel-label">Return IPC</span><h2>Reject IPC</h2></div>
      <button class="btn btn--icon btn--ghost" type="button" data-consultant-ipc-close><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="consultant-ipc-modal__body">
      <input type="hidden" name="ipc_id" data-consultant-ipc-reject-id value="<?= (int)$ipc['id'] ?>">
      <p data-consultant-ipc-reject-label>Record why IPC #<?= Security::e(format_number($ipc['ipc_number'])) ?> is being returned.</p>
      <label class="form-field"><span>Reason *</span><textarea class="form-control" name="reason" rows="5" required placeholder="Explain what must be corrected before certification."></textarea></label>
    </div>
    <div class="consultant-ipc-modal__footer">
      <button class="btn btn--outline" type="button" data-consultant-ipc-close>Cancel</button>
      <button class="btn btn--danger" type="submit"><i class="fa-solid fa-ban" aria-hidden="true"></i> Reject IPC</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
