<?php

session_start();
require_once __DIR__ . '/../config.php';

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

function qrReservationResponse(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit();
}

function ensureQrReservationTable(mysqli $conn): void
{
    $conn->query(
        'CREATE TABLE IF NOT EXISTS qr_reservations (
            token_hash CHAR(64) NOT NULL PRIMARY KEY,
            user_id INT NOT NULL,
            purpose VARCHAR(100) NOT NULL,
            reservation_start TIME NOT NULL,
            reservation_end TIME NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_qr_reservations_user (user_id),
            INDEX idx_qr_reservations_expiry (expires_at),
            CONSTRAINT fk_qr_reservations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );
}

function parseQrReservationTime(string $value): ?string
{
    $time = DateTime::createFromFormat('!g:i A', trim($value));
    $errors = DateTime::getLastErrors();

    if (!$time || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
        return null;
    }

    return $time->format('H:i:s');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        qrReservationResponse(400, ['error' => 'Invalid request.']);
    }

    try {
        ensureQrReservationTable($conn);
    } catch (Throwable $error) {
        qrReservationResponse(500, ['error' => 'QR reservations are unavailable right now.']);
    }

    if (($input['action'] ?? '') === 'create') {
        if (!isset($_SESSION['user_id'])) {
            qrReservationResponse(401, ['error' => 'Please sign in before creating a reservation QR.']);
        }

        $userId = (int) $_SESSION['user_id'];
        $purpose = trim((string) ($input['purpose'] ?? ''));
        $startTime = parseQrReservationTime((string) ($input['start_time'] ?? ''));
        $endTime = parseQrReservationTime((string) ($input['end_time'] ?? ''));
        if (!in_array($purpose, ['Enrollment', 'Payment', 'Classes', 'TESDA', 'Other'], true)
            || !$startTime || !$endTime || $startTime >= $endTime) {
            qrReservationResponse(400, ['error' => 'The reservation details are invalid.']);
        }

        $conn->begin_transaction();
        try {
            $lockUser = $conn->prepare('SELECT id, plate_number FROM users WHERE id = ? FOR UPDATE');
            $lockUser->bind_param('i', $userId);
            $lockUser->execute();
            $reservationUser = $lockUser->get_result()->fetch_assoc();
            if (!$reservationUser) {
                $lockUser->close();
                $conn->rollback();
                qrReservationResponse(401, ['error' => 'Please sign in again.']);
            }
            $lockUser->close();
            if (trim($reservationUser['plate_number']) === '') {
                $conn->rollback();
                qrReservationResponse(400, ['error' => 'Sign in again with your plate number before creating a reservation QR.']);
            }

            $activeReservation = $conn->prepare(
                'SELECT lane_number FROM parking_lanes WHERE is_reserved = 1 AND reserved_by = ? LIMIT 1'
            );
            $activeReservation->bind_param('i', $userId);
            $activeReservation->execute();
            $activeReservation->store_result();
            $hasActiveReservation = $activeReservation->num_rows > 0;
            $activeReservation->close();
            if ($hasActiveReservation) {
                $conn->rollback();
                qrReservationResponse(409, ['error' => 'Cancel your current reservation before creating a QR reservation.']);
            }

            $conn->query('DELETE FROM qr_reservations WHERE expires_at <= NOW() OR used_at IS NOT NULL');
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $createToken = $conn->prepare(
                'INSERT INTO qr_reservations (token_hash, user_id, purpose, reservation_start, reservation_end, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))'
            );
            $createToken->bind_param('sisss', $tokenHash, $userId, $purpose, $startTime, $endTime);
            $createToken->execute();
            $conn->commit();
        } catch (Throwable $error) {
            $conn->rollback();
            qrReservationResponse(500, ['error' => 'Unable to create a reservation QR right now.']);
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!preg_match('/\A[a-z0-9.-]+(?::[0-9]{1,5})?\z/i', $host)) {
            qrReservationResponse(400, ['error' => 'Unable to create a reservation link for this address.']);
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/src/qr_reservation.php')), '/');
        $reservationUrl = $scheme . '://' . $host . $directory . '/qr_reservation.php?token=' . rawurlencode($token);

        qrReservationResponse(200, [
            'url' => $reservationUrl,
            'expires_in' => 300,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => $purpose,
        ]);
    }

    if (($input['action'] ?? '') === 'claim') {
        $token = (string) ($input['token'] ?? '');
        if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            qrReservationResponse(400, ['error' => 'This reservation QR is invalid.']);
        }

        $tokenHash = hash('sha256', $token);
        $conn->begin_transaction();
        try {
            $findToken = $conn->prepare(
                'SELECT user_id, purpose, reservation_start, reservation_end FROM qr_reservations WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() FOR UPDATE'
            );
            $findToken->bind_param('s', $tokenHash);
            $findToken->execute();
            $reservation = $findToken->get_result()->fetch_assoc();
            $findToken->close();
            if (!$reservation) {
                $conn->rollback();
                qrReservationResponse(410, ['error' => 'This reservation QR has expired or was already used.']);
            }

            $userId = (int) $reservation['user_id'];
            $lockUser = $conn->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
            $lockUser->bind_param('i', $userId);
            $lockUser->execute();
            $lockUser->store_result();
            if ($lockUser->num_rows === 0) {
                $conn->rollback();
                qrReservationResponse(410, ['error' => 'The account for this reservation QR no longer exists.']);
            }
            $lockUser->close();

            $activeReservation = $conn->prepare(
                'SELECT lane_number FROM parking_lanes WHERE is_reserved = 1 AND reserved_by = ? LIMIT 1'
            );
            $activeReservation->bind_param('i', $userId);
            $activeReservation->execute();
            $activeReservation->store_result();
            $hasActiveReservation = $activeReservation->num_rows > 0;
            $activeReservation->close();
            if ($hasActiveReservation) {
                $conn->rollback();
                qrReservationResponse(409, ['error' => 'This account already has an active reservation.']);
            }

            $findLane = $conn->prepare(
                "SELECT lane_number FROM parking_lanes WHERE is_reserved = 0 AND sensor_status = 'vacant' ORDER BY lane_number LIMIT 1 FOR UPDATE"
            );
            $findLane->execute();
            $findLane->bind_result($laneNumber);
            if (!$findLane->fetch()) {
                $findLane->close();
                $conn->rollback();
                qrReservationResponse(409, ['error' => 'There are no vacant lanes available to reserve.']);
            }
            $findLane->close();

            $updateLane = $conn->prepare(
                'UPDATE parking_lanes SET is_reserved = 1, reservation_purpose = ?, reservation_start = ?, reservation_end = ?, reserved_by = ? WHERE lane_number = ? AND is_reserved = 0 AND sensor_status = \'vacant\''
            );
            $updateLane->bind_param(
                'sssii',
                $reservation['purpose'],
                $reservation['reservation_start'],
                $reservation['reservation_end'],
                $userId,
                $laneNumber
            );
            $updateLane->execute();
            if ($updateLane->affected_rows !== 1) {
                $conn->rollback();
                qrReservationResponse(409, ['error' => 'That lane is no longer vacant. Please create a new QR.']);
            }

            $markUsed = $conn->prepare('UPDATE qr_reservations SET used_at = NOW() WHERE token_hash = ? AND used_at IS NULL');
            $markUsed->bind_param('s', $tokenHash);
            $markUsed->execute();
            if ($markUsed->affected_rows !== 1) {
                $conn->rollback();
                qrReservationResponse(410, ['error' => 'This reservation QR has already been used.']);
            }

            $conn->commit();
            qrReservationResponse(200, [
                'lane_number' => (int) $laneNumber,
                'purpose' => $reservation['purpose'],
                'start_time' => $reservation['reservation_start'],
                'end_time' => $reservation['reservation_end'],
            ]);
        } catch (Throwable $error) {
            $conn->rollback();
            qrReservationResponse(500, ['error' => 'Unable to complete this reservation right now.']);
        }
    }

    qrReservationResponse(400, ['error' => 'Invalid QR reservation action.']);
}

$token = (string) ($_GET['token'] ?? '');
if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
    http_response_code(400);
    $pageMessage = 'This reservation QR is invalid.';
} else {
    $pageMessage = 'Connecting to oPark to reserve a vacant lane...';
}
$escapedToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>oPark QR Reservation</title>
    <style>
      body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; background: #f3f4f6; color: #111827; font: 16px/1.5 system-ui, sans-serif; }
      main { width: min(420px, 100%); padding: 28px; box-sizing: border-box; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; text-align: center; }
      h1 { margin: 0 0 12px; font-size: 1.35rem; }
      p { margin: 0; }
    </style>
  </head>
  <body>
    <main>
      <h1>oPark Reservation</h1>
      <p id="reservation-status" aria-live="polite"><?= htmlspecialchars($pageMessage, ENT_QUOTES, 'UTF-8') ?></p>
    </main>
    <script>
      const reservationToken = <?= json_encode($escapedToken) ?>;
      if (/^[a-f0-9]{64}$/i.test(reservationToken)) {
        fetch('./qr_reservation.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'claim', token: reservationToken }),
          cache: 'no-store',
        })
          .then(async (response) => {
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Unable to reserve a lane.');
            document.getElementById('reservation-status').textContent =
              `Lane ${result.lane_number} reserved for ${result.purpose}, ${result.start_time} to ${result.end_time}.`;
          })
          .catch((error) => {
            document.getElementById('reservation-status').textContent = error.message;
          });
      }
    </script>
  </body>
</html>