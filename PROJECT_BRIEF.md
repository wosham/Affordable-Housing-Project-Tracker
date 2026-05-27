# Trans-Nzoia AHP Tracker — Full Project Brief
### For handover to another AI / developer. Read every section. Miss nothing.

---

## 1. WHAT THIS PROJECT IS

**Name:** Trans-Nzoia Affordable Housing Programme Tracker (AHPTC)
**Purpose:** A full-stack web application for Trans-Nzoia County Government (Kenya) to plan, manage, track, and publicly report on the Affordable Housing Programme across 7 constituencies.
**Stack:** PHP 8.1+ (no framework), MySQL/MariaDB, vanilla HTML/CSS/JS (no frontend framework), XAMPP for local dev.
**Base URL (local):** `http://localhost/Trans-Nzoia-Affordable-Housing/`
**Root directory:** `c:\xampp\htdocs\Trans-Nzoia-Affordable-Housing\`

The system has **two sides**:
1. **Public website** — residents/public can view project progress, news, gallery, constituencies, FAQs, leadership, stakeholders, contact
2. **Staff portal (admin)** — county staff log in to manage everything: projects, contractors, IPCs, attendance, documents, CMS, reports, etc.

---

## 2. TECH STACK & KEY RULES

| Layer | Technology |
|---|---|
| Language | PHP 8.1+ (no Laravel/Symfony) |
| Database | MySQL/MariaDB 10.4+, database name: `trans_nzoia_affordable_housing` |
| Frontend | Vanilla HTML, CSS (custom design tokens), vanilla JS |
| Icons | Font Awesome 6.5.1 (CDN) |
| Fonts | Google Fonts: Syne (display/headings), Space Grotesk (UI), Inter (body) |
| Server | Apache via XAMPP (Windows local dev) |
| Sessions | PHP file sessions saved to `app/storage/sessions/` |
| Uploads | `uploads/` (public), `secure-uploads/` (private, not web-accessible) |

**Design tokens (CSS variables) used everywhere — DO NOT hardcode colors:**
```
--lime: #9fe870          (primary accent, buttons, highlights)
--lime-bright: #b8f083
--lime-muted: rgba(159,232,112,.15)
--ink: #050a02           (near-black body text)
--surface-dark: #163300  (dark green — hero backgrounds)
--primary-600: #1e4d00
--primary-700: #163300
--primary-800: #0f2d07
--neutral-50 through --neutral-950 (light to dark grays with green tint)
```

**PHP conventions:**
- No Composer, no autoloader — classes are manually required via `bootstrap.php`
- All DB access via `Database::fetch()`, `Database::fetchAll()`, `Database::query()`
- All redirects via `Response::redirect(Url::to('path/file.php'))`
- All output escaping via `Security::e($value)`
- CSRF tokens: `Csrf::token('form_name')` to generate, `Csrf::verify($token, 'form_name')` to check
- Authentication: `Auth::check()`, `Auth::user()`, `Auth::login($userArray)`, `Auth::logout()`
- Route protection: `Guard::guest()` (redirect logged-in users away), `Guard::auth()` (require login), `Guard::role(['role_slug'])` (require specific role)

---

## 3. HOW TO SET UP (First Run)

```
1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin
3. Import: app/database/ahptc_schema.sql  ← creates all 65 tables
4. Import: app/database/ahptc_seeds.sql   ← inserts roles, constituencies, sample data
5. Go to: http://localhost/Trans-Nzoia-Affordable-Housing/admin/setup.php
6. Enter a super admin password → creates Moses Awuor (director@transnzoia.go.ke)
7. DELETE admin/setup.php immediately after use
8. Login at: http://localhost/Trans-Nzoia-Affordable-Housing/admin/login.php
   Email: director@transnzoia.go.ke
   Password: (what you set in setup.php)
   OR if seeds used default: Admin@1234
```

Alternatively, run PHP migrations + seeds via CLI:
```
php app/database/migrate.php
php app/database/seed.php
```

---

## 4. COMPLETE FOLDER STRUCTURE

```
Trans-Nzoia-Affordable-Housing/         ← ROOT (web root subfolder in htdocs)
│
├── index.php                           ← Public homepage
├── about.php                           ← About the programme
├── projects.php                        ← Project listing (all constituencies)
├── project-detail.php                  ← Single project view (?id=X)
├── constituencies.php                  ← 7 constituencies overview
├── constituency-detail.php             ← Single constituency (?slug=X)
├── news.php                            ← News & announcements listing
├── news-article.php                    ← Single news article (?id=X)
├── gallery.php                         ← Photo gallery
├── contact.php                         ← Contact form + office info
├── faq.php                             ← Frequently asked questions
├── leadership.php                      ← County leadership profiles
├── stakeholders.php                    ← Programme stakeholders
├── sitemap.php                         ← HTML site map / page directory
├── 404.php                             ← Custom 404 error page
├── 500.php                             ← Custom 500 error page
├── maintenance.php                     ← Maintenance mode page
├── offline.php                         ← PWA offline fallback (stub)
├── robots.txt                          ← Search engine crawl rules
├── sitemap.xml                         ← XML sitemap for search engines
├── .htaccess                           ← Apache rewrites, error pages, security headers
│
├── admin/                              ← STAFF PORTAL (all protected)
│   ├── .htaccess                       ← Blocks direct access to PHP, enforces HTTPS
│   ├── index.php                       ← Router: redirects logged-in user to their role dashboard
│   ├── login.php                       ← Login page (full UI + PHP bootstrap + CSRF)
│   ├── logout.php                      ← Destroys session, redirects to login
│   ├── setup.php                       ← One-time super admin creation (DELETE AFTER USE)
│   │
│   ├── api/
│   │   └── login.php                   ← POST handler for login form → returns JSON
│   │
│   ├── auth/
│   │   ├── forgot-password.php         ← Forgot password page (full UI)
│   │   ├── reset-password.php          ← Reset password page (full UI, token from URL)
│   │   └── unauthorised.php            ← 403 access denied page
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   ├── admin-global.css        ← Admin-wide CSS variables, layout, cards, buttons, badges
│   │   │   ├── login.css               ← Login page styles (two-column layout)
│   │   │   ├── forgot-password.css     ← Forgot password page styles
│   │   │   ├── reset-password.css      ← Reset password page styles (strength meter)
│   │   │   ├── unauthorised.css        ← 403 page styles (countdown ring, SVG)
│   │   │   ├── dashboard-superadmin.css
│   │   │   ├── dashboard-manager.css
│   │   │   ├── dashboard-consultant.css
│   │   │   ├── dashboard-contractor.css
│   │   │   ├── dashboard-clerk.css
│   │   │   ├── dashboard-finance.css
│   │   │   ├── dashboard-intern.css
│   │   │   └── components/
│   │   │       ├── attendance.css      ← Attendance gateway + sign-in UI
│   │   │       ├── cards.css           ← Stat cards, project cards
│   │   │       ├── charts.css          ← Chart wrappers
│   │   │       ├── forms.css           ← Form fields, validation states
│   │   │       ├── gantt.css           ← Gantt chart / programme of works
│   │   │       ├── media-library.css   ← File/image picker grid
│   │   │       ├── messages.css        ← Internal messaging UI
│   │   │       ├── modals.css          ← Modal dialogs
│   │   │       └── tables.css          ← Data tables, pagination
│   │   └── js/
│   │       ├── admin-global.js         ← Sidebar toggle, mobile nav, global admin JS
│   │       ├── login.js                ← Login form → fetch → api/login.php
│   │       ├── forgot-password.js      ← Forgot password form + resend timer
│   │       ├── reset-password.js       ← Reset password: strength meter, requirements, countdown
│   │       ├── unauthorised.js         ← 403 page: reason banner, SVG countdown ring
│   │       ├── attendance-gateway.js   ← Open/close attendance gateway
│   │       ├── attendance-signin.js    ← Geofenced sign-in form
│   │       ├── charts.js               ← Chart.js wrappers for dashboard charts
│   │       ├── cms-editor.js           ← Rich text / CMS page editor
│   │       ├── data-tables.js          ← Sortable, filterable data tables
│   │       ├── file-uploader.js        ← Drag-and-drop file uploader
│   │       ├── gantt.js                ← Gantt / programme of works chart
│   │       ├── ipc-form.js             ← IPC submission form multi-step
│   │       ├── media-library.js        ← Media library picker
│   │       ├── messages.js             ← Real-time-style messaging UI
│   │       ├── notifications.js        ← Notification bell + dropdown
│   │       └── report-builder.js       ← Report filter + export UI
│   │
│   ├── superadmin/                     ← 31 pages (see Section 8.1)
│   ├── manager/                        ← 16 pages (see Section 8.2)
│   ├── consultant/                     ← 16 pages (see Section 8.3)
│   ├── contractor/                     ← 20 pages (see Section 8.4)
│   ├── clerk/                          ← 18 pages (see Section 8.5)
│   ├── finance/                        ← 8 pages (see Section 8.6)
│   └── intern/                         ← 7 pages (see Section 8.7)
│
├── api/                                ← JSON REST API endpoints (called by admin JS)
│   ├── attendance/
│   ├── boq/
│   ├── cms/
│   ├── ipcs/
│   ├── media/
│   ├── messages/
│   ├── notifications/
│   ├── programme/
│   ├── projects/
│   ├── public/
│   └── reports/
│
├── app/                                ← Backend application code
│   ├── auth/
│   │   ├── auth-guard.php              ← Include at top of protected pages: Guard::auth()
│   │   └── guest-guard.php             ← Include at top of login page: Guard::guest()
│   ├── config/
│   │   ├── app.php                     ← App name, env, timezone, session config, security
│   │   ├── database.php                ← DB host/name/user/pass/charset/PDO options
│   │   ├── mail.php                    ← SMTP config for password reset emails
│   │   └── paths.php                   ← Absolute paths to key directories
│   ├── core/
│   │   ├── bootstrap.php               ← Entry point: loads config, starts session, includes all core classes
│   │   ├── Auth.php                    ← login(), logout(), check(), user(), id(), hasRole()
│   │   ├── Csrf.php                    ← token(), verify(), field() — form CSRF protection
│   │   ├── Database.php                ← PDO singleton: connection(), query(), fetch(), fetchAll()
│   │   ├── Guard.php                   ← guest(), auth(), role() — redirect guards
│   │   ├── Logger.php                  ← File-based error/audit logging
│   │   ├── Mailer.php                  ← PHPMailer wrapper for sending emails
│   │   ├── Model.php                   ← Base model with find(), all(), create(), update(), delete()
│   │   ├── Paginator.php               ← Pagination helper
│   │   ├── Response.php                ← redirect(), json(), abort() — all HTTP responses
│   │   ├── Security.php                ← e() XSS escape, sendHeaders(), isSafeRedirect(), extensionAllowed()
│   │   ├── session.php                 ← Session class: start(), get(), set(), flash(), regenerate(), destroy()
│   │   ├── Url.php                     ← to(), basePath(), asset() — URL generation
│   │   ├── Validator.php               ← Input validation rules
│   │   ├── Uploader.php                ← File upload handler
│   │   ├── CmsLoader.php               ← Loads CMS page/section/setting content from DB
│   │   ├── Controller.php              ← Base controller (currently minimal)
│   │   ├── GeoFence.php                ← Geofencing checks for attendance sign-in
│   │   ├── helpers.php                 ← Global helper functions (nav_active_class etc.)
│   │   └── Paginator.php
│   ├── database/
│   │   ├── ahptc_schema.sql            ← IMPORT FIRST: creates all 65 tables
│   │   ├── ahptc_seeds.sql             ← IMPORT SECOND: default roles, constituencies, categories, sample data
│   │   ├── migrate.php                 ← CLI runner: executes all migrations/ in order
│   │   ├── seed.php                    ← CLI runner: executes all seeds/ in order
│   │   ├── migrations/                 ← 65 individual table migration files (001–065)
│   │   └── seeds/                      ← 15 seeder classes
│   ├── helpers/
│   │   └── (helper functions)
│   ├── middleware/
│   │   ├── ApiMiddleware.php           ← Sets JSON header, runs CSRF check on POST/PUT/DELETE
│   │   ├── AuthMiddleware.php          ← Redirects to login if not authenticated
│   │   ├── CsrfMiddleware.php          ← Verifies CSRF token, aborts 419 if invalid
│   │   └── RoleMiddleware.php          ← Checks role, redirects to unauthorised if wrong role
│   ├── models/                         ← 65 model classes (one per table) — see Section 5
│   ├── partials/                       ← PHP includes for public site layout
│   │   ├── navbar.php                  ← Top navigation bar (all public pages)
│   │   ├── mobile-menu.php             ← Mobile slide-out menu
│   │   ├── footer.php                  ← Site footer
│   │   ├── ticker.php                  ← News ticker strip below navbar
│   │   ├── head.php                    ← <head> tag contents (meta, CSS links)
│   │   └── (other shared partials)
│   ├── storage/
│   │   ├── sessions/                   ← PHP session files
│   │   ├── logs/                       ← Application logs
│   │   └── cache/                      ← Cache files
│   └── data/
│       └── (static JSON data files)
│
├── assets/                             ← PUBLIC frontend CSS + JS
│   ├── css/
│   │   ├── global.css                  ← MASTER stylesheet: tokens, reset, typography, navbar, footer, components
│   │   ├── home.css                    ← Homepage specific styles
│   │   ├── projects.css
│   │   ├── project-detail.css
│   │   ├── constituencies.css
│   │   ├── constituency-detail.css
│   │   ├── news.css
│   │   ├── news-article.css
│   │   ├── gallery.css
│   │   ├── contact.css
│   │   ├── faq.css
│   │   ├── leadership.css
│   │   ├── stakeholders.css
│   │   ├── about.css
│   │   ├── sitemap.css
│   │   ├── 404.css
│   │   └── legal.css
│   └── js/
│       ├── global.js                   ← Custom cursor, mobile menu toggle, scroll effects, lazy loading
│       ├── home.js
│       ├── projects.js
│       └── (page-specific JS files)
│
├── legal/
│   ├── privacy.php                     ← Privacy Policy
│   ├── terms.php                       ← Terms of Use
│   └── disclaimer.php                  ← Legal Disclaimer
│
├── uploads/
│   ├── logos/                          ← Site logos (afforadablehousinglogo.png)
│   └── (user-uploaded public files)
│
└── secure-uploads/                     ← Private uploads (not web-accessible, protected by .htaccess)
```

---

## 5. DATABASE — ALL 65 TABLES

Database name: `trans_nzoia_affordable_housing`
MariaDB 10.4+, charset utf8mb4_unicode_ci

### SETUP FILES
| File | Purpose |
|---|---|
| `app/database/ahptc_schema.sql` | Creates all 65 tables. Import this FIRST in phpMyAdmin. |
| `app/database/ahptc_seeds.sql` | Inserts default data: 7 roles, 7 constituencies, categories, CMS pages, FAQs, leadership, settings. Import SECOND. |
| `app/database/migrate.php` | CLI alternative: `php migrate.php` runs all migration files in order |
| `app/database/seed.php` | CLI alternative: `php seed.php` runs all seeders |

### TABLE REFERENCE (all 65)

#### AUTH & USERS
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 1 | `roles` | User roles/permissions | id, name, slug, color, permissions_json |
| 2 | `users` | All staff accounts | id, first_name, last_name, email, phone, password_hash, avatar, role_id, status, last_login |
| 3 | `user_role_assignments` | Project-specific role overrides | id, user_id, role_id, project_id, assigned_by |
| 4 | `user_sessions` | Remember-me tokens | id, user_id, token, ip, last_activity, expires_at |
| 5 | `password_resets` | Password reset tokens | id, user_id, token, expires_at, used |
| 6 | `audit_logs` | All user actions log | id, user_id, action, module, target_id, details_json, ip, created_at |

#### GEOGRAPHY
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 7 | `announcements` | County-wide announcements | id, title, body, type, is_pinned, published_at |
| 8 | `constituencies` | 7 Trans-Nzoia constituencies | id, name, slug, description, mp_name, total_units_target, image_url |
| 9 | `wards` | Sub-divisions of constituencies | id, constituency_id, name, slug |
| 10 | `geo_fences` | GPS boundaries for attendance | id, project_id, lat, lng, radius_meters, is_active |

#### PROJECTS (CORE)
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 11 | `project_categories` | Category tags for projects | id, name, slug, color, icon |
| 12 | `projects` | Main projects table | id, name, slug, constituency_id, category_id, status, progress_pct, contractor_name, contract_value, start_date, expected_completion, description, featured_image, location_lat, location_lng |
| 13 | `project_assignments` | Which staff are on which project | id, project_id, user_id, role, assigned_by, assigned_at |
| 14 | `milestones` | Project milestones/targets | id, project_id, title, description, target_date, completed_at, status |
| 15 | `subcontractors` | Sub-contractors on a project | id, project_id, company_name, contact_person, contact_email, trade, status |

#### SITE DOCUMENTS & DRAWINGS
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 16 | `documents` | Contract docs, reports, drawings | id, project_id, uploaded_by, title, file_path, file_type, document_type, version |
| 17 | `boq_items` | Bill of Quantities line items | id, project_id, description, unit, quantity, unit_rate, total_amount, category, status |
| 18 | `programme_tasks` | Programme of Works tasks | id, project_id, task_name, planned_start, planned_end, actual_start, actual_end, progress_pct, depends_on_task_id |
| 19 | `equipment_register` | Site equipment/machinery | id, project_id, name, type, serial_number, status, last_inspection |
| 32 | `shop_drawings` | Submitted shop drawings | id, project_id, submitted_by, title, drawing_number, revision, status, reviewed_by |
| 42 | `rfis` | Requests for Information | id, project_id, raised_by, subject, description, status, response, responded_by |

#### MATERIALS
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 20 | `material_deliveries` | Material deliveries to site | id, project_id, recorded_by, material_name, quantity, unit, supplier, delivery_date, status |
| 21 | `material_approvals` | Material approval requests | id, project_id, submitted_by, material_name, specification, status, reviewed_by, decision_notes |

#### SITE RECORDS
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 22 | `labour_register` | Daily labour on site | id, project_id, recorded_by, date, total_workers, skilled_count, unskilled_count, notes |
| 23 | `site_diaries` | Daily site diary entries | id, project_id, recorded_by, date, weather, work_done, issues, visitors |
| 24 | `weather_logs` | Weather records | id, project_id, recorded_by, date, condition, temperature_c, rainfall_mm |
| 25 | `site_meeting_minutes` | Site meeting records | id, project_id, recorded_by, meeting_date, attendees_json, agenda, minutes, action_items_json |
| 26 | `community_liaison` | Community engagement logs | id, project_id, recorded_by, date, type, description, outcome |

#### HEALTH, SAFETY & QUALITY
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 27 | `hs_incidents` | Health & safety incidents | id, project_id, reported_by, date, type, description, severity, action_taken |
| 28 | `environmental_logs` | Environmental impact logs | id, project_id, recorded_by, date, type, description, mitigation |
| 29 | `quality_tests` | QA tests and results | id, project_id, conducted_by, date, test_type, specimen, result, passed |
| 30 | `inspection_test_plans` | ITP tracking | id, project_id, created_by, element, test_required, hold_point, witness_point, status |
| 31 | `non_conformance_reports` | NCRs raised on site | id, project_id, raised_by, ncr_number, description, root_cause, corrective_action, status |
| 33 | `defects` | Defects register | id, project_id, raised_by, description, location, severity, status, resolved_by, resolved_at |

#### INTERIM PAYMENT CERTIFICATES (IPC)
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 34 | `ipcs` | IPC header records | id, project_id, ipc_number, submitted_by, period_from, period_to, gross_amount, retention_pct, net_amount, status (draft/submitted/certified/endorsed/approved/paid/rejected) |
| 35 | `ipc_lines` | Line items within an IPC | id, ipc_id, boq_item_id, description, quantity_this_period, amount_this_period |
| 36 | `ipc_approvals` | IPC approval workflow steps | id, ipc_id, approver_id, role, action (certify/endorse/approve/reject), comment, actioned_at |

#### FINANCIAL
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 37 | `variations` | Contract variation orders | id, project_id, submitted_by, variation_number, description, amount, status |
| 38 | `eot_requests` | Extension of Time requests | id, project_id, submitted_by, days_requested, reason, status, approved_days |
| 39 | `liquidated_damages` | LD calculations | id, project_id, rate_per_day, days_in_delay, total_amount, status |
| 40 | `payments` | Payment records | id, project_id, ipc_id, amount, payment_date, reference, status |
| 41 | `retention` | Retention tracking | id, project_id, total_retention_held, released_amount, remaining, release_date |

#### ATTENDANCE
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 43 | `attendance_gateways` | Daily open/close of sign-in | id, project_id, opened_by, date, is_open, opened_at, closed_at |
| 44 | `attendance_records` | Individual sign-in records | id, gateway_id, user_id, signed_in_at, lat, lng, within_fence, verification_method |

#### MESSAGING
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 45 | `message_threads` | Conversation threads | id, title, project_id, created_by, type (direct/group/project) |
| 46 | `messages` | Individual messages | id, thread_id, sender_id, body, sent_at |
| 47 | `message_participants` | Thread members | id, thread_id, user_id, joined_at |
| 48 | `message_reads` | Read receipts | id, message_id, user_id, read_at |
| 49 | `message_attachments` | Files attached to messages | id, message_id, file_path, file_name, file_type |
| 50 | `project_channels` | Auto-channels per project | id, project_id, thread_id |

#### MEDIA & CMS
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 51 | `media_library` | All uploaded media files | id, uploaded_by, file_path, file_name, file_type, title, alt_text, created_at |
| 52 | `cms_pages` | Editable public pages | id, slug, title, status, last_edited_by |
| 53 | `cms_sections` | Content sections within a page | id, page_id, section_key, content_html, updated_at |
| 54 | `cms_settings` | Site-wide settings (key-value) | id, key, value, type, label, group |

#### PUBLIC CONTENT
| # | Table | Purpose | Key Columns |
|---|---|---|---|
| 55 | `news_categories` | News category taxonomy | id, name, slug, color |
| 56 | `news_tags` | Tags for news articles | id, name, slug |
| 57 | `news_articles` | News & announcements | id, title, slug, excerpt, body_html, featured_image, category_id, author_id, status, published_at |
| 58 | `news_article_tags` | Many-to-many: articles↔tags | article_id, tag_id |
| 59 | `gallery_categories` | Gallery categories | id, name, slug, description |
| 60 | `gallery_images` | Gallery photos | id, category_id, uploaded_by, title, caption, file_path, project_id, is_featured |
| 61 | `faq_items` | Public FAQ entries | id, question, answer_html, category, sort_order, is_published |
| 62 | `leadership_profiles` | County leadership | id, name, title, bio, photo_url, sort_order, is_active |
| 63 | `stakeholders` | Programme stakeholders | id, name, organization, role, description, logo_url, website, sort_order |
| 64 | `contact_submissions` | Contact form submissions | id, name, email, phone, subject, message, ip, created_at, is_read |
| 65 | `subscribers` + `notifications` | Newsletter subscribers + in-app notifications | (in migration 065) |

---

## 6. AUTHENTICATION & ROLES

### How Login Works (Step by Step)
```
1. User visits admin/login.php
   → PHP bootstrap runs (bootstrap.php)
   → Guard::guest() — if already logged in, redirect to admin/index.php
   → CSRF token generated: Csrf::token('login')
   → Token embedded in <meta name="csrf-token"> in HTML

2. User fills email + password, clicks Sign In
   → login.js reads CSRF from meta tag
   → fetch('api/login.php', POST JSON {email, password, _csrf_token})

3. admin/api/login.php receives request:
   → Verifies CSRF: Csrf::verify($csrf, 'login')
   → Queries: SELECT users JOIN roles WHERE email=? AND status='active'
   → password_verify($password, $user['password_hash'])
   → Auth::login($user) — stores {id, name, email, role slug} in $_SESSION['auth_user']
   → Returns JSON {success: true, redirect: '/Trans-Nzoia-Affordable-Housing/admin/index.php'}

4. login.js receives success → window.location.href = data.redirect

5. admin/index.php:
   → Guard::auth() — must be logged in
   → Reads Auth::user()['role']
   → Redirects to role-specific dashboard:
     superadmin → admin/superadmin/dashboard.php
     manager    → admin/manager/dashboard.php
     ...etc
```

### Protecting Pages
Every admin PHP page MUST start with:
```php
<?php
require_once dirname(__DIR__, N) . '/app/core/bootstrap.php';
// Replace N with correct level count (e.g. 2 from admin/superadmin/, 1 from admin/)
Guard::auth();           // Must be logged in
// OR
Guard::role(['superadmin']); // Must be logged in AND have this role
```

### The 7 Roles (role slug → what they do)

| Slug | Name | Access Level |
|---|---|---|
| `superadmin` | Super Administrator | Full system access — CMS, users, all projects, all reports, settings, audit log |
| `manager` | Project Manager | Manages specific projects — milestones, BOQ, attendance summary, IPCs queue, reports |
| `consultant` | Resident Engineer / Consultant | Reviews/certifies IPCs, shop drawings, NCRs, quality, defects, variation review |
| `contractor` | Contractor | Submits IPCs, progress updates, material approvals, RFIs, EOTs, shop drawings |
| `clerk` | Site Clerk | Records site diaries, weather, labour, equipment, incidents, attendance gateway |
| `finance` | Finance Officer | Processes IPC payments, budget tracking, retention, LD, financial reports |
| `intern` | Site Intern | Limited — sign in/out, view their project, basic data entry, messages, upload photos |

---

## 7. PUBLIC FRONTEND PAGES

All public pages include partials from `app/partials/`:
- `navbar.php` — Staff Portal link → `admin/login.php`
- `mobile-menu.php` — responsive hamburger menu
- `footer.php` — links, social, copyright
- `ticker.php` — scrolling news/announcements strip

| File | Purpose | Data Source |
|---|---|---|
| `index.php` | Homepage: hero, stats, featured projects, news preview, constituency map, CTA | DB: projects, news_articles, constituencies |
| `about.php` | Programme overview, objectives, timeline, partners | DB: cms_sections (slug: about), stakeholders |
| `projects.php` | Project listing with filters by constituency/status/category | DB: projects + constituencies + project_categories |
| `project-detail.php?id=X` | Full project page: progress, milestones, gallery, documents, BOQ summary | DB: projects, milestones, gallery_images |
| `constituencies.php` | 7 constituency cards with unit targets and status | DB: constituencies |
| `constituency-detail.php?slug=X` | Single constituency: projects, wards, leadership | DB: constituencies, wards, projects |
| `news.php` | News listing with category filter | DB: news_articles, news_categories |
| `news-article.php?id=X` | Full article: body, author, tags, related articles | DB: news_articles, news_tags |
| `gallery.php` | Photo gallery with category filter | DB: gallery_images, gallery_categories |
| `contact.php` | Contact form (submits to contact_submissions table) + office info | DB: INSERT contact_submissions |
| `faq.php` | Accordion FAQ by category | DB: faq_items |
| `leadership.php` | County leadership profiles (grid) | DB: leadership_profiles |
| `stakeholders.php` | Programme stakeholders (grid with logos) | DB: stakeholders |
| `sitemap.php` | Full HTML page directory | Static HTML |
| `legal/privacy.php` | Privacy policy | DB: cms_sections OR static |
| `legal/terms.php` | Terms of use | DB: cms_sections OR static |
| `legal/disclaimer.php` | Legal disclaimer | DB: cms_sections OR static |

---

## 8. ADMIN DASHBOARD PAGES (by role)

### 8.1 SUPERADMIN — `admin/superadmin/` (31 pages)
| File | Purpose |
|---|---|
| `dashboard.php` | Overview stats: projects, users, IPCs pending, recent activity |
| `projects.php` | All projects list |
| `project-create.php` | Create new project |
| `project-edit.php?id=X` | Edit project details |
| `users.php` | All users list |
| `user-create.php` | Create new staff account |
| `user-edit.php?id=X` | Edit user / change role |
| `announcements.php` | Manage site announcements |
| `attendance.php` | View all attendance records |
| `audit-log.php` | Full system audit trail |
| `approvals.php` | IPC/variation/EOT approval queue |
| `boq.php` | BOQ overview across all projects |
| `cms.php` | CMS pages list |
| `cms-page-editor.php?slug=X` | Edit a CMS page (rich text) |
| `contact-inbox.php` | View contact form submissions |
| `faq.php` | Manage public FAQs |
| `financials.php` | Financial overview (payments, retention, LD) |
| `gallery.php` | Manage gallery images |
| `ipcs.php` | All IPCs across all projects |
| `leadership.php` | Manage leadership profiles |
| `media-library.php` | All uploaded files |
| `messages.php` | Internal messages |
| `news.php` | News articles list |
| `news-editor.php?id=X` | Create/edit news article |
| `programme-of-works.php` | Programme tasks (Gantt) |
| `reports.php` | Generate reports |
| `analytics.php` | Charts: progress, spend, attendance trends |
| `settings.php` | System settings (from cms_settings table) |
| `stakeholders.php` | Manage stakeholder profiles |
| `subscribers.php` | Newsletter subscriber list |
| `system-health.php` | PHP version, DB status, disk space, error log |

### 8.2 MANAGER — `admin/manager/` (16 pages)
| File | Purpose |
|---|---|
| `dashboard.php` | Project summary stats for assigned projects |
| `projects.php` | Assigned projects list |
| `milestones.php` | Milestone tracking for projects |
| `boq.php` | BOQ review |
| `assignments.php` | Staff assignments to projects |
| `attendance-summary.php` | Attendance summary reports |
| `ipc-queue.php` | IPCs waiting for manager review |
| `programme-of-works.php` | Gantt chart for projects |
| `site-meeting-minutes.php` | View meeting minutes |
| `hs-incidents.php` | Safety incident reports |
| `community-liaison.php` | Community engagement logs |
| `eot-requests.php` | Extension of time requests |
| `liquidated-damages.php` | LD calculations |
| `subcontractors.php` | Subcontractor register |
| `reports.php` | Project progress reports |
| `messages.php` | Internal messages |

### 8.3 CONSULTANT — `admin/consultant/` (16 pages)
| File | Purpose |
|---|---|
| `dashboard.php` | Pending reviews summary |
| `ipc-inbox.php` | IPCs submitted by contractor awaiting certification |
| `ipc-certify.php?id=X` | Certify/reject an IPC |
| `boq-review.php` | Review BOQ items |
| `defects.php` | Defects register |
| `documents.php` | Project documents |
| `eot-review.php` | Review EOT requests |
| `inspection-test-plans.php` | ITP management |
| `material-approvals.php` | Approve/reject material submissions |
| `non-conformance.php` | NCR management |
| `programme-review.php` | Programme of works review |
| `quality-register.php` | Quality tests register |
| `shop-drawings.php` | Shop drawing review |
| `site-reports.php` | Site diary and weather reports |
| `variations.php` | Variation order review |
| `messages.php` | Internal messages |

### 8.4 CONTRACTOR — `admin/contractor/` (20 pages)
| File | Purpose |
|---|---|
| `dashboard.php` | Contractor's project summary |
| `my-project.php` | View assigned project details |
| `progress-update.php` | Submit progress % update |
| `ipc-submit.php` | Create and submit new IPC |
| `ipc-history.php` | View all their IPCs + statuses |
| `boq.php` | View BOQ |
| `documents.php` | Upload/view project documents |
| `equipment-register.php` | Register site equipment |
| `eot-request.php` | Submit extension of time request |
| `hs-incidents.php` | Report health & safety incidents |
| `labour-register.php` | Submit daily labour records |
| `material-approval-submit.php` | Submit material for approval |
| `material-deliveries.php` | Log material deliveries |
| `payment-history.php` | View payment history |
| `programme-of-works.php` | View programme tasks |
| `rfis.php` | Submit / view RFIs |
| `shop-drawing-submit.php` | Upload shop drawings |
| `subcontractors.php` | Manage subcontractors |
| `variation-request.php` | Submit variation request |
| `messages.php` | Internal messages |

### 8.5 CLERK — `admin/clerk/` (18 pages)
| File | Purpose |
|---|---|
| `dashboard.php` | Today's tasks summary |
| `attendance-gateway.php` | Open/close daily attendance window |
| `attendance-live.php` | Real-time view of who has signed in |
| `daily-diary.php` | Write daily site diary entry |
| `defects.php` | Raise defect reports |
| `documents.php` | Upload documents |
| `equipment-check.php` | Equipment inspection log |
| `hs-incidents.php` | Record safety incidents |
| `inspection-test-plans.php` | Record ITP results |
| `ipc-verify.php` | Verify IPC quantities on site |
| `labour-verification.php` | Verify daily labour numbers |
| `material-delivery-log.php` | Log material arrivals |
| `non-conformance.php` | Raise NCRs |
| `photos.php` | Upload site photos |
| `quality-tests.php` | Record quality test results |
| `site-meeting-minutes.php` | Record meeting minutes |
| `weather-log.php` | Record daily weather |
| `messages.php` | Internal messages |

### 8.6 FINANCE — `admin/finance/` (8 pages)
| File | Purpose |
|---|---|
| `dashboard.php` | Financial overview stats |
| `approved-ipcs.php` | IPCs approved, ready for payment |
| `process-payment.php?ipc=X` | Record payment against an IPC |
| `budget-tracker.php` | Contract vs spend tracking |
| `financial-reports.php` | Export financial reports |
| `liquidated-damages.php` | Manage LD records |
| `retention.php` | Retention holding + release |
| `messages.php` | Internal messages |

### 8.7 INTERN — `admin/intern/` (7 pages)
| File | Purpose |
|---|---|
| `dashboard.php` | Welcome screen with assignment info |
| `sign-in.php` | Geofenced GPS attendance sign-in |
| `my-attendance.php` | View own attendance history |
| `my-project.php` | View assigned project details (read-only) |
| `site-data-entry.php` | Basic data entry (materials, weather) |
| `upload-photos.php` | Upload site photos |
| `messages.php` | Internal messages |

---

## 9. API ENDPOINTS — `api/` folder

All API files return JSON. Most require bootstrap + AuthMiddleware. POST/PUT/DELETE require CSRF via `ApiMiddleware`.

Pattern for every API file:
```php
<?php
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
ApiMiddleware::handle();   // Sets JSON header, checks CSRF
AuthMiddleware::handle();  // Must be logged in
// ... handle the request
Response::json([...]);
```

### `api/projects/`
| File | Method | Purpose |
|---|---|---|
| `get-all.php` | GET | Returns all projects (with filters: constituency, status, category) |
| `get-project.php?id=X` | GET | Returns single project with milestones, assignments |
| `update-progress.php` | POST | Updates project progress_pct (contractor role) |
| `update-milestone.php` | POST | Updates milestone status (manager role) |

### `api/attendance/`
| File | Method | Purpose |
|---|---|---|
| `gateway-open.php` | POST | Opens attendance gateway for today (clerk) |
| `gateway-close.php` | POST | Closes gateway (clerk) |
| `gateway-status.php` | GET | Returns if gateway is open + count signed in |
| `sign-in.php` | POST | Records attendance: verifies GPS vs geofence |
| `daily-report.php` | GET | Returns attendance summary for a date |

### `api/ipcs/`
| File | Method | Purpose |
|---|---|---|
| `submit.php` | POST | Contractor submits new IPC |
| `certify.php` | POST | Consultant certifies IPC |
| `endorse.php` | POST | Manager endorses IPC |
| `approve.php` | POST | Superadmin/Finance approves IPC |
| `reject.php` | POST | Any reviewer rejects with comment |

### `api/cms/`
| File | Method | Purpose |
|---|---|---|
| `get-page.php?slug=X` | GET | Returns CMS page sections |
| `update-section.php` | POST | Superadmin updates a CMS section |
| `get-settings.php` | GET | Returns all cms_settings |
| `update-setting.php` | POST | Updates a single setting |
| (5th stub) | — | — |

### `api/media/`
| File | Method | Purpose |
|---|---|---|
| `upload.php` | POST | Uploads file to media_library |
| `list.php` | GET | Returns paginated media list |
| `delete.php` | POST | Deletes a media file |

### `api/messages/`
| File | Method | Purpose |
|---|---|---|
| `send.php` | POST | Send a message in a thread |
| `list-threads.php` | GET | List all threads for current user |
| `get-thread.php?id=X` | GET | Get messages in a thread |
| `create-thread.php` | POST | Create new message thread |
| `mark-read.php` | POST | Mark messages as read |
| (6th stub) | — | — |

### `api/notifications/`
| File | Method | Purpose |
|---|---|---|
| `list.php` | GET | Get unread notifications for current user |
| `mark-read.php` | POST | Mark notification(s) as read |
| (3rd stub) | — | — |

### `api/public/`
| File | Method | Purpose |
|---|---|---|
| `projects.php` | GET | Public project data for homepage map/listing |
| `news.php` | GET | Latest news for public ticker/homepage |

### `api/reports/`
| File | Method | Purpose |
|---|---|---|
| `generate.php` | POST | Generates and returns a report (type, filters) |

### `api/boq/`
| File | Method | Purpose |
|---|---|---|
| (2 stubs) | — | BOQ CRUD |

### `api/programme/`
| File | Method | Purpose |
|---|---|---|
| (2 stubs) | — | Programme of works CRUD |

---

## 10. CORE CLASS QUICK REFERENCE

```php
// Bootstrap (top of every PHP file)
require_once __DIR__ . '/../app/core/bootstrap.php';

// Database
$user = Database::fetch("SELECT * FROM users WHERE id = ?", [1]);
$all  = Database::fetchAll("SELECT * FROM projects WHERE status = ?", ['active']);
Database::query("UPDATE users SET last_login=NOW() WHERE id=?", [5]);

// Authentication
Auth::check()           // bool — is logged in?
Auth::user()            // array|null — ['id','name','email','role']
Auth::id()              // int|null
Auth::login($user)      // sets session
Auth::logout()          // clears session
Auth::hasRole('manager')// bool

// Guards (redirect shortcuts)
Guard::guest()          // redirect logged-in away (use on login page)
Guard::auth()           // redirect guests to login (use on all admin pages)
Guard::role(['superadmin','manager'])  // must have one of these roles

// URLs
Url::to('admin/index.php')  // → /Trans-Nzoia-Affordable-Housing/admin/index.php
Url::basePath()              // → /Trans-Nzoia-Affordable-Housing

// Responses
Response::redirect(Url::to('admin/login.php'));
Response::json(['success' => true, 'data' => $result]);
Response::abort(404, 'Not found');

// CSRF
$token = Csrf::token('my_form');         // generate (embed in form/meta)
Csrf::verify($token, 'my_form')         // validate (returns bool)
echo Csrf::field('my_form');             // outputs <input type="hidden" ...>

// Security
echo Security::e($userInput);            // XSS-safe output
Security::cleanString($str);            // trim + strip_tags

// Session
Session::set('key', $value);
Session::get('key', $default);
Session::flash('status', 'Saved!');     // one-time flash message
Session::flash('status')               // read and consume flash
```

---

## 11. KEY CONVENTIONS — MUST FOLLOW

1. **Every PHP file in admin/ must call `Guard::auth()` or `Guard::role()`** — no exceptions.
2. **Every API POST endpoint must call `ApiMiddleware::handle()`** — this checks CSRF.
3. **Never echo raw DB data** — always use `Security::e()` for HTML output.
4. **Redirects always use `Response::redirect(Url::to(...))`** — never `header('Location: ...')` directly.
5. **Database queries always use prepared statements** — always pass values as the second array argument to `Database::query/fetch/fetchAll`.
6. **CSS design tokens** — never hardcode colors. Use `--lime`, `--primary-600`, `--neutral-*` etc.
7. **File uploads** go to `uploads/` (public) or `secure-uploads/` (private). Max 5MB. Allowed: pdf, jpg, jpeg, png.
8. **Admin pages for each role** should only load `admin-global.css` + their role's `dashboard-{role}.css` + relevant component CSS files.
9. **Password hashing** — always `password_hash($pass, PASSWORD_DEFAULT)` and `password_verify($input, $hash)`.
10. **The base_url is `/Trans-Nzoia-Affordable-Housing`** (set in `app/config/app.php`). All URLs generated through `Url::to()`.

---

## 12. WHAT IS BUILT vs WHAT IS STILL STUB

### ✅ FULLY BUILT (working code)
- Public frontend: all pages (HTML + CSS + JS) — index, about, projects, news, gallery, contact, faq, leadership, stakeholders, sitemap, legal pages, 404, 500
- Admin login page (UI + PHP + CSRF + real auth API)
- Admin auth pages: forgot-password, reset-password, unauthorised (UI complete, email sending needs Mailer wired up)
- Database schema: all 65 tables defined in `ahptc_schema.sql`
- Database seeds: roles, constituencies, categories, CMS, FAQs, leadership, super admin user
- All core PHP classes: Auth, Database, Guard, Response, Session, Security, Csrf, Url
- All middleware classes: Auth, Csrf, Api, Role
- All 65 model class stubs (structure defined, methods need implementation)
- Admin folder structure: all role directories and page files created (stubs)
- Admin CSS/JS: admin-global.css fully styled; dashboard CSS and component CSS files created (most are stubs needing implementation)
- Admin JS: login.js, forgot-password.js, reset-password.js, unauthorised.js fully written; others are stubs

### 🔲 STILL STUB / NEEDS IMPLEMENTATION
- All admin dashboard PHP pages (superadmin/, manager/, consultant/, etc.) — structure exists, DB queries and HTML need writing
- All API endpoint PHP files — structure exists, logic needs writing
- admin/auth/forgot-password.php and reset-password.php — UI done but Mailer + DB token logic not wired
- All Model classes — class files exist, methods like `find()`, `all()`, `create()` need implementation using Database::
- admin/assets/js/ files (charts.js, gantt.js, data-tables.js etc.) — stubs only
- Reports generation
- Notification system
- Real-time messaging
- Media library upload implementation
- Chart.js integration for analytics

---

## 13. DEFAULT CREDENTIALS & SEEDED DATA

**Super Admin:**
- Name: Moses Awuor
- Email: `director@transnzoia.go.ke`
- Password: Set via `admin/setup.php` OR seeded default: `Admin@1234`
- Role: `superadmin`

**Seeded Constituencies (7):**
Cherangany, Endebess, Kiminini, Kwanza, Saboti, Trans-Nzoia East, Trans-Nzoia West

**Seeded Roles (7):**
superadmin, manager, consultant, contractor, clerk, finance, intern

---

*Last updated: May 2026 — All admin folder restructuring complete. auth/ → admin/auth/, admin-assets/ → admin/assets/*
