<?php

session_start();
require_once '../config.php';
header('Cache-Control: no-store');

function apiResponse($status, $payload, $contentType = 'application/json') {
    http_response_code($status);
    header('Content-Type: ' . $contentType);
    echo is_string($payload) ? $payload : json_encode($payload);
    exit();
}

function parseReservationTime($value) {
    $time = DateTime::createFromFormat('!g:i A', trim((string) $value));
    $errors = DateTime::getLastErrors();

    if (!$time || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
        return null;
    }

    return $time->format('H:i:s');
}

function expireReservations($conn) {
    $conn->begin_transaction();
    try {
        $expiredLanes = $conn->query(
            'SELECT lane_number, reserved_by FROM parking_lanes WHERE is_reserved = 1 AND reservation_end IS NOT NULL AND reservation_end <= CURTIME() FOR UPDATE'
        );
        $addExpiration = $conn->prepare(
            'INSERT INTO reservation_expirations (user_id, lane_number) VALUES (?, ?)'
        );

        while ($lane = $expiredLanes->fetch_assoc()) {
            if ($lane['reserved_by'] !== null) {
                $userId = (int) $lane['reserved_by'];
                $laneNumber = (int) $lane['lane_number'];
                $addExpiration->bind_param('ii', $userId, $laneNumber);
                $addExpiration->execute();
            }
        }

        $conn->query(
            'UPDATE parking_lanes SET is_reserved = 0, reservation_purpose = NULL, reservation_start = NULL, reservation_end = NULL, reserved_by = NULL WHERE is_reserved = 1 AND reservation_end IS NOT NULL AND reservation_end <= CURTIME()'
        );
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

if (($_GET['device'] ?? '') === 'sync') {
    $providedToken = $_SERVER['HTTP_X_DEVICE_TOKEN'] ?? '';
    if ($deviceApiToken === '' || !hash_equals($deviceApiToken, $providedToken)) {
        apiResponse(403, 'forbidden', 'text/plain; charset=utf-8');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        apiResponse(405, 'method not allowed', 'text/plain; charset=utf-8');
    }

    expireReservations($conn);

    parse_str(file_get_contents('php://input'), $deviceInput);
    $updateSensor = $conn->prepare(
        "UPDATE parking_lanes SET sensor_status = ? WHERE device_channel = ?"
    );
    $findDeviceLane = $conn->prepare(
        'SELECT sensor_status, is_reserved, reserved_by FROM parking_lanes WHERE device_channel = ?'
    );
    $reserveDeviceLane = $conn->prepare(
        "UPDATE parking_lanes SET is_reserved = 1, reservation_purpose = 'Device reservation', reservation_start = CURTIME(), reservation_end = NULL, reserved_by = NULL WHERE device_channel = ?"
    );
    $releaseDeviceLane = $conn->prepare(
        'UPDATE parking_lanes SET is_reserved = 0, reservation_purpose = NULL, reservation_start = NULL, reservation_end = NULL, reserved_by = NULL WHERE device_channel = ?'
    );

    for ($channel = 1; $channel <= 4; $channel++) {
        $sensorStatus = $deviceInput['sensor_' . $channel] ?? '';
        if (in_array($sensorStatus, ['unknown', 'vacant', 'occupied'], true)) {
            $updateSensor->bind_param('si', $sensorStatus, $channel);
            $updateSensor->execute();
        }

        if (!isset($deviceInput['reserved_' . $channel])) {
            continue;
        }

        $desiredReservation = $deviceInput['reserved_' . $channel] === '1';
        $findDeviceLane->bind_param('i', $channel);
        $findDeviceLane->execute();
        $deviceLane = $findDeviceLane->get_result()->fetch_assoc();

        if ($desiredReservation && !$deviceLane['is_reserved'] && $deviceLane['sensor_status'] !== 'occupied') {
            $reserveDeviceLane->bind_param('i', $channel);
            $reserveDeviceLane->execute();
        } elseif (!$desiredReservation && $deviceLane['is_reserved'] && $deviceLane['reserved_by'] === null) {
            $releaseDeviceLane->bind_param('i', $channel);
            $releaseDeviceLane->execute();
        }
    }

    $result = $conn->query(
        'SELECT device_channel, is_reserved FROM parking_lanes WHERE device_channel BETWEEN 1 AND 4 ORDER BY device_channel'
    );
    $reservations = array_fill(0, 4, '0');
    while ($row = $result->fetch_assoc()) {
        $reservations[(int) $row['device_channel'] - 1] = (int) $row['is_reserved'] ? '1' : '0';
    }

    apiResponse(200, implode(',', $reservations), 'text/plain; charset=utf-8');
}

if (!isset($_SESSION['user_id'])) {
    apiResponse(401, ['error' => 'Please sign in.']);
}

$userId = (int) $_SESSION['user_id'];
$isAdmin = (($_SESSION['is_admin'] ?? false) === true);

expireReservations($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn->begin_transaction();
    $findExpirations = $conn->prepare(
        'SELECT id, lane_number FROM reservation_expirations WHERE user_id = ? AND notified_at IS NULL ORDER BY id FOR UPDATE'
    );
    $findExpirations->bind_param('i', $userId);
    $findExpirations->execute();
    $expirationResult = $findExpirations->get_result();
    $expiredReservations = [];
    $expirationIds = [];
    while ($expiration = $expirationResult->fetch_assoc()) {
        $expiredReservations[] = (int) $expiration['lane_number'];
        $expirationIds[] = (int) $expiration['id'];
    }
    $findExpirations->close();

    if ($expirationIds) {
        $markNotified = $conn->prepare(
            'UPDATE reservation_expirations SET notified_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND notified_at IS NULL'
        );
        foreach ($expirationIds as $expirationId) {
            $markNotified->bind_param('ii', $expirationId, $userId);
            $markNotified->execute();
        }
        $markNotified->close();
    }
    $conn->commit();

    $result = $conn->query(
        'SELECT parking_lanes.lane_number, parking_lanes.device_channel, parking_lanes.sensor_status, parking_lanes.is_reserved, parking_lanes.reservation_purpose, parking_lanes.reservation_start, parking_lanes.reservation_end, parking_lanes.reserved_by, users.name AS reserved_by_name, users.plate_number AS reserved_by_plate FROM parking_lanes LEFT JOIN users ON users.id = parking_lanes.reserved_by ORDER BY parking_lanes.lane_number'
    );
    $lanes = [];

    while ($row = $result->fetch_assoc()) {
        $row['lane_number'] = (int) $row['lane_number'];
        $row['device_channel'] = $row['device_channel'] === null ? null : (int) $row['device_channel'];
        $row['is_reserved'] = (bool) $row['is_reserved'];
        $row['reserved_by'] = $row['reserved_by'] === null ? null : (int) $row['reserved_by'];
        $row['is_mine'] = $row['reserved_by'] === $userId;
        $row['can_cancel'] = $isAdmin || $row['is_mine'];
        $row['reserved_by_name'] = $isAdmin && $row['reserved_by'] !== null
            ? $row['reserved_by_name']
            : null;
        $row['plate_number'] = ($isAdmin || $row['is_mine']) && $row['reserved_by'] !== null
            ? $row['reserved_by_plate']
            : null;
        unset($row['reserved_by_plate']);
        $lanes[] = $row;
    }

    apiResponse(200, [
        'lanes' => $lanes,
        'expired_reservations' => $expiredReservations,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(405, ['error' => 'Method not allowed.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    apiResponse(400, ['error' => 'Invalid request body.']);
}

$action = $input['action'] ?? '';
$laneNumber = filter_var($input['lane_number'] ?? null, FILTER_VALIDATE_INT);
if (!$laneNumber || $laneNumber < 1 || $laneNumber > 5) {
    apiResponse(400, ['error' => 'Invalid lane.']);
}

if ($action === 'reserve') {
    $findPlate = $conn->prepare('SELECT plate_number FROM users WHERE id = ?');
    $findPlate->bind_param('i', $userId);
    $findPlate->execute();
    $plateRow = $findPlate->get_result()->fetch_assoc();
    if (!$plateRow || trim($plateRow['plate_number']) === '') {
        apiResponse(400, [
            'error' => 'Sign in again with your plate number before reserving a lane.',
            'code' => 'plate_required',
        ]);
    }
}

$conn->begin_transaction();
if ($action === 'reserve' && !$isAdmin) {
    $lockUser = $conn->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
    $lockUser->bind_param('i', $userId);
    $lockUser->execute();
    $lockUser->bind_result($lockedUserId);
    $lockUser->fetch();
    $lockUser->close();

    $findActiveReservation = $conn->prepare(
        'SELECT lane_number FROM parking_lanes WHERE is_reserved = 1 AND reserved_by = ? LIMIT 1'
    );
    $findActiveReservation->bind_param('i', $userId);
    $findActiveReservation->execute();
    $activeReservations = $findActiveReservation->get_result();
    $hasActiveReservation = $activeReservations->num_rows > 0;
    $activeReservations->free();
    $findActiveReservation->close();

    if ($hasActiveReservation) {
        $conn->rollback();
        apiResponse(409, [
            'error' => 'You can reserve only one lane at a time. Cancel your current reservation before choosing another.',
            'code' => 'one_lane_limit',
        ]);
    }
}

$findLane = $conn->prepare(
    'SELECT sensor_status, is_reserved, reserved_by FROM parking_lanes WHERE lane_number = ? FOR UPDATE'
);
$findLane->bind_param('i', $laneNumber);
$findLane->execute();
$lane = $findLane->get_result()->fetch_assoc();

if (!$lane) {
    $conn->rollback();
    apiResponse(404, ['error' => 'Lane not found.']);
}

if ($action === 'cancel') {
    if (!$lane['is_reserved']) {
        $conn->rollback();
        apiResponse(409, ['error' => 'This lane is not reserved.']);
    }

    if (!$isAdmin && (int) $lane['reserved_by'] !== $userId) {
        $conn->rollback();
        apiResponse(403, ['error' => 'You can only cancel your own reservation.']);
    }

    $updateLane = $conn->prepare(
        'UPDATE parking_lanes SET is_reserved = 0, reservation_purpose = NULL, reservation_start = NULL, reservation_end = NULL, reserved_by = NULL WHERE lane_number = ?'
    );
    $updateLane->bind_param('i', $laneNumber);
    $updateLane->execute();
    $conn->commit();
    apiResponse(200, ['ok' => true]);
}

$adminSet = $action === 'admin_set';
if ($adminSet && !$isAdmin) {
    $conn->rollback();
    apiResponse(403, ['error' => 'Administrator access required.']);
}

if (!in_array($action, ['reserve', 'admin_set'], true)) {
    $conn->rollback();
    apiResponse(400, ['error' => 'Invalid action.']);
}

$shouldReserve = !$adminSet || !empty($input['reserved']);
if (!$shouldReserve) {
    if ($lane['is_reserved']) {
        $updateLane = $conn->prepare(
            'UPDATE parking_lanes SET is_reserved = 0, reservation_purpose = NULL, reservation_start = NULL, reservation_end = NULL, reserved_by = NULL WHERE lane_number = ?'
        );
        $updateLane->bind_param('i', $laneNumber);
        $updateLane->execute();
    }

    $conn->commit();
    apiResponse(200, ['ok' => true]);
}

if ($lane['is_reserved']) {
    $conn->rollback();
    apiResponse(409, ['error' => 'This lane is already reserved.']);
}

if ($lane['sensor_status'] === 'occupied') {
    $conn->rollback();
    apiResponse(409, ['error' => 'An occupied lane cannot be reserved.']);
}

$purpose = trim((string) ($input['purpose'] ?? ''));
if ($adminSet && $purpose === '') {
    $purpose = 'Admin reservation';
}

$allowedPurposes = ['Enrollment', 'Payment', 'Classes', 'TESDA', 'Other'];
if (!$adminSet && !in_array($purpose, $allowedPurposes, true)) {
    $conn->rollback();
    apiResponse(400, ['error' => 'Select a valid reservation purpose.']);
}

$startTime = parseReservationTime($input['start_time'] ?? '');
$endTime = parseReservationTime($input['end_time'] ?? '');
if (!$startTime || !$endTime || $startTime >= $endTime) {
    $conn->rollback();
    apiResponse(400, ['error' => 'Select a valid start and end time.']);
}

$updateLane = $conn->prepare(
    'UPDATE parking_lanes SET is_reserved = 1, reservation_purpose = ?, reservation_start = ?, reservation_end = ?, reserved_by = ? WHERE lane_number = ?'
);
$updateLane->bind_param('sssii', $purpose, $startTime, $endTime, $userId, $laneNumber);
$updateLane->execute();
$conn->commit();

apiResponse(200, ['ok' => true]);