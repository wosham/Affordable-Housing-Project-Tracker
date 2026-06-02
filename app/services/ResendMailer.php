<?php

class ResendMailer
{
    public static function send(string $to, string $subject, string $html, array $options = []): array
    {
        $to = trim($to);
        $subject = trim($subject);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || $subject === '' || trim($html) === '') {
            return ['success' => false, 'message' => 'Invalid email payload.'];
        }

        $apiKey = trim((string)(getenv('RESEND_API_KEY') ?: SystemConfig::text('email.resend_api_key', '')));
        $fromEmail = trim((string)(getenv('RESEND_FROM_EMAIL') ?: SystemConfig::text('email.from_email', '')));
        $fromName = trim((string)(getenv('RESEND_FROM_NAME') ?: SystemConfig::text('email.from_name', 'AHP Tracker')));

        if ($apiKey === '' || $fromEmail === '') {
            EmailLog::record([
                'recipient_email' => $to,
                'recipient_user_id' => $options['recipient_user_id'] ?? null,
                'subject' => $subject,
                'template_key' => $options['template_key'] ?? null,
                'status' => 'skipped',
                'error_message' => 'Resend API key or sender email is missing.',
                'payload' => ['to' => $to, 'subject' => $subject],
            ]);
            return ['success' => false, 'message' => 'Email provider is not configured.'];
        }

        $payload = [
            'from' => ($fromName !== '' ? $fromName . ' <' . $fromEmail . '>' : $fromEmail),
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
        ];

        $response = self::postJson('https://api.resend.com/emails', $payload, $apiKey);
        EmailLog::record([
            'provider_message_id' => $response['id'] ?? null,
            'recipient_email' => $to,
            'recipient_user_id' => $options['recipient_user_id'] ?? null,
            'subject' => $subject,
            'template_key' => $options['template_key'] ?? null,
            'status' => !empty($response['success']) ? 'sent' : 'failed',
            'error_message' => $response['message'] ?? null,
            'payload' => $payload,
        ]);

        return $response;
    }

    private static function postJson(string $url, array $payload, string $apiKey): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return ['success' => false, 'message' => 'Could not encode email payload.'];
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_TIMEOUT => 12,
            ]);
            $raw = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                    'content' => $body,
                    'timeout' => 12,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents($url, false, $context);
            $error = $raw === false ? 'HTTP request failed.' : '';
            $status = 0;
            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('/\s(\d{3})\s/', $header, $match)) {
                    $status = (int)$match[1];
                    break;
                }
            }
        }

        $decoded = json_decode((string)$raw, true);
        $decoded = is_array($decoded) ? $decoded : [];
        if ($status >= 200 && $status < 300) {
            return ['success' => true, 'id' => $decoded['id'] ?? null];
        }

        return [
            'success' => false,
            'message' => $decoded['message'] ?? $decoded['error'] ?? $error ?: 'Resend request failed.',
            'status' => $status,
        ];
    }
}
