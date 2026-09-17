<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/apiAuth.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Method not allowed. Use GET or POST.'
    ], 405);
}

requireMobileApiToken();

try {
    $conn = getDb();

    /**
     * POST JSON example:
     * {
     *   "user_id": 1,
     *   "field_id": 1,
     *   "sector_id": 1,
     *   "node_id": "A84F92"
     * }
     *
     * GET example:
     * /api/mobile/data?user_id=1&field_id=1&sector_id=1&node_id=A84F92
     *
     * user_id is treated as a selector, not as authentication.
     * The shared API token is what authorizes access to this endpoint.
     */
    $input = readRequestInput();

    $userId = parsePositiveInt($input['user_id'] ?? null);
    $fieldId = parsePositiveInt($input['field_id'] ?? null);
    $sectorId = parsePositiveInt($input['sector_id'] ?? null);
    $nodeId = trim((string)($input['node_id'] ?? ''));

    if ($userId === null || $fieldId === null || $sectorId === null || $nodeId === '') {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'user_id, field_id, sector_id, and node_id are required.'
        ], 400);
    }

    $onlineWindow = max(1, (int)(loadEnv()['MQTT_NODE_ONLINE_WINDOW'] ?? 30));

    $stmt = $conn->prepare("
        SELECT
            u.id AS user_id,
            u.username,

            f.id AS field_id,
            f.name AS field_name,
            f.location AS field_location,
            f.area AS field_area,

            s.id AS sector_id,
            s.name AS sector_name,
            s.crop_name,
            s.description AS sector_description,

            n.id AS node_db_id,
            n.node_id,
            n.name AS node_name,
            n.mac_address,
            n.status AS stored_node_status,
            n.last_seen,

            CASE
                WHEN n.status = 'maintenance' THEN 'maintenance'
                WHEN n.last_seen IS NOT NULL
                     AND TIMESTAMPDIFF(SECOND, n.last_seen, CURRENT_TIMESTAMP) <= ?
                    THEN 'online'
                ELSE 'offline'
            END AS node_status,

            t.air_temperature,
            t.air_humidity,
            t.soil_moisture,
            t.soil_ph,
            t.nitrogen,
            t.phosphorus,
            t.potassium,
            t.water_level,
            t.is_raining,
            t.wind_speed,
            t.brightness,
            t.pump_status,
            t.created_at AS telemetry_at

        FROM USERS u
        INNER JOIN FIELDS f
            ON f.user_id = u.id
        INNER JOIN SECTORS s
            ON s.field_id = f.id
        INNER JOIN IOT_NODES n
            ON n.sector_id = s.id
        LEFT JOIN SENSOR_TELEMETRY t
            ON t.id = (
                SELECT st.id
                FROM SENSOR_TELEMETRY st
                WHERE st.node_id = n.node_id
                ORDER BY st.id DESC
                LIMIT 1
            )
        WHERE u.id = ?
          AND f.id = ?
          AND s.id = ?
          AND n.node_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $onlineWindow,
        $userId,
        $fieldId,
        $sectorId,
        $nodeId
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'The requested user, field, sector, or IoT node was not found, or the relationship is invalid.'
        ], 404);
    }

    $response = [
        'status' => 'success',
        'data' => [
            'user' => [
                'id' => (int)$row['user_id'],
                'username' => $row['username'],
            ],
            'field' => [
                'id' => (int)$row['field_id'],
                'name' => $row['field_name'],
                'location' => $row['field_location'],
                'area' => toNullableFloat($row['field_area']),
            ],
            'sector' => [
                'id' => (int)$row['sector_id'],
                'name' => $row['sector_name'],
                'crop' => $row['crop_name'],
                'description' => $row['sector_description'],
            ],
            'node' => [
                'id' => (int)$row['node_db_id'],
                'node_id' => $row['node_id'],
                'name' => $row['node_name'],
                'mac_address' => $row['mac_address'],
                'status' => $row['node_status'],
                'last_seen' => $row['last_seen'],
            ],
            'telemetry' => [
                'temperature' => toNullableFloat($row['air_temperature']),
                'humidity' => toNullableFloat($row['air_humidity']),
                'soil_moisture' => toNullableFloat($row['soil_moisture']),
                'soil_ph' => toNullableFloat($row['soil_ph']),
                'nitrogen' => toNullableFloat($row['nitrogen']),
                'phosphorus' => toNullableFloat($row['phosphorus']),
                'potassium' => toNullableFloat($row['potassium']),
                'water_level' => toNullableFloat($row['water_level']),
                'rain' => isset($row['is_raining']) ? (bool)$row['is_raining'] : false,
                'wind_speed' => toNullableFloat($row['wind_speed']),
                'brightness' => toNullableFloat($row['brightness']),
                'pump_status' => isset($row['pump_status']) ? (bool)$row['pump_status'] : false,
                'timestamp' => $row['telemetry_at'],
            ],
        ],
        'meta' => [
            'online_window_seconds' => $onlineWindow,
        ],
    ];

    sendJsonResponse($response);
} catch (Throwable $e) {
    error_log('Mobile API error: ' . $e->getMessage());

    sendJsonResponse([
        'status' => 'error',
        'message' => 'Failed to load mobile data.'
    ], 500);
}

function readRequestInput(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        return $_GET;
    }

    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    $rawBody = file_get_contents('php://input') ?: '';

    if (str_contains($contentType, 'application/json')) {
        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded)) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Invalid JSON request body.'
            ], 400);
        }

        return $decoded;
    }

    return $_POST;
}

function parsePositiveInt(mixed $value): ?int
{
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }

    if (is_string($value) && preg_match('/^[1-9]\d*$/', trim($value)) === 1) {
        return (int)$value;
    }

    return null;
}

function toNullableFloat(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    return (float)$value;
}
