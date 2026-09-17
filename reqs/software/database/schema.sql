CREATE DATABASE IF NOT EXISTS `IAS42_DB` CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `IAS42_DB`;

CREATE TABLE
    IF NOT EXISTS `USERS` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `first_name` VARCHAR(42) NOT NULL,
        `last_name` VARCHAR(42) NOT NULL,
        `username` VARCHAR(42) NOT NULL UNIQUE,
        `email` VARCHAR(128) NOT NULL UNIQUE,
        `gender` ENUM ('male', 'female', 'not specified') DEFAULT 'not specified',
        `avatarType` ENUM ('default', 'oauth') NOT NULL DEFAULT 'default',
        `avatar` VARCHAR(255) DEFAULT 'nouser.webp',
        `password` VARCHAR(255) DEFAULT NULL,
        `accountType` ENUM ('normal', 'oauth') NOT NULL DEFAULT 'normal',
        `accountStatus` ENUM ('verified', 'unverified') NOT NULL DEFAULT 'unverified',
        `termsAccepted` BOOLEAN NOT NULL DEFAULT TRUE,
        `oauthID` VARCHAR(128) UNIQUE DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_email` (`email`),
        INDEX `idx_username` (`username`),
        INDEX `idx_oauthID` (`oauthID`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    IF NOT EXISTS `AUTH_TOKENS` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `tokenType` ENUM (
            'otp',
            'passwordReset',
            'accountDelete',
            'rememberMe'
        ) NOT NULL,
        `tokenValue` VARCHAR(64) NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_token` (`tokenValue`),
        INDEX `idx_expires_at` (`expires_at`),
        UNIQUE KEY `unique_user_token_type` (`user_id`, `tokenType`),
        FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 1. FIELDS (Owned by USERS)
CREATE TABLE
    IF NOT EXISTS FIELDS (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        location VARCHAR(255),
        area DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES USERS (id) ON DELETE CASCADE
    );

-- 2. SECTORS (Owned by FIELDS)
CREATE TABLE
    IF NOT EXISTS SECTORS (
        id INT AUTO_INCREMENT PRIMARY KEY,
        field_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        crop_name VARCHAR(100) NOT NULL,
        image_path VARCHAR(255) NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (field_id) REFERENCES FIELDS (id) ON DELETE CASCADE
    );

-- 3. IOT_NODES (Assigned to SECTORS)
CREATE TABLE
    IF NOT EXISTS IOT_NODES (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sector_id INT, -- Nullable so a node can be in inventory but unassigned
        node_id VARCHAR(50) UNIQUE NOT NULL,
        secret_hash VARCHAR(255) NOT NULL,
        mac_address VARCHAR(50),
        name VARCHAR(100),
        status ENUM ('online', 'offline', 'maintenance') NOT NULL DEFAULT 'offline',
        last_seen TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_iot_nodes_last_seen (last_seen),
        FOREIGN KEY (sector_id) REFERENCES SECTORS (id) ON DELETE CASCADE
    );

-- 4. SENSOR_TELEMETRY (Tied ONLY to the node)
CREATE TABLE
    IF NOT EXISTS SENSOR_TELEMETRY (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        node_id VARCHAR(50) NOT NULL,
        air_temperature DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
        air_humidity DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
        soil_moisture DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
        soil_ph DECIMAL(4, 2) NOT NULL DEFAULT 0.00,
        nitrogen DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
        phosphorus DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
        potassium DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
        water_level DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
        is_raining BOOLEAN NOT NULL DEFAULT FALSE,
        wind_speed DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
        brightness DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
        pump_status BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sensor_telemetry_node_created (node_id, created_at, id),
        FOREIGN KEY (node_id) REFERENCES IOT_NODES (node_id) ON DELETE CASCADE
    );
