<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

$id = Security::cleanInt($_GET['id'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));

if ($id <= 0 || $token === '' || strlen($token) < 32) {
    http_response_code(404);
    exit('Invalid unsubscribe link.');
}

$subscriber = Subscriber::findByUnsubscribeToken($id, $token);
if (!$subscriber) {
    http_response_code(404);
    exit('Invalid unsubscribe link.');
}

if (($subscriber['status'] ?? '') !== 'unsubscribed') {
    Subscriber::unsubscribe($id, 0);
    Logger::log('public-unsubscribe', 'subscribers', $id, [
        'email' => $subscriber['email'] ?? '',
    ]);
}

$pageTitle = 'Unsubscribed';
$message = 'You have been unsubscribed from Trans-Nzoia AHP Tracker programme updates.';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= Security::e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= Security::e(Url::asset('assets/css/global.css')) ?>">
</head>
<body>
  <main class="section" style="min-height:70vh;display:grid;place-items:center;padding:2rem;">
    <section class="container" style="max-width:640px;text-align:center;">
      <h1><?= Security::e($pageTitle) ?></h1>
      <p><?= Security::e($message) ?></p>
      <p><a class="btn btn-primary" href="<?= Security::e(Url::to('index.php')) ?>">Return to website</a></p>
    </section>
  </main>
</body>
</html>
