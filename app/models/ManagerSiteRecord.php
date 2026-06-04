<?php

class ManagerSiteRecord
{
    public const MEETING_STATUSES = ['draft', 'recorded', 'reviewed', 'closed'];
    public const ACTION_STATUSES = ['none', 'open', 'in-progress', 'completed', 'overdue'];
    public const INCIDENT_TYPES = ['near-miss', 'first-aid', 'medical', 'fatality'];
    public const INCIDENT_SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const INCIDENT_STATUSES = ['open', 'investigating', 'action-pending', 'resolved', 'closed'];
    public const COMMUNITY_TYPES = ['meeting', 'complaint', 'consultation', 'notice', 'visit', 'grievance', 'other'];
    public const COMMUNITY_STATUSES = ['open', 'follow-up', 'resolved', 'closed'];

    public static function projects(int $userId, string $role): array
    {
        $projects = ProjectAssignment::managerProjects($userId, $role);
        if ($projects === []) {
            return [];
        }

        $ids = array_map(static fn (array $project): int => (int)$project['id'], $projects);
        [$in, $bindings] = self::inClause($ids);
        $rows = Database::fetchAll(
            "SELECT p.id,
                    COUNT(DISTINCT smm.id) AS meeting_count,
                    COUNT(DISTINCT hsi.id) AS incident_count,
                    COUNT(DISTINCT cl.id) AS community_count
             FROM projects p
             LEFT JOIN site_meeting_minutes smm ON smm.project_id = p.id
             LEFT JOIN hs_incidents hsi ON hsi.project_id = p.id
             LEFT JOIN community_liaison cl ON cl.project_id = p.id
             WHERE p.id IN ({$in})
             GROUP BY p.id",
            $bindings
        );

        $meta = [];
        foreach ($rows as $row) {
            $meta[(int)$row['id']] = $row;
        }

        foreach ($projects as &$project) {
            $row = $meta[(int)$project['id']] ?? [];
            $project['meeting_count'] = (int)($row['meeting_count'] ?? 0);
            $project['incident_count'] = (int)($row['incident_count'] ?? 0);
            $project['community_count'] = (int)($row['community_count'] ?? 0);
        }
        unset($project);

        return $projects;
    }

    public static function canAccessProject(int $userId, string $role, int $projectId): bool
    {
        return $role === 'superadmin' || ProjectAssignment::canManageProject($userId, $projectId, $role);
    }

    public static function meetings(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::meetingWhere($userId, $role, $filters);
        return Database::fetchAll(self::meetingSelect() . $where . ' ORDER BY smm.meeting_date DESC, smm.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function countMeetings(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::meetingWhere($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM site_meeting_minutes smm JOIN projects p ON p.id = smm.project_id LEFT JOIN users rec ON rec.id = smm.recorded_by {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function meetingSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::meetingWhere($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN smm.meeting_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END), 0) AS this_month,
                    COALESCE(SUM(CASE WHEN smm.action_status IN ('open','in-progress','overdue') THEN 1 ELSE 0 END), 0) AS open_actions,
                    COALESCE(SUM(CASE WHEN smm.action_status = 'overdue' THEN 1 ELSE 0 END), 0) AS overdue_actions,
                    COALESCE(SUM(CASE WHEN smm.document_path IS NOT NULL AND smm.document_path <> '' THEN 1 ELSE 0 END), 0) AS with_document,
                    COALESCE(SUM(CASE WHEN smm.status = 'reviewed' THEN 1 ELSE 0 END), 0) AS reviewed
             FROM site_meeting_minutes smm
             JOIN projects p ON p.id = smm.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numericDefaults($row, ['total', 'this_month', 'open_actions', 'overdue_actions', 'with_document', 'reviewed']);
    }

    public static function incidents(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::incidentWhere($userId, $role, $filters);
        return Database::fetchAll(self::incidentSelect() . $where . ' ORDER BY FIELD(hsi.severity, "critical","high","medium","low"), hsi.incident_date DESC, hsi.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function countIncidents(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::incidentWhere($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM hs_incidents hsi JOIN projects p ON p.id = hsi.project_id LEFT JOIN users rep ON rep.id = hsi.reported_by {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function incidentSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::incidentWhere($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN hsi.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS open_items,
                    COALESCE(SUM(CASE WHEN hsi.severity IN ('high','critical') THEN 1 ELSE 0 END), 0) AS severe,
                    COALESCE(SUM(CASE WHEN hsi.incident_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END), 0) AS this_month,
                    COALESCE(SUM(CASE WHEN hsi.status = 'action-pending' THEN 1 ELSE 0 END), 0) AS action_pending,
                    COALESCE(SUM(CASE WHEN hsi.status IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS closed_items
             FROM hs_incidents hsi
             JOIN projects p ON p.id = hsi.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numericDefaults($row, ['total', 'open_items', 'severe', 'this_month', 'action_pending', 'closed_items']);
    }

    public static function community(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::communityWhere($userId, $role, $filters);
        return Database::fetchAll(self::communitySelect() . $where . ' ORDER BY cl.follow_up_date IS NULL ASC, cl.follow_up_date ASC, cl.log_date DESC, cl.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function countCommunity(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::communityWhere($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM community_liaison cl JOIN projects p ON p.id = cl.project_id LEFT JOIN users rec ON rec.id = cl.recorded_by {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function communitySummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::communityWhere($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN cl.status IN ('open','follow-up') THEN 1 ELSE 0 END), 0) AS open_items,
                    COALESCE(SUM(CASE WHEN cl.follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND cl.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS due_soon,
                    COALESCE(SUM(CASE WHEN cl.follow_up_date < CURDATE() AND cl.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS overdue,
                    COALESCE(SUM(CASE WHEN cl.status IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS resolved,
                    COALESCE(SUM(CASE WHEN cl.log_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END), 0) AS this_month
             FROM community_liaison cl
             JOIN projects p ON p.id = cl.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numericDefaults($row, ['total', 'open_items', 'due_soon', 'overdue', 'resolved', 'this_month']);
    }

    public static function find(string $type, int $id, int $userId, string $role): ?array
    {
        return match ($type) {
            'meeting' => Database::fetch(self::meetingSelect() . ' WHERE smm.id = ?' . self::scopeAnd($userId, $role, 'p'), array_merge([$id], self::scopeBindings($userId, $role))),
            'incident' => Database::fetch(self::incidentSelect() . ' WHERE hsi.id = ?' . self::scopeAnd($userId, $role, 'p'), array_merge([$id], self::scopeBindings($userId, $role))),
            'community' => Database::fetch(self::communitySelect() . ' WHERE cl.id = ?' . self::scopeAnd($userId, $role, 'p'), array_merge([$id], self::scopeBindings($userId, $role))),
            default => null,
        };
    }

    public static function saveMeeting(int $userId, string $role, array $data): int
    {
        $id = max(0, (int)($data['id'] ?? 0));
        $projectId = (int)($data['project_id'] ?? 0);
        if (!self::canAccessProject($userId, $role, $projectId)) {
            throw new RuntimeException('You do not have access to this project.');
        }

        $payload = [
            'project_id' => $projectId,
            'meeting_date' => self::dateOrToday($data['meeting_date'] ?? ''),
            'venue' => self::nullable($data['venue'] ?? ''),
            'attendees_json' => self::jsonList($data['attendees'] ?? ''),
            'agenda' => self::nullable($data['agenda'] ?? ''),
            'minutes_text' => self::nullable($data['minutes_text'] ?? ''),
            'action_items_json' => self::jsonList($data['action_items'] ?? ''),
            'document_path' => self::nullable($data['document_path'] ?? ''),
            'status' => self::option((string)($data['status'] ?? 'recorded'), self::MEETING_STATUSES, 'recorded'),
            'action_status' => self::option((string)($data['action_status'] ?? 'none'), self::ACTION_STATUSES, 'none'),
        ];

        if ($id > 0) {
            $current = self::find('meeting', $id, $userId, $role);
            if (!$current) {
                throw new RuntimeException('Meeting record could not be found.');
            }
            Database::query(
                'UPDATE site_meeting_minutes SET project_id = ?, meeting_date = ?, venue = ?, attendees_json = ?, agenda = ?, minutes_text = ?, action_items_json = ?, document_path = ?, status = ?, action_status = ?, updated_by = ?, reviewed_at = CASE WHEN ? = "reviewed" AND reviewed_at IS NULL THEN NOW() ELSE reviewed_at END WHERE id = ?',
                array_merge(array_values($payload), [$userId, $payload['status'], $id])
            );
            return $id;
        }

        Database::query(
            'INSERT INTO site_meeting_minutes (project_id, meeting_date, venue, attendees_json, agenda, minutes_text, action_items_json, document_path, recorded_by, status, action_status, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$payload['project_id'], $payload['meeting_date'], $payload['venue'], $payload['attendees_json'], $payload['agenda'], $payload['minutes_text'], $payload['action_items_json'], $payload['document_path'], $userId, $payload['status'], $payload['action_status'], $userId]
        );
        return (int)Database::lastInsertId();
    }

    public static function saveIncident(int $userId, string $role, array $data): int
    {
        $id = max(0, (int)($data['id'] ?? 0));
        $projectId = (int)($data['project_id'] ?? 0);
        if (!self::canAccessProject($userId, $role, $projectId)) {
            throw new RuntimeException('You do not have access to this project.');
        }

        $payload = [
            'project_id' => $projectId,
            'incident_date' => self::dateOrToday($data['incident_date'] ?? ''),
            'incident_type' => self::option((string)($data['incident_type'] ?? 'near-miss'), self::INCIDENT_TYPES, 'near-miss'),
            'description' => trim((string)($data['description'] ?? '')),
            'persons_involved' => self::nullable($data['persons_involved'] ?? ''),
            'cause' => self::nullable($data['cause'] ?? ''),
            'corrective_action' => self::nullable($data['corrective_action'] ?? ''),
            'severity' => self::option((string)($data['severity'] ?? 'medium'), self::INCIDENT_SEVERITIES, 'medium'),
            'status' => self::option((string)($data['status'] ?? 'open'), self::INCIDENT_STATUSES, 'open'),
            'follow_up_date' => self::nullableDate($data['follow_up_date'] ?? ''),
            'attachment_path' => self::nullable($data['attachment_path'] ?? ''),
        ];
        if ($payload['description'] === '') {
            throw new RuntimeException('Incident description is required.');
        }

        if ($id > 0) {
            $current = self::find('incident', $id, $userId, $role);
            if (!$current) {
                throw new RuntimeException('Incident record could not be found.');
            }
            Database::query(
                'UPDATE hs_incidents SET project_id = ?, incident_date = ?, incident_type = ?, description = ?, persons_involved = ?, cause = ?, corrective_action = ?, severity = ?, status = ?, follow_up_date = ?, attachment_path = ?, updated_by = ?, closed_at = CASE WHEN ? IN ("resolved","closed") AND closed_at IS NULL THEN NOW() ELSE closed_at END WHERE id = ?',
                array_merge(array_values($payload), [$userId, $payload['status'], $id])
            );
            return $id;
        }

        Database::query(
            'INSERT INTO hs_incidents (project_id, incident_date, incident_type, description, persons_involved, cause, corrective_action, reported_by, severity, status, follow_up_date, attachment_path, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$payload['project_id'], $payload['incident_date'], $payload['incident_type'], $payload['description'], $payload['persons_involved'], $payload['cause'], $payload['corrective_action'], $userId, $payload['severity'], $payload['status'], $payload['follow_up_date'], $payload['attachment_path'], $userId]
        );
        return (int)Database::lastInsertId();
    }

    public static function saveCommunity(int $userId, string $role, array $data): int
    {
        $id = max(0, (int)($data['id'] ?? 0));
        $projectId = (int)($data['project_id'] ?? 0);
        if (!self::canAccessProject($userId, $role, $projectId)) {
            throw new RuntimeException('You do not have access to this project.');
        }

        $payload = [
            'project_id' => $projectId,
            'log_date' => self::dateOrToday($data['log_date'] ?? ''),
            'engagement_type' => self::option((string)($data['engagement_type'] ?? 'meeting'), self::COMMUNITY_TYPES, 'meeting'),
            'community_rep' => self::nullable($data['community_rep'] ?? ''),
            'issues_raised' => self::nullable($data['issues_raised'] ?? ''),
            'resolution' => self::nullable($data['resolution'] ?? ''),
            'follow_up_date' => self::nullableDate($data['follow_up_date'] ?? ''),
            'status' => self::option((string)($data['status'] ?? 'open'), self::COMMUNITY_STATUSES, 'open'),
        ];

        if ($id > 0) {
            $current = self::find('community', $id, $userId, $role);
            if (!$current) {
                throw new RuntimeException('Community record could not be found.');
            }
            Database::query(
                'UPDATE community_liaison SET project_id = ?, log_date = ?, engagement_type = ?, community_rep = ?, issues_raised = ?, resolution = ?, follow_up_date = ?, status = ?, updated_by = ?, closed_at = CASE WHEN ? IN ("resolved","closed") AND closed_at IS NULL THEN NOW() ELSE closed_at END WHERE id = ?',
                array_merge(array_values($payload), [$userId, $payload['status'], $id])
            );
            return $id;
        }

        Database::query(
            'INSERT INTO community_liaison (project_id, log_date, engagement_type, community_rep, issues_raised, resolution, follow_up_date, recorded_by, status, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$payload['project_id'], $payload['log_date'], $payload['engagement_type'], $payload['community_rep'], $payload['issues_raised'], $payload['resolution'], $payload['follow_up_date'], $userId, $payload['status'], $userId]
        );
        return (int)Database::lastInsertId();
    }

    public static function updateStatus(string $type, int $id, int $userId, string $role, string $status): array
    {
        $record = self::find($type, $id, $userId, $role);
        if (!$record) {
            throw new RuntimeException('Record could not be found.');
        }

        if ($type === 'meeting') {
            $status = self::option($status, self::MEETING_STATUSES, (string)$record['status']);
            Database::query('UPDATE site_meeting_minutes SET status = ?, updated_by = ?, reviewed_at = CASE WHEN ? = "reviewed" AND reviewed_at IS NULL THEN NOW() ELSE reviewed_at END WHERE id = ?', [$status, $userId, $status, $id]);
        } elseif ($type === 'incident') {
            $status = self::option($status, self::INCIDENT_STATUSES, (string)$record['status']);
            Database::query('UPDATE hs_incidents SET status = ?, updated_by = ?, closed_at = CASE WHEN ? IN ("resolved","closed") AND closed_at IS NULL THEN NOW() ELSE closed_at END WHERE id = ?', [$status, $userId, $status, $id]);
        } else {
            $status = self::option($status, self::COMMUNITY_STATUSES, (string)$record['status']);
            Database::query('UPDATE community_liaison SET status = ?, updated_by = ?, closed_at = CASE WHEN ? IN ("resolved","closed") AND closed_at IS NULL THEN NOW() ELSE closed_at END WHERE id = ?', [$status, $userId, $status, $id]);
        }

        return self::find($type, $id, $userId, $role) ?: [];
    }

    public static function meetingPayload(array $row): array
    {
        $attendees = self::decodeList($row['attendees_json'] ?? '');
        $actions = self::decodeList($row['action_items_json'] ?? '');
        return array_merge($row, [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'attendees' => $attendees,
            'attendees_text' => implode("\n", $attendees),
            'attendees_count' => count($attendees),
            'action_items' => $actions,
            'action_items_text' => implode("\n", $actions),
            'action_count' => count($actions),
            'status_label' => status_label((string)($row['status'] ?? 'recorded')),
            'action_status_label' => status_label((string)($row['action_status'] ?? 'none')),
            'date_label' => format_date($row['meeting_date'] ?? null),
            'recorded_by_name' => trim((string)($row['recorded_by_name'] ?? '')) ?: 'System',
        ]);
    }

    public static function incidentPayload(array $row): array
    {
        return array_merge($row, [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'type_label' => status_label((string)($row['incident_type'] ?? 'near-miss')),
            'severity_label' => status_label((string)($row['severity'] ?? 'medium')),
            'status_label' => status_label((string)($row['status'] ?? 'open')),
            'date_label' => format_date($row['incident_date'] ?? null),
            'follow_up_label' => format_date($row['follow_up_date'] ?? null),
            'reported_by_name' => trim((string)($row['reported_by_name'] ?? '')) ?: 'System',
        ]);
    }

    public static function communityPayload(array $row): array
    {
        return array_merge($row, [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'type_label' => status_label((string)($row['engagement_type'] ?? 'meeting')),
            'status_label' => status_label((string)($row['status'] ?? 'open')),
            'date_label' => format_date($row['log_date'] ?? null),
            'follow_up_label' => format_date($row['follow_up_date'] ?? null),
            'recorded_by_name' => trim((string)($row['recorded_by_name'] ?? '')) ?: 'System',
        ]);
    }

    private static function meetingSelect(): string
    {
        return "SELECT smm.*, p.name AS project_name, c.name AS constituency_name, CONCAT(rec.first_name, ' ', rec.last_name) AS recorded_by_name
                FROM site_meeting_minutes smm
                JOIN projects p ON p.id = smm.project_id
                LEFT JOIN constituencies c ON c.id = p.constituency_id
                LEFT JOIN users rec ON rec.id = smm.recorded_by";
    }

    private static function incidentSelect(): string
    {
        return "SELECT hsi.*, p.name AS project_name, c.name AS constituency_name, CONCAT(rep.first_name, ' ', rep.last_name) AS reported_by_name
                FROM hs_incidents hsi
                JOIN projects p ON p.id = hsi.project_id
                LEFT JOIN constituencies c ON c.id = p.constituency_id
                LEFT JOIN users rep ON rep.id = hsi.reported_by";
    }

    private static function communitySelect(): string
    {
        return "SELECT cl.*, p.name AS project_name, c.name AS constituency_name, CONCAT(rec.first_name, ' ', rec.last_name) AS recorded_by_name
                FROM community_liaison cl
                JOIN projects p ON p.id = cl.project_id
                LEFT JOIN constituencies c ON c.id = p.constituency_id
                LEFT JOIN users rec ON rec.id = cl.recorded_by";
    }

    private static function meetingWhere(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::dateFilters($where, $bindings, 'smm.meeting_date', $filters);
        self::exactFilter($where, $bindings, 'smm.status', $filters['status'] ?? '');
        self::exactFilter($where, $bindings, 'smm.action_status', $filters['action_status'] ?? '');
        if (($filters['document'] ?? '') === 'yes') {
            $where[] = "smm.document_path IS NOT NULL AND smm.document_path <> ''";
        } elseif (($filters['document'] ?? '') === 'no') {
            $where[] = "(smm.document_path IS NULL OR smm.document_path = '')";
        }
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(smm.venue LIKE ? OR smm.agenda LIKE ? OR smm.minutes_text LIKE ? OR p.name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function incidentWhere(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::dateFilters($where, $bindings, 'hsi.incident_date', $filters);
        self::exactFilter($where, $bindings, 'hsi.status', $filters['status'] ?? '');
        self::exactFilter($where, $bindings, 'hsi.severity', $filters['severity'] ?? '');
        self::exactFilter($where, $bindings, 'hsi.incident_type', $filters['incident_type'] ?? '');
        if (($filters['followup'] ?? '') === 'due') {
            $where[] = "hsi.follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND hsi.status NOT IN ('resolved','closed')";
        } elseif (($filters['followup'] ?? '') === 'overdue') {
            $where[] = "hsi.follow_up_date < CURDATE() AND hsi.status NOT IN ('resolved','closed')";
        }
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(hsi.description LIKE ? OR hsi.persons_involved LIKE ? OR hsi.cause LIKE ? OR hsi.corrective_action LIKE ? OR p.name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function communityWhere(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::dateFilters($where, $bindings, 'cl.log_date', $filters);
        self::exactFilter($where, $bindings, 'cl.status', $filters['status'] ?? '');
        self::exactFilter($where, $bindings, 'cl.engagement_type', $filters['engagement_type'] ?? '');
        if (($filters['followup'] ?? '') === 'due') {
            $where[] = "cl.follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND cl.status NOT IN ('resolved','closed')";
        } elseif (($filters['followup'] ?? '') === 'overdue') {
            $where[] = "cl.follow_up_date < CURDATE() AND cl.status NOT IN ('resolved','closed')";
        }
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(cl.community_rep LIKE ? OR cl.issues_raised LIKE ? OR cl.resolution LIKE ? OR p.name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function baseWhere(int $userId, string $role, string $projectAlias, array $filters): array
    {
        $where = [];
        $bindings = [];
        if ($role !== 'superadmin') {
            $where[] = "EXISTS (SELECT 1 FROM project_assignments mpa WHERE mpa.project_id = {$projectAlias}.id AND mpa.user_id = ? AND mpa.status = 'active')";
            $bindings[] = $userId;
        }
        if (!empty($filters['project_id'])) {
            $where[] = "{$projectAlias}.id = ?";
            $bindings[] = (int)$filters['project_id'];
        }
        return [$where, $bindings];
    }

    private static function scopeAnd(int $userId, string $role, string $projectAlias): string
    {
        return $role === 'superadmin' ? '' : " AND EXISTS (SELECT 1 FROM project_assignments mpa WHERE mpa.project_id = {$projectAlias}.id AND mpa.user_id = ? AND mpa.status = 'active')";
    }

    private static function scopeBindings(int $userId, string $role): array
    {
        return $role === 'superadmin' ? [] : [$userId];
    }

    private static function dateFilters(array &$where, array &$bindings, string $column, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $where[] = "{$column} >= ?";
            $bindings[] = (string)$filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "{$column} <= ?";
            $bindings[] = (string)$filters['date_to'];
        }
    }

    private static function exactFilter(array &$where, array &$bindings, string $column, mixed $value): void
    {
        $value = trim((string)$value);
        if ($value !== '') {
            $where[] = "{$column} = ?";
            $bindings[] = $value;
        }
    }

    private static function numericDefaults(array $row, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = (int)($row[$key] ?? 0);
        }
        return $out;
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }

    private static function option(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private static function dateOrToday(mixed $value): string
    {
        $value = trim((string)$value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : date('Y-m-d');
    }

    private static function nullableDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private static function jsonList(mixed $value): string
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/\r\n|\r|\n/', (string)$value) ?: [];
        }
        $items = array_values(array_filter(array_map(static fn (mixed $item): string => trim((string)$item), $items), static fn (string $item): bool => $item !== ''));
        return json_encode($items, JSON_UNESCAPED_SLASHES);
    }

    private static function decodeList(mixed $json): array
    {
        $decoded = json_decode((string)$json, true);
        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }
}
