<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_ipcs';
$id = max(0, Security::cleanInt($_GET['id'] ?? 0));
$ipc = $id > 0 ? IPC::findDetailed($id) : null;

if (!$ipc) {
    Session::flash('error', 'IPC could not be found.');
    Response::redirect(Url::to('admin/superadmin/ipcs.php'));
}

$lines = IPC::lineItems($id);
$lineTotals = IPC::lineTotals($id);
$timeline = IPCApproval::timeline($id);
$workflow = IPC::workflowState($ipc);
$warnings = IPC::warnings($ipc);

$pageTitle = 'IPC #' . $ipc['ipc_number'];
$pageDescription = 'IPC certificate detail, line items, workflow trail and decision controls.';
$adminRole = 'superadmin';
$contentClass = 'sa-ipc-page sa-ipc-detail-page';
$componentCss = ['ipc-centre'];
$pageScripts = ['ipc-centre'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'IPC Centre', 'url' => Url::to('admin/superadmin/ipcs.php')],
    ['label' => 'IPC #' . $ipc['ipc_number']],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card ipc-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> IPC Detail</span>
    <h2>IPC #<?= Security::e((string)$ipc['ipc_number']) ?> - <?= Security::e($ipc['project_name']) ?></h2>
    <p><?= Security::e($ipc['contractor_name']) ?> Â· <?= Security::e(format_date($ipc['period_from'])) ?> to <?= Security::e(format_date($ipc['period_to'])) ?></p>
  </div>
  <div class="ipc-action-bar">
    <span class="badge <?= Security::e(status_badge_class($ipc['status'])) ?>"><?= Security::e(status_label($ipc['status'])) ?></span>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/ipcs.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back</a>
<?php if (IPC::canApprove($ipc)): ?>
    <button class="btn btn--primary" type="button" data-ipc-action="approve" data-ipc-id="<?= (int)$ipc['id'] ?>" data-ipc-title="IPC #<?= Security::e((string)$ipc['ipc_number']) ?>" data-ipc-summary="<?= Security::e($ipc['project_name'] . ' - ' . format_money($ipc['net_amount'])) ?>"><i class="fa-solid fa-check" aria-hidden="true"></i> Approve</button>
<?php endif; ?>
<?php if (IPC::canReject($ipc)): ?>
    <button class="btn btn--danger" type="button" data-ipc-action="reject" data-ipc-id="<?= (int)$ipc['id'] ?>" data-ipc-title="IPC #<?= Security::e((string)$ipc['ipc_number']) ?>" data-ipc-summary="<?= Security::e($ipc['project_name']) ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Reject</button>
<?php endif; ?>
  </div>
</section>

<section class="stat-grid stat-grid--4 ipc-stats">
  <?php ipc_detail_stat('fa-sack-dollar', format_money($ipc['gross_amount']), 'Gross Amount', 'Claimed value'); ?>
  <?php ipc_detail_stat('fa-shield-halved', format_money($ipc['retention_amount']), 'Retention', 'Held from gross'); ?>
  <?php ipc_detail_stat('fa-money-check-dollar', format_money($ipc['net_amount']), 'Net Payable', 'After retention'); ?>
  <?php ipc_detail_stat('fa-list-check', format_number($lineTotals['line_count'] ?? 0), 'Line Items', format_money($lineTotals['total_amount'] ?? 0) . ' line total'); ?>
</section>

<section class="card ipc-status-pipeline">
  <div class="card__header"><div><h2 class="card__title">Workflow Status</h2><p class="card__subtitle">Submission through final payment.</p></div></div>
  <div class="ipc-pipeline">
<?php foreach (IPC::statusFlow() as $status => $step): ?>
<?php
    $state = ipc_pipeline_state($workflow['current_step'], (int)$step['step'], (string)$ipc['status']);
    $stepTip = $step['label'] . ' | Step ' . $step['step'] . ' of ' . count(IPC::statusFlow()) . ' | ' . ucfirst(str_replace('is-', '', $state));
?>
    <div class="ipc-pipeline-step <?= Security::e($state) ?>" data-chart-tip="<?= Security::e($stepTip) ?>" title="<?= Security::e($stepTip) ?>"><span><?= Security::e((string)$step['step']) ?></span><strong><?= Security::e($step['label']) ?></strong></div>
<?php endforeach; ?>
  </div>
</section>

<?php if ($warnings !== []): ?>
<section class="card ipc-warning-panel">
  <div class="card__header"><div><h2 class="card__title">Review Warnings</h2><p class="card__subtitle">Resolve or acknowledge before final payment decisions.</p></div></div>
  <ul>
<?php foreach ($warnings as $warning): ?>
    <li><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> <?= Security::e($warning) ?></li>
<?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="ipc-detail-grid">
  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Project & Contractor</h2><p class="card__subtitle">Certificate ownership and site context.</p></div></div>
    <dl class="ipc-dl">
      <div><dt>Project</dt><dd><?= Security::e($ipc['project_name']) ?></dd></div>
      <div><dt>Constituency</dt><dd><?= Security::e($ipc['constituency_name'] ?: '-') ?></dd></div>
      <div><dt>Ward</dt><dd><?= Security::e($ipc['ward_name'] ?: '-') ?></dd></div>
      <div><dt>Contractor</dt><dd><?= Security::e($ipc['contractor_name']) ?></dd></div>
      <div><dt>Email</dt><dd><?= Security::e($ipc['contractor_email']) ?></dd></div>
      <div><dt>Contract Sum</dt><dd><?= Security::e(format_money($ipc['contract_sum'])) ?></dd></div>
      <div><dt>Project Progress</dt><dd><?= Security::e(format_percentage($ipc['pct_complete'])) ?></dd></div>
    </dl>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Certificate Dates</h2><p class="card__subtitle">Submission and decision timestamps.</p></div></div>
    <dl class="ipc-dl">
      <div><dt>Period From</dt><dd><?= Security::e(format_date($ipc['period_from'])) ?></dd></div>
      <div><dt>Period To</dt><dd><?= Security::e(format_date($ipc['period_to'])) ?></dd></div>
      <div><dt>Submitted</dt><dd><?= Security::e(format_datetime($ipc['submitted_at'])) ?></dd></div>
      <div><dt>Certified</dt><dd><?= Security::e(format_datetime($ipc['certified_at'])) ?></dd></div>
      <div><dt>Approved</dt><dd><?= Security::e(format_datetime($ipc['approved_at'])) ?></dd></div>
      <div><dt>Paid</dt><dd><?= Security::e(format_datetime($ipc['paid_at'])) ?></dd></div>
<?php if ((string)$ipc['status'] === 'rejected'): ?>
      <div><dt>Rejected</dt><dd><?= Security::e(format_datetime($ipc['rejected_at'] ?? null)) ?></dd></div>
      <div><dt>Reason</dt><dd><?= Security::e($ipc['rejection_reason'] ?? '-') ?></dd></div>
<?php endif; ?>
    </dl>
  </article>
</section>

<section class="card ipc-timeline-card">
  <div class="card__header"><div><h2 class="card__title">Approval Trail</h2><p class="card__subtitle">Every recorded decision on this IPC.</p></div></div>
  <div class="ipc-timeline">
<?php if ($timeline === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><strong class="empty-state__title">No approval records yet</strong></div>
<?php endif; ?>
<?php foreach ($timeline as $item): ?>
    <article class="ipc-timeline-item">
      <span class="badge <?= Security::e(status_badge_class($item['action'])) ?>"><?= Security::e(status_label($item['action'])) ?></span>
      <div><strong><?= Security::e($item['actor_name']) ?></strong><small><?= Security::e($item['actor_role'] ?: role_label($item['actor_role_slug'])) ?> Â· <?= Security::e(format_datetime($item['actioned_at'])) ?></small><?php if ($item['comments']): ?><p><?= Security::e($item['comments']) ?></p><?php endif; ?></div>
    </article>
<?php endforeach; ?>
  </div>
</section>

<section class="card ipc-line-items">
  <div class="card__header"><div><h2 class="card__title">IPC Line Items</h2><p class="card__subtitle">Measured quantities and payable amounts.</p></div><span class="badge badge--lime"><?= Security::e(format_money($lineTotals['total_amount'] ?? 0)) ?> total</span></div>
  <div class="table-wrap">
    <table class="data-table ipc-table">
      <thead><tr><th>#</th><th>BOQ Item</th><th>Description</th><th class="is-money">Qty This Period</th><th class="is-money">Cumulative Qty</th><th>Unit</th><th class="is-money">Rate</th><th class="is-money">Amount</th></tr></thead>
      <tbody>
<?php if ($lines === []): ?>
        <tr><td colspan="8">No line items recorded.</td></tr>
<?php endif; ?>
<?php foreach ($lines as $index => $line): ?>
        <tr><td><?= $index + 1 ?></td><td><?= Security::e($line['item_code'] ?: '-') ?></td><td><?= Security::e($line['description']) ?></td><td class="is-money"><?= Security::e(format_number($line['qty_this_period'], 3)) ?></td><td class="is-money"><?= Security::e(format_number($line['cumulative_qty'], 3)) ?></td><td><?= Security::e($line['unit'] ?: '-') ?></td><td class="is-money"><?= Security::e(format_money($line['rate'])) ?></td><td class="is-money"><strong><?= Security::e(format_money($line['amount'])) ?></strong></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?= ipc_decision_modal($csrfForm) ?>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function ipc_detail_stat(string $icon, string $value, string $label, string $sub): void
{
    ?>
    <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e($value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($sub) ?></small></span></article>
    <?php
}

function ipc_pipeline_state(int $currentStep, int $step, string $status): string
{
    if ($status === 'rejected') {
        return 'is-muted';
    }
    if ($currentStep > $step) {
        return 'is-complete';
    }
    if ($currentStep === $step) {
        return 'is-current';
    }
    return '';
}

function ipc_decision_modal(string $csrfForm): string
{
    ob_start();
    ?>
    <div class="modal-backdrop" data-ipc-backdrop></div>
    <div class="modal ipc-decision-modal" id="ipcDecisionModal" aria-hidden="true">
      <div class="modal__dialog">
        <form data-ipc-decision-form>
          <div class="modal__header"><h2 class="modal__title" data-ipc-modal-title>Record IPC decision</h2><button class="modal__close" type="button" data-ipc-modal-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
          <div class="modal__body">
            <input type="hidden" name="<?= Security::e(Csrf::tokenName()) ?>" value="<?= Security::e(Csrf::token($csrfForm)) ?>">
            <input type="hidden" name="ipc_id" data-ipc-modal-id>
            <input type="hidden" name="mode" data-ipc-modal-mode>
            <div class="alert alert--info" data-ipc-modal-summary>Confirm the IPC decision.</div>
            <label class="form-field"><span class="form-label" data-ipc-comment-label>Decision note</span><textarea class="form-textarea" name="comment" rows="5" data-ipc-comment></textarea></label>
            <div class="alert alert--danger" data-ipc-error hidden></div>
          </div>
          <div class="modal__footer"><button class="btn btn--outline" type="button" data-ipc-modal-close>Cancel</button><button class="btn btn--primary" type="submit" data-ipc-submit>Submit decision</button></div>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
