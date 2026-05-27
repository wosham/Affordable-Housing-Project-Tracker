<?php
// Mailer — SMTP email sending wrapper
// Uses PHP's mail() or PHPMailer (to be configured in app/config/mail.php)
class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        // TODO: Phase 3 — Integrate PHPMailer with SMTP config from mail.php
        return false;
    }

    public static function notifyIPC(int $ipcId, string $event, int $recipientId): bool
    {
        // TODO: Phase 4 — Template-based IPC notification emails
        return false;
    }

    public static function notifyAttendance(int $siteId, string $date): bool
    {
        // TODO: Phase 4 — Daily absent intern notification to Manager + Director
        return false;
    }

    public static function notifyHSIncident(int $incidentId): bool
    {
        // TODO: Phase 5 — Immediate H&S incident alert email to Manager + Director
        return false;
    }
}
