<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'contact';
$cmsPage = CmsLoader::page('contact');

function contact_content(array $page, string $key, array $defaults): array
{
    return CmsLoader::content($page, $key, $defaults);
}

function contact_setting(array $settings, string $key, string $default = ''): string
{
    $value = $settings[$key] ?? $default;
    return trim((string)$value) !== '' ? (string)$value : $default;
}

function contact_tel_href(string $phone): string
{
    $digits = preg_replace('/[^0-9+]/', '', $phone);
    return 'tel:' . $digits;
}

function contact_subject_key(string $name): string
{
    $key = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $name), '_'));
    return $key !== '' ? $key : 'general';
}

$settings = CmsSetting::getGroup('contact');
$hero = contact_content($cmsPage, 'contact_hero', [
    'eyebrow' => 'Get in Touch',
    'title' => "We're Here\nto Help.",
    'subtitle' => 'Reach our county housing team for enquiries about the Affordable Housing Programme - applications, site progress, allocation status, or any other question.',
    'response_value' => '24 hrs',
    'response_label' => 'Response Time',
    'hours_value' => 'Mon - Fri',
    'hours_label' => '8am - 5pm EAT',
    'departments_value' => '4',
    'departments_label' => 'Departments',
]);
$quick = contact_content($cmsPage, 'contact_quick_cards', [
    'phone_label' => 'Call Us',
    'phone_hint' => 'Mon-Fri, 8am-5pm',
    'email_label' => 'Email Us',
    'email_hint' => 'Reply within 24 hours',
    'whatsapp_label' => 'WhatsApp',
    'whatsapp_hint' => 'Quick questions welcome',
    'visit_label' => 'Visit Us',
    'visit_hint' => 'Open in Google Maps',
]);
$formCopy = contact_content($cmsPage, 'contact_form', [
    'title' => 'Send Us a Message',
    'subtitle' => 'Fill in the form below and a member of our team will get back to you within one business day.',
    'name_placeholder' => 'e.g. John Wafula',
    'phone_placeholder' => '07XX XXX XXX',
    'email_placeholder' => 'you@example.com',
    'subject_placeholder' => 'Select a subject...',
    'message_placeholder' => 'Please describe your enquiry in detail...',
    'file_label' => 'Choose file (PDF, JPG, PNG - max 5MB)',
    'privacy_note' => 'Your information is protected under our Privacy Policy and will not be shared with third parties.',
    'submit_label' => 'Send Message',
    'success_title' => 'Message Sent!',
    'success_text' => 'Thank you. We have received your message and will respond within one business day.',
    'error_text' => 'Something went wrong. Please try again or email us directly.',
]);
$office = contact_content($cmsPage, 'contact_office', [
    'office_title' => 'AHP Field Office - Trans-Nzoia',
    'weekday_label' => 'Monday - Friday',
    'weekday_hours' => '8:00am - 5:00pm',
    'saturday_label' => 'Saturday',
    'saturday_hours' => '9:00am - 1:00pm',
    'holiday_label' => 'Sunday & Public Holidays',
    'holiday_hours' => 'Closed',
    'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3986.8!2d35.0062!3d1.0154!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sKitale%2C+Trans-Nzoia!5e0!3m2!1sen!2ske!4v1',
    'map_link_label' => 'Open in Google Maps',
    'helpline_title' => 'National AHB Helpline',
    'helpline_number' => '0800 723 133',
    'helpline_note' => 'Toll-free - Mon-Fri 8am-6pm',
]);
$departmentsCopy = contact_content($cmsPage, 'contact_departments', [
    'eyebrow' => 'Departments',
    'title' => 'Who to Contact',
    'subtitle' => 'Reach the right team directly for faster assistance.',
    'empty_text' => 'Department contacts will appear after they are added to the contact directory.',
]);
$faqBanner = contact_content($cmsPage, 'contact_faq_banner', [
    'title' => 'Have a quick question?',
    'subtitle' => 'Browse our Frequently Asked Questions for instant answers.',
    'pill_1_label' => 'How do I apply?',
    'pill_1_url' => 'faq.php#q-how-apply',
    'pill_2_label' => 'What is the levy?',
    'pill_2_url' => 'faq.php#q-levy-amount',
    'pill_3_label' => 'When do units complete?',
    'pill_3_url' => 'faq.php#q-when-complete',
    'button_label' => 'View All FAQs',
    'button_url' => 'faq.php',
]);

$contactPhone = contact_setting($settings, 'contact_phone', '+254 53 000 0000');
$contactPhoneHref = contact_setting($settings, 'contact_phone_href', contact_tel_href($contactPhone));
$contactEmail = contact_setting($settings, 'contact_email', 'housing@transnzoia.go.ke');
$contactEmailHref = contact_setting($settings, 'contact_email_href', 'mailto:' . $contactEmail);
$whatsapp = contact_setting($settings, 'contact_whatsapp', '0700 000 000');
$whatsappHref = contact_setting($settings, 'contact_whatsapp_href', 'https://wa.me/254700000000');
$officeName = contact_setting($settings, 'contact_office', 'Ardhi House, Kitale');
$officeAddress = contact_setting($settings, 'contact_address', 'Ardhi House, Moi Avenue, Kitale, Trans-Nzoia County');
$postalAddress = contact_setting($settings, 'contact_postal_address', 'P.O. Box 123-30200');
$mapsUrl = contact_setting($settings, 'contact_maps_url', 'https://maps.google.com/?q=Kitale+Trans-Nzoia+County');

try {
    $departments = Database::fetchAll(
        'SELECT name, COALESCE(subject_key, "") AS subject_key, role, email, phone, COALESCE(icon, "fa-building") AS icon, COALESCE(accent, "a") AS accent
         FROM contact_departments
         WHERE is_visible = 1
         ORDER BY sort_order ASC, name ASC'
    );
} catch (Throwable) {
    $departments = [
        ['name' => 'Field Operations', 'subject_key' => 'field_operations', 'role' => 'Construction progress, site visits, contractor oversight', 'email' => 'fieldops@transnzoia.go.ke', 'phone' => '+254 53 000 0001', 'icon' => 'fa-hard-hat', 'accent' => 'a'],
        ['name' => 'Legal & Allocation', 'subject_key' => 'legal_allocation', 'role' => 'Applications, balloting, title deeds, legal enquiries', 'email' => 'legal@transnzoia.go.ke', 'phone' => '+254 53 000 0002', 'icon' => 'fa-scale-balanced', 'accent' => 'b'],
        ['name' => 'Finance & Levy', 'subject_key' => 'finance_levy', 'role' => 'Housing Levy, mortgage, refunds, payment queries', 'email' => 'finance@transnzoia.go.ke', 'phone' => '+254 53 000 0003', 'icon' => 'fa-coins', 'accent' => 'c'],
        ['name' => 'Communications', 'subject_key' => 'communications', 'role' => 'Media, press, events, public announcements', 'email' => 'comms@transnzoia.go.ke', 'phone' => '+254 53 000 0004', 'icon' => 'fa-bullhorn', 'accent' => 'd'],
    ];
}

$pageTitle = $cmsPage['seo_title'] ?? 'Contact Us | Trans-Nzoia AHP Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Contact the Trans-Nzoia Affordable Housing Programme team - phone, email, office location and online enquiry form.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia affordable housing contact, AHP Kenya office, Kitale housing desk, housing enquiry Kenya';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $cmsPage['canonical_url'] ?? 'https://housing.transnzoia.go.ke/contact.php';
$pageStyles = ['assets/css/global.css', 'assets/css/pages/contact.css'];
$pageScripts = ['assets/js/global.js', 'assets/js/pages/contact.js'];
$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
    '<meta property="og:title" content="' . Security::e($pageTitle) . '">',
    '<meta property="og:description" content="' . Security::e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . Security::e($canonicalUrl) . '">',
    '<meta property="og:image" content="' . Security::e($cmsPage['hero_image'] ?? 'uploads/heroes/hero-main.jpg') . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
];
include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?><main id="main-content">

    <section class="ct-hero" aria-label="Contact overview">
      <div class="ct-hero-bg" aria-hidden="true">
        <div class="ct-hero-overlay"></div>
        <div class="ct-hero-dots"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Contact Us</span>
        </nav>
        <div class="ct-hero-body">
          <div class="ct-hero-eyebrow"><i class="fa-solid fa-headset" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($hero, 'eyebrow', 'Get in Touch')) ?></div>
          <h1 class="ct-hero-title"><?= nl2br(Security::e(CmsLoader::text($hero, 'title', "We're Here\nto Help."))) ?></h1>
          <p class="ct-hero-sub"><?= Security::e(CmsLoader::text($hero, 'subtitle', 'Reach our county housing team for enquiries about the Affordable Housing Programme.')) ?></p>
          <div class="ct-hero-stats" role="region" aria-label="Response info">
            <div class="ct-stat-item"><span class="ct-stat-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></span><div><span class="ct-stat-val"><?= Security::e(CmsLoader::text($hero, 'response_value', '24 hrs')) ?></span><span class="ct-stat-lbl"><?= Security::e(CmsLoader::text($hero, 'response_label', 'Response Time')) ?></span></div></div>
            <div class="ct-stat-div" aria-hidden="true"></div>
            <div class="ct-stat-item"><span class="ct-stat-icon"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></span><div><span class="ct-stat-val"><?= Security::e(CmsLoader::text($hero, 'hours_value', 'Mon - Fri')) ?></span><span class="ct-stat-lbl"><?= Security::e(CmsLoader::text($hero, 'hours_label', '8am - 5pm EAT')) ?></span></div></div>
            <div class="ct-stat-div" aria-hidden="true"></div>
            <div class="ct-stat-item"><span class="ct-stat-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span><div><span class="ct-stat-val"><?= Security::e(CmsLoader::text($hero, 'departments_value', (string)count($departments))) ?></span><span class="ct-stat-lbl"><?= Security::e(CmsLoader::text($hero, 'departments_label', 'Departments')) ?></span></div></div>
          </div>
        </div>
      </div>
    </section>

    <section class="ct-cards-strip fade-up" aria-label="Quick contact options">
      <div class="container">
        <div class="ct-cards-grid">
          <a href="<?= Security::e($contactPhoneHref) ?>" class="ct-card" aria-label="Call us">
            <div class="ct-card-icon ct-card-icon--green"><i class="fa-solid fa-phone" aria-hidden="true"></i></div>
            <div class="ct-card-body"><span class="ct-card-label"><?= Security::e(CmsLoader::text($quick, 'phone_label', 'Call Us')) ?></span><span class="ct-card-val"><?= Security::e($contactPhone) ?></span><span class="ct-card-hint"><?= Security::e(CmsLoader::text($quick, 'phone_hint', 'Mon-Fri, 8am-5pm')) ?></span></div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
          <a href="<?= Security::e($contactEmailHref) ?>" class="ct-card" aria-label="Email us">
            <div class="ct-card-icon ct-card-icon--blue"><i class="fa-solid fa-envelope" aria-hidden="true"></i></div>
            <div class="ct-card-body"><span class="ct-card-label"><?= Security::e(CmsLoader::text($quick, 'email_label', 'Email Us')) ?></span><span class="ct-card-val"><?= Security::e($contactEmail) ?></span><span class="ct-card-hint"><?= Security::e(CmsLoader::text($quick, 'email_hint', 'Reply within 24 hours')) ?></span></div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
          <a href="<?= Security::e($whatsappHref) ?>" target="_blank" rel="noopener noreferrer" class="ct-card" aria-label="WhatsApp us">
            <div class="ct-card-icon ct-card-icon--lime"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></div>
            <div class="ct-card-body"><span class="ct-card-label"><?= Security::e(CmsLoader::text($quick, 'whatsapp_label', 'WhatsApp')) ?></span><span class="ct-card-val"><?= Security::e($whatsapp) ?></span><span class="ct-card-hint"><?= Security::e(CmsLoader::text($quick, 'whatsapp_hint', 'Quick questions welcome')) ?></span></div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
          <a href="<?= Security::e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="ct-card" aria-label="Find our office on map">
            <div class="ct-card-icon ct-card-icon--amber"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
            <div class="ct-card-body"><span class="ct-card-label"><?= Security::e(CmsLoader::text($quick, 'visit_label', 'Visit Us')) ?></span><span class="ct-card-val"><?= Security::e($officeName) ?></span><span class="ct-card-hint"><?= Security::e(CmsLoader::text($quick, 'visit_hint', 'Open in Google Maps')) ?></span></div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
        </div>
      </div>
    </section>

    <section class="ct-main" aria-labelledby="ct-main-heading">
      <div class="container">
        <h2 class="sr-only" id="ct-main-heading">Send a message and office information</h2>
        <div class="ct-main-grid">
          <div class="ct-form-wrap fade-up">
            <div class="ct-form-header">
              <h2 class="ct-form-title"><?= Security::e(CmsLoader::text($formCopy, 'title', 'Send Us a Message')) ?></h2>
              <p class="ct-form-sub"><?= Security::e(CmsLoader::text($formCopy, 'subtitle', 'Fill in the form below and a member of our team will get back to you within one business day.')) ?></p>
            </div>
            <form class="ct-form" id="contactForm" novalidate aria-label="Contact form" enctype="multipart/form-data" data-contact-endpoint="<?= Security::e(Url::to('api/public/contact-submit.php')) ?>">
              <input type="text" name="_gotcha" class="ct-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
              <div class="ct-form-row ct-form-row--2col">
                <div class="ct-field-group"><label class="ct-label" for="ctName">Full Name <span class="ct-required" aria-hidden="true">*</span></label><div class="ct-input-wrap"><i class="fa-solid fa-user ct-input-icon" aria-hidden="true"></i><input type="text" id="ctName" name="name" class="ct-input" placeholder="<?= Security::e(CmsLoader::text($formCopy, 'name_placeholder', 'e.g. John Wafula')) ?>" autocomplete="name" required aria-required="true"></div><span class="ct-error" id="ctNameErr" role="alert" aria-live="polite"></span></div>
                <div class="ct-field-group"><label class="ct-label" for="ctPhone">Phone Number</label><div class="ct-input-wrap"><i class="fa-solid fa-phone ct-input-icon" aria-hidden="true"></i><input type="tel" id="ctPhone" name="phone" class="ct-input" placeholder="<?= Security::e(CmsLoader::text($formCopy, 'phone_placeholder', '07XX XXX XXX')) ?>" autocomplete="tel"></div></div>
              </div>
              <div class="ct-field-group"><label class="ct-label" for="ctEmail">Email Address <span class="ct-required" aria-hidden="true">*</span></label><div class="ct-input-wrap"><i class="fa-solid fa-envelope ct-input-icon" aria-hidden="true"></i><input type="email" id="ctEmail" name="email" class="ct-input" placeholder="<?= Security::e(CmsLoader::text($formCopy, 'email_placeholder', 'you@example.com')) ?>" autocomplete="email" required aria-required="true"></div><span class="ct-error" id="ctEmailErr" role="alert" aria-live="polite"></span></div>
              <div class="ct-field-group">
                <label class="ct-label" for="ctSubject">Subject / Department <span class="ct-required" aria-hidden="true">*</span></label>
                <div class="ct-select-wrap"><i class="fa-solid fa-tag ct-input-icon" aria-hidden="true"></i><select id="ctSubject" name="subject" class="ct-select" required aria-required="true"><option value="" disabled selected><?= Security::e(CmsLoader::text($formCopy, 'subject_placeholder', 'Select a subject...')) ?></option><option value="general">General Enquiry</option><?php foreach ($departments as $department): ?><option value="<?= Security::e((string)($department['subject_key'] ?: contact_subject_key($department['name']))) ?>"><?= Security::e($department['name']) ?></option><?php endforeach; ?><option value="other">Other</option></select><i class="fa-solid fa-chevron-down ct-select-arrow" aria-hidden="true"></i></div>
                <span class="ct-error" id="ctSubjectErr" role="alert" aria-live="polite"></span>
              </div>
              <div class="ct-field-group"><label class="ct-label" for="ctMessage">Your Message <span class="ct-required" aria-hidden="true">*</span></label><div class="ct-textarea-wrap"><textarea id="ctMessage" name="message" class="ct-textarea" rows="5" placeholder="<?= Security::e(CmsLoader::text($formCopy, 'message_placeholder', 'Please describe your enquiry in detail...')) ?>" required aria-required="true" maxlength="1000"></textarea></div><div class="ct-char-counter"><span class="ct-error" id="ctMessageErr" role="alert" aria-live="polite"></span><span class="ct-char-count" id="ctCharCount" aria-live="polite">0 / 1000</span></div></div>
              <div class="ct-field-group"><label class="ct-label" for="ctAttachment">Attachment <span class="ct-optional">(optional)</span></label><label class="ct-file-label" for="ctAttachment" id="ctFileLabel"><i class="fa-solid fa-paperclip" aria-hidden="true"></i><span id="ctFileName"><?= Security::e(CmsLoader::text($formCopy, 'file_label', 'Choose file (PDF, JPG, PNG - max 5MB')) ?></span></label><input type="file" id="ctAttachment" name="attachment" class="ct-file-input" accept=".pdf,.jpg,.jpeg,.png" aria-label="Attach a file"><span class="ct-error" id="ctFileErr" role="alert" aria-live="polite"></span></div>
              <div class="ct-form-footer"><p class="ct-privacy-note"><i class="fa-solid fa-shield-check" aria-hidden="true"></i><?= Security::e(CmsLoader::text($formCopy, 'privacy_note', 'Your information is protected under our Privacy Policy and will not be shared with third parties.')) ?> <a href="legal/privacy.php" class="ct-link">Privacy Policy</a></p><button type="submit" class="ct-submit-btn" id="ctSubmitBtn"><span class="ct-submit-text"><?= Security::e(CmsLoader::text($formCopy, 'submit_label', 'Send Message')) ?></span><span class="ct-submit-spinner" aria-hidden="true"><i class="fa-solid fa-circle-notch fa-spin"></i></span><i class="fa-solid fa-paper-plane ct-submit-icon" aria-hidden="true"></i></button></div>
              <div class="ct-form-success" id="ctFormSuccess" hidden aria-live="polite" role="status"><div class="ct-success-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div><h3 class="ct-success-title"><?= Security::e(CmsLoader::text($formCopy, 'success_title', 'Message Sent!')) ?></h3><p class="ct-success-sub"><span data-contact-success-message><?= Security::e(CmsLoader::text($formCopy, 'success_text', 'Thank you. We have received your message and will respond within one business day.')) ?></span></p><button class="ct-success-reset" id="ctSuccessReset" type="button">Send another message</button></div>
              <div class="ct-form-error-state" id="ctFormErrorState" hidden aria-live="polite" role="alert"><div class="ct-error-icon"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i></div><p class="ct-error-msg"><span data-contact-error-message><?= Security::e(CmsLoader::text($formCopy, 'error_text', 'Something went wrong. Please try again or email us directly.')) ?></span> <a href="<?= Security::e($contactEmailHref) ?>" class="ct-link"><?= Security::e($contactEmail) ?></a>.</p></div>
            </form>
          </div>

          <div class="ct-office-wrap fade-up">
            <div class="ct-office-card">
              <div class="ct-office-card-header"><i class="fa-solid fa-building" aria-hidden="true"></i><h3><?= Security::e(CmsLoader::text($office, 'office_title', 'AHP Field Office - Trans-Nzoia')) ?></h3></div>
              <address class="ct-office-address">
                <div class="ct-office-row"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span><?= nl2br(Security::e(str_replace(', ', "\n", $officeAddress))) ?><br><?= Security::e($postalAddress) ?></span></div>
                <div class="ct-office-row"><i class="fa-solid fa-phone" aria-hidden="true"></i><span><a href="<?= Security::e($contactPhoneHref) ?>" class="ct-link"><?= Security::e($contactPhone) ?></a></span></div>
                <div class="ct-office-row"><i class="fa-solid fa-envelope" aria-hidden="true"></i><span><a href="<?= Security::e($contactEmailHref) ?>" class="ct-link"><?= Security::e($contactEmail) ?></a></span></div>
              </address>
              <div class="ct-hours-table" role="table" aria-label="Office hours">
                <div class="ct-hours-row ct-hours-head" role="row"><span role="columnheader">Day</span><span role="columnheader">Hours</span><span role="columnheader">Status</span></div>
                <div class="ct-hours-row" role="row"><span role="cell"><?= Security::e(CmsLoader::text($office, 'weekday_label', 'Monday - Friday')) ?></span><span role="cell"><?= Security::e(CmsLoader::text($office, 'weekday_hours', '8:00am - 5:00pm')) ?></span><span role="cell" class="ct-hours-status ct-hours-open" id="ctWeekdayStatus">Open</span></div>
                <div class="ct-hours-row" role="row"><span role="cell"><?= Security::e(CmsLoader::text($office, 'saturday_label', 'Saturday')) ?></span><span role="cell"><?= Security::e(CmsLoader::text($office, 'saturday_hours', '9:00am - 1:00pm')) ?></span><span role="cell" class="ct-hours-status" id="ctSaturdayStatus">-</span></div>
                <div class="ct-hours-row" role="row"><span role="cell"><?= Security::e(CmsLoader::text($office, 'holiday_label', 'Sunday & Public Holidays')) ?></span><span role="cell"><?= Security::e(CmsLoader::text($office, 'holiday_hours', 'Closed')) ?></span><span role="cell" class="ct-hours-status ct-hours-closed">Closed</span></div>
              </div>
            </div>
            <div class="ct-map-wrap"><div class="ct-map-placeholder" aria-label="Office location map"><iframe src="<?= Security::e(CmsLoader::text($office, 'map_embed_url', '')) ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Trans-Nzoia AHP Field Office location on Google Maps" aria-label="Google Maps showing Kitale, Trans-Nzoia County"></iframe></div><a href="<?= Security::e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="ct-map-link"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($office, 'map_link_label', 'Open in Google Maps')) ?></a></div>
            <div class="ct-ahb-card"><div class="ct-ahb-icon"><i class="fa-solid fa-phone-volume" aria-hidden="true"></i></div><div><p class="ct-ahb-title"><?= Security::e(CmsLoader::text($office, 'helpline_title', 'National AHB Helpline')) ?></p><a href="<?= Security::e(contact_tel_href(CmsLoader::text($office, 'helpline_number', '0800 723 133'))) ?>" class="ct-ahb-number"><?= Security::e(CmsLoader::text($office, 'helpline_number', '0800 723 133')) ?></a><p class="ct-ahb-note"><?= Security::e(CmsLoader::text($office, 'helpline_note', 'Toll-free - Mon-Fri 8am-6pm')) ?></p></div></div>
          </div>
        </div>
      </div>
    </section>

    <section class="ct-depts fade-up" aria-labelledby="ct-depts-heading">
      <div class="container">
        <div class="ct-section-header"><div class="ct-section-eyebrow"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($departmentsCopy, 'eyebrow', 'Departments')) ?></div><h2 class="ct-section-title" id="ct-depts-heading"><?= Security::e(CmsLoader::text($departmentsCopy, 'title', 'Who to Contact')) ?></h2><p class="ct-section-sub"><?= Security::e(CmsLoader::text($departmentsCopy, 'subtitle', 'Reach the right team directly for faster assistance.')) ?></p></div>
        <div class="ct-depts-grid">
          <?php foreach ($departments as $department): ?>
          <?php $accent = preg_match('/^[a-d]$/', (string)$department['accent']) ? (string)$department['accent'] : 'a'; ?>
          <div class="ct-dept-card"><div class="ct-dept-avatar ct-dept-avatar--<?= Security::e($accent) ?>" aria-hidden="true"><i class="fa-solid <?= Security::e($department['icon'] ?: 'fa-building') ?>"></i></div><div class="ct-dept-body"><h3 class="ct-dept-name"><?= Security::e($department['name']) ?></h3><p class="ct-dept-role"><?= Security::e($department['role']) ?></p><div class="ct-dept-contacts"><a href="mailto:<?= Security::e($department['email']) ?>" class="ct-dept-contact"><i class="fa-solid fa-envelope" aria-hidden="true"></i> <?= Security::e($department['email']) ?></a><a href="<?= Security::e(contact_tel_href((string)$department['phone'])) ?>" class="ct-dept-contact"><i class="fa-solid fa-phone" aria-hidden="true"></i> <?= Security::e($department['phone']) ?></a></div></div></div>
          <?php endforeach; ?>
          <?php if (!$departments): ?><p class="ct-section-sub"><?= Security::e(CmsLoader::text($departmentsCopy, 'empty_text', 'Department contacts will appear after they are added to the contact directory.')) ?></p><?php endif; ?>
        </div>
      </div>
    </section>

    <section class="ct-faq-banner fade-up" aria-label="FAQ quick links">
      <div class="container"><div class="ct-faq-banner-wrap"><div class="ct-faq-banner-left"><i class="fa-solid fa-circle-question" aria-hidden="true"></i><div><strong><?= Security::e(CmsLoader::text($faqBanner, 'title', 'Have a quick question?')) ?></strong><span><?= Security::e(CmsLoader::text($faqBanner, 'subtitle', 'Browse our Frequently Asked Questions for instant answers.')) ?></span></div></div><div class="ct-faq-banner-pills"><a href="<?= Security::e(CmsLoader::text($faqBanner, 'pill_1_url', 'faq.php#q-how-apply')) ?>" class="ct-faq-pill"><?= Security::e(CmsLoader::text($faqBanner, 'pill_1_label', 'How do I apply?')) ?></a><a href="<?= Security::e(CmsLoader::text($faqBanner, 'pill_2_url', 'faq.php#q-levy-amount')) ?>" class="ct-faq-pill"><?= Security::e(CmsLoader::text($faqBanner, 'pill_2_label', 'What is the levy?')) ?></a><a href="<?= Security::e(CmsLoader::text($faqBanner, 'pill_3_url', 'faq.php#q-when-complete')) ?>" class="ct-faq-pill"><?= Security::e(CmsLoader::text($faqBanner, 'pill_3_label', 'When do units complete?')) ?></a></div><a href="<?= Security::e(CmsLoader::text($faqBanner, 'button_url', 'faq.php')) ?>" class="ct-faq-btn"><?= Security::e(CmsLoader::text($faqBanner, 'button_label', 'View All FAQs')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div></div>
    </section>

  </main>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
