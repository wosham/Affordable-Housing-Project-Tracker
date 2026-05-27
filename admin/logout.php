<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$userId = Auth::id();
if ($userId !== null) {
    try {
        Database::query(
            'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                'logout',
                'auth',
                $userId,
                json_encode(['email' => Auth::user()['email'] ?? ''], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
    } catch (Throwable) {
        // Audit logging must never block logout.
    }
}

Auth::logout();
Session::flash('status', 'You have been signed out.');
Response::redirect(Url::to('admin/login.php'));
