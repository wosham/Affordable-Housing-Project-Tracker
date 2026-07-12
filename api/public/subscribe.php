<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'csrf' => false,
]);

$email = Security::cleanEmail((string)($_POST['email'] ?? ''));
$name = Security::cleanString((string)($_POST['name'] ?? ''));
$ip = PublicApi::clientIp();
$userAgent = PublicApi::userAgent();
$sourceUrl = PublicApi::safeSourcePath(PublicApi::sourceUrl(Url::to('news.php')));

if (PublicApi::honeypot()) {
    PublicApi::fakeSuccess('Subscription received.');
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    PublicApi::validation(['email' => 'Invalid email address.'], 'Please enter a valid email address.');
}
if (strlen($email) > 190) {
    PublicApi::validation(['email' => 'Email address is too long.'], 'Please enter a valid email address.');
}
if (strlen($name) > 120) {
    PublicApi::validation(['name' => 'Name is too long.']);
}

if ($ip !== '') {
    try {
        $limit = max(1, SystemConfig::int('public.subscribe_rate_limit_10m', 5));
        $recent = Database::fetch(
            'SELECT COUNT(*) AS total FROM subscribers WHERE ip = ? AND subscribed_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)',
            [$ip]
        );
        if ((int)($recent['total'] ?? 0) >= $limit) {
            PublicApi::fail('Please wait a few minutes before subscribing again.', 429, [], ['retry_after' => 600]);
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

    if (!empty($result['created']) && class_exists('Notification')) {
        Notification::pushRole(
            'superadmin',
            'subscriber',
            'New newsletter subscriber',
            $email . ' subscribed to programme updates.',
            'admin/superadmin/subscribers.php'
        );
    }

    PublicApi::ok([
        'subscribed' => true,
        'reactivated' => !empty($result['reactivated']),
    ], !empty($result['reactivated'])
        ? 'Your subscription has been reactivated.'
        : 'Thank you. You are subscribed to programme updates.');
} catch (Throwable $e) {
    PublicApi::log('Newsletter subscription failed', ['error' => $e->getMessage()]);
    PublicApi::fail('Unable to subscribe right now. Please try again.', 500);
}
