<?php

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
    Response::json(['success' => false, 'message' => 'Endpoint not found.'], 404);
}

function clerk_quality_evidence_handle(string $type): void
{
    require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

    ApiMiddleware::handle([
        'methods' => ['POST'],
        'roles' => ['clerk'],
        'csrf_form' => 'clerk_quality_evidence',
    ]);

    if ((string)Auth::role() !== 'clerk') {
        Response::json(['success' => false, 'message' => 'This action is restricted.'], 403);
    }

    try {
        $id = ClerkQualityEvidence::save($type, (int)Auth::id(), $_POST);
        $action = strtolower(trim((string)($_POST['ipc_action'] ?? 'endorse')));
        $isUpdate = Security::cleanInt($_POST['id'] ?? 0) > 0;
        Logger::log($isUpdate ? 'update' : 'save', 'clerk_quality_evidence', $id, [
            'type' => $type,
            'project_id' => Security::cleanInt($_POST['project_id'] ?? 0),
            'ipc_action' => $type === 'ipc' ? $action : null,
        ]);

        $message = 'Record saved successfully.';
        if ($type === 'ipc') {
            $message = $action === 'return'
                ? 'IPC returned to contractor successfully.'
                : 'IPC verified and sent for consultant certification.';
        } elseif ($isUpdate) {
            $message = 'Record updated successfully.';
        }

        Response::json([
            'success' => true,
            'message' => $message,
            'id' => $id,
        ]);
    } catch (RuntimeException $e) {
        Response::json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (Throwable) {
        Response::json(['success' => false, 'message' => 'Record could not be saved.'], 500);
    }
}
