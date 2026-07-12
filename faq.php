<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'faq';
$cmsPage = CmsLoader::page('faq');
$hero = CmsLoader::content($cmsPage, 'faq_hero', []);
$popularContent = CmsLoader::content($cmsPage, 'faq_popular', []);
$libraryContent = CmsLoader::content($cmsPage, 'faq_library', []);
$ctaContent = CmsLoader::content($cmsPage, 'faq_contact_cta', []);

$e = static fn (mixed $value): string => Security::e($value);
$text = static function (array $content, string $key, string $default = ''): string {
    $value = CmsLoader::text($content, $key, '');
    return $value !== '' ? $value : $default;
};

function faq_public_icon(mixed $icon, string $fallback = 'fa-circle-question'): string
{
    $icon = trim((string)$icon);
    return preg_match('/^fa-[a-z0-9-]+$/i', $icon) ? $icon : $fallback;
}

function faq_public_answer(mixed $html): string
{
    $html = (string)$html;
    $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
    $html = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><address><span>');
    $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/iu', '', $html) ?? '';
    $html = preg_replace('/\s+on[a-z]+\s*=\s*[^\s>]+/iu', '', $html) ?? '';
    $html = preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/iu', 'href="#"', $html) ?? '';
    $html = preg_replace('/<a\s+/i', '<a class="fq-link" rel="noopener noreferrer" ', $html) ?? '';
    return trim($html) !== '' ? $html : '<p>Answer coming soon.</p>';
}

function faq_public_dom_id(array $item): string
{
    return FAQItem::publicDomId($item);
}

$groups = FAQItem::publicGrouped();
$categories = FAQCategory::publicWithItems();
$stats = FAQItem::publicStats();
$popularLimit = max(1, min(12, (int)$text($popularContent, 'display_count', '6')));
$popularQuestions = FAQItem::publicPopular($popularLimit);
if ($popularQuestions === []) {
    $popularQuestions = array_slice(FAQItem::publicItems(), 0, $popularLimit);
}

if ($categories === [] && $groups !== []) {
    $categories = array_map(static fn (array $group): array => [
        'id' => $group['id'],
        'name' => $group['name'],
        'slug' => $group['slug'],
        'icon' => $group['icon'],
        'description' => $group['description'],
        'published_count' => count($group['items'] ?? []),
    ], array_values($groups));
}

$phone = (string)(function_exists('public_setting') ? public_setting('contact_phone', '+254 53 000 0000') : '+254 53 000 0000');
$email = (string)(function_exists('public_setting') ? public_setting('contact_email', 'housing@transnzoia.go.ke') : 'housing@transnzoia.go.ke');
$phoneHref = preg_replace('/[^\d+]/', '', $phone) ?: '+254530000000';
$heroImage = $text($hero, 'background_image', '');
if ($heroImage === '') {
    $heroImage = trim((string)($cmsPage['hero_image'] ?? ''));
}
if ($heroImage === '') {
    $heroImage = 'uploads/heroes/hero-main.jpg';
}
$heroTitle = $text($hero, 'title', "Your Questions,\nAnswered.");
$heroSubtitle = $text($hero, 'subtitle', 'Everything you need to know about eligibility, applying, monthly contributions, unit allocation and your rights as an AHP beneficiary in Trans-Nzoia County.');

$pageTitle = $cmsPage['seo_title'] ?? 'FAQ | Trans-Nzoia AHP Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Frequently asked questions about the Trans-Nzoia Affordable Housing Programme - eligibility, application, payments, unit allocation, construction timelines and beneficiary rights.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia affordable housing FAQ, AHP Kenya questions, housing levy Kenya, Kitale housing application';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $cmsPage['canonical_url'] ?? Url::to('faq.php');
$pageStyles = ['assets/css/global.css', 'assets/css/pages/faq.css'];
$pageScripts = ['assets/js/global.js', 'assets/js/pages/faq.js'];
$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
    '<meta property="og:title" content="' . $e($pageTitle) . '">',
    '<meta property="og:description" content="' . $e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . $e($canonicalUrl) . '">',
    '<meta property="og:image" content="' . $e(Url::asset($heroImage)) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
];
include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

  <section class="fq-hero" aria-label="FAQ overview">
    <div class="fq-hero-bg" aria-hidden="true">
      <img <?= public_image_attrs($heroImage, $text($hero, 'background_alt', 'Affordable housing programme frequently asked questions in Trans-Nzoia County'), ['loading' => 'eager', 'fetchpriority' => 'high', 'class' => 'fq-hero-image', 'onerror' => "this.style.display='none'"]) ?>>
      <div class="fq-hero-overlay"></div>
      <div class="fq-hero-grid"></div>
    </div>
    <div class="container">
      <div class="fq-hero-body">
        <div class="fq-hero-eyebrow">
          <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
          <?= $e($text($hero, 'eyebrow', 'Frequently Asked Questions')) ?>
        </div>
        <h1 class="fq-hero-title"><?= nl2br($e($heroTitle)) ?></h1>
        <p class="fq-hero-sub"><?= $e($heroSubtitle) ?></p>

        <div class="fq-search-wrap" role="search" aria-label="Search FAQ">
          <label for="faqSearch" class="sr-only">Search frequently asked questions</label>
          <i class="fa-solid fa-magnifying-glass fq-search-icon" aria-hidden="true"></i>
          <input type="search" id="faqSearch" class="fq-search-input" placeholder="<?= $e($text($hero, 'search_placeholder', 'Search questions, e.g. "How do I apply?"')) ?>" autocomplete="off" spellcheck="false">
          <button class="fq-search-clear" id="faqSearchClear" aria-label="Clear search" hidden>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>

        <div class="fq-hero-kpi-strip" role="region" aria-label="FAQ at a glance">
          <div class="fq-kpi-item">
            <span class="fq-kpi-num"><?= $e(format_number((int)$stats['questions'])) ?></span>
            <span class="fq-kpi-lbl"><?= $e($text($hero, 'questions_label', 'Questions Answered')) ?></span>
          </div>
          <div class="fq-kpi-div" aria-hidden="true"></div>
          <div class="fq-kpi-item">
            <span class="fq-kpi-num"><?= $e(format_number((int)$stats['categories'])) ?></span>
            <span class="fq-kpi-lbl"><?= $e($text($hero, 'categories_label', 'Categories')) ?></span>
          </div>
          <div class="fq-kpi-div" aria-hidden="true"></div>
          <div class="fq-kpi-item">
            <span class="fq-kpi-num"><?= $e($text($hero, 'updates_value', 'Monthly')) ?></span>
            <span class="fq-kpi-lbl"><?= $e($text($hero, 'updates_label', 'Content Updates')) ?></span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="fq-popular fade-up" aria-labelledby="popular-heading">
    <div class="container">
      <p class="fq-popular-label" id="popular-heading">
        <i class="fa-solid fa-fire" aria-hidden="true"></i> <?= $e($text($popularContent, 'label', 'Popular Questions')) ?>
      </p>
      <div class="fq-popular-pills" role="list">
        <?php if ($popularQuestions === []): ?>
          <span class="fq-muted"><?= $e($text($popularContent, 'empty_text', 'Popular questions will appear here soon.')) ?></span>
        <?php else: ?>
          <?php foreach ($popularQuestions as $item): ?>
            <button class="fq-pill" data-target="<?= $e(faq_public_dom_id($item)) ?>" role="listitem"><?= $e($item['question']) ?></button>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="fq-main" aria-labelledby="faq-main-heading">
    <div class="container">
      <h2 class="sr-only" id="faq-main-heading">All FAQ Categories</h2>

      <div class="fq-cat-tabs fade-up" role="tablist" aria-label="FAQ categories" id="fqCatTabs">
        <button class="fq-cat-tab is-active" role="tab" aria-selected="true" data-cat="all">
          <i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?= $e($text($libraryContent, 'all_label', 'All')) ?>
        </button>
        <?php foreach ($categories as $category): ?>
          <?php $slug = (string)($category['slug'] ?? 'general'); ?>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="<?= $e($slug) ?>">
            <i class="fa-solid <?= $e(faq_public_icon($category['icon'] ?? null)) ?>" aria-hidden="true"></i> <?= $e($category['name'] ?? 'General') ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="fq-search-noresults" id="fqNoResults" hidden aria-live="polite">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <p><?= $e($text($libraryContent, 'no_results_title', 'No questions match')) ?> <strong id="fqNoResultsQuery"></strong>. <button class="fq-inline-btn" id="fqClearSearch"><?= $e($text($libraryContent, 'clear_label', 'Clear the search')) ?></button>.</p>
      </div>

      <?php if ($groups === []): ?>
        <div class="fq-empty-state fade-up">
          <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
          <h2><?= $e($text($libraryContent, 'empty_title', 'FAQ content is being updated')) ?></h2>
          <p><?= $e($text($libraryContent, 'empty_text', 'Please check again soon or contact the housing desk for support.')) ?></p>
        </div>
      <?php endif; ?>

      <?php foreach ($groups as $group): ?>
        <?php
          $groupSlug = (string)($group['slug'] ?? 'general');
          $items = $group['items'] ?? [];
        ?>
        <div class="fq-group" data-group="<?= $e($groupSlug) ?>" id="group-<?= $e($groupSlug) ?>">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid <?= $e(faq_public_icon($group['icon'] ?? null)) ?>" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title"><?= $e($group['name'] ?? 'General Questions') ?></h3>
              <?php if (trim((string)($group['description'] ?? '')) !== ''): ?>
                <p class="fq-group-sub"><?= $e($group['description']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-<?= $e($groupSlug) ?>">
            <?php foreach ($items as $item): ?>
              <?php
                $domId = faq_public_dom_id($item);
                $panelId = 'ans-' . substr($domId, 2);
              ?>
              <div class="fq-item" data-cat="<?= $e($groupSlug) ?>" id="<?= $e($domId) ?>">
                <button class="fq-trigger" aria-expanded="false" aria-controls="<?= $e($panelId) ?>">
                  <span class="fq-q-text"><?= $e($item['question']) ?></span>
                  <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
                </button>
                <div class="fq-panel" id="<?= $e($panelId) ?>" role="region">
                  <div class="fq-panel-inner">
                    <?= faq_public_answer($item['answer'] ?? '') . PHP_EOL ?>
                    <div class="fq-helpful" data-id="<?= $e((string)($item['id'] ?? $domId)) ?>">
                      <span>Was this helpful?</span>
                      <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up" aria-hidden="true"></i></button>
                      <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down" aria-hidden="true"></i></button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="fq-cta fade-up" aria-labelledby="fq-cta-heading">
    <div class="container">
      <div class="fq-cta-wrap">
        <div class="fq-cta-left">
          <div class="fq-cta-icon"><i class="fa-solid fa-headset" aria-hidden="true"></i></div>
          <div class="fq-cta-body">
            <h2 class="fq-cta-title" id="fq-cta-heading"><?= $e($text($ctaContent, 'title', 'Still Have Questions?')) ?></h2>
            <p class="fq-cta-sub"><?= $e($text($ctaContent, 'subtitle', 'Our county housing team is available Monday to Friday, 8am-5pm. Reach us by phone, email or in person at the Kitale field office.')) ?></p>
            <div class="fq-cta-contacts">
              <a href="tel:<?= $e($phoneHref) ?>" class="fq-cta-contact-item">
                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                <span><?= $e($phone) ?></span>
              </a>
              <a href="mailto:<?= $e($email) ?>" class="fq-cta-contact-item">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                <span><?= $e($email) ?></span>
              </a>
            </div>
            <a href="<?= $e($text($ctaContent, 'contact_button_url', 'contact.php')) ?>" class="fq-cta-btn">
              <i class="fa-solid fa-message" aria-hidden="true"></i> <?= $e($text($ctaContent, 'contact_button_label', 'Send Us a Message')) ?>
            </a>
          </div>
        </div>
        <div class="fq-cta-divider" aria-hidden="true"></div>
        <div class="fq-cta-right">
          <div class="fq-cta-ecitizen-body">
            <div class="fq-cta-ecitizen-badge">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </div>
            <h3 class="fq-cta-ecitizen-title"><?= $e($text($ctaContent, 'apply_title', 'Ready to Apply?')) ?></h3>
            <p class="fq-cta-ecitizen-sub"><?= $e($text($ctaContent, 'apply_subtitle', "Applications are processed entirely through the Government's eCitizen portal - free, secure and available 24/7.")) ?></p>
            <a href="<?= $e($text($ctaContent, 'apply_button_url', 'https://ecitizen.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="fq-ecitizen-btn">
              <?= $e($text($ctaContent, 'apply_button_label', 'Apply via eCitizen')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>


