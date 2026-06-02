<?php

declare(strict_types=1);

$legalSlug = isset($legalSlug) ? (string)$legalSlug : 'disclaimer';
$legalDefaults = isset($legalDefaults) && is_array($legalDefaults) ? $legalDefaults : [];

require_once dirname(__DIR__) . '/core/bootstrap.php';

$legalPage = CmsLoader::page($legalSlug);
$legalContent = CmsLoader::content($legalPage, 'legal_document', $legalDefaults);

foreach ($legalDefaults as $key => $value) {
    if (!isset($legalContent[$key]) || trim((string)$legalContent[$key]) === '') {
        $legalContent[$key] = $value;
    }
}

$legalTitle = legal_value($legalContent, 'title', cms_legal_default_title($legalSlug));
$legalSubtitle = legal_value($legalContent, 'subtitle', '');
$legalBody = legal_remove_document_references(legal_value($legalContent, 'body', '<h2>Document Pending</h2><p>This legal document is being prepared.</p>'));
$legalIcon = legal_icon_class(legal_value($legalContent, 'icon', cms_legal_default_icon($legalSlug)));
$legalUpdated = legal_value($legalContent, 'last_updated', (string)($legalPage['updated_at'] ?? date('Y-m-d')));
$legalRoute = (string)($legalPage['route_path'] ?? ('legal/' . $legalSlug . '.php'));
$legalCanonical = (string)($legalPage['canonical_url'] ?? '');
$prepared = legal_prepare_document($legalBody);
$toc = $prepared['toc'];

$basePath = '../';
$activePage = $legalSlug;
$pageTitle = ($legalPage['seo_title'] ?? '') !== '' ? (string)$legalPage['seo_title'] : $legalTitle . ' | Trans-Nzoia AHP Tracker';
$pageDescription = ($legalPage['seo_description'] ?? '') !== '' ? (string)$legalPage['seo_description'] : strip_tags($legalSubtitle);
$pageKeywords = (string)($legalPage['seo_keywords'] ?? '');
$pageAuthor = 'Trans-Nzoia County Government';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $legalCanonical;
$pageStyles = [
    '../assets/css/global.css',
    'legal.css',
];
$pageScripts = [
    '../assets/js/global.js',
    'legal.js',
];
$headMeta = [];

include __DIR__ . '/head.php';
?>
<body>
<?php include __DIR__ . '/cursor.php'; ?>
<?php include __DIR__ . '/skip-link.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>
<main id="main-content">
  <section class="lg-hero lg-hero--<?= Security::e($legalSlug) ?>" aria-label="<?= Security::e($legalTitle) ?>">
    <div class="lg-hero-bg" aria-hidden="true"></div>
    <div class="container">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../index.php" class="breadcrumb-link">Home</a>
        <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
        <span class="breadcrumb-current" aria-current="page"><?= Security::e($legalTitle) ?></span>
      </nav>
      <div class="lg-hero-body">
        <div class="lg-hero-icon" aria-hidden="true"><i class="fa-solid <?= Security::e($legalIcon) ?>"></i></div>
        <h1 class="lg-hero-title"><?= Security::e($legalTitle) ?></h1>
        <?php if ($legalSubtitle !== ''): ?><p class="lg-hero-sub"><?= Security::e($legalSubtitle) ?></p><?php endif; ?>
        <div class="lg-hero-meta">
          <span class="lg-meta-badge"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Last updated: <?= Security::e(format_date($legalUpdated, 'j F Y')) ?></span>
          <span class="lg-meta-badge"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span id="lgReadingTime">Calculating...</span></span>
          <button class="lg-meta-badge lg-print-btn" id="lgPrintBtn" type="button" aria-label="Print this page">
            <i class="fa-solid fa-print" aria-hidden="true"></i> Print
          </button>
        </div>
      </div>
    </div>
  </section>

  <div class="lg-layout container">
    <aside class="lg-toc-sidebar" aria-label="Table of contents">
      <div class="lg-toc-inner" id="lgToc">
        <div class="lg-toc-head">
          <span><i class="fa-solid fa-list-ul" aria-hidden="true"></i> Contents</span>
          <button class="lg-toc-toggle" id="lgTocToggle" type="button" aria-label="Toggle contents" aria-expanded="true">
            <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
          </button>
        </div>
        <nav class="lg-toc-nav" id="lgTocNav" aria-label="Section navigation">
          <?php foreach ($toc as $item): ?>
            <a class="lg-toc-link" href="#<?= Security::e($item['id']) ?>"><?= Security::e($item['label']) ?></a>
          <?php endforeach; ?>
        </nav>
        <div class="lg-toc-links">
          <?php foreach (cms_legal_related_links($legalSlug) as $link): ?>
            <a href="<?= Security::e($link['href']) ?>" class="lg-toc-related"><i class="fa-solid <?= Security::e($link['icon']) ?>" aria-hidden="true"></i> <?= Security::e($link['label']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>

    <article class="lg-article lg-article--cms" id="lgArticle">
      <?= $prepared['html'] ?>
    </article>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
<?php include __DIR__ . '/scripts.php'; ?>
</body>
</html>
<?php

function legal_value(array $content, string $key, string $default = ''): string
{
    $value = $content[$key] ?? $default;
    return trim((string)$value) !== '' ? (string)$value : $default;
}

function cms_legal_default_title(string $slug): string
{
    return match ($slug) {
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Use',
        default => 'Disclaimer',
    };
}

function cms_legal_default_icon(string $slug): string
{
    return match ($slug) {
        'privacy' => 'fa-shield-halved',
        'terms' => 'fa-file-contract',
        default => 'fa-triangle-exclamation',
    };
}

function legal_icon_class(string $icon): string
{
    $icon = trim($icon);
    if ($icon === '') {
        return 'fa-file-shield';
    }

    $icon = preg_replace('/[^a-zA-Z0-9\-\s]/', '', $icon) ?: 'fa-file-shield';
    return str_contains($icon, 'fa-') ? $icon : 'fa-' . $icon;
}

function legal_prepare_document(string $html): array
{
    $toc = [];
    preg_match_all('/<h([1-2])([^>]*)>(.*?)<\/h\1>/is', $html, $matches, PREG_OFFSET_CAPTURE);

    if (!$matches[0]) {
        $toc[] = ['id' => 'document', 'label' => '1. Document'];
        $html = '<section class="lg-section" id="document">' . $html . '</section>';
        return ['html' => $html, 'toc' => $toc];
    }

    $output = '';
    $cursor = 0;
    $usedIds = [];
    $sectionOpen = false;

    foreach ($matches[0] as $index => $headingMatch) {
        $headingHtml = $headingMatch[0];
        $headingStart = $headingMatch[1];
        $headingEnd = $headingStart + strlen($headingHtml);
        $before = substr($html, $cursor, $headingStart - $cursor);

        if (trim($before) !== '') {
            if ($sectionOpen) {
                $output .= $before;
            } else {
                $output .= '<section class="lg-section lg-section--intro" id="document-introduction">' . $before . '</section>';
            }
        }

        if ($sectionOpen) {
            $output .= '</section>';
        }

        $level = (int)$matches[1][$index][0];
        $headingText = trim(strip_tags($matches[3][$index][0]));
        $clean = preg_replace('/^\d+[\).\s-]*/', '', $headingText) ?: $headingText;
        $id = legal_slugify($clean);
        $base = $id;
        $suffix = 2;
        while (in_array($id, $usedIds, true)) {
            $id = $base . '-' . $suffix;
            $suffix++;
        }
        $usedIds[] = $id;

        $toc[] = [
            'id' => $id,
            'label' => count($toc) + 1 . '. ' . $clean,
        ];

        $class = 'lg-section-heading';
        $number = '<span class="lg-section-num">' . count($toc) . '</span> ';
        $output .= '<section class="lg-section" id="' . Security::e($id) . '">';
        $output .= '<h' . $level . ' class="' . $class . '">' . $number . $matches[3][$index][0] . '</h' . $level . '>';

        $sectionOpen = true;
        $cursor = $headingEnd;
    }

    $tail = substr($html, $cursor);
    if (trim($tail) !== '') {
        $output .= $tail;
    }
    if ($sectionOpen) {
        $output .= '</section>';
    }

    return ['html' => $output, 'toc' => $toc];
}

function legal_remove_document_references(string $html): string
{
    $patterns = [
        '/\s*Document reference:\s*<code>[^<]*<\/code>\.?/i',
        '/\s*Document reference:\s*[A-Z0-9\-]+\.?/i',
    ];

    return preg_replace($patterns, '', $html) ?? $html;
}

function legal_slugify(string $value): string
{
    $value = strtolower(trim(strip_tags($value)));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'section';
    return trim($value, '-') ?: 'section';
}

function cms_legal_related_links(string $current): array
{
    $links = [
        'privacy' => ['href' => 'privacy.php', 'label' => 'Privacy Policy', 'icon' => 'fa-shield-halved'],
        'terms' => ['href' => 'terms.php', 'label' => 'Terms of Use', 'icon' => 'fa-file-contract'],
        'disclaimer' => ['href' => 'disclaimer.php', 'label' => 'Disclaimer', 'icon' => 'fa-triangle-exclamation'],
    ];

    unset($links[$current]);
    return array_values($links);
}
