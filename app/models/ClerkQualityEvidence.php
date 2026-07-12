<?php

class ClerkQualityEvidence
{
    public static function types(): array
    {
        return [
            'quality' => ['title' => 'Quality Tests', 'label' => 'Quality test', 'icon' => 'fa-vial-circle-check', 'page' => 'admin/clerk/quality-tests.php', 'api' => 'api/clerk/quality-test-save.php', 'date' => 'test_date', 'status' => 'verification_status', 'empty' => 'No quality tests found.'],
            'itp' => ['title' => 'Inspection Test Plans', 'label' => 'Inspection', 'icon' => 'fa-clipboard-check', 'page' => 'admin/clerk/inspection-test-plans.php', 'api' => 'api/clerk/itp-save.php', 'date' => 'inspection_date', 'status' => 'inspection_status', 'empty' => 'No inspection records found.'],
            'hs' => ['title' => 'Health & Safety Incidents', 'label' => 'Incident', 'icon' => 'fa-triangle-exclamation', 'page' => 'admin/clerk/hs-incidents.php', 'api' => 'api/clerk/hs-incident-save.php', 'date' => 'incident_date', 'status' => 'status', 'empty' => 'No incident records found.'],
            'ncr' => ['title' => 'Non-Conformance', 'label' => 'Non-conformance', 'icon' => 'fa-circle-exclamation', 'page' => 'admin/clerk/non-conformance.php', 'api' => 'api/clerk/ncr-save.php', 'date' => 'raised_date', 'status' => 'status', 'empty' => 'No non-conformance records found.'],
            'defect' => ['title' => 'Defects Register', 'label' => 'Defect', 'icon' => 'fa-magnifying-glass', 'page' => 'admin/clerk/defects.php', 'api' => 'api/clerk/defect-save.php', 'date' => 'raised_date', 'status' => 'status', 'empty' => 'No defects found.'],
            'meeting' => ['title' => 'Site Meeting Minutes', 'label' => 'Meeting', 'icon' => 'fa-people-group', 'page' => 'admin/clerk/site-meeting-minutes.php', 'api' => 'api/clerk/site-meeting-save.php', 'date' => 'meeting_date', 'status' => 'status', 'empty' => 'No meeting minutes found.'],
            'document' => ['title' => 'Site Documents', 'label' => 'Document', 'icon' => 'fa-folder-open', 'page' => 'admin/clerk/documents.php', 'api' => 'api/clerk/document-save.php', 'date' => 'site_record_date', 'status' => 'consultant_review_status', 'empty' => 'No documents found.'],
            'photo' => ['title' => 'Site Photos', 'label' => 'Photo evidence', 'icon' => 'fa-camera', 'page' => 'admin/clerk/photos.php', 'api' => 'api/clerk/photo-save.php', 'date' => 'site_record_date', 'status' => 'consultant_review_status', 'empty' => 'No photos found.'],
            'ipc' => ['title' => 'IPC Verification', 'label' => 'IPC check', 'icon' => 'fa-file-invoice-dollar', 'page' => 'admin/clerk/ipc-verify.php', 'api' => 'api/clerk/ipc-verify.php', 'date' => 'submitted_at', 'status' => 'status', 'empty' => 'No IPCs found.'],
        ];
    }

    /** Peer nav order for quality & evidence centre. */
    public static function peerKeys(): array
    {
        return ['quality', 'itp', 'ncr', 'defect', 'photo', 'document', 'hs', 'meeting', 'ipc'];
    }

    public static function config(string $type): array
    {
        $types = self::types();
        if (!isset($types[$type])) {
            throw new InvalidArgumentException('Unsupported clerk evidence page.');
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
            return self::emptyStats();
        }

        return match ($type) {
            'quality' => self::statsFor('quality_tests', 'test_date', $projectId, [
                'open' => "verification_status = 'pending'",
                'risk' => "verification_status IN ('failed','queried')",
                'value' => "verification_status = 'passed'",
            ]),
            'itp' => self::statsFor('inspection_test_plans', 'inspection_date', $projectId, [
                'open' => "inspection_status = 'pending'",
                'risk' => "inspection_status IN ('failed','rework-required')",
                'value' => "inspection_status = 'passed'",
            ]),
            'hs' => self::statsFor('hs_incidents', 'incident_date', $projectId, [
                'open' => "status NOT IN ('resolved','closed')",
                'risk' => "severity IN ('high','critical')",
                'value' => 'lost_time_hours > 0',
            ]),
            'ncr' => self::statsFor('non_conformance_reports', 'raised_date', $projectId, [
                'open' => "status <> 'closed'",
                'risk' => "severity IN ('major','critical')",
                'value' => "status = 'closed'",
            ]),
            'defect' => self::statsFor('defects', 'raised_date', $projectId, [
                'open' => "status IN ('open','in-progress')",
                'risk' => "severity IN ('major','critical')",
                'value' => "status IN ('resolved','closed')",
            ]),
            'meeting' => self::statsFor('site_meeting_minutes', 'meeting_date', $projectId, [
                'open' => "action_status IN ('open','in-progress','overdue')",
                'risk' => "action_status = 'overdue'",
                'value' => "status IN ('recorded','reviewed','closed')",
            ]),
            'document', 'photo' => self::documentStats($type, $projectId),
            'ipc' => self::ipcStats($projectId),
            default => self::emptyStats(),
        };
    }

    public static function list(string $type, int $userId, int $projectId, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        return match ($type) {
            'quality' => self::query(
                "SELECT qt.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name
                 FROM quality_tests qt
                 JOIN projects p ON p.id = qt.project_id
                 LEFT JOIN users u ON u.id = qt.tested_by",
                'qt', 'test_date', 'verification_status',
                ['qt.test_type', 'qt.location_on_site', 'qt.result', 'qt.required_result', 'qt.actual_result', 'qt.lab_ref', 'qt.clerk_observation'],
                $projectId, $filters, $limit, $offset
            ),
            'itp' => self::query(
                "SELECT itp.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name
                 FROM inspection_test_plans itp
                 JOIN projects p ON p.id = itp.project_id
                 LEFT JOIN users u ON u.id = itp.inspected_by",
                'itp', 'inspection_date', 'inspection_status',
                ['itp.activity', 'itp.hold_point', 'itp.inspection_area', 'itp.outcome', 'itp.clerk_notes'],
                $projectId, $filters, $limit, $offset
            ),
            'hs' => self::query(
                "SELECT hi.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name
                 FROM hs_incidents hi
                 JOIN projects p ON p.id = hi.project_id
                 LEFT JOIN users u ON u.id = hi.reported_by",
                'hi', 'incident_date', 'status',
                ['hi.incident_type', 'hi.description', 'hi.persons_involved', 'hi.cause', 'hi.corrective_action'],
                $projectId, $filters, $limit, $offset
            ),
            'ncr' => self::query(
                "SELECT ncr.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name
                 FROM non_conformance_reports ncr
                 JOIN projects p ON p.id = ncr.project_id
                 LEFT JOIN users u ON u.id = ncr.raised_by",
                'ncr', 'raised_date', 'status',
                ['ncr.ncr_reference', 'ncr.description', 'ncr.location_on_site', 'ncr.root_cause', 'ncr.corrective_action'],
                $projectId, $filters, $limit, $offset
            ),
            'defect' => self::query(
                "SELECT d.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name
                 FROM defects d
                 JOIN projects p ON p.id = d.project_id
                 LEFT JOIN users u ON u.id = d.raised_by",
                'd', 'raised_date', 'status',
                ['d.defect_reference', 'd.location', 'd.description', 'd.rectification_notes'],
                $projectId, $filters, $limit, $offset
            ),
            'meeting' => self::query(
                "SELECT smm.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name
                 FROM site_meeting_minutes smm
                 JOIN projects p ON p.id = smm.project_id
                 LEFT JOIN users u ON u.id = smm.recorded_by",
                'smm', 'meeting_date', 'status',
                ['smm.meeting_type', 'smm.venue', 'smm.agenda', 'smm.minutes_text', 'smm.chairperson'],
                $projectId, $filters, $limit, $offset
            ),
            'document' => self::documents($projectId, $filters, $limit, $offset, false),
            'photo' => self::documents($projectId, $filters, $limit, $offset, true),
            'ipc' => self::ipcs($projectId, $filters, $limit, $offset),
            default => [],
        };
    }

    public static function count(string $type, int $userId, int $projectId, array $filters = []): int
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return 0;
        }

        return match ($type) {
            'quality' => self::countQuery('quality_tests', 'qt', 'test_date', 'verification_status',
                ['qt.test_type', 'qt.location_on_site', 'qt.result', 'qt.required_result', 'qt.actual_result', 'qt.lab_ref', 'qt.clerk_observation'],
                $projectId, $filters),
            'itp' => self::countQuery('inspection_test_plans', 'itp', 'inspection_date', 'inspection_status',
                ['itp.activity', 'itp.hold_point', 'itp.inspection_area', 'itp.outcome', 'itp.clerk_notes'],
                $projectId, $filters),
            'hs' => self::countQuery('hs_incidents', 'hi', 'incident_date', 'status',
                ['hi.incident_type', 'hi.description', 'hi.persons_involved', 'hi.cause', 'hi.corrective_action'],
                $projectId, $filters),
            'ncr' => self::countQuery('non_conformance_reports', 'ncr', 'raised_date', 'status',
                ['ncr.ncr_reference', 'ncr.description', 'ncr.location_on_site', 'ncr.root_cause', 'ncr.corrective_action'],
                $projectId, $filters),
            'defect' => self::countQuery('defects', 'd', 'raised_date', 'status',
                ['d.defect_reference', 'd.location', 'd.description', 'd.rectification_notes'],
                $projectId, $filters),
            'meeting' => self::countQuery('site_meeting_minutes', 'smm', 'meeting_date', 'status',
                ['smm.meeting_type', 'smm.venue', 'smm.agenda', 'smm.minutes_text', 'smm.chairperson'],
                $projectId, $filters),
            'document' => self::countDocuments($projectId, $filters, false),
            'photo' => self::countDocuments($projectId, $filters, true),
            'ipc' => self::countIpcs($projectId, $filters),
            default => 0,
        };
    }

    /** IPCs waiting for clerk verification on a project. */
    public static function pendingIpcs(int $userId, int $projectId): array
    {
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            return [];
        }
        return Database::fetchAll(
            "SELECT i.id, i.ipc_number, i.net_amount, i.gross_amount, i.period_from, i.period_to, i.status, i.submitted_at,
                    p.name AS project_name,
                    (SELECT COUNT(*) FROM ipc_lines il WHERE il.ipc_id = i.id) AS line_count
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             WHERE i.project_id = ? AND i.status = 'submitted'
             ORDER BY i.submitted_at ASC, i.id ASC",
            [$projectId]
        );
    }

    public static function find(string $type, int $userId, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = match ($type) {
            'quality' => Database::fetch(
                "SELECT qt.*, p.name project_name FROM quality_tests qt JOIN projects p ON p.id = qt.project_id WHERE qt.id = ? LIMIT 1",
                [$id]
            ),
            'itp' => Database::fetch(
                "SELECT itp.*, p.name project_name FROM inspection_test_plans itp JOIN projects p ON p.id = itp.project_id WHERE itp.id = ? LIMIT 1",
                [$id]
            ),
            'hs' => Database::fetch(
                "SELECT hi.*, p.name project_name FROM hs_incidents hi JOIN projects p ON p.id = hi.project_id WHERE hi.id = ? LIMIT 1",
                [$id]
            ),
            'ncr' => Database::fetch(
                "SELECT ncr.*, p.name project_name FROM non_conformance_reports ncr JOIN projects p ON p.id = ncr.project_id WHERE ncr.id = ? LIMIT 1",
                [$id]
            ),
            'defect' => Database::fetch(
                "SELECT d.*, p.name project_name FROM defects d JOIN projects p ON p.id = d.project_id WHERE d.id = ? LIMIT 1",
                [$id]
            ),
            'meeting' => Database::fetch(
                "SELECT smm.*, p.name project_name FROM site_meeting_minutes smm JOIN projects p ON p.id = smm.project_id WHERE smm.id = ? LIMIT 1",
                [$id]
            ),
            'document', 'photo' => Database::fetch(
                "SELECT d.*, p.name project_name FROM documents d JOIN projects p ON p.id = d.project_id WHERE d.id = ? LIMIT 1",
                [$id]
            ),
            'ipc' => Database::fetch(
                "SELECT i.*, p.name project_name,
                        (SELECT COUNT(*) FROM ipc_lines il WHERE il.ipc_id = i.id) AS line_count
                 FROM ipcs i JOIN projects p ON p.id = i.project_id WHERE i.id = ? LIMIT 1",
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
        if ($type === 'photo' && (string)($row['clerk_document_type'] ?? '') !== 'photo') {
            return null;
        }
        if ($type === 'document' && (string)($row['clerk_document_type'] ?? '') === 'photo') {
            return null;
        }
        return $row;
    }

    public static function save(string $type, int $userId, array $input): int
    {
        if ($type === 'ipc') {
            return self::verifyIpc($userId, $input);
        }
        $projectId = Security::cleanInt($input['project_id'] ?? 0);
        if ($projectId <= 0 || !ClerkAttendance::canAccessProject($userId, $projectId)) {
            throw new RuntimeException('Choose an assigned project before saving.');
        }

        $id = Security::cleanInt($input['id'] ?? 0);
        if ($id > 0) {
            $existing = self::find($type, $userId, $id);
            if (!$existing) {
                throw new RuntimeException('Record could not be found for update.');
            }
            if ((int)$existing['project_id'] !== $projectId) {
                throw new RuntimeException('Record does not belong to the selected project.');
            }
        }

        return match ($type) {
            'quality' => self::saveQuality($projectId, $userId, $input, $id),
            'itp' => self::saveItp($projectId, $userId, $input, $id),
            'hs' => self::saveHs($projectId, $userId, $input, $id),
            'ncr' => self::saveNcr($projectId, $userId, $input, $id),
            'defect' => self::saveDefect($projectId, $userId, $input, $id),
            'meeting' => self::saveMeeting($projectId, $userId, $input, $id),
            'document' => self::saveDocument($projectId, $userId, $input, false, $id),
            'photo' => self::saveDocument($projectId, $userId, $input, true, $id),
            default => throw new InvalidArgumentException('Unsupported clerk evidence type.'),
        };
    }

    private static function saveQuality(int $projectId, int $userId, array $input, int $id = 0): int
    {
        $status = self::choice($input, 'verification_status', ['pending', 'passed', 'failed', 'queried'], 'pending');
        $passFail = $status === 'passed' ? 'pass' : ($status === 'failed' ? 'fail' : 'pending');
        $actual = self::text($input, 'actual_result', 180);
        $required = self::text($input, 'required_result', 180);
        $result = self::text($input, 'result', 150) ?: ($actual ?: $required);
        $media = self::mediaPayload($input, 'document_path', 'evidence_media_id');
        $data = [
            'project_id' => $projectId,
            'test_type' => self::required($input, 'test_type', 100),
            'test_date' => self::date($input, 'test_date', date('Y-m-d')),
            'location_on_site' => self::text($input, 'location_on_site', 200),
            'result' => $result,
            'required_result' => $required,
            'actual_result' => $actual,
            'pass_fail' => $passFail,
            'lab_ref' => self::text($input, 'lab_ref', 80),
            'tested_by' => $userId,
            'document_path' => $media['path'],
            'evidence_media_id' => $media['media_id'],
            'verification_status' => $status,
            'verified_by' => $status === 'pending' ? null : $userId,
            'verified_at' => $status === 'pending' ? null : date('Y-m-d H:i:s'),
            'clerk_observation' => self::text($input, 'clerk_observation'),
        ];
        if ($id > 0) {
            self::update('quality_tests', $id, $data);
            self::notify('Quality test', $projectId, 'admin/consultant/quality-tests.php');
            return $id;
        }
        return self::insert('quality_tests', $data, 'Quality test', $projectId, 'admin/consultant/quality-tests.php');
    }

    private static function saveItp(int $projectId, int $userId, array $input, int $id = 0): int
    {
        $status = self::choice($input, 'inspection_status', ['pending', 'passed', 'failed', 'rework-required'], 'pending');
        $media = self::mediaPayload($input, 'document_path', 'evidence_media_id');
        $data = [
            'project_id' => $projectId,
            'activity' => self::required($input, 'activity', 200),
            'inspection_area' => self::text($input, 'inspection_area', 180),
            'hold_point' => self::text($input, 'hold_point', 100),
            'inspection_date' => self::dateOrNull($input, 'inspection_date') ?: date('Y-m-d'),
            'inspected_by' => $userId,
            'outcome' => self::text($input, 'outcome', 100),
            'clerk_notes' => self::text($input, 'clerk_notes'),
            'witness_required' => !empty($input['witness_required']) ? 1 : 0,
            'document_path' => $media['path'],
            'evidence_media_id' => $media['media_id'],
            'inspection_status' => $status,
            'verified_by' => $status === 'pending' ? null : $userId,
            'verified_at' => $status === 'pending' ? null : date('Y-m-d H:i:s'),
        ];
        if ($id > 0) {
            self::update('inspection_test_plans', $id, $data);
            self::notify('Inspection record', $projectId, 'admin/consultant/inspection-test-plans.php');
            return $id;
        }
        return self::insert('inspection_test_plans', $data, 'Inspection record', $projectId, 'admin/consultant/inspection-test-plans.php');
    }

    private static function saveHs(int $projectId, int $userId, array $input, int $id = 0): int
    {
        $status = self::choice($input, 'status', ['open', 'investigating', 'action-pending', 'resolved', 'closed'], 'open');
        $media = self::mediaPayload($input, 'attachment_path', 'evidence_media_id');
        $data = [
            'project_id' => $projectId,
            'incident_date' => self::date($input, 'incident_date', date('Y-m-d')),
            'incident_type' => self::choice($input, 'incident_type', ['near-miss', 'first-aid', 'medical', 'fatality'], 'near-miss'),
            'description' => self::required($input, 'description'),
            'persons_involved' => self::text($input, 'persons_involved'),
            'cause' => self::text($input, 'cause'),
            'corrective_action' => self::text($input, 'corrective_action'),
            'immediate_action' => self::text($input, 'immediate_action'),
            'lost_time_hours' => max(0, Security::cleanFloat($input['lost_time_hours'] ?? 0)),
            'reported_by' => $userId,
            'severity' => self::choice($input, 'severity', ['low', 'medium', 'high', 'critical'], 'medium'),
            'status' => $status,
            'follow_up_date' => self::dateOrNull($input, 'follow_up_date'),
            'attachment_path' => $media['path'],
            'evidence_media_id' => $media['media_id'],
            'updated_by' => $userId,
        ];
        if (in_array($status, ['resolved', 'closed'], true)) {
            $data['closed_at'] = date('Y-m-d H:i:s');
        }
        if ($id > 0) {
            self::update('hs_incidents', $id, $data);
            self::notify('Safety incident', $projectId, 'admin/manager/hs-incidents.php');
            return $id;
        }
        return self::insert('hs_incidents', $data, 'Safety incident', $projectId, 'admin/manager/hs-incidents.php');
    }

    private static function saveNcr(int $projectId, int $userId, array $input, int $id = 0): int
    {
        $status = self::choice($input, 'status', ['open', 'in-progress', 'closed'], 'open');
        $ref = self::text($input, 'ncr_reference', 80);
        if ($ref === null || $ref === '') {
            $ref = self::nextReference('non_conformance_reports', 'ncr_reference', 'NCR');
        }
        $media = self::mediaPayload($input, 'document_path', 'evidence_media_id');
        $data = [
            'project_id' => $projectId,
            'ncr_reference' => $ref,
            'raised_by' => $userId,
            'raised_date' => self::date($input, 'raised_date', date('Y-m-d')),
            'location_on_site' => self::text($input, 'location_on_site', 180),
            'description' => self::required($input, 'description'),
            'severity' => self::choice($input, 'severity', ['minor', 'major', 'critical'], 'minor'),
            'root_cause' => self::text($input, 'root_cause'),
            'corrective_action' => self::text($input, 'corrective_action'),
            'target_close_date' => self::dateOrNull($input, 'target_close_date'),
            'evidence_media_id' => $media['media_id'],
            'status' => $status,
            'updated_by' => $userId,
        ];
        if ($status === 'closed') {
            $data['closed_by'] = $userId;
            $data['closed_date'] = date('Y-m-d');
        } else {
            $data['closed_by'] = null;
            $data['closed_date'] = null;
        }
        if ($id > 0) {
            unset($data['raised_by']);
            self::update('non_conformance_reports', $id, $data);
            self::notify('Non-conformance', $projectId, 'admin/consultant/non-conformance.php');
            return $id;
        }
        return self::insert('non_conformance_reports', $data, 'Non-conformance', $projectId, 'admin/consultant/non-conformance.php');
    }

    private static function saveDefect(int $projectId, int $userId, array $input, int $id = 0): int
    {
        $status = self::choice($input, 'status', ['open', 'in-progress', 'resolved', 'closed'], 'open');
        $ref = self::text($input, 'defect_reference', 80);
        if ($ref === null || $ref === '') {
            $ref = self::nextReference('defects', 'defect_reference', 'DEF');
        }
        $media = self::mediaPayload($input, 'photo_path', 'evidence_media_id');
        $data = [
            'project_id' => $projectId,
            'defect_reference' => $ref,
            'raised_by' => $userId,
            'raised_date' => self::date($input, 'raised_date', date('Y-m-d')),
            'location' => self::text($input, 'location', 200),
            'description' => self::required($input, 'description'),
            'severity' => self::choice($input, 'severity', ['minor', 'major', 'critical'], 'minor'),
            'photo_path' => $media['path'],
            'evidence_media_id' => $media['media_id'],
            'due_date' => self::dateOrNull($input, 'due_date'),
            'status' => $status,
            'rectification_notes' => self::text($input, 'rectification_notes'),
        ];
        if (in_array($status, ['resolved', 'closed'], true)) {
            $data['closed_date'] = date('Y-m-d');
            $data['verified_fixed_by'] = $userId;
            $data['verified_fixed_at'] = date('Y-m-d H:i:s');
        } else {
            $data['closed_date'] = null;
            $data['verified_fixed_by'] = null;
            $data['verified_fixed_at'] = null;
        }
        if ($id > 0) {
            unset($data['raised_by']);
            self::update('defects', $id, $data);
            self::notify('Defect', $projectId, 'admin/consultant/defects.php');
            return $id;
        }
        return self::insert('defects', $data, 'Defect', $projectId, 'admin/consultant/defects.php');
    }

    private static function saveMeeting(int $projectId, int $userId, array $input, int $id = 0): int
    {
        $media = self::mediaPayload($input, 'document_path', 'evidence_media_id');
        $data = [
            'project_id' => $projectId,
            'meeting_type' => self::choice($input, 'meeting_type', ['site', 'progress', 'safety', 'quality', 'community'], 'site'),
            'meeting_date' => self::date($input, 'meeting_date', date('Y-m-d')),
            'venue' => self::text($input, 'venue', 200),
            'chairperson' => self::text($input, 'chairperson', 150),
            'attendees_json' => self::jsonLines($input['attendees'] ?? ''),
            'agenda' => self::text($input, 'agenda'),
            'minutes_text' => self::required($input, 'minutes_text'),
            'action_items_json' => self::jsonLines($input['action_items'] ?? ''),
            'document_path' => $media['path'],
            'recorded_by' => $userId,
            'status' => self::choice($input, 'status', ['draft', 'recorded', 'reviewed', 'closed'], 'recorded'),
            'action_status' => self::choice($input, 'action_status', ['none', 'open', 'in-progress', 'completed', 'overdue'], 'open'),
            'next_meeting_date' => self::dateOrNull($input, 'next_meeting_date'),
            'updated_by' => $userId,
        ];
        if ($id > 0) {
            unset($data['recorded_by']);
            self::update('site_meeting_minutes', $id, $data);
            self::notify('Meeting minutes', $projectId, 'admin/manager/site-meetings.php');
            return $id;
        }
        return self::insert('site_meeting_minutes', $data, 'Meeting minutes', $projectId, 'admin/manager/site-meetings.php');
    }

    private static function saveDocument(int $projectId, int $userId, array $input, bool $photo, int $id = 0): int
    {
        $name = self::required($input, 'original_name', 255);
        $media = self::mediaPayload($input, 'filename', 'media_id');
        $path = $media['path'] ?: self::required($input, 'filename', 255);
        $size = max(0, Security::cleanInt($input['size'] ?? 0));
        if ($size <= 0 && $path) {
            $abs = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
            if (is_file($abs)) {
                $size = (int)filesize($abs);
            }
        }
        $data = [
            'project_id' => $projectId,
            'uploaded_by' => $userId,
            'category' => $photo ? 'other' : self::choice($input, 'category', ['contract', 'drawing', 'spec', 'report', 'correspondence', 'shop-drawing', 'quality-test', 'other'], 'other'),
            'clerk_document_type' => $photo ? 'photo' : self::choice($input, 'clerk_document_type', ['daily', 'quality', 'safety', 'meeting', 'ipc', 'other'], 'other'),
            'filename' => $path,
            'original_name' => $name,
            'size' => $size,
            'version' => self::text($input, 'version', 20) ?: '1.0',
            'site_record_date' => self::dateOrNull($input, 'site_record_date') ?: date('Y-m-d'),
            'linked_record_type' => self::text($input, 'linked_record_type', 60),
            'linked_record_id' => self::nullableInt($input['linked_record_id'] ?? null),
            'description' => self::text($input, 'description'),
            'is_confidential' => !empty($input['is_confidential']) ? 1 : 0,
            'review_required' => !empty($input['review_required']) ? 1 : ($photo ? 0 : 0),
            'consultant_review_status' => self::choice($input, 'consultant_review_status', ['pending', 'accepted', 'flagged', 'returned'], 'pending'),
        ];
        $label = $photo ? 'Photo evidence' : 'Site document';
        $link = $photo ? 'admin/clerk/photos.php' : 'admin/clerk/documents.php';
        if ($id > 0) {
            unset($data['uploaded_by']);
            self::update('documents', $id, $data);
            self::notify($label, $projectId, $link);
            return $id;
        }
        return self::insert('documents', $data, $label, $projectId, $link);
    }

    private static function verifyIpc(int $userId, array $input): int
    {
        $ipcId = Security::cleanInt($input['ipc_id'] ?? 0);
        $action = strtolower(trim((string)($input['ipc_action'] ?? 'endorse')));
        if (!in_array($action, ['endorse', 'return'], true)) {
            $action = 'endorse';
        }

        $ipc = $ipcId > 0 ? Database::fetch('SELECT * FROM ipcs WHERE id = ? LIMIT 1', [$ipcId]) : null;
        if (!$ipc || !ClerkAttendance::canAccessProject($userId, (int)$ipc['project_id'])) {
            throw new RuntimeException('Choose an assigned IPC before saving.');
        }

        $status = (string)($ipc['status'] ?? '');
        if ($status !== 'submitted') {
            throw new RuntimeException('Only submitted IPCs can be verified or returned by the clerk. Current status: ' . status_label($status) . '.');
        }

        $comment = self::required($input, 'clerk_verification_comment');
        $checklist = [
            'site_records_checked' => !empty($input['site_records_checked']),
            'line_quantities_checked' => !empty($input['line_quantities_checked']),
            'supporting_documents_checked' => !empty($input['supporting_documents_checked']),
            'exceptions_noted' => !empty($input['exceptions_noted']),
        ];

        $project = Project::findDetailed((int)$ipc['project_id']) ?: [];
        $projectName = (string)($project['name'] ?? 'project');
        $ipcNumber = (int)($ipc['ipc_number'] ?? 0);

        try {
            Database::beginTransaction();

            if ($action === 'return') {
                $changed = Database::affectedRows(
                    "UPDATE ipcs
                     SET status = 'rejected',
                         current_stage = 'returned_to_contractor',
                         clerk_verification_comment = ?,
                         clerk_checklist_json = ?,
                         clerk_verified_by = ?,
                         clerk_verified_at = NOW(),
                         rejected_by = ?,
                         rejected_at = NOW(),
                         rejection_reason = ?,
                         updated_at = NOW()
                     WHERE id = ? AND status = 'submitted'",
                    [$comment, json_encode($checklist), $userId, $userId, $comment, $ipcId]
                );
                if ($changed < 1) {
                    throw new RuntimeException('IPC was already processed by another user.');
                }
                Database::query(
                    "INSERT INTO ipc_approvals (ipc_id, step, action_by, action, comments, actioned_at) VALUES (?, 1, ?, 'rejected', ?, NOW())",
                    [$ipcId, $userId, $comment]
                );
                Database::commit();

                Notification::pushRole('consultant', 'ipc_rejected', 'IPC returned by clerk', 'IPC #' . $ipcNumber . ' for ' . $projectName . ' was returned by the Clerk of Works.', 'admin/consultant/ipc-inbox.php');
                Notification::push((int)$ipc['contractor_id'], 'ipc_updated', 'IPC returned for correction', 'IPC #' . $ipcNumber . ' for ' . $projectName . ' was returned by the clerk. Review the verification notes.', 'admin/contractor/ipc-history.php');
                return $ipcId;
            }

            // Endorse path
            if (empty($checklist['site_records_checked']) || empty($checklist['line_quantities_checked']) || empty($checklist['supporting_documents_checked'])) {
                throw new RuntimeException('Confirm site records, line quantities and supporting documents before endorsing.');
            }

            $changed = Database::affectedRows(
                "UPDATE ipcs
                 SET status = 'clerk-endorsed',
                     current_stage = 'consultant_review',
                     clerk_verification_comment = ?,
                     clerk_checklist_json = ?,
                     clerk_verified_by = ?,
                     clerk_verified_at = NOW(),
                     updated_at = NOW()
                 WHERE id = ? AND status = 'submitted'",
                [$comment, json_encode($checklist), $userId, $ipcId]
            );
            if ($changed < 1) {
                throw new RuntimeException('IPC was already processed by another user.');
            }
            Database::query(
                "INSERT INTO ipc_approvals (ipc_id, step, action_by, action, comments, actioned_at) VALUES (?, 1, ?, 'endorsed', ?, NOW())",
                [$ipcId, $userId, $comment]
            );
            Database::commit();

            Notification::pushRole('consultant', 'ipc_clerk_endorsed', 'IPC ready for certification', 'IPC #' . $ipcNumber . ' for ' . $projectName . ' has been verified by the Clerk of Works.', 'admin/consultant/ipc-inbox.php');
            Notification::push((int)$ipc['contractor_id'], 'ipc_updated', 'IPC verified by clerk', 'IPC #' . $ipcNumber . ' for ' . $projectName . ' moved to clerk-endorsed.', 'admin/contractor/ipc-history.php');
            return $ipcId;
        } catch (RuntimeException $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
            throw $e;
        } catch (Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
            throw new RuntimeException('IPC verification could not be completed.');
        }
    }

    private static function statsFor(string $table, string $dateColumn, int $projectId, array $expressions): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(DATE({$dateColumn}) = CURDATE()), 0) today,
                    COALESCE(SUM(CASE WHEN {$expressions['open']} THEN 1 ELSE 0 END), 0) open,
                    COALESCE(SUM(CASE WHEN {$expressions['risk']} THEN 1 ELSE 0 END), 0) risk,
                    COALESCE(SUM(CASE WHEN {$expressions['value']} THEN 1 ELSE 0 END), 0) value
             FROM {$table} WHERE project_id = ?",
            [$projectId]
        ) ?: [];
        return self::statsRow($row);
    }

    private static function documentStats(string $type, int $projectId): array
    {
        $photo = $type === 'photo';
        $extra = $photo ? "AND clerk_document_type = 'photo'" : "AND COALESCE(clerk_document_type, '') <> 'photo'";
        $row = Database::fetch(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(DATE(COALESCE(site_record_date, created_at)) = CURDATE()),0) today,
                    COALESCE(SUM(consultant_review_status = 'pending' OR review_required = 1),0) open,
                    COALESCE(SUM(consultant_review_status IN ('flagged','returned') OR is_confidential = 1),0) risk,
                    COALESCE(SUM(size),0) value
             FROM documents WHERE project_id = ? {$extra}",
            [$projectId]
        ) ?: [];
        return self::statsRow($row);
    }

    private static function ipcStats(int $projectId): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(DATE(submitted_at) = CURDATE()),0) today,
                    COALESCE(SUM(status = 'submitted'),0) open,
                    COALESCE(SUM(status = 'rejected'),0) risk,
                    COALESCE(SUM(status = 'clerk-endorsed'),0) value
             FROM ipcs WHERE project_id = ?",
            [$projectId]
        ) ?: [];
        return self::statsRow($row);
    }

    private static function query(string $select, string $alias, string $dateColumn, string $statusColumn, array $searchColumns, int $projectId, array $filters, int $limit, int $offset): array
    {
        $where = ["{$alias}.project_id = ?"];
        $bindings = [$projectId];
        self::applyFilters($where, $bindings, $alias, $dateColumn, $statusColumn, $searchColumns, $filters);
        return Database::fetchAll(
            $select . ' WHERE ' . implode(' AND ', $where)
            . " ORDER BY {$alias}.{$dateColumn} DESC, {$alias}.id DESC LIMIT " . $limit . ' OFFSET ' . $offset,
            $bindings
        );
    }

    private static function countQuery(string $table, string $alias, string $dateColumn, string $statusColumn, array $searchColumns, int $projectId, array $filters): int
    {
        $where = ["{$alias}.project_id = ?"];
        $bindings = [$projectId];
        self::applyFilters($where, $bindings, $alias, $dateColumn, $statusColumn, $searchColumns, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM `{$table}` {$alias} WHERE " . implode(' AND ', $where),
            $bindings
        );
        return (int)($row['c'] ?? 0);
    }

    private static function documents(int $projectId, array $filters, int $limit, int $offset, bool $photo): array
    {
        $where = ['d.project_id = ?', $photo ? "d.clerk_document_type = 'photo'" : "COALESCE(d.clerk_document_type, '') <> 'photo'"];
        $bindings = [$projectId];
        self::applyFilters($where, $bindings, 'd', 'site_record_date', 'consultant_review_status', ['d.original_name', 'd.filename', 'd.description', 'd.clerk_document_type'], $filters);
        return Database::fetchAll(
            "SELECT d.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name
             FROM documents d JOIN projects p ON p.id = d.project_id LEFT JOIN users u ON u.id = d.uploaded_by
             WHERE " . implode(' AND ', $where)
            . ' ORDER BY COALESCE(d.site_record_date, DATE(d.created_at)) DESC, d.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset,
            $bindings
        );
    }

    private static function countDocuments(int $projectId, array $filters, bool $photo): int
    {
        $where = ['d.project_id = ?', $photo ? "d.clerk_document_type = 'photo'" : "COALESCE(d.clerk_document_type, '') <> 'photo'"];
        $bindings = [$projectId];
        self::applyFilters($where, $bindings, 'd', 'site_record_date', 'consultant_review_status', ['d.original_name', 'd.filename', 'd.description', 'd.clerk_document_type'], $filters);
        $row = Database::fetch('SELECT COUNT(*) c FROM documents d WHERE ' . implode(' AND ', $where), $bindings);
        return (int)($row['c'] ?? 0);
    }

    private static function ipcs(int $projectId, array $filters, int $limit, int $offset): array
    {
        $where = ['i.project_id = ?'];
        $bindings = [$projectId];
        self::applyFilters($where, $bindings, 'i', 'submitted_at', 'status', ['i.contractor_reference', 'p.name'], $filters);
        return Database::fetchAll(
            "SELECT i.*, p.name project_name, CONCAT(u.first_name, ' ', u.last_name) actor_name,
                    (SELECT COUNT(*) FROM ipc_lines il WHERE il.ipc_id = i.id) AS line_count
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             LEFT JOIN users u ON u.id = i.contractor_id
             WHERE " . implode(' AND ', $where) . '
             ORDER BY COALESCE(i.submitted_at, i.created_at) DESC, i.id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $bindings
        );
    }

    private static function countIpcs(int $projectId, array $filters): int
    {
        $where = ['i.project_id = ?'];
        $bindings = [$projectId];
        self::applyFilters($where, $bindings, 'i', 'submitted_at', 'status', ['i.contractor_reference', 'p.name'], $filters);
        $row = Database::fetch(
            'SELECT COUNT(*) c FROM ipcs i JOIN projects p ON p.id = i.project_id WHERE ' . implode(' AND ', $where),
            $bindings
        );
        return (int)($row['c'] ?? 0);
    }

    private static function applyFilters(array &$where, array &$bindings, string $alias, string $dateColumn, string $statusColumn, array $searchColumns, array $filters): void
    {
        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = "{$alias}.{$statusColumn} = ?";
            $bindings[] = $status;
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '' && $searchColumns !== []) {
            $parts = [];
            foreach ($searchColumns as $column) {
                $parts[] = "{$column} LIKE ?";
                $bindings[] = '%' . $q . '%';
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $op) {
            $date = trim((string)($filters[$key] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where[] = "DATE({$alias}.{$dateColumn}) {$op} ?";
                $bindings[] = $date;
            }
        }
    }

    private static function mediaPayload(array $input, string $pathKey, string $mediaKey): array
    {
        $mediaId = self::nullableInt($input[$mediaKey] ?? ($input['media_id'] ?? null));
        $path = self::text($input, $pathKey, 255);
        if ($mediaId && (!$path || $path === '')) {
            $media = Database::fetch('SELECT id, path, url, size, original_name FROM media_library WHERE id = ? LIMIT 1', [$mediaId]);
            if ($media) {
                $path = (string)($media['path'] ?? $media['url'] ?? '');
            }
        }
        if ($path && !$mediaId) {
            $byPath = Database::fetch('SELECT id FROM media_library WHERE path = ? OR url = ? ORDER BY id DESC LIMIT 1', [$path, $path]);
            if ($byPath) {
                $mediaId = (int)$byPath['id'];
            }
        }
        return ['path' => $path, 'media_id' => $mediaId];
    }

    private static function nextReference(string $table, string $column, string $prefix): string
    {
        $year = date('Y');
        $like = $prefix . '-' . $year . '-%';
        $row = Database::fetch(
            "SELECT {$column} AS ref FROM `{$table}` WHERE {$column} LIKE ? ORDER BY id DESC LIMIT 1",
            [$like]
        );
        $seq = 1;
        if ($row && preg_match('/-(\d+)$/', (string)$row['ref'], $m)) {
            $seq = (int)$m[1] + 1;
        }
        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    private static function insert(string $table, array $data, string $label, int $projectId, string $link = 'admin/manager/reports.php'): int
    {
        $columns = array_keys($data);
        $safe = array_map(static fn ($column): string => '`' . str_replace('`', '', $column) . '`', $columns);
        Database::query(
            'INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $safe) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            array_values($data)
        );
        $id = (int)Database::lastInsertId();
        self::notify($label, $projectId, $link);
        return $id;
    }

    private static function update(string $table, int $id, array $data): void
    {
        $sets = [];
        $values = [];
        foreach ($data as $column => $value) {
            $sets[] = '`' . str_replace('`', '', (string)$column) . '` = ?';
            $values[] = $value;
        }
        $values[] = $id;
        Database::query(
            'UPDATE `' . str_replace('`', '', $table) . '` SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $values
        );
    }

    private static function notify(string $title, int $projectId, string $link): void
    {
        try {
            $project = Project::findDetailed($projectId);
            $message = $title . ' updated for ' . ($project['name'] ?? 'a project') . '.';
            Notification::pushRole('manager', 'clerk_evidence', $title . ' updated', $message, $link);
            Notification::pushRole('consultant', 'clerk_evidence', $title . ' updated', $message, $link);
        } catch (Throwable) {
        }
    }

    private static function emptyStats(): array
    {
        return ['total' => 0, 'today' => 0, 'open' => 0, 'risk' => 0, 'value' => 0];
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

    private static function nullableInt(mixed $value): ?int
    {
        $value = Security::cleanInt($value ?? 0);
        return $value > 0 ? $value : null;
    }

    private static function choice(array $input, string $key, array $allowed, string $fallback): string
    {
        $value = trim((string)($input[$key] ?? $fallback));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function jsonLines(mixed $value): string
    {
        if (is_array($value)) {
            $lines = array_values(array_filter(array_map(static fn ($v) => trim((string)$v), $value)));
            return json_encode($lines);
        }
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$value) ?: [])));
        return json_encode($lines);
    }
}
