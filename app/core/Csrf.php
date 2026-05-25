<?php

class Csrf
{
    public static function token(string $form = 'default'): string
    {
        $tokens = Session::get('_csrf_tokens', []);
        $now = time();

        if (
            !isset($tokens[$form]['value'], $tokens[$form]['expires'])
            || $tokens[$form]['expires'] < $now
        ) {
            $tokens[$form] = [
                'value' => bin2hex(random_bytes(32)),
                'expires' => $now + 7200,
            ];
            Session::set('_csrf_tokens', $tokens);
        }

        return $tokens[$form]['value'];
    }

    public static function verify(?string $token, string $form = 'default'): bool
    {
        $tokens = Session::get('_csrf_tokens', []);
        $stored = $tokens[$form]['value'] ?? null;
        $expires = $tokens[$form]['expires'] ?? 0;

        return is_string($token)
            && is_string($stored)
            && $expires >= time()
            && hash_equals($stored, $token);
    }

    public static function field(string $form = 'default'): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . Security::e(self::token($form)) . '">';
    }
}
