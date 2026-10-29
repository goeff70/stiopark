-- Create database
CREATE DATABASE IF NOT EXISTS users_db;
USE users_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    plate_number VARCHAR(20) NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS qr_reservations (
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
);

CREATE TABLE IF NOT EXISTS reservation_expirations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lane_number TINYINT UNSIGNED NOT NULL,
    expired_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notified_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_reservation_expirations_user (user_id, notified_at),
    CONSTRAINT fk_reservation_expirations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Parking state shared by the PHP pages and ESP32.
CREATE TABLE IF NOT EXISTS parking_lanes (
    lane_number TINYINT UNSIGNED PRIMARY KEY,
    device_channel TINYINT UNSIGNED UNIQUE NULL,
    sensor_status ENUM('unknown', 'vacant', 'occupied') NOT NULL DEFAULT 'unknown',
    is_reserved TINYINT(1) NOT NULL DEFAULT 0,
    reservation_purpose VARCHAR(100) NULL,
    reservation_start TIME NULL,
    reservation_end TIME NULL,
    reserved_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_parking_lanes_reserved_by
        FOREIGN KEY (reserved_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO parking_lanes (lane_number, device_channel, sensor_status) VALUES
    (1, 1, 'unknown'),
    (2, 2, 'unknown'),
    (3, 3, 'unknown'),
    (4, 4, 'unknown'),
    (5, NULL, 'unknown');
