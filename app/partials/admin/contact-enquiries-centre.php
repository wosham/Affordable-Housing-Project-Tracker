<?php
/**
 * Shared Contact Inbox / Assigned Enquiries centre.
 * Expects: $viewerId, $viewerRole, $isContactSupervisor, $contactInboxPath, $dashboardPath, $dashboardLabel
 */
$viewerId = (int)($viewerId ?? Auth::id());
$viewerRole = (string)($viewerRole ?? (Auth::role() ?: 'staff'));
$isContactSupervisor = (bool)($isContactSupervisor ?? ($viewerRole === 'superadmin'));
$contactInboxPath = (string)($contactInboxPath ?? 'admin/superadmin/contact-inbox.php');
$dashboardPath = (string)($dashboardPath ?? ('admin/' . $viewerRole . '/dashboard.php'));
$dashboardLabel = (string)($dashboardLabel ?? 'Dashboard');

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'read_state' => Security::cleanString((string)($_GET['read_state'] ?? '')),
    'has_attachment' => Security::cleanInt($_GET['has_attachment'] ?? 0),
    'priority' => Security::cleanString((string)($_GET['priority'] ?? '')),
    'box' => Security::cleanString((string)($_GET['box'] ?? '')),
    'sort' => Security::cleanString((string)($_GET['sort'] ?? 'newest')),
    'date_from' => contact_enquiries_date($_GET['date_from'] ?? ''),
    'date_to' => contact_enquiries_date($_GET['date_to'] ?? ''),
];
// Superadmin may filter by assignee. Non-superadmin assignee is forced in scopedFilters.
if ($isContactSupervisor) {
    $filters['assigned_to'] = Security::cleanInt($_GET['assigned_to'] ?? 0);
}
// Default list: hide archived unless supervisor browsing all / explicit status.
if ($filters['status'] === '' && $filters['box'] === '' && !$isContactSupervisor) {
    $filters['exclude_archived'] = 1;
}
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
// Always last: hard privacy scope for non-superadmin.
$filters = ContactSubmission::scopedFilters($filters, $viewerId, $viewerRole);

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalMessages = ContactSubmission::countItems($filters);
$totalPages = max(1, (int)ceil($totalMessages / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$contactMessages = array_map([ContactSubmission::class, 'payload'], ContactSubmission::items($filters, $perPage, $offset));
if ($contactMessages === [] && $totalMessages > 0) {
    $page = 1;
    $offset = 0;
    $contactMessages = array_map([ContactSubmission::class, 'payload'], ContactSubmission::items($filters, $perPage, $offset));
}

$summaryScope = ContactSubmission::scopedFilters([], $viewerId, $viewerRole);
$summary = ContactSubmission::summary($summaryScope);
$assignees = $isContactSupervisor ? ContactSubmission::assignableUsers() : [];
$templates = contact_enquiries_templates();
$showingFrom = $totalMessages > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($contactMessages), $totalMessages);
$heroTitle = $isContactSupervisor ? 'Contact Inbox' : 'Assigned Enquiries';
$heroLead = $isContactSupervisor
    ? 'Review public contact messages, assign follow-up, reply by email and keep a clear response trail.'
    : 'Public enquiries assigned to you. Reply by email, update status, set follow-ups and open an internal staff discussion when needed.';
$emptyTitle = $isContactSupervisor ? 'No contact messages found' : 'No enquiries assigned to you';
$emptyText = $isContactSupervisor
    ? 'Public contact submissions will appear here once visitors send messages.'
    : 'When the County Director assigns a public enquiry to your account, it will appear here for follow-up.';
?>

<section class="contact-inbox-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-inbox" aria-hidden="true"></i> <?= $isContactSupervisor ? 'Public enquiries' : 'Assigned cases only' ?></span>
    <h2><?= Security::e($heroTitle) ?></h2>
    <p><?= Security::e($heroLead) ?></p>
  </div>
  <div class="contact-inbox-hero__actions">
<?php if ($isContactSupervisor): ?>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('contact.php')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Public Form</a>
<?php else: ?>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/' . $viewerRole . '/messages.php')) ?>"><i class="fa-solid fa-comments" aria-hidden="true"></i> Messages</a>
<?php endif; ?>
    <a class="btn btn--outline" href="<?= Security::e(Url::to($dashboardPath)) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> <?= Security::e($dashboardLabel) ?></a>
  </div>
</section>

<section class="stat-grid stat-grid--4 contact-inbox-stats contact-inbox-stats--primary" aria-label="Enquiry summary">
  <?php contact_enquiries_stat('fa-folder-open', $summary['open_cases'] ?? $summary['total'] ?? 0, 'Open', $isContactSupervisor ? 'Not archived' : 'Assigned & open'); ?>
  <?php contact_enquiries_stat('fa-envelope-open-text', $summary['unread'] ?? 0, 'Unread', 'Needs attention', ((int)($summary['unread'] ?? 0) > 0 ? 'warn' : '')); ?>
  <?php contact_enquiries_stat('fa-triangle-exclamation', $summary['overdue'] ?? 0, 'Overdue', 'Past follow-up / ' . ContactSubmission::SLA_HOURS . 'h SLA', ((int)($summary['overdue'] ?? 0) > 0 ? 'danger' : '')); ?>
  <?php contact_enquiries_stat('fa-reply', $summary['replied'] ?? 0, 'Replied', 'Email sent'); ?>
</section>
<details class="contact-stats-more card">
  <summary>More stats</summary>
  <div class="stat-grid stat-grid--4 contact-inbox-stats contact-inbox-stats--secondary">
    <?php contact_enquiries_stat('fa-spinner', $summary['in_progress'] ?? 0, 'In progress', 'Actively working'); ?>
    <?php contact_enquiries_stat('fa-calendar-day', $summary['due_today'] ?? 0, 'Due today', 'Follow-up date today'); ?>
    <?php contact_enquiries_stat('fa-paperclip', $summary['with_attachments'] ?? 0, 'Attachments', 'Files included'); ?>
    <?php contact_enquiries_stat('fa-box-archive', $summary['archived'] ?? 0, 'Archived', 'Closed cases'); ?>
  </div>
</details>

<section class="card contact-inbox-card">
  <div class="card__header">
    <div>
      <h2 class="card__title"><?= $isContactSupervisor ? 'Message Registry' : 'My Assigned Cases' ?></h2>
      <p class="card__subtitle"><?= $isContactSupervisor ? 'Filter, assign, reply and close public enquiries.' : 'Only cases assigned to you. Open, reply, follow up, archive.' ?></p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalMessages)) ?> shown</span>
  </div>

  <form class="filter-bar contact-inbox-filter" method="get" action="<?= Security::e(Url::to($contactInboxPath)) ?>" data-contact-filters>
    <div class="contact-filter-primary">
      <div class="filter-group filter-group--search"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Name, email, phone, subject..."></div>
      <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ContactSubmission::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
      <div class="filter-group"><label class="filter-label" for="box">Focus</label><select class="form-select" id="box" name="box"><option value="">Default</option><option value="open" <?= (($filters['box'] ?? '') === 'open') ? 'selected' : '' ?>>Open only</option><option value="overdue" <?= (($filters['box'] ?? '') === 'overdue') ? 'selected' : '' ?>>Overdue</option><option value="due_today" <?= (($filters['box'] ?? '') === 'due_today') ? 'selected' : '' ?>>Due today</option></select></div>
      <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to($contactInboxPath)) ?>">Reset</a></div>
    </div>
    <details class="contact-filter-more"<?= (
        ($filters['read_state'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '' || !empty($filters['has_attachment']) || ($filters['sort'] ?? 'newest') !== 'newest' || ($isContactSupervisor && !empty($filters['assigned_to']))
    ) ? ' open' : '' ?>>
      <summary>More filters</summary>
      <div class="contact-filter-more__grid">
        <div class="filter-group"><label class="filter-label" for="read_state">Read</label><select class="form-select" id="read_state" name="read_state"><option value="">Any</option><option value="unread" <?= (($filters['read_state'] ?? '') === 'unread') ? 'selected' : '' ?>>Unread</option><option value="read" <?= (($filters['read_state'] ?? '') === 'read') ? 'selected' : '' ?>>Read</option></select></div>
        <div class="filter-group"><label class="filter-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority"><option value="">Any</option><option value="normal" <?= (($filters['priority'] ?? '') === 'normal') ? 'selected' : '' ?>>Normal</option><option value="urgent" <?= (($filters['priority'] ?? '') === 'urgent') ? 'selected' : '' ?>>Urgent</option></select></div>
        <div class="filter-group"><label class="filter-label" for="sort">Sort</label><select class="form-select" id="sort" name="sort"><option value="newest" <?= (($filters['sort'] ?? 'newest') === 'newest') ? 'selected' : '' ?>>Newest</option><option value="oldest" <?= (($filters['sort'] ?? '') === 'oldest') ? 'selected' : '' ?>>Oldest</option><option value="unread" <?= (($filters['sort'] ?? '') === 'unread') ? 'selected' : '' ?>>Unread first</option><option value="overdue" <?= (($filters['sort'] ?? '') === 'overdue') ? 'selected' : '' ?>>Overdue first</option></select></div>
<?php if ($isContactSupervisor): ?>
        <div class="filter-group"><label class="filter-label" for="assigned_to">Assigned</label><select class="form-select" id="assigned_to" name="assigned_to"><option value="">Anyone</option><?php foreach ($assignees as $user): ?><option value="<?= (int)$user['id'] ?>" <?= (int)($filters['assigned_to'] ?? 0) === (int)$user['id'] ? 'selected' : '' ?>><?= Security::e($user['name']) ?></option><?php endforeach; ?></select></div>
<?php endif; ?>
        <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
        <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
        <label class="filter-check"><input type="checkbox" name="has_attachment" value="1" <?= !empty($filters['has_attachment']) ? 'checked' : '' ?>> <span>Attachments only</span></label>
      </div>
    </details>
  </form>

  <div class="table-wrap">
    <table class="data-table contact-inbox-table">
      <thead>
        <tr>
          <th>Sender</th>
          <th>Subject</th>
          <th>Preview</th>
          <th>Status</th>
          <th>Priority</th>
          <th>Follow-up</th>
<?php if ($isContactSupervisor): ?>
          <th>Assigned</th>
<?php endif; ?>
          <th>Received</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
<?php if ($contactMessages === []): ?>
        <tr>
          <td colspan="<?= $isContactSupervisor ? '9' : '8' ?>">
            <div class="empty-state">
              <span class="empty-state__icon"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span>
              <strong class="empty-state__title"><?= Security::e($emptyTitle) ?></strong>
              <span class="empty-state__text"><?= Security::e($emptyText) ?></span>
            </div>
          </td>
        </tr>
<?php else: ?>
<?php foreach ($contactMessages as $message): ?>
<?php
    $rowClass = [];
    if ((int)$message['is_read'] === 0) {
        $rowClass[] = 'is-unread';
    }
    if (!empty($message['is_overdue'])) {
        $rowClass[] = 'is-overdue';
    }
?>
        <tr class="<?= Security::e(implode(' ', $rowClass)) ?>">
          <td>
            <strong><?= Security::e($message['name']) ?></strong>
            <small><?= Security::e($message['email']) ?><?= $message['phone'] ? ' | ' . Security::e($message['phone']) : '' ?></small>
          </td>
          <td>
            <strong><?= Security::e($message['subject'] ?: 'No subject') ?></strong>
            <?= !empty($message['has_attachment']) ? '<small><i class="fa-solid fa-paperclip" aria-hidden="true"></i> Attachment</small>' : '' ?>
          </td>
          <td class="contact-message-preview"><?= Security::e(safe_truncate((string)$message['message'], 100)) ?></td>
          <td>
            <span class="badge <?= Security::e(status_badge_class($message['status'])) ?>"><?= Security::e(status_label($message['status'])) ?></span>
            <small><?= (int)$message['is_read'] === 0 ? 'Unread' : 'Read' ?></small>
          </td>
          <td>
<?php if (($message['priority'] ?? 'normal') === 'urgent'): ?>
            <span class="badge badge--danger">Urgent</span>
<?php else: ?>
            <span class="badge badge--neutral">Normal</span>
<?php endif; ?>
          </td>
          <td>
<?php if (!empty($message['follow_up_at'])): ?>
            <?= Security::e(format_date($message['follow_up_at'])) ?>
            <?php if (!empty($message['is_overdue'])): ?><small class="text-danger">Overdue</small><?php elseif (!empty($message['is_due_today'])): ?><small>Due today</small><?php endif; ?>
<?php else: ?>
            <span class="text-muted">—</span>
            <?php if (!empty($message['is_overdue'])): ?><small class="text-danger">SLA overdue</small><?php endif; ?>
<?php endif; ?>
          </td>
<?php if ($isContactSupervisor): ?>
          <td><?= $message['assignee_name'] ? Security::e($message['assignee_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
<?php endif; ?>
          <td><?= Security::e(format_datetime($message['created_at'])) ?><small><?= Security::e(time_ago($message['created_at'])) ?></small></td>
          <td>
            <div class="row-actions contact-row-actions">
              <button class="btn btn--icon btn--primary" type="button" data-contact-open data-id="<?= (int)$message['id'] ?>" title="Open case" aria-label="Open case"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
              <button class="btn btn--icon btn--outline" type="button" data-contact-open data-contact-focus-reply="1" data-id="<?= (int)$message['id'] ?>" title="Reply by email" aria-label="Reply by email"><i class="fa-solid fa-reply" aria-hidden="true"></i></button>
              <button class="btn btn--icon btn--outline" type="button" data-contact-action="read" data-id="<?= (int)$message['id'] ?>" title="Mark read" aria-label="Mark read"><i class="fa-solid fa-envelope-open" aria-hidden="true"></i></button>
              <button class="btn btn--icon btn--danger" type="button" data-contact-action="archive" data-id="<?= (int)$message['id'] ?>" title="Archive" aria-label="Archive"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button>
            </div>
          </td>
        </tr>
<?php endforeach; ?>
<?php endif; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="Enquiries pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalMessages)) ?> messages</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(contact_enquiries_page_url($contactInboxPath, $filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(contact_enquiries_page_url($contactInboxPath, $filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
<?php endif; ?>
</section>

<div
  class="modal"
  data-contact-modal
  data-get-url="<?= Security::e(Url::to('api/contact/get-message.php')) ?>"
  data-reply-url="<?= Security::e(Url::to('api/contact/send-reply.php')) ?>"
  data-status-url="<?= Security::e(Url::to('api/contact/update-status.php')) ?>"
  data-assign-url="<?= Security::e(Url::to('api/contact/assign.php')) ?>"
  data-thread-url="<?= Security::e(Url::to('api/contact/start-thread.php')) ?>"
  data-case-url="<?= Security::e(Url::to('api/contact/save-case.php')) ?>"
  data-can-assign="<?= $isContactSupervisor ? '1' : '0' ?>"
  data-templates="<?= Security::e(json_encode($templates, JSON_UNESCAPED_UNICODE)) ?>"
  hidden
>
  <div class="modal__backdrop" data-contact-close></div>
  <div class="modal__dialog contact-modal contact-modal--workbench" role="dialog" aria-modal="true" aria-labelledby="contactModalTitle">
    <div class="modal__header contact-modal__header">
      <div class="contact-modal__header-copy">
        <h2 class="modal__title" id="contactModalTitle">Enquiry case</h2>
        <p class="contact-modal__header-sub" data-contact-header-sub>Load a case to review and reply.</p>
      </div>
      <div class="contact-modal__header-flags" data-contact-header-flags></div>
      <button class="modal__close" type="button" data-contact-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="modal__body contact-modal__body">
      <div class="contact-case-layout">
        <div class="contact-case-layout__read">
          <div class="contact-detail" data-contact-detail>
            <div class="contact-detail__loading">Loading case...</div>
          </div>
        </div>
        <div class="contact-case-layout__act">
          <section class="contact-act-panel" aria-label="Case management">
            <h3 class="contact-act-panel__title">Case management</h3>
<?php if ($isContactSupervisor): ?>
            <label class="form-field"><span>Assign to</span><select class="form-select" data-contact-assign><option value="">Unassigned</option><?php foreach ($assignees as $user): ?><option value="<?= (int)$user['id'] ?>"><?= Security::e($user['name']) ?><?= $user['role'] ? ' - ' . Security::e(role_label($user['role'])) : '' ?></option><?php endforeach; ?></select></label>
<?php else: ?>
            <div class="contact-assignment-note"><strong>Assigned to you</strong><span>Only the County Director can reassign public enquiries.</span></div>
<?php endif; ?>
            <div class="contact-act-grid">
              <label class="form-field"><span>Status</span>
                <select class="form-select" data-contact-status>
                  <option value="read">Mark read</option>
                  <option value="unread">Mark unread</option>
                  <option value="in_progress">In progress</option>
                  <option value="archive">Archive</option>
                  <option value="restore">Restore</option>
                </select>
              </label>
              <label class="form-field"><span>Priority</span>
                <select class="form-select" data-contact-priority>
                  <option value="normal">Normal</option>
                  <option value="urgent">Urgent</option>
                </select>
              </label>
              <label class="form-field"><span>Follow-up</span>
                <input class="form-input" type="date" data-contact-follow-up>
              </label>
            </div>
            <label class="form-field">
              <span>Internal note <small>(not emailed)</small></span>
              <textarea class="form-textarea" rows="3" data-contact-internal-note placeholder="Private staff notes..."></textarea>
            </label>
            <div class="contact-act-panel__actions">
<?php if ($isContactSupervisor): ?>
              <button class="btn btn--outline btn--sm" type="button" data-contact-save-assign><i class="fa-solid fa-user-check" aria-hidden="true"></i> Assignment</button>
<?php endif; ?>
              <button class="btn btn--outline btn--sm" type="button" data-contact-save-status><i class="fa-solid fa-flag" aria-hidden="true"></i> Status</button>
              <button class="btn btn--outline btn--sm" type="button" data-contact-save-case><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save case</button>
              <button class="btn btn--outline btn--sm" type="button" data-contact-start-thread><i class="fa-solid fa-comments" aria-hidden="true"></i> Internal chat</button>
            </div>
          </section>

          <section class="contact-reply-composer" aria-label="Email reply">
            <div class="contact-reply-composer__header">
              <div>
                <h3>Email reply</h3>
                <p>Reply goes to the citizen email on record.</p>
              </div>
              <span class="badge badge--neutral" data-contact-reply-recipient>Recipient</span>
            </div>
            <label class="form-field"><span>Template</span>
              <select class="form-select" data-contact-template>
                <option value="">Optional template…</option>
<?php foreach ($templates as $tpl): ?>
                <option value="<?= Security::e($tpl['id']) ?>"><?= Security::e($tpl['label']) ?></option>
<?php endforeach; ?>
              </select>
            </label>
            <label class="form-field"><span>Subject</span><input class="form-input" type="text" data-contact-reply-subject maxlength="220" placeholder="Reply subject"></label>
            <label class="form-field"><span>Message</span><textarea class="form-textarea" rows="7" data-contact-response-note placeholder="Write a clear professional reply..."></textarea></label>
          </section>

          <div class="alert alert--danger" data-contact-error hidden></div>
          <div class="alert alert--success" data-contact-success hidden></div>
        </div>
      </div>
    </div>
    <div class="modal__footer contact-modal__footer">
      <button class="btn btn--outline" type="button" data-contact-close>Close</button>
      <button class="btn btn--primary" type="button" data-contact-send-reply><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send email reply</button>
    </div>
  </div>
</div>
