<?php
// Finance Officer — Financial Reports (export PDF/Excel)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','finance']);
// TODO: Phase 7 — Generate financial summaries per project/category/constituency
