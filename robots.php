<?php
declare(strict_types=1);

define('APP_SKIP_SESSION', true);
require_once __DIR__ . '/app/core/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');
header_remove('X-Powered-By');
header('Cache-Control: public, max-age=900');

echo SitemapBuilder::robots();
