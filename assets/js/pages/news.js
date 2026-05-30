/* TRANS-NZOIA AHP - News & Updates Page Script */
(function () {
  var BATCH_SIZE = 9;
  var allCards = [];
  var visibleCards = [];
  var shown = 0;

  document.addEventListener('DOMContentLoaded', function () {
    allCards = Array.from(document.querySelectorAll('.news-card'));

    var filterPills = document.querySelectorAll('.news-filter-pill');
    var searchInput = document.getElementById('newsSearch');
    var searchClear = document.getElementById('newsSearchClear');
    var sortSelect = document.getElementById('newsSort');
    var loadMoreBtn = document.getElementById('loadMoreBtn');
    var loadMoreLbl = document.getElementById('loadMoreLabel');
    var resultsCount = document.getElementById('newsResultsCount');
    var emptyState = document.getElementById('newsEmpty');
    var emptyReset = document.getElementById('emptyReset');
    var gridConfig = document.getElementById('newsGrid');
    var labels = {
      single: gridConfig ? (gridConfig.getAttribute('data-results-single') || 'article') : 'article',
      plural: gridConfig ? (gridConfig.getAttribute('data-results-plural') || 'articles') : 'articles',
      showing: gridConfig ? (gridConfig.getAttribute('data-showing-label') || 'Showing') : 'Showing',
      of: gridConfig ? (gridConfig.getAttribute('data-of-label') || 'of') : 'of',
      all: gridConfig ? (gridConfig.getAttribute('data-all-label') || 'All') : 'All',
      shown: gridConfig ? (gridConfig.getAttribute('data-shown-label') || 'shown') : 'shown'
    };

    var activeCategory = 'all';
    var searchTerm = '';

    function articleLabel(count) {
      return count === 1 ? labels.single : labels.plural;
    }

    function showingText(shownCount, totalCount) {
      return labels.showing + ' ' + shownCount + ' ' + labels.of + ' ' + totalCount + ' ' + articleLabel(totalCount);
    }

    function allShownText(totalCount) {
      return labels.all + ' ' + totalCount + ' ' + articleLabel(totalCount) + ' ' + labels.shown;
    }

    function getSortedCards(cards) {
      var sorted = cards.slice();
      if (!sortSelect) {
        return sorted;
      }

      var val = sortSelect.value;
      sorted.sort(function (a, b) {
        var da = a.getAttribute('data-date') || '';
        var db = b.getAttribute('data-date') || '';
        return val === 'oldest' ? da.localeCompare(db) : db.localeCompare(da);
      });
      return sorted;
    }

    function applyFilters() {
      var base = getSortedCards(allCards);

      visibleCards = base.filter(function (card) {
        var cat = card.getAttribute('data-category') || '';
        var title = (card.getAttribute('data-title') || '').toLowerCase();
        var exc = (card.querySelector('.news-card-excerpt') || { textContent: '' }).textContent.toLowerCase();
        var catOk = activeCategory === 'all' || cat === activeCategory;
        var searchOk = !searchTerm || title.includes(searchTerm) || exc.includes(searchTerm);
        return catOk && searchOk;
      });

      allCards.forEach(function (card) {
        card.classList.add('is-hidden');
      });

      shown = Math.min(BATCH_SIZE, visibleCards.length);
      visibleCards.slice(0, shown).forEach(function (card) {
        card.classList.remove('is-hidden');
      });

      if (resultsCount) {
        resultsCount.textContent = visibleCards.length + ' ' + articleLabel(visibleCards.length);
      }

      if (loadMoreBtn) {
        if (shown < visibleCards.length) {
          loadMoreBtn.classList.remove('is-hidden');
          if (loadMoreLbl) {
            loadMoreLbl.textContent = showingText(shown, visibleCards.length);
          }
        } else {
          loadMoreBtn.classList.add('is-hidden');
          if (loadMoreLbl) {
            loadMoreLbl.textContent = visibleCards.length > 0 ? allShownText(visibleCards.length) : '';
          }
        }
      }

      if (emptyState) {
        emptyState.classList.toggle('is-visible', visibleCards.length === 0);
      }
    }

    filterPills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        filterPills.forEach(function (p) {
          p.classList.remove('is-active');
        });
        pill.classList.add('is-active');
        activeCategory = pill.getAttribute('data-category') || 'all';
        shown = 0;
        applyFilters();
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        searchTerm = searchInput.value.trim().toLowerCase();
        if (searchClear) {
          searchClear.classList.toggle('is-visible', searchTerm.length > 0);
        }
        shown = 0;
        applyFilters();
      });
    }

    if (searchClear) {
      searchClear.addEventListener('click', function () {
        if (searchInput) {
          searchInput.value = '';
        }
        searchTerm = '';
        searchClear.classList.remove('is-visible');
        shown = 0;
        applyFilters();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        shown = 0;
        applyFilters();
      });
    }

    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', function () {
        var next = shown + BATCH_SIZE;
        visibleCards.slice(shown, next).forEach(function (card) {
          card.classList.remove('is-hidden');
        });
        shown = Math.min(next, visibleCards.length);

        if (shown >= visibleCards.length) {
          loadMoreBtn.classList.add('is-hidden');
          if (loadMoreLbl) {
            loadMoreLbl.textContent = allShownText(visibleCards.length);
          }
        } else if (loadMoreLbl) {
          loadMoreLbl.textContent = showingText(shown, visibleCards.length);
        }
      });
    }

    if (emptyReset) {
      emptyReset.addEventListener('click', function () {
        filterPills.forEach(function (pill) {
          pill.classList.remove('is-active');
        });
        var allPill = document.querySelector('[data-category="all"]');
        if (allPill) {
          allPill.classList.add('is-active');
        }
        activeCategory = 'all';
        searchTerm = '';
        if (searchInput) {
          searchInput.value = '';
        }
        if (searchClear) {
          searchClear.classList.remove('is-visible');
        }
        shown = 0;
        applyFilters();
      });
    }

    var urlParams = new URLSearchParams(window.location.search);
    var catParam = urlParams.get('category');
    if (catParam) {
      var matchPill = document.querySelector('[data-category="' + catParam + '"]');
      if (matchPill) {
        filterPills.forEach(function (pill) {
          pill.classList.remove('is-active');
        });
        matchPill.classList.add('is-active');
        activeCategory = catParam;
      }
    }

    var catIcons = {
      'programme-updates': { icon: 'fa-bullhorn', label: 'Programme' },
      'groundbreaking': { icon: 'fa-hammer', label: 'Groundbreaking' },
      'construction': { icon: 'fa-hard-hat', label: 'Construction' },
      'policy': { icon: 'fa-file-contract', label: 'Policy' },
      'community': { icon: 'fa-people-group', label: 'Community' },
      'official': { icon: 'fa-stamp', label: 'Official' },
      'field-reports': { icon: 'fa-map-pin', label: 'Field Report' }
    };

    allCards.forEach(function (card) {
      var cat = card.getAttribute('data-category');
      var imgDiv = card.querySelector('.news-card-img');
      if (imgDiv && cat && catIcons[cat]) {
        var placeholder = document.createElement('div');
        placeholder.className = 'news-card-img-placeholder';
        placeholder.setAttribute('aria-hidden', 'true');
        placeholder.innerHTML =
          '<i class="fa-solid ' + catIcons[cat].icon + '"></i>' +
          '<span>' + catIcons[cat].label + '</span>';
        imgDiv.insertBefore(placeholder, imgDiv.firstChild);
      }
    });

    applyFilters();
  });
}());
