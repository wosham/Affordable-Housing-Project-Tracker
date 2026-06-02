<?php
$messageUserId = (int)Auth::id();
$messageRole = (string)(Auth::role() ?: ($adminRole ?? 'staff'));
$messageRecipients = MessageThread::recipientOptions($messageUserId, $messageRole);
$messageAudienceOptions = MessageThread::audienceOptions($messageUserId, $messageRole);
$messageProjects = Database::fetchAll("SELECT id, name FROM projects ORDER BY name ASC");
$initialThreadId = Security::cleanInt($_GET['thread'] ?? 0);
?>

<section class="messages-page" data-messages-app data-current-user="<?= (int)$messageUserId ?>" data-initial-thread="<?= (int)$initialThreadId ?>">
  <div class="messages-toolbar card">
    <div>
      <span class="sa-panel-label"><i class="fa-solid fa-comments" aria-hidden="true"></i> Internal communications</span>
      <h2>Messages</h2>
      <p>Direct messages, project channels and shared team conversations.</p>
    </div>
    <button class="btn btn--primary" type="button" data-compose-open>
      <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> New Message
    </button>
  </div>

  <div class="messages-shell card">
    <aside class="messages-sidebar" aria-label="Message folders">
      <form class="messages-search" data-message-search>
        <label class="sr-only" for="messageSearch">Search messages</label>
        <input class="form-input" type="search" id="messageSearch" name="q" placeholder="Search subject, project or message...">
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
        <span class="thread-empty__icon"><i class="fa-solid fa-comments" aria-hidden="true"></i></span>
        <strong>Select a conversation</strong>
        <span>Choose a thread from the inbox or compose a new message.</span>
      </div>

      <div class="thread-view" data-thread-view hidden>
        <header class="thread-header">
          <button class="btn btn--icon thread-back" type="button" data-thread-back aria-label="Back to inbox"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
          <div>
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
        <h3 id="composeTitle">New message</h3>
        <button class="btn btn--icon" type="button" data-compose-close aria-label="Close compose"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </header>
      <form class="compose-form" data-compose-form>
        <div class="form-grid form-grid--2">
          <div class="form-group">
            <label class="form-label" for="composeType">Type</label>
            <select class="form-select" id="composeType" name="type">
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
          <input class="form-input" type="text" id="composeSubject" name="subject" maxlength="255" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="composeProject">Project</label>
          <select class="form-select" id="composeProject" name="project_id">
            <option value="">No project link</option>
<?php foreach ($messageProjects as $project): ?>
            <option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option>
<?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="composeAudience">Audience shortcuts</label>
          <select class="form-select compose-select" id="composeAudience" name="audience_targets" multiple size="5">
<?php if ($messageAudienceOptions === []): ?>
            <option disabled>No role groups available yet</option>
<?php else: ?>
<?php foreach ($messageAudienceOptions as $option): ?>
            <option value="<?= Security::e($option['value']) ?>"><?= Security::e($option['label']) ?> (<?= (int)$option['count'] ?>)</option>
<?php endforeach; ?>
<?php endif; ?>
            <option value="project:selected">Selected project team</option>
          </select>
          <small class="form-help">Use role groups for broadcasts, project team for site/project conversations, or select individuals below.</small>
        </div>
        <div class="form-group">
          <label class="form-label" for="composeRecipients">Individual recipients</label>
          <select class="form-select compose-select" id="composeRecipients" name="recipients" multiple size="8">
<?php if ($messageRecipients === []): ?>
            <option disabled>No active recipients available for your role yet</option>
<?php else: ?>
<?php foreach ($messageRecipients as $recipient): ?>
            <option value="<?= (int)$recipient['id'] ?>"><?= Security::e(trim((string)$recipient['name']) . ' - ' . role_label((string)$recipient['role_slug'])) ?></option>
<?php endforeach; ?>
<?php endif; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="composeBody">Message</label>
          <textarea class="form-input" id="composeBody" name="body" rows="7" required></textarea>
        </div>
        <div class="message-compose__footer">
          <label class="btn btn--outline message-file">
            <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Attach
            <input type="file" data-compose-attachment multiple hidden>
          </label>
          <div class="attachment-list" data-compose-attachments></div>
          <button class="btn btn--primary" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Message</button>
        </div>
      </form>
    </div>
  </div>
</section>
