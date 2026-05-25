/* =========================================================
   TRANS-NZOIA AHP — News Article Page JS
   ========================================================= */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* ── Reading Progress Bar ── */
    var progressBar = document.getElementById('readingProgress');
    var articleProse = document.querySelector('.article-prose');
    if (progressBar && articleProse) {
      function updateProgress() {
        var articleTop  = articleProse.getBoundingClientRect().top + window.scrollY;
        var articleEnd  = articleTop + articleProse.offsetHeight;
        var viewBottom  = window.scrollY + window.innerHeight;
        var range       = articleEnd - articleTop - window.innerHeight;
        var pct = range <= 0
          ? (window.scrollY >= articleTop ? 100 : 0)
          : Math.min(100, Math.max(0, ((window.scrollY - articleTop) / range) * 100));
        progressBar.style.width = pct + '%';
      }
      window.addEventListener('scroll', updateProgress, { passive: true });
      updateProgress();
    }

    /* ── Dynamic Read Time ── */
    var readTimeEl = document.getElementById('articleReadTime');
    if (readTimeEl && articleProse) {
      var words = articleProse.textContent.trim().split(/\s+/).length;
      var mins  = Math.max(1, Math.round(words / 220));
      readTimeEl.textContent = mins + ' min read';
    }

    /* ── Copy Link Helper ── */
    function copyToClipboard(text, btn, defaultHtml) {
      var promise = navigator.clipboard
        ? navigator.clipboard.writeText(text)
        : new Promise(function (resolve) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus(); ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            resolve();
          });
      promise.then(function () {
        btn.classList.add('is-copied');
        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copied!';
        setTimeout(function () {
          btn.classList.remove('is-copied');
          btn.innerHTML = defaultHtml;
        }, 2400);
      }).catch(function () {});
    }

    /* ── Hero share buttons ── */
    document.querySelectorAll('[data-action="share-x"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var h = document.querySelector('.article-headline');
        var text = h ? h.textContent.trim() : document.title;
        window.open(
          'https://twitter.com/intent/tweet?text=' +
          encodeURIComponent(text) + '&url=' + encodeURIComponent(window.location.href),
          '_blank', 'noopener,width=600,height=420'
        );
      });
    });

    document.querySelectorAll('[data-action="share-fb"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        window.open(
          'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href),
          '_blank', 'noopener,width=620,height=520'
        );
      });
    });

    document.querySelectorAll('[data-action="share-wa"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var h = document.querySelector('.article-headline');
        var text = h ? h.textContent.trim() : document.title;
        window.open(
          'https://wa.me/?text=' + encodeURIComponent(text + '\n' + window.location.href),
          '_blank', 'noopener'
        );
      });
    });

    document.querySelectorAll('[data-action="copy-link"]').forEach(function (btn) {
      var defaultHtml = btn.innerHTML;
      btn.addEventListener('click', function () {
        copyToClipboard(window.location.href, btn, defaultHtml);
      });
    });

    /* ── Print ── */
    document.querySelectorAll('[data-action="print"]').forEach(function (btn) {
      btn.addEventListener('click', function () { window.print(); });
    });

    /* ── Feedback Widget ── */
    var feedbackBtns  = document.querySelectorAll('.article-feedback-btn');
    var feedbackBtnWrap = document.querySelector('.article-feedback-btns');
    var feedbackThanks  = document.querySelector('.article-feedback-thanks');
    feedbackBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.classList.contains('voted')) return;
        feedbackBtns.forEach(function (b) { b.classList.remove('voted'); });
        btn.classList.add('voted');
        if (feedbackBtnWrap && feedbackThanks) {
          setTimeout(function () {
            feedbackBtnWrap.style.display = 'none';
            feedbackThanks.classList.add('is-visible');
          }, 520);
        }
      });
    });

    /* ── Sidebar share buttons ── */
    document.querySelectorAll('.sidebar-share-btn[data-action]').forEach(function (btn) {
      var action = btn.getAttribute('data-action');
      if (action === 'share-x' || action === 'share-fb' || action === 'share-wa') return;
      if (action === 'copy-link') {
        var defaultHtml = btn.innerHTML;
        btn.addEventListener('click', function () {
          copyToClipboard(window.location.href, btn, defaultHtml);
        });
      }
    });

  });

}());
