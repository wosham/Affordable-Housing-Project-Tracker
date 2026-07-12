(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function text(selector, value, root) {
    var node = qs(selector, root);
    if (node) node.textContent = value;
  }

  function setStatus(root, message, state) {
    var status = qs('[data-attendance-status]', root) || qs('[data-form-status]', root);
    if (!status) return;
    status.textContent = message || '';
    status.dataset.state = state || '';
  }

  function setGps(root, state, label) {
    var dot = qs('[data-gps-dot]', root);
    var labelNode = qs('[data-gps-label]', root);
    if (dot) {
      dot.classList.remove('is-loading', 'is-success', 'is-error');
      if (state) dot.classList.add('is-' + state);
    }
    if (labelNode && label) labelNode.textContent = label;
  }

  function setButton(button, loading, label) {
    if (!button) return;
    button.disabled = !!loading;
    if (label) button.innerHTML = label;
  }

  function position() {
    return new Promise(function (resolve, reject) {
      if (!navigator.geolocation) {
        reject(new Error('Location is not available on this device.'));
        return;
      }

      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 16000,
        maximumAge: 15000
      });
    });
  }

  function request(url, options) {
    if (window.AHPTC && window.AHPTC.request) {
      return window.AHPTC.request(url, options);
    }

    return fetch(url, options).then(function (response) {
      return response.json().catch(function () {
        return { success: false, message: 'The server response could not be read.' };
      }).then(function (payload) {
        if (!response.ok && payload && payload.success !== true) {
          throw new Error(payload.message || 'Attendance request failed.');
        }
        return payload;
      });
    });
  }

  function applyResult(form, result) {
    if (!result) return;
    text('[data-distance-label]', result.distance_label || '-', form);
    text('[data-accuracy-label]', result.accuracy_label || '-', form);
    text('[data-result-label]', result.status_label || result.message || '-', form);
  }

  function initForm(form) {
    if (form.dataset.attendanceReady === '1') return;
    form.dataset.attendanceReady = '1';

    var endpoint = form.getAttribute('action') || '../../api/attendance/sign-in.php';
    var button = qs('[type="submit"]', form);
    var defaultButton = button ? button.innerHTML : '';
    var gatewayOpen = form.dataset.gatewayOpen === '1';
    var alreadySigned = form.dataset.alreadySigned === '1';

    if (!gatewayOpen) {
      setGps(form, 'error', 'Gateway closed');
      setStatus(form, 'Attendance sign-in is currently closed for this project.', 'error');
      if (button) button.disabled = true;
      return;
    }

    if (alreadySigned) {
      setGps(form, 'success', 'Already signed');
      setStatus(form, 'Your attendance has already been recorded today.', 'success');
      if (button) button.disabled = true;
      return;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      setButton(button, true, '<i class="fa-solid fa-location-crosshairs"></i> Getting location...');
      setGps(form, 'loading', 'Getting GPS');
      setStatus(form, 'Getting your location. Please keep this tab open.', 'loading');

      position().then(function (pos) {
        var data = new FormData(form);
        data.set('latitude', pos.coords.latitude);
        data.set('longitude', pos.coords.longitude);
        data.set('accuracy_meters', Math.round(pos.coords.accuracy || 0));
        text('[data-accuracy-label]', Math.round(pos.coords.accuracy || 0) + ' m', form);
        setGps(form, 'success', 'Location captured');
        setStatus(form, 'Submitting your attendance record...', 'loading');
        setButton(button, true, '<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
        return request(endpoint, { method: 'POST', body: data });
      }).then(function (result) {
        if (!result.success) throw new Error(result.message || 'Attendance sign-in failed.');
        applyResult(form, result);
        setGps(form, 'success', 'Recorded');
        setStatus(form, result.message || 'Attendance recorded.', 'success');
        setButton(button, true, '<i class="fa-solid fa-check"></i> Signed In');
        form.dispatchEvent(new CustomEvent('attendance:sign-in', { detail: result, bubbles: true }));
      }).catch(function (error) {
        setGps(form, 'error', 'Needs attention');
        setStatus(form, error.message || 'Attendance sign-in failed.', 'error');
        setButton(button, false, defaultButton);
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
