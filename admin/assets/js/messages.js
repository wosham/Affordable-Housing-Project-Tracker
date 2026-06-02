(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function selectedValues(select) {
    return qsa('option:checked', select).map(function (option) { return option.value; });
  }

  function initMessages(app) {
    var state = { box: 'inbox', type: '', q: '', threadId: Number(app.getAttribute('data-initial-thread') || 0), composeTokens: [], replyTokens: [] };
    var shell = qs('.messages-shell', app);
    var list = qs('[data-thread-list]', app);
    var empty = qs('[data-thread-empty]', app);
    var view = qs('[data-thread-view]', app);
    var messageList = qs('[data-message-list]', app);
    var modal = qs('[data-compose-modal]', app);

    function setLoadingThreads() {
      list.innerHTML = '<div class="empty-state"><strong class="empty-state__title">Loading messages...</strong></div>';
    }

    function loadInbox() {
      setLoadingThreads();
      var params = new URLSearchParams({ box: state.box, type: state.type, q: state.q, per_page: '30' });
      window.AHPTC.request('api/messages/inbox.php?' + params.toString()).then(function (data) {
        renderThreads(data.threads || []);
      }).catch(function (error) {
        list.innerHTML = '<div class="empty-state"><strong class="empty-state__title">' + escapeHtml(error.message || 'Messages could not be loaded.') + '</strong></div>';
      });
    }

    function renderThreads(threads) {
      if (!threads.length) {
        list.innerHTML = '<div class="empty-state"><strong class="empty-state__title">No conversations found</strong><span class="empty-state__text">Start a new message or adjust your filters.</span></div>';
        return;
      }
      list.innerHTML = threads.map(function (thread) {
        return '<button class="thread-item ' + (thread.unreadCount > 0 ? 'unread ' : '') + (thread.id === state.threadId ? 'is-active' : '') + '" type="button" data-thread-id="' + thread.id + '">' +
          '<span class="thread-item__top"><span class="thread-item__subject">' + escapeHtml(thread.subject) + '</span>' + (thread.unreadCount > 0 ? '<span class="thread-count">' + thread.unreadCount + '</span>' : '') + '</span>' +
          '<span class="thread-item__preview">' + escapeHtml(thread.lastMessage || 'No messages yet') + '</span>' +
          '<span class="thread-item__meta"><span>' + escapeHtml(thread.projectName || thread.type) + '</span><span>' + escapeHtml(thread.timeAgo || '') + '</span></span>' +
        '</button>';
      }).join('');
    }

    function loadThread(id) {
      if (!id) return;
      state.threadId = Number(id);
      shell.classList.add('has-thread');
      qsa('.thread-item', list).forEach(function (item) {
        item.classList.toggle('is-active', Number(item.getAttribute('data-thread-id')) === state.threadId);
      });
      empty.hidden = true;
      view.hidden = false;
      messageList.innerHTML = '<div class="empty-state"><strong class="empty-state__title">Loading thread...</strong></div>';

      window.AHPTC.request('api/messages/thread.php?thread_id=' + encodeURIComponent(state.threadId)).then(function (data) {
        renderThread(data);
        loadInbox();
      }).catch(function (error) {
        messageList.innerHTML = '<div class="empty-state"><strong class="empty-state__title">' + escapeHtml(error.message || 'Thread could not be loaded.') + '</strong></div>';
      });
    }

    function renderThread(data) {
      var thread = data.thread || {};
      qs('[data-thread-subject]', app).textContent = thread.subject || 'Conversation';
      qs('[data-thread-meta]', app).textContent = [thread.type, thread.projectName, thread.priority === 'urgent' ? 'Urgent' : 'Normal'].filter(Boolean).join(' • ');
      qs('[data-thread-participants]', app).innerHTML = (data.participants || []).map(function (person) {
        return '<span class="participant-chip"><i class="fa-solid fa-user" aria-hidden="true"></i>' + escapeHtml(person.name) + ' <small>' + escapeHtml(person.roleLabel || person.role) + '</small></span>';
      }).join('');
      messageList.innerHTML = (data.messages || []).map(renderMessage).join('') || '<div class="empty-state"><strong class="empty-state__title">No messages yet</strong></div>';
      messageList.scrollTop = messageList.scrollHeight;
    }

    function renderMessage(message) {
      var mine = Number(message.senderId) === Number((app.getAttribute('data-current-user') || 0));
      var attachments = (message.attachments || []).map(function (file) {
        return '<a class="attachment-chip" href="' + escapeHtml(file.url) + '" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip" aria-hidden="true"></i>' + escapeHtml(file.name) + '</a>';
      }).join('');
      return '<article class="message-bubble ' + (mine ? 'is-mine ' : '') + (message.isDeleted ? 'is-deleted' : '') + '" data-message-id="' + message.id + '">' +
        '<div class="message-bubble__meta"><strong>' + escapeHtml(message.senderName) + '</strong><span>' + escapeHtml(message.createdLabel) + '</span></div>' +
        '<div class="message-bubble__body">' + escapeHtml(message.body) + '</div>' +
        (attachments ? '<div class="attachment-list">' + attachments + '</div>' : '') +
        (!message.isDeleted ? '<div class="message-bubble__actions"><button class="btn btn--sm btn--outline" type="button" data-delete-message="' + message.id + '"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button></div>' : '') +
      '</article>';
    }

    function uploadFiles(files, target) {
      var store = target === 'compose' ? state.composeTokens : state.replyTokens;
      var wrap = qs(target === 'compose' ? '[data-compose-attachments]' : '[data-reply-attachments]', app);
      Array.prototype.slice.call(files || []).forEach(function (file) {
        var form = new FormData();
        form.append('attachment', file);
        window.AHPTC.request('api/messages/upload-attachment.php', { method: 'POST', body: form }).then(function (data) {
          var attachment = data.attachment || {};
          if (attachment.token) store.push(attachment.token);
          wrap.insertAdjacentHTML('beforeend', '<span class="attachment-chip"><i class="fa-solid fa-paperclip" aria-hidden="true"></i>' + escapeHtml(attachment.name || file.name) + '</span>');
        }).catch(function (error) {
          window.alert(error.message || 'Attachment could not be uploaded.');
        });
      });
    }

    function sendReply(form) {
      var body = qs('textarea[name="body"]', form).value.trim();
      if (!body || !state.threadId) return;
      window.AHPTC.request('api/messages/send.php', {
        method: 'POST',
        body: { thread_id: state.threadId, body: body, attachment_tokens: state.replyTokens }
      }).then(function () {
        form.reset();
        state.replyTokens = [];
        qs('[data-reply-attachments]', app).innerHTML = '';
        loadThread(state.threadId);
      }).catch(function (error) {
        window.alert(error.message || 'Message could not be sent.');
      });
    }

    function sendCompose(form) {
      var recipients = selectedValues(qs('[name="recipients"]', form));
      var audienceTargets = selectedValues(qs('[name="audience_targets"]', form));
      window.AHPTC.request('api/messages/send.php', {
        method: 'POST',
        body: {
          subject: qs('[name="subject"]', form).value,
          type: qs('[name="type"]', form).value,
          priority: qs('[name="priority"]', form).value,
          project_id: qs('[name="project_id"]', form).value,
          recipients: recipients,
          audience_targets: audienceTargets,
          body: qs('[name="body"]', form).value,
          attachment_tokens: state.composeTokens
        }
      }).then(function (data) {
        form.reset();
        state.composeTokens = [];
        qs('[data-compose-attachments]', app).innerHTML = '';
        modal.hidden = true;
        loadInbox();
        loadThread(data.threadId);
      }).catch(function (error) {
        window.alert(error.message || 'Message could not be sent.');
      });
    }

    list.addEventListener('click', function (event) {
      var item = event.target.closest('[data-thread-id]');
      if (item) loadThread(item.getAttribute('data-thread-id'));
    });

    qsa('[data-box]', app).forEach(function (button) {
      button.addEventListener('click', function () {
        qsa('[data-box]', app).forEach(function (tab) { tab.classList.remove('is-active'); });
        button.classList.add('is-active');
        state.box = button.getAttribute('data-box') || 'inbox';
        loadInbox();
      });
    });

    qs('[data-type-filter]', app).addEventListener('change', function (event) {
      state.type = event.target.value;
      loadInbox();
    });

    qs('[data-message-search]', app).addEventListener('submit', function (event) {
      event.preventDefault();
      state.q = qs('[name="q"]', event.currentTarget).value.trim();
      loadInbox();
    });

    var searchTimer = null;
    qs('[name="q"]', app).addEventListener('input', function (event) {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(function () {
        state.q = event.target.value.trim();
        loadInbox();
      }, 350);
    });

    qs('[data-reply-form]', app).addEventListener('submit', function (event) {
      event.preventDefault();
      sendReply(event.currentTarget);
    });

    qs('[data-compose-form]', app).addEventListener('submit', function (event) {
      event.preventDefault();
      sendCompose(event.currentTarget);
    });

    qs('[data-compose-open]', app).addEventListener('click', function () { modal.hidden = false; });
    qs('[data-compose-close]', app).addEventListener('click', function () { modal.hidden = true; });
    modal.addEventListener('click', function (event) {
      if (event.target === modal) modal.hidden = true;
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) modal.hidden = true;
    });
    qs('[data-thread-back]', app).addEventListener('click', function () { shell.classList.remove('has-thread'); });
    qs('[data-compose-attachment]', app).addEventListener('change', function (event) { uploadFiles(event.target.files, 'compose'); event.target.value = ''; });
    qs('[data-reply-attachment]', app).addEventListener('change', function (event) { uploadFiles(event.target.files, 'reply'); event.target.value = ''; });

    qs('[data-archive-thread]', app).addEventListener('click', function () {
      if (!state.threadId) return;
      window.AHPTC.request('api/messages/delete.php', { method: 'POST', body: { action: 'archive-thread', thread_id: state.threadId } }).then(function () {
        shell.classList.remove('has-thread');
        view.hidden = true;
        empty.hidden = false;
        state.threadId = 0;
        loadInbox();
      });
    });

    messageList.addEventListener('click', function (event) {
      var button = event.target.closest('[data-delete-message]');
      if (!button || !window.confirm('Delete this message?')) return;
      window.AHPTC.request('api/messages/delete.php', { method: 'POST', body: { message_id: button.getAttribute('data-delete-message') } }).then(function () {
        loadThread(state.threadId);
      }).catch(function (error) {
        window.alert(error.message || 'Message could not be deleted.');
      });
    });

    loadInbox();
    if (state.threadId) loadThread(state.threadId);
    window.setInterval(function () {
      loadInbox();
      if (state.threadId) loadThread(state.threadId);
    }, 30000);
  }

  function init() {
    qsa('[data-messages-app]').forEach(initMessages);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
