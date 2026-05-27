<?php
// Super Admin — Messages Centre (Gmail-style inbox)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 5 — Full inbox: direct messages, project channels, announcements
