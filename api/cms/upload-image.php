<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

Guard::auth();
Guard::role('superadmin');

if (isset($_FILES['image']) && !isset($_FILES['file'])) {
    $_FILES['file'] = $_FILES['image'];
}

$_POST['folder'] = MediaLibrary::normaliseFolder((string)($_POST['folder'] ?? 'cms'));
$_POST['csrf_form'] = (string)($_POST['csrf_form'] ?? 'cms_editor');

require __DIR__ . '/../media/upload.php';
