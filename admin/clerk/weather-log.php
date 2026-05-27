<?php
// Clerk of Works — Weather Log
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Daily weather: morning/afternoon conditions, rainfall, working hours
