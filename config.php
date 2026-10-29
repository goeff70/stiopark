<?php

$host = getenv('OPARK_DB_HOST') ?: 'localhost';
$user = getenv('OPARK_DB_USER') ?: 'root';
$password = getenv('OPARK_DB_PASSWORD') ?: '';
$database = getenv('OPARK_DB_NAME') ?: 'users_db';
$deviceApiToken = getenv('OPARK_DEVICE_API_TOKEN') ?: '';

$conn = new mysqli($host, $user, $password, $database);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

$plateNumberColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'plate_number'");
if ($plateNumberColumn && $plateNumberColumn->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN plate_number VARCHAR(20) NOT NULL DEFAULT '' AFTER email");
}

$conn->query(
    'CREATE TABLE IF NOT EXISTS reservation_expirations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        lane_number TINYINT UNSIGNED NOT NULL,
        expired_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        notified_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_reservation_expirations_user (user_id, notified_at),
        CONSTRAINT fk_reservation_expirations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

?>