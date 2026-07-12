<?php

class InternAttendance
{
    public static function projects(int $userId): array
    {
        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.pct_complete, p.est_delivery,
                    c.name AS constituency_name, w.name AS ward_name,
                    gf.latitude, gf.longitude, gf.radius_meters, gf.status AS geo_status, gf.site_name
             FROM project_assignments pa
             JOIN projects p ON p.id = pa.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN geo_fences gf ON gf.project_id = p.id
             WHERE pa.user_id = ? AND pa.status = 'active'
             ORDER BY pa.assigned_at DESC, p.name ASC",
            [$userId]
        );
    }

    public static function workLocations(int $userId): array
    {
        return array_map(static function (array $row): array {
            return [
                'id' => (int)$row['work_location_id'],
                'name' => (string)$row['work_location_name'],
                'status' => 'active',
                'pct_complete' => null,
                'est_delivery' => null,
                'constituency_name' => 'Internal Office',
                'ward_name' => (string)($row['address'] ?? ''),
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'radius_meters' => $row['radius_meters'],
                'geo_status' => $row['work_location_status'],
                'site_name' => (string)$row['work_location_name'],
                'target_type' => 'work_location',
                'target_id' => (int)$row['work_location_id'],
            ];
        }, WorkLocation::forUser($userId));
    }

    public static function attendanceTargets(int $userId): array
    {
        $projects = array_map(static function (array $project): array {
            $project['target_type'] = 'project';
            $project['target_id'] = (int)$project['id'];
            return $project;
        }, self::projects($userId));

        return array_merge(self::workLocations($userId), $projects);
    }

    public static function defaultProjectId(int $userId, int $requestedId = 0): int
    {
        if ($requestedId > 0 && self::canAccessProject($userId, $requestedId)) {
            return $requestedId;
        }
        $project = self::projects($userId)[0] ?? null;
        return (int)($project['id'] ?? 0);
    }

    public static function defaultTarget(int $userId, string $requestedType = '', int $requestedId = 0): array
    {
        $requestedType = $requestedType === 'work_location' ? 'work_location' : 'project';
        if ($requestedId > 0) {
            $target = self::target($userId, $requestedType, $requestedId);
            if ($target !== null) {
                return ['type' => $requestedType, 'id' => $requestedId, 'target' => $target];
            }
        }

        $target = self::attendanceTargets($userId)[0] ?? null;
        return [
            'type' => (string)($target['target_type'] ?? 'project'),
            'id' => (int)($target['target_id'] ?? 0),
            'target' => $target,
        ];
    }

    public static function canAccessProject(int $userId, int $projectId): bool
    {
        return Database::fetch(
            'SELECT id FROM project_assignments WHERE user_id = ? AND project_id = ? AND status = "active" LIMIT 1',
            [$userId, $projectId]
        ) !== null;
    }

    public static function project(int $userId, int $projectId): ?array
    {
        foreach (self::projects($userId) as $project) {
            if ((int)$project['id'] === $projectId) {
                return $project;
            }
        }
        return null;
    }

    public static function target(int $userId, string $type, int $id): ?array
    {
        foreach (self::attendanceTargets($userId) as $target) {
            if ((string)($target['target_type'] ?? '') === $type && (int)($target['target_id'] ?? 0) === $id) {
                return $target;
            }
        }
        return null;
    }

    public static function canAccessTarget(int $userId, string $type, int $id): bool
    {
        return $type === 'work_location' ? WorkLocation::canAccess($userId, $id) : self::canAccessProject($userId, $id);
    }

    public static function todayStatus(int $userId, int $projectId): array
    {
        $date = date('Y-m-d');
        $project = self::project($userId, $projectId);
        $gateway = $projectId > 0 ? AttendancePolicy::ensureProjectWindow($projectId, $date) : null;
        $attendance = AttendanceRecord::todayForAnyTarget($userId);
        $isOpen = AttendancePolicy::isEffectivelyOpen($gateway);
        $policy = AttendancePolicy::windowPayload();

        return [
            'project' => $project,
            'gateway' => $gateway,
            'attendance' => $attendance,
            'is_open' => (bool)$isOpen,
            'already_signed' => (bool)$attendance,
            'policy' => $policy,
            'status_label' => self::attendanceLabel($attendance['status'] ?? ($isOpen ? 'open' : 'closed')),
            'status_badge' => self::attendanceBadge($attendance['status'] ?? ($isOpen ? 'open' : 'closed')),
        ];
    }

    public static function todayTargetStatus(int $userId, string $targetType, int $targetId): array
    {
        $date = date('Y-m-d');
        $target = self::target($userId, $targetType, $targetId);
        $gateway = null;
        if ($targetId > 0) {
            $gateway = $targetType === 'work_location'
                ? AttendancePolicy::ensureWorkLocationWindow($targetId, $date)
                : AttendancePolicy::ensureProjectWindow($targetId, $date);
        }
        $attendance = AttendanceRecord::todayForAnyTarget($userId);
        $isOpen = AttendancePolicy::isEffectivelyOpen($gateway);
        $policy = AttendancePolicy::windowPayload();

        return [
            'project' => $target,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'gateway' => $gateway,
            'attendance' => $attendance,
            'is_open' => (bool)$isOpen,
            'already_signed' => (bool)$attendance,
            'policy' => $policy,
            'status_label' => self::attendanceLabel($attendance['status'] ?? ($isOpen ? 'open' : 'closed')),
            'status_badge' => self::attendanceBadge($attendance['status'] ?? ($isOpen ? 'open' : 'closed')),
        ];
    }

    public static function summary(int $userId, ?string $month = null, int $projectId = 0, string $targetType = 'project', int $targetId = 0): array
    {
        $month = self::month($month);
        $rangeStart = $month . '-01';
        $rangeEnd = date('Y-m-t', strtotime($rangeStart));
        $where = ['ar.user_id = ?', 'ar.date BETWEEN ? AND ?'];
        $bindings = [$userId, $rangeStart, $rangeEnd];
        if ($targetId > 0 && self::canAccessTarget($userId, $targetType, $targetId)) {
            $where[] = $targetType === 'work_location' ? 'ar.work_location_id = ?' : 'ar.project_id = ?';
            $bindings[] = $targetId;
        } elseif ($projectId > 0 && self::canAccessProject($userId, $projectId)) {
            $where[] = 'ar.project_id = ?';
            $bindings[] = $projectId;
        }

        $row = Database::fetch(
            "SELECT COUNT(*) AS signed_days,
                    COALESCE(SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END), 0) AS present,
                    COALESCE(SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END), 0) AS late,
                    COALESCE(SUM(CASE WHEN ar.status IN ('geo-fail','outside-window') THEN 1 ELSE 0 END), 0) AS flagged,
                    COALESCE(SUM(CASE WHEN ar.review_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_review
             FROM attendance_records ar
             WHERE " . implode(' AND ', $where),
            $bindings
        ) ?: [];

        $workingDays = self::workingDays($rangeStart, min($rangeEnd, date('Y-m-d')));
        $signed = (int)($row['signed_days'] ?? 0);
        return [
            'month' => $month,
            'working_days' => $workingDays,
            'signed_days' => $signed,
            'present' => (int)($row['present'] ?? 0),
            'late' => (int)($row['late'] ?? 0),
            'flagged' => (int)($row['flagged'] ?? 0),
            'pending_review' => (int)($row['pending_review'] ?? 0),
            'missed' => max(0, $workingDays - $signed),
            'attendance_percent' => $workingDays > 0 ? percentage(($signed / $workingDays) * 100) : 0,
        ];
    }

    public static function history(int $userId, array $filters = [], int $limit = 40, int $offset = 0): array
    {
        [$where, $bindings] = self::historyWhere($userId, $filters);
        $rows = Database::fetchAll(
            AttendanceRecord::detailedSqlForManager() . ' WHERE ' . implode(' AND ', $where) . '
             ORDER BY ar.date DESC, ar.signin_time DESC, ar.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );

        return array_map([self::class, 'decorateRecord'], $rows);
    }

    public static function historyCount(int $userId, array $filters = []): int
    {
        [$where, $bindings] = self::historyWhere($userId, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM attendance_records ar
             LEFT JOIN projects p ON p.id = ar.project_id
             LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
             WHERE " . implode(' AND ', $where),
            $bindings
        ) ?: [];
        return (int)($row['total'] ?? 0);
    }

    public static function calendar(int $userId, ?string $month = null, int $projectId = 0, string $targetType = 'project', int $targetId = 0): array
    {
        $month = self::month($month);
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $filters = ['month' => $month];
        if ($targetId > 0) {
            $filters['target_type'] = $targetType;
            $filters['target_id'] = $targetId;
        } else {
            $filters['project_id'] = $projectId;
        }
        $records = self::history($userId, $filters, 80);
        $byDate = [];
        foreach ($records as $record) {
            $byDate[(string)$record['date']] = $record;
        }

        $days = [];
        $cursor = strtotime($start);
        while ($cursor <= strtotime($end)) {
            $date = date('Y-m-d', $cursor);
            $days[] = [
                'date' => $date,
                'day' => date('j', $cursor),
                'weekday' => date('D', $cursor),
                'record' => $byDate[$date] ?? null,
                'is_today' => $date === date('Y-m-d'),
                'is_future' => $date > date('Y-m-d'),
                'is_weekend' => in_array(date('N', $cursor), [6, 7], true),
            ];
            $cursor = strtotime('+1 day', $cursor);
        }
        return $days;
    }

    public static function filters(array $input): array
    {
        $filters = [
            'month' => self::month($input['month'] ?? null),
            'project_id' => Security::cleanInt($input['project_id'] ?? 0),
            'target_type' => Security::cleanString((string)($input['target_type'] ?? '')),
            'target_id' => Security::cleanInt($input['target_id'] ?? 0),
            'status' => Security::cleanString((string)($input['status'] ?? '')),
        ];
        if (!in_array($filters['target_type'], ['', 'project', 'work_location'], true)) {
            $filters['target_type'] = '';
        }
        if (!in_array($filters['status'], ['', 'present', 'late', 'geo-fail', 'outside-window'], true)) {
            $filters['status'] = '';
        }
        return array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
    }

    public static function decorateRecord(array $record): array
    {
        $record['status_label'] = self::attendanceLabel($record['status'] ?? '');
        $record['status_badge'] = self::attendanceBadge($record['status'] ?? '');
        $record['distance_label'] = isset($record['distance_from_site_m']) && $record['distance_from_site_m'] !== null ? format_number((float)$record['distance_from_site_m']) . ' m' : '-';
        $record['accuracy_label'] = isset($record['accuracy_meters']) && $record['accuracy_meters'] !== null ? format_number((float)$record['accuracy_meters']) . ' m' : '-';
        $record['signin_label'] = trim((string)($record['signin_time'] ?? '')) !== '' ? date('H:i', strtotime((string)$record['signin_time'])) : '-';
        return $record;
    }

    public static function attendanceLabel(?string $status): string
    {
        return match ((string)$status) {
            'present' => 'Present',
            'late' => 'Late',
            'geo-fail' => 'Location flagged',
            'outside-window' => 'Outside window',
            'open' => 'Gateway open',
            'closed' => 'Gateway closed',
            default => 'Not signed in',
        };
    }

    public static function attendanceBadge(?string $status): string
    {
        return match ((string)$status) {
            'present', 'open' => 'badge--success',
            'late' => 'badge--warning',
            'geo-fail', 'outside-window' => 'badge--danger',
            default => 'badge--info',
        };
    }

    private static function historyWhere(int $userId, array $filters): array
    {
        $month = self::month($filters['month'] ?? null);
        $where = ['ar.user_id = ?', 'ar.date BETWEEN ? AND ?'];
        $bindings = [$userId, $month . '-01', date('Y-m-t', strtotime($month . '-01'))];

        $projectId = Security::cleanInt($filters['project_id'] ?? 0);
        $targetType = (string)($filters['target_type'] ?? '');
        $targetId = Security::cleanInt($filters['target_id'] ?? 0);
        if ($targetId > 0 && in_array($targetType, ['project', 'work_location'], true) && self::canAccessTarget($userId, $targetType, $targetId)) {
            $where[] = $targetType === 'work_location' ? 'ar.work_location_id = ?' : 'ar.project_id = ?';
            $bindings[] = $targetId;
        } elseif ($projectId > 0 && self::canAccessProject($userId, $projectId)) {
            $where[] = 'ar.project_id = ?';
            $bindings[] = $projectId;
        }
        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'ar.status = ?';
            $bindings[] = $status;
        }
        return [$where, $bindings];
    }

    private static function month(mixed $month): string
    {
        $month = trim((string)($month ?? date('Y-m')));
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    private static function workingDays(string $start, string $end): int
    {
        $count = 0;
        $cursor = strtotime($start);
        $last = strtotime($end);
        while ($cursor <= $last) {
            if (!in_array(date('N', $cursor), [6, 7], true)) {
                $count++;
            }
            $cursor = strtotime('+1 day', $cursor);
        }
        return $count;
    }
}
