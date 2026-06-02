<?php

class AuthEmailService
{
    public static function sendPasswordReset(array $user, string $token, string $expiresAt): array
    {
        $email = trim((string)($user['email'] ?? ''));
        $name = trim((string)($user['name'] ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))));
        $name = $name !== '' ? $name : 'Staff user';
        $link = self::absoluteUrl('admin/auth/reset-password.php?token=' . rawurlencode($token));
        $subject = 'Reset your AHP Tracker password';

        $html = '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#0f172a">'
            . '<h2 style="margin:0 0 12px">Reset your password</h2>'
            . '<p>Hello ' . Security::e($name) . ',</p>'
            . '<p>We received a request to reset your Trans-Nzoia AHP Tracker password.</p>'
            . '<p><a href="' . Security::e($link) . '" style="display:inline-block;background:#143d04;color:#fff;padding:11px 16px;border-radius:6px;text-decoration:none;font-weight:700">Create new password</a></p>'
            . '<p>This link expires at <strong>' . Security::e(format_datetime($expiresAt)) . '</strong> and can be used once.</p>'
            . '<p>If you did not request this, ignore this email and your password will remain unchanged.</p>'
            . '<p style="font-size:12px;color:#64748b">Trans-Nzoia Affordable Housing Programme Tracker</p>'
            . '</div>';

        return ResendMailer::send($email, $subject, $html, [
            'recipient_user_id' => (int)($user['id'] ?? 0),
            'template_key' => 'auth_password_reset',
        ]);
    }

    private static function absoluteUrl(string $path): string
    {
        $configured = trim((string)(getenv('APP_URL') ?: SystemConfig::text('general.app_url', '')));
        if ($configured !== '') {
            return rtrim($configured, '/') . '/' . ltrim($path, '/');
        }

        $base = Url::to($path);
        if (preg_match('/^https?:\/\//i', $base)) {
            return $base;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . '/' . ltrim($base, '/');
    }
}
