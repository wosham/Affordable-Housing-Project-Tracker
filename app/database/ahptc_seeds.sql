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
-- CONSTITUENCIES (7 in Trans-Nzoia County)
-- ============================================================
INSERT IGNORE INTO `constituencies` (`name`, `slug`, `total_units`, `total_projects`) VALUES
('Cherangany',   'cherangany',   0, 0),
('Saboti',       'saboti',       0, 0),
('Kiminini',     'kiminini',     0, 0),
('Kwanza',       'kwanza',       0, 0),
('Endebess',     'endebess',     0, 0),
('Trans-Nzoia West', 'trans-nzoia-west', 0, 0),
('Trans-Nzoia East', 'trans-nzoia-east', 0, 0);

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
('stat_constituencies', '7',                          'number',  'Constituencies Covered',  'stats'),
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
-- FAQ ITEMS
-- ============================================================
INSERT IGNORE INTO `faq_items` (`sort_order`, `question`, `answer`, `category`, `is_visible`) VALUES
(1, 'What is the Trans-Nzoia Affordable Housing Programme?',
 'The programme is a county government initiative under the national Affordable Housing Programme (AHP) to provide decent, affordable housing to residents of Trans-Nzoia County.',
 'General', 1),
(2, 'Who is eligible to apply for affordable housing units?',
 'Kenyan citizens residing in Trans-Nzoia County who meet the income threshold set by the national government. Priority is given to civil servants, low-income earners, and persons with disabilities.',
 'Eligibility', 1),
(3, 'How can I track the progress of a project near me?',
 'Visit the Projects section on this website and select your constituency to see all projects, their current status, and completion percentage.',
 'General', 1),
(4, 'Who are the key stakeholders in this programme?',
 'The programme involves Trans-Nzoia County Government, the State Department for Housing and Urban Development (SDHUD), contractors, and supervising consultants.',
 'General', 1),
(5, 'How are contractors selected for projects?',
 'Contractors are selected through competitive tendering processes in accordance with the Public Procurement and Asset Disposal Act.',
 'Procurement', 1);

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
