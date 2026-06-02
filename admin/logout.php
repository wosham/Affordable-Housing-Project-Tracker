<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$userId = Auth::id();
if ($userId !== null) {
    Logger::log('logout', 'auth', (int)$userId, ['email' => Auth::user()['email'] ?? '']);
}

Auth::logout();
Session::flash('status', 'You have been signed out.');
Response::redirect(Url::to('admin/login.php'));
