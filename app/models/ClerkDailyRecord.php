<?php

class ClerkDailyRecord
{
    public static function types(): array
    {
        return [
            'diary' => [
                'title' => 'Daily Site Diary',
                'label' => 'Site diary',
                'icon' => 'fa-book',
                'page' => 'admin/clerk/daily-diary.php',
                'api' => 'api/clerk/daily-diary-save.php',
                'date' => 'diary_date',
                'status' => 'status',
                'empty' => 'No diary records found.',
            ],
            'weather' => [
                'title' => 'Weather Log',
                'label' => 'Weather record',
                'icon' => 'fa-cloud-sun',
                'page' => 'admin/clerk/weather-log.php',
                'api' => 'api/clerk/weather-log-save.php',
                'date' => 'log_date',
                'status' => 'impact_level',
                'empty' => 'No weather records found.',
            ],
            'labour' => [
                'title' => 'Labour Verification',
                'label' => 'Labour check',
                'icon' => 'fa-users-gear',
                'page' => 'admin/clerk/labour-verification.php',
                'api' => 'api/clerk/labour-verification-save.php',
                'date' => 'diary_date',
                'status' => 'verification_status',
                'empty' => 'No labour records found.',
            ],
            'materials' => [
                'title' => 'Material Delivery Log',
                'label' => 'Delivery check',
                'icon' => 'fa-truck',
                'page' => 'admin/clerk/material-delivery-log.php',
                'api' => 'api/clerk/material-delivery-save.php',
                'date' => 'delivery_date',
                'status' => 'verification_status',
                'empty' => 'No material deliveries found.',
            ],
            'equipment' => [
                'title' => 'Equipment Check',
                'label' => 'Equipment check',
                'icon' => 'fa-screwdriver-wrench',
                'page' => 'admin/clerk/equipment-check.php',
                'api' => 'api/clerk/equipment-check-save.php',
                'date' => 'date_on_site',
                'status' => 'check_status',
                'empty' => 'No equipment records found.',
            ],
        ];
    }

    public static function config(string $type): array
    {
        $types = self::types();
        if (!isset($types[$type])) {
            throw new InvalidArgumentException('Unsupported clerk record type.');
        }
        return $types[$type];
    }

    public static function projects(int $userId): array
    {
        return ClerkAttendance::projects($userId);
    }

    public static function defaultProjectId(int $userId, int $requestedId = 0): int
    {
        return ClerkAttendance::defaultProjectId($userId, $requestedId);
    }

    public static function project(int $userId, int $projectId): ?array
    {
        return ClerkAttendance::project($userId, $projectId);
    }

    public static function stats(string $type, int $userId, int $projectId): array
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return ['total' => 0, 'today' => 0, 'open' => 0, 'risk' => 0, 'value' => 0];
        }

        return match ($type) {
            'diary' => self::diaryStats($projectId),
            'weather' => self::weatherStats($projectId),
            'labour' => self::labourStats($projectId),
            'materials' => self::materialStats($projectId),
            'equipment' => self::equipmentStats($projectId),
            default => ['total' => 0, 'today' => 0, 'open' => 0, 'risk' => 0, 'value' => 0],
        };
    }

    public static function list(string $type, int $userId, int $projectId, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return [];
        }

        return match ($type) {
            'diary' => self::diaries($projectId, $filters, $limit, $offset),
            'weather' => self::weather($projectId, $filters, $limit, $offset),
            'labour' => self::labour($projectId, $filters, $limit, $offset),
            'materials' => self::materials($projectId, $filters, $limit, $offset),
            'equipment' => self::equipment($projectId, $filters, $limit, $offset),
            default => [],
        };
    }

    public static function count(string $type, int $userId, int $projectId, array $filters = []): int
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return 0;
        }
        return match ($type) {
            'diary' => self::countDiaries($projectId, $filters),
            'weather' => self::countWeather($projectId, $filters),
            'labour' => self::countLabour($projectId, $filters),
            'materials' => self::countMaterials($projectId, $filters),
            'equipment' => self::countEquipment($projectId, $filters),
            default => 0,
        };
    }

    public static function save(string $type, int $userId, array $input): int
    {
        $projectId = Security::cleanInt($input['project_id'] ?? 0);
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            throw new RuntimeException('Choose an assigned project before saving.');
        }

        return match ($type) {
            'diary' => self::saveDiary($projectId, $userId, $input),
            'weather' => self::saveWeather($projectId, $userId, $input),
            'labour' => self::saveLabour($projectId, $userId, $input),
            'materials' => self::saveMaterial($projectId, $userId, $input),
            'equipment' => self::saveEquipment($projectId, $userId, $input),
            default => throw new InvalidArgumentException('Unsupported clerk record type.'),
        };
    }

    public static function formDefaults(string $type, int $projectId, string $date): array
    {
        return match ($type) {
            'diary' => Database::fetch('SELECT * FROM site_diaries WHERE project_id = ? AND diary_date = ? LIMIT 1', [$projectId, $date]) ?: [],
            'weather' => Database::fetch('SELECT * FROM weather_logs WHERE project_id = ? AND log_date = ? LIMIT 1', [$projectId, $date]) ?: [],
            'labour' => Database::fetch('SELECT * FROM labour_register WHERE project_id = ? AND diary_date = ? LIMIT 1', [$projectId, $date]) ?: [],
            default => [],
        };
    }

    public static function find(string $type, int $userId, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = match ($type) {
            'diary' => Database::fetch(
                "SELECT sd.*, p.name AS project_name FROM site_diaries sd JOIN projects p ON p.id = sd.project_id WHERE sd.id = ? LIMIT 1",
                [$id]
            ),
            'weather' => Database::fetch(
                "SELECT wl.*, p.name AS project_name FROM weather_logs wl JOIN projects p ON p.id = wl.project_id WHERE wl.id = ? LIMIT 1",
                [$id]
            ),
            'labour' => Database::fetch(
                "SELECT lr.*, p.name AS project_name FROM labour_register lr JOIN projects p ON p.id = lr.project_id WHERE lr.id = ? LIMIT 1",
                [$id]
            ),
            'materials' => Database::fetch(
                "SELECT md.*, p.name AS project_name FROM material_deliveries md JOIN projects p ON p.id = md.project_id WHERE md.id = ? LIMIT 1",
                [$id]
            ),
            'equipment' => Database::fetch(
                "SELECT er.*, p.name AS project_name FROM equipment_register er JOIN projects p ON p.id = er.project_id WHERE er.id = ? LIMIT 1",
                [$id]
            ),
            default => null,
        };
        if (!$row) {
            return null;
        }
        if (!ClerkAttendance::canAccessProject($userId, (int)$row['project_id'])) {
            return null;
        }
        return $row;
    }

    /** Deliveries still needing clerk verification (or all recent for re-check). */
    public static function materialOptions(int $userId, int $projectId, int $limit = 40): array
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return [];
        }
        return Database::fetchAll(
            "SELECT id, material, supplier, delivery_date, quantity, unit, delivery_note_no, verification_status, verified_quantity
             FROM material_deliveries
             WHERE project_id = ?
             ORDER BY
                CASE WHEN verification_status = 'pending' THEN 0 ELSE 1 END,
                delivery_date DESC, id DESC
             LIMIT " . max(1, min(100, $limit)),
            [$projectId]
        );
    }

    public static function equipmentOptions(int $userId, int $projectId, int $limit = 40): array
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return [];
        }
        return Database::fetchAll(
            "SELECT id, equipment_type, registration, owner, `condition`, status, check_status, date_on_site
             FROM equipment_register
             WHERE project_id = ?
             ORDER BY
                CASE WHEN check_status = 'pending' THEN 0 ELSE 1 END,
                COALESCE(checked_at, created_at) DESC, id DESC
             LIMIT " . max(1, min(100, $limit)),
            [$projectId]
        );
    }

    /** Site hub peers spanning daily + quality evidence pages. */
    public static function siteHubPeers(): array
    {
        return [
            'diary' => ['title' => 'Diary', 'icon' => 'fa-book', 'page' => 'admin/clerk/daily-diary.php'],
            'weather' => ['title' => 'Weather', 'icon' => 'fa-cloud-sun', 'page' => 'admin/clerk/weather-log.php'],
            'labour' => ['title' => 'Labour', 'icon' => 'fa-users-gear', 'page' => 'admin/clerk/labour-verification.php'],
            'materials' => ['title' => 'Materials', 'icon' => 'fa-truck', 'page' => 'admin/clerk/material-delivery-log.php'],
            'equipment' => ['title' => 'Equipment', 'icon' => 'fa-screwdriver-wrench', 'page' => 'admin/clerk/equipment-check.php'],
            'document' => ['title' => 'Documents', 'icon' => 'fa-folder-open', 'page' => 'admin/clerk/documents.php'],
            'hs' => ['title' => 'H&S', 'icon' => 'fa-triangle-exclamation', 'page' => 'admin/clerk/hs-incidents.php'],
            'meeting' => ['title' => 'Meetings', 'icon' => 'fa-people-group', 'page' => 'admin/clerk/site-meeting-minutes.php'],
            'messages' => ['title' => 'Messages', 'icon' => 'fa-comments', 'page' => 'admin/clerk/messages.php'],
        ];
    }

    private static function saveDiary(int $projectId, int $userId, array $input): int
    {
        $date = self::date($input, 'diary_date', date('Y-m-d'));
        $data = [
            'project_id' => $projectId,
            'diary_date' => $date,
            'report_title' => self::text($input, 'report_title', 180) ?: 'Daily site diary',
            'weather_summary' => self::text($input, 'weather_summary', 180),
            'work_done' => self::required($input, 'work_done'),
            'issues_raised' => self::text($input, 'issues_raised'),
            'safety_observations' => self::text($input, 'safety_observations'),
            'visitors_instructions' => self::text($input, 'visitors_instructions'),
            'next_day_plan' => self::text($input, 'next_day_plan'),
            'recorded_by' => $userId,
            'status' => self::choice($input, 'status', ['draft', 'submitted', 'reviewed'], 'submitted'),
            'updated_by' => $userId,
        ];

        $existing = Database::fetch('SELECT id FROM site_diaries WHERE project_id = ? AND diary_date = ? LIMIT 1', [$projectId, $date]);
        $id = $existing ? (int)$existing['id'] : self::insert('site_diaries', $data);
        if ($existing) {
            self::update('site_diaries', $id, $data);
        }
        self::notify('Daily site diary', $projectId, 'admin/manager/reports.php');
        return $id;
    }

    private static function saveWeather(int $projectId, int $userId, array $input): int
    {
        $date = self::date($input, 'log_date', date('Y-m-d'));
        $data = [
            'project_id' => $projectId,
            'log_date' => $date,
            'morning_condition' => self::choice($input, 'morning_condition', self::weatherConditions(), 'clear'),
            'afternoon_condition' => self::choice($input, 'afternoon_condition', self::weatherConditions(), 'clear'),
            'rainfall_mm' => max(0, Security::cleanFloat($input['rainfall_mm'] ?? 0)),
            'temperature_min' => self::nullableFloat($input['temperature_min'] ?? null),
            'temperature_max' => self::nullableFloat($input['temperature_max'] ?? null),
            'working_hours' => max(0, Security::cleanFloat($input['working_hours'] ?? 8)),
            'working_hours_lost' => max(0, Security::cleanFloat($input['working_hours_lost'] ?? 0)),
            'impact_level' => self::choice($input, 'impact_level', ['none', 'minor', 'moderate', 'severe'], 'none'),
            'remarks' => self::text($input, 'remarks'),
            'recorded_by' => $userId,
            'updated_by' => $userId,
        ];

        $existing = Database::fetch('SELECT id FROM weather_logs WHERE project_id = ? AND log_date = ? LIMIT 1', [$projectId, $date]);
        $id = $existing ? (int)$existing['id'] : self::insert('weather_logs', $data);
        if ($existing) {
            self::update('weather_logs', $id, $data);
        }
        self::notify('Weather log', $projectId, 'admin/manager/reports.php');
        return $id;
    }

    private static function saveLabour(int $projectId, int $userId, array $input): int
    {
        $id = Security::cleanInt($input['id'] ?? 0);
        $date = self::date($input, 'diary_date', date('Y-m-d'));
        $skilled = max(0, Security::cleanInt($input['clerk_skilled_count'] ?? 0));
        $unskilled = max(0, Security::cleanInt($input['clerk_unskilled_count'] ?? 0));
        $supervisors = max(0, Security::cleanInt($input['clerk_supervisor_count'] ?? 0));
        $total = $skilled + $unskilled + $supervisors;

        $existing = null;
        if ($id > 0) {
            $existing = Database::fetch('SELECT * FROM labour_register WHERE id = ? AND project_id = ? LIMIT 1', [$id, $projectId]);
            if (!$existing) {
                throw new RuntimeException('Labour record could not be found for update.');
            }
            $date = (string)($existing['diary_date'] ?? $date);
        } else {
            $existing = Database::fetch('SELECT * FROM labour_register WHERE project_id = ? AND diary_date = ? LIMIT 1', [$projectId, $date]);
        }

        $contractorTotal = (int)($existing['total'] ?? 0);
        $hasContractor = $existing && ((int)($existing['total'] ?? 0) > 0 || (int)($existing['skilled_count'] ?? 0) > 0 || (int)($existing['unskilled_count'] ?? 0) > 0);
        $defaultStatus = $hasContractor
            ? ($total === $contractorTotal ? 'verified' : 'queried')
            : 'verified';
        $notes = self::text($input, 'verification_notes');
        if (!$hasContractor && ($notes === null || $notes === '')) {
            $notes = 'Clerk-only labour count (no contractor submission for this date).';
        }

        $data = [
            'project_id' => $projectId,
            'diary_date' => $date,
            'skilled_count' => (int)($existing['skilled_count'] ?? 0),
            'unskilled_count' => (int)($existing['unskilled_count'] ?? 0),
            'supervisor_count' => (int)($existing['supervisor_count'] ?? 0),
            'total' => $contractorTotal,
            'recorded_by' => (int)($existing['recorded_by'] ?? $userId),
            'clerk_skilled_count' => $skilled,
            'clerk_unskilled_count' => $unskilled,
            'clerk_supervisor_count' => $supervisors,
            'clerk_total' => $total,
            'variance_total' => $total - $contractorTotal,
            'status' => 'reviewed',
            'verification_status' => self::choice($input, 'verification_status', ['pending', 'verified', 'queried'], $defaultStatus),
            'verified_by' => $userId,
            'verified_at' => date('Y-m-d H:i:s'),
            'verification_notes' => $notes,
            'updated_by' => $userId,
        ];

        if ($existing) {
            $rowId = (int)$existing['id'];
            self::update('labour_register', $rowId, $data);
            self::notify('Labour verification', $projectId, 'admin/manager/attendance-summary.php');
            return $rowId;
        }
        $newId = self::insert('labour_register', $data);
        self::notify('Labour verification', $projectId, 'admin/manager/attendance-summary.php');
        return $newId;
    }

    private static function saveMaterial(int $projectId, int $userId, array $input): int
    {
        $id = Security::cleanInt($input['id'] ?? 0);
        $existing = $id > 0
            ? Database::fetch('SELECT * FROM material_deliveries WHERE id = ? AND project_id = ? LIMIT 1', [$id, $projectId])
            : null;

        if ($id > 0 && !$existing) {
            throw new RuntimeException('Material delivery could not be found for update.');
        }

        $verification = self::choice($input, 'verification_status', ['pending', 'accepted', 'queried', 'rejected'], 'accepted');
        $workflowStatus = match ($verification) {
            'accepted' => 'accepted',
            'queried', 'rejected' => 'queried',
            default => 'submitted',
        };

        if (!$existing) {
            $payload = [
                'project_id' => $projectId,
                'material' => self::required($input, 'material', 150),
                'supplier' => self::text($input, 'supplier', 150),
                'delivery_date' => self::date($input, 'delivery_date', date('Y-m-d')),
                'quantity' => max(0, Security::cleanFloat($input['quantity'] ?? 0)),
                'unit' => self::required($input, 'unit', 30),
                'delivery_note_no' => self::text($input, 'delivery_note_no', 60),
                'received_by' => $userId,
                'condition' => self::choice($input, 'condition', ['good', 'damaged', 'short-delivered', 'pending-check'], 'good'),
                'approved' => $verification === 'accepted' ? 1 : 0,
                'status' => $workflowStatus,
                'verified_quantity' => max(0, Security::cleanFloat($input['verified_quantity'] ?? ($input['quantity'] ?? 0))),
                'verification_status' => $verification,
                'verified_by' => $userId,
                'verified_at' => date('Y-m-d H:i:s'),
                'verification_notes' => self::text($input, 'verification_notes'),
                'updated_by' => $userId,
            ];
            $newId = self::insert('material_deliveries', $payload);
            self::notify('Material delivery verification', $projectId, 'admin/manager/reports.php');
            return $newId;
        }

        // Full update when editing known delivery.
        $verifiedQty = max(0, Security::cleanFloat($input['verified_quantity'] ?? ($input['quantity'] ?? $existing['quantity'] ?? 0)));
        $quantity = array_key_exists('quantity', $input)
            ? max(0, Security::cleanFloat($input['quantity']))
            : (float)($existing['quantity'] ?? 0);
        self::update('material_deliveries', $id, [
            'material' => self::text($input, 'material', 150) ?: (string)($existing['material'] ?? ''),
            'supplier' => self::text($input, 'supplier', 150) ?? ($existing['supplier'] ?? null),
            'delivery_date' => self::date($input, 'delivery_date', (string)($existing['delivery_date'] ?? date('Y-m-d'))),
            'quantity' => $quantity,
            'unit' => self::text($input, 'unit', 30) ?: (string)($existing['unit'] ?? ''),
            'delivery_note_no' => self::text($input, 'delivery_note_no', 60) ?? ($existing['delivery_note_no'] ?? null),
            'condition' => self::choice($input, 'condition', ['good', 'damaged', 'short-delivered', 'pending-check'], (string)($existing['condition'] ?? 'good')),
            'verified_quantity' => $verifiedQty,
            'verification_status' => $verification,
            'verified_by' => $userId,
            'verified_at' => date('Y-m-d H:i:s'),
            'verification_notes' => self::text($input, 'verification_notes'),
            'status' => $workflowStatus,
            'approved' => $verification === 'accepted' ? 1 : 0,
            'updated_by' => $userId,
        ]);
        self::notify('Material delivery verification', $projectId, 'admin/manager/reports.php');
        return $id;
    }

    private static function saveEquipment(int $projectId, int $userId, array $input): int
    {
        $id = Security::cleanInt($input['id'] ?? 0);
        $existing = $id > 0
            ? Database::fetch('SELECT * FROM equipment_register WHERE id = ? AND project_id = ? LIMIT 1', [$id, $projectId])
            : null;

        if ($id > 0 && !$existing) {
            throw new RuntimeException('Equipment record could not be found for update.');
        }

        $checkStatus = self::choice($input, 'check_status', ['pending', 'present', 'missing', 'off-site', 'maintenance', 'queried'], 'present');
        $condition = self::choice($input, 'condition', ['good', 'serviceable', 'maintenance', 'poor'], (string)($existing['condition'] ?? 'good'));
        $siteStatus = self::choice($input, 'status', ['on-site', 'off-site', 'maintenance'], (string)($existing['status'] ?? 'on-site'));

        if (!$existing) {
            $payload = [
                'project_id' => $projectId,
                'equipment_type' => self::required($input, 'equipment_type', 120),
                'registration' => self::text($input, 'registration', 60),
                'owner' => self::text($input, 'owner', 150),
                'date_on_site' => self::dateOrNull($input, 'date_on_site') ?: date('Y-m-d'),
                'date_off_site' => self::dateOrNull($input, 'date_off_site'),
                'condition' => $condition,
                'status' => $siteStatus,
                'check_status' => $checkStatus,
                'checked_by' => $userId,
                'checked_at' => date('Y-m-d H:i:s'),
                'check_notes' => self::text($input, 'check_notes'),
                'updated_by' => $userId,
            ];
            $newId = self::insert('equipment_register', $payload);
            self::notify('Equipment check', $projectId, 'admin/manager/reports.php');
            return $newId;
        }

        self::update('equipment_register', $id, [
            'equipment_type' => self::text($input, 'equipment_type', 120) ?: (string)($existing['equipment_type'] ?? ''),
            'registration' => self::text($input, 'registration', 60) ?? ($existing['registration'] ?? null),
            'owner' => self::text($input, 'owner', 150) ?? ($existing['owner'] ?? null),
            'date_on_site' => self::dateOrNull($input, 'date_on_site') ?? ($existing['date_on_site'] ?? null),
            'date_off_site' => self::dateOrNull($input, 'date_off_site'),
            'condition' => $condition,
            'status' => $siteStatus,
            'check_status' => $checkStatus,
            'checked_by' => $userId,
            'checked_at' => date('Y-m-d H:i:s'),
            'check_notes' => self::text($input, 'check_notes'),
            'updated_by' => $userId,
        ]);
        self::notify('Equipment check', $projectId, 'admin/manager/reports.php');
        return $id;
    }

    private static function diaries(int $projectId, array $filters, int $limit, int $offset = 0): array
    {
        return self::queryRecords(
            "SELECT sd.*, p.name AS project_name, CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name
             FROM site_diaries sd JOIN projects p ON p.id = sd.project_id LEFT JOIN users u ON u.id = sd.recorded_by",
            'sd', 'diary_date', 'status',
            ['sd.report_title', 'sd.work_done', 'sd.issues_raised', 'sd.next_day_plan'],
            $projectId, $filters, $limit, $offset
        );
    }

    private static function weather(int $projectId, array $filters, int $limit, int $offset = 0): array
    {
        return self::queryRecords(
            "SELECT wl.*, p.name AS project_name, CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name
             FROM weather_logs wl JOIN projects p ON p.id = wl.project_id LEFT JOIN users u ON u.id = wl.recorded_by",
            'wl', 'log_date', 'impact_level',
            ['wl.morning_condition', 'wl.afternoon_condition', 'wl.remarks', 'wl.impact_level'],
            $projectId, $filters, $limit, $offset
        );
    }

    private static function labour(int $projectId, array $filters, int $limit, int $offset = 0): array
    {
        return self::queryRecords(
            "SELECT lr.*, p.name AS project_name, CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name, CONCAT(v.first_name, ' ', v.last_name) AS verified_by_name
             FROM labour_register lr JOIN projects p ON p.id = lr.project_id LEFT JOIN users u ON u.id = lr.recorded_by LEFT JOIN users v ON v.id = lr.verified_by",
            'lr', 'diary_date', 'verification_status',
            ['lr.verification_status', 'lr.verification_notes'],
            $projectId, $filters, $limit, $offset
        );
    }

    private static function materials(int $projectId, array $filters, int $limit, int $offset = 0): array
    {
        return self::queryRecords(
            "SELECT md.*, p.name AS project_name, CONCAT(u.first_name, ' ', u.last_name) AS received_by_name, CONCAT(v.first_name, ' ', v.last_name) AS verified_by_name
             FROM material_deliveries md JOIN projects p ON p.id = md.project_id LEFT JOIN users u ON u.id = md.received_by LEFT JOIN users v ON v.id = md.verified_by",
            'md', 'delivery_date', 'verification_status',
            ['md.material', 'md.supplier', 'md.delivery_note_no', 'md.verification_notes'],
            $projectId, $filters, $limit, $offset
        );
    }

    private static function equipment(int $projectId, array $filters, int $limit, int $offset = 0): array
    {
        [$where, $bindings] = self::filterParts('er', 'date_on_site', 'check_status', ['er.equipment_type', 'er.registration', 'er.owner', 'er.check_notes'], $projectId, $filters);
        return Database::fetchAll(
            "SELECT er.*, p.name AS project_name, CONCAT(u.first_name, ' ', u.last_name) AS checked_by_name
             FROM equipment_register er
             JOIN projects p ON p.id = er.project_id
             LEFT JOIN users u ON u.id = er.checked_by
             WHERE " . implode(' AND ', $where) . '
             ORDER BY COALESCE(er.checked_at, er.date_on_site, er.created_at) DESC, er.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    private static function countDiaries(int $projectId, array $filters): int
    {
        return self::countRecords('site_diaries', 'sd', 'diary_date', 'status', ['sd.report_title', 'sd.work_done', 'sd.issues_raised', 'sd.next_day_plan'], $projectId, $filters);
    }

    private static function countWeather(int $projectId, array $filters): int
    {
        return self::countRecords('weather_logs', 'wl', 'log_date', 'impact_level', ['wl.morning_condition', 'wl.afternoon_condition', 'wl.remarks'], $projectId, $filters);
    }

    private static function countLabour(int $projectId, array $filters): int
    {
        return self::countRecords('labour_register', 'lr', 'diary_date', 'verification_status', ['lr.verification_status', 'lr.verification_notes'], $projectId, $filters);
    }

    private static function countMaterials(int $projectId, array $filters): int
    {
        return self::countRecords('material_deliveries', 'md', 'delivery_date', 'verification_status', ['md.material', 'md.supplier', 'md.delivery_note_no'], $projectId, $filters);
    }

    private static function countEquipment(int $projectId, array $filters): int
    {
        return self::countRecords('equipment_register', 'er', 'date_on_site', 'check_status', ['er.equipment_type', 'er.registration', 'er.owner'], $projectId, $filters);
    }

    private static function filterParts(string $alias, string $dateColumn, string $statusColumn, array $searchColumns, int $projectId, array $filters): array
    {
        $where = ["{$alias}.project_id = ?"];
        $bindings = [$projectId];
        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = "{$alias}.{$statusColumn} = ?";
            $bindings[] = $status;
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(' . implode(' OR ', array_map(static fn ($column): string => "{$column} LIKE ?", $searchColumns)) . ')';
            foreach ($searchColumns as $_) {
                $bindings[] = '%' . $q . '%';
            }
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $op) {
            $date = trim((string)($filters[$key] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where[] = "DATE({$alias}.{$dateColumn}) {$op} ?";
                $bindings[] = $date;
            }
        }
        return [$where, $bindings];
    }

    private static function queryRecords(string $select, string $alias, string $dateColumn, string $statusColumn, array $searchColumns, int $projectId, array $filters, int $limit, int $offset = 0): array
    {
        [$where, $bindings] = self::filterParts($alias, $dateColumn, $statusColumn, $searchColumns, $projectId, $filters);
        return Database::fetchAll(
            $select . ' WHERE ' . implode(' AND ', $where)
            . " ORDER BY {$alias}.{$dateColumn} DESC, {$alias}.id DESC"
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    private static function countRecords(string $table, string $alias, string $dateColumn, string $statusColumn, array $searchColumns, int $projectId, array $filters): int
    {
        [$where, $bindings] = self::filterParts($alias, $dateColumn, $statusColumn, $searchColumns, $projectId, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total FROM `{$table}` {$alias} WHERE " . implode(' AND ', $where),
            $bindings
        );
        return (int)($row['total'] ?? 0);
    }

    private static function diaryStats(int $projectId): array
    {
        $row = Database::fetch("SELECT COUNT(*) total, SUM(DATE(diary_date)=CURDATE()) today, SUM(status='draft') open, SUM(NULLIF(issues_raised,'') IS NOT NULL) risk FROM site_diaries WHERE project_id = ?", [$projectId]) ?: [];
        return self::statsRow($row);
    }

    private static function weatherStats(int $projectId): array
    {
        $row = Database::fetch("SELECT COUNT(*) total, SUM(DATE(log_date)=CURDATE()) today, SUM(impact_level IN ('moderate','severe')) risk, COALESCE(SUM(working_hours_lost),0) value FROM weather_logs WHERE project_id = ?", [$projectId]) ?: [];
        return self::statsRow($row);
    }

    private static function labourStats(int $projectId): array
    {
        $row = Database::fetch("SELECT COUNT(*) total, SUM(DATE(diary_date)=CURDATE()) today, SUM(verification_status='pending') open, SUM(verification_status='queried' OR variance_total <> 0) risk, COALESCE(SUM(clerk_total),0) value FROM labour_register WHERE project_id = ?", [$projectId]) ?: [];
        return self::statsRow($row);
    }

    private static function materialStats(int $projectId): array
    {
        $row = Database::fetch("SELECT COUNT(*) total, SUM(DATE(delivery_date)=CURDATE()) today, SUM(verification_status='pending') open, SUM(verification_status IN ('queried','rejected')) risk, COALESCE(SUM(quantity),0) value FROM material_deliveries WHERE project_id = ?", [$projectId]) ?: [];
        return self::statsRow($row);
    }

    private static function equipmentStats(int $projectId): array
    {
        $row = Database::fetch("SELECT COUNT(*) total, SUM(DATE(COALESCE(checked_at, created_at))=CURDATE()) today, SUM(check_status='pending') open, SUM(check_status IN ('missing','queried') OR `condition` IN ('poor','maintenance')) risk, SUM(status='on-site') value FROM equipment_register WHERE project_id = ?", [$projectId]) ?: [];
        return self::statsRow($row);
    }

    private static function statsRow(array $row): array
    {
        return [
            'total' => (int)($row['total'] ?? 0),
            'today' => (int)($row['today'] ?? 0),
            'open' => (int)($row['open'] ?? 0),
            'risk' => (int)($row['risk'] ?? 0),
            'value' => (float)($row['value'] ?? 0),
        ];
    }

    private static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $safe = array_map(static fn ($column): string => '`' . str_replace('`', '', $column) . '`', $columns);
        Database::query('INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $safe) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
        return (int)Database::lastInsertId();
    }

    private static function update(string $table, int $id, array $data): void
    {
        $columns = array_keys($data);
        $assignments = array_map(static fn ($column): string => '`' . str_replace('`', '', $column) . '` = ?', $columns);
        $bindings = array_values($data);
        $bindings[] = $id;
        Database::query('UPDATE `' . str_replace('`', '', $table) . '` SET ' . implode(', ', $assignments) . ' WHERE id = ?', $bindings);
    }

    private static function notify(string $title, int $projectId, string $link): void
    {
        try {
            $project = Project::findDetailed($projectId);
            $message = $title . ' updated for ' . ($project['name'] ?? 'a project') . '.';
            Notification::pushRole('manager', 'clerk_record', $title . ' updated', $message, $link);
            Notification::pushRole('superadmin', 'clerk_record', $title . ' updated', $message, 'admin/superadmin/reports.php');
        } catch (Throwable) {
        }
    }

    private static function required(array $input, string $key, int $limit = 0): string
    {
        $value = self::text($input, $key, $limit);
        if ($value === null || $value === '') {
            throw new RuntimeException('Please complete all required fields.');
        }
        return $value;
    }

    private static function text(array $input, string $key, int $limit = 0): ?string
    {
        $value = trim((string)($input[$key] ?? ''));
        if ($value === '') {
            return null;
        }
        return $limit > 0 ? substr($value, 0, $limit) : $value;
    }

    private static function date(array $input, string $key, string $fallback): string
    {
        $value = trim((string)($input[$key] ?? $fallback));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
    }

    private static function dateOrNull(array $input, string $key): ?string
    {
        $value = trim((string)($input[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        $value = trim((string)($value ?? ''));
        return $value === '' ? null : Security::cleanFloat($value);
    }

    private static function choice(array $input, string $key, array $allowed, string $fallback): string
    {
        $value = trim((string)($input[$key] ?? $fallback));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function weatherConditions(): array
    {
        return ['clear', 'cloudy', 'light-rain', 'heavy-rain', 'windy', 'hot', 'cold', 'storm'];
    }
}
