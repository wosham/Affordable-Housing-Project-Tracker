(function () {
  const root = document.querySelector("[data-search-page]");
  if (!root) return;

  const form = document.getElementById("siteSearchForm");
  const input = document.getElementById("siteSearchInput");
  const clearButton = document.getElementById("siteSearchClear");
  const tabs = Array.from(document.querySelectorAll("[data-search-type]"));
  const countBadges = Array.from(document.querySelectorAll("[data-type-count]"));
  const summary = document.getElementById("searchSummary");
  const spinner = document.getElementById("searchSpinner");
  const empty = document.getElementById("searchEmpty");
  const results = document.getElementById("searchResults");
  const loadMore = document.getElementById("searchLoadMore");
  const apiUrl = root.dataset.apiUrl || "api/public/search.php";

  const state = {
    q: root.dataset.initialQuery || "",
    type: root.dataset.initialType || "all",
    page: 1,
    limit: 10,
    total: 0,
    loading: false,
    controller: null
  };

  let debounceTimer = null;

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function escapeRegExp(value) {
    return String(value || "").replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  function highlight(value) {
    const safe = escapeHtml(value);
    if (state.q.trim().length < 2) return safe;
    const regex = new RegExp("(" + escapeRegExp(state.q.trim()) + ")", "ig");
    return safe.replace(regex, "<mark>$1</mark>");
  }

  function setLoading(isLoading) {
    state.loading = isLoading;
    if (spinner) spinner.hidden = !isLoading;
    if (loadMore) loadMore.disabled = isLoading;
  }

  function updateTabs(counts) {
    tabs.forEach((button) => {
      const active = button.dataset.searchType === state.type;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-pressed", active ? "true" : "false");
    });

    const total = Object.values(counts || {}).reduce((sum, value) => sum + Number(value || 0), 0);
    countBadges.forEach((badge) => {
      const type = badge.dataset.typeCount;
      badge.textContent = type === "all" ? String(total) : String((counts && counts[type]) || 0);
    });
  }

  function updateUrl() {
    const params = new URLSearchParams();
    if (state.q.trim()) params.set("q", state.q.trim());
    if (state.type !== "all") params.set("type", state.type);
    const next = window.location.pathname + (params.toString() ? "?" + params.toString() : "");
    window.history.replaceState({}, "", next);
  }

  function resetView(message) {
    if (results) results.innerHTML = "";
    if (empty) {
      empty.hidden = false;
      empty.querySelector("h3").textContent = message || "Start with a name, location, topic, or status.";
      empty.querySelector("p").textContent = "Examples: Kitale, progress report, procurement, FAQ, Saboti, gallery.";
    }
    if (loadMore) loadMore.hidden = true;
    if (summary) summary.textContent = "Type at least 2 characters to search.";
    updateTabs({});
  }

  function renderResult(item) {
    const image = item.image
      ? '<img src="' + escapeHtml(item.image) + '" alt="" loading="lazy" decoding="async">'
      : '<i class="fa-solid ' + escapeHtml(item.icon || "fa-magnifying-glass") + '" aria-hidden="true"></i>';
    const meta = [item.meta, item.date].filter(Boolean).map(escapeHtml).join("<span aria-hidden=\"true\">·</span>");

    return [
      '<article class="search-result">',
      '  <div class="search-result__media">' + image + "</div>",
      '  <div class="search-result__body">',
      '    <div class="search-result__meta">',
      '      <span class="search-result__type">' + escapeHtml(item.label || item.type || "Result") + "</span>",
      item.badge ? '      <span class="search-result__badge">' + escapeHtml(item.badge) + "</span>" : "",
      "    </div>",
      '    <h3><a href="' + escapeHtml(item.url || "#") + '">' + highlight(item.title || "Untitled") + "</a></h3>",
      item.excerpt ? "    <p>" + highlight(item.excerpt) + "</p>" : "",
      meta ? '    <div class="search-result__foot">' + meta + "</div>" : "",
      "  </div>",
      '  <a class="search-result__open" href="' + escapeHtml(item.url || "#") + '" aria-label="Open result">',
      '    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>',
      "  </a>",
      "</article>"
    ].join("");
  }

  function renderPayload(payload, append) {
    const list = Array.isArray(payload.results) ? payload.results : [];
    state.total = Number(payload.total || 0);
    updateTabs(payload.counts || {});

    if (summary) {
      if (state.total === 0) {
        summary.textContent = payload.message || "No public results matched your search.";
      } else {
        summary.textContent = state.total + " result" + (state.total === 1 ? "" : "s") + " for \"" + state.q.trim() + "\"";
      }
    }

    if (!append && results) results.innerHTML = "";
    if (results && list.length) {
      results.insertAdjacentHTML("beforeend", list.map(renderResult).join(""));
    }

    if (empty) {
      empty.hidden = state.total > 0;
      if (state.total === 0) {
        empty.querySelector("h3").textContent = payload.message || "No matching public result found.";
        empty.querySelector("p").textContent = "Try another project name, constituency, topic, date, or category.";
      }
    }

    if (loadMore) {
      loadMore.hidden = !payload.has_more;
    }
  }

  async function runSearch(options) {
    const append = options && options.append;
    state.q = input ? input.value.trim() : state.q.trim();

    if (state.q.length < 2) {
      if (state.controller) state.controller.abort();
      resetView();
      updateUrl();
      return;
    }

    if (state.controller) state.controller.abort();
    state.controller = new AbortController();
    setLoading(true);
    updateUrl();

    const params = new URLSearchParams({
      q: state.q,
      type: state.type,
      page: String(state.page),
      limit: String(state.limit)
    });

    try {
      const response = await fetch(apiUrl + "?" + params.toString(), {
        headers: { Accept: "application/json" },
        signal: state.controller.signal
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload.message || "Search failed.");
      }
      const normalized = {
        results: payload.data && Array.isArray(payload.data.results) ? payload.data.results : (Array.isArray(payload.results) ? payload.results : []),
        counts: payload.data && payload.data.counts ? payload.data.counts : (payload.counts || {}),
        total: payload.meta && payload.meta.total !== undefined ? payload.meta.total : payload.total,
        has_more: payload.meta && payload.meta.has_more !== undefined ? payload.meta.has_more : payload.has_more,
        message: payload.message || ""
      };
      renderPayload(normalized, append);
    } catch (error) {
      if (error.name === "AbortError") return;
      if (summary) summary.textContent = "Search is temporarily unavailable.";
      if (empty) {
        empty.hidden = false;
        empty.querySelector("h3").textContent = "Search could not be completed.";
        empty.querySelector("p").textContent = "Please try again shortly.";
      }
      if (results && !append) results.innerHTML = "";
      if (loadMore) loadMore.hidden = true;
    } finally {
      setLoading(false);
    }
  }

  function scheduleSearch() {
    window.clearTimeout(debounceTimer);
    state.page = 1;
    debounceTimer = window.setTimeout(() => runSearch(), 280);
  }

  if (form) {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      state.page = 1;
      runSearch();
    });
  }

  if (input) {
    input.addEventListener("input", scheduleSearch);
  }

  if (clearButton) {
    clearButton.addEventListener("click", () => {
      if (input) input.value = "";
      state.q = "";
      state.page = 1;
      resetView();
      updateUrl();
      if (input) input.focus();
    });
  }

  tabs.forEach((button) => {
    button.addEventListener("click", () => {
      state.type = button.dataset.searchType || "all";
      state.page = 1;
      updateTabs({});
      runSearch();
    });
  });

  if (loadMore) {
    loadMore.addEventListener("click", () => {
      if (state.loading) return;
      state.page += 1;
      runSearch({ append: true });
    });
  }

  if (state.q.length >= 2 && input) {
    input.value = state.q;
    runSearch();
  } else {
    resetView();
  }
})();
