<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'intern', 'clerk'],
    'csrf_form' => 'attendance_signin',
]);

$projectId = Security::cleanInt($_POST['project_id'] ?? 0);
if ($projectId <= 0) {
    $assignment = Database::fetch(
        'SELECT project_id FROM project_assignments WHERE user_id = ? ORDER BY assigned_at DESC LIMIT 1',
        [(int)Auth::id()]
    );
    $projectId = (int)($assignment['project_id'] ?? 0);
}

if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'No assigned project was found for attendance sign-in.'], 422);
}

if (Auth::role() !== 'superadmin' && Database::fetch('SELECT id FROM project_assignments WHERE user_id = ? AND project_id = ? LIMIT 1', [(int)Auth::id(), $projectId]) === null) {
    Response::json(['success' => false, 'message' => 'You are not assigned to this project.'], 403);
}

$latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
$accuracy = filter_var($_POST['accuracy_meters'] ?? null, FILTER_VALIDATE_FLOAT);

if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    Response::json(['success' => false, 'message' => 'A valid GPS latitude and longitude is required.'], 422);
}

$date = date('Y-m-d');
$gateway = AttendanceGateway::forProjectDate($projectId, $date);
if (!$gateway || (int)$gateway['is_open'] !== 1) {
    Response::json(['success' => false, 'message' => 'Attendance gateway is not open for this project.'], 409);
}

$alreadySigned = AttendanceRecord::todayForUser((int)Auth::id(), $projectId);
if ($alreadySigned) {
    Response::json([
        'success' => true,
        'message' => 'You already signed in today.',
        'attendance' => $alreadySigned,
    ]);
}

$geoFence = GeoFenceModel::forProject($projectId);
$distance = null;
$withinFence = false;
$status = 'present';

if ($geoFence && ($geoFence['status'] ?? '') === 'configured') {
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
} else {
    $status = 'geo-fail';
}

if (strtotime((string)$gateway['closes_at']) < time()) {
    $status = 'outside-window';
}

Database::query(
    'INSERT INTO attendance_records
        (user_id, project_id, role_at_signin, gateway_id, date, signin_time, latitude, longitude, distance_from_site_m, accuracy_meters, status, review_status)
     VALUES (?, ?, ?, ?, ?, CURTIME(), ?, ?, ?, ?, ?, ?)',
    [
        (int)Auth::id(),
        $projectId,
        Auth::role(),
        (int)$gateway['id'],
        $date,
        (float)$latitude,
        (float)$longitude,
        $distance,
        $accuracy !== false ? $accuracy : null,
        $status,
        $status === 'present' ? 'accepted' : 'flagged',
    ]
);

$recordId = (int)Database::lastInsertId();
Logger::log('sign_in', 'attendance_records', $recordId, ['project_id' => $projectId, 'status' => $status]);

Response::json([
    'success' => true,
    'message' => $status === 'present' ? 'Attendance sign-in recorded.' : 'Attendance sign-in recorded and flagged for review.',
    'within_fence' => $withinFence,
    'distance_from_site_m' => $distance,
    'status' => $status,
    'attendance' => Database::fetch('SELECT * FROM attendance_records WHERE id = ?', [$recordId]),
]);

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
