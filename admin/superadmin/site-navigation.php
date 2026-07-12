<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'Site Navigation';
$pageDescription = 'Control public header, footer and mobile navigation labels, logos and menu visibility.';
$adminRole = 'superadmin';
$csrfForm = 'site_navigation';
$contentClass = 'site-navigation-page';
$componentCss = ['media-library', 'site-navigation'];
$pageScripts = ['media-picker'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Public Content', 'url' => Url::to('admin/superadmin/cms.php')],
    ['label' => 'Site Navigation'],
];

$settingDefinitions = [
    'header' => [
        'label' => 'Header',
        'icon' => 'fa-window-maximize',
        'description' => 'Top navigation brand, logos and staff portal action.',
        'items' => [
            'asset_county_logo' => ['label' => 'County logo', 'type' => 'image', 'fallback' => '', 'folder' => 'logos'],
            'asset_logo' => ['label' => 'AHP logo', 'type' => 'image', 'fallback' => 'uploads/logos/afforadablehousinglogo.png', 'folder' => 'logos'],
            'site_short_name' => ['label' => 'Header title', 'type' => 'text', 'fallback' => 'Trans-Nzoia AHP Tracker'],
            'site_county_label' => ['label' => 'Header subtitle', 'type' => 'text', 'fallback' => 'Trans-Nzoia County'],
            'header_staff_visible' => ['label' => 'Show staff portal button', 'type' => 'boolean', 'fallback' => '1'],
            'header_staff_label' => ['label' => 'Staff portal label', 'type' => 'text', 'fallback' => 'Staff Portal'],
            'header_staff_url' => ['label' => 'Staff portal URL', 'type' => 'text', 'fallback' => 'admin/login.php'],
            'header_staff_icon' => ['label' => 'Staff portal icon class', 'type' => 'text', 'fallback' => 'fa-lock'],
        ],
    ],
    'mobile' => [
        'label' => 'Mobile Menu',
        'icon' => 'fa-mobile-screen-button',
        'description' => 'Mobile drawer brand, section headings and action buttons.',
        'items' => [
            'mobile_brand_label' => ['label' => 'Mobile brand label', 'type' => 'text', 'fallback' => 'Trans-Nzoia AHP Tracker'],
            'mobile_pages_heading' => ['label' => 'Pages section heading', 'type' => 'text', 'fallback' => 'Pages'],
            'mobile_legal_heading' => ['label' => 'Legal section heading', 'type' => 'text', 'fallback' => 'Legal'],
            'mobile_staff_visible' => ['label' => 'Show staff login button', 'type' => 'boolean', 'fallback' => '1'],
            'mobile_staff_label' => ['label' => 'Staff login label', 'type' => 'text', 'fallback' => 'Staff Portal Login'],
            'mobile_staff_url' => ['label' => 'Staff login URL', 'type' => 'text', 'fallback' => 'admin/login.php'],
            'mobile_staff_icon' => ['label' => 'Staff login icon class', 'type' => 'text', 'fallback' => 'fa-lock'],
            'mobile_apply_visible' => ['label' => 'Show eCitizen button', 'type' => 'boolean', 'fallback' => '1'],
            'mobile_apply_label' => ['label' => 'eCitizen button label', 'type' => 'text', 'fallback' => 'Apply via eCitizen'],
            'apply_portal_url' => ['label' => 'eCitizen URL', 'type' => 'text', 'fallback' => ''],
            'mobile_apply_icon' => ['label' => 'eCitizen icon class', 'type' => 'text', 'fallback' => 'fa-arrow-up-right-from-square'],
            'mobile_contact_visible' => ['label' => 'Show contact strip', 'type' => 'boolean', 'fallback' => '1'],
        ],
    ],
    'footer' => [
        'label' => 'Footer',
        'icon' => 'fa-shoe-prints',
        'description' => 'Footer logos, column headings, contact copy and copyright.',
        'items' => [
            'asset_footer_logo_primary' => ['label' => 'Footer primary logo', 'type' => 'image', 'fallback' => '', 'folder' => 'logos'],
            'asset_footer_logo_secondary' => ['label' => 'Footer secondary logo', 'type' => 'image', 'fallback' => 'uploads/logos/afforadablehousinglogo.png', 'folder' => 'logos'],
            'footer_description' => ['label' => 'Footer description', 'type' => 'textarea', 'fallback' => 'The Trans-Nzoia County Affordable Housing Project Tracker provides transparent, real-time monitoring of construction delivery under the national AHP programme.'],
            'footer_public_heading' => ['label' => 'Public links heading', 'type' => 'text', 'fallback' => 'Public Pages'],
            'footer_programme_heading' => ['label' => 'Programme links heading', 'type' => 'text', 'fallback' => 'Programme'],
            'footer_contact_heading' => ['label' => 'Contact heading', 'type' => 'text', 'fallback' => 'Contact'],
            'contact_address' => ['label' => 'Contact address', 'type' => 'textarea', 'fallback' => 'Trans-Nzoia County Government Offices,\nKitale'],
            'contact_phone' => ['label' => 'Contact phone label', 'type' => 'text', 'fallback' => '+254 53 000 0000'],
            'contact_phone_href' => ['label' => 'Contact phone link', 'type' => 'text', 'fallback' => 'tel:+254530000000'],
            'contact_email' => ['label' => 'Contact email label', 'type' => 'text', 'fallback' => 'housing@transnzoia.go.ke'],
            'contact_email_href' => ['label' => 'Contact email link', 'type' => 'text', 'fallback' => 'mailto:housing@transnzoia.go.ke'],
            'footer_copyright' => ['label' => 'Copyright line', 'type' => 'text', 'fallback' => 'Â© {year} Trans-Nzoia County Government â€” Dept. of Land, Housing & Physical Planning. All rights reserved.'],
        ],
    ],
];

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/site-navigation.php'));
    }

    try {
        $postedSettings = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [];
        foreach ($settingDefinitions as $groupKey => $group) {
            foreach ($group['items'] as $key => $definition) {
                $type = (string)$definition['type'];
                $raw = $postedSettings[$key] ?? ($type === 'boolean' ? '0' : '');
                $value = $type === 'boolean'
                    ? (in_array((string)$raw, ['1', 'true', 'on', 'yes'], true) ? '1' : '0')
                    : trim((string)$raw);
                $storedType = $type === 'boolean' ? 'boolean' : 'text';
                CmsSetting::upsert($key, $value, $storedType, (string)$definition['label'], 'site_navigation');
            }
        }

        $postedNav = is_array($_POST['nav'] ?? null) ? $_POST['nav'] : [];
        $currentIds = Database::fetchAll('SELECT id FROM navigation_links');
        $allowedIds = array_flip(array_map(static fn (array $row): int => (int)$row['id'], $currentIds));
        foreach ($postedNav as $id => $row) {
            $linkId = (int)$id;
            if (!isset($allowedIds[$linkId]) || !is_array($row)) {
                continue;
            }
            $label = Security::cleanString((string)($row['label'] ?? ''));
            $href = trim((string)($row['href'] ?? ''));
            $pageKey = Security::cleanString((string)($row['page_key'] ?? ''));
            $sortOrder = Security::cleanInt($row['sort_order'] ?? 0, 0);
            $isVisible = !empty($row['is_visible']) ? 1 : 0;
            $isExternal = !empty($row['is_external']) ? 1 : 0;
            if ($label === '' || $href === '') {
                continue;
            }
            Database::query(
                'UPDATE navigation_links SET label = ?, href = ?, page_key = ?, sort_order = ?, is_visible = ?, is_external = ? WHERE id = ?',
                [$label, $href, $pageKey, $sortOrder, $isVisible, $isExternal, $linkId]
            );
        }

        Logger::log('update', 'site_navigation', 0, ['settings_groups' => array_keys($settingDefinitions), 'nav_rows' => count($postedNav)]);
        Session::flash('status', 'Header, footer and mobile menu controls saved.');
    } catch (Throwable $e) {
        Logger::error('Site navigation save failed', ['error' => $e->getMessage()]);
        Session::flash('error', 'Site navigation update failed: ' . $e->getMessage());
    }

    Response::redirect(Url::to('admin/superadmin/site-navigation.php'));
}

$values = [];
foreach ($settingDefinitions as $group) {
    foreach ($group['items'] as $key => $definition) {
        $values[$key] = CmsSetting::get($key, $definition['fallback'] ?? '');
    }
}

$navigationRows = Database::fetchAll('SELECT * FROM navigation_links ORDER BY area ASC, sort_order ASC, id ASC');
$navigationByArea = [];
foreach ($navigationRows as $row) {
    $navigationByArea[(string)$row['area']][] = $row;
}
$areaLabels = [
    'main' => 'Desktop Header',
    'mobile' => 'Mobile Drawer',
    'footer_public_pages' => 'Footer Public Pages',
    'footer_programme' => 'Footer Programme',
    'legal' => 'Legal Links',
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sn-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-compass" aria-hidden="true"></i> Public chrome</span>
    <h2>Site navigation</h2>
    <p>Control the public header, mobile drawer and footer without changing page content. Ticker items stay managed from News.</p>
  </div>
  <div class="sn-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>"><i class="fa-solid fa-newspaper" aria-hidden="true"></i> Ticker is in News</a>
    <button class="btn btn--primary" type="submit" form="site-navigation-form"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Controls</button>
  </div>
</section>

<form id="site-navigation-form" class="sn-layout" method="post" action="<?= Security::e(Url::to('admin/superadmin/site-navigation.php')) ?>">
  <?= Csrf::field($csrfForm) ?>

  <section class="sn-settings-grid" aria-label="Header footer and mobile settings">
<?php foreach ($settingDefinitions as $groupKey => $group): ?>
    <article class="sn-panel card">
      <header class="card__header">
        <div>
          <h2 class="card__title"><i class="fa-solid <?= Security::e((string)$group['icon']) ?>" aria-hidden="true"></i> <?= Security::e((string)$group['label']) ?></h2>
          <p class="card__subtitle"><?= Security::e((string)$group['description']) ?></p>
        </div>
      </header>
      <div class="sn-fields">
<?php foreach ($group['items'] as $key => $definition): ?>
        <?php sn_render_field($key, $definition, $values[$key] ?? ''); ?>
<?php endforeach; ?>
      </div>
    </article>
<?php endforeach; ?>
  </section>

  <section class="sn-menu-panel card">
    <header class="card__header">
      <div>
        <h2 class="card__title"><i class="fa-solid fa-bars-staggered" aria-hidden="true"></i> Menu Links</h2>
        <p class="card__subtitle">Edit existing menu labels, destinations, order and visibility. No links are deleted from this screen.</p>
      </div>
    </header>

<?php foreach ($navigationByArea as $area => $rows): ?>
    <section class="sn-menu-area" aria-label="<?= Security::e($areaLabels[$area] ?? status_label($area)) ?>">
      <div class="sn-menu-area__head">
        <h3><?= Security::e($areaLabels[$area] ?? status_label($area)) ?></h3>
        <span class="badge badge--neutral"><?= Security::e(format_number(count($rows))) ?> links</span>
      </div>
      <div class="sn-nav-table-wrap">
        <table class="data-table sn-nav-table">
          <thead><tr><th>Visible</th><th>Label</th><th>URL</th><th>Page key</th><th>Order</th><th>External</th></tr></thead>
          <tbody>
<?php foreach ($rows as $row): $id = (int)$row['id']; ?>
            <tr>
              <td><label class="sn-mini-toggle"><input type="checkbox" name="nav[<?= $id ?>][is_visible]" value="1" <?= (int)($row['is_visible'] ?? 0) === 1 ? 'checked' : '' ?>><span></span></label></td>
              <td><input class="form-input" name="nav[<?= $id ?>][label]" value="<?= Security::e((string)$row['label']) ?>" required></td>
              <td><input class="form-input" name="nav[<?= $id ?>][href]" value="<?= Security::e((string)$row['href']) ?>" required></td>
              <td><input class="form-input" name="nav[<?= $id ?>][page_key]" value="<?= Security::e((string)$row['page_key']) ?>"></td>
              <td><input class="form-input sn-order-input" type="number" name="nav[<?= $id ?>][sort_order]" value="<?= Security::e((string)$row['sort_order']) ?>"></td>
              <td><label class="sn-mini-toggle"><input type="checkbox" name="nav[<?= $id ?>][is_external]" value="1" <?= (int)($row['is_external'] ?? 0) === 1 ? 'checked' : '' ?>><span></span></label></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
<?php endforeach; ?>
  </section>

  <div class="sn-savebar">
    <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Saves only existing CMS settings and navigation rows.</span>
    <button class="btn btn--primary btn--lg" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Navigation</button>
  </div>
</form>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function sn_render_field(string $key, array $definition, mixed $value): void
{
    $type = (string)($definition['type'] ?? 'text');
    $label = (string)($definition['label'] ?? status_label($key));
    $safeValue = (string)$value;
    if ($type === 'boolean') {
        $checked = in_array(strtolower($safeValue), ['1', 'true', 'yes', 'on'], true);
?>
        <label class="sn-field sn-field--toggle">
          <span class="sn-field__label"><?= Security::e($label) ?></span>
          <input type="hidden" name="settings[<?= Security::e($key) ?>]" value="0">
          <span class="sn-toggle"><input type="checkbox" name="settings[<?= Security::e($key) ?>]" value="1" <?= $checked ? 'checked' : '' ?>><span></span><em><?= $checked ? 'Visible' : 'Hidden' ?></em></span>
        </label>
<?php
        return;
    }

    if ($type === 'image') {
        $folder = (string)($definition['folder'] ?? 'logos');
        $previewUrl = $safeValue !== '' ? Url::asset($safeValue) : '';
?>
        <div class="sn-field sn-media-field" data-cms-upload data-upload-folder="<?= Security::e($folder) ?>" data-media-kind="image">
          <span class="sn-field__label"><?= Security::e($label) ?></span>
          <input type="hidden" name="settings[<?= Security::e($key) ?>]" value="<?= Security::e($safeValue) ?>" data-cms-upload-target>
          <div class="sn-media-control">
            <div class="sn-media-preview" data-cms-asset-preview>
<?php if ($previewUrl !== ''): ?>
              <img src="<?= Security::e($previewUrl) ?>" alt="" onerror="this.style.display='none'">
<?php else: ?>
              <span><i class="fa-solid fa-image" aria-hidden="true"></i></span>
<?php endif; ?>
            </div>
            <div class="sn-media-meta">
              <strong data-cms-asset-name><?= Security::e($safeValue !== '' ? basename($safeValue) : 'No logo selected') ?></strong>
              <small><?= Security::e($safeValue !== '' ? $safeValue : 'Choose from Media Library') ?></small>
            </div>
            <button class="btn btn--outline btn--sm" type="button" data-media-picker-open data-media-picker-folder="<?= Security::e($folder) ?>" data-media-picker-type="image" data-media-picker-title="Choose logo"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Pick</button>
          </div>
        </div>
<?php
        return;
    }

    if ($type === 'textarea') {
?>
        <label class="sn-field">
          <span class="sn-field__label"><?= Security::e($label) ?></span>
          <textarea class="form-textarea" name="settings[<?= Security::e($key) ?>]" rows="4"><?= Security::e($safeValue) ?></textarea>
        </label>
<?php
        return;
    }
?>
        <label class="sn-field">
          <span class="sn-field__label"><?= Security::e($label) ?></span>
          <input class="form-input" name="settings[<?= Security::e($key) ?>]" value="<?= Security::e($safeValue) ?>">
        </label>
<?php
}