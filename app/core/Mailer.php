<?php

class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $to = trim($to);
        $subject = trim($subject);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || $subject === '' || trim($htmlBody) === '') {
            return false;
        }

        if (class_exists('ResendMailer')) {
            $result = ResendMailer::send($to, $subject, $htmlBody, ['template_key' => 'generic']);
            if (!empty($result['success'])) {
                return true;
            }
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::fromHeader(),
        ];

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    public static function notifyIPC(int $ipcId, string $event, int $recipientId): bool
    {
        $user = User::findDetailed($recipientId);
        $ipc = IPC::findDetailed($ipcId);
        if (!$user || !$ipc) {
            return false;
        }

        return self::send(
            (string)$user['email'],
            'IPC update: ' . status_label($event),
            '<p>IPC #' . Security::e($ipc['ipc_number'] ?? '') . ' for ' . Security::e($ipc['project_name'] ?? 'a project') . ' has been updated.</p>'
        );
    }

    public static function notifyAttendance(int $siteId, string $date): bool
    {
        return false;
    }

    public static function notifyHSIncident(int $incidentId): bool
    {
        return false;
    }

    private static function fromHeader(): string
    {
        $config = require dirname(__DIR__) . '/config/mail.php';
        $email = trim((string)(getenv('MAIL_FROM_EMAIL') ?: SystemConfig::text('email.from_email', $config['from_email'] ?? 'noreply@localhost')));
        $name = trim((string)(getenv('MAIL_FROM_NAME') ?: SystemConfig::text('email.from_name', $config['from_name'] ?? 'AHP Tracker')));

        return $name !== '' ? $name . ' <' . $email . '>' : $email;
    }
}
