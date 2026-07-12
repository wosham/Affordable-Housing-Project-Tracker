<?php

class Mailer
{
    public static function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = "",
    ): bool {
        $to = trim($to);
        $subject = trim($subject);
        if (
            !filter_var($to, FILTER_VALIDATE_EMAIL) ||
            $subject === "" ||
            trim($htmlBody) === ""
        ) {
            return false;
        }

        if (class_exists("ResendMailer")) {
            $result = ResendMailer::send($to, $subject, $htmlBody, [
                "template_key" => "generic",
            ]);
            if (!empty($result["success"])) {
                return true;
            }
        }

        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: " . self::fromHeader(),
        ];

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    public static function notifyIPC(
        int $ipcId,
        string $event,
        int $recipientId,
    ): bool {
        $user = User::findDetailed($recipientId);
        $ipc = IPC::findDetailed($ipcId);
        if (!$user || !$ipc) {
            return false;
        }

        $projectName = Security::e(
            (string) ($ipc["project_name"] ?? "a project"),
        );
        $ipcNumber = Security::e((string) ($ipc["ipc_number"] ?? ""));
        $statusLabel = Security::e(status_label($event));

        $html =
            '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#0f172a">' .
            '<h2 style="margin:0 0 12px">IPC Status Update</h2>' .
            "<p>IPC <strong>#" .
            $ipcNumber .
            "</strong> for <strong>" .
            $projectName .
            "</strong>" .
            " has been updated to: <strong>" .
            $statusLabel .
            "</strong>.</p>" .
            '<p style="font-size:12px;color:#64748b">Trans-Nzoia Affordable Housing Programme Tracker</p>' .
            "</div>";

        return self::send(
            (string) $user["email"],
            "IPC update: " . $statusLabel,
            $html,
        );
    }

    public static function notifyAttendance(int $gatewayId, string $date): bool
    {
        // Attendance notifications are handled via in-app Notification::pushRole().
        // Email delivery can be wired here when an SMTP/Resend template is ready.
        Logger::info(
            "notifyAttendance skipped — no email template configured",
            [
                "gateway_id" => $gatewayId,
                "date" => $date,
            ],
        );
        return false;
    }

    public static function notifyHSIncident(int $incidentId): bool
    {
        // H&S incident notifications are handled via in-app notifications.
        // Email delivery can be wired here when an SMTP/Resend template is ready.
        Logger::info(
            "notifyHSIncident skipped — no email template configured",
            [
                "incident_id" => $incidentId,
            ],
        );
        return false;
    }

    private static function fromHeader(): string
    {
        $config = require dirname(__DIR__) . "/config/mail.php";
        $email = trim(
            (string) (getenv("MAIL_FROM_EMAIL") ?:
            SystemConfig::text(
                "email.from_email",
                $config["from_email"] ?? "noreply@localhost",
            )),
        );
        $name = trim(
            (string) (getenv("MAIL_FROM_NAME") ?:
            SystemConfig::text(
                "email.from_name",
                $config["from_name"] ?? "AHP Tracker",
            )),
        );

        return $name !== "" ? $name . " <" . $email . ">" : $email;
    }
}
