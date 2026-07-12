<?php

require_once dirname(__DIR__) . '/helpers/functions.php';

if (!function_exists('nav_active_class')) {
    function nav_active_class(string $pageOrPath, string $activePageOrClass = 'is-active'): string
    {
        if (str_contains($pageOrPath, '/') || str_ends_with($pageOrPath, '.php')) {
            return Url::isActive($pageOrPath) ? $activePageOrClass : '';
        }

        return $pageOrPath === $activePageOrClass ? ' is-active' : '';
    }
}

if (!function_exists('admin_nav_active')) {
    function admin_nav_active(string $path): string
    {
        return Url::isActive($path) ? ' is-active' : '';
    }
}

if (!function_exists('format_money')) {
    function format_money(float|int|string|null $amount, string $currency = 'KES'): string
    {
        return trim($currency . ' ' . number_format((float)($amount ?? 0), 2));
    }
}

if (!function_exists('format_number')) {
    function format_number(float|int|string|null $value, int $decimals = 0): string
    {
        return number_format((float)($value ?? 0), max(0, $decimals));
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd M Y'): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);
        return $timestamp === false ? '-' : date($format, $timestamp);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $date, string $format = 'd M Y, H:i'): string
    {
        return format_date($date, $format);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '-';
        }

        $diff = max(0, time() - $timestamp);

        if ($diff < 60) {
            return 'just now';
        }

        $units = [
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'min',
        ];

        foreach ($units as $seconds => $label) {
            if ($diff >= $seconds) {
                $value = (int)floor($diff / $seconds);
                $plural = $value === 1 || $label === 'min' ? '' : 's';
                return $value . ' ' . $label . $plural . ' ago';
            }
        }

        return 'just now';
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class(?string $status): string
    {
        $status = strtolower(trim((string)$status));

        return match ($status) {
            'active', 'approved', 'certified', 'completed', 'complete', 'paid', 'published', 'open', 'passed', 'accepted', 'resolved', 'clerk-endorsed' => 'badge--success',
            'pending', 'submitted', 'endorsed', 'planning', 'draft', 'in_review', 'awaiting_payment', 'queried', 'minor', 'low' => 'badge--warning',
            'rejected', 'cancelled', 'canceled', 'suspended', 'failed', 'closed', 'overdue', 'returned', 'flagged', 'critical', 'major', 'high', 'rework-required', 'fatality' => 'badge--danger',
            'on_hold', 'in_progress', 'processing', 'unread', 'investigating', 'action-pending', 'medium', 'recorded', 'reviewed' => 'badge--info',
            default => 'badge--neutral',
        };
    }
}

if (!function_exists('status_label')) {
    function status_label(?string $status): string
    {
        $status = trim((string)$status);
        if ($status === '') {
            return 'Unknown';
        }

        return ucwords(str_replace(['_', '-'], ' ', $status));
    }
}

if (!function_exists('user_initials')) {
    function user_initials(array|string|null $user): string
    {
        if (is_array($user)) {
            $name = trim((string)($user['name'] ?? ''));
            if ($name === '') {
                $name = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
            }
            if ($name === '') {
                $name = (string)($user['email'] ?? '');
            }
        } else {
            $name = trim((string)$user);
        }

        if ($name === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';
        $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

        return $initials !== '' ? $initials : 'U';
    }
}

if (!function_exists('percentage')) {
    function percentage(float|int|string|null $value, int $min = 0, int $max = 100): int
    {
        $min = min($min, $max);
        $max = max($min, $max);
        $value = (int)round((float)($value ?? 0));

        return max($min, min($max, $value));
    }
}

if (!function_exists('format_percentage')) {
    function format_percentage(float|int|string|null $value): string
    {
        return percentage($value) . '%';
    }
}

if (!function_exists('role_label')) {
    function role_label(?string $role): string
    {
        return match (strtolower(trim((string)$role))) {
            'superadmin' => 'County Director',
            'manager' => 'Project Manager',
            'consultant' => 'Resident Engineer / Consultant',
            'contractor' => 'Contractor',
            'clerk' => 'Site Clerk',
            'finance' => 'Finance Officer',
            'intern' => 'Site Intern',
            default => 'Staff',
        };
    }
}

if (!function_exists('role_badge_class')) {
    function role_badge_class(?string $role): string
    {
        $role = strtolower(trim((string)$role));
        $role = preg_match('/^[a-z0-9_-]+$/', $role) ? $role : 'staff';

        return 'role-badge role-badge--' . $role;
    }
}

if (!function_exists('current_user_name')) {
    function current_user_name(): string
    {
        $name = Auth::name();
        return $name !== '' ? $name : 'Staff User';
    }
}

if (!function_exists('current_user_initials')) {
    function current_user_initials(): string
    {
        return user_initials(Auth::user());
    }
}

if (!function_exists('current_user_avatar')) {
    function current_user_avatar(): string
    {
        $user = Auth::user();
        return is_array($user) ? trim((string)($user['avatar'] ?? '')) : '';
    }
}

if (!function_exists('current_user_title')) {
    function current_user_title(): string
    {
        $user = Auth::user();
        $title = is_array($user) ? trim((string)($user['job_title'] ?? '')) : '';
        return $title !== '' ? $title : current_user_role_label();
    }
}

if (!function_exists('current_user_role_label')) {
    function current_user_role_label(): string
    {
        return role_label(Auth::role());
    }
}

if (!function_exists('safe_truncate')) {
    function safe_truncate(?string $text, int $limit = 120): string
    {
        $text = trim((string)$text);
        if ($limit <= 0 || $text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, max(0, $limit - 3))) . '...' : $text;
        }

        return strlen($text) > $limit ? rtrim(substr($text, 0, $limit - 1)) . '...' : $text;
    }
}

if (!function_exists('public_setting')) {
function public_setting(string $key, mixed $fallback = ''): mixed
{
    if (!class_exists('CmsSetting')) {
        return '';
    }

    try {
        $value = CmsSetting::get($key);
    } catch (Throwable) {
        return '';
    }

    if ($value === null || $value === '') {
        return '';
    }

    return $value;
}
}

if (!function_exists('public_asset')) {
    function public_asset(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) {
            return $path;
        }

        return class_exists('Url') ? Url::asset($path) : $path;
    }
}

if (!function_exists('public_media_path')) {
    function public_media_path(string $path, string $fallback = ''): string
    {
        $path = trim($path);
        if ($path === '' || str_starts_with($path, 'secure-uploads/')) {
            return $fallback;
        }

        return $path;
    }
}

if (!function_exists('public_image_dimensions')) {
    function public_image_dimensions(string $path): array
    {
        $path = trim($path);
        if ($path === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) || str_starts_with($path, 'data:')) {
            return [];
        }

        $absolute = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/\\'));
        if (!is_file($absolute)) {
            return [];
        }

        $size = @getimagesize($absolute);
        if (!is_array($size) || empty($size[0]) || empty($size[1])) {
            return [];
        }

        return ['width' => (int)$size[0], 'height' => (int)$size[1]];
    }
}

if (!function_exists('public_image_attrs')) {
    function public_image_attrs(string $path, string $alt = '', array $options = []): string
    {
        $fallback = (string)($options['fallback'] ?? '');
        $path = public_media_path($path, $fallback);
        $src = public_asset($path);
        $loading = (string)($options['loading'] ?? 'lazy');
        $decoding = (string)($options['decoding'] ?? ($loading === 'eager' ? 'sync' : 'async'));
        $attrs = [
            'src' => $src,
            'alt' => $alt,
            'loading' => $loading,
            'decoding' => $decoding,
        ];

        if (!empty($options['fetchpriority'])) {
            $attrs['fetchpriority'] = (string)$options['fetchpriority'];
        }

        foreach (public_image_dimensions($path) as $key => $value) {
            $attrs[$key] = (string)$value;
        }

        if (!empty($options['class'])) {
            $attrs['class'] = (string)$options['class'];
        }
        if (!empty($options['onerror'])) {
            $attrs['onerror'] = (string)$options['onerror'];
        }

        $html = [];
        foreach ($attrs as $key => $value) {
            if ($value === '') {
                continue;
            }
            $html[] = $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return implode(' ', $html);
    }
}

if (!function_exists('public_url')) {
    function public_url(string $path = ''): string
    {
        return class_exists('Url') ? Url::to($path) : $path;
    }
}

if (!function_exists('public_navigation_links')) {
    function public_navigation_links(string $area): array
    {
        if (!class_exists('Database')) {
            return [];
        }

        try {
            return Database::fetchAll(
                'SELECT label, href, page_key, is_external
                 FROM navigation_links
                 WHERE area = ? AND is_visible = 1
                 ORDER BY sort_order ASC, id ASC',
                [$area]
            );
        } catch (Throwable) {
            return [];
        }
    }
}

if (!function_exists('asset_version')) {
    function asset_version(string $path): string
    {
        $configured = getenv('ASSET_VERSION') ?: '';
        if ($configured !== '') {
            return $configured;
        }

        $absolute = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
        return is_file($absolute) ? (string)filemtime($absolute) : (string)time();
    }
}

if (!function_exists('public_json_script')) {
    function public_json_script(string $id, array $payload): string
    {
        if (class_exists('CmsLoader')) {
            return CmsLoader::jsonScript($id, $payload);
        }

        $id = preg_replace('/[^a-zA-Z0-9_-]/', '-', $id) ?: 'page-data';
        $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        return '<script type="application/json" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . ($json ?: '{}') . '</script>';
    }
}

if (!function_exists('public_page_payload')) {
function public_page_payload(string $slug, array $extraModels = [], array $fallback = []): array
{
    if (class_exists('PublicDataContract')) {
        return PublicDataContract::payload($slug, $extraModels, []);
    }

    if (class_exists('CmsLoader')) {
        return CmsLoader::publicPayload($slug, $extraModels, []);
    }

    return [
        'slug' => $slug,
        'page' => [],
        'sections' => [],
        'models' => $extraModels,
        'fallback' => [],
    ];
}
}

if (!function_exists('public_page_json')) {
function public_page_json(string $slug, array $extraModels = [], array $fallback = [], string $id = 'ahp-page-data'): string
{
    return public_json_script($id, public_page_payload($slug, $extraModels, []));
}
}

if (!function_exists('format_bytes')) {
    function format_bytes(float|int|string|null $bytes, int $precision = 2): string
    {
        $bytes = (float)($bytes ?? 0);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
