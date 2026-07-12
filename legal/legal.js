(function () {
  'use strict';

  var article = document.getElementById('lgArticle');
  var readingTime = document.getElementById('lgReadingTime');
  var tocLinks = Array.from(document.querySelectorAll('.lg-toc-link'));
  var sections = Array.from(document.querySelectorAll('.lg-section[id]'));
  var noResults = document.getElementById('lgNoResults');

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

  function updateActiveToc() {
    var active = null;
    sections.forEach(function (section) {
      if (!section.classList.contains('is-filtered-out') && section.getBoundingClientRect().top <= 170) {
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

  var searchInput = document.getElementById('lgSearchInput');
  var searchClear = document.getElementById('lgSearchClear');

  function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function clearHighlights(section) {
    section.querySelectorAll('mark.lg-mark').forEach(function (mark) {
      var text = document.createTextNode(mark.textContent);
      mark.parentNode.replaceChild(text, mark);
      if (text.parentNode) text.parentNode.normalize();
    });
  }

  function highlightText(section, query) {
    clearHighlights(section);
    if (!query) return;

    var re = new RegExp(escapeRegExp(query), 'gi');
    var walker = document.createTreeWalker(section, NodeFilter.SHOW_TEXT, {
      acceptNode: function (node) {
        var parent = node.parentElement;
        if (!parent || parent.closest('script, style, mark, .lg-copy-anchor')) {
          return NodeFilter.FILTER_REJECT;
        }
        re.lastIndex = 0;
        return re.test(node.nodeValue || '') ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      }
    });

    var nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);

    nodes.forEach(function (node) {
      var text = node.nodeValue || '';
      var fragment = document.createDocumentFragment();
      var lastIndex = 0;

      re.lastIndex = 0;
      text.replace(re, function (match, offset) {
        if (offset > lastIndex) {
          fragment.appendChild(document.createTextNode(text.slice(lastIndex, offset)));
        }
        var mark = document.createElement('mark');
        mark.className = 'lg-mark';
        mark.textContent = match;
        fragment.appendChild(mark);
        lastIndex = offset + match.length;
        return match;
      });

      if (lastIndex < text.length) {
        fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
      }

      node.parentNode.replaceChild(fragment, node);
    });
  }

  function applySearch() {
    var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    var visible = 0;

    sections.forEach(function (section) {
      clearHighlights(section);
      var matched = !query || section.innerText.toLowerCase().indexOf(query) !== -1;
      section.classList.toggle('is-filtered-out', !matched);
      if (matched) {
        visible += 1;
        highlightText(section, query);
      }
    });

    tocLinks.forEach(function (link) {
      var id = (link.getAttribute('href') || '').replace(/^#/, '');
      var target = document.getElementById(id);
      link.hidden = !!query && (!target || target.classList.contains('is-filtered-out'));
    });

    if (searchClear) searchClear.hidden = !query;
    if (noResults) noResults.hidden = visible !== 0;
    updateActiveToc();
  }

  if (searchInput) {
    searchInput.addEventListener('input', applySearch);
  }

  if (searchClear && searchInput) {
    searchClear.addEventListener('click', function () {
      searchInput.value = '';
      searchInput.focus();
      applySearch();
    });
  }
})();
