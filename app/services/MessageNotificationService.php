<?php

class MessageNotificationService
{
    public static function notifyNewMessage(int $threadId, int $messageId, int $senderId): void
    {
        $thread = MessageThread::find($threadId);
        $message = Message::find($messageId);
        if (!$thread || !$message) {
            return;
        }

        $sender = User::findDetailed($senderId) ?: [];
        $senderName = trim((string)($sender['name'] ?? '')) ?: 'AHP Tracker user';
        $subject = (string)($thread['subject'] ?? 'New message');
        $link = 'admin/' . (Auth::role() ?: 'superadmin') . '/messages.php?thread=' . $threadId;

        foreach (MessageParticipant::forThread($threadId) as $participant) {
            $recipientId = (int)$participant['user_id'];
            if ($recipientId === $senderId) {
                continue;
            }

            $role = (string)($participant['role_slug'] ?? 'superadmin');
            $recipientLink = 'admin/' . $role . '/messages.php?thread=' . $threadId;
            Notification::push($recipientId, 'message', 'New message from ' . $senderName, $subject, $recipientLink);

            if (!SystemConfig::bool('email.enabled', false) || !SystemConfig::bool('email.message_alerts', true)) {
                continue;
            }

            $email = trim((string)($participant['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            ResendMailer::send(
                $email,
                'New AHP Tracker message: ' . $subject,
                self::emailHtml($senderName, $subject, (string)($message['body'] ?? ''), self::absoluteUrl($recipientLink)),
                ['recipient_user_id' => $recipientId, 'template_key' => 'message_new']
            );
        }

        Logger::log('send', 'messages', $messageId, ['thread_id' => $threadId]);
    }

    private static function emailHtml(string $senderName, string $subject, string $body, string $link): string
    {
        $preview = Security::e(safe_truncate($body, 220));
        return '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#0f172a">'
            . '<h2 style="margin:0 0 12px">New AHP Tracker message</h2>'
            . '<p><strong>' . Security::e($senderName) . '</strong> sent you a message in <strong>' . Security::e($subject) . '</strong>.</p>'
            . '<p style="padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px">' . $preview . '</p>'
            . '<p><a href="' . Security::e($link) . '" style="display:inline-block;background:#143d04;color:#fff;padding:10px 14px;border-radius:6px;text-decoration:none">Open message</a></p>'
            . '<p style="font-size:12px;color:#64748b">Trans-Nzoia Affordable Housing Programme Tracker</p>'
            . '</div>';
    }

    private static function absoluteUrl(string $path): string
    {
        $url = Url::to($path);
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . '/' . ltrim($url, '/');
    }
}
