<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['intern'],
    'csrf_form' => 'intern_work',
]);

try {
    $entryId = InternProjectWork::saveEntry((int)Auth::id(), $_POST);
    Logger::log('save', 'intern_site_entries', $entryId, ['project_id' => Security::cleanInt($_POST['project_id'] ?? 0)]);

    Response::json([
        'success' => true,
        'message' => 'Site note saved.',
        'entry' => Database::fetch('SELECT * FROM intern_site_entries WHERE id = ? AND user_id = ? LIMIT 1', [$entryId, (int)Auth::id()]),
    ]);
} catch (InvalidArgumentException $exception) {
    Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    Logger::log('error', 'intern_site_entries', null, ['message' => $exception->getMessage()]);
    Response::json(['success' => false, 'message' => 'Site note could not be saved.'], 500);
}
