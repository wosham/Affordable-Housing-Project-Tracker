<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['clerk'],
]);

$type = trim((string)($_GET['type'] ?? ''));
$id = Security::cleanInt($_GET['id'] ?? 0);

try {
    $config = ClerkQualityEvidence::config($type);
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Unsupported record type.'], 422);
}

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Record id is required.'], 422);
}

$row = ClerkQualityEvidence::find($type, (int)Auth::id(), $id);
if (!$row) {
    Response::json(['success' => false, 'message' => 'Record not found or not assigned to you.'], 404);
}

// Expand JSON fields for meeting form edit payload
if ($type === 'meeting') {
    $attendees = json_decode((string)($row['attendees_json'] ?? '[]'), true);
    $actions = json_decode((string)($row['action_items_json'] ?? '[]'), true);
    $row['attendees'] = is_array($attendees) ? implode("\n", $attendees) : '';
    $row['action_items'] = is_array($actions) ? implode("\n", $actions) : '';
}

$path = match ($type) {
    'quality', 'itp', 'meeting' => (string)($row['document_path'] ?? ''),
    'hs' => (string)($row['attachment_path'] ?? ''),
    'defect' => (string)($row['photo_path'] ?? ''),
    'document', 'photo' => (string)($row['filename'] ?? ''),
    default => '',
};
$mediaRow = null;
$mediaId = (int)($row['evidence_media_id'] ?? $row['media_id'] ?? 0);
if ($mediaId > 0) {
    $mediaRow = Database::fetch('SELECT path, url, original_name, type FROM media_library WHERE id = ? LIMIT 1', [$mediaId]);
    if ($mediaRow && $path === '') {
        $path = (string)($mediaRow['path'] ?? '');
    }
}

$url = '';
if ($path !== '') {
    if (preg_match('#^https?://#i', $path)) {
        $url = $path;
    } else {
        $url = Url::asset(ltrim(str_replace('\\', '/', $path), '/'));
    }
} elseif ($mediaRow && !empty($mediaRow['url'])) {
    $url = (string)$mediaRow['url'];
}

$isImage = (bool)preg_match('/\.(jpe?g|png|webp|gif)$/i', $path)
    || ($mediaRow && str_starts_with((string)($mediaRow['type'] ?? ''), 'image/'));

$status = match ($type) {
    'quality' => (string)($row['verification_status'] ?? 'pending'),
    'itp' => (string)($row['inspection_status'] ?? 'pending'),
    'document', 'photo' => (string)($row['consultant_review_status'] ?? 'pending'),
    default => (string)($row['status'] ?? 'pending'),
};

$date = match ($type) {
    'quality' => (string)($row['test_date'] ?? ''),
    'itp' => (string)($row['inspection_date'] ?? ''),
    'hs' => (string)($row['incident_date'] ?? ''),
    'ncr', 'defect' => (string)($row['raised_date'] ?? ''),
    'meeting' => (string)($row['meeting_date'] ?? ''),
    'document', 'photo' => (string)($row['site_record_date'] ?? $row['created_at'] ?? ''),
    'ipc' => (string)($row['submitted_at'] ?? ''),
    default => '',
};

$title = match ($type) {
    'quality' => (string)($row['test_type'] ?? 'Quality test'),
    'itp' => (string)($row['activity'] ?? 'Inspection'),
    'hs' => status_label((string)($row['incident_type'] ?? 'Incident')),
    'ncr' => (string)($row['ncr_reference'] ?: 'Non-conformance'),
    'defect' => (string)($row['defect_reference'] ?: 'Defect'),
    'meeting' => status_label((string)($row['meeting_type'] ?? 'Meeting')) . ' meeting',
    'document', 'photo' => (string)($row['original_name'] ?? 'Evidence'),
    'ipc' => 'IPC #' . (int)($row['ipc_number'] ?? 0),
    default => $config['title'],
};

$fields = match ($type) {
    'quality' => [
        ['label' => 'Location', 'value' => (string)($row['location_on_site'] ?? '—')],
        ['label' => 'Lab ref', 'value' => (string)($row['lab_ref'] ?? '—')],
        ['label' => 'Required', 'value' => (string)($row['required_result'] ?? '—')],
        ['label' => 'Actual', 'value' => (string)($row['actual_result'] ?? $row['result'] ?? '—')],
        ['label' => 'Observation', 'value' => (string)($row['clerk_observation'] ?? '—'), 'wide' => true],
    ],
    'itp' => [
        ['label' => 'Area', 'value' => (string)($row['inspection_area'] ?? '—')],
        ['label' => 'Hold point', 'value' => (string)($row['hold_point'] ?? '—')],
        ['label' => 'Outcome', 'value' => (string)($row['outcome'] ?? '—')],
        ['label' => 'Witness', 'value' => !empty($row['witness_required']) ? 'Required' : 'Not required'],
        ['label' => 'Notes', 'value' => (string)($row['clerk_notes'] ?? '—'), 'wide' => true],
    ],
    'hs' => [
        ['label' => 'Severity', 'value' => status_label((string)($row['severity'] ?? ''))],
        ['label' => 'Lost time (hrs)', 'value' => (string)($row['lost_time_hours'] ?? '0')],
        ['label' => 'Follow-up', 'value' => (string)($row['follow_up_date'] ?? '—')],
        ['label' => 'Description', 'value' => (string)($row['description'] ?? '—'), 'wide' => true],
        ['label' => 'Cause', 'value' => (string)($row['cause'] ?? '—'), 'wide' => true],
        ['label' => 'Immediate action', 'value' => (string)($row['immediate_action'] ?? '—'), 'wide' => true],
        ['label' => 'Corrective action', 'value' => (string)($row['corrective_action'] ?? '—'), 'wide' => true],
    ],
    'ncr' => [
        ['label' => 'Location', 'value' => (string)($row['location_on_site'] ?? '—')],
        ['label' => 'Severity', 'value' => status_label((string)($row['severity'] ?? ''))],
        ['label' => 'Target close', 'value' => (string)($row['target_close_date'] ?? '—')],
        ['label' => 'Description', 'value' => (string)($row['description'] ?? '—'), 'wide' => true],
        ['label' => 'Root cause', 'value' => (string)($row['root_cause'] ?? '—'), 'wide' => true],
        ['label' => 'Corrective action', 'value' => (string)($row['corrective_action'] ?? '—'), 'wide' => true],
    ],
    'defect' => [
        ['label' => 'Location', 'value' => (string)($row['location'] ?? '—')],
        ['label' => 'Severity', 'value' => status_label((string)($row['severity'] ?? ''))],
        ['label' => 'Due date', 'value' => (string)($row['due_date'] ?? '—')],
        ['label' => 'Description', 'value' => (string)($row['description'] ?? '—'), 'wide' => true],
        ['label' => 'Rectification', 'value' => (string)($row['rectification_notes'] ?? '—'), 'wide' => true],
    ],
    'meeting' => [
        ['label' => 'Venue', 'value' => (string)($row['venue'] ?? '—')],
        ['label' => 'Chairperson', 'value' => (string)($row['chairperson'] ?? '—')],
        ['label' => 'Action status', 'value' => status_label((string)($row['action_status'] ?? ''))],
        ['label' => 'Next meeting', 'value' => (string)($row['next_meeting_date'] ?? '—')],
        ['label' => 'Attendees', 'value' => (string)($row['attendees'] ?? '—'), 'wide' => true],
        ['label' => 'Agenda', 'value' => (string)($row['agenda'] ?? '—'), 'wide' => true],
        ['label' => 'Minutes', 'value' => (string)($row['minutes_text'] ?? '—'), 'wide' => true],
        ['label' => 'Actions', 'value' => (string)($row['action_items'] ?? '—'), 'wide' => true],
    ],
    'document', 'photo' => [
        ['label' => 'Type', 'value' => status_label((string)($row['clerk_document_type'] ?? ($type === 'photo' ? 'photo' : 'other')))],
        ['label' => 'Version', 'value' => (string)($row['version'] ?? '1.0')],
        ['label' => 'Size', 'value' => number_format(((float)($row['size'] ?? 0)) / 1024, 1) . ' KB'],
        ['label' => 'Description', 'value' => (string)($row['description'] ?? '—'), 'wide' => true],
        ['label' => 'File', 'value' => $path !== '' ? basename($path) : '—', 'wide' => true],
    ],
    default => [],
};

Response::json([
    'success' => true,
    'type' => $type,
    'record' => $row,
    'view' => [
        'id' => (int)$row['id'],
        'title' => $title,
        'project_name' => (string)($row['project_name'] ?? ''),
        'status' => $status,
        'date' => $date,
        'fields' => $fields,
        'media' => $url !== '' || $path !== '' ? [
            'path' => $path,
            'url' => $url,
            'is_image' => $isImage,
            'title' => (string)(($row['original_name'] ?? '') !== '' ? $row['original_name'] : (basename($path) ?: 'Evidence')),
        ] : null,
    ],
]);
