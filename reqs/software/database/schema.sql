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
