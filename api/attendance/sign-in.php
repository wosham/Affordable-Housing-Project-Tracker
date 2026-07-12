<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['intern', 'clerk'],
    'csrf_form' => 'attendance_signin',
]);

$input = $_POST;
$userId = (int)Auth::id();
$role = (string)Auth::role();
$targetType = (string)($input['target_type'] ?? 'project');
$targetType = $targetType === 'work_location' ? 'work_location' : 'project';
$targetId = Security::cleanInt($input['target_id'] ?? ($input['project_id'] ?? 0));

// Self-sign only: never accept another user id from the client.
if (Security::cleanInt($input['user_id'] ?? 0) > 0 && Security::cleanInt($input['user_id']) !== $userId) {
    Response::json(['success' => false, 'message' => 'You can only sign in for yourself.'], 403);
}

$defaultTarget = $role === 'clerk'
    ? clerk_attendance_default_target($userId, $targetType, $targetId)
    : InternAttendance::defaultTarget($userId, $targetType, $targetId);
$targetType = (string)$defaultTarget['type'];
$targetId = (int)$defaultTarget['id'];
$target = $defaultTarget['target'] ?? null;

if ($targetId <= 0 || !$target) {
    Response::json(['success' => false, 'message' => 'No assigned attendance location was found for sign-in.'], 422);
}

if ($role === 'intern' && !InternAttendance::canAccessTarget($userId, $targetType, $targetId)) {
    Response::json(['success' => false, 'message' => 'You are not assigned to this attendance location.'], 403);
}
if ($role === 'clerk' && $targetType === 'project' && !ClerkAttendance::canAccessProject($userId, $targetId)) {
    Response::json(['success' => false, 'message' => 'You are not assigned to this project.'], 403);
}

$projectId = $targetType === 'project' ? $targetId : null;
$workLocationId = $targetType === 'work_location' ? $targetId : null;
$project = ['id' => $targetId, 'name' => (string)($target['name'] ?? 'Attendance location'), 'target_type' => $targetType];
$latitude = filter_var($input['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($input['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
$accuracy = filter_var($input['accuracy_meters'] ?? null, FILTER_VALIDATE_FLOAT);

$gpsRequired = SystemConfig::bool('attendance.gps_required', true);
if ($gpsRequired && ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180)) {
    Response::json(['success' => false, 'message' => 'A valid GPS latitude and longitude is required.'], 422);
}

$date = date('Y-m-d');
$policy = AttendancePolicy::windowPayload();

// One sign-in per person per day (any site).
$alreadySigned = AttendanceRecord::todayForAnyTarget($userId);
if ($alreadySigned) {
    attendance_already_signed_response($alreadySigned, $project);
}

if (!$policy['is_active_day']) {
    Response::json(['success' => false, 'message' => 'Attendance is only available on scheduled weekdays (Mon–Fri).'], 409);
}
if ($policy['is_before_open']) {
    Response::json(['success' => false, 'message' => 'Attendance opens at ' . $policy['open_time'] . ' (County Director policy).'], 409);
}
if ($policy['is_after_close']) {
    Response::json(['success' => false, 'message' => 'Attendance closed at ' . $policy['close_time'] . ' today.'], 409);
}

// Auto-open policy window so early arrivals are not blocked by clerk delay.
if ($targetType === 'project') {
    $gateway = AttendancePolicy::ensureProjectWindow($targetId, $date);
} else {
    $gateway = AttendancePolicy::ensureWorkLocationWindow($targetId, $date);
}

if (!AttendancePolicy::isEffectivelyOpen($gateway)) {
    Response::json(['success' => false, 'message' => 'Attendance gateway is not open for this location right now.'], 409);
}

$geoFence = $targetType === 'work_location' ? [
    'latitude' => $target['latitude'] ?? null,
    'longitude' => $target['longitude'] ?? null,
    'radius_meters' => $target['radius_meters'] ?? null,
    'status' => $target['geo_status'] ?? 'missing',
] : GeoFenceModel::forProject($targetId);
$distance = null;
$withinFence = false;
$status = AttendancePolicy::statusForSignInTime();

if ($status === 'outside-window') {
    Response::json(['success' => false, 'message' => 'Attendance is outside the County Director policy window.'], 409);
}

if ($gpsRequired && $geoFence && ($geoFence['status'] ?? '') === 'configured') {
    $distance = attendance_distance_meters(
        (float)$latitude,
        (float)$longitude,
        (float)$geoFence['latitude'],
        (float)$geoFence['longitude']
    );
    $withinFence = $distance <= (float)$geoFence['radius_meters'];
    if (!$withinFence) {
        $status = 'geo-fail';
    }
} elseif ($gpsRequired) {
    $status = 'geo-fail';
}

$maxAccuracy = SystemConfig::number('attendance.min_accuracy_m', 50);
if (in_array($status, ['present', 'late'], true) && $accuracy !== false && $maxAccuracy > 0 && (float)$accuracy > $maxAccuracy) {
    $status = 'geo-fail';
}

$storedLatitude = $latitude !== false ? (float)$latitude : null;
$storedLongitude = $longitude !== false ? (float)$longitude : null;
$storedAccuracy = $accuracy !== false ? (float)$accuracy : null;

$raceCheck = AttendanceRecord::todayForAnyTarget($userId);
if ($raceCheck) {
    attendance_already_signed_response($raceCheck, $project);
}

try {
    Database::query(
        'INSERT INTO attendance_records
            (user_id, project_id, work_location_id, role_at_signin, gateway_id, date, signin_time, latitude, longitude, distance_from_site_m, accuracy_meters, status, review_status)
         VALUES (?, ?, ?, ?, ?, ?, CURTIME(), ?, ?, ?, ?, ?, ?)',
        [
            $userId,
            $projectId,
            $workLocationId,
            $role,
            (int)$gateway['id'],
            $date,
            $storedLatitude,
            $storedLongitude,
            $distance,
            $storedAccuracy,
            $status,
            in_array($status, ['present', 'late'], true) ? 'accepted' : 'pending',
        ]
    );
} catch (Throwable $e) {
    $existing = AttendanceRecord::todayForAnyTarget($userId);
    if ($existing) {
        attendance_already_signed_response($existing, $project);
    }
    Response::json(['success' => false, 'message' => 'Attendance could not be recorded. Please try again.'], 500);
}

$recordId = (int)Database::lastInsertId();
Logger::log('sign_in', 'attendance_records', $recordId, [
    'target_type' => $targetType,
    'target_id' => $targetId,
    'status' => $status,
    'self_only' => true,
]);

$attendance = Database::fetch('SELECT * FROM attendance_records WHERE id = ?', [$recordId]);
$payload = attendance_response_payload($attendance ?: []);

Response::json([
    'success' => true,
    'message' => $status === 'present'
        ? 'Attendance sign-in recorded.'
        : ($status === 'late' ? 'Signed in late (within policy leeway).' : 'Attendance sign-in recorded and flagged for review.'),
    'project' => $project,
    'target_type' => $targetType,
    'target_id' => $targetId,
    'within_fence' => $withinFence,
    'distance_from_site_m' => $distance,
    'status' => $status,
    'status_label' => $payload['status_label'],
    'status_badge' => $payload['status_badge'],
    'distance_label' => $payload['distance_label'],
    'accuracy_label' => $payload['accuracy_label'],
    'signed_at_label' => $payload['signed_at_label'],
    'policy' => $policy,
    'attendance' => $payload,
]);

function clerk_attendance_default_target(int $userId, string $type, int $id): array
{
    if ($type === 'project' && $id > 0 && ClerkAttendance::canAccessProject($userId, $id)) {
        $project = ClerkAttendance::project($userId, $id);
        return ['type' => 'project', 'id' => $id, 'target' => $project];
    }
    $projectId = ClerkAttendance::defaultProjectId($userId, $id);
    $project = $projectId > 0 ? ClerkAttendance::project($userId, $projectId) : null;
    return ['type' => 'project', 'id' => $projectId, 'target' => $project];
}

function attendance_already_signed_response(array $alreadySigned, array $project): never
{
    $payload = attendance_response_payload($alreadySigned);
    $signedTarget = (string)($alreadySigned['target_name'] ?? 'your attendance location');
    Response::json([
        'success' => true,
        'message' => 'You already signed in today at ' . $signedTarget . '. Only one sign-in per person per day is allowed.',
        'project' => $project,
        'attendance' => $payload,
        'status_label' => $payload['status_label'],
        'status_badge' => $payload['status_badge'],
        'distance_label' => $payload['distance_label'],
        'accuracy_label' => $payload['accuracy_label'],
        'already_signed' => true,
    ]);
}

function attendance_distance_meters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371000;
    $latFrom = deg2rad($lat1);
    $latTo = deg2rad($lat2);
    $latDelta = deg2rad($lat2 - $lat1);
    $lngDelta = deg2rad($lng2 - $lng1);
    $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 1);
}

function attendance_response_payload(array $record): array
{
    $status = (string)($record['status'] ?? 'pending');
    $distance = isset($record['distance_from_site_m']) && $record['distance_from_site_m'] !== null
        ? number_format((float)$record['distance_from_site_m'], 1) . ' m'
        : '-';
    $accuracy = isset($record['accuracy_meters']) && $record['accuracy_meters'] !== null
        ? number_format((float)$record['accuracy_meters'], 0) . ' m'
        : '-';
    $signedAt = !empty($record['signin_time']) ? date('H:i', strtotime((string)$record['signin_time'])) : '-';

    return array_merge($record, [
        'status_label' => attendance_status_label($status),
        'status_badge' => attendance_status_badge($status),
        'distance_label' => $distance,
        'accuracy_label' => $accuracy,
        'signed_at_label' => $signedAt,
    ]);
}

function attendance_status_label(string $status): string
{
    return match ($status) {
        'present' => 'Present',
        'late' => 'Late',
        'geo-fail' => 'Location review',
        'outside-window' => 'Outside window',
        default => 'Pending review',
    };
}

function attendance_status_badge(string $status): string
{
    return match ($status) {
        'present' => 'success',
        'late' => 'warning',
        'geo-fail', 'outside-window' => 'danger',
        default => 'muted',
    };
}
