<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    Response::json(['success' => false, 'message' => 'Endpoint not found.'], 404);
}

function contractor_site_record_handle(string $type): void
{
    ApiMiddleware::handle([
        'methods' => ['POST'],
        'roles' => ['contractor'],
        'csrf' => false,
    ]);

    ApiCsrf::requireAny(['contractor_site_records', 'default']);

    $input = Security::jsonInput();
    if ($input === []) {
        $input = $_POST;
    }

    try {
        $id = ContractorSiteRecord::save($type, (int)Auth::id(), (string)Auth::role(), $input);
        Response::json(['success' => true, 'message' => 'Record saved successfully.', 'id' => $id]);
    } catch (RuntimeException $e) {
        Response::json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (Throwable) {
        Response::json(['success' => false, 'message' => 'Record could not be saved.'], 500);
    }
}
