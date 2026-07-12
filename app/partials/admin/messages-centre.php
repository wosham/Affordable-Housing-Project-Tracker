<?php
$messageUserId = (int)Auth::id();
$messageRole = (string)(Auth::role() ?: ($adminRole ?? 'staff'));
$messageRecipients = MessageThread::recipientOptions($messageUserId, $messageRole);
$messageAudienceOptions = MessageThread::audienceOptions($messageUserId, $messageRole);
$messageProjectIds = ProjectAccess::visibleProjectIds($messageUserId, $messageRole);
if ($messageProjectIds === []) {
    $messageProjects = [];
} else {
    $placeholders = implode(',', array_fill(0, count($messageProjectIds), '?'));
    $messageProjects = Database::fetchAll(
        "SELECT id, name FROM projects WHERE id IN ({$placeholders}) ORDER BY name ASC",
        $messageProjectIds
    );
}
$initialThreadId = Security::cleanInt($_GET['thread'] ?? 0);
$recipientCount = count($messageRecipients);

$policyOneLiner = match ($messageRole) {
    'superadmin' => 'You can message any active staff, or narrow to a project team.',
    'manager' => 'You can only message people on your assigned projects, plus county leadership.',
    'consultant' => 'You can only message managers, contractors and clerks on your projects, plus county leadership.',
    'contractor' => 'You can only message managers, consultants, clerks and finance on your projects, plus county leadership.',
    'clerk' => 'You can only message managers, consultants, contractors and interns on your sites, plus county leadership.',
    'finance' => 'You can message managers, contractors and county leadership about payments and certification.',
    'intern' => 'You can only message managers and clerks on your assigned projects, plus county leadership.',
    default => 'Messaging is limited to people you share projects with and county leadership.',
};

$directoryPayload = array_map(static fn (array $row): array => [
    'id' => (int)$row['id'],
    'name' => trim((string)($row['name'] ?? '')) ?: 'Staff User',
    'email' => (string)($row['email'] ?? ''),
    'role' => (string)($row['role_slug'] ?? ''),
    'roleLabel' => role_label((string)($row['role_slug'] ?? '')),
], $messageRecipients);

$audiencePayload = array_map(static fn (array $row): array => [
    'value' => (string)$row['value'],
    'label' => (string)$row['label'],
    'count' => (int)$row['count'],
], $messageAudienceOptions);
?>

<section
  class="messages-page"
  data-messages-app
  data-current-user="<?= (int)$messageUserId ?>"
  data-current-role="<?= Security::e($messageRole) ?>"
  data-initial-thread="<?= (int)$initialThreadId ?>"
  data-recipient-count="<?= (int)$recipientCount ?>"
  data-directory="<?= Security::e(json_encode($directoryPayload, JSON_UNESCAPED_UNICODE)) ?>"
  data-audience="<?= Security::e(json_encode($audiencePayload, JSON_UNESCAPED_UNICODE)) ?>"
>
  <div class="messages-toolbar card">
    <div class="messages-toolbar__copy">
      <span class="sa-panel-label"><i class="fa-solid fa-comments" aria-hidden="true"></i> Internal communications</span>
      <h2>Message Centre</h2>
      <p class="messages-toolbar__lead">
        Project-scoped staff messaging
        <button class="messages-help-btn" type="button" data-policy-toggle aria-expanded="false" title="Who can I message?">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span class="sr-only">Who can I message?</span>
        </button>
      </p>
      <p class="messages-policy-line" data-policy-panel hidden>
        <?= Security::e($policyOneLiner) ?>
        Threads and attachments are only visible to participants.
      </p>
    </div>
    <div class="messages-toolbar__actions">
      <span class="messages-policy-badge" title="People you may contact">
        <i class="fa-solid fa-users" aria-hidden="true"></i>
        <?= (int)$recipientCount ?> contact<?= $recipientCount === 1 ? '' : 's' ?>
      </span>
      <button class="btn btn--primary" type="button" data-compose-open>
        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> New Message
      </button>
    </div>
  </div>

  <div class="messages-shell card">
    <aside class="messages-sidebar" aria-label="Message folders">
      <form class="messages-search" data-message-search>
        <label class="sr-only" for="messageSearch">Search messages</label>
        <div class="messages-search__wrap">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <input class="form-input" type="search" id="messageSearch" name="q" placeholder="Search mail" autocomplete="off">
        </div>
      </form>

      <div class="messages-tabs" role="tablist" aria-label="Message folders">
        <button class="messages-tab is-active" type="button" data-box="inbox"><i class="fa-solid fa-inbox" aria-hidden="true"></i> Inbox</button>
        <button class="messages-tab" type="button" data-box="unread"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Unread</button>
        <button class="messages-tab" type="button" data-box="sent"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Sent</button>
        <button class="messages-tab" type="button" data-box="archived"><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archived</button>
      </div>

      <div class="messages-type-filter">
        <select class="form-select" data-type-filter aria-label="Filter message type">
          <option value="">All conversations</option>
          <option value="direct">Direct</option>
          <option value="group">Group</option>
          <option value="project-channel">Project channels</option>
        </select>
      </div>

      <div class="thread-list" data-thread-list aria-live="polite"></div>
    </aside>

    <section class="messages-thread" data-thread-panel aria-label="Selected conversation">
      <div class="thread-empty" data-thread-empty>
        <span class="thread-empty__icon"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span>
        <strong data-empty-title>No conversation selected</strong>
        <span data-empty-text>Choose a thread from the inbox, or compose a new message.</span>
        <button class="btn btn--primary" type="button" data-compose-open>
          <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Compose
        </button>
      </div>

      <div class="thread-view" data-thread-view hidden>
        <header class="thread-header">
          <button class="btn btn--icon thread-back" type="button" data-thread-back aria-label="Back to inbox"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
          <div class="thread-header__main">
            <h3 data-thread-subject></h3>
            <p data-thread-meta></p>
          </div>
          <button class="btn btn--outline" type="button" data-archive-thread><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archive</button>
        </header>

        <div class="thread-participants" data-thread-participants></div>
        <div class="message-list" data-message-list></div>

        <form class="message-compose" data-reply-form>
          <label class="sr-only" for="replyBody">Reply</label>
          <textarea class="form-input" id="replyBody" name="body" rows="3" placeholder="Write a reply..." required></textarea>
          <div class="message-compose__footer">
            <label class="btn btn--outline message-file">
              <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Attach
              <input type="file" data-reply-attachment multiple hidden>
            </label>
            <div class="attachment-list" data-reply-attachments></div>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send</button>
          </div>
        </form>
      </div>
    </section>
  </div>

  <div class="messages-modal" data-compose-modal hidden>
    <div class="messages-modal__panel" role="dialog" aria-modal="true" aria-labelledby="composeTitle">
      <header class="messages-modal__header">
        <div>
          <h3 id="composeTitle">New message</h3>
          <p class="messages-modal__sub">Search people on your projects. Groups expand only to allowed contacts.</p>
        </div>
        <button class="btn btn--icon" type="button" data-compose-close aria-label="Close compose"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </header>
      <form class="compose-form" data-compose-form>
        <div class="compose-to-field" data-compose-to-field>
          <span class="form-label" id="composeToLabel">To</span>
          <div class="compose-to-box" role="group" aria-labelledby="composeToLabel">
            <div class="compose-chips" data-compose-chips aria-live="polite"></div>
            <input
              class="compose-to-input"
              type="search"
              id="composeRecipientSearch"
              data-compose-search
              placeholder="Search name, role or email..."
              autocomplete="off"
              aria-autocomplete="list"
              aria-controls="composeRecipientResults"
              aria-expanded="false"
              aria-label="Search recipients"
            >
          </div>
          <div class="compose-results" id="composeRecipientResults" data-compose-results hidden role="listbox" aria-label="Matching contacts"></div>
          <div class="compose-to-actions">
            <label class="sr-only" for="composeGroupAdd">Add group</label>
            <select class="form-select compose-group-select" id="composeGroupAdd" data-compose-group>
              <option value="">+ Add group…</option>
<?php foreach ($messageAudienceOptions as $option): ?>
              <option value="<?= Security::e($option['value']) ?>"><?= Security::e($option['label']) ?> (<?= (int)$option['count'] ?>)</option>
<?php endforeach; ?>
              <option value="project:selected">Selected project team</option>
            </select>
            <span class="compose-to-meta" data-compose-to-meta><?= (int)$recipientCount ?> searchable contacts</span>
          </div>
          <input type="hidden" name="recipients_json" data-compose-recipients-json value="[]">
          <input type="hidden" name="audience_json" data-compose-audience-json value="[]">
        </div>

        <div class="form-grid form-grid--2">
          <div class="form-group">
            <label class="form-label" for="composeType">Type</label>
            <select class="form-select" id="composeType" name="type" data-compose-type>
              <option value="direct">Direct</option>
              <option value="group">Group</option>
              <option value="project-channel">Project channel</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="composePriority">Priority</label>
            <select class="form-select" id="composePriority" name="priority">
              <option value="normal">Normal</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="composeSubject">Subject</label>
          <input class="form-input" type="text" id="composeSubject" name="subject" maxlength="255" required placeholder="Subject">
        </div>

        <div class="form-group">
          <label class="form-label" for="composeProject">Project <span class="form-label-hint" data-project-required-hint hidden>(required for project channel)</span></label>
          <select class="form-select" id="composeProject" name="project_id" data-compose-project>
            <option value="">No project link</option>
<?php foreach ($messageProjects as $project): ?>
            <option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option>
<?php endforeach; ?>
          </select>
<?php if ($messageProjects === []): ?>
          <small class="form-help form-help--warn">No projects linked yet. You can still message county leadership if available.</small>
<?php else: ?>
          <small class="form-help">Link a project to narrow search to that team only.</small>
<?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="composeBody">Message</label>
          <textarea class="form-input" id="composeBody" name="body" rows="7" required placeholder="Write your message..."></textarea>
        </div>

        <div class="message-compose__footer">
          <label class="btn btn--outline message-file">
            <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Attach
            <input type="file" data-compose-attachment multiple hidden>
          </label>
          <div class="attachment-list" data-compose-attachments></div>
          <button class="btn btn--primary" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send</button>
        </div>
      </form>
    </div>
  </div>
</section>
