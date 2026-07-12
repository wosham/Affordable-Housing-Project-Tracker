<?php

class ContractorSiteRecord
{
    public static function config(string $type): array
    {
        $configs = self::types();
        if (!isset($configs[$type])) {
            throw new InvalidArgumentException('Unsupported contractor record type.');
        }
        return $configs[$type];
    }

    public static function types(): array
    {
        return [
            'documents' => [
                'title' => 'Documents',
                'label' => 'Project documents',
                'icon' => 'fa-folder-open',
                'page' => 'admin/contractor/documents.php',
                'api' => 'api/contractor/document-save.php',
                'table' => 'documents',
                'date' => 'created_at',
                'owner' => 'uploaded_by',
                'statuses' => ['contract', 'drawing', 'spec', 'report', 'correspondence', 'shop-drawing', 'quality-test', 'other'],
                'filter_label' => 'Category',
                'can_create' => true,
            ],
            'equipment' => [
                'title' => 'Equipment Register',
                'label' => 'Plant and equipment',
                'icon' => 'fa-truck-ramp-box',
                'page' => 'admin/contractor/equipment-register.php',
                'api' => 'api/contractor/equipment-save.php',
                'table' => 'equipment_register',
                'date' => 'date_on_site',
                'owner' => 'updated_by',
                'statuses' => ['on-site', 'off-site', 'maintenance'],
                'filter_label' => 'Status',
                'can_create' => true,
            ],
            'incidents' => [
                'title' => 'H&S Incidents',
                'label' => 'Safety records',
                'icon' => 'fa-triangle-exclamation',
                'page' => 'admin/contractor/hs-incidents.php',
                'api' => 'api/contractor/hs-incident-save.php',
                'table' => 'hs_incidents',
                'date' => 'incident_date',
                'owner' => 'reported_by',
                // Contractor may open / update investigation; close is manager-side.
                'statuses' => ['open', 'investigating', 'action-pending'],
                'filter_statuses' => ['open', 'investigating', 'action-pending', 'resolved', 'closed'],
                'filter_label' => 'Status',
                'can_create' => true,
            ],
            'labour' => [
                'title' => 'Labour Register',
                'label' => 'Daily workforce',
                'icon' => 'fa-people-carry-box',
                'page' => 'admin/contractor/labour-register.php',
                'api' => 'api/contractor/labour-save.php',
                'table' => 'labour_register',
                'date' => 'diary_date',
                'owner' => 'recorded_by',
                'statuses' => ['submitted'],
                'filter_statuses' => ['submitted', 'reviewed'],
                'filter_label' => 'Status',
                'can_create' => true,
            ],
            'materials' => [
                'title' => 'Material Deliveries',
                'label' => 'Delivery records',
                'icon' => 'fa-truck',
                'page' => 'admin/contractor/material-deliveries.php',
                'api' => 'api/contractor/material-delivery-save.php',
                'table' => 'material_deliveries',
                'date' => 'delivery_date',
                'owner' => 'received_by',
                // Contractor submits / queries; acceptance is clerk-side.
                'statuses' => ['submitted', 'queried'],
                'filter_statuses' => ['submitted', 'accepted', 'queried'],
                'filter_label' => 'Status',
                'can_create' => true,
            ],
            'subcontractors' => [
                'title' => 'Subcontractors',
                'label' => 'Site partners',
                'icon' => 'fa-helmet-safety',
                'page' => 'admin/contractor/subcontractors.php',
                'api' => 'api/contractor/subcontractor-save.php',
                'table' => 'subcontractors',
                'date' => 'created_at',
                'owner' => 'updated_by',
                'statuses' => ['active', 'pending', 'suspended', 'terminated'],
                'filter_label' => 'Status',
                'can_create' => true,
            ],
        ];
    }

    public static function siteTypes(): array
    {
        return self::types();
    }

    public static function filterStatuses(string $type): array
    {
        $config = self::config($type);
        return $config['filter_statuses'] ?? $config['statuses'];
    }

    public static function fields(string $type): array
    {
        return match ($type) {
            'documents' => [
                ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => self::config('documents')['statuses'], 'required' => true],
                ['name' => 'original_name', 'label' => 'Document title', 'type' => 'text', 'required' => true],
                ['name' => 'filename', 'label' => 'Supporting file', 'type' => 'media', 'required' => true, 'help' => 'Choose a library file or upload PDF, drawing or office document.'],
                ['name' => 'version', 'label' => 'Version', 'type' => 'text', 'placeholder' => '1.0'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ],
            'equipment' => [
                ['name' => 'equipment_type', 'label' => 'Equipment type', 'type' => 'text', 'required' => true],
                ['name' => 'registration', 'label' => 'Registration / serial', 'type' => 'text'],
                ['name' => 'owner', 'label' => 'Owner', 'type' => 'text'],
                ['name' => 'condition', 'label' => 'Condition', 'type' => 'select', 'options' => ['good', 'serviceable', 'maintenance', 'poor'], 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::config('equipment')['statuses'], 'required' => true],
                ['name' => 'date_on_site', 'label' => 'Date on site', 'type' => 'date'],
                ['name' => 'date_off_site', 'label' => 'Date off site', 'type' => 'date'],
            ],
            'incidents' => [
                ['name' => 'incident_date', 'label' => 'Incident date', 'type' => 'date', 'required' => true],
                ['name' => 'incident_type', 'label' => 'Incident type', 'type' => 'select', 'options' => ['near-miss', 'first-aid', 'medical', 'fatality'], 'required' => true],
                ['name' => 'severity', 'label' => 'Severity', 'type' => 'select', 'options' => ['low', 'medium', 'high', 'critical'], 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::config('incidents')['statuses'], 'required' => true],
                ['name' => 'follow_up_date', 'label' => 'Follow-up date', 'type' => 'date'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
                ['name' => 'persons_involved', 'label' => 'Persons involved', 'type' => 'textarea'],
                ['name' => 'cause', 'label' => 'Cause', 'type' => 'textarea'],
                ['name' => 'corrective_action', 'label' => 'Corrective action', 'type' => 'textarea'],
                ['name' => 'attachment_path', 'label' => 'Evidence file', 'type' => 'media', 'help' => 'Optional photo, sketch or report supporting the incident.'],
            ],
            'labour' => [
                ['name' => 'diary_date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'skilled_count', 'label' => 'Skilled workers', 'type' => 'number', 'min' => 0],
                ['name' => 'unskilled_count', 'label' => 'Unskilled workers', 'type' => 'number', 'min' => 0],
                ['name' => 'supervisor_count', 'label' => 'Supervisors', 'type' => 'number', 'min' => 0],
            ],
            'materials' => [
                ['name' => 'material', 'label' => 'Material', 'type' => 'text', 'required' => true],
                ['name' => 'supplier', 'label' => 'Supplier', 'type' => 'text'],
                ['name' => 'delivery_date', 'label' => 'Delivery date', 'type' => 'date', 'required' => true],
                ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'step' => '0.001', 'min' => 0, 'required' => true],
                ['name' => 'unit', 'label' => 'Unit', 'type' => 'text', 'required' => true],
                ['name' => 'delivery_note_no', 'label' => 'Delivery note no.', 'type' => 'text'],
                ['name' => 'condition', 'label' => 'Condition', 'type' => 'select', 'options' => ['good', 'damaged', 'short-delivered', 'pending-check'], 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::config('materials')['statuses']],
                ['name' => 'delivery_note_ref', 'label' => 'Delivery note / photo', 'type' => 'media', 'help' => 'Optional delivery note scan or site photo.'],
            ],
            'subcontractors' => [
                ['name' => 'company', 'label' => 'Company', 'type' => 'text', 'required' => true],
                ['name' => 'contact_person', 'label' => 'Contact person', 'type' => 'text'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'contract_value', 'label' => 'Contract value', 'type' => 'number', 'step' => '0.01', 'min' => 0],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::config('subcontractors')['statuses']],
                ['name' => 'compliance_status', 'label' => 'Compliance', 'type' => 'select', 'options' => ['pending', 'compliant', 'issue', 'expired']],
                ['name' => 'risk_status', 'label' => 'Risk', 'type' => 'select', 'options' => ['normal', 'watch', 'high', 'critical']],
                ['name' => 'scope_of_work', 'label' => 'Scope of work', 'type' => 'textarea'],
                ['name' => 'performance_note', 'label' => 'Performance note', 'type' => 'textarea'],
            ],
            default => [],
        };
    }

    public static function projects(int $userId, string $role): array
    {
        return ContractorProject::projects($userId, $role);
    }

    public static function defaultProjectId(int $userId, string $role, int $requestedId = 0): int
    {
        return ContractorProject::defaultProjectId($userId, $role, $requestedId);
    }

    public static function stats(string $type, int $projectId, int $userId, string $role): array
    {
        if (!ContractorProject::canAccess($userId, $role, $projectId)) {
            return self::emptyStats();
        }
        return self::recordStats($type, $projectId);
    }

    public static function count(string $type, int $projectId, int $userId, string $role, array $filters = []): int
    {
        if (!ContractorProject::canAccess($userId, $role, $projectId)) {
            return 0;
        }
        [$where, $bindings, $alias, $config] = self::filterSql($type, $projectId, $filters);
        if ($where === null) {
            return 0;
        }
        try {
            $row = Database::fetch(
                "SELECT COUNT(*) AS total FROM {$config['table']} {$alias} WHERE " . implode(' AND ', $where),
                $bindings
            );
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            Logger::error('contractor_site_record_count_failed', ['type' => $type, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    public static function list(string $type, int $projectId, int $userId, string $role, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        if (!ContractorProject::canAccess($userId, $role, $projectId)) {
            return [];
        }
        [$where, $bindings, $alias, $config] = self::filterSql($type, $projectId, $filters);
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
            Logger::error('contractor_site_record_list_failed', ['type' => $type, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public static function findForUser(string $type, int $id, int $userId, string $role): ?array
    {
        if ($id <= 0 || !isset(self::types()[$type])) {
            return null;
        }
        try {
            $row = Database::fetch(self::selectSql($type) . ' WHERE ' . self::alias($type) . '.id = ? LIMIT 1', [$id]);
            if (!$row) {
                return null;
            }
            if (!ContractorProject::canAccess($userId, $role, (int)$row['project_id'])) {
                return null;
            }
            return $row;
        } catch (Throwable) {
            return null;
        }
    }

    public static function detailForUser(string $type, int $id, int $userId, string $role): ?array
    {
        $row = self::findForUser($type, $id, $userId, $role);
        if (!$row) {
            return null;
        }
        $media = null;
        $mediaId = (int)($row['evidence_media_id'] ?? 0);
        $path = (string)($row['filename'] ?? $row['attachment_path'] ?? '');
        if ($mediaId > 0) {
            $media = Database::fetch('SELECT id, path, title, type, extension, size FROM media_library WHERE id = ? LIMIT 1', [$mediaId]);
        } elseif ($path !== '' && str_starts_with($path, 'uploads/')) {
            $media = Database::fetch('SELECT id, path, title, type, extension, size FROM media_library WHERE path = ? LIMIT 1', [$path]);
        }
        return [
            'item' => $row,
            'type' => $type,
            'config' => self::config($type),
            'media' => $media,
        ];
    }

    public static function create(string $type, int $userId, string $role, array $input): int
    {
        $config = self::config($type);
        if (empty($config['can_create'])) {
            throw new RuntimeException('This record is view only.');
        }

        $projectId = Security::cleanInt($input['project_id'] ?? 0);
        if (!ContractorProject::canAccess($userId, $role, $projectId)) {
            throw new RuntimeException('Project could not be found.');
        }

        $data = self::payload($type, $projectId, $userId, $input, null);

        if ($type === 'labour') {
            $existing = self::findLabourByDate($projectId, (string)$data['diary_date']);
            if ($existing) {
                $merged = self::preserveProtected($type, $existing, $data);
                self::updateRow($config['table'], (int)$existing['id'], $merged);
                self::notify($type, $projectId, (int)$existing['id'], false);
                return (int)$existing['id'];
            }
        }

        $id = self::insert($config['table'], $data);
        self::notify($type, $projectId, $id, true);
        return $id;
    }

    public static function save(string $type, int $userId, string $role, array $input): int
    {
        $id = Security::cleanInt($input['id'] ?? 0);
        return $id > 0
            ? self::update($type, $id, $userId, $role, $input)
            : self::create($type, $userId, $role, $input);
    }

    public static function update(string $type, int $id, int $userId, string $role, array $input): int
    {
        $config = self::config($type);
        if (empty($config['can_create'])) {
            throw new RuntimeException('This record is view only.');
        }

        $existing = self::find($type, $id);
        if (!$existing) {
            throw new RuntimeException('Record could not be found.');
        }

        $projectId = (int)($existing['project_id'] ?? 0);
        if (!ContractorProject::canAccess($userId, $role, $projectId)) {
            throw new RuntimeException('Project could not be found.');
        }

        // Do not allow project moves via form.
        $input['project_id'] = $projectId;
        $data = self::payload($type, $projectId, $userId, $input, $existing);

        if ($type === 'labour') {
            $sameDay = self::findLabourByDate($projectId, (string)$data['diary_date']);
            if ($sameDay && (int)$sameDay['id'] !== $id) {
                $target = self::find($type, (int)$sameDay['id']) ?: $sameDay;
                $merged = self::preserveProtected($type, $target, $data);
                self::updateRow($config['table'], (int)$sameDay['id'], $merged);
                self::deleteRow($config['table'], $id);
                self::notify($type, $projectId, (int)$sameDay['id'], false);
                return (int)$sameDay['id'];
            }
        }

        $data = self::preserveProtected($type, $existing, $data);
        self::updateRow($config['table'], $id, $data);
        self::notify($type, $projectId, $id, false);
        return $id;
    }

    /** @return array{0:?array,1:?array,2:?string,3:?array} */
    private static function filterSql(string $type, int $projectId, array $filters): array
    {
        $config = self::config($type);
        $alias = self::alias($type);
        $where = ["{$alias}.project_id = ?"];
        $bindings = [$projectId];
        $filterStatuses = self::filterStatuses($type);

        $bucket = strtolower(trim((string)($filters['bucket'] ?? '')));
        $status = trim((string)($filters['status'] ?? ''));

        if ($status !== '' && in_array($status, $filterStatuses, true)) {
            if ($type === 'documents') {
                $where[] = "{$alias}.category = ?";
            } else {
                $where[] = "{$alias}.status = ?";
            }
            $bindings[] = $status;
        } elseif ($bucket !== '' && in_array($bucket, ['active', 'today', 'month', 'risk', 'all'], true)) {
            if ($bucket === 'today') {
                $where[] = "DATE({$alias}.{$config['date']}) = CURDATE()";
            } elseif ($bucket === 'month') {
                $where[] = "DATE_FORMAT({$alias}.{$config['date']}, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            } elseif ($bucket === 'active') {
                $where[] = self::activeWhere($type, $alias);
            } elseif ($bucket === 'risk') {
                $risk = self::riskWhere($type, $alias);
                if ($risk !== null) {
                    $where[] = $risk;
                } else {
                    $where[] = '0=1';
                }
            }
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $columns = self::searchColumns($type, $alias);
            $where[] = '(' . implode(' OR ', array_map(static fn ($col) => "{$col} LIKE ?", $columns)) . ')';
            foreach ($columns as $_) {
                $bindings[] = '%' . $q . '%';
            }
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = "DATE({$alias}.{$config['date']}) >= ?";
            $bindings[] = $dateFrom;
        }
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = "DATE({$alias}.{$config['date']}) <= ?";
            $bindings[] = $dateTo;
        }

        return [$where, $bindings, $alias, $config];
    }

    private static function activeWhere(string $type, string $alias): string
    {
        return match ($type) {
            'documents' => "{$alias}.consultant_review_status IN ('pending','flagged','returned')",
            'equipment' => "{$alias}.status = 'on-site'",
            'incidents' => "{$alias}.status IN ('open','investigating','action-pending')",
            'labour' => "{$alias}.status = 'submitted'",
            'materials' => "{$alias}.status IN ('submitted','queried')",
            'subcontractors' => "{$alias}.status IN ('active','pending')",
            default => '1=1',
        };
    }

    private static function riskWhere(string $type, string $alias): ?string
    {
        return match ($type) {
            'incidents' => "{$alias}.severity IN ('high','critical') OR {$alias}.status IN ('open','investigating','action-pending')",
            'materials' => "{$alias}.status = 'queried' OR {$alias}.`condition` <> 'good'",
            'subcontractors' => "{$alias}.risk_status IN ('high','critical') OR {$alias}.compliance_status IN ('issue','expired')",
            'equipment' => "{$alias}.status = 'maintenance' OR {$alias}.`condition` IN ('maintenance','poor')",
            'documents' => "{$alias}.consultant_review_status IN ('flagged','returned')",
            default => null,
        };
    }

    private static function recordStats(string $type, int $projectId): array
    {
        $config = self::config($type);
        $table = $config['table'];
        $date = $config['date'];
        $alias = 'r';

        try {
            $activeSql = str_replace(self::alias($type) . '.', 'r.', self::activeWhere($type, self::alias($type)));
            $riskSql = self::riskWhere($type, self::alias($type));
            $riskSql = $riskSql ? str_replace(self::alias($type) . '.', 'r.', $riskSql) : '0';

            $valueExpr = match ($type) {
                'labour' => 'COALESCE(SUM(r.total), 0)',
                'materials' => 'COALESCE(SUM(r.quantity), 0)',
                'subcontractors' => 'COALESCE(SUM(r.contract_value), 0)',
                'equipment' => "COALESCE(SUM(CASE WHEN r.status = 'on-site' THEN 1 ELSE 0 END), 0)",
                'documents' => "COALESCE(SUM(CASE WHEN r.consultant_review_status = 'pending' THEN 1 ELSE 0 END), 0)",
                'incidents' => "COALESCE(SUM(CASE WHEN r.severity IN ('high','critical') THEN 1 ELSE 0 END), 0)",
                default => '0',
            };

            $row = Database::fetch(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN {$activeSql} THEN 1 ELSE 0 END), 0) AS active,
                        COALESCE(SUM(CASE WHEN DATE(r.{$date}) = CURDATE() THEN 1 ELSE 0 END), 0) AS today,
                        COALESCE(SUM(CASE WHEN DATE_FORMAT(r.{$date}, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN 1 ELSE 0 END), 0) AS this_month,
                        {$valueExpr} AS value,
                        COALESCE(SUM(CASE WHEN ({$riskSql}) THEN 1 ELSE 0 END), 0) AS risk
                 FROM {$table} r
                 WHERE r.project_id = ?",
                [$projectId]
            ) ?: [];
        } catch (Throwable $e) {
            Logger::error('contractor_site_record_stats_failed', ['type' => $type, 'error' => $e->getMessage()]);
            $row = [];
        }

        return [
            'total' => (int)($row['total'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'today' => (int)($row['today'] ?? 0),
            'this_month' => (int)($row['this_month'] ?? 0),
            'value' => (float)($row['value'] ?? 0),
            'risk' => (int)($row['risk'] ?? 0),
        ];
    }

    private static function payload(string $type, int $projectId, int $userId, array $input, ?array $existing): array
    {
        return match ($type) {
            'documents' => self::documentPayload($projectId, $userId, $input, $existing),
            'equipment' => self::equipmentPayload($projectId, $userId, $input),
            'incidents' => self::incidentPayload($projectId, $userId, $input, $existing),
            'labour' => self::labourPayload($projectId, $userId, $input),
            'materials' => self::materialPayload($projectId, $userId, $input, $existing),
            'subcontractors' => self::subcontractorPayload($projectId, $userId, $input),
            default => throw new InvalidArgumentException('Unsupported contractor record type.'),
        };
    }

    private static function documentPayload(int $projectId, int $userId, array $input, ?array $existing): array
    {
        $media = self::resolveMediaInput($input, $userId, ['filename', 'attachment_reference']);
        $filename = $media['path'] ?: trim((string)($input['filename'] ?? ''));
        if ($filename === '' && $existing) {
            $filename = (string)($existing['filename'] ?? '');
        }
        if ($filename === '') {
            throw new RuntimeException('Please choose or upload a supporting file.');
        }
        $size = $media['size'] > 0 ? $media['size'] : max(0, Security::cleanInt($input['size'] ?? ($existing['size'] ?? 0)));

        return [
            'project_id' => $projectId,
            'uploaded_by' => $existing ? (int)($existing['uploaded_by'] ?? $userId) : $userId,
            'category' => self::choice($input, 'category', self::config('documents')['statuses'], 'other'),
            'filename' => substr($filename, 0, 255),
            'original_name' => self::required($input, 'original_name', 255),
            'size' => $size,
            'version' => self::optional($input, 'version', 20) ?: '1.0',
            'description' => self::optional($input, 'description'),
            'is_confidential' => (int)($existing['is_confidential'] ?? 0),
            'consultant_review_status' => $existing
                ? (string)($existing['consultant_review_status'] ?? 'pending')
                : 'pending',
        ];
    }

    private static function equipmentPayload(int $projectId, int $userId, array $input): array
    {
        $on = self::dateOrNull($input, 'date_on_site');
        $off = self::dateOrNull($input, 'date_off_site');
        if ($on && $off && $off < $on) {
            throw new RuntimeException('Date off site cannot be earlier than date on site.');
        }
        return [
            'project_id' => $projectId,
            'equipment_type' => self::required($input, 'equipment_type', 120),
            'registration' => self::optional($input, 'registration', 60),
            'owner' => self::optional($input, 'owner', 150),
            'date_on_site' => $on,
            'date_off_site' => $off,
            'condition' => self::choice($input, 'condition', ['good', 'serviceable', 'maintenance', 'poor'], 'good'),
            'status' => self::choice($input, 'status', self::config('equipment')['statuses'], 'on-site'),
            'updated_by' => $userId,
        ];
    }

    private static function incidentPayload(int $projectId, int $userId, array $input, ?array $existing): array
    {
        $media = self::resolveMediaInput($input, $userId, ['attachment_path', 'filename']);
        $statusAllowed = self::config('incidents')['statuses'];
        $status = self::choice($input, 'status', $statusAllowed, 'open');
        // Never let contractor form force closed/resolved if existing is open path; allow keeping existing closed.
        if ($existing && in_array((string)($existing['status'] ?? ''), ['resolved', 'closed'], true)) {
            $status = (string)$existing['status'];
        }

        return [
            'project_id' => $projectId,
            'incident_date' => self::requiredDate($input, 'incident_date'),
            'incident_type' => self::choice($input, 'incident_type', ['near-miss', 'first-aid', 'medical', 'fatality'], 'near-miss'),
            'description' => self::required($input, 'description'),
            'persons_involved' => self::optional($input, 'persons_involved'),
            'cause' => self::optional($input, 'cause'),
            'corrective_action' => self::optional($input, 'corrective_action'),
            'reported_by' => $existing ? (int)($existing['reported_by'] ?? $userId) : $userId,
            'severity' => self::choice($input, 'severity', ['low', 'medium', 'high', 'critical'], 'medium'),
            'status' => $status,
            'follow_up_date' => self::dateOrNull($input, 'follow_up_date'),
            'updated_by' => $userId,
            'attachment_path' => $media['path'] ?: self::optional($input, 'attachment_path', 255),
            'evidence_media_id' => $media['id'] > 0 ? $media['id'] : ($existing['evidence_media_id'] ?? null),
        ];
    }

    private static function labourPayload(int $projectId, int $userId, array $input): array
    {
        $skilled = max(0, Security::cleanInt($input['skilled_count'] ?? 0));
        $unskilled = max(0, Security::cleanInt($input['unskilled_count'] ?? 0));
        $supervisors = max(0, Security::cleanInt($input['supervisor_count'] ?? 0));
        if (($skilled + $unskilled + $supervisors) <= 0) {
            throw new RuntimeException('Enter at least one skilled, unskilled or supervisor count.');
        }
        return [
            'project_id' => $projectId,
            'diary_date' => self::requiredDate($input, 'diary_date'),
            'skilled_count' => $skilled,
            'unskilled_count' => $unskilled,
            'supervisor_count' => $supervisors,
            'total' => $skilled + $unskilled + $supervisors,
            'recorded_by' => $userId,
            'status' => 'submitted',
            'updated_by' => $userId,
        ];
    }

    private static function materialPayload(int $projectId, int $userId, array $input, ?array $existing): array
    {
        $qty = max(0, Security::cleanFloat($input['quantity'] ?? 0));
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }
        $media = self::resolveMediaInput($input, $userId, ['delivery_note_ref', 'attachment_path', 'filename']);
        $note = self::optional($input, 'delivery_note_no', 60);
        if ($media['path'] && $note === null) {
            $note = basename($media['path']);
        }
        // Keep clerk-accepted status if already accepted.
        $status = self::choice($input, 'status', self::config('materials')['statuses'], 'submitted');
        if ($existing && (string)($existing['status'] ?? '') === 'accepted') {
            $status = 'accepted';
        }

        return [
            'project_id' => $projectId,
            'material' => self::required($input, 'material', 150),
            'supplier' => self::optional($input, 'supplier', 150),
            'delivery_date' => self::requiredDate($input, 'delivery_date'),
            'quantity' => $qty,
            'unit' => self::required($input, 'unit', 30),
            'delivery_note_no' => $note,
            'received_by' => $existing ? (int)($existing['received_by'] ?? $userId) : $userId,
            'condition' => self::choice($input, 'condition', ['good', 'damaged', 'short-delivered', 'pending-check'], 'good'),
            'approved' => (int)($existing['approved'] ?? 0),
            'status' => $status,
            'updated_by' => $userId,
        ];
    }

    private static function subcontractorPayload(int $projectId, int $userId, array $input): array
    {
        $email = self::optional($input, 'email', 180);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid email address or leave it blank.');
        }
        return [
            'project_id' => $projectId,
            'company' => self::required($input, 'company', 200),
            'scope_of_work' => self::optional($input, 'scope_of_work'),
            'contract_value' => max(0, Security::cleanFloat($input['contract_value'] ?? 0)),
            'status' => self::choice($input, 'status', self::config('subcontractors')['statuses'], 'active'),
            'contact_person' => self::optional($input, 'contact_person', 150),
            'phone' => self::optional($input, 'phone', 60),
            'email' => $email,
            'compliance_status' => self::choice($input, 'compliance_status', ['pending', 'compliant', 'issue', 'expired'], 'pending'),
            'risk_status' => self::choice($input, 'risk_status', ['normal', 'watch', 'high', 'critical'], 'normal'),
            'performance_note' => self::optional($input, 'performance_note'),
            'updated_by' => $userId,
        ];
    }

    private static function resolveMediaInput(array $input, int $userId, array $pathKeys = []): array
    {
        $mediaId = Security::cleanInt($input['media_id'] ?? 0);
        $path = null;
        foreach ($pathKeys as $key) {
            $value = trim((string)($input[$key] ?? ''));
            if ($value !== '') {
                $path = substr($value, 0, 255);
                break;
            }
        }
        $size = 0;
        $title = null;

        if ($mediaId > 0) {
            $media = Database::fetch(
                "SELECT id, path, title, size, folder, uploaded_by
                 FROM media_library
                 WHERE id = ? AND uploaded_by = ? AND folder IN ('site-photos','contractor-documents') AND deleted_at IS NULL
                 LIMIT 1",
                [$mediaId, $userId]
            );
            if ($media) {
                $path = (string)$media['path'];
                $size = (int)($media['size'] ?? 0);
                $title = (string)($media['title'] ?? '');
            } else {
                $mediaId = 0;
            }
        }

        return ['id' => $mediaId, 'path' => $path, 'size' => $size, 'title' => $title];
    }

    private static function preserveProtected(string $type, array $existing, array $data): array
    {
        $protected = match ($type) {
            'documents' => ['consultant_review_status', 'consultant_review_note', 'consultant_reviewed_by', 'consultant_reviewed_at', 'is_confidential', 'clerk_document_type', 'review_required', 'linked_record_type', 'linked_record_id', 'site_record_date'],
            'equipment' => ['check_status', 'checked_by', 'checked_at', 'check_notes'],
            'labour' => ['clerk_skilled_count', 'clerk_unskilled_count', 'clerk_supervisor_count', 'clerk_total', 'variance_total', 'verification_status', 'verified_by', 'verified_at', 'verification_notes'],
            'materials' => ['verified_quantity', 'verification_status', 'verified_by', 'verified_at', 'verification_notes', 'approved'],
            'incidents' => ['closed_at', 'immediate_action', 'lost_time_hours'],
            default => [],
        };
        foreach ($protected as $column) {
            if (array_key_exists($column, $existing) && !array_key_exists($column, $data)) {
                // leave as-is by not updating — so remove from data if accidentally set empty
            }
            // Never overwrite protected fields from contractor form
            unset($data[$column]);
        }
        // For materials accepted, keep status if we already handled in payload
        return $data;
    }

    private static function selectSql(string $type): string
    {
        $alias = self::alias($type);
        return match ($type) {
            'documents' => "SELECT {$alias}.*, p.name AS project_name FROM documents {$alias} JOIN projects p ON p.id = {$alias}.project_id",
            'equipment' => "SELECT {$alias}.*, p.name AS project_name FROM equipment_register {$alias} JOIN projects p ON p.id = {$alias}.project_id",
            'incidents' => "SELECT {$alias}.*, p.name AS project_name, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS reported_by_name FROM hs_incidents {$alias} JOIN projects p ON p.id = {$alias}.project_id LEFT JOIN users u ON u.id = {$alias}.reported_by",
            'labour' => "SELECT {$alias}.*, p.name AS project_name FROM labour_register {$alias} JOIN projects p ON p.id = {$alias}.project_id",
            'materials' => "SELECT {$alias}.*, p.name AS project_name FROM material_deliveries {$alias} JOIN projects p ON p.id = {$alias}.project_id",
            'subcontractors' => "SELECT {$alias}.*, p.name AS project_name FROM subcontractors {$alias} JOIN projects p ON p.id = {$alias}.project_id",
            default => '',
        };
    }

    private static function searchColumns(string $type, string $alias): array
    {
        return match ($type) {
            'documents' => ["{$alias}.original_name", "{$alias}.filename", "{$alias}.description", "{$alias}.category"],
            'equipment' => ["{$alias}.equipment_type", "{$alias}.registration", "{$alias}.owner", "{$alias}.condition"],
            'incidents' => ["{$alias}.description", "{$alias}.persons_involved", "{$alias}.cause", "{$alias}.corrective_action"],
            'labour' => ["{$alias}.status", "CAST({$alias}.total AS CHAR)"],
            'materials' => ["{$alias}.material", "{$alias}.supplier", "{$alias}.delivery_note_no", "{$alias}.condition"],
            'subcontractors' => ["{$alias}.company", "{$alias}.scope_of_work", "{$alias}.contact_person", "{$alias}.phone", "{$alias}.email"],
            default => ["{$alias}.id"],
        };
    }

    private static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $safe = array_map(static fn ($column) => '`' . str_replace('`', '', (string)$column) . '`', $columns);
        Database::query(
            'INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $safe) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            array_values($data)
        );
        return (int)Database::lastInsertId();
    }

    private static function updateRow(string $table, int $id, array $data): void
    {
        if ($data === []) {
            return;
        }
        $columns = array_keys($data);
        $assignments = array_map(static fn ($column) => '`' . str_replace('`', '', (string)$column) . '` = ?', $columns);
        $bindings = array_values($data);
        $bindings[] = $id;
        Database::query(
            'UPDATE `' . str_replace('`', '', $table) . '` SET ' . implode(', ', $assignments) . ' WHERE id = ?',
            $bindings
        );
    }

    private static function deleteRow(string $table, int $id): void
    {
        Database::query('DELETE FROM `' . str_replace('`', '', $table) . '` WHERE id = ?', [$id]);
    }

    private static function find(string $type, int $id): ?array
    {
        $config = self::config($type);
        try {
            $row = Database::fetch(
                'SELECT * FROM `' . str_replace('`', '', $config['table']) . '` WHERE id = ? LIMIT 1',
                [$id]
            );
            return $row ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function findLabourByDate(int $projectId, string $date): ?array
    {
        try {
            $row = Database::fetch(
                'SELECT * FROM labour_register WHERE project_id = ? AND diary_date = ? LIMIT 1',
                [$projectId, $date]
            );
            return $row ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function notify(string $type, int $projectId, int $id, bool $isCreate): void
    {
        try {
            $config = self::config($type);
            $project = Project::findDetailed($projectId);
            $projectName = $project['name'] ?? 'a project';
            $verb = $isCreate ? 'submitted' : 'updated';
            $message = $config['title'] . ' ' . $verb . ' for ' . $projectName . '.';
            $title = $config['title'] . ' ' . $verb;
            Notification::pushRole('manager', 'contractor_record', $title, $message, self::managerLink($type));
            Notification::pushRole('superadmin', 'contractor_record', $title, $message, self::superadminLink($type));
            if ($type === 'incidents') {
                Notification::pushRole('consultant', 'contractor_record', 'H&S incident ' . $verb, $message, 'admin/consultant/dashboard.php');
            }
            if ($type === 'documents' && $isCreate) {
                Notification::pushRole('consultant', 'contractor_record', 'Document submitted', $message, 'admin/consultant/documents.php');
            }
        } catch (Throwable) {
        }
    }

    private static function managerLink(string $type): string
    {
        return match ($type) {
            'incidents' => 'admin/manager/hs-incidents.php',
            'subcontractors' => 'admin/manager/subcontractors.php',
            'labour' => 'admin/manager/attendance-summary.php',
            'materials', 'equipment', 'documents' => 'admin/manager/reports.php',
            default => 'admin/manager/dashboard.php',
        };
    }

    private static function superadminLink(string $type): string
    {
        return match ($type) {
            'incidents' => 'admin/superadmin/reports.php',
            'labour' => 'admin/superadmin/attendance.php',
            default => 'admin/superadmin/reports.php',
        };
    }

    private static function emptyStats(): array
    {
        return ['total' => 0, 'active' => 0, 'today' => 0, 'this_month' => 0, 'value' => 0, 'risk' => 0];
    }

    private static function required(array $input, string $key, int $limit = 0): string
    {
        $value = trim((string)($input[$key] ?? ''));
        if ($value === '') {
            throw new RuntimeException('Please complete all required fields.');
        }
        return $limit > 0 ? substr($value, 0, $limit) : $value;
    }

    private static function requiredDate(array $input, string $key): string
    {
        $value = trim((string)($input[$key] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new RuntimeException('Please enter a valid date.');
        }
        return $value;
    }

    private static function optional(array $input, string $key, int $limit = 0): ?string
    {
        $value = trim((string)($input[$key] ?? ''));
        if ($value === '') {
            return null;
        }
        return $limit > 0 ? substr($value, 0, $limit) : $value;
    }

    private static function dateOrNull(array $input, string $key): ?string
    {
        $value = trim((string)($input[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private static function choice(array $input, string $key, array $allowed, string $fallback): string
    {
        $value = trim((string)($input[$key] ?? $fallback));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function alias(string $type): string
    {
        return match ($type) {
            'documents' => 'd',
            'equipment' => 'e',
            'incidents' => 'h',
            'labour' => 'l',
            'materials' => 'm',
            'subcontractors' => 's',
            default => 'r',
        };
    }
}
