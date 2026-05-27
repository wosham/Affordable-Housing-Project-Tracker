<?php
// API — Reports: Generate and return a report (PDF/Excel/JSON)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager','finance']);
// TODO: Phase 7 — Generate report by type: progress, financial, attendance, SDHUD brief
