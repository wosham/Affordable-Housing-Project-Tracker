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
$group = trim((string)($input['group'] ?? ''));

try {
    if ($key !== '') {
        Response::json([
            'success' => true,
            'message' => 'Setting reset to default.',
            'setting' => SystemSetting::resetKey($key, (int)Auth::id()),
        ]);
    }

    if ($group !== '') {
        $count = SystemSetting::resetGroup($group, (int)Auth::id());
        Response::json([
            'success' => true,
            'message' => format_number($count) . ' settings reset to defaults.',
            'count' => $count,
        ]);
    }

    Response::json(['success' => false, 'message' => 'Setting key or group is required.'], 422);
} catch (InvalidArgumentException $exception) {
    Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    Logger::error('System setting reset failed', ['key' => $key, 'group' => $group, 'error' => $exception->getMessage()]);
    Response::json(['success' => false, 'message' => 'Setting could not be reset.'], 500);
}
