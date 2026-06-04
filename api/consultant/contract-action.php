<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'consultant'],
    'csrf' => false,
]);

if (!consultant_contract_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = Security::jsonInput();
if ($input === []) {
    $input = $_POST;
}

$type = trim((string)($input['type'] ?? ''));
$id = Security::cleanInt($input['id'] ?? 0);
$action = trim((string)($input['action'] ?? ''));

if ($type === '' || $id <= 0 || $action === '') {
    Response::json(['success' => false, 'message' => 'Review item and action are required.'], 422);
}

try {
    $result = ConsultantContractDecision::applyAction($type, $id, $action, $input, (int)Auth::id(), (string)Auth::role());
    Response::json(['success' => true, 'message' => $result['message'] ?? 'Review saved.']);
} catch (InvalidArgumentException $e) {
    Response::json(['success' => false, 'message' => $e->getMessage()], 422);
} catch (RuntimeException $e) {
    Response::json(['success' => false, 'message' => $e->getMessage()], 404);
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Review could not be saved.'], 500);
}

function consultant_contract_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    foreach (['consultant_contract', 'consultant_technical', 'default'] as $form) {
        if (Csrf::verify($token, $form)) {
            return true;
        }
    }
    return false;
}
