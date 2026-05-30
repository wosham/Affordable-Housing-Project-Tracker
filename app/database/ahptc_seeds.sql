-- ============================================================
-- AHPTC — Trans-Nzoia Affordable Housing Programme Tracker
-- Seed Data — Roles, Constituencies, Categories, CMS, FAQs etc.
-- IMPORT VIA: phpMyAdmin > Import > Select this file > Go
-- NOTE: Import ahptc_schema.sql FIRST, then this file.
-- NOTE: Admin user is created separately via admin/setup.php
-- ============================================================

USE `trans_nzoia_affordable_housing`;

-- ============================================================
-- ROLES (7 roles)
-- ============================================================
INSERT IGNORE INTO `roles` (`name`, `slug`, `color`) VALUES
('Super Admin',           'superadmin',  '#163300'),
('Programme Manager',     'manager',     '#1a4a00'),
('Supervising Consultant','consultant',  '#0a3d62'),
('Contractor',            'contractor',  '#6a1a00'),
('Clerk of Works',        'clerk',       '#4a3500'),
('Finance Officer',       'finance',     '#1a3a4a'),
('Intern / Site Staff',   'intern',      '#2a2a2a');

-- ============================================================
-- CONSTITUENCIES (5 in Trans-Nzoia County)
-- ============================================================
INSERT IGNORE INTO `constituencies` (`name`, `slug`, `population`, `total_units`, `total_projects`, `avg_completion`, `status`) VALUES
('Cherangany',   'cherangany',   152000, 200, 1, 35, 'active'),
('Saboti',       'saboti',       165000, 1120, 2, 58, 'active'),
('Kiminini',     'kiminini',     138000, 120, 2, 5, 'planning'),
('Kwanza',       'kwanza',       112000, 80, 1, 8, 'planning'),
('Endebess',     'endebess',     104000, 210, 2, 10, 'active');

INSERT IGNORE INTO `wards` (`constituency_id`, `name`, `slug`) VALUES
((SELECT `id` FROM `constituencies` WHERE `slug` = 'saboti' LIMIT 1), 'Matisi', 'matisi'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'saboti' LIMIT 1), 'Tuwan', 'tuwan'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'saboti' LIMIT 1), 'Kinyoro', 'kinyoro'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'saboti' LIMIT 1), 'Bidii', 'bidii'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'saboti' LIMIT 1), 'Township', 'township'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'cherangany' LIMIT 1), 'Matunda', 'matunda'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'cherangany' LIMIT 1), 'Sinyerere', 'sinyerere'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'cherangany' LIMIT 1), 'Kaplamai', 'kaplamai'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'cherangany' LIMIT 1), 'Motosiet', 'motosiet'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'endebess' LIMIT 1), 'Endebess', 'endebess'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'endebess' LIMIT 1), 'Chepchoina', 'chepchoina'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'endebess' LIMIT 1), 'Kapkoi', 'kapkoi'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'endebess' LIMIT 1), 'Metkei', 'metkei'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kiminini' LIMIT 1), 'Kiminini', 'kiminini'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kiminini' LIMIT 1), 'Waitaluk', 'waitaluk'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kiminini' LIMIT 1), 'Sikhendu', 'sikhendu'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kiminini' LIMIT 1), 'Hospital', 'hospital'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kwanza' LIMIT 1), 'Kwanza', 'kwanza'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kwanza' LIMIT 1), 'Keiyo', 'keiyo'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kwanza' LIMIT 1), 'Bidii', 'bidii'),
((SELECT `id` FROM `constituencies` WHERE `slug` = 'kwanza' LIMIT 1), 'Kapomboi', 'kapomboi');

-- ============================================================
-- PROJECT CATEGORIES
-- ============================================================
INSERT IGNORE INTO `project_categories` (`name`, `slug`, `icon`, `color`) VALUES
('Affordable Housing',    'affordable-housing',     'fa-home',       '#163300'),
('Social Infrastructure', 'social-infrastructure',  'fa-hospital',   '#0a3d62'),
('Roads & Drainage',      'roads-drainage',          'fa-road',       '#4a3500'),
('Water & Sanitation',    'water-sanitation',        'fa-tint',       '#1a3a4a'),
('Public Amenities',      'public-amenities',        'fa-building',   '#1a4a00');

-- ============================================================
-- CMS PAGES
-- ============================================================
INSERT IGNORE INTO `cms_pages` (`slug`, `status`, `seo_title`, `seo_description`) VALUES
('home',                 'published', 'Trans-Nzoia Affordable Housing Programme',              'Official home of the Trans-Nzoia County Affordable Housing Programme Tracker'),
('about',                'published', 'About the Programme | AHPTC',                          'Learn about the Trans-Nzoia Affordable Housing Programme goals and leadership'),
('projects',             'published', 'Projects | AHPTC',                                    'Browse all affordable housing projects across Trans-Nzoia County'),
('project-detail',       'published', 'Project Detail | AHPTC',                               'Detailed view of a single housing project'),
('constituencies',       'published', 'Constituencies | AHPTC',                              'View housing projects by constituency in Trans-Nzoia County'),
('constituency-detail',  'published', 'Constituency Detail | AHPTC',                         'All projects within a single constituency'),
('news',                 'published', 'News & Updates | AHPTC',                              'Latest news and updates from the AHPTC programme'),
('news-article',         'published', 'News Article | AHPTC',                                'Full news article'),
('contact',              'published', 'Contact Us | AHPTC',                                  'Get in touch with the AHPTC team'),
('faq',                  'published', 'Frequently Asked Questions | AHPTC',                  'Common questions about the Trans-Nzoia Affordable Housing Programme'),
('gallery',              'published', 'Photo Gallery | AHPTC',                               'Construction progress photos from AHPTC project sites'),
('leadership',           'published', 'Leadership | AHPTC',                                  'Meet the leaders driving the housing programme forward'),
('privacy-policy',       'published', 'Privacy Policy | AHPTC',                              'Privacy policy for the AHPTC website'),
('terms-of-use',         'published', 'Terms of Use | AHPTC',                                'Terms and conditions for using the AHPTC website'),
('disclaimer',           'published', 'Disclaimer | AHPTC',                                  'Legal disclaimer for the AHPTC website');

-- ============================================================
-- CMS SECTIONS (home page)
-- ============================================================
INSERT IGNORE INTO `cms_sections` (`page_id`, `section_key`, `label`, `is_visible`) VALUES
(1, 'hero',      'Hero Banner',       1),
(1, 'ticker',    'News Ticker',       1),
(1, 'stats',     'Statistics Bar',    1),
(1, 'about',     'About Section',     1),
(1, 'projects',  'Featured Projects', 1),
(1, 'map',       'Project Map',       1),
(1, 'news',      'Latest News',       1),
(1, 'gallery',   'Photo Gallery',     1),
(1, 'cta',       'Call to Action',    1);

-- ============================================================
-- CMS SETTINGS (17 site-wide settings)
-- ============================================================
INSERT IGNORE INTO `cms_settings` (`key`, `value`, `type`, `label`, `group`) VALUES
('site_name',           'Trans-Nzoia Affordable Housing Programme Tracker', 'text',    'Site Name',               'global'),
('site_tagline',        'Building Decent, Affordable Homes for Trans-Nzoia Residents', 'text', 'Site Tagline',    'global'),
('contact_email',       'housing@transnzoia.go.ke',   'text',    'Contact Email',           'contact'),
('contact_phone',       '+254 XXX XXX XXX',           'text',    'Contact Phone',           'contact'),
('contact_address',     'Trans-Nzoia County Government Offices, Kitale', 'text', 'Contact Address',       'contact'),
('stat_units_targeted', '4000',                       'number',  'Units Targeted',          'stats'),
('stat_units_completed','0',                          'number',  'Units Completed',         'stats'),
('stat_projects_active','0',                          'number',  'Active Projects',         'stats'),
('stat_constituencies', '5',                          'number',  'Constituencies Covered',  'stats'),
('social_twitter',      '',                           'text',    'Twitter / X URL',         'social'),
('social_facebook',     '',                           'text',    'Facebook URL',            'social'),
('social_youtube',      '',                           'text',    'YouTube URL',             'social'),
('social_instagram',    '',                           'text',    'Instagram URL',           'social'),
('attendance_open_hour',  '7',                        'number',  'Attendance Opens (hour)', 'attendance'),
('attendance_close_hour', '9',                        'number',  'Attendance Closes (hour)','attendance'),
('attendance_radius_m',   '200',                      'number',  'Geo-fence Radius (m)',    'attendance'),
('google_analytics_id',   '',                         'text',    'Google Analytics ID',     'global');

-- ============================================================
-- NEWS CATEGORIES
-- ============================================================
INSERT IGNORE INTO `news_categories` (`name`, `slug`, `color`) VALUES
('Programme Updates', 'programme-updates', '#163300'),
('Site Progress',     'site-progress',     '#1a4a00'),
('Community Stories', 'community-stories', '#0a3d62'),
('Tenders & Notices', 'tenders-notices',   '#6a1a00'),
('Events',            'events',            '#4a3500'),
('Press Releases',    'press-releases',    '#1a3a4a');

-- ============================================================
-- FAQ CATEGORIES + ITEMS
-- ============================================================
INSERT IGNORE INTO `faq_categories` (`name`, `slug`, `icon`, `description`, `sort_order`, `status`) VALUES
('Eligibility',              'eligibility',  'fa-user-check',     'Who qualifies for the programme and what conditions apply.', 10, 'published'),
('Application Process',      'application',  'fa-file-pen',       'Step-by-step guidance on registration and applications.', 20, 'published'),
('Payments & Housing Levy',  'payments',     'fa-coins',          'Housing levy, savings, refunds and repayment questions.', 30, 'published'),
('Unit Allocation',          'allocation',   'fa-house-circle-check', 'How units are assigned, balloted and handed over.', 40, 'published'),
('Construction & Timeline',  'construction', 'fa-helmet-safety',  'Progress updates, timelines and site quality controls.', 50, 'published'),
('Legal & Documents',        'legal',        'fa-scale-balanced', 'Ownership titles, tenancy agreements and legal protections.', 60, 'published'),
('Beneficiary Rights',       'rights',       'fa-shield-halved',  'Applicant rights, complaints and malpractice reporting.', 70, 'published'),
('Contact & Support',        'contact',      'fa-phone',          'How to reach the AHP field office and housing desk.', 80, 'published');

INSERT IGNORE INTO `faq_items` (`category_id`, `slug`, `sort_order`, `question`, `answer`, `category`, `is_popular`, `status`, `search_keywords`, `is_visible`) VALUES
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'eligibility' LIMIT 1), 'who-is-eligible-to-apply-for-affordable-housing-units', 1, 'Who is eligible to apply for an affordable housing unit?',
 '<p>Kenyan citizens who meet the national Affordable Housing Programme requirements may apply through the approved government application portal. County-specific project allocation follows the official beneficiary and unit allocation process.</p>',
 'Eligibility', 1, 'published', 'eligibility applicants requirements', 1),
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'application' LIMIT 1), 'how-do-i-apply-for-an-affordable-housing-unit', 1, 'How do I apply for an affordable housing unit?',
 '<p>Applications are submitted through the approved government portal. Create or sign in to your account, complete your housing profile, choose Trans-Nzoia as your preferred allocation area and follow the portal instructions.</p>',
 'Application Process', 1, 'published', 'apply application portal boma yangu ecitizen', 1),
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'payments' LIMIT 1), 'what-is-the-affordable-housing-levy-and-how-much-is-it', 1, 'What is the Affordable Housing Levy and how much is it?',
 '<p>The levy and savings requirements are administered under the national programme. Applicants should confirm current contribution rules through official government channels before making payments.</p>',
 'Payments & Housing Levy', 1, 'published', 'levy payments savings refund', 1),
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'allocation' LIMIT 1), 'how-are-housing-units-allocated-to-applicants', 1, 'How are housing units allocated to applicants?',
 '<p>Units are allocated through the official national programme process using verified applicant records, project availability and the published allocation rules.</p>',
 'Unit Allocation', 1, 'published', 'allocation ballot units applicants', 1),
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'construction' LIMIT 1), 'when-will-construction-be-completed-and-units-handed-over', 1, 'When will construction be completed and units handed over?',
 '<p>Completion timelines depend on each project programme of works, site progress, contractor performance and statutory approvals. The project pages publish the latest expected delivery dates.</p>',
 'Construction & Timeline', 1, 'published', 'construction completion handover delivery', 1),
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'legal' LIMIT 1), 'will-i-receive-a-title-deed-for-my-unit', 1, 'Will I receive legal ownership documents for my unit?',
 '<p>Ownership and occupation documents are processed under the applicable housing, land and conveyancing rules for the selected unit type.</p>',
 'Legal & Documents', 0, 'published', 'documents title ownership legal', 1),
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'rights' LIMIT 1), 'what-rights-do-i-have-as-an-ahp-applicant', 1, 'What rights do I have as an AHP applicant?',
 '<p>Applicants have the right to transparent information, secure application handling, fair allocation processes and clear channels for complaints or suspected malpractice.</p>',
 'Beneficiary Rights', 0, 'published', 'rights complaints transparency', 1),
((SELECT `id` FROM `faq_categories` WHERE `slug` = 'contact' LIMIT 1), 'where-is-the-ahp-field-office-in-trans-nzoia', 1, 'Where is the AHP field office in Trans-Nzoia?',
 '<p>Use the contact page for the current county housing desk location, telephone, email and working hours.</p>',
 'Contact & Support', 0, 'published', 'contact office support housing desk', 1);

-- ============================================================
-- LEADERSHIP PROFILES
-- ============================================================
INSERT IGNORE INTO `leadership_profiles` (`sort_order`, `name`, `title`, `organisation`, `is_visible`) VALUES
(1, 'H.E. George Natembeya', 'Governor, Trans-Nzoia County',            'Trans-Nzoia County Government',          1),
(2, 'Moses Awuor',           'County Director — Affordable Housing',     'Trans-Nzoia County Government',          1),
(3, 'CS Charles Hinga',      'Cabinet Secretary — Housing & Urban Dev.', 'State Department for Housing',           1);

-- ============================================================
-- STAKEHOLDERS
-- ============================================================
INSERT IGNORE INTO `stakeholders` (`sort_order`, `organisation`, `role`, `is_visible`) VALUES
(1, 'Trans-Nzoia County Government',         'Implementing Agency',         1),
(2, 'State Dept. for Housing & Urban Dev.',  'National Government Partner', 1),
(3, 'Kenya National Housing Corporation',    'Technical Partner',           1),
(4, 'National Housing Corporation (NHC)',    'Housing Developer',           1),
(5, 'World Bank',                            'Development Partner',         1);

-- ============================================================
-- END OF SEEDS
-- Next step: Open localhost/Trans-Nzoia-Affordable-Housing/admin/setup.php
-- to create the Super Admin user account
-- ============================================================
