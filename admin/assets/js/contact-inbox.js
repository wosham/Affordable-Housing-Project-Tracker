(function () {
  "use strict";

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call(
      (root || document).querySelectorAll(selector),
    );
  }

  function esc(value) {
    var span = document.createElement("span");
    span.textContent = value == null ? "" : String(value);
    return span.innerHTML;
  }

  function parseTemplates() {
    if (!modal) return [];
    try {
      var raw = modal.getAttribute("data-templates") || "[]";
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  var modal = qs("[data-contact-modal]");
  var detail = qs("[data-contact-detail]");
  var assignSelect = qs("[data-contact-assign]");
  var statusSelect = qs("[data-contact-status]");
  var responseNote = qs("[data-contact-response-note]");
  var replySubject = qs("[data-contact-reply-subject]");
  var replyRecipient = qs("[data-contact-reply-recipient]");
  var sendReplyButton = qs("[data-contact-send-reply]");
  var startThreadButton = qs("[data-contact-start-thread]");
  var prioritySelect = qs("[data-contact-priority]");
  var followUpInput = qs("[data-contact-follow-up]");
  var internalNoteInput = qs("[data-contact-internal-note]");
  var templateSelect = qs("[data-contact-template]");
  var saveCaseButton = qs("[data-contact-save-case]");
  var templates = parseTemplates();
  var activeId = 0;
  var activeMessage = null;

  function apiUrl(key, fallback) {
    if (!modal) return fallback;
    return modal.getAttribute("data-" + key + "-url") || fallback;
  }

  function setError(message) {
    var node = qs("[data-contact-error]", modal);
    if (!node) return;
    node.textContent = message || "";
    node.hidden = !message;
  }

  function setSuccess(message) {
    var node = qs("[data-contact-success]", modal);
    if (!node) return;
    node.textContent = message || "";
    node.hidden = !message;
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove("is-open");
    modal.setAttribute("hidden", "");
    document.body.classList.remove("modal-open");
    activeId = 0;
    activeMessage = null;
    setError("");
    setSuccess("");
  }

  function renderReplies(replies) {
    if (!replies || !replies.length) {
      return '<div class="contact-replies__empty">No email replies have been sent yet.</div>';
    }

    return replies
      .map(function (reply) {
        var statusClass =
          reply.deliveryStatus === "sent"
            ? "is-sent"
            : reply.deliveryStatus === "failed"
              ? "is-failed"
              : "is-pending";
        return (
          "" +
          '<article class="contact-reply-item ' +
          statusClass +
          '">' +
          '<div class="contact-reply-item__meta">' +
          "<strong>" +
          esc(reply.senderName || "AHP Tracker") +
          "</strong>" +
          "<span>" +
          esc(reply.createdAtFormatted || "") +
          "</span>" +
          '<span class="badge badge--neutral">' +
          esc(reply.deliveryStatus || "pending") +
          "</span>" +
          "</div>" +
          "<h4>" +
          esc(reply.subject || "Reply") +
          "</h4>" +
          "<p>" +
          esc(reply.body || "") +
          "</p>" +
          (reply.errorMessage
            ? '<small class="contact-reply-item__error">' +
              esc(reply.errorMessage) +
              "</small>"
            : "") +
          "</article>"
        );
      })
      .join("");
  }

  function renderMessage(message) {
    activeMessage = message;
    var flags = [];
    if (message.status)
      flags.push(
        '<span class="badge ' +
          (message.status === "archived"
            ? "badge--neutral"
            : message.status === "replied"
              ? "badge--success"
              : message.status === "in_progress"
                ? "badge--info"
                : "badge--neutral") +
          '">' +
          esc(message.status.replace(/_/g, " ")) +
          "</span>",
      );
    if (message.isOverdue) flags.push('<span class="badge badge--danger">Overdue</span>');
    if (message.isDueToday) flags.push('<span class="badge badge--warning">Due today</span>');
    if (message.priority === "urgent") flags.push('<span class="badge badge--danger">Urgent</span>');
    if (!message.isRead) flags.push('<span class="badge badge--warning">Unread</span>');

    var headerFlags = qs("[data-contact-header-flags]", modal);
    var headerSub = qs("[data-contact-header-sub]", modal);
    if (headerFlags) headerFlags.innerHTML = flags.join(" ");
    if (headerSub) {
      headerSub.textContent = [
        message.name || "",
        message.email || "",
        message.createdAtFormatted || "",
      ]
        .filter(Boolean)
        .join(" · ");
    }

    var titleEl = qs("#contactModalTitle", modal);
    if (titleEl) titleEl.textContent = message.subject || "Enquiry case";

    var attachment = message.attachmentUrl
      ? '<a class="contact-attachment-chip" href="' +
        esc(message.attachmentUrl) +
        '" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-paperclip" aria-hidden="true"></i> Open attachment</a>'
      : "";

    detail.innerHTML =
      '<article class="contact-detail-card">' +
      '<div class="contact-detail-meta">' +
      '<span><i class="fa-solid fa-user" aria-hidden="true"></i> ' +
      esc(message.name) +
      "</span>" +
      '<span><i class="fa-solid fa-envelope" aria-hidden="true"></i> ' +
      esc(message.email) +
      "</span>" +
      (message.phone
        ? '<span><i class="fa-solid fa-phone" aria-hidden="true"></i> ' +
          esc(message.phone) +
          "</span>"
        : "") +
      '<span><i class="fa-solid fa-clock" aria-hidden="true"></i> ' +
      esc(message.createdAtFormatted) +
      "</span>" +
      "</div>" +
      '<div class="contact-detail-message">' +
      esc(message.body) +
      "</div>" +
      (attachment ? "<div>" + attachment + "</div>" : "") +
      '<dl class="contact-detail-facts">' +
      "<div><dt>Status</dt><dd>" +
      esc(message.status || "—") +
      "</dd></div>" +
      "<div><dt>Assigned</dt><dd>" +
      esc(message.assigneeName || "Unassigned") +
      "</dd></div>" +
      (message.assignedAtFormatted
        ? "<div><dt>Assigned at</dt><dd>" + esc(message.assignedAtFormatted) + "</dd></div>"
        : "") +
      (message.followUpAt
        ? "<div><dt>Follow-up</dt><dd>" + esc(message.followUpAt) + "</dd></div>"
        : "") +
      "</dl>" +
      (message.internalNote
        ? '<div class="contact-internal-note-preview"><strong>Saved internal note</strong><p>' +
          esc(message.internalNote) +
          "</p></div>"
        : "") +
      (message.internalThreadUrl
        ? '<div class="contact-internal-thread"><a class="btn btn--outline btn--sm" href="' +
          esc(message.internalThreadUrl) +
          '"><i class="fa-solid fa-comments" aria-hidden="true"></i> Open internal discussion</a></div>'
        : "") +
      "</article>" +
      '<section class="contact-replies">' +
      '<div class="contact-replies__head">' +
      "<h3>Reply history</h3>" +
      "<span>" +
      esc((message.replies || []).length) +
      " sent</span>" +
      "</div>" +
      renderReplies(message.replies || []) +
      "</section>";

    if (assignSelect) assignSelect.value = message.assignedTo || "";
    if (statusSelect) {
      if (message.status === "archived") statusSelect.value = "restore";
      else if (message.status === "in_progress") statusSelect.value = "in_progress";
      else statusSelect.value = "read";
    }
    if (prioritySelect) prioritySelect.value = message.priority === "urgent" ? "urgent" : "normal";
    if (followUpInput) followUpInput.value = message.followUpAt || "";
    if (internalNoteInput) internalNoteInput.value = message.internalNote || "";
    if (responseNote) responseNote.value = "";
    if (templateSelect) templateSelect.value = "";
    if (replySubject)
      replySubject.value =
        message.subject && /^re:/i.test(message.subject)
          ? message.subject
          : "Re: " + (message.subject || "Your enquiry");
    if (replyRecipient)
      replyRecipient.textContent = message.email || "Recipient";
    if (startThreadButton) {
      startThreadButton.innerHTML = message.internalThreadUrl
        ? '<i class="fa-solid fa-comments" aria-hidden="true"></i> Open chat'
        : '<i class="fa-solid fa-comments" aria-hidden="true"></i> Internal chat';
    }
  }

  function openMessage(id, focusReply) {
    activeId = parseInt(id, 10) || 0;
    if (!activeId || !modal) return;

    detail.innerHTML =
      '<div class="contact-detail__loading">Loading case...</div>';
    setError("");
    setSuccess("");
    modal.removeAttribute("hidden");
    modal.classList.add("is-open");
    document.body.classList.add("modal-open");

    window.AHPTC.request(
      apiUrl("get", "api/contact/get-message.php") +
        "?id=" +
        encodeURIComponent(activeId),
      {
        method: "GET",
      },
    )
      .then(function (data) {
        renderMessage(data.message || {});
        if (focusReply && responseNote) {
          responseNote.focus();
        }
      })
      .catch(function (error) {
        setError(
          error && error.message
            ? error.message
            : "Message could not be loaded.",
        );
      });
  }

  function postStatus(action, note) {
    if (!activeId) return Promise.reject(new Error("No active message."));
    return window.AHPTC.request(
      apiUrl("status", "api/contact/update-status.php"),
      {
        method: "POST",
        body: {
          id: activeId,
          action: action,
          response_note: note || "",
        },
      },
    );
  }

  function saveStatus() {
    var action = statusSelect ? statusSelect.value : "read";
    var note = responseNote ? responseNote.value : "";
    var saveStatusButton = qs("[data-contact-save-status]");
    setError("");

    if (action === "archive" && !window.confirm("Archive this contact message?")) {
      return;
    }

    if (saveStatusButton) saveStatusButton.disabled = true;
    postStatus(action, note)
      .then(function () {
        window.location.reload();
      })
      .catch(function (error) {
        setError(
          error && error.message ? error.message : "Status could not be saved.",
        );
      })
      .finally(function () {
        if (saveStatusButton) saveStatusButton.disabled = false;
      });
  }

  function saveCase() {
    if (!activeId) return;
    setError("");
    setSuccess("");
    if (saveCaseButton) saveCaseButton.disabled = true;
    window.AHPTC.request(apiUrl("case", "api/contact/save-case.php"), {
      method: "POST",
      body: {
        id: activeId,
        internal_note: internalNoteInput ? internalNoteInput.value : "",
        follow_up_at: followUpInput ? followUpInput.value : "",
        priority: prioritySelect ? prioritySelect.value : "normal",
      },
    })
      .then(function (data) {
        setSuccess(data.message || "Case saved.");
        return window.AHPTC.request(
          apiUrl("get", "api/contact/get-message.php") +
            "?id=" +
            encodeURIComponent(activeId),
          { method: "GET" },
        );
      })
      .then(function (data) {
        renderMessage(data.message || {});
      })
      .catch(function (error) {
        setError(
          error && error.message ? error.message : "Case could not be saved.",
        );
      })
      .finally(function () {
        if (saveCaseButton) saveCaseButton.disabled = false;
      });
  }

  function applyTemplate() {
    if (!templateSelect || !activeMessage) return;
    var id = templateSelect.value;
    if (!id) return;
    var tpl = templates.find(function (row) {
      return row.id === id;
    });
    if (!tpl) return;
    var name = activeMessage.name || "Applicant";
    var subject = activeMessage.subject || "your enquiry";
    var fill = function (text) {
      return String(text || "")
        .replace(/\{name\}/g, name)
        .replace(/\{subject\}/g, subject);
    };
    if (replySubject) replySubject.value = fill(tpl.subject);
    if (responseNote) responseNote.value = fill(tpl.body);
  }

  function sendReply() {
    if (!activeId || !activeMessage) return;
    var subject = replySubject ? replySubject.value : "";
    var body = responseNote ? responseNote.value : "";
    setError("");
    setSuccess("");

    if (!body || !String(body).trim()) {
      setError("Write a reply message before sending.");
      return;
    }

    if (sendReplyButton) sendReplyButton.disabled = true;
    window.AHPTC.request(apiUrl("reply", "api/contact/send-reply.php"), {
      method: "POST",
      body: {
        id: activeId,
        subject: subject,
        body: body,
      },
    })
      .then(function (data) {
        setSuccess(data.message || "Reply sent.");
        return window.AHPTC.request(
          apiUrl("get", "api/contact/get-message.php") +
            "?id=" +
            encodeURIComponent(activeId),
          {
            method: "GET",
          },
        );
      })
      .then(function (data) {
        renderMessage(data.message || {});
      })
      .catch(function (error) {
        setError(
          error && error.message ? error.message : "Reply could not be sent.",
        );
      })
      .finally(function () {
        if (sendReplyButton) sendReplyButton.disabled = false;
      });
  }

  function saveAssignment() {
    if (!activeId || !assignSelect) return;
    var saveAssignButton = qs("[data-contact-save-assign]");
    setError("");

    if (saveAssignButton) saveAssignButton.disabled = true;
    window.AHPTC.request(apiUrl("assign", "api/contact/assign.php"), {
      method: "POST",
      body: {
        id: activeId,
        assigned_to: assignSelect ? assignSelect.value : "",
      },
    })
      .then(function () {
        window.location.reload();
      })
      .catch(function (error) {
        setError(
          error && error.message
            ? error.message
            : "Assignment could not be saved.",
        );
      })
      .finally(function () {
        if (saveAssignButton) saveAssignButton.disabled = false;
      });
  }

  function startInternalThread() {
    if (!activeId) return;
    setError("");
    setSuccess("");

    if (activeMessage && activeMessage.internalThreadUrl) {
      window.location.href = activeMessage.internalThreadUrl;
      return;
    }

    if (startThreadButton) startThreadButton.disabled = true;
    window.AHPTC.request(apiUrl("thread", "api/contact/start-thread.php"), {
      method: "POST",
      body: { id: activeId },
    })
      .then(function (data) {
        if (data.threadUrl) {
          window.location.href = data.threadUrl;
          return;
        }
        setSuccess(data.message || "Internal discussion created.");
      })
      .catch(function (error) {
        setError(
          error && error.message
            ? error.message
            : "Internal discussion could not be opened.",
        );
      })
      .finally(function () {
        if (startThreadButton) startThreadButton.disabled = false;
      });
  }

  function quickAction(button) {
    activeId = parseInt(button.getAttribute("data-id") || "0", 10);
    var action = button.getAttribute("data-contact-action") || "read";
    if (!activeId) return;
    if (action === "archive" && !window.confirm("Archive this contact message?")) {
      return;
    }

    button.disabled = true;
    postStatus(action, "")
      .then(function () {
        window.location.reload();
      })
      .catch(function () {
        button.disabled = false;
      });
  }

  function init() {
    if (!modal) return;

    document.addEventListener("click", function (event) {
      var button = event.target.closest("[data-contact-open]");
      if (button) {
        event.preventDefault();
        openMessage(
          button.getAttribute("data-id"),
          button.getAttribute("data-contact-focus-reply") === "1",
        );
        return;
      }

      button = event.target.closest("[data-contact-action]");
      if (button) {
        event.preventDefault();
        quickAction(button);
        return;
      }

      button = event.target.closest("[data-contact-close]");
      if (button) {
        event.preventDefault();
        closeModal();
      }
    });

    var saveStatusButton = qs("[data-contact-save-status]");
    if (saveStatusButton)
      saveStatusButton.addEventListener("click", saveStatus);

    var saveAssignButton = qs("[data-contact-save-assign]");
    if (saveAssignButton)
      saveAssignButton.addEventListener("click", saveAssignment);

    if (saveCaseButton) saveCaseButton.addEventListener("click", saveCase);
    if (sendReplyButton) sendReplyButton.addEventListener("click", sendReply);
    if (startThreadButton)
      startThreadButton.addEventListener("click", startInternalThread);
    if (templateSelect)
      templateSelect.addEventListener("change", applyTemplate);

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") closeModal();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
