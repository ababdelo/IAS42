<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
requireAuthentication();
header('Content-Type: application/json; charset=UTF-8');

function historyJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function boolValue(mixed $value): bool
{
    return $value === true || $value === 1 || $value === '1';
}

try {
    $conn = getDb();
    $userId = (int)$_SESSION['user_id'];
    $days = max(1, min(30, (int)($_GET['days'] ?? 7)));

    $fieldStmt = $conn->prepare('SELECT id, name FROM FIELDS WHERE user_id = ? ORDER BY name ASC');
    $fieldStmt->execute([$userId]);
    $fields = $fieldStmt->fetchAll(PDO::FETCH_ASSOC);

    $eventRows = [];

    $fieldEvents = $conn->prepare("
        SELECT id, name, created_at
        FROM FIELDS
        WHERE user_id = ?
          AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $days DAY)
        ORDER BY created_at DESC
    ");
    $fieldEvents->execute([$userId]);
    foreach ($fieldEvents->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $eventRows[] = [
            'id' => 'field-' . $row['id'],
            'timestamp' => $row['created_at'],
            'field_id' => (int)$row['id'],
            'field_name' => $row['name'],
            'type' => 'update',
            'icon' => 'ri-seedling-line',
            'title_key' => 'history.events.field_created.title',
            'description_key' => 'history.events.field_created.description',
            'params' => ['field' => $row['name']],
        ];
    }

    $sectorEvents = $conn->prepare("
        SELECT s.id, s.name, s.created_at, f.id AS field_id, f.name AS field_name
        FROM SECTORS s
        INNER JOIN FIELDS f ON f.id = s.field_id
        WHERE f.user_id = ?
          AND s.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $days DAY)
        ORDER BY s.created_at DESC
    ");
    $sectorEvents->execute([$userId]);
    foreach ($sectorEvents->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $eventRows[] = [
            'id' => 'sector-' . $row['id'],
            'timestamp' => $row['created_at'],
            'field_id' => (int)$row['field_id'],
            'field_name' => $row['field_name'],
            'type' => 'update',
            'icon' => 'ri-layout-grid-line',
            'title_key' => 'history.events.sector_created.title',
            'description_key' => 'history.events.sector_created.description',
            'params' => ['sector' => $row['name'], 'field' => $row['field_name']],
        ];
    }

    $nodeEvents = $conn->prepare("
        SELECT n.id, n.node_id, n.created_at, f.id AS field_id, f.name AS field_name, s.name AS sector_name
        FROM IOT_NODES n
        INNER JOIN SECTORS s ON s.id = n.sector_id
        INNER JOIN FIELDS f ON f.id = s.field_id
        WHERE f.user_id = ?
          AND n.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $days DAY)
        ORDER BY n.created_at DESC
    ");
    $nodeEvents->execute([$userId]);
    foreach ($nodeEvents->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $eventRows[] = [
            'id' => 'node-' . $row['id'],
            'timestamp' => $row['created_at'],
            'field_id' => (int)$row['field_id'],
            'field_name' => $row['field_name'],
            'type' => 'update',
            'icon' => 'ri-router-line',
            'title_key' => 'history.events.node_registered.title',
            'description_key' => 'history.events.node_registered.description',
            'params' => ['node' => $row['node_id'], 'sector' => $row['sector_name']],
        ];
    }

    $telemetryStmt = $conn->prepare("
        SELECT
            t.id,
            t.node_id,
            t.air_temperature,
            t.air_humidity,
            t.soil_moisture,
            t.soil_ph,
            t.is_raining,
            t.pump_status,
            t.created_at,
            f.id AS field_id,
            f.name AS field_name,
            s.name AS sector_name
        FROM SENSOR_TELEMETRY t
        INNER JOIN IOT_NODES n ON n.node_id = t.node_id
        INNER JOIN SECTORS s ON s.id = n.sector_id
        INNER JOIN FIELDS f ON f.id = s.field_id
        WHERE f.user_id = ?
          AND t.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $days DAY)
        ORDER BY t.node_id ASC, t.created_at ASC, t.id ASC
    ");
    $telemetryStmt->execute([$userId]);
    $rows = $telemetryStmt->fetchAll(PDO::FETCH_ASSOC);

    $previous = [];
    foreach ($rows as $row) {
        $node = (string)$row['node_id'];
        $current = [
            'temperature' => is_numeric($row['air_temperature']) ? (float)$row['air_temperature'] : null,
            'moisture' => is_numeric($row['soil_moisture']) ? (float)$row['soil_moisture'] : null,
            'rain' => boolValue($row['is_raining']),
            'pump' => boolValue($row['pump_status']),
        ];

        if (isset($previous[$node])) {
            $old = $previous[$node];

            if ($old['pump'] !== $current['pump']) {
                $eventRows[] = [
                    'id' => 'pump-' . $row['id'],
                    'timestamp' => $row['created_at'],
                    'field_id' => (int)$row['field_id'],
                    'field_name' => $row['field_name'],
                    'type' => 'irrigation',
                    'icon' => 'ri-drop-line',
                    'title_key' => $current['pump'] ? 'history.events.irrigation_start.title' : 'history.events.irrigation_complete.title',
                    'description_key' => $current['pump'] ? 'history.events.irrigation_start.description' : 'history.events.irrigation_complete.description',
                    'params' => ['sector' => $row['sector_name'], 'node' => $node],
                ];
            }

            if (!$old['rain'] && $current['rain']) {
                $eventRows[] = [
                    'id' => 'rain-' . $row['id'],
                    'timestamp' => $row['created_at'],
                    'field_id' => (int)$row['field_id'],
                    'field_name' => $row['field_name'],
                    'type' => 'alert',
                    'icon' => 'ri-rainy-line',
                    'title_key' => 'history.events.rain.title',
                    'description_key' => 'history.events.rain.description',
                    'params' => ['sector' => $row['sector_name']],
                ];
            }

            if ($old['moisture'] !== null && $current['moisture'] !== null && $old['moisture'] >= 45 && $current['moisture'] < 45) {
                $eventRows[] = [
                    'id' => 'moisture-' . $row['id'],
                    'timestamp' => $row['created_at'],
                    'field_id' => (int)$row['field_id'],
                    'field_name' => $row['field_name'],
                    'type' => 'alert',
                    'icon' => 'ri-alert-line',
                    'title_key' => 'history.events.low_moisture.title',
                    'description_key' => 'history.events.low_moisture.description',
                    'params' => ['value' => number_format($current['moisture'], 1)],
                ];
            }

            if ($old['temperature'] !== null && $current['temperature'] !== null && $old['temperature'] < 31 && $current['temperature'] >= 31) {
                $eventRows[] = [
                    'id' => 'temperature-' . $row['id'],
                    'timestamp' => $row['created_at'],
                    'field_id' => (int)$row['field_id'],
                    'field_name' => $row['field_name'],
                    'type' => 'temperature',
                    'icon' => 'ri-temp-hot-line',
                    'title_key' => 'history.events.high_temperature.title',
                    'description_key' => 'history.events.high_temperature.description',
                    'params' => ['value' => number_format($current['temperature'], 1)],
                ];
            }
        }

        $previous[$node] = $current;
    }

    usort($eventRows, static function (array $a, array $b): int {
        return strcmp((string)$b['timestamp'], (string)$a['timestamp']);
    });

    $eventRows = array_slice($eventRows, 0, 250);

    historyJson([
        'status' => 'success',
        'days' => $days,
        'fields' => array_map(static fn(array $field): array => [
            'id' => (int)$field['id'],
            'name' => $field['name'],
        ], $fields),
        'events' => $eventRows,
    ]);
} catch (Throwable $e) {
    error_log('History API error: ' . $e->getMessage());
    historyJson([
        'status' => 'error',
        'message' => 'Failed to load activity history.'
    ], 500);
}
