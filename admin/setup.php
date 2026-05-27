<?php
/**
 * AHPTC — One-time Super Admin Setup
 * ─────────────────────────────────────────────────────────────
 * Run ONCE after importing ahptc_schema.sql + ahptc_seeds.sql
 * Creates the Super Admin user with a properly hashed password.
 * DELETE THIS FILE immediately after use.
 * ─────────────────────────────────────────────────────────────
 * Access: http://localhost/Trans-Nzoia-Affordable-Housing/admin/setup.php
 */

// Localhost only
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1', '::ffff:127.0.0.1'])) {
    http_response_code(403);
    die('403 Forbidden — Setup only accessible from localhost.');
}

// DB Config (inline for safety — this file is deleted after use)
$host   = 'localhost';
$dbname = 'trans_nzoia_affordable_housing';
$user   = 'root';
$pass   = '';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminPass = $_POST['admin_password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (strlen($adminPass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($adminPass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Get superadmin role id
            $roleId = $pdo->query("SELECT id FROM roles WHERE slug = 'superadmin'")->fetchColumn();
            if (!$roleId) {
                $error = 'Roles not found — import ahptc_seeds.sql first.';
            } else {
                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users
                        (first_name, last_name, email, phone, password_hash, role_id, status)
                    VALUES
                        ('Moses', 'Awuor', 'director@transnzoia.go.ke', '+254 XXX XXX XXX', ?, ?, 'active')
                    ON DUPLICATE KEY UPDATE
                        password_hash = VALUES(password_hash),
                        role_id       = VALUES(role_id),
                        status        = 'active'
                ");
                $stmt->execute([$hash, $roleId]);
                $success = 'Super Admin created. Login: director@transnzoia.go.ke / ' . htmlspecialchars($adminPass);
            }
        } catch (PDOException $e) {
            $error = 'DB Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AHPTC — One-Time Setup</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;display:flex;align-items:center;justify-content:center;min-height:100vh}
  .card{background:#fff;border-radius:12px;padding:2.5rem 2rem;width:100%;max-width:440px;box-shadow:0 4px 20px rgba(0,0,0,.12)}
  .logo{text-align:center;margin-bottom:1.5rem}
  .logo h1{font-size:1.2rem;color:#163300;font-weight:800}
  .logo p{font-size:.8rem;color:#718096;margin-top:.25rem}
  .badge-warn{background:#fefcbf;color:#744210;border:1px solid #f6e05e;border-radius:8px;padding:.75rem 1rem;font-size:.8rem;margin-bottom:1.5rem;text-align:center}
  label{display:block;font-size:.85rem;font-weight:600;margin-bottom:.35rem;color:#1a202c}
  input{width:100%;padding:.6rem .9rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.875rem;margin-bottom:1rem}
  input:focus{outline:none;border-color:#163300}
  button{width:100%;padding:.75rem;background:#163300;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer}
  button:hover{background:#1a4a00}
  .alert-success{background:#c6f6d5;color:#22543d;border-radius:8px;padding:.85rem 1rem;margin-bottom:1rem;font-size:.875rem}
  .alert-error  {background:#fed7d7;color:#742a2a;border-radius:8px;padding:.85rem 1rem;margin-bottom:1rem;font-size:.875rem}
  .delete-note{margin-top:1.5rem;font-size:.75rem;color:#e53e3e;text-align:center;font-weight:600}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>AHPTC Admin Setup</h1>
    <p>Trans-Nzoia Affordable Housing Programme Tracker</p>
  </div>

  <div class="badge-warn">⚠ One-time setup. Delete this file immediately after use.</div>

  <?php if ($success): ?>
    <div class="alert-success">✅ <?= $success ?></div>
    <p style="font-size:.8rem;color:#718096;text-align:center">
      <a href="/Trans-Nzoia-Affordable-Housing/admin/login.php">→ Go to Admin Login</a>
    </p>
    <p class="delete-note">DELETE admin/setup.php now!</p>
  <?php else: ?>

    <?php if ($error): ?>
      <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <p style="font-size:.8rem;color:#718096;margin-bottom:1.25rem">
        Creates the Super Admin account for <strong>Moses Awuor</strong>
        (<code>director@transnzoia.go.ke</code>). Set a secure password below.
      </p>

      <label>Admin Password</label>
      <input type="password" name="admin_password" placeholder="Min. 8 characters" required>

      <label>Confirm Password</label>
      <input type="password" name="confirm_password" placeholder="Repeat password" required>

      <button type="submit">Create Super Admin Account</button>
    </form>

    <p class="delete-note">DELETE this file after completing setup</p>
  <?php endif; ?>
</div>
</body>
</html>
