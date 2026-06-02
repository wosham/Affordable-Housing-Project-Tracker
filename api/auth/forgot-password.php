<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'csrf_form' => 'forgot_password',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$email = strtolower(Security::cleanEmail((string)($input['email'] ?? '')));
$ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
$generic = [
    'success' => true,
    'message' => 'If that email exists, a secure reset link has been sent.',
];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::json(['success' => false, 'message' => 'Enter a valid email address.'], 422);
}

if (PasswordReset::requestCount($email, $ip, 10) >= 5) {
    Logger::log('rate-limit', 'password_resets', 0, ['email' => $email, 'ip' => $ip]);
    Response::json($generic);
}

$user = User::findByEmail($email);
if (!$user || (string)($user['status'] ?? '') !== 'active') {
    Logger::log('request', 'password_resets', 0, ['email' => $email, 'matched' => false]);
    Response::json($generic);
}

try {
    $reset = PasswordReset::createForUser($user, 30);
    $result = AuthEmailService::sendPasswordReset($user, $reset['token'], $reset['expires_at']);
    Logger::log(!empty($result['success']) ? 'email-sent' : 'email-failed', 'password_resets', (int)$reset['id'], [
        'user_id' => (int)$user['id'],
        'email' => $email,
        'provider' => 'resend',
    ]);
} catch (Throwable $error) {
    Logger::log('request-failed', 'password_resets', (int)($user['id'] ?? 0), ['email' => $email, 'error' => $error->getMessage()]);
}

Response::json($generic);
