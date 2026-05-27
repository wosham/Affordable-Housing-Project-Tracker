<?php
// Clerk of Works — Material Delivery Log (independent verification)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Verify and record material deliveries arriving on site
