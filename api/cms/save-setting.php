<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'cms_editor',
]);

$data = json_decode(file_get_contents('php://input') ?: '', true);
$data = is_array($data) ? $data : $_POST;

$key = preg_replace('/[^a-z0-9_:-]+/', '', strtolower((string)($data['key'] ?? '')));
$type = (string)($data['type'] ?? 'text');
$label = Security::cleanString((string)($data['label'] ?? ''));
$group = preg_replace('/[^a-z0-9_-]+/', '', strtolower((string)($data['group'] ?? 'global'))) ?: 'global';
$value = $data['value'] ?? '';

if ($key === '') {
    Response::json(['success' => false, 'message' => 'Setting key is required.'], 422);
}

if (!in_array($type, ['text', 'number', 'boolean', 'json', 'image', 'color', 'url', 'email'], true)) {
    Response::json(['success' => false, 'message' => 'Invalid setting type.'], 422);
}

if ($type === 'json') {
    $decoded = is_array($value) ? $value : json_decode((string)$value, true);
    if (!is_array($decoded)) {
        Response::json(['success' => false, 'message' => 'JSON setting value is invalid.'], 422);
    }
    $value = $decoded;
} elseif ($type === 'boolean') {
    $value = in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
} elseif ($type === 'number') {
    $value = (string)(float)$value;
} elseif ($type === 'email') {
    $value = Security::cleanEmail(trim((string)$value));
    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        Response::json(['success' => false, 'message' => 'Email setting value is invalid.'], 422);
    }
} elseif ($type === 'url') {
    $value = Security::cleanString(trim((string)$value));
    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL) && !str_starts_with($value, '/')) {
        Response::json(['success' => false, 'message' => 'URL setting value is invalid.'], 422);
    }
} else {
    $value = Security::cleanString(trim((string)$value));
}

$previous = Database::fetch('SELECT * FROM cms_settings WHERE `key` = ? LIMIT 1', [$key]);
if ($previous) {
    Database::query(
        'INSERT INTO cms_revisions (page_id, section_id, revision_type, target_key, snapshot_json, created_by)
         VALUES ((SELECT id FROM cms_pages WHERE slug = "home" LIMIT 1), NULL, "setting", ?, ?, ?)',
        [$key, json_encode($previous, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), (int)Auth::id()]
    );
}

CmsSetting::upsert($key, $value, $type, $label, $group);
Logger::log('update', 'cms_settings', 0, ['key' => $key]);

Response::json(['success' => true, 'message' => 'Setting saved.']);
