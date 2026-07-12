<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    Response::json(['success' => false, 'message' => 'Endpoint not found.'], 404);
}

function contractor_submission_handle(string $type): void
{
    ApiMiddleware::handle([
        'methods' => ['POST'],
        'roles' => ['contractor'],
        'csrf' => false,
    ]);

    ApiCsrf::requireAny(['contractor_submission', 'contractor_progress', 'default']);

    $input = Security::jsonInput();
    if ($input === []) {
        $input = $_POST;
    }

    try {
        $id = ContractorSubmission::create($type, (int)Auth::id(), (string)Auth::role(), $input);
        Response::json([
            'success' => true,
            'message' => 'Submission saved successfully.',
            'id' => $id,
        ]);
    } catch (RuntimeException $e) {
        Response::json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (Throwable) {
        Response::json(['success' => false, 'message' => 'Submission could not be saved.'], 500);
    }
}
