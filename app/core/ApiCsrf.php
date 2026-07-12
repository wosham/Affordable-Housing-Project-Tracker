<?php

/**
 * Shared multi-form CSRF helpers for admin APIs.
 *
 * Use when a single endpoint is called from several portals that mint
 * different page csrfForm tokens. Prefer ApiMiddleware csrf_form when
 * only one form owns the call.
 */
class ApiCsrf
{
    /**
     * @param list<string> $forms
     */
    public static function checkAny(array $forms, ?string $token = null): bool
    {
        $token = $token ?? Csrf::fromRequest();
        if ($token === null || $token === '') {
            return false;
        }

        foreach ($forms as $form) {
            $form = trim((string)$form);
            if ($form === '') {
                continue;
            }
            if (Csrf::verify($token, $form)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify multi-form CSRF or abort with JSON 419.
     *
     * @param list<string> $forms
     */
    public static function requireAny(array $forms, string $message = 'CSRF token mismatch. Please refresh the page and try again.'): void
    {
        if (self::checkAny($forms)) {
            return;
        }

        Response::json([
            'success' => false,
            'message' => $message,
        ], 419);
    }

    /**
     * Common form packs used across the admin API surface.
     *
     * @return list<string>
     */
    public static function forms(string $pack): array
    {
        return match ($pack) {
            'manager_site_records' => ['manager_site_records', 'default'],
            'manager_contract_controls' => ['manager_contract_controls', 'default'],
            'manager_boq' => ['superadmin_boq', 'manager_boq', 'consultant_boq', 'clerk_boq', 'finance_boq', 'default'],
            'consultant_documents' => ['consultant_documents', 'default'],
            'consultant_quality' => ['consultant_quality', 'default'],
            'consultant_technical' => ['consultant_technical', 'default'],
            'consultant_contract' => ['consultant_contract', 'default'],
            'contractor_site_records' => ['contractor_site_records', 'default'],
            'contractor_submission' => ['contractor_submission', 'contractor_progress', 'default'],
            'project_progress' => ['manager_projects', 'manager_dashboard', 'contractor_project', 'contractor_progress', 'clerk_site_records', 'superadmin_projects', 'default'],
            'project_milestone' => ['manager_milestones', 'manager_projects', 'manager_dashboard', 'clerk_site_records', 'clerk_quality_evidence', 'superadmin_projects', 'default'],
            'programme_task' => ['superadmin_programme', 'manager_programme', 'contractor_programme', 'default'],
            'ipc_submit' => ['contractor_ipc', 'contractor_ipcs', 'contractor_ipc_submit', 'default'],
            'ipc_endorse' => ['manager_ipc_queue', 'clerk_ipc', 'superadmin_ipcs', 'default'],
            'ipc_reject' => ['superadmin_approvals', 'superadmin_ipcs', 'manager_ipc_queue', 'consultant_ipc_certify', 'clerk_ipc', 'default'],
            'ipc_approve' => ['superadmin_approvals', 'superadmin_ipcs', 'default'],
            default => ['default'],
        };
    }
}
