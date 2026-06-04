(function () {
  'use strict';

  const picker = document.querySelector('[data-programme-project-picker]');
  if (!picker) return;

  picker.addEventListener('change', function () {
    const form = picker.closest('form');
    if (form) form.submit();
  });
}());
