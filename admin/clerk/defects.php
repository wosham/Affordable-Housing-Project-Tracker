<?php
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
Guard::exactRole('clerk');
$qualityType = 'defect';
include dirname(__DIR__, 2) . '/app/partials/admin/clerk-quality-evidence-page.php';
