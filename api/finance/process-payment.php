<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['finance'],
    'csrf_form' => 'finance_payments',
]);

if ((string)Auth::role() !== 'finance') {
    Response::json(['success' => false, 'message' => 'This finance action is restricted.'], 403);
}

$input = Security::jsonInput();
if ($input === []) {
    $input = $_POST;
}

try {
    $paymentId = FinancePayment::process((int)Auth::id(), $input);
    Response::json([
        'success' => true,
        'message' => 'Payment processed successfully.',
        'payment_id' => $paymentId,
    ]);
} catch (RuntimeException $e) {
    Response::json(['success' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Payment could not be processed.'], 500);
}
