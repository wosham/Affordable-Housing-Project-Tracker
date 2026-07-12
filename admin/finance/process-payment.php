<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('finance');

$selectedIpcId = Security::cleanInt($_GET['ipc_id'] ?? 0);
$payables = FinancePayment::payableOptions();
$selected = $selectedIpcId > 0 ? FinancePayment::findPayable($selectedIpcId) : ($payables[0] ?? null);
$recentPayments = FinancePayment::recentPayments(8);
$stats = FinancePayment::summary();

$pageTitle = 'Process Payment';
$pageDescription = 'Record a verified IPC payment and update the payment register.';
$adminRole = 'finance';
$csrfForm = 'finance_payments';
$contentClass = 'finance-payments-page finance-process-page';
$componentCss = ['finance-payments', 'media-library'];
$pageScripts = ['media-picker', 'finance-payments'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance', 'url' => Url::to('admin/finance/dashboard.php')],
    ['label' => 'Process Payment'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="finance-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i> Payment workspace</span>
    <h2>Process Payment</h2>
    <p>Record payment details for an approved IPC and keep the project payment history current.</p>
  </div>
  <div class="finance-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Approved IPCs</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
  </div>
</section>

<section class="finance-stat-grid finance-stat-grid--4" aria-label="Finance payment summary">
  <?php finance_process_stat('fa-file-circle-check', $stats['approved_count'], 'Ready to Pay', 'Approved certificates', 'admin/finance/approved-ipcs.php'); ?>
  <?php finance_process_stat('fa-money-bill-transfer', format_money($stats['approved_value']), 'Net Payable', 'Available for payment', 'admin/finance/approved-ipcs.php'); ?>
  <?php finance_process_stat('fa-calendar-check', $stats['paid_month_count'], 'Paid This Month', format_money($stats['paid_month_value']), 'admin/finance/process-payment.php'); ?>
  <?php finance_process_stat('fa-shield-halved', format_money($stats['retention_value']), 'Retention Held', 'Tracked separately', 'admin/finance/retention.php'); ?>
</section>

<nav class="finance-hub card" aria-label="Finance modules">
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check"></i><span>Approved IPCs</span></a>
  <a class="finance-hub__link is-active" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar"></i><span>Process Payment</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>"><i class="fa-solid fa-chart-column"></i><span>Budget</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>"><i class="fa-solid fa-lock"></i><span>Retention</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/liquidated-damages.php')) ?>"><i class="fa-solid fa-money-bill-trend-up"></i><span>Damages</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i><span>Reports</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/messages.php')) ?>"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
</nav>

<section class="finance-process-layout">
  <div class="finance-process-main">
    <section class="card finance-card finance-payment-form-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">Payment Details</h2>
          <p class="card__subtitle">Confirm the payable certificate before saving the payment.</p>
        </div>
        <?php if ($selected): ?><span class="badge badge--success">IPC #<?= Security::e($selected['ipc_number']) ?></span><?php endif; ?>
      </div>

<?php if ($payables === []): ?>
      <div class="empty-state">
        <span class="empty-state__icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
        <strong class="empty-state__title">No approved IPCs awaiting payment</strong>
        <span class="empty-state__text">New approved certificates will appear here when ready.</span>
      </div>
<?php else: ?>
      <form class="finance-payment-form" data-finance-payment-form>
        <input type="hidden" name="confirm_payment" value="1">
        <div class="finance-form-grid">
          <label class="form-field form-field--wide" for="ipc_id">
            <span class="form-label">Approved IPC *</span>
            <select class="form-select" id="ipc_id" name="ipc_id" data-ipc-select required>
<?php foreach ($payables as $ipc): ?>
              <option value="<?= (int)$ipc['id'] ?>"
                data-net="<?= Security::e((string)round((float)$ipc['outstanding_amount'], 2)) ?>"
                data-gross="<?= Security::e((string)round((float)$ipc['gross_amount'], 2)) ?>"
                data-retention="<?= Security::e((string)round((float)$ipc['retention_amount'], 2)) ?>"
                data-ipc-number="<?= Security::e((string)$ipc['ipc_number']) ?>"
                data-project="<?= Security::e($ipc['project_name']) ?>"
                data-contractor="<?= Security::e($ipc['contractor_name']) ?>"
                data-approved="<?= Security::e(format_date($ipc['approved_at'] ?? null)) ?>"
                <?= $selected && (int)$selected['id'] === (int)$ipc['id'] ? 'selected' : '' ?>>
                IPC #<?= Security::e($ipc['ipc_number']) ?> - <?= Security::e($ipc['project_name']) ?> - <?= Security::e(format_money((float)$ipc['outstanding_amount'])) ?>
              </option>
<?php endforeach; ?>
            </select>
          </label>
          <label class="form-field" for="amount"><span class="form-label">Payment amount * (must match net payable)</span><input class="form-input" type="number" id="amount" name="amount" min="0" step="0.01" value="<?= Security::e($selected ? (string)round((float)$selected['outstanding_amount'], 2) : '') ?>" required data-amount-input></label>
          <label class="form-field" for="payment_date"><span class="form-label">Payment date *</span><input class="form-input" type="date" id="payment_date" name="payment_date" value="<?= Security::e(date('Y-m-d')) ?>" max="<?= Security::e(date('Y-m-d', strtotime('+1 day'))) ?>" required></label>
          <label class="form-field" for="reference_no"><span class="form-label">Payment reference *</span><input class="form-input" type="text" id="reference_no" name="reference_no" maxlength="100" placeholder="Bank, IFMIS or treasury reference" required></label>
          <label class="form-field" for="voucher_no"><span class="form-label">Voucher number</span><input class="form-input" type="text" id="voucher_no" name="voucher_no" maxlength="100" placeholder="Optional voucher number"></label>
          <label class="form-field" for="payment_method"><span class="form-label">Payment method</span><select class="form-select" id="payment_method" name="payment_method"><option value="bank_transfer">Bank transfer</option><option value="treasury_transfer">Treasury transfer</option><option value="cheque">Cheque</option><option value="mobile_money">Mobile money</option></select></label>
          <label class="form-field" for="bank"><span class="form-label">Bank / account note</span><input class="form-input" type="text" id="bank" name="bank" maxlength="150" placeholder="Bank or receiving account note"></label>
          <div class="form-field form-field--wide">
            <span class="form-label">Receipt / voucher file</span>
            <div class="finance-media-card" data-cms-upload data-upload-folder="finance-receipts" data-media-kind="document" data-finance-media>
              <div class="finance-media-card__preview" data-cms-asset-preview><span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span></div>
              <div class="finance-media-card__body">
                <strong data-cms-asset-name>No file selected</strong>
                <small>Optional. Choose from media library or upload a receipt/voucher.</small>
                <input type="hidden" name="receipt_path" value="" data-cms-upload-target data-media-picker-value="path">
                <div class="finance-media-card__actions">
                  <button class="btn btn--outline btn--sm" type="button" data-media-picker-open data-media-picker-folder="finance-receipts" data-media-picker-type="document" data-media-picker-accept="image/*,application/pdf,.pdf,.png,.jpg,.jpeg,.webp" data-media-picker-title="Choose receipt or voucher"><i class="fa-solid fa-photo-film"></i> Choose / upload</button>
                  <button class="btn btn--outline btn--sm" type="button" data-finance-media-clear><i class="fa-solid fa-xmark"></i> Clear</button>
                </div>
              </div>
            </div>
          </div>
          <label class="form-field form-field--wide" for="notes"><span class="form-label">Finance note</span><textarea class="form-textarea" id="notes" name="notes" rows="4" placeholder="Add payment remarks for the finance register."></textarea></label>
        </div>

        <div class="finance-confirm">
          <label class="finance-confirm__check"><input type="checkbox" name="confirm_review" value="1" required> <span>I have checked the payable amount, reference and payment date.</span></label>
          <p data-finance-form-status></p>
        </div>

        <div class="finance-form-actions">
          <button class="btn btn--outline" type="reset">Reset</button>
          <button class="btn btn--primary" type="submit"><i class="fa-solid fa-lock" aria-hidden="true"></i> Save Payment</button>
        </div>
      </form>
<?php endif; ?>
    </section>
  </div>

  <aside class="finance-process-side">
    <section class="card finance-card finance-selected-card" data-selected-card>
      <div class="card__header">
        <div>
          <h2 class="card__title">Selected IPC</h2>
          <p class="card__subtitle">Amounts and certificate context.</p>
        </div>
      </div>
      <div data-selected-body>
<?php if ($selected): ?>
        <?php finance_selected_ipc($selected); ?>
<?php else: ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No certificate selected</strong></div>
<?php endif; ?>
      </div>
    </section>

    <section class="card finance-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">Recent Payments</h2>
          <p class="card__subtitle">Latest payment records.</p>
        </div>
      </div>
      <div class="finance-mini-list">
<?php if ($recentPayments === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No payments yet</strong></div>
<?php else: foreach ($recentPayments as $payment): ?>
        <article class="finance-mini-item">
          <strong><?= Security::e($payment['reference_no'] ?: 'Payment') ?></strong>
          <span>IPC #<?= Security::e($payment['ipc_number']) ?> / <?= Security::e($payment['project_name']) ?></span>
          <em><?= Security::e(format_money((float)$payment['amount'])) ?></em>
        </article>
<?php endforeach; endif; ?>
      </div>
    </section>
  </aside>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function finance_process_stat(string $icon, mixed $value, string $label, string $hint, string $href = ''): void
{
    $url = $href !== '' ? Url::to($href) : '';
    $tag = $url !== '' ? 'a' : 'article';
    $hrefAttr = $url !== '' ? ' href="' . Security::e($url) . '"' : '';
?>
  <<?= $tag ?> class="finance-stat card<?= $url !== '' ? ' finance-stat--link' : '' ?>"<?= $hrefAttr ?>>
    <span class="finance-stat__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong><em><?= Security::e($label) ?></em><small><?= Security::e($hint) ?></small></span>
  </<?= $tag ?>>
<?php
}

function finance_selected_ipc(array $ipc): void
{
?>
  <div class="finance-selected">
    <strong>IPC #<?= Security::e($ipc['ipc_number']) ?></strong>
    <span><?= Security::e($ipc['project_name']) ?></span>
    <span><?= Security::e($ipc['contractor_name']) ?></span>
    <dl>
      <div><dt>Gross</dt><dd><?= Security::e(format_money((float)$ipc['gross_amount'])) ?></dd></div>
      <div><dt>Retention</dt><dd><?= Security::e(format_money((float)$ipc['retention_amount'])) ?></dd></div>
      <div><dt>Net payable</dt><dd><?= Security::e(format_money((float)$ipc['outstanding_amount'])) ?></dd></div>
      <div><dt>Approved</dt><dd><?= Security::e(format_date($ipc['approved_at'] ?? null)) ?></dd></div>
    </dl>
  </div>
<?php
}
