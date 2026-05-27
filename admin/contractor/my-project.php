<?php
// Contractor — My Project Overview
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 3 — Project details, progress %, contract sum, key contacts
