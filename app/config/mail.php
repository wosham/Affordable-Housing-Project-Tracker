<?php
// Mail Configuration — SMTP settings for system notifications
return [
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => '',                         // TODO: Set SMTP username
    'password'   => getenv('SMTP_PASSWORD') ?: '',
    'from_email' => 'noreply@transnzoia.go.ke',
    'from_name'  => 'Trans-Nzoia AHP Tracker',
];
