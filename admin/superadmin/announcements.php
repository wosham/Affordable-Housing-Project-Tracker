<?php
// Super Admin — Announcements (broadcast to all or specific roles)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 5 — Create/send announcements, target by role, pin/unpin
