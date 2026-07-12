(function () {
  'use strict';

  const root = window;
  const existing = root.AHP_DATA && typeof root.AHP_DATA === 'object' ? root.AHP_DATA : {};

  const parsePageData = () => {
    const node = document.getElementById('ahp-page-data') || document.getElementById('tna-page-data');
    if (!node) return {};

    try {
      return JSON.parse(node.textContent || '{}');
    } catch (error) {
      if (root.console && root.console.warn) {
        root.console.warn('Could not parse public page data.', error);
      }
      return {};
    }
  };

  const pageData = parsePageData();
  const modelData = pageData.models && typeof pageData.models === 'object' ? pageData.models : {};

  const compatibility = {
    dataSource: 'database',
    hasStaticFallbacks: false,
    pages: pageData,
    settings: pageData.settings || {},
    constituencies: modelData.constituencies || [],
    projects: modelData.projects || [],
    news: modelData.news || [],
    announcements: modelData.announcements || [],
    gallery: modelData.gallery || [],
    stats: modelData.stats || {},
    getConstituency(id) {
      const key = String(id || '').toLowerCase();
      return this.constituencies.find((item) => {
        return String(item.id || item.slug || item.name || '').toLowerCase() === key;
      }) || null;
    },
    getProject(id) {
      const key = String(id || '').toLowerCase();
      return this.projects.find((item) => {
        return String(item.id || item.slug || item.title || item.name || '').toLowerCase() === key;
      }) || null;
    },
  };

  root.AHP_DATA = Object.assign(compatibility, existing, {
    dataSource: existing.dataSource || 'database',
    hasStaticFallbacks: Boolean(existing.hasStaticFallbacks),
  });

  root.TNAH = root.TNAH || {};
  root.TNAH.data = Object.assign(root.TNAH.data || {}, {
    source: 'database',
    payload: pageData,
    page() {
      return pageData;
    },
    get(key, fallbackValue = null) {
      return Object.prototype.hasOwnProperty.call(root.AHP_DATA, key) ? root.AHP_DATA[key] : fallbackValue;
    },
    has(key) {
      return Object.prototype.hasOwnProperty.call(root.AHP_DATA, key);
    },
    section(key, fallbackValue = {}) {
      return pageData.sections && pageData.sections[key] ? pageData.sections[key] : fallbackValue;
    },
    model(key, fallbackValue = []) {
      return modelData[key] || fallbackValue;
    },
    hasModel(key) {
      return Object.prototype.hasOwnProperty.call(modelData, key);
    },
  });

  if (root.TNAH?.config?.env !== 'production' && root.console && root.console.info) {
    root.console.info('Public page data is now sourced from CMS/database contracts.');
  }
})();
