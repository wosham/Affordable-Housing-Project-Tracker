<?php

class ContractorSubmission
{
    public static function types(): array
    {
        return [
            'eot' => [
                'title' => 'EOT Request',
                'plural' => 'EOT Requests',
                'label' => 'Time request',
                'icon' => 'fa-clock',
                'table' => 'eot_requests',
                'owner' => 'submitted_by',
                'number' => 'eot_number',
                'number_label' => 'EOT',
                'date' => 'created_at',
                'page' => 'admin/contractor/eot-request.php',
                'api' => 'api/contractor/eot-submit.php',
                'statuses' => ['pending', 'granted', 'partially-granted', 'rejected'],
                'columns' => ['reference', 'project', 'details', 'impact', 'status', 'submitted'],
            ],
            'variation' => [
                'title' => 'Variation Request',
                'plural' => 'Variation Requests',
                'label' => 'Change request',
                'icon' => 'fa-code-branch',
                'table' => 'variations',
                'owner' => 'submitted_by',
                'number' => 'vo_number',
                'number_label' => 'VAR',
                'date' => 'created_at',
                'page' => 'admin/contractor/variation-request.php',
                'api' => 'api/contractor/variation-submit.php',
                'statuses' => ['pending', 'approved', 'rejected'],
                'columns' => ['reference', 'project', 'details', 'impact', 'status', 'submitted'],
            ],
            'rfi' => [
                'title' => 'RFI',
                'plural' => 'RFIs',
                'label' => 'Information request',
                'icon' => 'fa-circle-question',
                'table' => 'rfis',
                'owner' => 'raised_by',
                'number' => 'rfi_number',
                'number_label' => 'RFI',
                'date' => 'raised_date',
                'page' => 'admin/contractor/rfis.php',
                'api' => 'api/contractor/rfi-submit.php',
                'statuses' => ['open', 'answered', 'closed'],
                'columns' => ['reference', 'project', 'details', 'priority', 'status', 'submitted'],
            ],
            'shop_drawing' => [
                'title' => 'Shop Drawing',
                'plural' => 'Shop Drawings',
                'label' => 'Drawing submission',
                'icon' => 'fa-compass-drafting',
                'table' => 'shop_drawings',
                'owner' => 'submitted_by',
                'number' => 'drawing_no',
                'number_label' => 'SD',
                'date' => 'submitted_date',
                'page' => 'admin/contractor/shop-drawing-submit.php',
                'api' => 'api/contractor/shop-drawing-submit.php',
                'statuses' => ['under-review', 'approved', 'rejected', 'resubmit'],
                'columns' => ['reference', 'project', 'details', 'revision', 'status', 'submitted'],
            ],
            'material' => [
                'title' => 'Material Approval',
                'plural' => 'Material Approvals',
                'label' => 'Material submission',
                'icon' => 'fa-cubes',
                'table' => 'material_approvals',
                'owner' => 'submitted_by',
                'number' => '',
                'number_label' => 'MAT',
                'date' => 'submitted_date',
                'page' => 'admin/contractor/material-approval-submit.php',
                'api' => 'api/contractor/material-approval-submit.php',
                'statuses' => ['pending', 'approved', 'rejected'],
                'columns' => ['reference', 'project', 'details', 'specification', 'status', 'submitted'],
            ],
        ];
    }

    public static function config(string $type): array
    {
        $configs = self::types();
        if (!isset($configs[$type])) {
            throw new InvalidArgumentException('Unsupported submission type.');
        }
        return $configs[$type];
    }

    public static function list(string $type, int $userId, string $role, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $bindings, $alias, $config] = self::filterSql($type, $userId, $role, $filters);
        if ($where === null) {
            return [];
        }

        try {
            return Database::fetchAll(
                self::selectSql($type) . ' WHERE ' . implode(' AND ', $where)
                . " ORDER BY {$alias}.{$config['date']} DESC, {$alias}.id DESC"
                . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
                $bindings
            );
        } catch (Throwable $e) {
            Logger::error('contractor_submission_list_failed', ['type' => $type, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public static function count(string $type, int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings, $alias, $config] = self::filterSql($type, $userId, $role, $filters);
        if ($where === null) {
            return 0;
        }
        try {
            $row = Database::fetch(
                "SELECT COUNT(*) AS total FROM {$config['table']} {$alias}
                 JOIN projects p ON p.id = {$alias}.project_id
                 WHERE " . implode(' AND ', $where),
                $bindings
            );
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            Logger::error('contractor_submission_count_failed', ['type' => $type, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    public static function findForUser(string $type, int $id, int $userId, string $role): ?array
    {
        $config = self::config($type);
        $projectIds = ContractorProject::projectIds($userId, $role);
        if ($id <= 0 || $projectIds === []) {
            return null;
        }
        [$in, $projectBindings] = self::inClause($projectIds);
        $alias = self::alias($type);
        try {
            $row = Database::fetch(
                self::selectSql($type) . " WHERE {$alias}.id = ? AND {$alias}.project_id IN ({$in}) AND {$alias}.{$config['owner']} = ? LIMIT 1",
                array_merge([$id], $projectBindings, [$userId])
            );
            return $row ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function stats(string $type, int $userId, string $role, array $filters = []): array
    {
        $config = self::config($type);
        $projectIds = ContractorProject::projectIds($userId, $role);
        $stats = ['total' => 0, 'pending' => 0, 'accepted' => 0, 'returned' => 0, 'this_month' => 0, 'impact' => 0, 'closed' => 0];
        if ($projectIds === []) {
            return $stats;
        }

        // Scope stats to selected project when provided (matches list).
        if (!empty($filters['project_id']) && in_array((int)$filters['project_id'], $projectIds, true)) {
            $projectIds = [(int)$filters['project_id']];
        }

        [$in, $bindings] = self::inClause($projectIds);
        $bindings[] = $userId;
        $date = $config['date'];
        $table = $config['table'];
        $owner = $config['owner'];
        $accepted = self::acceptedStatuses($type);
        $returned = self::returnedStatuses($type);
        $pending = self::pendingStatuses($type);
        $impactExpr = self::impactExpression($type);
        $closed = self::closedStatuses($type);

        try {
            $row = Database::fetch(
                "SELECT
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status IN ({$pending}) THEN 1 ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN status IN ({$accepted}) THEN 1 ELSE 0 END), 0) AS accepted,
                    COALESCE(SUM(CASE WHEN status IN ({$returned}) THEN 1 ELSE 0 END), 0) AS returned,
                    COALESCE(SUM(CASE WHEN status IN ({$closed}) THEN 1 ELSE 0 END), 0) AS closed,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT({$date}, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN 1 ELSE 0 END), 0) AS this_month,
                    COALESCE(SUM({$impactExpr}), 0) AS impact
                 FROM {$table}
                 WHERE project_id IN ({$in}) AND {$owner} = ?",
                $bindings
            ) ?: [];

            foreach ($stats as $key => $value) {
                $stats[$key] = is_numeric($row[$key] ?? null) ? (float)$row[$key] : $value;
            }
        } catch (Throwable $e) {
            Logger::error('contractor_submission_stats_failed', ['type' => $type, 'error' => $e->getMessage()]);
        }

        return $stats;
    }

    /** @return array{0:?array,1:?array,2:?string,3:?array} */
    private static function filterSql(string $type, int $userId, string $role, array $filters): array
    {
        $config = self::config($type);
        $projectIds = ContractorProject::projectIds($userId, $role);
        if ($projectIds === []) {
            return [null, null, null, null];
        }

        [$in, $projectBindings] = self::inClause($projectIds);
        $alias = self::alias($type);
        $where = ["{$alias}.project_id IN ({$in})", "{$alias}.{$config['owner']} = ?"];
        $bindings = array_merge($projectBindings, [$userId]);

        if (!empty($filters['project_id']) && in_array((int)$filters['project_id'], $projectIds, true)) {
            $where[] = "{$alias}.project_id = ?";
            $bindings[] = (int)$filters['project_id'];
        }

        $bucket = strtolower(trim((string)($filters['bucket'] ?? '')));
        $statusExact = (string)($filters['status'] ?? '');
        if ($statusExact !== '' && in_array($statusExact, $config['statuses'], true)) {
            $where[] = "{$alias}.status = ?";
            $bindings[] = $statusExact;
        } elseif ($bucket !== '' && in_array($bucket, ['pending', 'accepted', 'returned', 'closed', 'month'], true)) {
            if ($bucket === 'month') {
                $where[] = "DATE_FORMAT({$alias}.{$config['date']}, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            } else {
                $statusSql = match ($bucket) {
                    'pending' => self::pendingStatuses($type),
                    'accepted' => self::acceptedStatuses($type),
                    'returned' => self::returnedStatuses($type),
                    'closed' => self::closedStatuses($type),
                    default => "'__never__'",
                };
                $where[] = "{$alias}.status IN ({$statusSql})";
            }
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $search = self::searchColumns($type, $alias);
            $where[] = '(' . implode(' OR ', array_map(static fn (string $column): string => "{$column} LIKE ?", $search)) . ')';
            foreach ($search as $_) {
                $bindings[] = '%' . $q . '%';
            }
        }

        return [$where, $bindings, $alias, $config];
    }

    public static function attachments(string $type, int $submissionId): array
    {
        if ($submissionId <= 0 || !isset(self::types()[$type])) {
            return [];
        }
        try {
            return Database::fetchAll(
                'SELECT csa.id, csa.submission_type, csa.submission_id, csa.project_id, csa.media_id,
                        csa.path, csa.title, csa.uploaded_by, csa.created_at,
                        m.path AS media_path, m.title AS media_title, m.type AS media_type, m.extension AS media_extension
                 FROM contractor_submission_attachments csa
                 LEFT JOIN media_library m ON m.id = csa.media_id
                 WHERE csa.submission_type = ? AND csa.submission_id = ?
                 ORDER BY csa.id ASC',
                [$type, $submissionId]
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function detailForUser(string $type, int $id, int $userId, string $role): ?array
    {
        $row = self::findForUser($type, $id, $userId, $role);
        if (!$row) {
            return null;
        }
        return [
            'item' => $row,
            'attachments' => self::attachments($type, $id),
            'type' => $type,
            'config' => self::config($type),
        ];
    }

    public static function create(string $type, int $userId, string $role, array $input): int
    {
        $config = self::config($type);
        $projectId = Security::cleanInt($input['project_id'] ?? 0);
        if (!ContractorProject::canAccess($userId, $role, $projectId)) {
            throw new RuntimeException('Project could not be found.');
        }

        $data = self::payload($type, $projectId, $userId, $input);
        $next = self::nextNumber($config, $projectId);
        if ($config['number'] !== '' && !isset($data[$config['number']])) {
            $data[$config['number']] = $next;
        }

        $id = self::insert($config['table'], $data);
        self::recordAttachment($type, $id, $projectId, $userId, $input);
        self::audit($type, $id, $projectId, $userId);
        self::notify($type, $id, $projectId);
        return $id;
    }

    public static function fields(string $type): array
    {
        $fields = match ($type) {
            'eot' => [
                ['name' => 'delay_category', 'label' => 'Delay category', 'type' => 'select', 'options' => [
                    'weather' => 'Weather',
                    'access' => 'Access',
                    'design' => 'Design information',
                    'materials' => 'Materials',
                    'utilities' => 'Utilities',
                    'labour' => 'Labour',
                    'authority' => 'Authority',
                    'other' => 'Other',
                ], 'required' => true],
                ['name' => 'days_requested', 'label' => 'Days requested', 'type' => 'number', 'required' => true, 'min' => 1],
                ['name' => 'impact_summary', 'label' => 'Impact summary', 'type' => 'textarea', 'required' => true],
                ['name' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true],
            ],
            'variation' => [
                ['name' => 'description', 'label' => 'Variation description', 'type' => 'textarea', 'required' => true],
                ['name' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true],
                ['name' => 'amount', 'label' => 'Estimated cost impact', 'type' => 'number', 'step' => '0.01', 'min' => 0],
                ['name' => 'impact_on_time_days', 'label' => 'Time impact days', 'type' => 'number', 'min' => 0],
            ],
            'rfi' => [
                ['name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'required' => true],
                ['name' => 'urgency', 'label' => 'Priority', 'type' => 'select', 'options' => ['low' => 'Low', 'normal' => 'Normal', 'urgent' => 'Urgent'], 'required' => true],
                ['name' => 'description', 'label' => 'Question / details', 'type' => 'textarea', 'required' => true],
            ],
            'shop_drawing' => [
                ['name' => 'drawing_no', 'label' => 'Drawing number', 'type' => 'text', 'required' => true],
                ['name' => 'title', 'label' => 'Drawing title', 'type' => 'text', 'required' => true],
                ['name' => 'revision', 'label' => 'Revision', 'type' => 'text', 'placeholder' => 'A'],
            ],
            'material' => [
                ['name' => 'material', 'label' => 'Material name', 'type' => 'text', 'required' => true],
                ['name' => 'specification', 'label' => 'Specification / supplier details', 'type' => 'textarea', 'required' => true],
                ['name' => 'notes', 'label' => 'Supporting notes', 'type' => 'textarea'],
            ],
            default => [],
        };

        return array_merge($fields, self::supportingFields($type));
    }

    private static function payload(string $type, int $projectId, int $userId, array $input): array
    {
        $today = date('Y-m-d');
        return match ($type) {
            'eot' => [
                'project_id' => $projectId,
                'submitted_by' => $userId,
                'days_requested' => max(1, Security::cleanInt($input['days_requested'] ?? 0)),
                'reason' => self::required($input, 'reason'),
                'supporting_evidence' => self::optional($input, 'supporting_evidence', 255),
                'delay_category' => self::delayCategorySlug((string)($input['delay_category'] ?? 'other')),
                'impact_summary' => self::required($input, 'impact_summary'),
                'status' => 'pending',
                'consultant_review_status' => 'pending',
            ],
            'variation' => [
                'project_id' => $projectId,
                'submitted_by' => $userId,
                'description' => self::required($input, 'description'),
                'reason' => self::optional($input, 'reason'),
                'amount' => max(0, Security::cleanFloat($input['amount'] ?? 0)),
                'impact_on_time_days' => max(0, Security::cleanInt($input['impact_on_time_days'] ?? 0)),
                'status' => 'pending',
                'consultant_review_status' => 'pending',
            ],
            'rfi' => [
                'project_id' => $projectId,
                'raised_by' => $userId,
                'subject' => self::required($input, 'subject', 200),
                'description' => self::required($input, 'description'),
                'urgency' => in_array((string)($input['urgency'] ?? 'normal'), ['low', 'normal', 'urgent'], true) ? (string)$input['urgency'] : 'normal',
                'raised_date' => $today,
                'status' => 'open',
            ],
            'shop_drawing' => [
                'project_id' => $projectId,
                'drawing_no' => self::required($input, 'drawing_no', 60),
                'title' => self::required($input, 'title', 200),
                'submitted_by' => $userId,
                'submitted_date' => $today,
                'revision' => self::optional($input, 'revision', 10) ?: 'A',
                'document_path' => self::optional($input, 'document_path', 255),
                'status' => 'under-review',
            ],
            'material' => [
                'project_id' => $projectId,
                'material' => self::required($input, 'material', 150),
                'specification' => self::required($input, 'specification'),
                'submitted_by' => $userId,
                'submitted_date' => $today,
                'notes' => self::optional($input, 'notes'),
                'status' => 'pending',
            ],
            default => throw new InvalidArgumentException('Unsupported submission type.'),
        };
    }

    private static function selectSql(string $type): string
    {
        $config = self::config($type);
        $alias = self::alias($type);
        return "SELECT {$alias}.*, p.name AS project_name, c.name AS constituency_name, w.name AS ward_name,
                    (SELECT COUNT(*) FROM contractor_submission_attachments csa WHERE csa.submission_type = '{$type}' AND csa.submission_id = {$alias}.id) AS attachment_count
                FROM {$config['table']} {$alias}
                JOIN projects p ON p.id = {$alias}.project_id
                LEFT JOIN constituencies c ON c.id = p.constituency_id
                LEFT JOIN wards w ON w.id = p.ward_id";
    }

    private static function searchColumns(string $type, string $alias): array
    {
        return match ($type) {
            'eot' => ["{$alias}.reason", "{$alias}.impact_summary", "{$alias}.delay_category"],
            'variation' => ["{$alias}.description", "{$alias}.reason"],
            'rfi' => ["{$alias}.subject", "{$alias}.description"],
            'shop_drawing' => ["{$alias}.drawing_no", "{$alias}.title", "{$alias}.revision"],
            'material' => ["{$alias}.material", "{$alias}.specification", "{$alias}.notes"],
            default => ["{$alias}.status"],
        };
    }

    private static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $safe = array_map(static fn (string $column): string => '`' . str_replace('`', '', $column) . '`', $columns);
        Database::query(
            'INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $safe) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            array_values($data)
        );
        return (int)Database::lastInsertId();
    }

    private static function recordAttachment(string $type, int $submissionId, int $projectId, int $userId, array $input): void
    {
        $reference = self::optional($input, 'attachment_reference', 255);
        $title = self::optional($input, 'attachment_title', 180);
        $mediaId = Security::cleanInt($input['media_id'] ?? 0);

        if ($reference === null && $mediaId <= 0) {
            $reference = match ($type) {
                'eot' => self::optional($input, 'supporting_evidence', 255),
                'shop_drawing' => self::optional($input, 'document_path', 255),
                default => null,
            };
        }

        if ($mediaId > 0) {
            $media = self::resolveMediaForUser($mediaId, $userId);
            if (!$media) {
                $mediaId = 0;
            } else {
                if ($reference === null || $reference === '') {
                    $reference = (string)($media['path'] ?? '');
                }
                if ($title === null || $title === '') {
                    $title = (string)($media['title'] ?? '') ?: null;
                }
            }
        }

        if (($reference === null || $reference === '') && $mediaId <= 0) {
            return;
        }

        try {
            Database::query(
                'INSERT INTO contractor_submission_attachments
                    (submission_type, submission_id, project_id, media_id, path, title, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $type,
                    $submissionId,
                    $projectId,
                    $mediaId > 0 ? $mediaId : null,
                    $reference,
                    $title ?: self::config($type)['title'] . ' support',
                    $userId,
                ]
            );
        } catch (Throwable) {
        }
    }

    private static function resolveMediaForUser(int $mediaId, int $userId): ?array
    {
        if ($mediaId <= 0 || $userId <= 0) {
            return null;
        }
        try {
            $row = Database::fetch(
                'SELECT id, path, title, folder, uploaded_by
                 FROM media_library
                 WHERE id = ? AND uploaded_by = ? AND folder IN (\'site-photos\', \'contractor-documents\')
                 LIMIT 1',
                [$mediaId, $userId]
            );
            return $row ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function supportingFields(string $type): array
    {
        $pathField = match ($type) {
            'eot' => 'supporting_evidence',
            'shop_drawing' => 'document_path',
            default => 'attachment_reference',
        };

        return [
            [
                'name' => 'attachment_title',
                'label' => 'Attachment title',
                'type' => 'text',
                'placeholder' => 'Optional title for the supporting file',
            ],
            [
                'name' => $pathField,
                'label' => 'Supporting document or media',
                'type' => 'media',
                'placeholder' => 'Choose a library file or upload a supporting document',
                'help' => 'Optional. Attach drawings, letters, photos, PDFs or site evidence from your media library.',
            ],
        ];
    }

    private static function nextNumber(array $config, int $projectId): int
    {
        if ($config['number'] === '') {
            return 0;
        }

        $row = Database::fetch("SELECT COALESCE(MAX({$config['number']}), 0) + 1 AS next_no FROM {$config['table']} WHERE project_id = ?", [$projectId]);
        return max(1, (int)($row['next_no'] ?? 1));
    }

    private static function audit(string $type, int $id, int $projectId, int $userId): void
    {
        try {
            Logger::log('create', self::config($type)['table'], $id, ['project_id' => $projectId, 'submitted_by' => $userId]);
        } catch (Throwable) {
        }
    }

    private static function notify(string $type, int $id, int $projectId): void
    {
        try {
            $config = self::config($type);
            $project = Project::findDetailed($projectId);
            $projectName = $project['name'] ?? 'a project';
            $message = $config['title'] . ' submitted for ' . $projectName . '.';
            $managerLink = match ($type) {
                'eot' => 'admin/manager/eot-requests.php',
                'variation' => 'admin/manager/reports.php',
                default => 'admin/manager/dashboard.php',
            };
            $consultantLink = match ($type) {
                'material' => 'admin/consultant/material-approvals.php',
                'shop_drawing' => 'admin/consultant/shop-drawings.php',
                'eot' => 'admin/consultant/eot-review.php',
                'variation' => 'admin/consultant/variations.php',
                default => 'admin/consultant/dashboard.php',
            };
            Notification::pushRole('manager', 'contractor_submission', $config['title'] . ' submitted', $message, $managerLink);
            Notification::pushRole('consultant', 'contractor_submission', $config['title'] . ' submitted', $message, $consultantLink);
            Notification::pushRole('superadmin', 'contractor_submission', $config['title'] . ' submitted', $message, 'admin/superadmin/dashboard.php');
        } catch (Throwable) {
        }
    }

    private static function delayCategorySlug(string $value): string
    {
        $value = strtolower(trim($value));
        $map = [
            'weather' => 'weather',
            'access' => 'access',
            'design' => 'design',
            'design information' => 'design',
            'materials' => 'materials',
            'utilities' => 'utilities',
            'labour' => 'labour',
            'authority' => 'authority',
            'site condition' => 'other',
            'other' => 'other',
        ];
        return $map[$value] ?? (in_array($value, ['weather', 'access', 'design', 'materials', 'utilities', 'labour', 'authority', 'other'], true) ? $value : 'other');
    }

    private static function required(array $input, string $key, int $limit = 0): string
    {
        $value = trim((string)($input[$key] ?? ''));
        if ($value === '') {
            throw new RuntimeException('Please complete all required fields.');
        }
        return $limit > 0 ? substr($value, 0, $limit) : $value;
    }

    private static function optional(array $input, string $key, int $limit = 0): ?string
    {
        $value = trim((string)($input[$key] ?? ''));
        if ($value === '') {
            return null;
        }
        return $limit > 0 ? substr($value, 0, $limit) : $value;
    }

    private static function acceptedStatuses(string $type): string
    {
        return match ($type) {
            'eot' => "'granted','partially-granted'",
            'rfi' => "'answered','closed'",
            'shop_drawing' => "'approved'",
            default => "'approved'",
        };
    }

    private static function returnedStatuses(string $type): string
    {
        // RFI has no rejected status in schema — do not double-count closed as returned.
        return match ($type) {
            'rfi' => "'__never__'",
            'shop_drawing' => "'rejected','resubmit'",
            default => "'rejected'",
        };
    }

    private static function closedStatuses(string $type): string
    {
        return match ($type) {
            'rfi' => "'closed'",
            default => "'__never__'",
        };
    }

    private static function pendingStatuses(string $type): string
    {
        // shop_drawing: resubmit is returned, not pending.
        return match ($type) {
            'rfi' => "'open'",
            'shop_drawing' => "'under-review'",
            default => "'pending'",
        };
    }

    private static function impactExpression(string $type): string
    {
        return match ($type) {
            'eot' => 'days_requested',
            'variation' => 'amount',
            default => '0',
        };
    }

    private static function alias(string $type): string
    {
        return match ($type) {
            'eot' => 'e',
            'variation' => 'v',
            'rfi' => 'r',
            'shop_drawing' => 'sd',
            'material' => 'ma',
            default => 's',
        };
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
