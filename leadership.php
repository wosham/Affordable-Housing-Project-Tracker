<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$cmsPage = CmsLoader::page('leadership');

$hero = CmsLoader::content($cmsPage, 'leadership_hero', [
    'eyebrow' => 'Programme leadership',
    'title' => 'Programme Leadership',
    'subtitle' => 'A coordinated programme team guiding delivery across Trans-Nzoia County.',
    'background_image' => 'uploads/heroes/hero-main.jpg',
    'background_alt' => 'Affordable housing team at a Trans-Nzoia construction site',
]);
$orgContent = CmsLoader::content($cmsPage, 'leadership_org_chart', [
    'eyebrow' => 'Chain of command',
    'title' => 'From policy leadership to site delivery',
    'subtitle' => 'See the public programme structure and the teams responsible for implementation.',
]);
$cardsContent = CmsLoader::content($cmsPage, 'leadership_national', [
    'eyebrow' => 'Leadership',
    'title' => 'Senior officials guiding the programme',
    'subtitle' => 'Published leadership records for the programme.',
]);
$spotlightContent = CmsLoader::content($cmsPage, 'leadership_spotlight', [
    'eyebrow' => 'Field office',
    'title' => 'Trans-Nzoia programme coordination',
]);
$contractorContent = CmsLoader::content($cmsPage, 'leadership_contractors', [
    'eyebrow' => 'Delivery teams',
    'title' => 'Contractors and site delivery',
    'subtitle' => 'Active delivery partners supporting affordable housing construction across Trans-Nzoia County.',
]);
$quoteContent = CmsLoader::content($cmsPage, 'leadership_quotes', [
    'eyebrow' => 'In their own words',
    'title' => 'Public leadership notes',
]);
$partnerContent = CmsLoader::content($cmsPage, 'leadership_partners', [
    'eyebrow' => 'Implementing partners',
    'title' => 'Organisations supporting delivery',
    'subtitle' => 'Public partners and institutions supporting programme delivery.',
]);
$ctaContent = CmsLoader::content($cmsPage, 'leadership_contact_cta', [
    'eyebrow' => 'Trans-Nzoia field office',
    'title' => 'Reach the programme office',
    'subtitle' => 'For public enquiries, contact the programme team or visit the contact page.',
]);

$projectStats = Project::publicStats();
$constituencyStats = Constituency::publicStats();
$leadershipStats = LeadershipProfile::publicStats();
$contractorStats = Contractor::stats();
$orgNodes = LeadershipProfile::orgChart();
$leadershipCards = LeadershipProfile::publicCards(8);
$spotlight = LeadershipProfile::featuredSpotlight();
$contractors = Contractor::featured(8);
$quotes = LeadershipQuote::featured(8);
if (!$quotes) {
    $quotes = LeadershipQuote::ordered(['status' => 'published']);
}
$partners = Stakeholder::leadershipPartners(12);

$basePath = '';
$activePage = 'leadership';
$pageTitle = (string)($cmsPage['seo_title'] ?? 'Programme Leadership | Trans-Nzoia AHP Tracker');
$pageDescription = (string)($cmsPage['seo_description'] ?? 'Meet the public programme leadership, contractors and implementing partners delivering affordable housing in Trans-Nzoia County.');
$pageKeywords = (string)($cmsPage['seo_keywords'] ?? 'Trans-Nzoia affordable housing leadership, AHP Kenya, county housing leadership');
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = Url::to('leadership.php');
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/leadership.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/leadership.js',
];
$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
    '<meta property="og:title" content="' . ld_h($pageTitle) . '">',
    '<meta property="og:description" content="' . ld_h($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . ld_h($canonicalUrl) . '">',
    '<meta property="og:image" content="' . ld_h(ld_asset(ld_text($hero, 'background_image', (string)($cmsPage['hero_image'] ?? 'uploads/heroes/hero-main.jpg')))) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
];

function ld_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ld_text(array $data, string $key, string $fallback = ''): string
{
    $value = trim((string)($data[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
}

function ld_asset(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(?:https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    return Url::asset($path);
}

function ld_icon(?string $icon, string $fallback = 'fa-user-tie'): string
{
    $icon = preg_replace('/[^a-z0-9 -]/i', '', (string)$icon);
    $icon = trim((string)$icon);
    return $icon !== '' ? $icon : $fallback;
}

function ld_initials(array $row, string $nameKey = 'name'): string
{
    $initials = trim((string)($row['initials'] ?? $row['avatar_label'] ?? ''));
    if ($initials !== '') {
        return strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $initials), 0, 4));
    }
    return LeadershipProfile::initials((string)($row[$nameKey] ?? $row['company_name'] ?? $row['author_name'] ?? 'AHP'));
}

function ld_profile_photo(array $profile): string
{
    foreach (['photo_path', 'media_url', 'media_path'] as $key) {
        $value = trim((string)($profile[$key] ?? ''));
        if ($value !== '') {
            return ld_asset($value);
        }
    }
    return '';
}

function ld_group_by_tier(array $rows): array
{
    $grouped = [];
    foreach ($rows as $row) {
        $tier = max(1, min(6, (int)($row['tier'] ?? 1)));
        $grouped[$tier][] = $row;
    }
    ksort($grouped);
    return $grouped;
}

function ld_progress(mixed $value): int
{
    return max(0, min(100, (int)round((float)$value)));
}

function ld_status_class(string $status): string
{
    return match (strtolower($status)) {
        'active' => 'ld-contractor-badge--active',
        'tendering' => 'ld-contractor-badge--tender',
        default => 'ld-contractor-badge--active',
    };
}

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>

<main id="main-content">
    <section class="ld-hero" aria-label="Programme leadership overview">
        <div class="ld-hero-bg" aria-hidden="true">
            <?php $heroImage = ld_asset(ld_text($hero, 'background_image', (string)($cmsPage['hero_image'] ?? 'uploads/heroes/hero-main.jpg'))); ?>
            <?php if ($heroImage !== ''): ?>
                <img <?= public_image_attrs($heroImage, '', ['loading' => 'eager', 'fetchpriority' => 'high', 'onerror' => "this.style.display='none'"]) ?>>
            <?php endif; ?>
            <div class="ld-hero-overlay"></div>
            <div class="ld-hero-grid-overlay" aria-hidden="true"></div>
        </div>
        <div class="container">
            <div class="ld-hero-body">
                <div class="ld-hero-eyebrow">
                    <i class="fa-solid <?= ld_h(ld_icon($hero['icon'] ?? 'fa-user-tie')) ?>" aria-hidden="true"></i>
                    <?= ld_h(ld_text($hero, 'eyebrow', 'Programme leadership')) ?>
                </div>
                <h1 class="ld-hero-title"><?= ld_h(ld_text($hero, 'title', 'The People Delivering Trans-Nzoia Housing')) ?></h1>
                <p class="ld-hero-sub"><?= ld_h(ld_text($hero, 'subtitle', 'A coordinated programme team guiding delivery across Trans-Nzoia County.')) ?></p>
                <div class="ld-hero-pills">
                    <span class="ld-hero-pill"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> <?= number_format((int)($projectStats['total_projects'] ?? 0)) ?> Projects</span>
                    <span class="ld-hero-pill"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i> <?= number_format((int)($projectStats['total_units'] ?? 0)) ?> Units Planned</span>
                    <span class="ld-hero-pill"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> <?= number_format((int)($constituencyStats['constituencies'] ?? 0)) ?> Constituencies</span>
                    <span class="ld-hero-pill"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i> <?= number_format((int)($contractorStats['active'] ?? 0)) ?> Active Contractors</span>
                </div>
            </div>
        </div>
        <div class="ld-hero-scroll-hint" aria-hidden="true">
            <span>Scroll to explore</span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>
    </section>

    <section class="ld-org fade-up" aria-label="Programme leadership structure">
        <div class="container">
            <div class="ld-section-header">
                <div class="ld-eyebrow"><i class="fa-solid <?= ld_h(ld_icon($orgContent['icon'] ?? 'fa-sitemap', 'fa-sitemap')) ?>" aria-hidden="true"></i> <?= ld_h(ld_text($orgContent, 'eyebrow', 'Chain of command')) ?></div>
                <h2 class="ld-section-title"><?= ld_h(ld_text($orgContent, 'title', 'From policy leadership to site delivery')) ?></h2>
                <p class="ld-section-sub"><?= ld_h(ld_text($orgContent, 'subtitle', 'See the public programme structure and the teams responsible for implementation.')) ?></p>
            </div>

            <?php $tiers = ld_group_by_tier($orgNodes); ?>
            <?php if ($tiers): ?>
                <div class="ld-org-chart" id="orgChart" role="tree" aria-label="Programme leadership hierarchy">
                    <?php $tierIndex = 0; $tierTotal = count($tiers); ?>
                    <?php foreach ($tiers as $tier => $nodes): ?>
                        <?php $tierIndex++; ?>
                        <div class="ld-org-level <?= count($nodes) > 1 ? 'ld-org-level--wide' : '' ?>" role="group">
                            <?php foreach ($nodes as $node): ?>
                                <?php
                                $photo = ld_profile_photo($node);
                                $popoverId = 'leader-pop-' . (int)$node['id'];
                                $nodeTier = max(1, min(5, (int)($node['tier'] ?? $tier)));
                                $highlight = !empty($node['show_in_spotlight']) ? ' ld-org-node--highlighted' : '';
                                ?>
                                <article class="ld-org-node ld-org-node--tier<?= $nodeTier ?><?= $highlight ?>" role="treeitem" tabindex="0" aria-expanded="false" data-popover="<?= ld_h($popoverId) ?>">
                                    <div class="ld-org-avatar ld-org-avatar--tier<?= $nodeTier ?>">
                                        <?php if ($photo !== ''): ?>
                                            <img <?= public_image_attrs($photo, '', ['onerror' => "this.remove()"]) ?>>
                                        <?php else: ?>
                                            <i class="fa-solid <?= ld_h(ld_icon($node['icon'] ?? 'fa-user-tie')) ?>" aria-hidden="true"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ld-org-info">
                                        <span class="ld-org-tier-badge"><?= ld_h((string)($node['appointment_label'] ?? $node['profile_type'] ?? 'Leadership')) ?></span>
                                        <h3 class="ld-org-name"><?= ld_h($node['name'] ?? '') ?></h3>
                                        <span class="ld-org-dept"><?= ld_h(trim(($node['title'] ?? '') . (($node['organisation'] ?? '') !== '' ? ' - ' . $node['organisation'] : ''))) ?></span>
                                    </div>
                                    <?php if (!empty($node['office_location'])): ?>
                                        <div class="ld-org-location-badge"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= ld_h($node['office_location']) ?></div>
                                    <?php endif; ?>
                                    <button class="ld-org-info-btn" aria-label="View details for <?= ld_h($node['name'] ?? 'leader') ?>" tabindex="-1">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </button>
                                    <div class="ld-org-popover" id="<?= ld_h($popoverId) ?>" role="tooltip">
                                        <p><?= ld_h(ld_text($node, 'bio', ld_text($node, 'quote', 'This public leadership record is available for programme reference.'))) ?></p>
                                        <?php if (!empty($node['office_location'])): ?>
                                            <span class="ld-org-pop-dept"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= ld_h($node['office_location']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($tierIndex < $tierTotal): ?>
                            <div class="ld-org-connector" aria-hidden="true"><div class="ld-org-connector-line"></div></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ld-empty-state">
                    <i class="fa-solid fa-sitemap" aria-hidden="true"></i>
                    <strong>Leadership structure will appear here.</strong>
                    <span>Published leadership records will show in this section.</span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ld-national fade-up" aria-label="Public leadership cards">
        <div class="container">
            <div class="ld-section-header">
                <div class="ld-eyebrow"><i class="fa-solid <?= ld_h(ld_icon($cardsContent['icon'] ?? 'fa-building-columns', 'fa-building-columns')) ?>" aria-hidden="true"></i> <?= ld_h(ld_text($cardsContent, 'eyebrow', 'Leadership')) ?></div>
                <h2 class="ld-section-title"><?= ld_h(ld_text($cardsContent, 'title', 'Senior officials guiding the programme')) ?></h2>
                    <p class="ld-section-sub"><?= ld_h(ld_text($cardsContent, 'subtitle', 'Published leadership records for the programme.')) ?></p>
            </div>

            <?php if ($leadershipCards): ?>
                <div class="ld-national-track-wrap">
                    <div class="ld-national-track" id="nationalTrack">
                        <?php foreach ($leadershipCards as $card): ?>
                            <article class="ld-national-card" aria-label="<?= ld_h($card['name'] ?? 'Leadership profile') ?>">
                                <div class="ld-national-card-top">
                                    <?php $cardPhoto = ld_profile_photo($card); ?>
                                    <div class="ld-national-avatar ld-national-avatar--green">
                                        <?php if ($cardPhoto !== ''): ?>
                                            <img <?= public_image_attrs($cardPhoto, '', ['onerror' => "this.remove()"]) ?>>
                                        <?php else: ?>
                                            <span aria-hidden="true"><?= ld_h(ld_initials($card)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ld-national-flag-strip" aria-hidden="true"></div>
                                </div>
                                <div class="ld-national-card-body">
                                    <span class="ld-national-role"><?= ld_h($card['title'] ?? 'Programme leadership') ?></span>
                                    <h3 class="ld-national-name"><?= ld_h($card['name'] ?? '') ?></h3>
                                    <p class="ld-national-mandate"><?= ld_h(ld_text($card, 'bio', ld_text($card, 'appointment_source', 'Supports public programme delivery in Trans-Nzoia.'))) ?></p>
                                    <?php if (!empty($card['organisation']) || !empty($card['office_location'])): ?>
                                        <div class="ld-national-dept">
                                            <i class="fa-solid fa-building-flag" aria-hidden="true"></i>
                                            <span><?= ld_h(trim(($card['organisation'] ?? '') . (($card['office_location'] ?? '') !== '' ? ' - ' . $card['office_location'] : ''))) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($card['quote'])): ?>
                                        <blockquote class="ld-national-quote"><?= ld_h($card['quote']) ?></blockquote>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (count($leadershipCards) > 1): ?>
                    <div class="ld-national-nav" aria-label="Leadership carousel controls">
                        <button class="ld-national-prev" id="natPrev" aria-label="Previous leadership cards"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                        <div class="ld-national-dots" id="natDots" role="tablist" aria-label="Leadership card groups"></div>
                        <button class="ld-national-next" id="natNext" aria-label="Next leadership cards"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                    </div>
                <?php else: ?>
                    <div id="natDots" hidden></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="ld-empty-state"><i class="fa-solid fa-user-tie"></i><strong>Leadership cards will appear here.</strong><span>Published leadership profiles will show in this section.</span></div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($spotlight): ?>
        <section class="ld-spotlight fade-up" aria-label="Featured programme leader">
            <div class="container">
                <div class="ld-spotlight-inner">
                    <div class="ld-spotlight-visual">
                        <div class="ld-spotlight-avatar">
                            <?php $spotPhoto = ld_profile_photo($spotlight); ?>
                            <?php if ($spotPhoto !== ''): ?>
                                <img <?= public_image_attrs($spotPhoto, '', ['onerror' => "this.remove()"]) ?>>
                            <?php else: ?>
                                <span><?= ld_h(ld_initials($spotlight)) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="ld-spotlight-badge"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= ld_h(ld_text($spotlight, 'office_location', 'Trans-Nzoia')) ?></div>
                        <?php if (!empty($spotlight['office_location'])): ?>
                            <div class="ld-spotlight-office-card">
                                <i class="fa-solid fa-building" aria-hidden="true"></i>
                                <div><strong>Office</strong><span><?= ld_h($spotlight['office_location']) ?></span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ld-spotlight-content">
                        <div class="ld-eyebrow"><i class="fa-solid <?= ld_h(ld_icon($spotlightContent['icon'] ?? 'fa-location-dot', 'fa-location-dot')) ?>" aria-hidden="true"></i> <?= ld_h(ld_text($spotlightContent, 'eyebrow', 'Field office')) ?></div>
                        <h2 class="ld-spotlight-name"><?= ld_h($spotlight['name'] ?? '') ?></h2>
                        <div class="ld-spotlight-title"><?= ld_h($spotlight['title'] ?? '') ?></div>
                        <?php if (!empty($spotlight['appointment_source'])): ?>
                            <div class="ld-spotlight-appointment-note">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                <span><?= ld_h($spotlight['appointment_source']) ?></span>
                            </div>
                        <?php endif; ?>
                        <p class="ld-spotlight-bio"><?= ld_h(ld_text($spotlight, 'bio', ld_text($spotlightContent, 'subtitle', 'Coordinates programme delivery in Trans-Nzoia County.'))) ?></p>
                        <?php if (!empty($spotlight['quote'])): ?>
                            <blockquote class="ld-spotlight-quote">
                                <i class="fa-solid fa-quote-left ld-quote-icon" aria-hidden="true"></i>
                                <?= ld_h($spotlight['quote']) ?>
                                <span class="ld-spotlight-quote-attr"><?= ld_h($spotlight['name'] ?? '') ?></span>
                            </blockquote>
                        <?php endif; ?>
                        <?php $responsibilities = LeadershipProfile::responsibilities($spotlight); ?>
                        <?php if ($responsibilities): ?>
                            <div class="ld-spotlight-responsibilities">
                                <div class="ld-spotlight-resp-title">Responsibilities</div>
                                <ul class="ld-spotlight-resp-list">
                                    <?php foreach ($responsibilities as $item): ?>
                                        <li><i class="fa-solid fa-check" aria-hidden="true"></i><?= ld_h($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="ld-contractors fade-up" aria-label="Programme contractors">
        <div class="container">
            <div class="ld-section-header">
                <div class="ld-eyebrow"><i class="fa-solid <?= ld_h(ld_icon($contractorContent['icon'] ?? 'fa-helmet-safety', 'fa-helmet-safety')) ?>" aria-hidden="true"></i> <?= ld_h(ld_text($contractorContent, 'eyebrow', 'Delivery teams')) ?></div>
                <h2 class="ld-section-title"><?= ld_h(ld_text($contractorContent, 'title', 'Contractors and site delivery')) ?></h2>
                <p class="ld-section-sub"><?= ld_h(ld_text($contractorContent, 'subtitle', 'Active delivery partners and the public project records linked to them.')) ?></p>
            </div>
            <?php if ($contractors): ?>
                <div class="ld-contractors-grid" id="contractorsGrid">
                    <?php foreach ($contractors as $contractor): ?>
                        <?php $progress = ld_progress($contractor['project_progress'] ?? $contractor['progress_pct'] ?? 0); ?>
                        <article class="ld-contractor-card">
                            <div class="ld-contractor-header">
                                <div class="ld-contractor-avatar" style="--avatar-hue: <?= (int)((crc32((string)($contractor['company_name'] ?? 'contractor')) % 220) + 20) ?>">
                                    <span><?= ld_h(ld_initials($contractor, 'company_name')) ?></span>
                                </div>
                                <div class="ld-contractor-badge <?= ld_h(ld_status_class((string)($contractor['status'] ?? 'active'))) ?>">
                                    <i class="fa-solid fa-circle" aria-hidden="true"></i> <?= ld_h(ucfirst((string)($contractor['status'] ?? 'Active'))) ?>
                                </div>
                            </div>
                            <div class="ld-contractor-body">
                                <div class="ld-contractor-nca"><?= ld_h(ld_text($contractor, 'nca_grade', 'Registered contractor')) ?></div>
                                <h3 class="ld-contractor-name"><?= ld_h($contractor['company_name'] ?? '') ?></h3>
                                <div class="ld-contractor-project">
                                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    <span><?= ld_h(trim(($contractor['project_name'] ?? 'Assigned project') . (($contractor['constituency_name'] ?? '') !== '' ? ' - ' . $contractor['constituency_name'] : ''))) ?></span>
                                </div>
                                <div class="ld-contractor-progress">
                                    <div class="ld-contractor-prog-bar">
                                        <div class="ld-contractor-prog-fill" style="--prog: <?= $progress ?>%" aria-label="<?= $progress ?> percent complete"></div>
                                    </div>
                                    <span><?= $progress ?>% complete</span>
                                </div>
                                <?php if (!empty($contractor['quote'])): ?>
                                    <blockquote class="ld-contractor-quote"><?= ld_h($contractor['quote']) ?></blockquote>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ld-empty-state"><i class="fa-solid fa-helmet-safety"></i><strong>No public contractor records yet.</strong><span>Active contractors will appear here when published.</span></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ld-quotes fade-up" aria-label="Leadership notes">
        <div class="container">
            <div class="ld-section-header ld-section-header--light">
                <div class="ld-eyebrow ld-eyebrow--light"><i class="fa-solid <?= ld_h(ld_icon($quoteContent['icon'] ?? 'fa-quote-right', 'fa-quote-right')) ?>" aria-hidden="true"></i> <?= ld_h(ld_text($quoteContent, 'eyebrow', 'In their own words')) ?></div>
                <h2 class="ld-section-title ld-section-title--light"><?= ld_h(ld_text($quoteContent, 'title', 'Public leadership notes')) ?></h2>
            </div>
            <?php if ($quotes): ?>
                <div class="ld-quotes-carousel" id="quotesCarousel" aria-roledescription="carousel" aria-label="Leadership notes">
                    <div class="ld-quotes-track" id="quotesTrack">
                        <?php foreach ($quotes as $index => $quote): ?>
                            <div class="ld-quote-slide" role="group" aria-roledescription="slide" aria-label="Note <?= $index + 1 ?> of <?= count($quotes) ?>">
                                <div class="ld-quote-mark" aria-hidden="true">&quot;</div>
                                <p class="ld-quote-text"><?= ld_h($quote['quote_text'] ?? '') ?></p>
                                <div class="ld-quote-attr">
                                    <div class="ld-quote-avatar ld-quote-avatar--<?= ld_h(preg_replace('/[^a-z]/', '', strtolower((string)($quote['theme'] ?? 'green'))) ?: 'green') ?>"><?= ld_h(ld_initials($quote, 'author_name')) ?></div>
                                    <div>
                                        <strong><?= ld_h($quote['author_name'] ?? '') ?></strong>
                                        <span><?= ld_h($quote['author_title'] ?? '') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (count($quotes) > 1): ?>
                    <div class="ld-quotes-controls" aria-label="Leadership note controls">
                        <button class="ld-quotes-prev" id="quotesPrev" aria-label="Previous note"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                        <div class="ld-quotes-dots" id="quotesDots" role="tablist" aria-label="Leadership notes"></div>
                        <button class="ld-quotes-next" id="quotesNext" aria-label="Next note"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                    </div>
                <?php else: ?>
                    <div id="quotesDots" hidden></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="ld-empty-state ld-empty-state--dark"><i class="fa-solid fa-quote-right"></i><strong>No public notes yet.</strong><span>Published quotes will appear here.</span></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ld-partners fade-up" aria-label="Implementing partner organisations">
        <div class="container">
            <div class="ld-section-header">
                <div class="ld-eyebrow"><i class="fa-solid <?= ld_h(ld_icon($partnerContent['icon'] ?? 'fa-handshake', 'fa-handshake')) ?>" aria-hidden="true"></i> <?= ld_h(ld_text($partnerContent, 'eyebrow', 'Implementing partners')) ?></div>
                <h2 class="ld-section-title"><?= ld_h(ld_text($partnerContent, 'title', 'Organisations supporting delivery')) ?></h2>
                <p class="ld-section-sub"><?= ld_h(ld_text($partnerContent, 'subtitle', 'Public partners and institutions supporting programme delivery.')) ?></p>
            </div>
            <?php if ($partners): ?>
                <div class="ld-partners-grid">
                    <?php foreach ($partners as $partner): ?>
                        <div class="ld-partner-card">
                            <div class="ld-partner-icon" aria-hidden="true"><i class="fa-solid <?= ld_h(ld_icon($partner['icon'] ?? 'fa-handshake', 'fa-handshake')) ?>"></i></div>
                            <div class="ld-partner-info">
                                <h3 class="ld-partner-name"><?= ld_h($partner['organisation'] ?? '') ?></h3>
                                <p class="ld-partner-role"><?= ld_h(ld_text($partner, 'role', ld_text($partner, 'description', 'Programme partner'))) ?></p>
                            </div>
                            <?php if (!empty($partner['website'])): ?>
                                <a href="<?= ld_h(Url::to((string)$partner['website'])) ?>" target="_blank" rel="noopener noreferrer" class="ld-partner-link" aria-label="Visit <?= ld_h($partner['organisation'] ?? 'partner') ?> website">
                                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ld-empty-state"><i class="fa-solid fa-handshake"></i><strong>No public partners yet.</strong><span>Featured stakeholders will appear here when published.</span></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ld-cta fade-up" aria-label="Contact the programme office">
        <div class="container">
            <div class="ld-cta-inner">
                <div class="ld-cta-content">
                    <div class="ld-eyebrow ld-eyebrow--light"><i class="fa-solid <?= ld_h(ld_icon($ctaContent['icon'] ?? 'fa-location-dot', 'fa-location-dot')) ?>" aria-hidden="true"></i> <?= ld_h(ld_text($ctaContent, 'eyebrow', 'Trans-Nzoia field office')) ?></div>
                    <h2 class="ld-cta-title"><?= ld_h(ld_text($ctaContent, 'title', 'Reach the programme office')) ?></h2>
                    <p class="ld-cta-sub"><?= ld_h(ld_text($ctaContent, 'subtitle', 'For public enquiries, contact the programme team or visit the contact page.')) ?></p>
                    <div class="ld-cta-contact-list">
                        <div class="ld-cta-contact-item">
                            <div class="ld-cta-contact-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
                            <div><strong>Physical Address</strong><span><?= ld_h(CmsLoader::settingText('contact_address', 'County Headquarters, Kitale, Trans-Nzoia County, Kenya')) ?></span></div>
                        </div>
                        <div class="ld-cta-contact-item">
                            <div class="ld-cta-contact-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></div>
                            <div><strong>Telephone</strong><span><?= ld_h(CmsLoader::settingText('contact_phone', '+254 53 000 0000')) ?></span></div>
                        </div>
                        <div class="ld-cta-contact-item">
                            <div class="ld-cta-contact-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></div>
                            <div><strong>Email</strong><span><?= ld_h(CmsLoader::settingText('contact_email', 'housing@transnzoia.go.ke')) ?></span></div>
                        </div>
                        <div class="ld-cta-contact-item">
                            <div class="ld-cta-contact-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></div>
                            <div><strong>Office Hours</strong><span><?= ld_h(CmsLoader::settingText('office_hours', 'Monday - Friday, 8:00 AM - 5:00 PM')) ?></span></div>
                        </div>
                    </div>
                    <div class="ld-cta-actions">
                        <a href="<?= ld_h(Url::to('contact.php')) ?>" class="ld-btn-lime"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Send a Message</a>
                        <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="ld-btn-outline">Apply via Boma Yangu <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="ld-cta-map-card" aria-label="Office location illustration">
                    <div class="ld-cta-map-inner" aria-hidden="true">
                        <div class="ld-cta-map-pin"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="ld-cta-map-label">Trans-Nzoia</div>
                        <div class="ld-cta-map-sublabel"><?= ld_h(CmsLoader::settingText('contact_address', 'Kitale, Kenya')) ?></div>
                        <div class="ld-cta-map-grid" aria-hidden="true"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?= public_page_json('leadership', [
    'stats' => [
        'projects' => $projectStats,
        'constituencies' => $constituencyStats,
        'leadership' => $leadershipStats,
        'contractors' => $contractorStats,
    ],
], ['title' => $pageTitle, 'description' => $pageDescription]) ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
