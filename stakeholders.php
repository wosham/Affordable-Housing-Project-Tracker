<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'stakeholders';

$cmsPage = CmsLoader::page('stakeholders');
$hero = CmsLoader::content($cmsPage, 'stakeholders_hero', [
    'background_image' => 'uploads/heroes/hero-main.jpg',
    'background_alt' => 'Affordable housing programme site visit in Trans-Nzoia County',
    'eyebrow' => 'Programme Ecosystem',
    'title' => 'Everyone Who Makes It Happen',
    'subtitle' => 'Explore the institutions, partners, community channels and oversight bodies supporting the Affordable Housing Programme in Trans-Nzoia County.',
    'scroll_label' => 'Explore the Ecosystem',
]);
$ecosystem = CmsLoader::content($cmsPage, 'stakeholders_ecosystem', [
    'eyebrow' => 'Ecosystem Overview',
    'title' => 'The Programme Web',
    'subtitle' => 'Select any stakeholder category to see its public role and active partners.',
    'hub_label' => 'Trans-Nzoia AHP',
    'empty_text' => 'Stakeholder groups will appear here once published.',
]);
$pillars = CmsLoader::content($cmsPage, 'stakeholders_pillars', [
    'eyebrow' => 'Stakeholder Groups',
    'title' => 'Programme Delivery Pillars',
    'subtitle' => 'Every stakeholder belongs to a public accountability group with clear responsibilities.',
    'empty_text' => 'No stakeholder groups have been published yet.',
]);
$mandates = CmsLoader::content($cmsPage, 'stakeholders_mandates', [
    'eyebrow' => 'Mandates & Accountabilities',
    'title' => 'Who Does What?',
    'subtitle' => 'Open each group to understand its mandate, responsibilities and reporting lines.',
]);
$milestoneCopy = CmsLoader::content($cmsPage, 'stakeholders_milestones', [
    'eyebrow' => 'Programme Journey',
    'title' => 'Stakeholder Engagement Milestones',
    'subtitle' => 'A public timeline of stakeholder engagement and programme coordination.',
    'empty_text' => 'Stakeholder milestones will appear here once published.',
]);
$voiceCopy = CmsLoader::content($cmsPage, 'stakeholders_voices', [
    'eyebrow' => 'Community Voice',
    'title' => 'The People Behind the Programme',
    'subtitle' => 'Published community and beneficiary voices appear here.',
    'empty_text' => 'Community voices will appear here once published.',
]);
$partnerCopy = CmsLoader::content($cmsPage, 'stakeholders_formal_partners', [
    'eyebrow' => 'Formal Partnerships',
    'title' => 'Institutional Partners',
    'subtitle' => 'Published formal partners and public institutions supporting the programme.',
    'empty_text' => 'Formal partners will appear here once published.',
]);

$groups = StakeholderGroup::published();
$stakeholders = Stakeholder::ordered(['status' => 'published', 'featured_on_stakeholders' => 1]);
$milestones = StakeholderMilestone::ordered(['status' => 'published']);
$voices = StakeholderTestimonial::ordered(['status' => 'published']);
$partners = Stakeholder::formalPartners(18);
$stats = Stakeholder::stats();

$stakeholdersByGroup = [];
foreach ($stakeholders as $stakeholder) {
    $stakeholdersByGroup[(int)($stakeholder['group_id'] ?? 0)][] = $stakeholder;
}

$pageTitle = $cmsPage['seo_title'] ?? 'Programme Stakeholders | Trans-Nzoia AHP Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Explore the public stakeholder ecosystem behind the Trans-Nzoia Affordable Housing Programme.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia affordable housing stakeholders, AHP partners, housing programme Kenya';
$pageAuthor = 'Trans-Nzoia County Government';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $cmsPage['canonical_url'] ?? Url::to('stakeholders.php');
$heroImage = st_text($hero, 'background_image', trim((string)($cmsPage['hero_image'] ?? '')) ?: 'uploads/heroes/hero-main.jpg');
$heroAlt = st_text($hero, 'background_alt', 'Affordable housing programme site visit in Trans-Nzoia County');
$ogImage = $heroImage;
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/stakeholders.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/stakeholders.js',
];
$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
];

function st_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function st_text(array $content, string $key, string $fallback = ''): string
{
    return CmsLoader::text($content, $key, $fallback);
}

function st_lines(array $value): array
{
    return array_values(array_filter(array_map(static fn ($item): string => trim((string)$item), $value)));
}

function st_icon(?string $icon, string $fallback = 'fa-handshake'): string
{
    $icon = trim((string)$icon);
    return preg_match('/^fa[-a-z0-9 ]+$/i', $icon) ? $icon : $fallback;
}

function st_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    return Url::to($url);
}

function st_kpi(array $hero, string $valueKey, string $labelKey, string $fallbackValue, string $fallbackLabel): array
{
    return [
        'value' => st_text($hero, $valueKey, $fallbackValue),
        'label' => st_text($hero, $labelKey, $fallbackLabel),
    ];
}

$kpis = [
    st_kpi($hero, 'kpi_1_value', 'kpi_1_label', (string)count($groups), 'Stakeholder Groups'),
    st_kpi($hero, 'kpi_2_value', 'kpi_2_label', (string)($stats['stakeholder_page'] ?? count($stakeholders)), 'Public Partners'),
    st_kpi($hero, 'kpi_3_value', 'kpi_3_label', (string)($stats['formal_partners'] ?? count($partners)), 'Formal Partners'),
    st_kpi($hero, 'kpi_4_value', 'kpi_4_label', (string)count($milestones), 'Milestones'),
];

$pagePayload = [
    'groups' => array_map(static function (array $group) use ($stakeholdersByGroup): array {
        $groupStakeholders = array_slice($stakeholdersByGroup[(int)$group['id']] ?? [], 0, 8);
        return [
            'id' => (int)$group['id'],
            'slug' => (string)$group['slug'],
            'name' => (string)$group['name'],
            'summary' => (string)($group['summary'] ?? ''),
            'description' => (string)($group['description'] ?? ''),
            'icon' => st_icon($group['icon'] ?? '', 'fa-layer-group'),
            'count_label' => (string)($group['count_label'] ?: format_number($group['stakeholder_count'] ?? 0) . ' records'),
            'tags' => StakeholderGroup::tags($group),
            'entities' => array_map(static fn (array $item): array => [
                'label' => (string)$item['organisation'],
                'role' => (string)($item['role'] ?? ''),
            ], $groupStakeholders),
        ];
    }, $groups),
];

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>

<main id="main-content">
  <section class="sk-hero" aria-label="Programme stakeholder ecosystem overview">
    <div class="sk-hero-bg" aria-hidden="true">
      <img <?= public_image_attrs($heroImage, $heroAlt, ['loading' => 'eager', 'fetchpriority' => 'high', 'onerror' => "this.style.display='none'"]) ?>>
      <div class="sk-hero-overlay"></div>
      <div class="sk-hero-grid-overlay"></div>
    </div>
    <div class="container">
      <div class="sk-hero-body">
        <div class="sk-hero-eyebrow">
          <i class="fa-solid fa-network-wired" aria-hidden="true"></i>
          <?= st_e(st_text($hero, 'eyebrow', 'Programme Ecosystem')) ?>
        </div>
        <h1 class="sk-hero-title"><?= nl2br(st_e(st_text($hero, 'title', 'Everyone Who Makes It Happen'))) ?></h1>
        <p class="sk-hero-sub"><?= st_e(st_text($hero, 'subtitle')) ?></p>
        <div class="sk-hero-kpi-strip" role="region" aria-label="Programme at a glance">
<?php foreach ($kpis as $index => $kpi): ?>
          <?php if ($index > 0): ?><div class="sk-hero-kpi-div" aria-hidden="true"></div><?php endif; ?>
          <div class="sk-hero-kpi-item">
            <span class="sk-hero-kpi-num"><?= st_e($kpi['value']) ?></span>
            <span class="sk-hero-kpi-lbl"><?= st_e($kpi['label']) ?></span>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="sk-hero-scroll-hint" aria-hidden="true">
      <span><?= st_e(st_text($hero, 'scroll_label', 'Explore the ecosystem')) ?></span>
      <i class="fa-solid fa-chevron-down"></i>
    </div>
  </section>

  <section class="sk-ecosystem" aria-labelledby="ecosystem-heading">
    <div class="container">
      <div class="sk-section-header fade-up">
        <div class="sk-eyebrow"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> <?= st_e(st_text($ecosystem, 'eyebrow', 'Ecosystem Overview')) ?></div>
        <h2 class="sk-section-title" id="ecosystem-heading"><?= st_e(st_text($ecosystem, 'title', 'The Programme Web')) ?></h2>
        <p class="sk-section-sub"><?= st_e(st_text($ecosystem, 'subtitle')) ?></p>
      </div>

<?php if ($groups): ?>
      <div class="sk-map-wrap fade-up">
        <div class="sk-map" id="stakeholderMap" role="region" aria-label="Interactive stakeholder ecosystem diagram">
          <div class="sk-map-hub">
            <div class="sk-map-hub-ring sk-map-hub-ring--1" aria-hidden="true"></div>
            <div class="sk-map-hub-ring sk-map-hub-ring--2" aria-hidden="true"></div>
            <div class="sk-map-hub-inner" aria-hidden="true"><i class="fa-solid fa-house-chimney"></i></div>
            <span class="sk-map-hub-label"><?= nl2br(st_e(st_text($ecosystem, 'hub_label', 'Trans-Nzoia AHP'))) ?></span>
          </div>
          <svg class="sk-map-connectors" aria-hidden="true" viewBox="0 0 600 600" preserveAspectRatio="xMidYMid meet">
<?php foreach ($groups as $index => $group): ?>
            <line class="sk-conn-line" data-cat="<?= st_e($group['slug']) ?>" x1="300" y1="300" x2="<?= st_e((string)(300 + 200 * sin(deg2rad($index * (360 / max(1, count($groups))))))) ?>" y2="<?= st_e((string)(300 - 200 * cos(deg2rad($index * (360 / max(1, count($groups))))))) ?>"></line>
<?php endforeach; ?>
          </svg>
<?php foreach ($groups as $index => $group): ?>
          <button class="sk-map-node" data-cat="<?= st_e($group['slug']) ?>" style="--angle:<?= st_e((string)($index * (360 / max(1, count($groups))))) ?>deg" aria-label="<?= st_e($group['name']) ?>">
            <div class="sk-map-node-icon">
              <i class="fa-solid <?= st_e(st_icon($group['icon'] ?? '', 'fa-layer-group')) ?>" aria-hidden="true"></i>
            </div>
            <span class="sk-map-node-label"><?= st_e($group['name']) ?></span>
            <span class="sk-map-node-count"><?= st_e($group['count_label'] ?: format_number($group['stakeholder_count'] ?? 0) . ' records') ?></span>
          </button>
<?php endforeach; ?>
        </div>
        <div class="sk-map-detail" id="mapDetail" aria-live="polite" aria-atomic="true">
          <div class="sk-map-detail-inner" id="mapDetailInner">
            <div class="sk-map-detail-placeholder">
              <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i>
              <p><?= st_e(st_text($ecosystem, 'empty_text', 'Select a stakeholder group to view details.')) ?></p>
            </div>
          </div>
        </div>
      </div>
<?php else: ?>
      <div class="sk-empty fade-up"><?= st_e(st_text($ecosystem, 'empty_text', 'Stakeholder groups will appear here once published.')) ?></div>
<?php endif; ?>
    </div>
  </section>

  <section class="sk-categories" id="stakeholder-groups" aria-labelledby="categories-heading">
    <div class="container">
      <div class="sk-section-header fade-up">
        <div class="sk-eyebrow"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?= st_e(st_text($pillars, 'eyebrow', 'Stakeholder Groups')) ?></div>
        <h2 class="sk-section-title" id="categories-heading"><?= st_e(st_text($pillars, 'title', 'Programme Delivery Pillars')) ?></h2>
        <p class="sk-section-sub"><?= st_e(st_text($pillars, 'subtitle')) ?></p>
      </div>

<?php if ($groups): ?>
      <div class="sk-cat-grid" id="catGrid">
<?php foreach ($groups as $group): ?>
        <?php $groupStakeholders = array_slice($stakeholdersByGroup[(int)$group['id']] ?? [], 0, 8); ?>
        <article class="sk-cat-card fade-up" data-cat="<?= st_e($group['slug']) ?>">
          <div class="sk-cat-card-front">
            <div class="sk-cat-icon">
              <i class="fa-solid <?= st_e(st_icon($group['icon'] ?? '', 'fa-layer-group')) ?>" aria-hidden="true"></i>
            </div>
            <div class="sk-cat-count"><?= st_e($group['count_label'] ?: format_number($group['stakeholder_count'] ?? 0) . ' records') ?></div>
            <h3 class="sk-cat-name"><?= st_e($group['name']) ?></h3>
            <p class="sk-cat-desc"><?= st_e($group['description'] ?: $group['summary']) ?></p>
            <div class="sk-cat-tags">
<?php foreach (StakeholderGroup::tags($group) as $tag): ?>
              <span class="sk-cat-tag"><?= st_e($tag) ?></span>
<?php endforeach; ?>
            </div>
            <button class="sk-cat-flip-btn" type="button" aria-label="See records in <?= st_e($group['name']) ?>">
              <?= st_e($group['cta_label'] ?: 'See records') ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
          </div>
          <div class="sk-cat-card-back">
            <h4 class="sk-cat-back-title"><?= st_e($group['name']) ?></h4>
<?php if ($groupStakeholders): ?>
            <ul class="sk-cat-entities">
<?php foreach ($groupStakeholders as $item): ?>
              <li><i class="fa-solid fa-circle-dot" aria-hidden="true"></i><span><?= st_e($item['organisation']) ?><?php if (!empty($item['role'])): ?> <small><?= st_e($item['role']) ?></small><?php endif; ?></span></li>
<?php endforeach; ?>
            </ul>
<?php else: ?>
            <p class="sk-card-empty">No public organisations in this group yet.</p>
<?php endif; ?>
            <?php $ctaUrl = st_url((string)($group['cta_url'] ?? '')); ?>
            <?php if ($ctaUrl !== ''): ?>
            <a href="<?= st_e($ctaUrl) ?>" class="sk-cat-back-link">
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> <?= st_e($group['cta_label'] ?: 'Open link') ?>
            </a>
            <?php endif; ?>
            <button class="sk-cat-flip-back-btn" type="button" aria-label="Go back to <?= st_e($group['name']) ?> overview">
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
            </button>
          </div>
        </article>
<?php endforeach; ?>
      </div>
<?php else: ?>
      <div class="sk-empty fade-up"><?= st_e(st_text($pillars, 'empty_text', 'No stakeholder groups have been published yet.')) ?></div>
<?php endif; ?>
    </div>
  </section>

  <section class="sk-roles" aria-labelledby="roles-heading">
    <div class="sk-roles-bg" aria-hidden="true"></div>
    <div class="container">
      <div class="sk-section-header sk-section-header--light fade-up">
        <div class="sk-eyebrow sk-eyebrow--light"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> <?= st_e(st_text($mandates, 'eyebrow', 'Mandates & Accountabilities')) ?></div>
        <h2 class="sk-section-title sk-section-title--light" id="roles-heading"><?= st_e(st_text($mandates, 'title', 'Who Does What?')) ?></h2>
        <p class="sk-section-sub sk-section-sub--light"><?= st_e(st_text($mandates, 'subtitle')) ?></p>
      </div>
      <div class="sk-accordion">
<?php foreach ($groups as $index => $group): ?>
        <?php $panelId = 'stakeholder-panel-' . (int)$group['id']; ?>
        <article class="sk-acc-item fade-up">
          <button class="sk-acc-trigger" type="button" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="<?= st_e($panelId) ?>">
            <span class="sk-acc-trigger-left">
              <span class="sk-acc-icon"><i class="fa-solid <?= st_e(st_icon($group['icon'] ?? '', 'fa-layer-group')) ?>" aria-hidden="true"></i></span>
              <span><span class="sk-acc-name"><?= st_e($group['name']) ?></span><span class="sk-acc-sub"><?= st_e($group['summary']) ?></span></span>
            </span>
            <i class="fa-solid fa-chevron-down sk-acc-chevron" aria-hidden="true"></i>
          </button>
          <div class="sk-acc-panel" id="<?= st_e($panelId) ?>" <?= $index === 0 ? '' : 'hidden' ?>>
            <div class="sk-acc-panel-inner">
              <div class="sk-acc-row">
                <div class="sk-acc-col">
                  <h3 class="sk-acc-col-title"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Mandate</h3>
                  <p><?= st_e($group['legal_basis'] ?: $group['description']) ?></p>
                </div>
                <div class="sk-acc-col">
                  <h3 class="sk-acc-col-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Responsibilities</h3>
                  <ul class="sk-acc-list">
<?php foreach (StakeholderGroup::responsibilities($group) as $responsibility): ?>
                    <li><?= st_e($responsibility) ?></li>
<?php endforeach; ?>
                  </ul>
                </div>
                <div class="sk-acc-col">
                  <h3 class="sk-acc-col-title"><i class="fa-solid fa-route" aria-hidden="true"></i> Reporting</h3>
                  <p><?= st_e($group['reporting_lines'] ?: 'Reporting lines will be published after confirmation.') ?></p>
                </div>
              </div>
            </div>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="sk-timeline" aria-labelledby="timeline-heading">
    <div class="container">
      <div class="sk-section-header fade-up">
        <div class="sk-eyebrow"><i class="fa-solid fa-timeline" aria-hidden="true"></i> <?= st_e(st_text($milestoneCopy, 'eyebrow', 'Programme Journey')) ?></div>
        <h2 class="sk-section-title" id="timeline-heading"><?= nl2br(st_e(st_text($milestoneCopy, 'title', 'Stakeholder Engagement Milestones'))) ?></h2>
        <p class="sk-section-sub"><?= st_e(st_text($milestoneCopy, 'subtitle')) ?></p>
      </div>
<?php if ($milestones): ?>
      <div class="sk-timeline-nav fade-up">
        <button class="sk-tl-arrow" id="tlPrev" aria-label="Scroll to previous milestones"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <div class="sk-timeline-scroll-wrap" tabindex="0">
          <div class="sk-timeline-track">
<?php foreach ($milestones as $milestone): ?>
            <article class="sk-tl-item" data-cat="<?= st_e($milestone['group_name'] ?? '') ?>">
              <div class="sk-tl-dot"><i class="fa-solid <?= st_e(st_icon($milestone['icon'] ?: $milestone['group_icon'] ?? '', 'fa-flag')) ?>" aria-hidden="true"></i></div>
              <div class="sk-tl-card">
                <div class="sk-tl-date"><?= st_e($milestone['date_label'] ?: (!empty($milestone['milestone_date']) ? format_date($milestone['milestone_date']) : 'Current')) ?></div>
                <?php if (!empty($milestone['badge_label']) || !empty($milestone['group_name'])): ?><div class="sk-tl-badge"><?= st_e($milestone['badge_label'] ?: $milestone['group_name']) ?></div><?php endif; ?>
                <h3 class="sk-tl-title"><?= st_e($milestone['title']) ?></h3>
                <p class="sk-tl-desc"><?= st_e($milestone['summary']) ?></p>
              </div>
            </article>
<?php endforeach; ?>
          </div>
        </div>
        <button class="sk-tl-arrow" id="tlNext" aria-label="Scroll to next milestones"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
      </div>
<?php else: ?>
      <div class="sk-empty fade-up"><?= st_e(st_text($milestoneCopy, 'empty_text')) ?></div>
<?php endif; ?>
    </div>
  </section>

  <section class="sk-voices" id="community-voices" aria-labelledby="voices-heading">
    <div class="container">
      <div class="sk-section-header fade-up">
        <div class="sk-eyebrow"><i class="fa-solid fa-quote-left" aria-hidden="true"></i> <?= st_e(st_text($voiceCopy, 'eyebrow', 'Community Voice')) ?></div>
        <h2 class="sk-section-title" id="voices-heading"><?= st_e(st_text($voiceCopy, 'title', 'The People Behind the Programme')) ?></h2>
        <p class="sk-section-sub"><?= st_e(st_text($voiceCopy, 'subtitle')) ?></p>
      </div>
<?php if ($voices): ?>
      <div class="sk-voices-grid" id="voicesGrid">
<?php foreach ($voices as $voice): ?>
        <article class="sk-voice-card fade-up">
          <div class="sk-voice-top">
            <div class="sk-voice-avatar"><?= st_e($voice['initials'] ?: StakeholderTestimonial::initials((string)$voice['name'])) ?></div>
            <div class="sk-voice-meta">
              <strong><?= st_e($voice['name']) ?></strong>
              <span><?= st_e($voice['role']) ?></span>
              <div class="sk-voice-stars" aria-label="<?= st_e((string)(int)$voice['rating']) ?> out of 5 stars">
<?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="<?= $i <= (int)$voice['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star" aria-hidden="true"></i>
<?php endfor; ?>
              </div>
            </div>
          </div>
          <blockquote class="sk-voice-quote"><?= st_e($voice['quote']) ?></blockquote>
          <div class="sk-voice-tags">
<?php foreach (StakeholderTestimonial::tags($voice) as $tag): ?>
            <span class="sk-voice-tag sk-voice-tag--community"><?= st_e($tag) ?></span>
<?php endforeach; ?>
          </div>
        </article>
<?php endforeach; ?>
      </div>
<?php else: ?>
      <div class="sk-empty fade-up"><?= st_e(st_text($voiceCopy, 'empty_text')) ?></div>
<?php endif; ?>
    </div>
  </section>

  <section class="sk-partners" aria-labelledby="partners-heading">
    <div class="sk-partners-bg" aria-hidden="true"></div>
    <div class="container">
      <div class="sk-section-header sk-section-header--light fade-up">
        <div class="sk-eyebrow sk-eyebrow--light"><i class="fa-solid fa-handshake" aria-hidden="true"></i> <?= st_e(st_text($partnerCopy, 'eyebrow', 'Formal Partnerships')) ?></div>
        <h2 class="sk-section-title sk-section-title--light" id="partners-heading"><?= st_e(st_text($partnerCopy, 'title', 'Institutional Partners')) ?></h2>
        <p class="sk-section-sub sk-section-sub--light"><?= st_e(st_text($partnerCopy, 'subtitle')) ?></p>
      </div>
<?php if ($partners): ?>
      <div class="sk-partners-row fade-up">
<?php foreach ($partners as $partner): ?>
        <?php $partnerUrl = st_url((string)($partner['website'] ?? '')); ?>
        <<?= $partnerUrl !== '' ? 'a href="' . st_e($partnerUrl) . '" target="_blank" rel="noopener noreferrer"' : 'div' ?> class="sk-partner-tile<?= $partnerUrl === '' ? ' sk-partner-tile--local' : '' ?>">
          <div class="sk-partner-tile-icon"><i class="fa-solid <?= st_e(st_icon($partner['icon'] ?: $partner['group_icon'] ?? '', 'fa-handshake')) ?>" aria-hidden="true"></i></div>
          <div class="sk-partner-tile-body">
            <strong><?= st_e($partner['organisation']) ?></strong>
            <span><?= st_e($partner['role'] ?: $partner['partner_type']) ?></span>
          </div>
          <?php if ($partnerUrl !== ''): ?><i class="fa-solid fa-arrow-up-right-from-square sk-partner-tile-arrow" aria-hidden="true"></i><?php endif; ?>
        </<?= $partnerUrl !== '' ? 'a' : 'div' ?>>
<?php endforeach; ?>
      </div>
<?php else: ?>
      <div class="sk-empty sk-empty--dark fade-up"><?= st_e(st_text($partnerCopy, 'empty_text')) ?></div>
<?php endif; ?>
    </div>
  </section>

</main>

<?= CmsLoader::jsonScript('stakeholders-page-data', $pagePayload) ?>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
