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

    function update() {
      var fullName = (((first && first.value) || '').trim() + ' ' + ((last && last.value) || '').trim()).trim();
      setText('[data-profile-name]', fullName || 'New User');
      setText('[data-profile-title]', (title && title.value.trim()) || 'Role title will appear here');
      setText('[data-profile-email]', (email && email.value.trim()) || 'email@example.com');
      setText('[data-profile-department]', (department && department.value.trim()) || 'Not set');

      if (status) {
        var label = status.options[status.selectedIndex] ? status.options[status.selectedIndex].text : 'Unknown';
        setText('[data-profile-status]', label);
      }

      if (avatar && avatar.querySelector('img') === null) {
        avatar.textContent = initials(first && first.value, last && last.value, email && email.value);
      }
    }

    [first, last, email, title, department, status].forEach(function (field) {
      if (field) field.addEventListener('input', update);
      if (field) field.addEventListener('change', update);
    });

    update();
  }

  function init() {
    initPasswordToggles();
    initAvatarPreview();
    initLiveProfile();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
