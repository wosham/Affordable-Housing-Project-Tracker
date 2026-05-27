<?php
// Mail Configuration — SMTP settings for system notifications
return [
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => '',                         // TODO: Set SMTP username
    'password'   => '',                         // TODO: Set SMTP password (use env var in production)
    'from_email' => 'noreply@transnzoia.go.ke',
    'from_name'  => 'Trans-Nzoia AHP Tracker',
];
