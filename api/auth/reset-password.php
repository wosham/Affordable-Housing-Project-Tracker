<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    ApiMiddleware::handle([
        'methods' => ['GET'],
        'csrf' => false,
    ]);

    $token = trim((string)($_GET['token'] ?? ''));
    Response::json([
        'success' => PasswordReset::validByToken($token) !== null,
        'message' => 'Token checked.',
    ]);
}

ApiMiddleware::handle([
    'methods' => ['POST'],
    'csrf_form' => 'reset_password',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$token = trim((string)($input['token'] ?? ''));
$password = (string)($input['password'] ?? '');
$confirm = (string)($input['confirm_password'] ?? '');

if ($token === '' || strlen($token) < 32) {
    Response::json(['success' => false, 'message' => 'Reset link is invalid or expired.'], 422);
}

if ($password !== $confirm) {
    Response::json(['success' => false, 'message' => 'Passwords do not match.'], 422);
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
    Response::json(['success' => false, 'message' => 'Password does not meet all requirements.'], 422);
}

$reset = PasswordReset::validByToken($token);
if (!$reset) {
    Logger::log('invalid-token', 'password_resets', 0);
    Response::json(['success' => false, 'message' => 'Reset link is invalid or expired.'], 422);
}

Database::beginTransaction();
try {
    $userId = (int)$reset['user_id'];
    User::update($userId, ['password_hash' => User::hashPassword($password)]);
    PasswordReset::markUsed((int)$reset['id']);
    Database::query('DELETE FROM user_sessions WHERE user_id = ?', [$userId]);
    Logger::log('complete', 'password_resets', (int)$reset['id'], ['user_id' => $userId]);
    Database::commit();
} catch (Throwable $error) {
    Database::rollBack();
    Logger::log('complete-failed', 'password_resets', (int)$reset['id'], ['error' => $error->getMessage()]);
    Response::json(['success' => false, 'message' => 'Password could not be updated.'], 500);
}

Response::json(['success' => true, 'message' => 'Password updated successfully.']);
