# Trans-Nzoia Affordable Housing Project Tracker

Static PHP frontend and backend foundation for the Trans-Nzoia County Affordable Housing Programme tracker.

## Current Status

The public HTML frontend has been converted to static PHP pages. Shared PHP partials, static PHP data arrays, and a backend foundation are in place, but database-backed features are not connected yet.

## Local Setup

Place this folder under XAMPP:

```text
C:\xampp\htdocs\Trans-Nzoia-Affordable-Housing
```

Run Apache from XAMPP and open:

```text
http://localhost/Trans-Nzoia-Affordable-Housing/
```

## Main Structure

```text
app/config/       App, path, and database configuration
app/core/         Bootstrap, session, CSRF, auth, guards, URL, response, database helpers
app/data/         Static PHP arrays for projects, constituencies, news, and site settings
app/partials/     Shared layout partials
assets/           Public CSS and JavaScript for public pages
auth/             Staff auth pages and future backend routes
legal/            Legal pages
uploads/          Public images and media
```

## Public Routes

```text
index.php
about.php
projects.php
project-detail.php?id=...
constituencies.php
constituency-detail.php?id=...
news.php
news-article.php?id=...
gallery.php
leadership.php
stakeholders.php
faq.php
contact.php
sitemap.php
legal/privacy.php
legal/terms.php
legal/disclaimer.php
```

## Backend Foundation

The backend foundation includes:

```text
app/core/bootstrap.php
app/core/Database.php
app/core/session.php
app/core/Csrf.php
app/core/Auth.php
app/core/Guard.php
app/core/Url.php
app/core/Response.php
app/core/security.php
app/helpers/functions.php
```

Database connection settings are in:

```text
app/config/database.php
```

The database wrapper connects lazily, so public pages continue to load without an active database connection until backend features are wired.

## Not Yet Connected

These features are intentionally pending for the backend phase:

```text
Database schema and migrations
Real login authentication
Password reset database/email flow
Dashboard data views
Project editor CRUD
Contact form storage/email handling
Admin roles and audit logs
```

## Important Notes

The original `frontend/` reference folder has been retired after the PHP conversion was verified.

Do not expose `app/` publicly. The root `.htaccess` blocks direct access to backend/config/data files when Apache honors `.htaccess`.
