<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'csrf' => false,
]);

$email = Security::cleanEmail((string)($_POST['email'] ?? ''));
$name = Security::cleanString((string)($_POST['name'] ?? ''));
$honeypot = trim((string)($_POST['_gotcha'] ?? ''));
$ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
$userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$sourceUrl = substr((string)($_SERVER['HTTP_REFERER'] ?? Url::to('news.php')), 0, 500);

if ($honeypot !== '') {
    Response::json(['success' => true, 'message' => 'Subscription received.']);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::json(['success' => false, 'message' => 'Please enter a valid email address.', 'errors' => ['email' => 'Invalid email address.']], 422);
}

if ($ip !== '') {
    try {
        $limit = max(1, SystemConfig::int('public.subscribe_rate_limit_10m', 5));
        $recent = Database::fetch(
            'SELECT COUNT(*) AS total FROM subscribers WHERE ip = ? AND subscribed_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)',
            [$ip]
        );
        if ((int)($recent['total'] ?? 0) >= $limit) {
            Response::json(['success' => false, 'message' => 'Please wait a few minutes before subscribing again.'], 429);
        }
    } catch (Throwable) {
    }
}

try {
    $result = Subscriber::subscribe($email, $name, [
        'ip' => $ip ?: null,
        'user_agent' => $userAgent ?: null,
        'source_url' => $sourceUrl ?: null,
    ]);

    $subscriber = $result['subscriber'] ?? null;
    if (!empty($result['created']) && class_exists('Notification')) {
        Notification::pushRole(
            'superadmin',
            'subscriber',
            'New newsletter subscriber',
            $email . ' subscribed to programme updates.',
            'admin/superadmin/subscribers.php'
        );
    }

    Response::json([
        'success' => true,
        'message' => !empty($result['reactivated'])
            ? 'Your subscription has been reactivated.'
            : 'Thank you. You are subscribed to programme updates.',
        'subscriber' => [
            'email' => $subscriber['email'] ?? $email,
            'status' => $subscriber['status'] ?? 'active',
        ],
    ]);
} catch (Throwable $e) {
    Logger::error('Newsletter subscription failed', ['error' => $e->getMessage()]);
    Response::json(['success' => false, 'message' => 'Unable to subscribe right now. Please try again.'], 500);
}
