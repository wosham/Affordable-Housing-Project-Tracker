<?php

return [
    'name' => 'Trans-Nzoia AHP Tracker',
    'env' => 'local',
    'debug' => true,
    'timezone' => 'Africa/Nairobi',
    'base_path' => dirname(__DIR__, 2),
    'base_url' => '/Trans-Nzoia-Affordable-Housing',
    'session' => [
        'name' => 'tnah_session',
        'lifetime' => 7200,
        'save_path' => dirname(__DIR__, 2) . '/app/storage/sessions',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'csrf' => [
        'token_name' => '_csrf_token',
        'session_key' => '_csrf_tokens',
        'ttl' => 7200,
    ],
    'security' => [
        'allowed_redirect_hosts' => ['localhost', '127.0.0.1'],
        'max_upload_bytes' => 52428800,
        'allowed_upload_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'mov'],
    ],
];
