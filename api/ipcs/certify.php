<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . "/app/core/bootstrap.php";

// Consultant IPC certification. Allowed for superadmin and consultant roles.
// CSRF is verified against 'consultant_ipc_certify' — the form must emit this key.
ApiMiddleware::handle([
    "methods" => ["POST"],
    "roles" => ["superadmin", "consultant"],
    "csrf_form" => "consultant_ipc_certify",
]);

$input = Security::jsonInput();
if ($input === []) {
    $input = $_POST;
}

$ipcId = Security::cleanInt($input["ipc_id"] ?? 0);
$comment = trim(strip_tags((string) ($input["comment"] ?? "")));
$checklist = array_values(
    array_filter(array_map("strval", (array) ($input["checklist"] ?? []))),
);

if ($ipcId <= 0) {
    Response::json(
        ["success" => false, "message" => "IPC id is required."],
        422,
    );
}

$ipc = IPC::findDetailed($ipcId);
if (!$ipc) {
    Response::json(
        ["success" => false, "message" => "IPC could not be found."],
        404,
    );
}

if ((string) $ipc["status"] !== "clerk-endorsed") {
    Response::json(
        [
            "success" => false,
            "message" => "Only verified IPCs can be certified.",
        ],
        409,
    );
}

$actorId = (int) Auth::id();
$role = (string) Auth::role();

// Consultants must be assigned to the project; superadmins may certify any IPC.
if (
    $role === "consultant" &&
    !ipc_certify_consultant_allowed($actorId, (int) $ipc["project_id"])
) {
    Response::json(
        [
            "success" => false,
            "message" => "You are not assigned to certify this project.",
        ],
        403,
    );
}

$lines = ConsultantIPC::lineItems($ipcId);
$warnings = ConsultantIPC::warnings($ipc, $lines);
$requiredChecklist = ConsultantIPC::REVIEW_CHECKLIST;

if (
    $role === "consultant" &&
    count(array_intersect($requiredChecklist, $checklist)) <
        count($requiredChecklist)
) {
    Response::json(
        [
            "success" => false,
            "message" => "Complete the certification checklist first.",
        ],
        422,
    );
}

if (ConsultantIPC::hasCriticalWarnings($warnings)) {
    Response::json(
        [
            "success" => false,
            "message" => "Resolve critical IPC warnings before certification.",
            "warnings" => $warnings,
        ],
        409,
    );
}

try {
    Database::beginTransaction();

    Database::query(
        "UPDATE ipcs SET status = 'certified', certified_at = NOW(), certified_by = ?, certification_comment = ? WHERE id = ?",
        [$actorId, $comment !== "" ? $comment : null, $ipcId],
    );

    Database::query(
        "UPDATE boq_items bi
         JOIN ipc_lines il ON il.boq_item_id = bi.id
         SET bi.certified_qty = GREATEST(COALESCE(bi.certified_qty, 0), COALESCE(il.cumulative_qty, 0)),
             bi.certified_updated_by = ?,
             bi.last_certified_at = NOW(),
             bi.updated_by = ?,
             bi.updated_at = NOW()
         WHERE il.ipc_id = ?",
        [$actorId, $actorId, $ipcId],
    );

    IPCApproval::record($ipcId, 2, $actorId, "certified", $comment);

    Logger::log("certify", "ipcs", $ipcId, [
        "ipc_number" => $ipc["ipc_number"],
        "project" => $ipc["project_name"],
        "warnings" => $warnings,
    ]);

    Notification::pushRole(
        "manager",
        "ipc_certified",
        "IPC certified",
        "IPC #" .
            $ipc["ipc_number"] .
            " for " .
            $ipc["project_name"] .
            " is ready for manager endorsement.",
        "admin/manager/ipc-queue.php",
    );
    Notification::pushRole(
        "superadmin",
        "ipc_certified",
        "IPC certified",
        "IPC #" .
            $ipc["ipc_number"] .
            " has been certified for " .
            $ipc["project_name"] .
            ".",
        "admin/superadmin/ipcs.php",
    );
    Notification::push(
        (int) $ipc["contractor_id"],
        "ipc_certified",
        "IPC certified",
        "IPC #" .
            $ipc["ipc_number"] .
            " for " .
            $ipc["project_name"] .
            " has been certified.",
        "admin/contractor/ipc-history.php",
    );

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(
        [
            "success" => false,
            "message" => "IPC certification could not be completed.",
        ],
        500,
    );
}

Response::json([
    "success" => true,
    "message" => "IPC certified successfully.",
    "ipc" => IPC::findDetailed($ipcId),
]);

// ── Helpers ──────────────────────────────────────────────────────────────────

function ipc_certify_consultant_allowed(int $userId, int $projectId): bool
{
    $project = Project::findDetailed($projectId);
    if ((int) ($project["consultant_id"] ?? 0) === $userId) {
        return true;
    }

    return ProjectAssignment::canManageProject(
        $userId,
        $projectId,
        "consultant",
    );
}
