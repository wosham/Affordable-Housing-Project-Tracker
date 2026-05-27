<?php

class Csrf
{
    public static function tokenName(): string
    {
        $config = $GLOBALS['app_config']['csrf'] ?? [];
        return (string)($config['token_name'] ?? '_csrf_token');
    }

    private static function sessionKey(): string
    {
        $config = $GLOBALS['app_config']['csrf'] ?? [];
        return (string)($config['session_key'] ?? '_csrf_tokens');
    }

    private static function ttl(): int
    {
        $config = $GLOBALS['app_config']['csrf'] ?? [];
        return (int)($config['ttl'] ?? 7200);
    }

    public static function token(string $form = 'default'): string
    {
        $tokens = Session::get(self::sessionKey(), []);
        $now = time();

        if (
            !isset($tokens[$form]['value'], $tokens[$form]['expires'])
            || $tokens[$form]['expires'] < $now
        ) {
            $tokens[$form] = [
                'value' => bin2hex(random_bytes(32)),
                'expires' => $now + self::ttl(),
            ];
            Session::set(self::sessionKey(), $tokens);
        }

        return $tokens[$form]['value'];
    }

    public static function verify(?string $token, string $form = 'default'): bool
    {
        $tokens = Session::get(self::sessionKey(), []);
        $stored = $tokens[$form]['value'] ?? null;
        $expires = $tokens[$form]['expires'] ?? 0;

        return is_string($token)
            && is_string($stored)
            && $expires >= time()
            && hash_equals($stored, $token);
    }

    public static function field(string $form = 'default'): string
    {
        return '<input type="hidden" name="' . Security::e(self::tokenName()) . '" value="' . Security::e(self::token($form)) . '">';
    }

    public static function meta(string $form = 'default'): string
    {
        return '<meta name="csrf-token" content="' . Security::e(self::token($form)) . '">';
    }

    public static function fromRequest(): ?string
    {
        $name = self::tokenName();

        if (isset($_POST[$name]) && is_string($_POST[$name])) {
            return $_POST[$name];
        }

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        $input = Security::jsonInput();
        return isset($input[$name]) && is_string($input[$name]) ? $input[$name] : null;
    }
}
