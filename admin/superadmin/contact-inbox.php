<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'read_state' => Security::cleanString((string)($_GET['read_state'] ?? '')),
    'assigned_to' => Security::cleanInt($_GET['assigned_to'] ?? 0),
    'has_attachment' => Security::cleanInt($_GET['has_attachment'] ?? 0),
    'date_from' => contact_inbox_date($_GET['date_from'] ?? ''),
    'date_to' => contact_inbox_date($_GET['date_to'] ?? ''),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalMessages = ContactSubmission::countItems($filters);
$totalPages = max(1, (int)ceil($totalMessages / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$messages = array_map([ContactSubmission::class, 'payload'], ContactSubmission::items($filters, $perPage, $offset));
$summary = ContactSubmission::summary([]);
$assignees = ContactSubmission::assignableUsers();
$showingFrom = $totalMessages > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($messages), $totalMessages);

$pageTitle = 'Contact Inbox';
$pageDescription = 'Public contact submissions, read status, assignment, archive and response tracking.';
$adminRole = 'superadmin';
$csrfForm = 'contact_inbox';
$contentClass = 'sa-contact-inbox-page';
$componentCss = ['contact-inbox'];
$pageScripts = ['contact-inbox'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Contact Inbox'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contact-inbox-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-inbox" aria-hidden="true"></i> Public enquiries</span>
    <h2>Contact Inbox</h2>
    <p>Review public contact messages, manage read status, assign follow-up, archive and track response notes.</p>
  </div>
  <div class="contact-inbox-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('contact.php')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Public Form</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 contact-inbox-stats" aria-label="Contact inbox summary">
  <?php contact_inbox_stat('fa-envelope', $summary['total'] ?? 0, 'Total Messages', 'All public submissions'); ?>
  <?php contact_inbox_stat('fa-envelope-open-text', $summary['unread'] ?? 0, 'Unread', 'Needs attention'); ?>
  <?php contact_inbox_stat('fa-reply', $summary['replied'] ?? 0, 'Replied', 'Response recorded'); ?>
  <?php contact_inbox_stat('fa-box-archive', $summary['archived'] ?? 0, 'Archived', 'Closed messages'); ?>
  <?php contact_inbox_stat('fa-paperclip', $summary['with_attachments'] ?? 0, 'Attachments', 'Files included'); ?>
  <?php contact_inbox_stat('fa-calendar-day', $summary['today'] ?? 0, 'Today', 'New today'); ?>
  <?php contact_inbox_stat('fa-calendar-week', $summary['this_week'] ?? 0, 'This Week', 'Last 7 days'); ?>
  <?php contact_inbox_stat('fa-eye', $summary['read_messages'] ?? 0, 'Read', 'Opened messages'); ?>
</section>

<section class="card contact-inbox-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Message Registry</h2>
      <p class="card__subtitle">Filter public enquiries and open a message to manage status, assignment and response notes.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalMessages)) ?> messages</span>
  </div>

  <form class="filter-bar contact-inbox-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/contact-inbox.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Name, email, phone, subject..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ContactSubmission::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="read_state">Read</label><select class="form-select" id="read_state" name="read_state"><option value="">Any</option><option value="unread" <?= (($filters['read_state'] ?? '') === 'unread') ? 'selected' : '' ?>>Unread</option><option value="read" <?= (($filters['read_state'] ?? '') === 'read') ? 'selected' : '' ?>>Read</option></select></div>
    <div class="filter-group"><label class="filter-label" for="assigned_to">Assigned</label><select class="form-select" id="assigned_to" name="assigned_to"><option value="">Anyone</option><?php foreach ($assignees as $user): ?><option value="<?= (int)$user['id'] ?>" <?= (int)($filters['assigned_to'] ?? 0) === (int)$user['id'] ? 'selected' : '' ?>><?= Security::e($user['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <label class="filter-check"><input type="checkbox" name="has_attachment" value="1" <?= !empty($filters['has_attachment']) ? 'checked' : '' ?>> <span>Attachments</span></label>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/contact-inbox.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table contact-inbox-table">
      <thead><tr><th>Sender</th><th>Subject</th><th>Preview</th><th>Status</th><th>Assigned</th><th>Received</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($messages === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span><strong class="empty-state__title">No contact messages found</strong><span class="empty-state__text">Public contact submissions will appear here once visitors send messages.</span></div></td></tr>
<?php else: ?>
<?php foreach ($messages as $message): ?>
        <tr class="<?= (int)$message['is_read'] === 0 ? 'is-unread' : '' ?>">
          <td><strong><?= Security::e($message['name']) ?></strong><small><?= Security::e($message['email']) ?><?= $message['phone'] ? ' | ' . Security::e($message['phone']) : '' ?></small></td>
          <td><strong><?= Security::e($message['subject'] ?: 'No subject') ?></strong><?= $message['has_attachment'] ? '<small><i class="fa-solid fa-paperclip" aria-hidden="true"></i> Attachment</small>' : '' ?></td>
          <td class="contact-message-preview"><?= Security::e(safe_truncate((string)$message['message'], 120)) ?></td>
          <td><span class="badge <?= Security::e(status_badge_class($message['status'])) ?>"><?= Security::e(status_label($message['status'])) ?></span><small><?= (int)$message['is_read'] === 0 ? 'Unread' : 'Read' ?></small></td>
          <td><?= $message['assignee_name'] ? Security::e($message['assignee_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
          <td><?= Security::e(format_datetime($message['created_at'])) ?><small><?= Security::e(time_ago($message['created_at'])) ?></small></td>
          <td>
            <button class="btn btn--icon btn--primary" type="button" data-contact-open data-id="<?= (int)$message['id'] ?>" title="Open message" aria-label="Open message"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            <button class="btn btn--icon btn--outline" type="button" data-contact-action="read" data-id="<?= (int)$message['id'] ?>" title="Mark read" aria-label="Mark read"><i class="fa-solid fa-envelope-open" aria-hidden="true"></i></button>
            <button class="btn btn--icon btn--danger" type="button" data-contact-action="archive" data-id="<?= (int)$message['id'] ?>" title="Archive" aria-label="Archive"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button>
          </td>
        </tr>
<?php endforeach; ?>
<?php endif; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="Contact inbox pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalMessages)) ?> messages</p>
    <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(contact_inbox_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(contact_inbox_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
  </nav>
<?php endif; ?>
</section>

<div class="modal" data-contact-modal hidden>
  <div class="modal__backdrop" data-contact-close></div>
  <div class="modal__dialog contact-modal" role="dialog" aria-modal="true" aria-labelledby="contactModalTitle">
    <div class="modal__header"><h2 class="modal__title" id="contactModalTitle">Contact message</h2><button class="modal__close" type="button" data-contact-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
    <div class="modal__body">
      <div class="contact-detail" data-contact-detail>
        <div class="contact-detail__loading">Loading message...</div>
      </div>
      <div class="form-grid form-grid--2 contact-modal-controls">
        <label class="form-field"><span>Assign to</span><select class="form-select" data-contact-assign><option value="">Unassigned</option><?php foreach ($assignees as $user): ?><option value="<?= (int)$user['id'] ?>"><?= Security::e($user['name']) ?><?= $user['role'] ? ' - ' . Security::e(role_label($user['role'])) : '' ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span>Status action</span><select class="form-select" data-contact-status><option value="read">Mark read</option><option value="unread">Mark unread</option><option value="replied">Mark replied</option><option value="archive">Archive</option><option value="restore">Restore</option></select></label>
      </div>
      <label class="form-field"><span>Response note</span><textarea class="form-textarea" rows="4" data-contact-response-note placeholder="Record how this enquiry was handled. This does not send email yet."></textarea></label>
      <div class="alert alert--danger" data-contact-error hidden></div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--outline" type="button" data-contact-close>Close</button>
      <button class="btn btn--outline" type="button" data-contact-save-assign><i class="fa-solid fa-user-check" aria-hidden="true"></i> Save Assignment</button>
      <button class="btn btn--primary" type="button" data-contact-save-status><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Status</button>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function contact_inbox_date(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function contact_inbox_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function contact_inbox_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
    return Url::to('admin/superadmin/contact-inbox.php?' . http_build_query($query));
}
