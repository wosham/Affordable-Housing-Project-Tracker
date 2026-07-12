<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('clerk');
$recordType = 'materials';
include __DIR__ . '/../../app/partials/admin/clerk-daily-record-page.php';
