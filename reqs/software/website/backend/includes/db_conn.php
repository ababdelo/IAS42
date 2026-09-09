<?php
/**
 * Database connection provider.
 * This file defines a function that returns a PDO instance.
 */

require_once __DIR__ . '/credLoader.php';

/**
 * Create and return a PDO connection using environment variables.
 *
 * @return PDO
 * @throws Exception If connection fails or environment variables are missing.
 */
function getDbConnection(): PDO {
    $env = loadEnv();

    $host   = $env['PMA_HOST'] ?? 'mysql';
    $user   = $env['MYSQL_USER'] ?? 'root';
    $pass   = $env['MYSQL_PASSWORD'] ?? '';
    $dbname = $env['MYSQL_DATABASE_NAME'] ?? 'ias42_db';

    if ($host === '' || $user === '' || $dbname === '') {
        throw new Exception('Missing database environment variables.');
    }

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ];

    return new PDO($dsn, $user, $pass, $options);
}
