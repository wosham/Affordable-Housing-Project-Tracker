/* =========================================================
   TRANS-NZOIA AHP — Legal Page Scripts (Terms of Use)
   Handles: TOC scroll-spy, TOC toggle, reading time,
            print button, copy-anchor links
   ========================================================= */
(function () {
  'use strict';

  /* ── Reading time ── */
  var article = document.getElementById('lgArticle');
  var rtEl    = document.getElementById('lgReadingTime');
  if (article && rtEl) {
    var words = article.innerText.trim().split(/\s+/).length;
    rtEl.textContent = '~' + Math.max(1, Math.round(words / 200)) + ' min read';
  }

  /* ── Print button ── */
  var printBtn = document.getElementById('lgPrintBtn');
  if (printBtn) {
    printBtn.addEventListener('click', function () { window.print(); });
  }

  /* ── TOC collapse / expand ── */
  var tocToggle = document.getElementById('lgTocToggle');
  var tocNav    = document.getElementById('lgTocNav');
  if (tocToggle && tocNav) {
    tocToggle.addEventListener('click', function () {
      var collapsed = tocNav.classList.toggle('is-collapsed');
      tocToggle.classList.toggle('is-collapsed', collapsed);
      tocToggle.setAttribute('aria-expanded', String(!collapsed));
    });
  }

  /* ── TOC scroll spy ── */
  var tocLinks = Array.from(document.querySelectorAll('.lg-toc-link'));
  var sections = Array.from(document.querySelectorAll('.lg-section[id]'));

  function updateActiveToc() {
    var active = null;
    sections.forEach(function (sec) {
      if (sec.getBoundingClientRect().top <= 160) { active = sec; }
    });
    tocLinks.forEach(function (link) {
      link.classList.toggle(
        'is-active',
        !!active && link.getAttribute('href') === '#' + active.id
      );
    });
  }

  if (tocLinks.length && sections.length) {
    window.addEventListener('scroll', updateActiveToc, { passive: true });
    updateActiveToc();
  }

  /* ── Copy-anchor links (injected into headings on hover) ── */
  document.querySelectorAll('.lg-section-heading').forEach(function (heading) {
    var section = heading.closest('.lg-section');
    if (!section || !section.id) { return; }

    var btn = document.createElement('a');
    btn.href      = '#' + section.id;
    btn.className = 'lg-copy-anchor';
    btn.setAttribute('aria-label', 'Copy link to this section');
    btn.innerHTML = '<i class="fa-solid fa-link" aria-hidden="true"></i>';

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var url = window.location.origin + window.location.pathname + '#' + section.id;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
          btn.classList.add('copied');
          btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
          setTimeout(function () {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fa-solid fa-link" aria-hidden="true"></i>';
          }, 1500);
        }).catch(function () { window.location.hash = section.id; });
      } else {
        window.location.hash = section.id;
      }
    });

    heading.appendChild(btn);
  });

})();
