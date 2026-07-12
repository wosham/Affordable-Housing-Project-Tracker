<?php

class ReportBuilder extends Model
{
    public const TYPES = [
        'executive_summary' => [
            'label' => 'Executive Summary',
            'description' => 'County leadership brief covering delivery, finance, attendance and risk signals.',
            'icon' => 'fa-file-signature',
            'roles' => ['superadmin', 'manager'],
        ],
        'project_progress' => [
            'label' => 'Project Progress',
            'description' => 'Project delivery, completion, milestones, programme tasks and overdue work.',
            'icon' => 'fa-chart-line',
            'roles' => ['superadmin', 'manager'],
        ],
        'financial' => [
            'label' => 'Financial Report',
            'description' => 'Contract values, IPC totals, payments, retention and BOQ exposure.',
            'icon' => 'fa-file-invoice-dollar',
            'roles' => ['superadmin', 'finance'],
        ],
        'attendance' => [
            'label' => 'Attendance Report',
            'description' => 'Site sign-ins, project attendance, roles, GPS flags and review exceptions.',
            'icon' => 'fa-user-check',
            'roles' => ['superadmin', 'manager'],
        ],
        'project_register' => [
            'label' => 'Project Register',
            'description' => 'Full project register with constituency, contractor, units, value and delivery dates.',
            'icon' => 'fa-building',
            'roles' => ['superadmin', 'manager', 'finance'],
        ],
        'public_content' => [
            'label' => 'Public Content',
            'description' => 'News, announcements, contacts, subscribers, gallery and CMS visibility.',
            'icon' => 'fa-newspaper',
            'roles' => ['superadmin'],
        ],
    ];

    public const FORMATS = ['json', 'csv', 'html'];

    public static function typesForRole(string $role): array
    {
        return array_filter(self::TYPES, static fn (array $type): bool => in_array($role, $type['roles'], true));
    }

    public static function canGenerate(string $role, string $type): bool
    {
        return isset(self::TYPES[$type]) && in_array($role, self::TYPES[$type]['roles'], true);
    }

    public static function filters(array $input): array
    {
        $start = self::dateOrDefault($input['date_from'] ?? '', date('Y-m-01'));
        $end = self::dateOrDefault($input['date_to'] ?? '', date('Y-m-d'));

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [
            'date_from' => $start,
            'date_to' => $end,
            'project_id' => self::positiveInt($input['project_id'] ?? null),
            'constituency_id' => self::positiveInt($input['constituency_id'] ?? null),
            'status' => Security::cleanString((string)($input['status'] ?? '')),
        ];
    }

    public static function generate(string $type, array $filters): array
    {
        if (!isset(self::TYPES[$type])) {
            throw new InvalidArgumentException('Unknown report type.');
        }

        $filters = self::filtersForType($filters, $type);

        return match ($type) {
            'executive_summary' => self::executiveSummary($filters),
            'project_progress' => self::projectProgress($filters),
            'financial' => self::financial($filters),
            'attendance' => self::attendance($filters),
            'project_register' => self::projectRegister($filters),
            'public_content' => self::publicContent($filters),
            default => throw new InvalidArgumentException('Unknown report type.'),
        };
    }

    public static function recordRun(?int $userId, string $type, string $format, array $filters, int $rowCount, string $status = 'generated', ?string $scopeRole = null, ?int $scopeUserId = null): int
    {
        try {
            return self::createRun([
                'user_id' => $userId ?: null,
                'report_type' => $type,
                'format' => $format,
                'filters_json' => json_encode($filters, JSON_UNESCAPED_SLASHES),
                'row_count' => $rowCount,
                'status' => $status,
                'scope_role' => $scopeRole,
                'scope_user_id' => $scopeUserId,
            ]);
        } catch (Throwable) {
            return 0;
        }
    }

    public static function recentRuns(int $limit = 8): array
    {
        try {
            return Database::fetchAll(
                'SELECT rr.*, COALESCE(CONCAT(u.first_name, " ", u.last_name), "System") AS user_name
                 FROM report_runs rr
                 LEFT JOIN users u ON u.id = rr.user_id
                 ORDER BY rr.created_at DESC, rr.id DESC
                 LIMIT ' . max(1, min(30, $limit))
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function csv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Trans-Nzoia Affordable Housing Programme Tracker']);
        fputcsv($handle, [$report['title'] ?? 'Report']);
        fputcsv($handle, ['Generated', $report['generated_at'] ?? date('Y-m-d H:i:s')]);
        fputcsv($handle, ['Date From', $report['filters']['date_from'] ?? '']);
        fputcsv($handle, ['Date To', $report['filters']['date_to'] ?? '']);
        fputcsv($handle, ['Project ID', $report['filters']['project_id'] ?? 'All']);
        fputcsv($handle, ['Constituency ID', $report['filters']['constituency_id'] ?? 'All']);
        fputcsv($handle, ['Status', $report['filters']['status'] ?: 'Any']);
        fputcsv($handle, []);
        fputcsv($handle, ['Summary']);
        foreach (($report['summary'] ?? []) as $label => $value) {
            fputcsv($handle, [self::heading((string)$label), self::csvValue($value)]);
        }
        fputcsv($handle, []);

        foreach (($report['sections'] ?? []) as $section) {
            fputcsv($handle, [$section['title'] ?? 'Section']);
            $rows = $section['rows'] ?? [];
            if ($rows === []) {
                fputcsv($handle, ['No data']);
                fputcsv($handle, []);
                continue;
            }

            $headers = array_keys($rows[0]);
            fputcsv($handle, array_map([self::class, 'heading'], $headers));
            foreach ($rows as $row) {
                fputcsv($handle, array_map(static fn ($value): string => self::csvValue($value), array_values($row)));
            }
            fputcsv($handle, []);
        }

        rewind($handle);
        return (string)stream_get_contents($handle);
    }

    public static function html(array $report): string
    {
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= Security::e($report['title'] ?? 'Report') ?></title>
  <style>
    :root{--green:#163300;--lime:#a6ef27;--line:#d8e1cf;--muted:#667085;--soft:#f5f8f1}
    *{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:0;color:#101828;background:#fff}.page{padding:24px}.toolbar{position:sticky;top:0;display:flex;justify-content:flex-end;padding:12px 24px;background:#fff;border-bottom:1px solid var(--line)}button{border:0;border-radius:6px;background:var(--green);color:#fff;padding:9px 14px;font-weight:700;cursor:pointer}.letterhead{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:start;border-bottom:3px solid var(--green);padding-bottom:16px;margin-bottom:18px}.brand{display:grid;gap:4px}.brand small{color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em}.brand h1{margin:0;font-size:25px;line-height:1.15;color:var(--green)}.stamp{border:1px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--soft);font-size:12px;color:var(--muted)}.meta{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:12px 0 18px}.meta div,.summary div{border:1px solid var(--line);border-radius:8px;padding:10px;background:#fff}.meta strong,.summary strong{display:block;color:#101828}.meta span,.summary span{display:block;color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase}.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:18px 0}.summary strong{font-size:19px;color:var(--green)}h2{margin:24px 0 8px;font-size:16px;color:var(--green)}.empty{color:var(--muted);border:1px dashed var(--line);border-radius:8px;padding:12px;background:var(--soft)}table{width:100%;border-collapse:collapse;margin-bottom:16px;page-break-inside:auto}tr{page-break-inside:avoid;page-break-after:auto}th,td{border:1px solid var(--line);padding:7px;text-align:left;font-size:11px;vertical-align:top}th{background:var(--green);color:#fff}tbody tr:nth-child(even){background:#fbfcfa}.footer{margin-top:20px;border-top:1px solid var(--line);padding-top:10px;color:var(--muted);font-size:11px}@page{size:A4 landscape;margin:10mm}@media print{.toolbar{display:none}.page{padding:0}.letterhead{margin-top:0}.summary,.meta{grid-template-columns:repeat(4,1fr)}}
  </style>
</head>
<body>
<?php if (SystemConfig::bool('reports.include_print_toolbar', true)): ?>
  <div class="toolbar"><button onclick="window.print()">Print report</button></div>
<?php endif; ?>
  <main class="page">
  <header class="letterhead">
    <div class="brand">
      <small>Trans-Nzoia Affordable Housing Programme Tracker</small>
      <h1><?= Security::e($report['title'] ?? 'Report') ?></h1>
      <span><?= Security::e($report['description'] ?? '') ?></span>
    </div>
    <div class="stamp">Generated<br><strong><?= Security::e($report['generated_at'] ?? date('Y-m-d H:i:s')) ?></strong></div>
  </header>
  <section class="meta">
    <div><strong><?= Security::e($report['filters']['date_from'] ?? '') ?></strong><span>Date From</span></div>
    <div><strong><?= Security::e($report['filters']['date_to'] ?? '') ?></strong><span>Date To</span></div>
    <div><strong><?= Security::e((string)($report['filters']['project_id'] ?? 'All')) ?></strong><span>Project ID</span></div>
    <div><strong><?= Security::e((string)(($report['filters']['status'] ?? '') ?: 'Any')) ?></strong><span>Status</span></div>
  </section>
  <section class="summary">
<?php foreach (($report['summary'] ?? []) as $label => $value): ?>
    <div><strong><?= Security::e(self::displayValue((string)$label, $value)) ?></strong><span><?= Security::e(self::heading((string)$label)) ?></span></div>
<?php endforeach; ?>
  </section>
<?php foreach (($report['sections'] ?? []) as $section): ?>
  <h2><?= Security::e($section['title'] ?? 'Section') ?></h2>
<?php $rows = $section['rows'] ?? []; ?>
<?php if ($rows === []): ?>
  <p class="empty">No data matched this section.</p>
<?php else: ?>
  <table>
    <thead><tr><?php foreach (array_keys($rows[0]) as $header): ?><th><?= Security::e(self::heading((string)$header)) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
<?php foreach ($rows as $row): ?>
      <tr><?php foreach ($row as $key => $value): ?><td><?= Security::e(self::displayValue((string)$key, $value)) ?></td><?php endforeach; ?></tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php endforeach; ?>
  <footer class="footer"><?= Security::e(SystemConfig::text('reports.footer_note', 'Generated from live AHPTC information. Verify figures against approved source documents before statutory filing.')) ?></footer>
  </main>
</body>
</html>
        <?php
        return (string)ob_get_clean();
    }

    public static function projectsForFilter(): array
    {
        return self::rows('SELECT id, name FROM projects ORDER BY name');
    }

    public static function constituenciesForFilter(): array
    {
        return self::rows('SELECT id, name FROM constituencies ORDER BY name');
    }

    private static function executiveSummary(array $filters): array
    {
        $progress = self::projectProgress($filters);
        $finance = self::financial($filters);
        $attendance = self::attendance($filters);

        return self::report('executive_summary', $filters, [
            'project_count' => $progress['summary']['projects'] ?? 0,
            'average_completion' => $progress['summary']['average_completion'] ?? 0,
            'contract_value' => $finance['summary']['contract_value'] ?? 0,
            'paid_to_date' => $finance['summary']['paid_to_date'] ?? 0,
            'sign_ins' => $attendance['summary']['records'] ?? 0,
            'gps_flags' => $attendance['summary']['geo_fail'] ?? 0,
        ], [
            ['title' => 'Project Progress', 'rows' => array_slice($progress['sections'][0]['rows'] ?? [], 0, 12)],
            ['title' => 'Financial Exposure', 'rows' => array_slice($finance['sections'][0]['rows'] ?? [], 0, 12)],
            ['title' => 'Attendance Coverage', 'rows' => array_slice($attendance['sections'][0]['rows'] ?? [], 0, 12)],
        ]);
    }

    private static function projectProgress(array $filters): array
    {
        [$where, $bindings] = self::projectWhere($filters, 'p');
        $projects = self::rows(
            "SELECT p.name, c.name AS constituency, p.status, p.units, p.pct_complete, p.current_milestone, p.est_delivery,
                    COALESCE(SUM(CASE WHEN pt.status NOT IN ('complete','cancelled') AND pt.planned_end < CURDATE() THEN 1 ELSE 0 END), 0) AS delayed_tasks,
                    COALESCE(SUM(CASE WHEN pt.critical_path = 1 THEN 1 ELSE 0 END), 0) AS critical_tasks
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN programme_tasks pt ON pt.project_id = p.id
             {$where}
             GROUP BY p.id, p.name, c.name, p.status, p.units, p.pct_complete, p.current_milestone, p.est_delivery
             ORDER BY p.updated_at DESC",
            $bindings
        );
        $tasks = self::rows(
            "SELECT p.name AS project, pt.task_name, pt.status, pt.pct_complete, pt.planned_start, pt.planned_end, pt.critical_path,
                    COALESCE(CONCAT(u.first_name, ' ', u.last_name), '-') AS assigned_to
             FROM programme_tasks pt
             JOIN projects p ON p.id = pt.project_id
             LEFT JOIN users u ON u.id = pt.assigned_to
             " . self::projectWhereForJoinedProject($filters, 'p') . "
             ORDER BY p.name, pt.sort_order, pt.planned_start
             LIMIT 500"
        );

        return self::report('project_progress', $filters, [
            'projects' => count($projects),
            'average_completion' => self::average($projects, 'pct_complete'),
            'delayed_tasks' => array_sum(array_column($projects, 'delayed_tasks')),
            'critical_tasks' => array_sum(array_column($projects, 'critical_tasks')),
        ], [
            ['title' => 'Project Progress', 'rows' => $projects],
            ['title' => 'Programme Tasks', 'rows' => $tasks],
        ]);
    }

    private static function financial(array $filters): array
    {
        [$where, $bindings] = self::projectWhere($filters, 'p');
        $rows = self::rows(
            "SELECT p.name, c.name AS constituency, p.contract_sum,
                    COALESCE(pay.paid, 0) AS paid,
                    COALESCE(ipc.gross, 0) AS ipc_gross,
                    COALESCE(ipc.retention, 0) AS retention,
                    COALESCE(ipc.net, 0) AS ipc_net,
                    COALESCE(latest.status, '-') AS latest_ipc_status
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN (
                SELECT project_id, SUM(amount) AS paid
                FROM payments
                WHERE payment_date BETWEEN ? AND ? AND status = 'processed'
                GROUP BY project_id
             ) pay ON pay.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(gross_amount) AS gross, SUM(retention_amount) AS retention, SUM(net_amount) AS net
                FROM ipcs
                GROUP BY project_id
             ) ipc ON ipc.project_id = p.id
             LEFT JOIN (
                SELECT i1.project_id, i1.status
                FROM ipcs i1
                JOIN (
                    SELECT project_id, MAX(id) AS id
                    FROM ipcs
                    GROUP BY project_id
                ) i2 ON i2.id = i1.id
             ) latest ON latest.project_id = p.id
             {$where}
             ORDER BY p.name",
            array_merge([$filters['date_from'], $filters['date_to']], $bindings)
        );
        $boq = self::rows(
            "SELECT p.name AS project, b.section, b.item_no, b.description, b.quantity, b.certified_qty, b.paid_qty, b.rate,
                    CASE WHEN b.certified_qty > b.quantity OR b.paid_qty > b.certified_qty THEN 'risk' ELSE b.status END AS status
             FROM boq_items b
             JOIN projects p ON p.id = b.project_id
             WHERE (b.certified_qty > b.quantity OR b.paid_qty > b.certified_qty)"
             . (!empty($filters['project_id']) ? ' AND p.id = ' . (int)$filters['project_id'] : '')
             . (empty($filters['project_id']) && !empty($filters['project_ids']) && is_array($filters['project_ids']) ? ' AND p.id IN (' . implode(',', array_values(array_filter(array_map('intval', $filters['project_ids'])))) . ')' : '')
             . (!empty($filters['constituency_id']) ? ' AND p.constituency_id = ' . (int)$filters['constituency_id'] : '') .
             "
             ORDER BY p.name, b.section
             LIMIT 100"
        );
        $ipcStatuses = self::rows(
            "SELECT i.status, COUNT(*) AS certificates, COALESCE(SUM(i.gross_amount), 0) AS gross_amount,
                    COALESCE(SUM(i.retention_amount), 0) AS retention_amount, COALESCE(SUM(i.net_amount), 0) AS net_amount
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             " . self::projectWhereForJoinedProject($filters, 'p', 'i') . "
             GROUP BY i.status
             ORDER BY certificates DESC, i.status"
        );
        $payments = self::rows(
            "SELECT p.name AS project, pay.amount, pay.payment_date, pay.reference_no, pay.bank, COALESCE(CONCAT(u.first_name, ' ', u.last_name), '-') AS processed_by
             FROM payments pay
             JOIN projects p ON p.id = pay.project_id
             LEFT JOIN users u ON u.id = pay.processed_by
             WHERE pay.payment_date BETWEEN ? AND ? AND pay.status = 'processed'"
             . self::projectWhereSuffix($filters, 'p') . "
             ORDER BY pay.payment_date DESC, p.name",
            [$filters['date_from'], $filters['date_to']]
        );
        $retention = self::rows(
            "SELECT p.name AS project, r.total_held, r.released_amount, r.release_date, r.release_reason, COALESCE(CONCAT(u.first_name, ' ', u.last_name), '-') AS processed_by
             FROM retention r
             JOIN projects p ON p.id = r.project_id
             LEFT JOIN users u ON u.id = r.processed_by
             WHERE 1 = 1"
             . self::projectWhereSuffix($filters, 'p') . "
             ORDER BY r.created_at DESC"
        );

        return self::report('financial', $filters, [
            'contract_value' => array_sum(array_column($rows, 'contract_sum')),
            'paid_to_date' => array_sum(array_column($rows, 'paid')),
            'ipc_net' => array_sum(array_column($rows, 'ipc_net')),
            'boq_risk_items' => count($boq),
        ], [
            ['title' => 'Project Financials', 'rows' => $rows],
            ['title' => 'IPC Pipeline', 'rows' => $ipcStatuses],
            ['title' => 'Payments in Range', 'rows' => $payments],
            ['title' => 'Retention Register', 'rows' => $retention],
            ['title' => 'BOQ Quantity Risks', 'rows' => $boq],
        ]);
    }

    private static function attendance(array $filters): array
    {
        [$where, $bindings] = self::attendanceWhere($filters);
        $rows = self::rows(
            "SELECT ar.date, COALESCE(p.name, wl.name, 'Unassigned') AS project, COALESCE(c.name, 'Internal Office') AS constituency,
                    COALESCE(ar.role_at_signin, '-') AS role, ar.status, ar.review_status,
                    COUNT(*) AS records
             FROM attendance_records ar
             LEFT JOIN projects p ON p.id = ar.project_id
             LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             {$where}
             GROUP BY ar.date, p.name, wl.name, c.name, ar.role_at_signin, ar.status, ar.review_status
             ORDER BY ar.date DESC, records DESC",
            $bindings
        );
        $details = self::rows(
            "SELECT ar.date, ar.signin_time, COALESCE(CONCAT(u.first_name, ' ', u.last_name), '-') AS staff_name,
                    COALESCE(r.name, ar.role_at_signin, '-') AS role, COALESCE(p.name, wl.name, 'Unassigned') AS project,
                    ar.status, ar.review_status, ar.distance_from_site_m, ar.accuracy_meters
             FROM attendance_records ar
             LEFT JOIN users u ON u.id = ar.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN projects p ON p.id = ar.project_id
             LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
             " . $where . "
             ORDER BY ar.date DESC, ar.signin_time DESC",
            $bindings
        );

        return self::report('attendance', $filters, [
            'records' => array_sum(array_column($rows, 'records')),
            'geo_fail' => array_sum(array_map(static fn (array $row): int => ($row['status'] ?? '') === 'geo-fail' ? (int)$row['records'] : 0, $rows)),
            'pending_review' => array_sum(array_map(static fn (array $row): int => ($row['review_status'] ?? '') === 'pending' ? (int)$row['records'] : 0, $rows)),
        ], [
            ['title' => 'Attendance Summary', 'rows' => $rows],
            ['title' => 'Attendance Detail', 'rows' => $details],
        ]);
    }

    private static function projectRegister(array $filters): array
    {
        [$where, $bindings] = self::projectWhere($filters, 'p');
        $rows = self::rows(
            "SELECT p.id, p.name, c.name AS constituency, w.name AS ward, p.status, p.units, p.contract_sum,
                    p.contractor_name, p.pct_complete, p.start_date, p.est_delivery, p.updated_at
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where}
             ORDER BY c.name, p.name",
            $bindings
        );

        return self::report('project_register', $filters, [
            'projects' => count($rows),
            'units' => array_sum(array_column($rows, 'units')),
            'contract_value' => array_sum(array_column($rows, 'contract_sum')),
            'average_completion' => self::average($rows, 'pct_complete'),
        ], [
            ['title' => 'Project Register', 'rows' => $rows],
        ]);
    }

    private static function publicContent(array $filters): array
    {
        $news = self::rows(
            "SELECT title, post_format, status, is_featured, is_visible, views, published_at
             FROM news_articles
             WHERE deleted_at IS NULL AND (published_at IS NULL OR DATE(published_at) BETWEEN ? AND ?)
             ORDER BY views DESC, updated_at DESC"
            ,
            [$filters['date_from'], $filters['date_to']]
        );
        $contacts = self::rows('SELECT status, COUNT(*) AS total FROM contact_submissions WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status ORDER BY total DESC', [$filters['date_from'], $filters['date_to']]);
        $subscribers = self::rows('SELECT status, COUNT(*) AS total FROM subscribers WHERE DATE(subscribed_at) BETWEEN ? AND ? GROUP BY status ORDER BY total DESC', [$filters['date_from'], $filters['date_to']]);
        $announcements = self::rows('SELECT status, priority, COUNT(*) AS total FROM announcements WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status, priority ORDER BY total DESC', [$filters['date_from'], $filters['date_to']]);

        return self::report('public_content', $filters, [
            'news_articles' => count($news),
            'news_views' => array_sum(array_column($news, 'views')),
            'contact_records' => array_sum(array_column($contacts, 'total')),
            'subscribers' => array_sum(array_column($subscribers, 'total')),
        ], [
            ['title' => 'News Articles', 'rows' => $news],
            ['title' => 'Contact Inbox', 'rows' => $contacts],
            ['title' => 'Subscribers', 'rows' => $subscribers],
            ['title' => 'Announcements', 'rows' => $announcements],
        ]);
    }

    private static function report(string $type, array $filters, array $summary, array $sections): array
    {
        return [
            'type' => $type,
            'title' => self::TYPES[$type]['label'],
            'description' => self::TYPES[$type]['description'],
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => self::publicFilters($filters),
            'summary' => $summary,
            'sections' => $sections,
            'row_count' => array_sum(array_map(static fn (array $section): int => count($section['rows'] ?? []), $sections)),
        ];
    }

    private static function publicFilters(array $filters): array
    {
        unset($filters['project_ids'], $filters['_scope_role'], $filters['_scope_user_id']);
        return $filters;
    }

    private static function filtersForType(array $filters, string $type): array
    {
        $status = (string)($filters['status'] ?? '');
        $projectStatuses = ['planning', 'active', 'on_hold', 'stalled', 'completed', 'cancelled'];
        $attendanceStatuses = ['present', 'late', 'absent', 'geo-fail', 'outside-window'];

        // Financial reports do not use project status the same way; ignore status filter.
        if (in_array($type, ['financial', 'public_content'], true)) {
            $filters['status'] = '';
        } elseif (in_array($type, ['project_progress', 'project_register', 'executive_summary'], true)) {
            if ($status !== '' && !in_array($status, $projectStatuses, true)) {
                $filters['status'] = '';
            }
        } elseif ($type === 'attendance') {
            if ($status !== '' && !in_array($status, $attendanceStatuses, true)) {
                $filters['status'] = '';
            }
        } else {
            $filters['status'] = '';
        }

        return $filters;
    }

    private static function createRun(array $data): int
    {
        Database::query(
            'INSERT INTO report_runs (user_id, report_type, format, filters_json, row_count, status, scope_role, scope_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$data['user_id'], $data['report_type'], $data['format'], $data['filters_json'], $data['row_count'], $data['status'], $data['scope_role'] ?? null, $data['scope_user_id'] ?? null]
        );
        return (int)Database::lastInsertId();
    }

    private static function projectWhere(array $filters, string $alias): array
    {
        $where = [];
        $bindings = [];
        if (!empty($filters['project_id'])) {
            $where[] = "{$alias}.id = ?";
            $bindings[] = (int)$filters['project_id'];
        } elseif (!empty($filters['project_ids']) && is_array($filters['project_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['project_ids']), static fn (int $id): bool => $id > 0));
            if ($ids !== []) {
                $where[] = "{$alias}.id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')';
                array_push($bindings, ...$ids);
            }
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = "{$alias}.constituency_id = ?";
            $bindings[] = (int)$filters['constituency_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "{$alias}.status = ?";
            $bindings[] = (string)$filters['status'];
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function attendanceWhere(array $filters): array
    {
        $where = ['ar.date BETWEEN ? AND ?'];
        $bindings = [$filters['date_from'], $filters['date_to']];
        if (!empty($filters['project_id'])) {
            // Include HQ rows only when not filtering a specific project.
            $where[] = 'ar.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        } elseif (!empty($filters['project_ids']) && is_array($filters['project_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['project_ids']), static fn (int $id): bool => $id > 0));
            if ($ids !== []) {
                $where[] = 'ar.project_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
                array_push($bindings, ...$ids);
            }
        }
        if (!empty($filters['work_location_id'])) {
            $where[] = 'ar.work_location_id = ?';
            $bindings[] = (int)$filters['work_location_id'];
        }
        if (!empty($filters['constituency_id'])) {
            // Constituency applies to project-based records; keep HQ rows out of constituency slices.
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ar.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        return [' WHERE ' . implode(' AND ', $where), $bindings];
    }

    private static function projectWhereForJoinedProject(array $filters, string $projectAlias, ?string $statusAlias = null): string
    {
        $where = [];
        if (!empty($filters['project_id'])) {
            $where[] = "{$projectAlias}.id = " . (int)$filters['project_id'];
        } elseif (!empty($filters['project_ids']) && is_array($filters['project_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['project_ids']), static fn (int $id): bool => $id > 0));
            if ($ids !== []) {
                $where[] = "{$projectAlias}.id IN (" . implode(',', $ids) . ')';
            }
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = "{$projectAlias}.constituency_id = " . (int)$filters['constituency_id'];
        }
        if (!empty($filters['status'])) {
            $alias = $statusAlias ?: $projectAlias;
            $where[] = "{$alias}.status = " . Database::connection()->quote((string)$filters['status']);
        }

        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    private static function projectWhereSuffix(array $filters, string $projectAlias): string
    {
        $suffix = '';
        if (!empty($filters['project_id'])) {
            $suffix .= " AND {$projectAlias}.id = " . (int)$filters['project_id'];
        } elseif (!empty($filters['project_ids']) && is_array($filters['project_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['project_ids']), static fn (int $id): bool => $id > 0));
            if ($ids !== []) {
                $suffix .= " AND {$projectAlias}.id IN (" . implode(',', $ids) . ')';
            }
        }
        if (!empty($filters['constituency_id'])) {
            $suffix .= " AND {$projectAlias}.constituency_id = " . (int)$filters['constituency_id'];
        }
        if (!empty($filters['status'])) {
            $suffix .= " AND {$projectAlias}.status = " . Database::connection()->quote((string)$filters['status']);
        }
        return $suffix;
    }

    private static function rows(string $sql, array $bindings = []): array
    {
        return Database::fetchAll($sql, $bindings);
    }

    private static function average(array $rows, string $key): float
    {
        if ($rows === []) {
            return 0;
        }
        return array_sum(array_map(static fn (array $row): float => (float)($row[$key] ?? 0), $rows)) / count($rows);
    }

    private static function dateOrDefault(mixed $value, string $default): string
    {
        $value = trim((string)$value);
        $timestamp = $value !== '' ? strtotime($value) : false;
        return $timestamp === false ? $default : date('Y-m-d', $timestamp);
    }

    private static function positiveInt(mixed $value): ?int
    {
        return ctype_digit((string)$value) && (int)$value > 0 ? (int)$value : null;
    }

    private static function csvValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '';
        }
        return (string)$value;
    }

    private static function heading(string $value): string
    {
        $heading = ucwords(str_replace('_', ' ', $value));
        return str_replace(
            ['Ipc', 'Boq', 'Cms', 'Gps', 'Id', 'Pct'],
            ['IPC', 'BOQ', 'CMS', 'GPS', 'ID', 'Percentage'],
            $heading
        );
    }

    private static function displayValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (str_contains($key, 'amount') || str_contains($key, 'value') || str_contains($key, 'sum') || str_contains($key, 'paid') || str_contains($key, 'retention') || str_contains($key, 'gross') || str_contains($key, 'net') || str_contains($key, 'contract')) {
            return is_numeric($value) ? format_money((float)$value) : (string)$value;
        }

        if (str_contains($key, 'pct') || str_contains($key, 'completion') || str_contains($key, 'progress')) {
            return is_numeric($value) ? format_percentage((float)$value) : (string)$value;
        }

        if (str_contains($key, 'date') || str_ends_with($key, '_at')) {
            return format_date((string)$value);
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string)$value;
    }
}
