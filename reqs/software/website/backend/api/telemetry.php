<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
requireAuthentication();
header('Content-Type: application/json; charset=UTF-8');

try {
    $conn = getDb();
    $userId = (int)$_SESSION['user_id'];
    $onlineWindow = 30;

    $stmt = $conn->prepare("
        SELECT
            s.id AS sector_id,
            s.name AS sector_name,
            s.crop_name,
            n.node_id,
            CASE
                WHEN n.status = 'maintenance' THEN 'maintenance'
                WHEN n.last_seen IS NOT NULL
                     AND TIMESTAMPDIFF(SECOND, n.last_seen, CURRENT_TIMESTAMP) <= ?
                    THEN 'online'
                ELSE 'offline'
            END AS node_status,
            n.last_seen,
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
        FROM FIELDS f
        INNER JOIN SECTORS s ON s.field_id = f.id
        LEFT JOIN IOT_NODES n ON n.sector_id = s.id
        LEFT JOIN SENSOR_TELEMETRY t ON t.id = (
            SELECT st.id
            FROM SENSOR_TELEMETRY st
            WHERE st.node_id = n.node_id
            ORDER BY st.id DESC
            LIMIT 1
        )
        WHERE f.user_id = ?
        ORDER BY s.name ASC
    ");

    $stmt->execute([$onlineWindow, $userId]);
    $telemetry = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($telemetry as &$row) {
        $row['is_raining'] = isset($row['is_raining']) ? (bool)$row['is_raining'] : false;
        $row['pump_status'] = isset($row['pump_status']) ? (bool)$row['pump_status'] : false;
    }
    unset($row);

    echo json_encode([
        'status' => 'success',
        'data' => $telemetry,
        'online_window' => $onlineWindow,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Telemetry API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load telemetry.'
    ]);
}
