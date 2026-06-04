(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function setStatus(root, message, state) {
    var status = qs('[data-attendance-status]', root) || qs('[data-form-status]', root);
    if (!status) return;
    status.textContent = message || '';
    status.dataset.state = state || '';
  }

  function position() {
    return new Promise(function (resolve, reject) {
      if (!navigator.geolocation) {
        reject(new Error('Location is not available on this device.'));
        return;
      }

      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 30000
      });
    });
  }

  function request(url, options) {
    if (window.AHPTC && window.AHPTC.request) {
      return window.AHPTC.request(url, options);
    }
    return fetch(url, options).then(function (response) {
      return response.json();
    });
  }

  function initForm(form) {
    if (form.dataset.attendanceReady === '1') return;
    form.dataset.attendanceReady = '1';

    var endpoint = form.getAttribute('action') || 'api/attendance/sign-in.php';
    var button = qs('[type="submit"]', form);

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (button) button.disabled = true;
      setStatus(form, 'Getting your location...', 'loading');

      position().then(function (pos) {
        var data = new FormData(form);
        data.set('latitude', pos.coords.latitude);
        data.set('longitude', pos.coords.longitude);
        data.set('accuracy_meters', pos.coords.accuracy || '');
        setStatus(form, 'Submitting attendance...', 'loading');
        return request(endpoint, { method: 'POST', body: data });
      }).then(function (result) {
        if (!result.success) throw new Error(result.message || 'Sign-in failed.');
        setStatus(form, result.message || 'Attendance recorded.', 'success');
        form.dispatchEvent(new CustomEvent('attendance:sign-in', { detail: result, bubbles: true }));
      }).catch(function (error) {
        setStatus(form, error.message || 'Attendance sign-in failed.', 'error');
      }).finally(function () {
        if (button) button.disabled = false;
      });
    });
  }

  function init() {
    Array.prototype.slice.call(document.querySelectorAll('[data-attendance-signin]')).forEach(initForm);
  }

  window.AHPTC = Object.assign(window.AHPTC || {}, {
    initAttendanceSignin: init
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
