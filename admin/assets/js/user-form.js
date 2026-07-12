(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function initials(first, last, email) {
    var source = ((first || '').trim() + ' ' + (last || '').trim()).trim() || (email || '').trim() || 'AH';
    var parts = source.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }

  function setText(selector, value) {
    var node = qs(selector);
    if (node) node.textContent = value;
  }

  function initPasswordToggles() {
    qsa('[data-password-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        var field = button.parentElement ? qs('[data-password-input]', button.parentElement) : null;
        var icon = qs('i', button);
        if (!field) return;
        var visible = field.type === 'text';
        field.type = visible ? 'password' : 'text';
        button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
        if (icon) {
          icon.classList.toggle('fa-eye', visible);
          icon.classList.toggle('fa-eye-slash', !visible);
        }
      });
    });
  }

  function passwordScore(value) {
    var score = 0;
    if (value.length >= 8) score++;
    if (value.length >= 12) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return Math.min(score, 5);
  }

  function initPasswordTools() {
    var password = qs('input[name="password"]');
    var confirm = qs('input[name="password_confirm"]');
    if (!password) return;

    var meter = document.createElement('span');
    meter.className = 'sa-password-strength';
    meter.setAttribute('data-password-strength', '1');
    password.closest('.form-field').appendChild(meter);

    var generate = document.createElement('button');
    generate.className = 'btn btn--outline btn--sm sa-password-generate';
    generate.type = 'button';
    generate.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Generate password';
    password.closest('.form-field').appendChild(generate);

    function update() {
      var value = password.value || '';
      if (!value) {
        meter.textContent = 'Password strength will appear here.';
        meter.className = 'sa-password-strength';
        return;
      }
      var score = passwordScore(value);
      var label = score >= 5 ? 'Strong' : (score >= 3 ? 'Good' : 'Weak');
      meter.textContent = label + ' password';
      meter.className = 'sa-password-strength is-score-' + score;
    }

    generate.addEventListener('click', function () {
      var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
      var symbols = '!@#$%&*?';
      var value = '';
      for (var i = 0; i < 12; i++) value += chars.charAt(Math.floor(Math.random() * chars.length));
      value += symbols.charAt(Math.floor(Math.random() * symbols.length)) + '7';
      password.value = value;
      if (confirm) confirm.value = value;
      password.type = 'text';
      if (confirm) confirm.type = 'text';
      update();
    });

    password.addEventListener('input', update);
    update();
  }

  function initAvatarPreview() {
    var input = qs('[data-avatar-input]');
    var preview = qs('[data-avatar-preview]');
    var profileAvatar = qs('[data-profile-avatar]');
    var path = qs('[data-avatar-path]');
    if (!input || !preview) return;

    input.addEventListener('change', function () {
      var file = input.files && input.files[0] ? input.files[0] : null;
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (event) {
        var image = '<img src="' + event.target.result + '" alt="">';
        preview.innerHTML = image;
        if (profileAvatar) profileAvatar.innerHTML = image;
        if (path) path.value = 'Pending upload: ' + file.name;
      };
      reader.readAsDataURL(file);
    });
  }

  function initLiveProfile() {
    var first = qs('[name="first_name"]');
    var last = qs('[name="last_name"]');
    var email = qs('[name="email"]');
    var title = qs('[name="job_title"]');
    var department = qs('[name="department"]');
    var status = qs('[name="status"]');
    var avatar = qs('[data-profile-avatar]');
    var phone = qs('[name="phone"]');

    function update() {
      var fullName = (((first && first.value) || '').trim() + ' ' + ((last && last.value) || '').trim()).trim();
      var emailValue = (email && email.value.trim()) || '';
      var phoneValue = (phone && phone.value.trim()) || '';
      setText('[data-profile-name]', fullName || 'New User');
      setText('[data-profile-title]', (title && title.value.trim()) || 'Role title will appear here');
      setText('[data-profile-email]', emailValue || 'email@example.com');
      setText('[data-profile-phone]', phoneValue || 'Not set');
      setText('[data-profile-department]', (department && department.value.trim()) || 'Not set');

      if (status) {
        var label = status.options[status.selectedIndex] ? status.options[status.selectedIndex].text : 'Unknown';
        setText('[data-profile-status]', label);
      }

      if (avatar && avatar.querySelector('img') === null) {
        avatar.textContent = initials(first && first.value, last && last.value, email && email.value);
      }
    }

    [first, last, email, phone, title, department, status].forEach(function (field) {
      if (field) field.addEventListener('input', update);
      if (field) field.addEventListener('change', update);
    });

    update();
  }

  function initAssignmentPicker() {
    var roleSelect = qs('[data-role-select]');
    var allAccessRoles = ['superadmin', 'county_director'];
    var assignableRoles = ['manager', 'consultant', 'contractor', 'clerk', 'intern'];
    var singleSiteRoles = ['clerk', 'intern'];
    var financeRoles = ['finance'];

    function currentRole(picker) {
      if (picker && picker.dataset.staticRole) return picker.dataset.staticRole;
      if (!roleSelect) return '';
      var option = roleSelect.options[roleSelect.selectedIndex];
      return option ? (option.getAttribute('data-role-slug') || '').toLowerCase() : '';
    }

    function projectInputs(picker) {
      return qsa('input[name="project_ids[]"]', picker);
    }

    function enforceSingleSite(picker) {
      var role = currentRole(picker);
      if (singleSiteRoles.indexOf(role) === -1) return;
      var boxes = projectInputs(picker);
      var kept = false;
      boxes.forEach(function (box) {
        if (!box.checked) return;
        if (!kept) {
          kept = true;
          return;
        }
        box.checked = false;
      });
    }

    function updateSummary(picker) {
      var summary = qs('[data-selected-project-summary]');
      if (!summary) return;
      var list = qs('[data-selected-project-list]', summary);
      var count = qs('[data-selected-project-count]', summary);
      if (!list || !count) return;

      var role = currentRole(picker);
      var boxes = projectInputs(picker);
      var checked = boxes.filter(function (box) { return box.checked; });
      var emptyText = summary.getAttribute('data-empty-text') || 'No project sites selected yet.';

      list.innerHTML = '';

      if (allAccessRoles.indexOf(role) !== -1) {
        count.textContent = String(boxes.length);
        var allPill = document.createElement('span');
        allPill.className = 'sa-selected-project-pill is-all-access';
        allPill.textContent = 'All project sites';
        list.appendChild(allPill);
        return;
      }

      if (financeRoles.indexOf(role) !== -1) {
        count.textContent = String(boxes.length);
        var financePill = document.createElement('span');
        financePill.className = 'sa-selected-project-pill is-all-access';
        financePill.textContent = 'Finance portfolio visibility';
        list.appendChild(financePill);
        return;
      }

      count.textContent = String(checked.length);
      if (checked.length === 0) {
        var empty = document.createElement('span');
        empty.className = 'sa-selected-projects__empty';
        empty.textContent = emptyText;
        list.appendChild(empty);
        return;
      }

      checked.forEach(function (box) {
        var pill = document.createElement('span');
        pill.className = 'sa-selected-project-pill';
        pill.dataset.projectId = box.value;
        pill.textContent = box.getAttribute('data-project-name') || (box.closest('.sa-assignment-option') && box.closest('.sa-assignment-option').querySelector('strong') ? box.closest('.sa-assignment-option').querySelector('strong').textContent : 'Project site');
        list.appendChild(pill);
      });
    }

    function applyRoleMode(picker) {
      var role = currentRole(picker);
      var allAccess = allAccessRoles.indexOf(role) !== -1;
      var financeAccess = financeRoles.indexOf(role) !== -1;
      var isAssignable = assignableRoles.indexOf(role) !== -1;
      var singleSite = singleSiteRoles.indexOf(role) !== -1;
      var hasRole = role !== '';
      var boxes = projectInputs(picker);
      var emptyPanel = qs('[data-assignment-empty-state]', picker);
      var allPanel = qs('[data-assignment-all-access]', picker);
      var financePanel = qs('[data-assignment-finance-access]', picker);
      var tools = qs('.sa-assignment-picker__tools', picker);
      var groups = qs('.sa-assignment-groups', picker);
      var selectAll = qs('[data-assignment-select-all]', picker);

      if (emptyPanel) emptyPanel.hidden = hasRole;
      if (allPanel) allPanel.hidden = !allAccess;
      if (financePanel) financePanel.hidden = !financeAccess;
      if (tools) tools.hidden = !isAssignable;
      if (selectAll) selectAll.hidden = singleSite;
      if (groups) {
        groups.hidden = !isAssignable;
        groups.classList.toggle('is-readonly', !isAssignable);
        groups.dataset.singleSite = singleSite ? '1' : '0';
      }

      boxes.forEach(function (box) {
        box.disabled = !isAssignable;
        if (allAccess || financeAccess) box.checked = true;
        if (!hasRole) box.checked = false;
      });

      if (singleSite) enforceSingleSite(picker);
      updateSummary(picker);
    }

    qsa('.sa-assignment-picker[data-assignment-picker]').forEach(function (picker) {
      var boxes = projectInputs(picker);
      var selectAll = qs('[data-assignment-select-all]', picker);
      var clear = qs('[data-assignment-clear]', picker);
      var search = qs('[data-assignment-search]', picker);
      var selectedOnly = qs('[data-assignment-show-selected]', picker);

      function applyFilter() {
        var term = search ? search.value.trim().toLowerCase() : '';
        var onlySelected = selectedOnly && selectedOnly.getAttribute('aria-pressed') === 'true';
        qsa('[data-assignment-group]', picker).forEach(function (group) {
          var visibleCount = 0;
          qsa('.sa-assignment-option', group).forEach(function (option) {
            var box = qs('input[name="project_ids[]"]', option);
            var text = option.textContent.toLowerCase();
            var visible = (!term || text.indexOf(term) !== -1) && (!onlySelected || (box && box.checked));
            option.hidden = !visible;
            if (visible) visibleCount++;
          });
          group.hidden = visibleCount === 0;
        });
      }

      if (selectAll) {
        selectAll.addEventListener('click', function () {
          if (singleSiteRoles.indexOf(currentRole(picker)) !== -1) return;
          projectInputs(picker).forEach(function (box) { if (!box.disabled) box.checked = true; });
          updateSummary(picker);
          applyFilter();
        });
      }

      if (clear) {
        clear.addEventListener('click', function () {
          projectInputs(picker).forEach(function (box) { if (!box.disabled) box.checked = false; });
          updateSummary(picker);
          applyFilter();
        });
      }

      if (search) {
        search.addEventListener('input', applyFilter);
      }

      if (selectedOnly) {
        selectedOnly.addEventListener('click', function () {
          var pressed = selectedOnly.getAttribute('aria-pressed') === 'true';
          selectedOnly.setAttribute('aria-pressed', pressed ? 'false' : 'true');
          selectedOnly.classList.toggle('is-active', !pressed);
          applyFilter();
        });
      }

      qsa('[data-assignment-group-toggle]', picker).forEach(function (button) {
        button.addEventListener('click', function () {
          if (singleSiteRoles.indexOf(currentRole(picker)) !== -1) return;
          var group = button.closest('[data-assignment-group]');
          var groupBoxes = group ? qsa('input[name="project_ids[]"]', group).filter(function (box) { return !box.disabled; }) : [];
          var shouldSelect = groupBoxes.some(function (box) { return !box.checked; });
          groupBoxes.forEach(function (box) { box.checked = shouldSelect; });
          button.textContent = shouldSelect ? 'Clear constituency' : 'Select constituency';
          updateSummary(picker);
          applyFilter();
        });
      });

      boxes.forEach(function (box) {
        box.addEventListener('change', function () {
          if (box.checked && singleSiteRoles.indexOf(currentRole(picker)) !== -1) {
            projectInputs(picker).forEach(function (other) {
              if (other !== box) other.checked = false;
            });
          }
          updateSummary(picker);
          applyFilter();
        });
      });

      applyRoleMode(picker);
      applyFilter();

      if (roleSelect) {
        roleSelect.addEventListener('change', function () {
          applyRoleMode(picker);
          applyFilter();
        });
      }
    });
  }

  function initWorkLocationPicker() {
    var roleSelect = qs('[data-role-select]');

    function currentRole(picker) {
      if (picker && picker.dataset.staticRole && !roleSelect) return picker.dataset.staticRole.toLowerCase();
      if (!roleSelect) return '';
      var option = roleSelect.options[roleSelect.selectedIndex];
      return option ? (option.getAttribute('data-role-slug') || '').toLowerCase() : '';
    }

    qsa('.sa-work-location-picker').forEach(function (picker) {
      var emptyState = qs('[data-work-location-empty-state]', picker);
      var notApplicable = qs('[data-work-location-not-applicable]', picker);
      var groups = qs('[data-work-location-groups]', picker);
      var boxes = qsa('input[type="checkbox"]', picker);

      function applyRoleMode() {
        var role = currentRole(picker);
        var isIntern = role === 'intern';
        var hasRole = role !== '';

        if (emptyState) emptyState.hidden = hasRole;
        if (notApplicable) notApplicable.hidden = !hasRole || isIntern;
        if (groups) groups.hidden = !isIntern;

        if (!isIntern) {
          boxes.forEach(function (box) { box.checked = false; });
        }
      }

      applyRoleMode();

      if (roleSelect) {
        roleSelect.addEventListener('change', applyRoleMode);
      }
    });
  }

  function init() {
    initPasswordToggles();
    initPasswordTools();
    initAvatarPreview();
    initLiveProfile();
    initAssignmentPicker();
    initWorkLocationPicker();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
