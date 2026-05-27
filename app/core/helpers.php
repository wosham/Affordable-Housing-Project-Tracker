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
            'active', 'approved', 'certified', 'completed', 'complete', 'paid', 'published', 'open', 'passed' => 'badge--success',
            'pending', 'submitted', 'endorsed', 'planning', 'draft', 'in_review', 'awaiting_payment' => 'badge--warning',
            'rejected', 'cancelled', 'canceled', 'suspended', 'failed', 'closed', 'overdue' => 'badge--danger',
            'on_hold', 'in_progress', 'processing', 'unread' => 'badge--info',
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
            'superadmin' => 'Super Administrator',
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
            return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)) . '…' : $text;
        }

        return strlen($text) > $limit ? rtrim(substr($text, 0, $limit - 1)) . '...' : $text;
    }
}
