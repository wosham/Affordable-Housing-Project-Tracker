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

  function parseJsonAttr(el, name, fallback) {
    try {
      var raw = el.getAttribute(name);
      if (!raw) return fallback;
      var parsed = JSON.parse(raw);
      return parsed == null ? fallback : parsed;
    } catch (e) {
      return fallback;
    }
  }

  function initMessages(app) {
    var state = {
      box: 'inbox',
      type: '',
      q: '',
      page: 1,
      totalPages: 1,
      threadId: Number(app.getAttribute('data-initial-thread') || 0),
      composeTokens: [],
      replyTokens: [],
      pendingUploads: 0,
      threadCount: 0,
      directory: parseJsonAttr(app, 'data-directory', []),
      audience: parseJsonAttr(app, 'data-audience', []),
      selectedPeople: {},
      selectedGroups: {},
      resultIndex: -1
    };
    var currentRole = app.getAttribute('data-current-role') || '';
    var shell = qs('.messages-shell', app);
    var list = qs('[data-thread-list]', app);
    var empty = qs('[data-thread-empty]', app);
    var view = qs('[data-thread-view]', app);
    var messageList = qs('[data-message-list]', app);
    var modal = qs('[data-compose-modal]', app);
    var replyForm = qs('[data-reply-form]', app);
    var composeForm = qs('[data-compose-form]', app);
    var projectSelect = qs('[data-compose-project]', app);
    var typeSelect = qs('[data-compose-type]', app);
    var projectRequiredHint = qs('[data-project-required-hint]', app);
    var emptyTitle = qs('[data-empty-title]', app);
    var emptyText = qs('[data-empty-text]', app);
    var searchInput = qs('[data-compose-search]', app);
    var resultsBox = qs('[data-compose-results]', app);
    var chipsBox = qs('[data-compose-chips]', app);
    var groupSelect = qs('[data-compose-group]', app);
    var toMeta = qs('[data-compose-to-meta]', app);
    var recipientsJson = qs('[data-compose-recipients-json]', app);
    var audienceJson = qs('[data-compose-audience-json]', app);
    var policyToggle = qs('[data-policy-toggle]', app);
    var policyPanel = qs('[data-policy-panel]', app);

    function showEmpty(title, text) {
      if (emptyTitle && title) emptyTitle.textContent = title;
      if (emptyText && text) emptyText.textContent = text;
      empty.hidden = false;
      view.hidden = true;
      shell.classList.remove('has-thread');
    }

    function showThreadPanel() {
      empty.hidden = true;
      view.hidden = false;
      shell.classList.add('has-thread');
    }

    function setLoadingThreads() {
      list.innerHTML = '<div class="empty-state"><strong class="empty-state__title">Loading messages...</strong></div>';
    }

    function loadInbox(append) {
      if (!append) setLoadingThreads();
      var params = new URLSearchParams({
        box: state.box,
        type: state.type,
        q: state.q,
        page: String(state.page),
        per_page: '10'
      });
      window.AHPTC.request('api/messages/inbox.php?' + params.toString()).then(function (data) {
        state.totalPages = Number((data.pagination || {}).totalPages || 1);
        var threads = data.threads || [];
        if (!append) state.threadCount = Number((data.pagination || {}).total || threads.length || 0);
        renderThreads(threads, append);
        if (!state.threadId) {
          if (state.threadCount === 0 && state.box === 'inbox' && !state.q && !state.type) {
            showEmpty('Your inbox is empty', 'Compose a message to project colleagues or county leadership.');
          } else if (!append && threads.length === 0) {
            showEmpty('No conversations found', 'Try another folder, clear filters, or compose a new message.');
          } else {
            showEmpty('No conversation selected', 'Choose a thread from the list, or compose a new message.');
          }
        }
      }).catch(function (error) {
        list.innerHTML = '<div class="empty-state"><strong class="empty-state__title">' + escapeHtml(error.message || 'Messages could not be loaded.') + '</strong></div>';
      });
    }

    function renderThreads(threads, append) {
      if (!threads.length) {
        if (!append) {
          list.innerHTML = '<div class="empty-state"><strong class="empty-state__title">No conversations found</strong><span class="empty-state__text">Start a new message or adjust filters.</span></div>';
        }
        return;
      }
      var html = threads.map(function (thread) {
        return '<button class="thread-item ' + (thread.unreadCount > 0 ? 'unread ' : '') + (thread.id === state.threadId ? 'is-active' : '') + '" type="button" data-thread-id="' + thread.id + '">' +
          '<span class="thread-item__top"><span class="thread-item__subject">' + escapeHtml(thread.subject) + '</span>' + (thread.unreadCount > 0 ? '<span class="thread-count">' + thread.unreadCount + '</span>' : '') + '</span>' +
          '<span class="thread-item__preview">' + escapeHtml(thread.lastMessage || 'No messages yet') + '</span>' +
          '<span class="thread-item__meta"><span>' + escapeHtml(thread.projectName || thread.type) + '</span><span>' + escapeHtml(thread.timeAgo || '') + '</span></span>' +
        '</button>';
      }).join('');
      if (state.page < state.totalPages) {
        html += '<button class="btn btn--outline messages-load-more" type="button" data-thread-more>Load more</button>';
      }
      if (append) {
        var more = qs('[data-thread-more]', list);
        if (more) more.remove();
        list.insertAdjacentHTML('beforeend', html);
      } else {
        list.innerHTML = html;
      }
    }

    function loadThread(id, options) {
      if (!id) return;
      var opts = options || {};
      var markRead = opts.markRead !== false;
      var silent = !!opts.silent;
      state.threadId = Number(id);
      showThreadPanel();
      qsa('.thread-item', list).forEach(function (item) {
        item.classList.toggle('is-active', Number(item.getAttribute('data-thread-id')) === state.threadId);
      });
      if (!silent) {
        messageList.innerHTML = '<div class="empty-state"><strong class="empty-state__title">Loading thread...</strong></div>';
      }

      window.AHPTC.request('api/messages/thread.php?thread_id=' + encodeURIComponent(state.threadId)).then(function (data) {
        renderThread(data);
        if (markRead) {
          markThreadRead();
          loadInbox();
        }
      }).catch(function (error) {
        if (!silent) {
          messageList.innerHTML = '<div class="empty-state"><strong class="empty-state__title">' + escapeHtml(error.message || 'Thread could not be loaded.') + '</strong></div>';
        }
      });
    }

    function renderThread(data) {
      var thread = data.thread || {};
      qs('[data-thread-subject]', app).textContent = thread.subject || 'Conversation';
      qs('[data-thread-meta]', app).textContent = [
        thread.type,
        thread.projectName,
        thread.priority === 'urgent' ? 'Urgent' : 'Normal',
        (data.participants || []).length ? ((data.participants || []).length + ' participants') : ''
      ].filter(Boolean).join(' · ');
      qs('[data-thread-participants]', app).innerHTML = (data.participants || []).map(function (person) {
        return '<span class="participant-chip"><i class="fa-solid fa-user" aria-hidden="true"></i>' + escapeHtml(person.name) + ' <small>' + escapeHtml(person.roleLabel || person.role) + '</small></span>';
      }).join('') || '<span class="participant-chip">Participants unavailable</span>';
      messageList.innerHTML = (data.messages || []).map(renderMessage).join('') || '<div class="empty-state"><strong class="empty-state__title">No messages yet</strong></div>';
      messageList.scrollTop = messageList.scrollHeight;
    }

    function renderMessage(message) {
      var mine = Number(message.senderId) === Number((app.getAttribute('data-current-user') || 0));
      var canDelete = mine || currentRole === 'superadmin';
      var attachments = (message.attachments || []).map(function (file) {
        return '<a class="attachment-chip" href="' + escapeHtml(file.url) + '" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip" aria-hidden="true"></i>' + escapeHtml(file.name) + '</a>';
      }).join('');
      return '<article class="message-bubble ' + (mine ? 'is-mine ' : '') + (message.isDeleted ? 'is-deleted' : '') + '" data-message-id="' + message.id + '">' +
        '<div class="message-bubble__meta"><strong>' + escapeHtml(message.senderName) + '</strong><span>' + escapeHtml(message.createdLabel) + '</span></div>' +
        '<div class="message-bubble__body">' + escapeHtml(message.body) + '</div>' +
        (attachments ? '<div class="attachment-list">' + attachments + '</div>' : '') +
        (!message.isDeleted && canDelete ? '<div class="message-bubble__actions"><button class="btn btn--sm btn--outline" type="button" data-delete-message="' + message.id + '"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button></div>' : '') +
      '</article>';
    }

    function markThreadRead() {
      if (!state.threadId) return;
      window.AHPTC.request('api/messages/mark-read.php', { method: 'POST', body: { thread_id: state.threadId } }).catch(function () {
        return null;
      });
    }

    function setSendDisabled(disabled) {
      qsa('[data-reply-form] button[type="submit"], [data-compose-form] button[type="submit"]', app).forEach(function (button) {
        button.disabled = disabled || state.pendingUploads > 0;
      });
    }

    function uploadFiles(files, target) {
      var store = target === 'compose' ? state.composeTokens : state.replyTokens;
      var wrap = qs(target === 'compose' ? '[data-compose-attachments]' : '[data-reply-attachments]', app);
      Array.prototype.slice.call(files || []).forEach(function (file) {
        state.pendingUploads += 1;
        setSendDisabled(false);
        var form = new FormData();
        form.append('attachment', file);
        window.AHPTC.request('api/messages/upload-attachment.php', { method: 'POST', body: form }).then(function (data) {
          var attachment = data.attachment || {};
          if (attachment.token) store.push(attachment.token);
          wrap.insertAdjacentHTML('beforeend', '<span class="attachment-chip"><i class="fa-solid fa-paperclip" aria-hidden="true"></i>' + escapeHtml(attachment.name || file.name) + '</span>');
        }).catch(function (error) {
          window.alert(error.message || 'Attachment could not be uploaded.');
        }).finally(function () {
          state.pendingUploads = Math.max(0, state.pendingUploads - 1);
          setSendDisabled(false);
        });
      });
    }

    /* ---------- Compose: search + chips + groups ---------- */

    function selectedRecipientIds() {
      return Object.keys(state.selectedPeople).map(Number).filter(function (id) { return id > 0; });
    }

    function selectedGroupValues() {
      return Object.keys(state.selectedGroups);
    }

    function syncHiddenFields() {
      if (recipientsJson) recipientsJson.value = JSON.stringify(selectedRecipientIds());
      if (audienceJson) audienceJson.value = JSON.stringify(selectedGroupValues());
    }

    function renderChips() {
      if (!chipsBox) return;
      var html = '';
      selectedGroupValues().forEach(function (value) {
        var label = state.selectedGroups[value] || value;
        html += '<span class="compose-chip is-group" data-chip-group="' + escapeHtml(value) + '">' +
          '<i class="fa-solid fa-users" aria-hidden="true"></i> ' + escapeHtml(label) +
          '<button type="button" data-remove-group="' + escapeHtml(value) + '" aria-label="Remove group">&times;</button></span>';
      });
      selectedRecipientIds().forEach(function (id) {
        var person = state.selectedPeople[id];
        if (!person) return;
        html += '<span class="compose-chip" data-chip-user="' + id + '">' +
          escapeHtml(person.name) +
          '<button type="button" data-remove-user="' + id + '" aria-label="Remove recipient">&times;</button></span>';
      });
      chipsBox.innerHTML = html;
      syncHiddenFields();
    }

    function addPerson(person) {
      if (!person || !person.id) return;
      var id = Number(person.id);
      if (!id || state.selectedPeople[id]) return;
      state.selectedPeople[id] = {
        id: id,
        name: person.name || 'Staff',
        roleLabel: person.roleLabel || person.role || ''
      };
      renderChips();
    }

    function removePerson(id) {
      delete state.selectedPeople[Number(id)];
      renderChips();
    }

    function addGroup(value, label) {
      if (!value) return;
      state.selectedGroups[value] = label || value;
      // Project channel type when using project team shortcut with project selected
      if (value === 'project:selected' && typeSelect && typeSelect.value === 'direct') {
        typeSelect.value = 'group';
      }
      renderChips();
    }

    function removeGroup(value) {
      delete state.selectedGroups[value];
      renderChips();
    }

    function clearComposeRecipients() {
      state.selectedPeople = {};
      state.selectedGroups = {};
      renderChips();
      if (searchInput) searchInput.value = '';
      hideResults();
    }

    function filterDirectory(query) {
      var q = String(query || '').trim().toLowerCase();
      var selected = state.selectedPeople;
      var list = (state.directory || []).filter(function (row) {
        return !selected[Number(row.id)];
      });
      if (!q) return list.slice(0, 8);
      return list.filter(function (row) {
        var hay = [
          row.name || '',
          row.email || '',
          row.role || '',
          row.roleLabel || ''
        ].join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
      }).slice(0, 12);
    }

    function hideResults() {
      if (!resultsBox || !searchInput) return;
      resultsBox.hidden = true;
      resultsBox.innerHTML = '';
      searchInput.setAttribute('aria-expanded', 'false');
      state.resultIndex = -1;
    }

    function positionResults() {
      if (!resultsBox || !searchInput) return;
      var field = qs('[data-compose-to-field]', app);
      var box = qs('.compose-to-box', app);
      if (!field || !box) return;
      // Anchor dropdown under the To box (not under group row).
      var fieldRect = field.getBoundingClientRect();
      var boxRect = box.getBoundingClientRect();
      resultsBox.style.top = (boxRect.bottom - fieldRect.top + 4) + 'px';
    }

    function showResults(items) {
      if (!resultsBox || !searchInput) return;
      if (!items.length) {
        resultsBox.innerHTML = '<div class="compose-result__empty">No matching contacts. Try another name or link a project.</div>';
        resultsBox.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');
        state.resultIndex = -1;
        positionResults();
        return;
      }
      resultsBox.innerHTML = items.map(function (row, index) {
        return '<button class="compose-result" type="button" role="option" data-result-id="' + row.id + '" data-result-index="' + index + '">' +
          '<span><span class="compose-result__name">' + escapeHtml(row.name) + '</span><br><span class="compose-result__meta">' + escapeHtml(row.roleLabel || row.role || '') + (row.email ? ' · ' + escapeHtml(row.email) : '') + '</span></span>' +
          '<i class="fa-solid fa-plus" aria-hidden="true"></i></button>';
      }).join('');
      resultsBox.hidden = false;
      searchInput.setAttribute('aria-expanded', 'true');
      state.resultIndex = -1;
      positionResults();
    }

    function highlightResult(index) {
      var buttons = qsa('[data-result-id]', resultsBox);
      if (!buttons.length) return;
      state.resultIndex = Math.max(0, Math.min(index, buttons.length - 1));
      buttons.forEach(function (btn, i) {
        btn.classList.toggle('is-active', i === state.resultIndex);
      });
      buttons[state.resultIndex].scrollIntoView({ block: 'nearest' });
    }

    function pickResultByIndex(index) {
      var buttons = qsa('[data-result-id]', resultsBox);
      if (!buttons[index]) return;
      var id = Number(buttons[index].getAttribute('data-result-id'));
      var person = (state.directory || []).find(function (row) { return Number(row.id) === id; });
      if (person) {
        addPerson(person);
        if (searchInput) searchInput.value = '';
        hideResults();
        if (searchInput) searchInput.focus();
      }
    }

    function refreshDirectory(projectId) {
      var params = new URLSearchParams();
      if (projectId) params.set('project_id', String(projectId));
      var url = 'api/messages/recipients.php' + (params.toString() ? '?' + params.toString() : '');
      return window.AHPTC.request(url).then(function (data) {
        state.directory = data.recipients || [];
        state.audience = data.audience || [];
        // Drop selected people no longer allowed in this project context
        Object.keys(state.selectedPeople).forEach(function (id) {
          var exists = state.directory.some(function (row) { return Number(row.id) === Number(id); });
          if (!exists) delete state.selectedPeople[id];
        });
        if (groupSelect) {
          var html = '<option value="">+ Add group…</option>';
          (state.audience || []).forEach(function (opt) {
            html += '<option value="' + escapeHtml(opt.value) + '">' + escapeHtml(opt.label) + ' (' + (opt.count || 0) + ')</option>';
          });
          html += '<option value="project:selected">Selected project team</option>';
          groupSelect.innerHTML = html;
        }
        if (toMeta) {
          var count = Number((data.policy || {}).recipientCount || state.directory.length || 0);
          toMeta.textContent = projectId
            ? (count + ' contacts on selected project')
            : (count + ' searchable contacts');
        }
        renderChips();
        if (searchInput && searchInput.value.trim()) {
          showResults(filterDirectory(searchInput.value));
        }
        return data;
      }).catch(function () {
        return null;
      });
    }

    function updateProjectChannelHint() {
      var isChannel = typeSelect && typeSelect.value === 'project-channel';
      if (projectRequiredHint) projectRequiredHint.hidden = !isChannel;
      if (projectSelect) projectSelect.required = !!isChannel;
    }

    function sendReply(form) {
      var body = qs('textarea[name="body"]', form).value.trim();
      if (!body || !state.threadId || state.pendingUploads > 0) return;
      setSendDisabled(true);
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
      }).finally(function () {
        setSendDisabled(false);
      });
    }

    function sendCompose(form) {
      if (state.pendingUploads > 0) return;
      var type = (typeSelect && typeSelect.value) || 'direct';
      var projectId = (projectSelect && projectSelect.value) || '';
      var recipients = selectedRecipientIds();
      var audienceTargets = selectedGroupValues();

      if (type === 'project-channel' && !projectId) {
        window.alert('Choose a project for a project-channel conversation.');
        return;
      }
      if (!recipients.length && !audienceTargets.length) {
        window.alert('Add at least one recipient (search and select) or an allowed group.');
        if (searchInput) searchInput.focus();
        return;
      }
      // Auto-upgrade type when multiple recipients
      if (type === 'direct' && (recipients.length + audienceTargets.length) > 1) {
        type = 'group';
      }

      setSendDisabled(true);
      window.AHPTC.request('api/messages/send.php', {
        method: 'POST',
        body: {
          subject: qs('[name="subject"]', form).value,
          type: type,
          priority: qs('[name="priority"]', form).value,
          project_id: projectId,
          recipients: recipients,
          audience_targets: audienceTargets,
          body: qs('[name="body"]', form).value,
          attachment_tokens: state.composeTokens
        }
      }).then(function (data) {
        form.reset();
        state.composeTokens = [];
        qs('[data-compose-attachments]', app).innerHTML = '';
        clearComposeRecipients();
        if (projectRequiredHint) projectRequiredHint.hidden = true;
        modal.hidden = true;
        refreshDirectory('');
        loadInbox();
        if (data.threadId) loadThread(data.threadId);
      }).catch(function (error) {
        window.alert(error.message || 'Message could not be sent.');
      }).finally(function () {
        setSendDisabled(false);
      });
    }

    function openCompose() {
      modal.hidden = false;
      updateProjectChannelHint();
      refreshDirectory((projectSelect && projectSelect.value) || '');
      window.setTimeout(function () {
        if (searchInput) searchInput.focus();
      }, 40);
    }

    function closeCompose() {
      modal.hidden = true;
      hideResults();
    }

    /* ---------- Events ---------- */

    list.addEventListener('click', function (event) {
      var item = event.target.closest('[data-thread-id]');
      var more = event.target.closest('[data-thread-more]');
      if (more) {
        state.page += 1;
        loadInbox(true);
        return;
      }
      if (item) loadThread(item.getAttribute('data-thread-id'));
    });

    qsa('[data-box]', app).forEach(function (button) {
      button.addEventListener('click', function () {
        qsa('[data-box]', app).forEach(function (tab) { tab.classList.remove('is-active'); });
        button.classList.add('is-active');
        state.box = button.getAttribute('data-box') || 'inbox';
        state.page = 1;
        state.threadId = 0;
        showEmpty('No conversation selected', 'Choose a thread from this folder, or compose a new message.');
        loadInbox();
      });
    });

    qs('[data-type-filter]', app).addEventListener('change', function (event) {
      state.type = event.target.value;
      state.page = 1;
      state.threadId = 0;
      showEmpty('No conversation selected', 'Choose a thread from the filtered list, or compose a new message.');
      loadInbox();
    });

    qs('[data-message-search]', app).addEventListener('submit', function (event) {
      event.preventDefault();
      state.q = qs('[name="q"]', event.currentTarget).value.trim();
      state.page = 1;
      loadInbox();
    });

    var searchTimer = null;
    qs('[name="q"]', app).addEventListener('input', function (event) {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(function () {
        state.q = event.target.value.trim();
        state.page = 1;
        loadInbox();
      }, 350);
    });

    if (replyForm) {
      replyForm.addEventListener('submit', function (event) {
        event.preventDefault();
        sendReply(event.currentTarget);
      });
    }

    if (composeForm) {
      composeForm.addEventListener('submit', function (event) {
        event.preventDefault();
        sendCompose(event.currentTarget);
      });
    }

    qsa('[data-compose-open]', app).forEach(function (button) {
      button.addEventListener('click', openCompose);
    });
    qs('[data-compose-close]', app).addEventListener('click', closeCompose);
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeCompose();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) closeCompose();
    });

    qs('[data-thread-back]', app).addEventListener('click', function () {
      state.threadId = 0;
      showEmpty('No conversation selected', 'Choose a thread from the inbox, or compose a new message.');
      qsa('.thread-item.is-active', list).forEach(function (item) { item.classList.remove('is-active'); });
    });

    qs('[data-compose-attachment]', app).addEventListener('change', function (event) {
      uploadFiles(event.target.files, 'compose');
      event.target.value = '';
    });
    qs('[data-reply-attachment]', app).addEventListener('change', function (event) {
      uploadFiles(event.target.files, 'reply');
      event.target.value = '';
    });

    if (projectSelect) {
      projectSelect.addEventListener('change', function () {
        refreshDirectory(projectSelect.value || '');
      });
    }
    if (typeSelect) {
      typeSelect.addEventListener('change', updateProjectChannelHint);
    }

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        showResults(filterDirectory(searchInput.value));
      });
      searchInput.addEventListener('focus', function () {
        showResults(filterDirectory(searchInput.value));
      });
      searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown') {
          event.preventDefault();
          highlightResult(state.resultIndex < 0 ? 0 : state.resultIndex + 1);
        } else if (event.key === 'ArrowUp') {
          event.preventDefault();
          highlightResult(state.resultIndex <= 0 ? 0 : state.resultIndex - 1);
        } else if (event.key === 'Enter') {
          if (state.resultIndex >= 0) {
            event.preventDefault();
            pickResultByIndex(state.resultIndex);
          }
        } else if (event.key === 'Backspace' && !searchInput.value) {
          var ids = selectedRecipientIds();
          if (ids.length) removePerson(ids[ids.length - 1]);
        } else if (event.key === 'Escape') {
          hideResults();
        }
      });
    }

    if (resultsBox) {
      resultsBox.addEventListener('mousedown', function (event) {
        // Prevent input blur before click
        event.preventDefault();
      });
      resultsBox.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-result-id]');
        if (!btn) return;
        pickResultByIndex(Number(btn.getAttribute('data-result-index') || 0));
      });
    }

    if (chipsBox) {
      chipsBox.addEventListener('click', function (event) {
        var removeUser = event.target.closest('[data-remove-user]');
        var removeGroupBtn = event.target.closest('[data-remove-group]');
        if (removeUser) {
          removePerson(removeUser.getAttribute('data-remove-user'));
        }
        if (removeGroupBtn) {
          removeGroup(removeGroupBtn.getAttribute('data-remove-group'));
        }
      });
    }

    if (groupSelect) {
      groupSelect.addEventListener('change', function () {
        var value = groupSelect.value;
        if (!value) return;
        if (value === 'project:selected' && projectSelect && !projectSelect.value) {
          window.alert('Select a project first, then add the project team.');
          groupSelect.value = '';
          return;
        }
        var label = groupSelect.options[groupSelect.selectedIndex]
          ? groupSelect.options[groupSelect.selectedIndex].text
          : value;
        addGroup(value, label);
        groupSelect.value = '';
      });
    }

    if (policyToggle && policyPanel) {
      policyToggle.addEventListener('click', function () {
        var open = policyPanel.hidden;
        policyPanel.hidden = !open;
        policyToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    document.addEventListener('click', function (event) {
      if (!modal.hidden && !event.target.closest('[data-compose-to-field]')) {
        hideResults();
      }
    });

    qs('[data-archive-thread]', app).addEventListener('click', function () {
      if (!state.threadId) return;
      if (!window.confirm('Archive this conversation?')) return;
      window.AHPTC.request('api/messages/delete.php', { method: 'POST', body: { action: 'archive-thread', thread_id: state.threadId } }).then(function () {
        state.threadId = 0;
        showEmpty('Conversation archived', 'Choose another thread or compose a new message.');
        loadInbox();
      }).catch(function (error) {
        window.alert(error.message || 'Conversation could not be archived.');
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

    showEmpty('No conversation selected', 'Choose a thread from the inbox, or compose a new message.');
    renderChips();
    loadInbox();
    if (state.threadId) loadThread(state.threadId, { markRead: true });
    window.setInterval(function () {
      loadInbox();
      if (state.threadId) loadThread(state.threadId, { markRead: false, silent: true });
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
