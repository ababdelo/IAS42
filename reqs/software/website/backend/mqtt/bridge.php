<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/credLoader.php';
require_once __DIR__ . '/../includes/db_conn.php';

$composerAutoload =
    '/var/www/composer/vendor/autoload.php';

if (
    !is_readable(
        $composerAutoload
    )
) {
    fwrite(
        STDERR,
        "[FATAL] Composer autoloader not found: {$composerAutoload}\n"
    );

    exit(1);
}

require_once $composerAutoload;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;

$env =
    loadEnv();

$brokerHost =
    trim(
        $env[
            'MQTT_BROKER_HOST'
        ]
        ?? 'broker.hivemq.com'
    );

$brokerPort =
    (int)
    (
        $env[
            'MQTT_BROKER_PORT'
        ]
        ?? 1883
    );

$topic =
    trim(
        $env[
            'MQTT_TELEMETRY_TOPIC'
        ]
        ?? 'ias42/v1/nodes/+/telemetry'
    );

$clientIdBase =
    trim(
        $env[
            'MQTT_BRIDGE_CLIENT_ID'
        ]
        ?? 'IAS42_MQTT_BRIDGE'
    );

$hostName =
    preg_replace(
        '/[^A-Za-z0-9_-]/',
        '_',
        gethostname()
        ?: 'bridge'
    );

$clientId =
    $clientIdBase .
    '_' .
    $hostName .
    '_' .
    bin2hex(
        random_bytes(
            4
        )
    );

$reconnectDelay =
    max(
        2,
        (int)
        (
            $env[
                'MQTT_RECONNECT_DELAY'
            ]
            ?? 5
        )
    );

if (
    $brokerHost === '' ||
    $brokerPort <= 0 ||
    $topic === ''
) {
    fwrite(
        STDERR,
        "[FATAL] Invalid MQTT configuration.\n"
    );

    exit(1);
}

function bridgeLog(
    string $message
): void {
    echo
        '[' .
        date(
            'Y-m-d H:i:s'
        ) .
        "] {$message}\n";
}

function extractNodeIdFromTopic(
    string $topic
): ?string {
    $prefix =
        'ias42/v1/nodes/';

    $suffix =
        '/telemetry';

    if (
        !str_starts_with(
            $topic,
            $prefix
        ) ||
        !str_ends_with(
            $topic,
            $suffix
        )
    ) {
        return null;
    }

    $nodeId =
        substr(
            $topic,
            strlen(
                $prefix
            ),
            -strlen(
                $suffix
            )
        );

    return
        $nodeId !== '' &&
        !str_contains(
            $nodeId,
            '/'
        )
            ? $nodeId
            : null;
}

function processTelemetryMessage(
    PDO $db,
    string $topic,
    string $message
): void {
    $topicNodeId =
        extractNodeIdFromTopic(
            $topic
        );

    if (
        $topicNodeId === null
    ) {
        bridgeLog(
            "[DROP] Unexpected topic: {$topic}"
        );

        return;
    }

    $envelope =
        json_decode(
            $message,
            true
        );

    if (
        !is_array(
            $envelope
        )
    ) {
        bridgeLog(
            '[DROP] Invalid outer JSON.'
        );

        return;
    }

    $payloadJson =
        $envelope[
            'payload'
        ]
        ?? null;

    $payloadNodeId =
        $envelope[
            'node_id'
        ]
        ?? null;

    $signature =
        $envelope[
            'signature'
        ]
        ?? null;

    if (
        !is_string(
            $payloadJson
        ) ||
        !is_string(
            $payloadNodeId
        ) ||
        !is_string(
            $signature
        )
    ) {
        bridgeLog(
            '[DROP] Invalid telemetry envelope structure.'
        );

        return;
    }

    if (
        !hash_equals(
            $topicNodeId,
            $payloadNodeId
        )
    ) {
        bridgeLog(
            "[DROP] Topic node '{$topicNodeId}' does not match payload node '{$payloadNodeId}'."
        );

        return;
    }

    $payload =
        json_decode(
            $payloadJson,
            true
        );

    if (
        !is_array(
            $payload
        )
    ) {
        bridgeLog(
            "[DROP] Invalid inner JSON for node {$topicNodeId}."
        );

        return;
    }

    try {
        $nodeStmt =
            $db->prepare(
                '
                SELECT
                    id,
                    node_id,
                    secret_hash
                FROM IOT_NODES
                WHERE node_id = ?
                LIMIT 1
                '
            );

        $nodeStmt->execute(
            [
                $topicNodeId
            ]
        );

        $node =
            $nodeStmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (
            !$node
        ) {
            bridgeLog(
                "[DROP] Unknown node: {$topicNodeId}"
            );

            return;
        }

        $secret =
            (string)
            (
                $node[
                    'secret_hash'
                ]
                ?? ''
            );

        if (
            $secret === ''
        ) {
            bridgeLog(
                "[DROP] Node {$topicNodeId} has no configured secret."
            );

            return;
        }

        $expectedSignature =
            hash_hmac(
                'sha256',
                $payloadJson,
                $secret
            );

        if (
            !hash_equals(
                $expectedSignature,
                strtolower(
                    $signature
                )
            )
        ) {
            bridgeLog(
                "[DROP] Invalid signature for node {$topicNodeId}."
            );

            return;
        }

        $temperature =
            is_numeric(
                $payload[
                    'temperature'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'temperature'
                  ]
                : 0.0;

        $humidity =
            is_numeric(
                $payload[
                    'humidity'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'humidity'
                  ]
                : 0.0;

        $soilMoisture =
            is_numeric(
                $payload[
                    'soil_moisture'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'soil_moisture'
                  ]
                : 0.0;

        $ph =
            is_numeric(
                $payload[
                    'ph'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'ph'
                  ]
                : 0.0;

        $nitrogen =
            is_numeric(
                $payload[
                    'nitrogen'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'nitrogen'
                  ]
                : 0.0;

        $phosphorus =
            is_numeric(
                $payload[
                    'phosphorus'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'phosphorus'
                  ]
                : 0.0;

        $potassium =
            is_numeric(
                $payload[
                    'potassium'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'potassium'
                  ]
                : 0.0;

        $waterLevel =
            is_numeric(
                $payload[
                    'water_level'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'water_level'
                  ]
                : 0.0;

        $isRaining =
            filter_var(
                $payload[
                    'rain'
                ]
                ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
                ? 1
                : 0;

        $windSpeed =
            is_numeric(
                $payload[
                    'wind_speed'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'wind_speed'
                  ]
                : 0.0;

        $brightness =
            is_numeric(
                $payload[
                    'brightness'
                ]
                ?? null
            )
                ? (float)
                  $payload[
                      'brightness'
                  ]
                : 0.0;

        $pumpStatus =
            filter_var(
                $payload[
                    'pump_status'
                ]
                ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
                ? 1
                : 0;

        $db->beginTransaction();

        $insert =
            $db->prepare(
                '
                INSERT INTO SENSOR_TELEMETRY (
                    node_id,
                    air_temperature,
                    air_humidity,
                    soil_moisture,
                    soil_ph,
                    nitrogen,
                    phosphorus,
                    potassium,
                    water_level,
                    is_raining,
                    wind_speed,
                    brightness,
                    pump_status
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
                '
            );

        $insert->execute(
            [
                $topicNodeId,
                $temperature,
                $humidity,
                $soilMoisture,
                $ph,
                $nitrogen,
                $phosphorus,
                $potassium,
                $waterLevel,
                $isRaining,
                $windSpeed,
                $brightness,
                $pumpStatus
            ]
        );

        $update =
            $db->prepare(
                "
                UPDATE IOT_NODES
                SET
                    status = CASE
                        WHEN status = 'maintenance'
                            THEN status
                        ELSE 'online'
                    END,
                    last_seen =
                        CURRENT_TIMESTAMP
                WHERE node_id = ?
                "
            );

        $update->execute(
            [
                $topicNodeId
            ]
        );

        $db->commit();

        bridgeLog(
            sprintf(
                '[OK] %s stored | temp=%.1f°C hum=%.1f%% moisture=%.1f%% pH=%.1f water=%.1fcm pump=%s',
                $topicNodeId,
                $temperature,
                $humidity,
                $soilMoisture,
                $ph,
                $waterLevel,
                $pumpStatus
                    ? 'ON'
                    : 'OFF'
            )
        );
    } catch (
        Throwable $e
    ) {
        if (
            $db->inTransaction()
        ) {
            $db->rollBack();
        }

        bridgeLog(
            '[ERROR] Database processing failed: ' .
            $e->getMessage()
        );
    }
}

bridgeLog(
    "Starting MQTT bridge | broker={$brokerHost}:{$brokerPort} | topic={$topic}"
);

while (
    true
) {
    try {
        $db =
            getDbConnection();

        $mqtt =
            new MqttClient(
                $brokerHost,
                $brokerPort,
                $clientId,
                MqttClient::MQTT_3_1_1
            );

        bridgeLog(
            "Connecting to MQTT broker with client ID {$clientId}..."
        );

        $mqtt->connect(
            null,
            true
        );

        bridgeLog(
            'MQTT connected. Subscribing...'
        );

        $mqtt->subscribe(
            $topic,
            function (
                string $receivedTopic,
                string $receivedMessage
            ): void {
                try {
                    processTelemetryMessage(
                        getDbConnection(),
                        $receivedTopic,
                        $receivedMessage
                    );
                } catch (
                    Throwable $e
                ) {
                    bridgeLog(
                        '[ERROR] Could not open database connection: ' .
                        $e->getMessage()
                    );
                }
            },
            0
        );

        bridgeLog(
            "Subscribed to {$topic}. Waiting for telemetry..."
        );

        $mqtt->loop(
            true
        );

        $mqtt->disconnect();

        bridgeLog(
            'MQTT loop ended. Reconnecting...'
        );
    } catch (
        MqttClientException $e
    ) {
        bridgeLog(
            '[MQTT ERROR] ' .
            $e->getMessage()
        );
    } catch (
        Throwable $e
    ) {
        bridgeLog(
            '[FATAL LOOP ERROR] ' .
            $e->getMessage()
        );
    }

    sleep(
        $reconnectDelay
    );
}
