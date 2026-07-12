<?php

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
    Response::json(['success' => false, 'message' => 'Endpoint not found.'], 404);
}

function clerk_daily_record_handle(string $type): void
{
    require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

    ApiMiddleware::handle([
        'methods' => ['POST'],
        'roles' => ['clerk'],
        'csrf_form' => 'clerk_daily_records',
    ]);

    try {
        $id = ClerkDailyRecord::save($type, (int)Auth::id(), $_POST);
        $isUpdate = Security::cleanInt($_POST['id'] ?? 0) > 0;
        Logger::log($isUpdate ? 'update' : 'save', 'clerk_daily_records', $id, [
            'type' => $type,
            'project_id' => Security::cleanInt($_POST['project_id'] ?? 0),
        ]);

        Response::json([
            'success' => true,
            'message' => $isUpdate ? 'Record updated successfully.' : 'Record saved successfully.',
            'id' => $id,
        ]);
    } catch (RuntimeException $e) {
        Response::json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (Throwable) {
        Response::json(['success' => false, 'message' => 'Record could not be saved.'], 500);
    }
}
