<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'about';
$aboutPage = CmsLoader::page('about');
$projectStats = Project::stats();
$constituencies = Constituency::withProjectCounts();

$hero = CmsLoader::content($aboutPage, 'about_hero', about_defaults('about_hero'));
$overview = CmsLoader::content($aboutPage, 'programme_overview', about_defaults('programme_overview'));
$pillars = CmsLoader::content($aboutPage, 'core_commitments', about_defaults('core_commitments'));
$history = CmsLoader::content($aboutPage, 'programme_history', about_defaults('programme_history'));
$legal = CmsLoader::content($aboutPage, 'legal_framework', about_defaults('legal_framework'));
$partners = CmsLoader::content($aboutPage, 'partners_teaser', about_defaults('partners_teaser'));
$faqTeaser = CmsLoader::content($aboutPage, 'faq_teaser', about_defaults('faq_teaser'));
$leadership = CmsLoader::content($aboutPage, 'leadership_contact', about_defaults('leadership_contact'));
$applyCta = CmsLoader::content($aboutPage, 'apply_cta', about_defaults('apply_cta'));

$pageTitle = (string)($aboutPage['seo_title'] ?? 'About the Programme | Trans-Nzoia AHP Tracker');
$pageDescription = (string)($aboutPage['seo_description'] ?? 'Learn about the Trans-Nzoia County Affordable Housing Programme - mandate, partners, legal framework and how to apply.');
$pageKeywords = (string)($aboutPage['seo_keywords'] ?? 'Trans-Nzoia affordable housing, AHP Kenya, housing programme, Boma Yangu, county housing policy');
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = (string)($aboutPage['canonical_url'] ?? 'https://housing.transnzoia.go.ke/about.php');
$heroImage = CmsLoader::text($hero, 'background_image', (string)($aboutPage['hero_image'] ?? 'uploads/gallery/maili-tatu-2.jpg'));
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/about.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/about.js',
];
$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
    '<meta property="og:title" content="' . Security::e($pageTitle) . '">',
    '<meta property="og:description" content="' . Security::e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . Security::e($canonicalUrl) . '">',
    '<meta property="og:image" content="' . Security::e($heroImage) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta name="twitter:title" content="' . Security::e($pageTitle) . '">',
    '<meta name="twitter:description" content="' . Security::e($pageDescription) . '">',
    '<meta name="twitter:image" content="' . Security::e($heroImage) . '">',
];
include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

<?php if (CmsLoader::visible($aboutPage, 'about_hero')): ?>
    <section class="ab-hero" aria-label="About the programme">
      <div class="ab-hero-bg" aria-hidden="true">
        <img src="<?= Security::e($heroImage) ?>" alt="<?= Security::e(CmsLoader::text($hero, 'background_alt', 'Affordable housing construction works in Trans-Nzoia County')) ?>" loading="eager" onerror="this.style.display='none'">
        <div class="ab-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">About</span>
        </nav>
        <div class="ab-hero-body">
          <div class="ab-hero-eyebrow">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <?= Security::e(CmsLoader::text($hero, 'eyebrow', 'National Affordable Housing Programme - Trans-Nzoia County')) ?>
          </div>
          <h1 class="ab-hero-title"><?= nl2br(Security::e(CmsLoader::text($hero, 'title', 'Delivering Affordable Homes for Trans-Nzoia County Residents')), false) ?></h1>
          <p class="ab-hero-desc"><?= Security::e(CmsLoader::text($hero, 'subtitle', 'A government-led initiative to provide quality, subsidised housing to low-to-middle-income earners across all five constituencies - tracked transparently for every resident.')) ?></p>
          <div class="ab-hero-ctas">
            <a href="<?= Security::e(CmsLoader::text($hero, 'primary_url', 'projects.php')) ?>" class="ab-btn-lime">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($hero, 'primary_label', 'View All Projects')) ?>
            </a>
            <a href="<?= Security::e(CmsLoader::text($hero, 'secondary_url', 'https://bomayangu.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="ab-btn-outline">
              <?= Security::e(CmsLoader::text($hero, 'secondary_label', 'Apply on Boma Yangu')) ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>
        </div>
        <div class="ab-hero-kpis" aria-label="Programme key figures">
          <?php about_kpi('fa-map-location-dot', count($constituencies), 'Constituencies'); ?>
          <?php about_kpi('fa-building-columns', (int)($projectStats['total_projects'] ?? 0), 'Projects'); ?>
          <?php about_kpi('fa-house-chimney', (int)($projectStats['total_units'] ?? 0), 'Units Planned'); ?>
          <?php about_kpi('fa-calendar-days', CmsLoader::text($hero, 'launch_year', '2024'), CmsLoader::text($hero, 'launch_label', 'Programme Launch')); ?>
        </div>
      </div>
    </section>
<?php endif; ?>

<?php if (CmsLoader::visible($aboutPage, 'programme_overview')): ?>
    <section class="ab-overview fade-up" aria-label="Programme overview">
      <div class="container">
        <div class="ab-overview-inner">
          <div class="ab-overview-copy">
            <div class="ab-section-eyebrow"><?= Security::e(CmsLoader::text($overview, 'eyebrow', 'Programme Overview')) ?></div>
            <h2 class="ab-section-title"><?= Security::e(CmsLoader::text($overview, 'title', 'What Is the Affordable Housing Programme?')) ?></h2>
            <p class="ab-overview-lead"><?= Security::e(CmsLoader::text($overview, 'lead', about_defaults('programme_overview')['lead'])) ?></p>
            <?php about_paragraph($overview, 'body_1'); ?>
            <?php about_paragraph($overview, 'body_2'); ?>
            <a href="<?= Security::e(CmsLoader::text($overview, 'link_url', 'projects.php')) ?>" class="ab-text-link">
              <?= Security::e(CmsLoader::text($overview, 'link_label', 'Explore all active projects')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
          <div class="ab-snapshot">
            <div class="ab-snapshot-header">
              <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
              <?= Security::e(CmsLoader::text($overview, 'snapshot_title', 'Programme at a Glance')) ?>
            </div>
            <ul class="ab-snapshot-list">
              <?php foreach (about_series($overview, 'glance', 6, ['label', 'value', 'url']) as $row): ?>
                <?php if (($row['label'] ?? '') !== '' || ($row['value'] ?? '') !== ''): ?>
              <li>
                <span class="ab-snap-label"><?= Security::e($row['label'] ?? '') ?></span>
                <span class="ab-snap-value">
                  <?php if (!empty($row['url'])): ?>
                    <a href="<?= Security::e($row['url']) ?>" target="_blank" rel="noopener noreferrer"><?= Security::e($row['value'] ?? '') ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px"></i></a>
                  <?php else: ?>
                    <?= Security::e($row['value'] ?? '') ?>
                  <?php endif; ?>
                </span>
              </li>
                <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </section>
<?php endif; ?>

<?php if (CmsLoader::visible($aboutPage, 'core_commitments')): ?>
    <section class="ab-pillars fade-up" aria-label="Programme pillars">
      <div class="container">
        <div class="ab-pillars-header">
          <div class="ab-section-eyebrow ab-section-eyebrow--light"><?= Security::e(CmsLoader::text($pillars, 'eyebrow', 'Our Pillars')) ?></div>
          <h2 class="ab-section-title ab-section-title--light"><?= Security::e(CmsLoader::text($pillars, 'title', 'Built on Four Core Commitments')) ?></h2>
          <p class="ab-section-sub ab-section-sub--light"><?= Security::e(CmsLoader::text($pillars, 'subtitle', 'Every decision, project and process is guided by four foundational principles.')) ?></p>
        </div>
        <div class="ab-pillars-grid">
          <?php foreach (about_series($pillars, 'pillar', 4, ['icon', 'title', 'body']) as $pillar): ?>
            <?php if (($pillar['title'] ?? '') !== ''): ?>
          <div class="ab-pillar-card">
            <div class="ab-pillar-icon-wrap" aria-hidden="true"><i class="fa-solid <?= Security::e(about_icon($pillar['icon'] ?? 'fa-circle-check')) ?>"></i></div>
            <h3 class="ab-pillar-title"><?= Security::e($pillar['title']) ?></h3>
            <p class="ab-pillar-desc"><?= Security::e($pillar['body'] ?? '') ?></p>
          </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <div class="ab-stats-strip" aria-label="Programme statistics">
      <div class="container">
        <div class="ab-stats-inner">
          <?php about_stat((string)count($constituencies), 'Constituencies'); ?>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <?php about_stat((string)(int)($projectStats['total_projects'] ?? 0), 'Projects'); ?>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <?php about_stat((string)(int)($projectStats['total_units'] ?? 0), 'Units Planned'); ?>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <?php about_stat(CmsLoader::text($pillars, 'families_target', '8650'), 'Families to Benefit'); ?>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <?php about_stat(CmsLoader::text($hero, 'launch_year', '2024'), 'Year Launched', true); ?>
        </div>
      </div>
    </div>
<?php endif; ?>

<?php if (CmsLoader::visible($aboutPage, 'programme_history')): ?>
    <section class="ab-timeline fade-up" aria-label="Programme timeline">
      <div class="container">
        <div class="ab-timeline-header">
          <div class="ab-section-eyebrow ab-section-eyebrow--light"><?= Security::e(CmsLoader::text($history, 'eyebrow', 'Programme History')) ?></div>
          <h2 class="ab-section-title ab-section-title--light"><?= Security::e(CmsLoader::text($history, 'title', 'From Announcement to Active Construction')) ?></h2>
          <p class="ab-section-sub ab-section-sub--light"><?= Security::e(CmsLoader::text($history, 'subtitle', 'A chronological record of how affordable housing came to life in Trans-Nzoia County.')) ?></p>
        </div>
        <ol class="ab-tl-track">
          <?php foreach (about_series($history, 'item', 6, ['period', 'icon', 'title', 'body', 'status']) as $item): ?>
            <?php if (($item['title'] ?? '') !== ''): ?>
          <li class="ab-tl-item <?= Security::e(about_timeline_class($item['status'] ?? '')) ?>">
            <span class="ab-tl-year"><?= Security::e($item['period'] ?? '') ?></span>
            <div class="ab-tl-node" aria-hidden="true">
              <div class="ab-tl-dot"><i class="fa-solid <?= Security::e(about_icon($item['icon'] ?? 'fa-circle')) ?>"></i></div>
              <div class="ab-tl-connector"></div>
            </div>
            <div class="ab-tl-content">
              <h3 class="ab-tl-title"><?= Security::e($item['title']) ?></h3>
              <p class="ab-tl-desc"><?= Security::e($item['body'] ?? '') ?></p>
              <span class="ab-tl-badge <?= Security::e(about_timeline_badge_class($item['status'] ?? '')) ?>"><i class="fa-solid <?= Security::e(about_timeline_icon($item['status'] ?? '')) ?>" aria-hidden="true"></i> <?= Security::e($item['status'] ?: 'Upcoming') ?></span>
            </div>
          </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ol>
      </div>
    </section>
<?php endif; ?>

<?php if (CmsLoader::visible($aboutPage, 'legal_framework')): ?>
    <section class="ab-legal fade-up" aria-label="Legal and regulatory framework">
      <div class="container">
        <div class="ab-legal-header">
          <div class="ab-section-eyebrow"><?= Security::e(CmsLoader::text($legal, 'eyebrow', 'Legal Framework')) ?></div>
          <h2 class="ab-section-title"><?= Security::e(CmsLoader::text($legal, 'title', 'Backed by Law & Policy')) ?></h2>
          <p class="ab-section-sub"><?= Security::e(CmsLoader::text($legal, 'subtitle', 'A robust legal and regulatory framework ensures compliance, accountability and environmental responsibility across every project site.')) ?></p>
        </div>
        <div class="ab-legal-grid">
          <?php foreach (about_series($legal, 'card', 6, ['icon', 'title', 'body', 'link_label', 'url']) as $card): ?>
            <?php if (($card['title'] ?? '') !== ''): ?>
          <div class="ab-legal-card">
            <div class="ab-legal-icon" aria-hidden="true"><i class="fa-solid <?= Security::e(about_icon($card['icon'] ?? 'fa-book')) ?>"></i></div>
            <h3 class="ab-legal-title"><?= Security::e($card['title']) ?></h3>
            <p class="ab-legal-desc"><?= Security::e($card['body'] ?? '') ?></p>
            <?php if (!empty($card['url'])): ?>
            <a href="<?= Security::e($card['url']) ?>" target="_blank" rel="noopener noreferrer" class="ab-legal-link"><?= Security::e($card['link_label'] ?: 'Read more') ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            <?php endif; ?>
          </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
<?php endif; ?>

<?php if (CmsLoader::visible($aboutPage, 'partners_teaser')): ?>
    <section class="ab-partners fade-up" aria-label="Implementing partners">
      <div class="container">
        <div class="ab-partners-header">
          <div class="ab-section-eyebrow ab-section-eyebrow--light"><?= Security::e(CmsLoader::text($partners, 'eyebrow', 'Partners')) ?></div>
          <h2 class="ab-section-title ab-section-title--light"><?= Security::e(CmsLoader::text($partners, 'title', 'Implementing Partners & Stakeholders')) ?></h2>
          <p class="ab-section-sub ab-section-sub--light"><?= Security::e(CmsLoader::text($partners, 'subtitle', 'Delivered through collaboration between national and county government agencies, financial institutions and regulatory bodies.')) ?></p>
        </div>
        <div class="ab-partners-grid">
          <?php foreach (about_partner_rows((int)($partners['display_count'] ?? 5)) as $partner): ?>
          <div class="ab-partner-card">
            <div class="ab-partner-icon" aria-hidden="true"><i class="fa-solid <?= Security::e(about_icon($partner['icon'] ?? 'fa-handshake')) ?>"></i></div>
            <h3 class="ab-partner-name"><?= Security::e($partner['name']) ?></h3>
            <p class="ab-partner-role"><?= Security::e($partner['description']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
<?php endif; ?>

<?php if (CmsLoader::visible($aboutPage, 'faq_teaser')): ?>
    <section class="ab-faq fade-up" aria-label="Frequently asked questions">
      <div class="container">
        <div class="ab-faq-wrap">
          <div class="ab-faq-header">
            <div class="ab-section-eyebrow"><?= Security::e(CmsLoader::text($faqTeaser, 'eyebrow', 'Common Questions')) ?></div>
            <h2 class="ab-section-title"><?= Security::e(CmsLoader::text($faqTeaser, 'title', 'Frequently Asked Questions')) ?></h2>
            <p class="ab-section-sub"><?= Security::e(CmsLoader::text($faqTeaser, 'subtitle', 'Quick answers to the most common questions about the programme.')) ?></p>
          </div>
          <div class="ab-faq-list">
            <?php foreach (about_faq_rows((int)($faqTeaser['display_count'] ?? 4)) as $index => $faq): ?>
            <div class="ab-faq-item">
              <button class="ab-faq-trigger" aria-expanded="false" aria-controls="faq-<?= (int)$index + 1 ?>" id="faq-trigger-<?= (int)$index + 1 ?>">
                <span><?= Security::e($faq['question']) ?></span>
                <i class="fa-solid fa-chevron-down ab-faq-chevron" aria-hidden="true"></i>
              </button>
              <div class="ab-faq-body" id="faq-<?= (int)$index + 1 ?>" role="region" aria-labelledby="faq-trigger-<?= (int)$index + 1 ?>">
                <p><?= Security::e($faq['answer']) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="ab-faq-footer">
            <a href="<?= Security::e(CmsLoader::text($faqTeaser, 'link_url', 'faq.php')) ?>" class="ab-text-link">
              <?= Security::e(CmsLoader::text($faqTeaser, 'link_label', 'Read all frequently asked questions')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>
    </section>
<?php endif; ?>

<?php if (CmsLoader::visible($aboutPage, 'leadership_contact')): ?>
    <section class="ab-leadership fade-up" aria-label="Programme leadership and contact">
      <div class="container">
        <div class="ab-leadership-inner">
          <div class="ab-leadership-col">
            <div class="ab-section-eyebrow"><?= Security::e(CmsLoader::text($leadership, 'eyebrow', 'National Programme Leadership')) ?></div>
            <h2 class="ab-section-title"><?= Security::e(CmsLoader::text($leadership, 'title', 'Programme Leadership')) ?></h2>
            <p class="ab-leadership-sub"><?= Security::e(CmsLoader::text($leadership, 'subtitle', about_defaults('leadership_contact')['subtitle'])) ?></p>
            <div class="ab-leaders-grid">
              <?php foreach (about_leadership_rows() as $leader): ?>
              <div class="ab-leader-card">
                <div class="ab-leader-avatar" aria-hidden="true"><i class="fa-solid <?= Security::e(about_icon($leader['icon'] ?? 'fa-user-tie')) ?>"></i></div>
                <div class="ab-leader-info">
                  <span class="ab-leader-role"><?= Security::e($leader['title']) ?></span>
                  <h3 class="ab-leader-name"><?= Security::e($leader['name']) ?></h3>
                  <span class="ab-leader-dept"><?= Security::e($leader['organisation']) ?></span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <a href="<?= Security::e(CmsLoader::text($leadership, 'link_url', 'leadership.php')) ?>" class="ab-text-link" style="margin-top:var(--space-6);display:inline-flex;align-items:center;gap:var(--space-2)">
              <?= Security::e(CmsLoader::text($leadership, 'link_label', 'Meet the full leadership team')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
          <div class="ab-contact-col">
            <div class="ab-contact-card">
              <div class="ab-contact-header">
                <div class="ab-contact-header-icon" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></div>
                <h3><?= Security::e(CmsLoader::text($leadership, 'contact_title', 'Get In Touch')) ?></h3>
                <p><?= Security::e(CmsLoader::text($leadership, 'contact_intro', 'Have a question about the programme or your application? Reach the housing department directly.')) ?></p>
              </div>
              <ul class="ab-contact-list">
                <?php about_contact_item('fa-location-dot', 'Physical Address', (string)(CmsLoader::setting('contact_address') ?? 'Trans-Nzoia County Government Offices, Kitale')); ?>
                <?php about_contact_item('fa-phone', 'Phone', (string)(CmsLoader::setting('contact_phone') ?? '+254 53 000 0000'), 'tel:'); ?>
                <?php about_contact_item('fa-envelope', 'Email', (string)(CmsLoader::setting('contact_email') ?? 'housing@transnzoia.go.ke'), 'mailto:'); ?>
                <?php about_contact_item('fa-clock', 'Office Hours', CmsLoader::text($leadership, 'office_hours', 'Monday - Friday, 8:00 AM - 5:00 PM')); ?>
              </ul>
              <a href="<?= Security::e(CmsLoader::text($leadership, 'contact_button_url', 'contact.php')) ?>" class="ab-contact-cta">
                <?= Security::e(CmsLoader::text($leadership, 'contact_button_label', 'Send a Message')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
<?php endif; ?>

</main>

<?php if (CmsLoader::visible($aboutPage, 'apply_cta')): ?>
  <div class="ab-apply-banner" role="complementary" aria-label="Apply for affordable housing">
    <div class="container">
      <div class="ab-apply-inner">
        <div class="ab-apply-icon" aria-hidden="true"><i class="fa-solid fa-house-circle-check"></i></div>
        <div class="ab-apply-copy">
          <h3 class="ab-apply-title"><?= Security::e(CmsLoader::text($applyCta, 'title', 'Ready to Apply for Affordable Housing in Trans-Nzoia?')) ?></h3>
          <p class="ab-apply-sub"><?= Security::e(CmsLoader::text($applyCta, 'subtitle', 'Register on the national Boma Yangu portal, save your monthly deposit and select Trans-Nzoia as your allocation preference to join the housing ballot.')) ?></p>
        </div>
        <div class="ab-apply-ctas">
          <a href="<?= Security::e(CmsLoader::text($applyCta, 'primary_url', 'https://bomayangu.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="ab-btn-lime">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($applyCta, 'primary_label', 'Apply on Boma Yangu')) ?>
          </a>
          <a href="<?= Security::e(CmsLoader::text($applyCta, 'secondary_url', 'projects.php')) ?>" class="ab-btn-outline-dark">
            <?= Security::e(CmsLoader::text($applyCta, 'secondary_label', 'View All Projects')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>

<?php
function about_defaults(string $section): array
{
    $defaults = [
        'about_hero' => [
            'background_image' => 'uploads/gallery/maili-tatu-2.jpg',
            'background_alt' => 'Affordable housing construction works in Trans-Nzoia County',
            'eyebrow' => 'National Affordable Housing Programme - Trans-Nzoia County',
            'title' => 'Delivering Affordable Homes for Trans-Nzoia County Residents',
            'subtitle' => 'A government-led initiative to provide quality, subsidised housing to low-to-middle-income earners across all five constituencies - tracked transparently for every resident.',
            'primary_label' => 'View All Projects',
            'primary_url' => 'projects.php',
            'secondary_label' => 'Apply on Boma Yangu',
            'secondary_url' => 'https://bomayangu.go.ke',
            'launch_year' => '2024',
            'launch_label' => 'Programme Launch',
        ],
        'programme_overview' => [
            'lead' => "The National Affordable Housing Programme (AHP) is a flagship initiative of the Kenya Kwanza Administration, aimed at bridging the nation's housing deficit by delivering quality, affordable units to low-to-middle-income earners.",
            'body_1' => 'In Trans-Nzoia County, the programme is implemented jointly by the State Department of Housing & Urban Development and the Trans-Nzoia County Government - Department of Land, Housing & Physical Planning. Projects span all five constituencies.',
            'body_2' => 'Units are allocated exclusively through the Boma Yangu Portal, using a transparent ballot to ensure equitable access for all registered applicants across Trans-Nzoia County.',
        ],
        'core_commitments' => [],
        'programme_history' => [],
        'legal_framework' => [],
        'partners_teaser' => [],
        'faq_teaser' => [],
        'leadership_contact' => [
            'subtitle' => 'This is a national government initiative delivered through the State Department of Housing & Urban Development. The Trans-Nzoia field office coordinates local programme delivery and public support.',
        ],
        'apply_cta' => [],
    ];

    return $defaults[$section] ?? [];
}

function about_kpi(string $icon, string|int|float $value, string $label): void
{
    ?>
    <div class="ab-kpi">
      <i class="fa-solid <?= Security::e($icon) ?> ab-kpi-icon" aria-hidden="true"></i>
      <span class="ab-kpi-val"><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></span>
      <span class="ab-kpi-lbl"><?= Security::e($label) ?></span>
    </div>
    <?php
}

function about_stat(string $value, string $label, bool $static = false): void
{
    $numeric = preg_replace('/[^0-9.]/', '', $value);
    ?>
    <div class="ab-stat-item">
      <span class="ab-stat-val<?= $static ? ' ab-stat-static' : '' ?>"<?= !$static && $numeric !== '' ? ' data-target="' . Security::e($numeric) . '"' : '' ?>><?= Security::e($static ? $value : '0') ?></span>
      <span class="ab-stat-lbl"><?= Security::e($label) ?></span>
    </div>
    <?php
}

function about_paragraph(array $content, string $key): void
{
    $text = trim((string)($content[$key] ?? ''));
    if ($text === '') {
        return;
    }
    echo '<p class="ab-overview-body">' . Security::e($text) . '</p>';
}

function about_series(array $content, string $prefix, int $count, array $keys): array
{
    $rows = [];
    for ($i = 1; $i <= $count; $i++) {
        $row = [];
        foreach ($keys as $key) {
            $row[$key] = trim((string)($content[$prefix . '_' . $i . '_' . $key] ?? ''));
        }
        $rows[] = $row;
    }

    return $rows;
}

function about_icon(string $icon): string
{
    $icon = trim($icon);
    if ($icon === '') {
        return 'fa-circle-check';
    }
    return preg_match('/^fa-[a-z0-9-]+$/', $icon) ? $icon : 'fa-circle-check';
}

function about_timeline_class(string $status): string
{
    $status = strtolower($status);
    if (str_contains($status, 'progress')) {
        return 'ab-tl-current';
    }
    if (str_contains($status, 'complete') || str_contains($status, 'done')) {
        return 'ab-tl-done';
    }
    return '';
}

function about_timeline_badge_class(string $status): string
{
    $status = strtolower($status);
    if (str_contains($status, 'progress')) {
        return 'ab-tl-badge--current';
    }
    if (str_contains($status, 'complete') || str_contains($status, 'done')) {
        return 'ab-tl-badge--done';
    }
    return '';
}

function about_timeline_icon(string $status): string
{
    $status = strtolower($status);
    if (str_contains($status, 'progress')) {
        return 'fa-rotate';
    }
    if (str_contains($status, 'complete') || str_contains($status, 'done')) {
        return 'fa-check';
    }
    return 'fa-clock';
}

function about_partner_rows(int $limit): array
{
    try {
        $rows = Database::fetchAll(
            'SELECT organisation, role, description, category
             FROM stakeholders
             WHERE is_visible = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT ' . max(1, $limit)
        );
    } catch (Throwable $e) {
        $rows = [];
    }

    if ($rows) {
        return array_map(static function (array $row): array {
            return [
                'icon' => match (strtolower((string)($row['category'] ?? ''))) {
                    'government' => 'fa-building-columns',
                    'finance', 'financial' => 'fa-hand-holding-dollar',
                    'regulator', 'regulatory' => 'fa-helmet-safety',
                    default => 'fa-handshake',
                },
                'name' => (string)($row['organisation'] ?? ''),
                'description' => (string)($row['description'] ?: ($row['role'] ?? 'Programme partner')),
            ];
        }, $rows);
    }

    return [
        ['icon' => 'fa-building-columns', 'name' => 'State Dept. of Housing', 'description' => 'Lead implementing agency for project oversight, contractor management and national housing policy direction.'],
        ['icon' => 'fa-landmark-flag', 'name' => 'Trans-Nzoia County Government', 'description' => 'Land allocation, community engagement, site coordination and county-level programme support.'],
        ['icon' => 'fa-hand-holding-dollar', 'name' => 'Kenya Mortgage Refinance Company', 'description' => 'Long-term mortgage financing that supports affordable repayments for eligible beneficiaries.'],
        ['icon' => 'fa-house-circle-check', 'name' => 'Boma Yangu Portal', 'description' => 'National beneficiary registration, savings tracking and transparent unit allocation ballot.'],
        ['icon' => 'fa-helmet-safety', 'name' => 'National Construction Authority', 'description' => 'Contractor registration, site inspection compliance and occupational safety oversight.'],
    ];
}

function about_faq_rows(int $limit): array
{
    try {
        $rows = Database::fetchAll(
            'SELECT question, answer
             FROM faq_items
             WHERE is_visible = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT ' . max(1, $limit)
        );
    } catch (Throwable $e) {
        $rows = [];
    }

    return $rows ?: [
        ['question' => 'What is the Affordable Housing Programme?', 'answer' => 'The Affordable Housing Programme is a government initiative to deliver subsidised homes for low-to-middle-income earners.'],
        ['question' => 'Who qualifies to apply for a unit?', 'answer' => 'Eligible Kenyan citizens can apply through the Boma Yangu portal and follow the national allocation process.'],
        ['question' => 'How are units allocated?', 'answer' => 'Units are allocated through the official Boma Yangu process using transparent eligibility and ballot controls.'],
        ['question' => 'Where can I apply?', 'answer' => 'Applications are submitted through the national Boma Yangu portal.'],
    ];
}

function about_leadership_rows(): array
{
    try {
        $rows = Database::fetchAll(
            'SELECT name, title, organisation
             FROM leadership_profiles
             WHERE is_visible = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT 2'
        );
    } catch (Throwable $e) {
        $rows = [];
    }

    if ($rows) {
        return array_map(static fn (array $row): array => [
            'icon' => 'fa-user-tie',
            'name' => (string)($row['name'] ?? ''),
            'title' => (string)($row['title'] ?? 'Programme Leadership'),
            'organisation' => (string)($row['organisation'] ?? ''),
        ], $rows);
    }

    return [
        ['icon' => 'fa-user-tie', 'title' => 'County Director', 'name' => 'Moses Owuor', 'organisation' => 'State Dept. of Housing & Urban Development - Trans-Nzoia Representative'],
        ['icon' => 'fa-building-columns', 'title' => 'Lead National Agency', 'name' => 'State Dept. of Housing & Urban Development', 'organisation' => 'Ministry of Lands, Housing & Urban Development - National Government'],
    ];
}

function about_contact_item(string $icon, string $label, string $value, string $scheme = ''): void
{
    $value = trim($value);
    if ($value === '') {
        return;
    }

    $href = '';
    if ($scheme === 'tel:') {
        $href = 'tel:' . preg_replace('/[^0-9+]/', '', $value);
    } elseif ($scheme === 'mailto:') {
        $href = 'mailto:' . $value;
    }
    ?>
    <li>
      <div class="ab-contact-icon" aria-hidden="true"><i class="fa-solid <?= Security::e($icon) ?>"></i></div>
      <div>
        <strong><?= Security::e($label) ?></strong>
        <span>
          <?php if ($href !== ''): ?>
            <a href="<?= Security::e($href) ?>"><?= Security::e($value) ?></a>
          <?php else: ?>
            <?= nl2br(Security::e($value), false) ?>
          <?php endif; ?>
        </span>
      </div>
    </li>
    <?php
}
