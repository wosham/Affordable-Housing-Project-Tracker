<?php

class Analytics extends Model
{
    public static function dashboard(array $filters = []): array
    {
        $range = self::dateRange($filters);

        return [
            'range' => $range,
            'summary' => self::summary($range),
            'projects' => self::projectAnalytics($range),
            'finance' => self::financeAnalytics($range),
            'attendance' => self::attendanceAnalytics($range),
            'content' => self::contentAnalytics($range),
            'risks' => self::riskSignals($range),
        ];
    }

    public static function dateRange(array $filters = []): array
    {
        $preset = (string)($filters['range'] ?? '30_days');
        $today = new DateTimeImmutable('today');

        $ranges = [
            'today' => [$today, $today],
            '7_days' => [$today->modify('-6 days'), $today],
            '30_days' => [$today->modify('-29 days'), $today],
            'this_month' => [$today->modify('first day of this month'), $today],
            'this_quarter' => [self::quarterStart($today), $today],
        ];

        [$start, $end] = $ranges[$preset] ?? $ranges['30_days'];

        if ($preset === 'custom') {
            $customStart = self::parseDate($filters['date_from'] ?? null);
            $customEnd = self::parseDate($filters['date_to'] ?? null);
            $start = $customStart ?: $start;
            $end = $customEnd ?: $end;
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [
            'preset' => $preset,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'start_label' => $start->format('d M Y'),
            'end_label' => $end->format('d M Y'),
        ];
    }

    public static function rangeOptions(): array
    {
        return [
            'today' => 'Today',
            '7_days' => 'Last 7 days',
            '30_days' => 'Last 30 days',
            'this_month' => 'This month',
            'this_quarter' => 'This quarter',
            'custom' => 'Custom range',
        ];
    }

    private static function summary(array $range): array
    {
        $projectTotal = self::scalar('SELECT COUNT(*) AS total FROM projects');
        $activeProjects = self::scalar("SELECT COUNT(*) AS total FROM projects WHERE status = 'active'");
        $completedProjects = self::scalar("SELECT COUNT(*) AS total FROM projects WHERE status = 'completed'");
        $averageProgress = self::scalar('SELECT COALESCE(AVG(pct_complete), 0) AS total FROM projects');
        $contractValue = self::scalar('SELECT COALESCE(SUM(contract_sum), 0) AS total FROM projects');
        $paidToDate = self::scalar('SELECT COALESCE(SUM(amount), 0) AS total FROM payments');
        $rangePayments = self::scalar(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_date BETWEEN ? AND ?',
            [$range['start'], $range['end']]
        );
        $attendanceRange = self::scalar(
            'SELECT COUNT(*) AS total FROM attendance_records WHERE date BETWEEN ? AND ?',
            [$range['start'], $range['end']]
        );
        $contentViews = self::scalar("SELECT COALESCE(SUM(views), 0) AS total FROM news_articles WHERE status = 'published' AND deleted_at IS NULL");

        return [
            'project_total' => (int)$projectTotal,
            'active_projects' => (int)$activeProjects,
            'completed_projects' => (int)$completedProjects,
            'average_progress' => (float)$averageProgress,
            'contract_value' => (float)$contractValue,
            'paid_to_date' => (float)$paidToDate,
            'range_payments' => (float)$rangePayments,
            'attendance_range' => (int)$attendanceRange,
            'content_views' => (int)$contentViews,
        ];
    }

    private static function projectAnalytics(array $range): array
    {
        return [
            'statuses' => self::rows('SELECT status, COUNT(*) AS total FROM projects GROUP BY status ORDER BY total DESC, status'),
            'by_constituency' => self::rows(
                'SELECT c.name, COUNT(p.id) AS projects, COALESCE(SUM(p.units), 0) AS units, COALESCE(AVG(p.pct_complete), 0) AS progress
                 FROM constituencies c
                 LEFT JOIN projects p ON p.constituency_id = c.id
                 GROUP BY c.id, c.name
                 ORDER BY c.name'
            ),
            'completion' => self::rows(
                'SELECT name, pct_complete, status, est_delivery
                 FROM projects
                 ORDER BY pct_complete DESC, updated_at DESC
                 LIMIT 10'
            ),
            'overdue' => self::rows(
                "SELECT id, name, status, pct_complete, est_delivery
                 FROM projects
                 WHERE est_delivery IS NOT NULL AND est_delivery < CURDATE() AND status <> 'completed'
                 ORDER BY est_delivery ASC
                 LIMIT 8"
            ),
            'recent' => self::rows(
                'SELECT id, name, status, pct_complete, updated_at
                 FROM projects
                 ORDER BY updated_at DESC
                 LIMIT 8'
            ),
        ];
    }

    private static function financeAnalytics(array $range): array
    {
        return [
            'ipc_statuses' => self::rows('SELECT status, COUNT(*) AS total FROM ipcs GROUP BY status ORDER BY total DESC, status'),
            'ipc_totals' => self::row(
                'SELECT COALESCE(SUM(gross_amount), 0) AS gross,
                        COALESCE(SUM(retention_amount), 0) AS retention,
                        COALESCE(SUM(net_amount), 0) AS net
                 FROM ipcs'
            ),
            'payment_months' => self::rows(
                'SELECT DATE_FORMAT(payment_date, "%Y-%m") AS month, COALESCE(SUM(amount), 0) AS total
                 FROM payments
                 WHERE payment_date BETWEEN ? AND ?
                 GROUP BY DATE_FORMAT(payment_date, "%Y-%m")
                 ORDER BY month',
                [$range['start'], $range['end']]
            ),
            'budget_burn' => self::rows(
                'SELECT p.id, p.name, COALESCE(p.contract_sum, 0) AS contract_sum, COALESCE(pay.paid, 0) AS paid
                 FROM projects p
                 LEFT JOIN (
                    SELECT project_id, SUM(amount) AS paid
                    FROM payments
                    WHERE payment_date BETWEEN ? AND ?
                    GROUP BY project_id
                 ) pay ON pay.project_id = p.id
                 ORDER BY p.contract_sum DESC
                 LIMIT 10',
                [$range['start'], $range['end']]
            ),
            'boq_exposure' => self::row(
                'SELECT COALESCE(SUM(quantity * rate), 0) AS planned,
                        COALESCE(SUM(certified_qty * rate), 0) AS certified,
                        COALESCE(SUM(paid_qty * rate), 0) AS paid,
                        COALESCE(SUM(CASE WHEN paid_qty > certified_qty OR certified_qty > quantity THEN 1 ELSE 0 END), 0) AS risk_items
                 FROM boq_items'
            ),
            'awaiting_payment' => self::rows(
                "SELECT i.id, i.ipc_number, i.net_amount, i.approved_at, p.name AS project_name
                 FROM ipcs i
                 JOIN projects p ON p.id = i.project_id
                 WHERE i.status = 'approved'
                 ORDER BY i.approved_at DESC, i.id DESC
                 LIMIT 8"
            ),
        ];
    }

    private static function attendanceAnalytics(array $range): array
    {
        return [
            'daily' => self::rows(
                'SELECT date, COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN status = "geo-fail" THEN 1 ELSE 0 END), 0) AS geo_fail
                 FROM attendance_records
                 WHERE date BETWEEN ? AND ?
                 GROUP BY date
                 ORDER BY date',
                [$range['start'], $range['end']]
            ),
            'by_project' => self::rows(
                'SELECT COALESCE(p.name, "Unassigned") AS name, COUNT(ar.id) AS total
                 FROM attendance_records ar
                 LEFT JOIN projects p ON p.id = ar.project_id
                 WHERE ar.date BETWEEN ? AND ?
                 GROUP BY p.id, p.name
                 ORDER BY total DESC
                 LIMIT 10',
                [$range['start'], $range['end']]
            ),
            'by_role' => self::rows(
                'SELECT COALESCE(role_at_signin, "unknown") AS role, COUNT(*) AS total
                 FROM attendance_records
                 WHERE date BETWEEN ? AND ?
                 GROUP BY role_at_signin
                 ORDER BY total DESC',
                [$range['start'], $range['end']]
            ),
            'exceptions' => self::row(
                'SELECT COALESCE(SUM(CASE WHEN status = "geo-fail" THEN 1 ELSE 0 END), 0) AS geo_fail,
                        COALESCE(SUM(CASE WHEN review_status = "pending" THEN 1 ELSE 0 END), 0) AS pending_review
                 FROM attendance_records
                 WHERE date BETWEEN ? AND ?',
                [$range['start'], $range['end']]
            ),
        ];
    }

    private static function contentAnalytics(array $range): array
    {
        return [
            'news_statuses' => self::rows(
                'SELECT status, COUNT(*) AS total
                 FROM news_articles
                 WHERE deleted_at IS NULL
                 GROUP BY status
                 ORDER BY total DESC, status'
            ),
            'top_news' => self::rows(
                "SELECT id, title, views, published_at
                 FROM news_articles
                 WHERE status = 'published' AND deleted_at IS NULL
                 ORDER BY views DESC, published_at DESC
                 LIMIT 8"
            ),
            'announcements' => self::rows('SELECT status, COUNT(*) AS total FROM announcements GROUP BY status ORDER BY total DESC, status'),
            'subscribers_daily' => self::rows(
                'SELECT DATE(subscribed_at) AS date, COUNT(*) AS total
                 FROM subscribers
                 WHERE subscribed_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                 GROUP BY DATE(subscribed_at)
                 ORDER BY date',
                [$range['start'], $range['end']]
            ),
            'contact_statuses' => self::rows('SELECT status, COUNT(*) AS total FROM contact_submissions GROUP BY status ORDER BY total DESC, status'),
            'gallery_statuses' => self::rows('SELECT status, COUNT(*) AS total FROM gallery_images GROUP BY status ORDER BY total DESC, status'),
            'cms_visibility' => self::row(
                'SELECT COALESCE(SUM(CASE WHEN is_visible = 1 THEN 1 ELSE 0 END), 0) AS visible,
                        COALESCE(SUM(CASE WHEN is_visible = 0 THEN 1 ELSE 0 END), 0) AS hidden
                 FROM cms_sections'
            ),
        ];
    }

    private static function riskSignals(array $range): array
    {
        $signals = [];
        $add = static function (string $severity, string $icon, string $title, string $text, string $href) use (&$signals): void {
            $signals[] = compact('severity', 'icon', 'title', 'text', 'href');
        };

        $pendingApprovals = (int)self::scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status IN ('certified', 'endorsed')");
        $approvedUnpaid = (int)self::scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status = 'approved'");
        $delayedTasks = (int)self::scalar(
            "SELECT COUNT(*) AS total FROM programme_tasks WHERE status NOT IN ('complete', 'cancelled') AND planned_end IS NOT NULL AND planned_end < CURDATE()"
        );
        $boqRisks = (int)self::scalar('SELECT COUNT(*) AS total FROM boq_items WHERE certified_qty > quantity OR paid_qty > certified_qty');
        $geoFails = (int)self::scalar(
            'SELECT COUNT(*) AS total FROM attendance_records WHERE status = "geo-fail" AND date BETWEEN ? AND ?',
            [$range['start'], $range['end']]
        );
        $unreadContacts = (int)self::scalar("SELECT COUNT(*) AS total FROM contact_submissions WHERE is_read = 0 AND status <> 'archived'");

        if ($pendingApprovals > 0) {
            $add('critical', 'fa-clipboard-check', 'IPC approvals waiting', format_number($pendingApprovals) . ' certified or endorsed IPCs need review.', Url::to('admin/superadmin/ipcs.php?payment_readiness=approval-ready'));
        }
        if ($approvedUnpaid > 0) {
            $add('action', 'fa-money-bill-transfer', 'Approved IPCs unpaid', format_number($approvedUnpaid) . ' approved IPCs are waiting for finance processing.', Url::to('admin/superadmin/financials.php'));
        }
        if ($delayedTasks > 0) {
            $add('critical', 'fa-chart-gantt', 'Programme delays', format_number($delayedTasks) . ' tasks are past planned finish date.', Url::to('admin/superadmin/programme-of-works.php?delay=delayed'));
        }
        if ($boqRisks > 0) {
            $add('critical', 'fa-list-check', 'BOQ quantity exposure', format_number($boqRisks) . ' BOQ items have certified or paid quantity risk.', Url::to('admin/superadmin/boq.php?risk=overpaid'));
        }
        if ($geoFails > 0) {
            $add('monitor', 'fa-location-dot', 'Attendance GPS flags', format_number($geoFails) . ' sign-ins failed geofence checks in this range.', Url::to('admin/superadmin/attendance.php'));
        }
        if ($unreadContacts > 0) {
            $add('action', 'fa-inbox', 'Citizen inbox unread', format_number($unreadContacts) . ' public submissions are unread.', Url::to('admin/superadmin/contact-inbox.php'));
        }

        return $signals;
    }

    private static function scalar(string $sql, array $bindings = [], string $column = 'total'): mixed
    {
        try {
            $row = Database::fetch($sql, $bindings);
            return $row[$column] ?? 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private static function row(string $sql, array $bindings = []): array
    {
        try {
            return Database::fetch($sql, $bindings) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private static function rows(string $sql, array $bindings = []): array
    {
        try {
            return Database::fetchAll($sql, $bindings);
        } catch (Throwable) {
            return [];
        }
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : (new DateTimeImmutable())->setTimestamp($timestamp);
    }

    private static function quarterStart(DateTimeImmutable $today): DateTimeImmutable
    {
        $month = (int)$today->format('n');
        $quarterMonth = ((int)floor(($month - 1) / 3) * 3) + 1;
        return $today->setDate((int)$today->format('Y'), $quarterMonth, 1);
    }
}
