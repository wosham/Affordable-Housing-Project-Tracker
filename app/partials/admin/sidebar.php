<?php
$adminRole = $adminRole ?? Auth::role() ?? 'staff';
$roleKey = strtolower((string)$adminRole);
$roleKey = preg_match('/^[a-z0-9_-]+$/', $roleKey) ? $roleKey : 'staff';
$sidebarTitle = $sidebarTitle ?? 'AHP Tracker';
$sidebarSubtitle = $sidebarSubtitle ?? 'Trans-Nzoia County';
$sidebarLogo = $sidebarLogo ?? Url::asset('uploads/logos/afforadablehousinglogo.png');
$sidebarUserName = current_user_name();
$sidebarUserInitials = current_user_initials();
$sidebarRoleLabel = role_label($roleKey);

$roleNav = [
    'superadmin' => [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/superadmin/dashboard.php'],
        ['label' => 'Projects', 'icon' => 'fa-building', 'path' => 'admin/superadmin/projects.php'],
        ['label' => 'Constituencies', 'icon' => 'fa-map-location-dot', 'path' => 'admin/superadmin/constituencies.php', 'active_paths' => ['admin/superadmin/constituencies.php', 'admin/superadmin/constituency-edit.php']],
        ['label' => 'Users', 'icon' => 'fa-users', 'path' => 'admin/superadmin/users.php'],
        ['label' => 'IPC Centre', 'icon' => 'fa-file-invoice-dollar', 'path' => 'admin/superadmin/ipcs.php', 'active_paths' => ['admin/superadmin/ipcs.php', 'admin/superadmin/ipc-detail.php']],
        ['label' => 'BOQ', 'icon' => 'fa-list-check', 'path' => 'admin/superadmin/boq.php'],
        ['label' => 'Programme', 'icon' => 'fa-chart-gantt', 'path' => 'admin/superadmin/programme-of-works.php'],
        ['label' => 'IPCs & Approvals', 'icon' => 'fa-clipboard-check', 'path' => 'admin/superadmin/approvals.php'],
        ['label' => 'Financials', 'icon' => 'fa-coins', 'path' => 'admin/superadmin/financials.php'],
        ['label' => 'Attendance', 'icon' => 'fa-calendar-check', 'path' => 'admin/superadmin/attendance.php'],
        ['label' => 'Work Locations', 'icon' => 'fa-building-user', 'path' => 'admin/superadmin/work-locations.php'],
        ['label' => 'Staff Announcements', 'icon' => 'fa-bullhorn', 'path' => 'admin/superadmin/announcements.php'],
        ['label' => 'News', 'icon' => 'fa-newspaper', 'path' => 'admin/superadmin/news.php'],
        ['label' => 'Gallery', 'icon' => 'fa-images', 'path' => 'admin/superadmin/gallery.php'],
        ['label' => 'Media Library', 'icon' => 'fa-photo-film', 'path' => 'admin/superadmin/media-library.php'],
        ['label' => 'Site Navigation', 'icon' => 'fa-compass', 'path' => 'admin/superadmin/site-navigation.php'],
        ['label' => 'CMS Pages', 'icon' => 'fa-pen-to-square', 'path' => 'admin/superadmin/cms.php'],
        ['label' => 'FAQ', 'icon' => 'fa-circle-question', 'path' => 'admin/superadmin/faq.php'],
        ['label' => 'Leadership', 'icon' => 'fa-user-tie', 'path' => 'admin/superadmin/leadership.php'],
        ['label' => 'Stakeholders', 'icon' => 'fa-handshake', 'path' => 'admin/superadmin/stakeholders.php'],
        ['label' => 'Contact Inbox', 'icon' => 'fa-inbox', 'path' => 'admin/superadmin/contact-inbox.php'],
        ['label' => 'Subscribers', 'icon' => 'fa-envelope', 'path' => 'admin/superadmin/subscribers.php'],
        ['label' => 'Analytics', 'icon' => 'fa-chart-pie', 'path' => 'admin/superadmin/analytics.php'],
        ['label' => 'Reports', 'icon' => 'fa-file-lines', 'path' => 'admin/superadmin/reports.php'],
        ['label' => 'Settings', 'icon' => 'fa-gear', 'path' => 'admin/superadmin/settings.php'],
        ['label' => 'Audit Log', 'icon' => 'fa-magnifying-glass-chart', 'path' => 'admin/superadmin/audit-log.php'],
        ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/superadmin/messages.php'],
        ['label' => 'System Health', 'icon' => 'fa-server', 'path' => 'admin/superadmin/system-health.php'],
        ['label' => 'Backups & Recovery', 'icon' => 'fa-database', 'path' => 'admin/superadmin/backups.php'],
    ],
    'manager' => [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/manager/dashboard.php'],
        ['label' => 'My Projects', 'icon' => 'fa-building', 'path' => 'admin/manager/projects.php'],
        ['label' => 'Milestones', 'icon' => 'fa-bullseye', 'path' => 'admin/manager/milestones.php'],
        ['label' => 'IPC Queue', 'icon' => 'fa-clipboard-list', 'path' => 'admin/manager/ipc-queue.php'],
        ['label' => 'BOQ', 'icon' => 'fa-list-check', 'path' => 'admin/manager/boq.php'],
        ['label' => 'Assignments', 'icon' => 'fa-user-plus', 'path' => 'admin/manager/assignments.php'],
        ['label' => 'Attendance Summary', 'icon' => 'fa-calendar-days', 'path' => 'admin/manager/attendance-summary.php'],
        ['label' => 'Programme of Works', 'icon' => 'fa-chart-gantt', 'path' => 'admin/manager/programme-of-works.php'],
        ['label' => 'Site Meeting Minutes', 'icon' => 'fa-clipboard', 'path' => 'admin/manager/site-meeting-minutes.php'],
        ['label' => 'HS Incidents', 'icon' => 'fa-triangle-exclamation', 'path' => 'admin/manager/hs-incidents.php'],
        ['label' => 'Community Liaison', 'icon' => 'fa-handshake-angle', 'path' => 'admin/manager/community-liaison.php'],
        ['label' => 'EOT Requests', 'icon' => 'fa-clock', 'path' => 'admin/manager/eot-requests.php'],
        ['label' => 'Liquidated Damages', 'icon' => 'fa-money-bill-trend-up', 'path' => 'admin/manager/liquidated-damages.php'],
        ['label' => 'Subcontractors', 'icon' => 'fa-helmet-safety', 'path' => 'admin/manager/subcontractors.php'],
        ['label' => 'Reports', 'icon' => 'fa-file-lines', 'path' => 'admin/manager/reports.php'],
        ['label' => 'Assigned Enquiries', 'icon' => 'fa-inbox', 'path' => 'admin/manager/assigned-enquiries.php'],
        ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/manager/messages.php'],
    ],
    'consultant' => [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/consultant/dashboard.php'],
        ['label' => 'IPC Inbox', 'icon' => 'fa-inbox', 'path' => 'admin/consultant/ipc-inbox.php'],
        ['label' => 'IPC Certify', 'icon' => 'fa-clipboard-check', 'path' => 'admin/consultant/ipc-certify.php'],
        ['label' => 'BOQ Review', 'icon' => 'fa-list-check', 'path' => 'admin/consultant/boq-review.php'],
        ['label' => 'Defects Register', 'icon' => 'fa-magnifying-glass', 'path' => 'admin/consultant/defects.php'],
        ['label' => 'Documents', 'icon' => 'fa-folder-open', 'path' => 'admin/consultant/documents.php'],
        ['label' => 'EOT Review', 'icon' => 'fa-clock-rotate-left', 'path' => 'admin/consultant/eot-review.php'],
        ['label' => 'Inspection Test Plans', 'icon' => 'fa-clipboard-list', 'path' => 'admin/consultant/inspection-test-plans.php'],
        ['label' => 'Material Approvals', 'icon' => 'fa-cubes', 'path' => 'admin/consultant/material-approvals.php'],
        ['label' => 'Non-Conformance', 'icon' => 'fa-triangle-exclamation', 'path' => 'admin/consultant/non-conformance.php'],
        ['label' => 'Programme Review', 'icon' => 'fa-chart-gantt', 'path' => 'admin/consultant/programme-review.php'],
        ['label' => 'Quality Register', 'icon' => 'fa-vial-circle-check', 'path' => 'admin/consultant/quality-register.php'],
        ['label' => 'Shop Drawings', 'icon' => 'fa-compass-drafting', 'path' => 'admin/consultant/shop-drawings.php'],
        ['label' => 'Site Reports', 'icon' => 'fa-file-signature', 'path' => 'admin/consultant/site-reports.php'],
        ['label' => 'Variations', 'icon' => 'fa-code-branch', 'path' => 'admin/consultant/variations.php'],
        ['label' => 'Assigned Enquiries', 'icon' => 'fa-inbox', 'path' => 'admin/consultant/assigned-enquiries.php'],
        ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/consultant/messages.php'],
    ],
    'contractor' => [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/contractor/dashboard.php'],
        ['label' => 'My Project', 'icon' => 'fa-building', 'path' => 'admin/contractor/my-project.php'],
        ['label' => 'Progress Update', 'icon' => 'fa-chart-simple', 'path' => 'admin/contractor/progress-update.php'],
        ['label' => 'Submit IPC', 'icon' => 'fa-file-circle-plus', 'path' => 'admin/contractor/ipc-submit.php'],
        ['label' => 'IPC History', 'icon' => 'fa-clock-rotate-left', 'path' => 'admin/contractor/ipc-history.php'],
        ['label' => 'BOQ', 'icon' => 'fa-list-check', 'path' => 'admin/contractor/boq.php'],
        ['label' => 'Documents', 'icon' => 'fa-folder-open', 'path' => 'admin/contractor/documents.php'],
        ['label' => 'Equipment Register', 'icon' => 'fa-truck-ramp-box', 'path' => 'admin/contractor/equipment-register.php'],
        ['label' => 'EOT Request', 'icon' => 'fa-clock', 'path' => 'admin/contractor/eot-request.php'],
        ['label' => 'HS Incidents', 'icon' => 'fa-triangle-exclamation', 'path' => 'admin/contractor/hs-incidents.php'],
        ['label' => 'Labour Register', 'icon' => 'fa-people-carry-box', 'path' => 'admin/contractor/labour-register.php'],
        ['label' => 'Material Approval', 'icon' => 'fa-cubes', 'path' => 'admin/contractor/material-approval-submit.php'],
        ['label' => 'Material Deliveries', 'icon' => 'fa-truck', 'path' => 'admin/contractor/material-deliveries.php'],
        ['label' => 'Payment History', 'icon' => 'fa-credit-card', 'path' => 'admin/contractor/payment-history.php'],
        ['label' => 'Programme of Works', 'icon' => 'fa-chart-gantt', 'path' => 'admin/contractor/programme-of-works.php'],
        ['label' => 'RFIs', 'icon' => 'fa-circle-question', 'path' => 'admin/contractor/rfis.php'],
        ['label' => 'Shop Drawings', 'icon' => 'fa-compass-drafting', 'path' => 'admin/contractor/shop-drawing-submit.php'],
        ['label' => 'Subcontractors', 'icon' => 'fa-helmet-safety', 'path' => 'admin/contractor/subcontractors.php'],
        ['label' => 'Variation Request', 'icon' => 'fa-code-branch', 'path' => 'admin/contractor/variation-request.php'],
        ['label' => 'Assigned Enquiries', 'icon' => 'fa-inbox', 'path' => 'admin/contractor/assigned-enquiries.php'],
        ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/contractor/messages.php'],
    ],
    'clerk' => [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/clerk/dashboard.php'],
        ['label' => 'Attendance Gateway', 'icon' => 'fa-door-open', 'path' => 'admin/clerk/attendance-gateway.php'],
        ['label' => 'Live Attendance', 'icon' => 'fa-eye', 'path' => 'admin/clerk/attendance-live.php'],
        ['label' => 'Daily Site Diary', 'icon' => 'fa-book', 'path' => 'admin/clerk/daily-diary.php'],
        ['label' => 'Weather Log', 'icon' => 'fa-cloud-sun', 'path' => 'admin/clerk/weather-log.php'],
        ['label' => 'Labour Verification', 'icon' => 'fa-users-gear', 'path' => 'admin/clerk/labour-verification.php'],
        ['label' => 'Material Delivery Log', 'icon' => 'fa-truck', 'path' => 'admin/clerk/material-delivery-log.php'],
        ['label' => 'Equipment Check', 'icon' => 'fa-screwdriver-wrench', 'path' => 'admin/clerk/equipment-check.php'],
        ['label' => 'Quality Tests', 'icon' => 'fa-vial', 'path' => 'admin/clerk/quality-tests.php'],
        ['label' => 'Inspection Test Plans', 'icon' => 'fa-clipboard-list', 'path' => 'admin/clerk/inspection-test-plans.php'],
        ['label' => 'HS Incidents', 'icon' => 'fa-triangle-exclamation', 'path' => 'admin/clerk/hs-incidents.php'],
        ['label' => 'Non-Conformance', 'icon' => 'fa-circle-xmark', 'path' => 'admin/clerk/non-conformance.php'],
        ['label' => 'Defects', 'icon' => 'fa-magnifying-glass', 'path' => 'admin/clerk/defects.php'],
        ['label' => 'Site Meeting Minutes', 'icon' => 'fa-clipboard', 'path' => 'admin/clerk/site-meeting-minutes.php'],
        ['label' => 'Documents', 'icon' => 'fa-folder-open', 'path' => 'admin/clerk/documents.php'],
        ['label' => 'Photos', 'icon' => 'fa-camera', 'path' => 'admin/clerk/photos.php'],
        ['label' => 'IPC Verify', 'icon' => 'fa-clipboard-check', 'path' => 'admin/clerk/ipc-verify.php'],
        ['label' => 'Assigned Enquiries', 'icon' => 'fa-inbox', 'path' => 'admin/clerk/assigned-enquiries.php'],
        ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/clerk/messages.php'],
    ],
    'finance' => [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/finance/dashboard.php'],
        ['label' => 'Approved IPCs', 'icon' => 'fa-circle-check', 'path' => 'admin/finance/approved-ipcs.php'],
        ['label' => 'Process Payment', 'icon' => 'fa-credit-card', 'path' => 'admin/finance/process-payment.php'],
        ['label' => 'Budget Tracker', 'icon' => 'fa-chart-column', 'path' => 'admin/finance/budget-tracker.php'],
        ['label' => 'Liquidated Damages', 'icon' => 'fa-money-bill-trend-up', 'path' => 'admin/finance/liquidated-damages.php'],
        ['label' => 'Retention', 'icon' => 'fa-lock', 'path' => 'admin/finance/retention.php'],
        ['label' => 'Financial Reports', 'icon' => 'fa-file-invoice-dollar', 'path' => 'admin/finance/financial-reports.php'],
        ['label' => 'Assigned Enquiries', 'icon' => 'fa-inbox', 'path' => 'admin/finance/assigned-enquiries.php'],
        ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/finance/messages.php'],
    ],
    'intern' => [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/intern/dashboard.php'],
        ['label' => 'Sign In', 'icon' => 'fa-location-dot', 'path' => 'admin/intern/sign-in.php'],
        ['label' => 'My Attendance', 'icon' => 'fa-calendar-check', 'path' => 'admin/intern/my-attendance.php'],
        ['label' => 'My Project', 'icon' => 'fa-building', 'path' => 'admin/intern/my-project.php'],
        ['label' => 'Data Entry', 'icon' => 'fa-pen-to-square', 'path' => 'admin/intern/site-data-entry.php'],
        ['label' => 'Upload Photos', 'icon' => 'fa-camera', 'path' => 'admin/intern/upload-photos.php'],
        ['label' => 'Assigned Enquiries', 'icon' => 'fa-inbox', 'path' => 'admin/intern/assigned-enquiries.php'],
        ['label' => 'Messages', 'icon' => 'fa-comments', 'path' => 'admin/intern/messages.php'],
    ],
];

$adminNav = $adminNav ?? ($roleNav[$roleKey] ?? [
    ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'path' => 'admin/index.php'],
]);

$navGroups = [
    'superadmin' => [
        ['type' => 'links', 'items' => ['Dashboard', 'Projects', 'Constituencies', 'Users', 'IPC Centre', 'BOQ', 'Programme', 'IPCs & Approvals', 'Financials', 'Attendance', 'Work Locations']],
        ['label' => 'Public Content', 'icon' => 'fa-newspaper', 'items' => ['News', 'Gallery', 'Media Library', 'Site Navigation', 'CMS Pages', 'FAQ', 'Leadership', 'Stakeholders']],
        ['label' => 'Communication', 'icon' => 'fa-comments', 'items' => ['Staff Announcements', 'Contact Inbox', 'Subscribers', 'Messages']],
        ['label' => 'Intelligence', 'icon' => 'fa-chart-pie', 'items' => ['Analytics', 'Reports', 'Audit Log', 'System Health']],
        ['label' => 'Administration', 'icon' => 'fa-screwdriver-wrench', 'items' => ['Settings', 'Backups & Recovery']],
    ],
    'manager' => [
        ['label' => 'Overview', 'icon' => 'fa-gauge-high', 'items' => ['Dashboard', 'Reports', 'Assigned Enquiries', 'Messages']],
        ['label' => 'Delivery', 'icon' => 'fa-building-circle-check', 'items' => ['My Projects', 'Milestones', 'Programme of Works', 'Assignments']],
        ['label' => 'Claims & Cost', 'icon' => 'fa-file-invoice-dollar', 'items' => ['IPC Queue', 'BOQ', 'EOT Requests', 'Liquidated Damages']],
        ['label' => 'Site Operations', 'icon' => 'fa-helmet-safety', 'items' => ['Attendance Summary', 'Site Meeting Minutes', 'HS Incidents', 'Community Liaison', 'Subcontractors']],
    ],
    'consultant' => [
        ['label' => 'Overview', 'icon' => 'fa-gauge-high', 'items' => ['Dashboard', 'Documents', 'Assigned Enquiries', 'Messages']],
        ['label' => 'IPC Review', 'icon' => 'fa-file-invoice', 'items' => ['IPC Inbox', 'IPC Certify', 'BOQ Review']],
        ['label' => 'Technical Review', 'icon' => 'fa-compass-drafting', 'items' => ['Material Approvals', 'Shop Drawings', 'EOT Review', 'Variations', 'Programme Review']],
        ['label' => 'Quality & Site', 'icon' => 'fa-vial-circle-check', 'items' => ['Defects Register', 'Inspection Test Plans', 'Non-Conformance', 'Quality Register', 'Site Reports']],
    ],
    'contractor' => [
        ['label' => 'Overview', 'icon' => 'fa-gauge-high', 'items' => ['Dashboard', 'My Project', 'Assigned Enquiries', 'Messages']],
        ['label' => 'Claims', 'icon' => 'fa-file-invoice-dollar', 'items' => ['Submit IPC', 'IPC History', 'Payment History', 'BOQ']],
        ['label' => 'Progress & Programme', 'icon' => 'fa-chart-gantt', 'items' => ['Progress Update', 'Programme of Works', 'EOT Request', 'Variation Request']],
        ['label' => 'Site Records', 'icon' => 'fa-helmet-safety', 'items' => ['Documents', 'Equipment Register', 'HS Incidents', 'Labour Register', 'Material Approval', 'Material Deliveries', 'RFIs', 'Shop Drawings', 'Subcontractors']],
    ],
    'clerk' => [
        ['label' => 'Overview', 'icon' => 'fa-gauge-high', 'items' => ['Dashboard', 'Assigned Enquiries', 'Messages']],
        ['label' => 'Attendance', 'icon' => 'fa-door-open', 'items' => ['Attendance Gateway', 'Live Attendance']],
        ['label' => 'Daily Records', 'icon' => 'fa-book', 'items' => ['Daily Site Diary', 'Weather Log', 'Labour Verification', 'Material Delivery Log', 'Equipment Check']],
        ['label' => 'Quality & Safety', 'icon' => 'fa-shield-halved', 'items' => ['Quality Tests', 'Inspection Test Plans', 'HS Incidents', 'Non-Conformance', 'Defects', 'IPC Verify']],
        ['label' => 'Documents', 'icon' => 'fa-folder-open', 'items' => ['Site Meeting Minutes', 'Documents', 'Photos']],
    ],
    'finance' => [
        ['label' => 'Overview', 'icon' => 'fa-gauge-high', 'items' => ['Dashboard', 'Financial Reports', 'Assigned Enquiries', 'Messages']],
        ['label' => 'Payments', 'icon' => 'fa-credit-card', 'items' => ['Approved IPCs', 'Process Payment']],
        ['label' => 'Controls', 'icon' => 'fa-chart-column', 'items' => ['Budget Tracker', 'Liquidated Damages', 'Retention']],
    ],
];

$groupedNav = [];
if (isset($navGroups[$roleKey])) {
    $itemsByLabel = [];
    foreach ($adminNav as $item) {
        $itemsByLabel[(string)($item['label'] ?? '')] = $item;
    }

    foreach ($navGroups[$roleKey] as $group) {
        $children = [];
        foreach ((array)$group['items'] as $label) {
            if (isset($itemsByLabel[$label])) {
                $children[] = $itemsByLabel[$label];
                unset($itemsByLabel[$label]);
            }
        }
        if ($children !== []) {
            $group['children'] = $children;
            $groupedNav[] = $group;
        }
    }

    if ($itemsByLabel !== []) {
        $groupedNav[] = ['label' => 'More', 'icon' => 'fa-ellipsis', 'children' => array_values($itemsByLabel)];
    }
}

$sidebarEnquiryUnread = 0;
try {
    if (in_array($roleKey, ['manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern', 'superadmin'], true)) {
        $sidebarEnquiryUnread = contact_enquiries_unread_count((int)Auth::id(), $roleKey);
    }
} catch (Throwable) {
    $sidebarEnquiryUnread = 0;
}

$renderSidebarLink = static function (array $item, bool $isChild = false) use ($sidebarEnquiryUnread): void {
    $label = (string)($item['label'] ?? '');
    $icon = (string)($item['icon'] ?? 'fa-circle');
    $path = (string)($item['path'] ?? 'admin/index.php');
    $activePaths = array_merge([$path], (array)($item['active_paths'] ?? []));
    $isActive = false;
    foreach ($activePaths as $activePath) {
        if (Url::isActive((string)$activePath)) {
            $isActive = true;
            break;
        }
    }
    $linkClass = ($isChild ? 'sidebar-link sidebar-link--child' : 'sidebar-link') . ($isActive ? ' is-active' : '');
    $showBadge = $sidebarEnquiryUnread > 0 && (
        $label === 'Assigned Enquiries' || $label === 'Contact Inbox'
    );
?>
    <a class="<?= Security::e($linkClass) ?>" href="<?= Security::e(Url::to($path)) ?>" title="<?= Security::e($label) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i>
      <span><?= Security::e($label) ?></span>
<?php if ($showBadge): ?>
      <span class="sidebar-badge" aria-label="<?= (int)$sidebarEnquiryUnread ?> unread"><?= (int)$sidebarEnquiryUnread ?></span>
<?php endif; ?>
    </a>
<?php
};
?>
<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
  <div class="sidebar-brand">
    <button type="button" class="sidebar-close" data-sidebar-close aria-label="Close navigation">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
    <a href="<?= Security::e(Url::to('admin/index.php')) ?>" class="sidebar-brand-link" aria-label="AHP Tracker admin home">
      <img class="sidebar-brand-logo" src="<?= Security::e($sidebarLogo) ?>" alt="Trans-Nzoia County" width="40" height="40" onerror="this.style.display='none'">
      <span class="sidebar-brand-copy">
        <span class="sidebar-brand-title"><?= Security::e($sidebarTitle) ?></span>
        <small><?= Security::e($sidebarSubtitle) ?></small>
      </span>
    </a>
  </div>

  <nav class="sidebar-nav" aria-label="<?= Security::e(role_label($roleKey)) ?> navigation">
<?php if ($groupedNav !== []): ?>
<?php foreach ($groupedNav as $groupIndex => $group): ?>
<?php
    $groupType = (string)($group['type'] ?? 'group');
    if ($groupType === 'links') {
        foreach ((array)($group['children'] ?? []) as $item) {
            $renderSidebarLink($item);
        }
        continue;
    }

    $groupLabel = (string)($group['label'] ?? 'Navigation');
    $groupIcon = (string)($group['icon'] ?? 'fa-folder');
    $groupId = $roleKey . '-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $groupLabel));
    $groupChildren = (array)($group['children'] ?? []);
    $groupActive = false;
    foreach ($groupChildren as $child) {
        $activePaths = array_merge([(string)($child['path'] ?? '')], (array)($child['active_paths'] ?? []));
        foreach ($activePaths as $activePath) {
            if (Url::isActive((string)$activePath)) {
                $groupActive = true;
                break 2;
            }
        }
    }
?>
    <div class="sidebar-group is-open<?= $groupActive ? ' has-active' : '' ?>" data-sidebar-group-id="<?= Security::e($groupId) ?>">
      <button type="button" class="sidebar-group-heading" data-sidebar-group-toggle aria-expanded="true" aria-controls="<?= Security::e($groupId) ?>-list">
        <i class="fa-solid <?= Security::e($groupIcon) ?>" aria-hidden="true"></i>
        <span class="sidebar-group-copy"><?= Security::e($groupLabel) ?></span>
        <i class="fa-solid fa-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
      </button>
      <div class="sidebar-group-list" id="<?= Security::e($groupId) ?>-list">
<?php foreach ($groupChildren as $item): ?>
<?php $renderSidebarLink($item, true); ?>
<?php endforeach; ?>
      </div>
    </div>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($adminNav as $item): ?>
<?php $renderSidebarLink($item); ?>
<?php endforeach; ?>
<?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-system" aria-label="System status">
      <span class="sidebar-system-dot" aria-hidden="true"></span>
      <span class="sidebar-system-meta">
        <strong>System Online</strong>
        <small>Secure session</small>
      </span>
    </div>
  </div>
</aside>
