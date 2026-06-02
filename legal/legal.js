(function () {
  'use strict';

  var article = document.getElementById('lgArticle');
  var readingTime = document.getElementById('lgReadingTime');
  if (article && readingTime) {
    var words = article.innerText.trim().split(/\s+/).filter(Boolean).length;
    readingTime.textContent = '~' + Math.max(1, Math.round(words / 200)) + ' min read';
  }

  var printBtn = document.getElementById('lgPrintBtn');
  if (printBtn) {
    printBtn.addEventListener('click', function () {
      window.print();
    });
  }

  var tocToggle = document.getElementById('lgTocToggle');
  var tocNav = document.getElementById('lgTocNav');
  if (tocToggle && tocNav) {
    tocToggle.addEventListener('click', function () {
      var collapsed = tocNav.classList.toggle('is-collapsed');
      tocToggle.classList.toggle('is-collapsed', collapsed);
      tocToggle.setAttribute('aria-expanded', String(!collapsed));
    });
  }

  var tocLinks = Array.from(document.querySelectorAll('.lg-toc-link'));
  var sections = Array.from(document.querySelectorAll('.lg-section[id]'));

  function updateActiveToc() {
    var active = null;
    sections.forEach(function (section) {
      if (section.getBoundingClientRect().top <= 170) {
        active = section;
      }
    });

    tocLinks.forEach(function (link) {
      link.classList.toggle('is-active', !!active && link.getAttribute('href') === '#' + active.id);
    });
  }

  if (tocLinks.length && sections.length) {
    window.addEventListener('scroll', updateActiveToc, { passive: true });
    updateActiveToc();
  }

  document.querySelectorAll('.lg-section-heading').forEach(function (heading) {
    var section = heading.closest('.lg-section');
    if (!section || !section.id) return;

    var link = document.createElement('a');
    link.href = '#' + section.id;
    link.className = 'lg-copy-anchor';
    link.setAttribute('aria-label', 'Copy link to this section');
    link.innerHTML = '<i class="fa-solid fa-link" aria-hidden="true"></i>';

    link.addEventListener('click', function (event) {
      event.preventDefault();
      var url = window.location.origin + window.location.pathname + '#' + section.id;
      if (!navigator.clipboard || !navigator.clipboard.writeText) {
        window.location.hash = section.id;
        return;
      }

      navigator.clipboard.writeText(url).then(function () {
        link.classList.add('copied');
        link.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
        setTimeout(function () {
          link.classList.remove('copied');
          link.innerHTML = '<i class="fa-solid fa-link" aria-hidden="true"></i>';
        }, 1400);
      }).catch(function () {
        window.location.hash = section.id;
      });
    });

    heading.appendChild(link);
  });
})();
