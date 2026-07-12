<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . "/app/core/bootstrap.php";

ApiMiddleware::handle([
    "methods" => ["POST"],
    "csrf_form" => "login",
]);

$input = Security::jsonInput();
$email = Security::cleanEmail((string) ($input["email"] ?? ""));
$password = (string) ($input["password"] ?? "");

if ($email === "" || $password === "") {
    Response::json(
        ["success" => false, "message" => "Email and password are required."],
        422,
    );
}

// ── Brute-force protection ────────────────────────────────────────────────────
// Block an IP after 10 failed login attempts within 15 minutes.
$clientIp = substr((string) ($_SERVER["REMOTE_ADDR"] ?? ""), 0, 45) ?: null;
$maxAttempts = 10;
$windowMins = 15;

if ($clientIp !== null) {
    try {
        $recentFailures = Database::fetch(
            "SELECT COUNT(*) AS total
               FROM audit_logs
              WHERE action    = 'login_failed'
                AND ip        = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$clientIp, $windowMins],
        );
        if ((int) ($recentFailures["total"] ?? 0) >= $maxAttempts) {
            Response::json(
                [
                    "success" => false,
                    "message" =>
                        "Too many failed sign-in attempts. Please wait " .
                        $windowMins .
                        " minutes and try again.",
                ],
                429,
            );
        }
    } catch (Throwable) {
        // If the rate-limit query fails, allow the request through (fail-open).
    }
}
// ─────────────────────────────────────────────────────────────────────────────

$audit = static function (
    ?int $userId,
    string $action,
    array $details = [],
) use ($email): void {
    try {
        $payload = array_merge(["email" => $email], $details);
        $severity = $action === "login_failed" ? "critical" : "info";
        $ip = substr((string) ($_SERVER["REMOTE_ADDR"] ?? ""), 0, 45) ?: null;
        $userAgent =
            substr((string) ($_SERVER["HTTP_USER_AGENT"] ?? ""), 0, 255) ?:
            null;
        $route = substr(
            (string) ($_SERVER["REQUEST_URI"] ?? "admin/api/login.php"),
            0,
            255,
        );
        $method = substr(
            (string) ($_SERVER["REQUEST_METHOD"] ?? "POST"),
            0,
            10,
        );
        Database::query(
            'INSERT INTO audit_logs
                (user_id, actor_role, action, module, target_id, details_json, ip, user_agent, request_method, route, severity, event_hash, metadata_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $details["role"] ?? null,
                $action,
                "auth",
                $userId ?? 0,
                json_encode(
                    $payload,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ),
                $ip,
                $userAgent,
                $method,
                $route,
                $severity,
                hash(
                    "sha256",
                    implode("|", [
                        (string) ($userId ?? 0),
                        $action,
                        "auth",
                        (string) $ip,
                        $email,
                    ]),
                ),
                json_encode(
                    [
                        "referer" =>
                            substr(
                                (string) ($_SERVER["HTTP_REFERER"] ?? ""),
                                0,
                                500,
                            ) ?:
                            null,
                    ],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ),
            ],
        );
    } catch (Throwable) {
        try {
            Database::query(
                'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $action,
                    "auth",
                    $userId ?? 0,
                    json_encode(
                        array_merge(["email" => $email], $details),
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    ),
                    $_SERVER["REMOTE_ADDR"] ?? null,
                    substr(
                        (string) ($_SERVER["HTTP_USER_AGENT"] ?? ""),
                        0,
                        255,
                    ),
                ],
            );
        } catch (Throwable) {
        }
    }
};

try {
    $user = Database::fetch(
        "SELECT u.id,
                u.first_name,
                u.last_name,
                CONCAT(u.first_name, ' ', u.last_name) AS name,
                u.email,
                u.avatar,
                u.job_title,
                u.password_hash,
                u.status,
                r.slug AS role,
                r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.email = ? AND u.status = 'active'
         LIMIT 1",
        [$email],
    );
} catch (Throwable) {
    Response::json(
        [
            "success" => false,
            "message" => "Database error. Please contact the administrator.",
        ],
        500,
    );
}

if (!$user || !password_verify($password, $user["password_hash"] ?? "")) {
    $audit(null, "login_failed");
    Response::json(
        ["success" => false, "message" => "Invalid email or password."],
        401,
    );
}

try {
    Database::beginTransaction();
    Database::query("UPDATE users SET last_login = NOW() WHERE id = ?", [
        $user["id"],
    ]);
    $audit((int) $user["id"], "login", ["role" => $user["role"] ?? ""]);
    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(
        [
            "success" => false,
            "message" => "Unable to complete sign in. Please try again.",
        ],
        500,
    );
}

Auth::login($user);

Response::json([
    "success" => true,
    "message" => "Signed in successfully.",
    "redirect" => Url::to("admin/index.php"),
]);
