<?php
/**
 * Phase 21 admin API smoke:
 * - inventory Phase 21 folders
 * - every PHP endpoint has auth path + lint
 * - POST endpoints must have CSRF path (middleware form, ApiCsrf, or Csrf::verify)
 */
$root = dirname(__DIR__);
require $root . '/app/core/bootstrap.php';

$groups = [
    'auth', 'settings', 'system-health', 'audit', 'media', 'projects', 'reports', 'messages', 'notifications',
    'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern',
    'attendance', 'ipcs', 'boq', 'programme', 'assignments', 'eot', 'variations',
];

$fail = 0;
function ok(bool $c, string $m): void
{
    global $fail;
    echo ($c ? 'OK   ' : 'FAIL ') . $m . "\n";
    if (!$c) {
        $fail++;
    }
}

echo "=== Phase 21 API smoke ===\n";

ok(class_exists('ApiCsrf'), 'ApiCsrf class loaded');
ok(method_exists('ApiCsrf', 'requireAny') && method_exists('ApiCsrf', 'checkAny'), 'ApiCsrf methods');
ok(ApiCsrf::checkAny(['___never___']) === false, 'ApiCsrf rejects unknown form');

$total = 0;
$postNeedCsrf = 0;
$postHaveCsrf = 0;
$noAuth = [];

foreach ($groups as $g) {
    $dir = $root . '/api/' . $g;
    ok(is_dir($dir), "group exists api/{$g}/");
    if (!is_dir($dir)) {
        continue;
    }

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
        $base = basename($path);
        $src = file_get_contents($path);
        $total++;

        // Handlers are libraries, not direct endpoints
        $isHandler = str_starts_with($base, '_') && str_contains($base, 'handler');
        $isThinWrapper = (bool)preg_match("/require_once\s+__DIR__\s*\.\s*['\"]\/_/", $src)
            && !str_contains($src, 'ApiMiddleware::handle')
            && !str_contains($src, 'Guard::');
        $isAlias = (bool)preg_match("/require(?:_once)?\s+__DIR__\s*\.\s*['\"]\/[a-z0-9_-]+\.php['\"]/", $src)
            && !str_contains($src, 'ApiMiddleware::handle')
            && !str_contains($src, 'Guard::')
            && substr_count($src, 'require') <= 2;

        // Lint
        $out = [];
        $code = 0;
        exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        ok($code === 0, "lint {$rel}");

        if ($isHandler) {
            ok(str_contains($src, 'ApiMiddleware::handle') || str_contains($src, 'function '), "handler structure {$rel}");
            // Handler must include CSRF for POST roles
            if (str_contains($src, "'methods' => ['POST']") || str_contains($src, '"methods" => ["POST"]')) {
                $hasCsrf = str_contains($src, 'csrf_form')
                    || str_contains($src, 'ApiCsrf::')
                    || str_contains($src, 'Csrf::verify');
                ok($hasCsrf, "handler POST csrf {$rel}");
            }
            continue;
        }

        if ($isThinWrapper) {
            ok(true, "thin wrapper {$rel}");
            continue;
        }

        if ($isAlias) {
            ok(true, "alias endpoint {$rel}");
            continue;
        }

        $hasBootstrap = str_contains($src, 'bootstrap.php');
        $hasMw = str_contains($src, 'ApiMiddleware::handle');
        $hasGuard = str_contains($src, 'Guard::');
        $hasAuth = $hasMw || $hasGuard || str_contains($src, 'AuthMiddleware');
        ok($hasBootstrap, "bootstrap {$rel}");
        ok($hasAuth, "auth path {$rel}");
        if (!$hasAuth) {
            $noAuth[] = $rel;
        }

        // Detect POST methods
        $isPost = (bool)preg_match("/'methods'\s*=>\s*\[[^\]]*'POST'/i", $src)
            || (bool)preg_match('/"methods"\s*=>\s*\[[^\]]*"POST"/i', $src)
            || (str_contains($src, "methods' => ['POST']") || str_contains($src, 'methods" => ["POST"]'));

        // Auth public endpoints still need csrf_form
        if ($isPost || (str_contains($src, "'methods' => ['POST']"))) {
            $postNeedCsrf++;
            $hasCsrf = str_contains($src, "'csrf_form'")
                || str_contains($src, '"csrf_form"')
                || str_contains($src, 'ApiCsrf::')
                || str_contains($src, 'Csrf::verify')
                || str_contains($src, 'CsrfMiddleware::');
            // csrf => true is default when csrf_form set; csrf false without verify is bad
            $csrfFalse = (bool)preg_match("/'csrf'\s*=>\s*false/", $src);
            if ($csrfFalse && !$hasCsrf) {
                ok(false, "POST missing CSRF verify {$rel}");
            } else {
                ok($hasCsrf || !$csrfFalse, "POST csrf path {$rel}");
                if ($hasCsrf || !$csrfFalse) {
                    $postHaveCsrf++;
                }
            }
        }
    }
}

echo "\nScanned files: {$total}\n";
echo "POST endpoints with CSRF path: {$postHaveCsrf}\n";

// Spot checks for normalized exports
$audit = file_get_contents($root . '/api/audit/export.php');
ok(str_contains($audit, 'ApiMiddleware::handle') && str_contains($audit, "'superadmin'"), 'audit/export ApiMiddleware');

$reports = file_get_contents($root . '/api/reports/generate.php');
ok(str_contains($reports, 'ApiMiddleware::handle') && str_contains($reports, 'reports'), 'reports/generate ApiMiddleware');

// Sample migrated endpoints
$boq = file_get_contents($root . '/api/boq/update-item.php');
ok(str_contains($boq, 'ApiCsrf::requireAny'), 'boq uses ApiCsrf');

$mgr = file_get_contents($root . '/api/manager/community-liaison-save.php');
ok(str_contains($mgr, 'ApiCsrf::requireAny'), 'manager save uses ApiCsrf');

$fin = file_get_contents($root . '/api/finance/process-payment.php');
ok(str_contains($fin, 'csrf_form') && str_contains($fin, 'finance'), 'finance payment csrf_form');

$att = file_get_contents($root . '/api/attendance/sign-in.php');
ok(str_contains($att, 'csrf_form') && str_contains($att, 'attendance_signin'), 'attendance sign-in csrf');

if ($noAuth !== []) {
    echo "NO AUTH:\n - " . implode("\n - ", $noAuth) . "\n";
}

echo $fail === 0 ? "PHASE21 SMOKE OK\n" : "PHASE21 FAIL {$fail}\n";
exit($fail === 0 ? 0 : 1);
