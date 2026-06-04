<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'consultant'],
    'csrf' => false,
]);

if (!consultant_documents_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = Security::jsonInput();
if ($input === []) {
    $input = $_POST;
}

$type = trim((string)($input['type'] ?? ''));
$id = Security::cleanInt($input['id'] ?? 0);
$action = trim((string)($input['action'] ?? ''));
$note = trim(strip_tags((string)($input['note'] ?? '')));

if ($type === '' || $id <= 0 || $action === '') {
    Response::json(['success' => false, 'message' => 'Review type, record and action are required.'], 422);
}

try {
    $result = ConsultantDocumentCentre::applyAction($type, $id, $action, $note, (int)Auth::id(), (string)Auth::role());
    Response::json(['success' => true, 'message' => $result['message'] ?? 'Review saved.']);
} catch (InvalidArgumentException $e) {
    Response::json(['success' => false, 'message' => $e->getMessage()], 422);
} catch (RuntimeException $e) {
    Response::json(['success' => false, 'message' => $e->getMessage()], 404);
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Review could not be saved.'], 500);
}

function consultant_documents_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    foreach (['consultant_documents', 'consultant_reports', 'default'] as $form) {
        if (Csrf::verify($token, $form)) {
            return true;
        }
    }

    return false;
}
