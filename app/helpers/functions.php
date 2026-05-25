<?php

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Security::e($value);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return Url::to($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Url::asset($path);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(string $form = 'default'): string
    {
        return Csrf::token($form);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(string $form = 'default'): string
    {
        return Csrf::field($form);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value = null): mixed
    {
        return func_num_args() === 2 ? Session::flash($key, $value) : Session::flash($key);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::get('_old_input', []);
        return $old[$key] ?? $default;
    }
}
