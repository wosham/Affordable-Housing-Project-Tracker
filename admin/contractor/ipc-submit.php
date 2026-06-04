<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('contractor'));

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$requestedProjectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectId = ContractorIPC::defaultProjectId($userId, $role, $requestedProjectId);
$projects = ContractorIPC::projects($userId, $role);
$summary = $projectId > 0 ? ContractorIPC::projectSummary($projectId, $userId, $role) : [];
$project = $summary['project'] ?? null;
$boqLines = $projectId > 0 ? ContractorIPC::boqLines($projectId, $userId, $role) : [];

$pageTitle = 'Submit IPC';
$pageDescription = 'Prepare and submit interim payment claims from approved BOQ records.';
$adminRole = 'contractor';
$contentClass = 'contractor-ipc-page';
$componentCss = ['contractor-ipc'];
$pageScripts = ['ipc-form'];
$csrfForm = 'contractor_ipc';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Submit IPC'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-ipc-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Contractor claim</span>
    <h2>Submit IPC</h2>
    <p>Prepare a payment claim from measured BOQ quantities and send it for site verification.</p>
  </div>
  <div class="contractor-ipc-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/ipc-history.php')) ?>"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> IPC History</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/boq.php')) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i> BOQ</a>
  </div>
</section>

<?php if (!$project): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Assigned projects will appear here once configured.</span></div>
<?php else: ?>

<form class="contractor-ipc-layout" data-ipc-form action="<?= Security::e(Url::to('api/ipcs/submit.php')) ?>">
  <input type="hidden" name="csrf_form" value="contractor_ipc">
  <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">

  <main class="contractor-ipc-main">
    <section class="card contractor-ipc-panel">
      <div class="card__header">
        <div>
          <h2 class="card__title">Claim Details</h2>
          <p class="card__subtitle">Select the assigned project, claim period and contractor reference.</p>
        </div>
        <span class="badge badge--info">IPC #<?= (int)($summary['next_ipc_number'] ?? 1) ?></span>
      </div>
      <div class="contractor-ipc-fields">
        <label><span>Project</span>
          <select name="project_selector" data-project-jump>
            <?php foreach ($projects as $option): ?>
              <option value="<?= Security::e(Url::to('admin/contractor/ipc-submit.php?project_id=' . (int)$option['id'])) ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label><span>Contractor reference</span><input type="text" name="contractor_reference" maxlength="100" placeholder="Example: IPC-MAI-2026-01"></label>
        <label><span>Period from</span><input type="date" name="period_from" required></label>
        <label><span>Period to</span><input type="date" name="period_to" required></label>
      </div>
    </section>

    <section class="card contractor-ipc-panel">
      <div class="card__header">
        <div>
          <h2 class="card__title">BOQ Claim Lines</h2>
          <p class="card__subtitle">Enter this period's measured quantity. The system prevents claiming above the remaining BOQ quantity.</p>
        </div>
        <span class="badge badge--success"><?= count($boqLines) ?> items</span>
      </div>
      <div class="contractor-ipc-table-wrap">
        <table class="contractor-ipc-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Description</th>
              <th>BOQ Qty</th>
              <th>Previously Claimed</th>
              <th>This Claim</th>
              <th>Remaining</th>
              <th>Rate</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($boqLines === []): ?>
            <tr><td colspan="8"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No BOQ items found</strong><span class="empty-state__text">BOQ records are required before submitting an IPC.</span></div></td></tr>
          <?php endif; ?>
          <?php foreach ($boqLines as $index => $line): ?>
            <?php
              $qty = (float)($line['quantity'] ?? 0);
              $previous = (float)($line['previously_claimed_qty'] ?? 0);
              $remaining = max(0, $qty - $previous);
              $rate = (float)($line['rate'] ?? 0);
            ?>
            <tr data-ipc-line data-remaining="<?= Security::e((string)$remaining) ?>" data-rate="<?= Security::e((string)$rate) ?>">
              <td>
                <strong><?= Security::e($line['item_no']) ?></strong>
                <small><?= Security::e($line['section']) ?></small>
                <input type="hidden" name="lines[<?= (int)$index ?>][boq_item_id]" value="<?= (int)$line['id'] ?>">
                <input type="hidden" name="lines[<?= (int)$index ?>][description]" value="<?= Security::e($line['description']) ?>">
              </td>
              <td><?= Security::e(safe_truncate((string)$line['description'], 110)) ?><small><?= Security::e($line['unit']) ?></small></td>
              <td><?= number_format($qty, 3) ?></td>
              <td><?= number_format($previous, 3) ?></td>
              <td><input class="contractor-ipc-qty" type="number" name="lines[<?= (int)$index ?>][qty_this_period]" min="0" max="<?= Security::e((string)$remaining) ?>" step="0.001" placeholder="0.000"></td>
              <td data-ipc-remaining><?= number_format($remaining, 3) ?></td>
              <td>KES <?= number_format($rate, 2) ?></td>
              <td><strong data-ipc-line-amount>KES 0.00</strong></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card contractor-ipc-panel">
      <label class="contractor-ipc-declaration">
        <input type="checkbox" name="declaration_accepted" value="1" required>
        <span>I confirm that the claimed quantities are supported by site records, measured work and the project BOQ.</span>
      </label>
      <div class="contractor-ipc-submit-row">
        <span data-ipc-status></span>
        <button class="btn btn--primary" type="submit" <?= $boqLines === [] ? 'disabled' : '' ?>><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit IPC</button>
      </div>
    </section>
  </main>

  <aside class="contractor-ipc-side">
    <section class="card contractor-ipc-summary">
      <h2>Claim Summary</h2>
      <dl>
        <div><dt>Contract sum</dt><dd>KES <?= number_format((float)($project['contract_sum'] ?? 0), 2) ?></dd></div>
        <div><dt>BOQ value</dt><dd>KES <?= number_format((float)($summary['boq_value'] ?? 0), 2) ?></dd></div>
        <div><dt>Certified to date</dt><dd>KES <?= number_format((float)($summary['certified_value'] ?? 0), 2) ?></dd></div>
        <div><dt>Gross claim</dt><dd>KES <span data-ipc-total>0.00</span></dd></div>
        <div><dt>Retention</dt><dd>KES <span data-ipc-retention>0.00</span></dd></div>
        <div><dt>Net payable</dt><dd>KES <span data-ipc-net>0.00</span></dd></div>
      </dl>
    </section>
    <section class="card contractor-ipc-summary">
      <h2>Workflow</h2>
      <ol class="contractor-ipc-flow">
        <li>Contractor submits</li>
        <li>Clerk verifies</li>
        <li>Consultant certifies</li>
        <li>Manager endorses</li>
        <li>Director approves</li>
        <li>Finance pays</li>
      </ol>
    </section>
  </aside>
</form>

<?php endif; ?>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
