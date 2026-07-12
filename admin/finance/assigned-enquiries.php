<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('finance');

$viewerId = (int)Auth::id();
$viewerRole = 'finance';
$isContactSupervisor = false;
$contactInboxPath = 'admin/finance/assigned-enquiries.php';
$dashboardPath = 'admin/finance/dashboard.php';
$dashboardLabel = 'Dashboard';

$pageTitle = 'Assigned Enquiries';
$pageDescription = 'Public contact enquiries assigned to you for follow-up and email reply.';
$adminRole = 'finance';
$csrfForm = 'contact_inbox';
$contentClass = 'sa-contact-inbox-page finance-payments-page finance-enquiries-page';
$componentCss = ['finance-payments', 'contact-inbox'];
$pageScripts = ['contact-inbox'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance', 'url' => Url::to('admin/finance/dashboard.php')],
    ['label' => 'Assigned Enquiries'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>
<nav class="finance-hub card" aria-label="Finance modules">
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check"></i><span>Approved IPCs</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar"></i><span>Process Payment</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>"><i class="fa-solid fa-chart-column"></i><span>Budget</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>"><i class="fa-solid fa-lock"></i><span>Retention</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/liquidated-damages.php')) ?>"><i class="fa-solid fa-money-bill-trend-up"></i><span>Damages</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i><span>Reports</span></a>
  <a class="finance-hub__link is-active" href="<?= Security::e(Url::to('admin/finance/assigned-enquiries.php')) ?>"><i class="fa-solid fa-inbox"></i><span>Enquiries</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/messages.php')) ?>"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
</nav>
<?php
include __DIR__ . '/../../app/partials/admin/contact-enquiries-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
