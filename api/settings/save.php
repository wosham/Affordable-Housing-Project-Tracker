<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'settings',
]);

$input = Security::jsonInput();
if ($input === []) {
    $input = $_POST;
}

$key = trim((string)($input['key'] ?? ''));
$value = $input['value'] ?? '';

if ($key === '') {
    Response::json(['success' => false, 'message' => 'Setting key is required.'], 422);
}

try {
    $setting = SystemSetting::setValue($key, $value, (int)Auth::id());
    Response::json([
        'success' => true,
        'message' => 'Setting saved.',
        'setting' => $setting,
    ]);
} catch (InvalidArgumentException $exception) {
    Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    Logger::error('System setting save failed', ['key' => $key, 'error' => $exception->getMessage()]);
    Response::json(['success' => false, 'message' => 'Setting could not be saved.'], 500);
}
