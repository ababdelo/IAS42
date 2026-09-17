<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

requireAuthentication();

header(
    'Content-Type: application/json; charset=UTF-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);

function analyticsJson(
    array $payload,
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

function isPumpOn(
    mixed $value
): bool {
    return
        $value === true ||
        $value === 1 ||
        $value === '1';
}

function conditionForTelemetry(
    ?float $moisture,
    ?float $ph
): string {

    if (
        $moisture === null &&
        $ph === null
    ) {
        return 'attention';
    }

    $critical =
        (
            $moisture !== null &&
            (
                $moisture < 20 ||
                $moisture > 90
            )
        )
        ||
        (
            $ph !== null &&
            (
                $ph < 4.5 ||
                $ph > 9.0
            )
        );

    if ($critical) {
        return 'critical';
    }

    $attention =
        (
            $moisture !== null &&
            (
                $moisture < 30 ||
                $moisture > 80
            )
        )
        ||
        (
            $ph !== null &&
            (
                $ph < 5.5 ||
                $ph > 8.0
            )
        );

    return $attention
        ? 'attention'
        : 'healthy';
}

try {
    $conn =
        getDb();

    $userId =
        (int)
        $_SESSION['user_id'];

    $days =
        max(
            1,
            min(
                30,
                (int)
                (
                    $_GET['days']
                    ?? 7
                )
            )
        );

    $fieldParam =
        $_GET['field_id']
        ?? '';

    $fieldId =
        $fieldParam !== '' &&
        $fieldParam !== 'all'
            ? (int)
              $fieldParam
            : null;

    $sectorParam =
        $_GET['sector_id']
        ?? '';

    $sectorId =
        $sectorParam !== '' &&
        $sectorParam !== 'all'
            ? (int)
              $sectorParam
            : null;

    $nodeId =
        trim(
            (string)
            (
                $_GET['node_id']
                ?? ''
            )
        );

    $nodeId =
        $nodeId === '' ||
        strtolower(
            $nodeId
        ) === 'all'
            ? null
            : $nodeId;

    $filters = [
        'f.user_id = ?'
    ];

    $params = [
        $userId
    ];

    if (
        $fieldId !== null
    ) {
        $filters[] =
            'f.id = ?';

        $params[] =
            $fieldId;
    }

    if (
        $sectorId !== null
    ) {
        $filters[] =
            's.id = ?';

        $params[] =
            $sectorId;
    }

    if (
        $nodeId !== null
    ) {
        $filters[] =
            'n.node_id = ?';

        $params[] =
            $nodeId;
    }

    $where =
        implode(
            ' AND ',
            $filters
        );

    $stmt =
        $conn->prepare(
            "
            SELECT
                t.id,
                t.node_id,
                t.air_temperature,
                t.air_humidity,
                t.soil_moisture,
                t.soil_ph,
                t.nitrogen,
                t.phosphorus,
                t.potassium,
                t.water_level,
                t.pump_status,
                t.is_raining,
                t.created_at,
                f.id AS field_id,
                f.name AS field_name,
                s.id AS sector_id,
                s.name AS sector_name,
                s.crop_name
            FROM SENSOR_TELEMETRY t
            INNER JOIN IOT_NODES n
                ON n.node_id = t.node_id
            INNER JOIN SECTORS s
                ON s.id = n.sector_id
            INNER JOIN FIELDS f
                ON f.id = s.field_id
            WHERE $where
              AND t.created_at >= DATE_SUB(
                    CURRENT_TIMESTAMP,
                    INTERVAL $days DAY
              )
            ORDER BY
                t.created_at ASC,
                t.node_id ASC,
                t.id ASC
            "
        );

    $stmt->execute(
        $params
    );

    $rows =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    $liveRows =
        array_slice(
            $rows,
            -500
        );

    $readings =
        array_map(
            static function (
                array $row
            ): array {
                return [
                    'id' =>
                        (int)
                        $row['id'],

                    'timestamp' =>
                        (string)
                        $row['created_at'],

                    'soil_moisture' =>
                        is_numeric(
                            $row[
                                'soil_moisture'
                            ]
                        )
                            ? (float)
                              $row[
                                  'soil_moisture'
                              ]
                            : null,

                    'soil_ph' =>
                        is_numeric(
                            $row['soil_ph']
                        )
                            ? (float)
                              $row[
                                  'soil_ph'
                              ]
                            : null,

                    'nitrogen' =>
                        is_numeric(
                            $row['nitrogen']
                        )
                            ? (float)
                              $row[
                                  'nitrogen'
                              ]
                            : null,

                    'phosphorus' =>
                        is_numeric(
                            $row[
                                'phosphorus'
                            ]
                        )
                            ? (float)
                              $row[
                                  'phosphorus'
                              ]
                            : null,

                    'potassium' =>
                        is_numeric(
                            $row[
                                'potassium'
                            ]
                        )
                            ? (float)
                              $row[
                                  'potassium'
                              ]
                            : null,

                    'temperature' =>
                        is_numeric(
                            $row[
                                'air_temperature'
                            ]
                        )
                            ? (float)
                              $row[
                                  'air_temperature'
                              ]
                            : null,

                    'humidity' =>
                        is_numeric(
                            $row[
                                'air_humidity'
                            ]
                        )
                            ? (float)
                              $row[
                                  'air_humidity'
                              ]
                            : null
                ];
            },
            $liveRows
        );

    $fieldStmt =
        $conn->prepare(
            '
            SELECT
                id,
                name
            FROM FIELDS
            WHERE user_id = ?
            ORDER BY name ASC
            '
        );

    $fieldStmt->execute(
        [
            $userId
        ]
    );

    $fields =
        $fieldStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    $hierarchyStmt =
        $conn->prepare(
            "
            SELECT
                s.id AS sector_id,
                s.name AS sector_name,
                s.field_id,
                n.node_id
            FROM SECTORS s
            INNER JOIN FIELDS f
                ON f.id = s.field_id
            LEFT JOIN IOT_NODES n
                ON n.sector_id = s.id
            WHERE f.user_id = ?
            ORDER BY
                s.name ASC,
                n.node_id ASC
            "
        );

    $hierarchyStmt->execute(
        [
            $userId
        ]
    );

    $hierarchyRows =
        $hierarchyStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    $daysData =
        [];

    $end =
        new DateTimeImmutable(
            'today'
        );

    for (
        $offset =
            $days - 1;
        $offset >= 0;
        $offset--
    ) {
        $date =
            $end->sub(
                new DateInterval(
                    'P' .
                    $offset .
                    'D'
                )
            );

        $key =
            $date->format(
                'Y-m-d'
            );

        $daysData[
            $key
        ] = [
            'date' =>
                $key,

            'soil_moisture' =>
                [],

            'soil_ph' =>
                [],

            'nitrogen' =>
                [],

            'phosphorus' =>
                [],

            'potassium' =>
                [],

            'temperature' =>
                [],

            'humidity' =>
                [],

            'irrigation_minutes' =>
                0.0,

            'water_level' =>
                []
        ];
    }

    $previousByNode =
        [];

    $latestBySector =
        [];

    foreach (
        $rows as $row
    ) {
        $date =
            (
                new DateTimeImmutable(
                    (string)
                    $row['created_at']
                )
            )->format(
                'Y-m-d'
            );

        if (
            !isset(
                $daysData[
                    $date
                ]
            )
        ) {
            continue;
        }

        $soil =
            is_numeric(
                $row[
                    'soil_moisture'
                ]
            )
                ? (float)
                  $row[
                      'soil_moisture'
                  ]
                : null;

        $ph =
            is_numeric(
                $row[
                    'soil_ph'
                ]
            )
                ? (float)
                  $row[
                      'soil_ph'
                  ]
                : null;

        $nitrogen =
            is_numeric(
                $row[
                    'nitrogen'
                ]
            )
                ? (float)
                  $row[
                      'nitrogen'
                  ]
                : null;

        $phosphorus =
            is_numeric(
                $row[
                    'phosphorus'
                ]
            )
                ? (float)
                  $row[
                      'phosphorus'
                  ]
                : null;

        $potassium =
            is_numeric(
                $row[
                    'potassium'
                ]
            )
                ? (float)
                  $row[
                      'potassium'
                  ]
                : null;

        $temperature =
            is_numeric(
                $row[
                    'air_temperature'
                ]
            )
                ? (float)
                  $row[
                      'air_temperature'
                  ]
                : null;

        $humidity =
            is_numeric(
                $row[
                    'air_humidity'
                ]
            )
                ? (float)
                  $row[
                      'air_humidity'
                  ]
                : null;

        $water =
            is_numeric(
                $row[
                    'water_level'
                ]
            )
                ? (float)
                  $row[
                      'water_level'
                  ]
                : null;

        if (
            $soil !== null
        ) {
            $daysData[
                $date
            ][
                'soil_moisture'
            ][] =
                $soil;
        }

        if (
            $ph !== null
        ) {
            $daysData[
                $date
            ][
                'soil_ph'
            ][] =
                $ph;
        }

        if (
            $nitrogen !== null
        ) {
            $daysData[
                $date
            ][
                'nitrogen'
            ][] =
                $nitrogen;
        }

        if (
            $phosphorus !== null
        ) {
            $daysData[
                $date
            ][
                'phosphorus'
            ][] =
                $phosphorus;
        }

        if (
            $potassium !== null
        ) {
            $daysData[
                $date
            ][
                'potassium'
            ][] =
                $potassium;
        }

        if (
            $temperature !== null
        ) {
            $daysData[
                $date
            ][
                'temperature'
            ][] =
                $temperature;
        }

        if (
            $humidity !== null
        ) {
            $daysData[
                $date
            ][
                'humidity'
            ][] =
                $humidity;
        }

        if (
            $water !== null
        ) {
            $daysData[
                $date
            ][
                'water_level'
            ][] =
                $water;
        }

        $node =
            (string)
            $row[
                'node_id'
            ];

        $currentTimestamp =
            strtotime(
                (string)
                $row[
                    'created_at'
                ]
            );

        if (
            isset(
                $previousByNode[
                    $node
                ]
            )
        ) {
            $previous =
                $previousByNode[
                    $node
                ];

            $delta =
                $currentTimestamp -
                $previous[
                    'timestamp'
                ];

            if (
                $previous[
                    'pump'
                ] &&
                $delta > 0 &&
                $delta <= 300
            ) {
                $daysData[
                    $date
                ][
                    'irrigation_minutes'
                ] +=
                    $delta /
                    60;
            }
        }

        $previousByNode[
            $node
        ] = [
            'timestamp' =>
                $currentTimestamp,

            'pump' =>
                isPumpOn(
                    $row[
                        'pump_status'
                    ]
                )
        ];

        $latestBySector[
            (int)
            $row[
                'sector_id'
            ]
        ] =
            $row;
    }

    $average =
        static function (
            array $values
        ): ?float {

            if (
                !$values
            ) {
                return null;
            }

            return
                array_sum(
                    $values
                ) /
                count(
                    $values
                );
        };

    $series =
        [];

    foreach (
        $daysData as $day
    ) {
        $series[] = [
            'date' =>
                $day['date'],

            'soil_moisture' =>
                $average(
                    $day[
                        'soil_moisture'
                    ]
                ),

            'soil_ph' =>
                $average(
                    $day[
                        'soil_ph'
                    ]
                ),

            'nitrogen' =>
                $average(
                    $day[
                        'nitrogen'
                    ]
                ),

            'phosphorus' =>
                $average(
                    $day[
                        'phosphorus'
                    ]
                ),

            'potassium' =>
                $average(
                    $day[
                        'potassium'
                    ]
                ),

            'temperature' =>
                $average(
                    $day[
                        'temperature'
                    ]
                ),

            'humidity' =>
                $average(
                    $day[
                        'humidity'
                    ]
                ),

            'water_level' =>
                $average(
                    $day[
                        'water_level'
                    ]
                ),

            'irrigation_minutes' =>
                round(
                    $day[
                        'irrigation_minutes'
                    ],
                    2
                )
        ];
    }

    $condition = [
        'healthy' =>
            0,

        'attention' =>
            0,

        'critical' =>
            0
    ];

    foreach (
        $latestBySector as $row
    ) {
        $status =
            conditionForTelemetry(
                is_numeric(
                    $row[
                        'soil_moisture'
                    ]
                )
                    ? (float)
                      $row[
                          'soil_moisture'
                      ]
                    : null,

                is_numeric(
                    $row[
                        'soil_ph'
                    ]
                )
                    ? (float)
                      $row[
                          'soil_ph'
                      ]
                    : null
            );

        $condition[
            $status
        ]++;
    }

    $selectedScope = [
        'field_name' =>
            null,

        'sector_name' =>
            null,

        'node_id' =>
            $nodeId
    ];

    foreach (
        $rows as $row
    ) {
        if (
            $fieldId !== null &&
            (int)
            $row[
                'field_id'
            ] ===
            $fieldId
        ) {
            $selectedScope[
                'field_name'
            ] =
                $row[
                    'field_name'
                ];
        }

        if (
            $sectorId !== null &&
            (int)
            $row[
                'sector_id'
            ] ===
            $sectorId
        ) {
            $selectedScope[
                'sector_name'
            ] =
                $row[
                    'sector_name'
                ];
        }

        if (
            $selectedScope[
                'field_name'
            ] !== null ||
            $selectedScope[
                'sector_name'
            ] !== null ||
            $nodeId !== null
        ) {
            break;
        }
    }

    $sectors =
        [];

    foreach (
        $hierarchyRows
        as $item
    ) {
        $sectorKey =
            (string)
            $item[
                'sector_id'
            ];

        if (
            !isset(
                $sectors[
                    $sectorKey
                ]
            )
        ) {
            $sectors[
                $sectorKey
            ] = [
                'id' =>
                    (int)
                    $item[
                        'sector_id'
                    ],

                'name' =>
                    $item[
                        'sector_name'
                    ],

                'field_id' =>
                    (int)
                    $item[
                        'field_id'
                    ],

                'nodes' =>
                    []
            ];
        }

        if (
            !empty(
                $item[
                    'node_id'
                ]
            )
        ) {
            $sectors[
                $sectorKey
            ][
                'nodes'
            ][] =
                $item[
                    'node_id'
                ];
        }
    }

    analyticsJson([
        'status' =>
            'success',

        'days' =>
            $days,

        'series' =>
            $series,

        'readings' =>
            $readings,

        'condition' =>
            $condition,

        'scope' =>
            $selectedScope,

        'filters' => [
            'fields' =>
                array_map(
                    static fn(
                        array $field
                    ): array => [
                        'id' =>
                            (int)
                            $field[
                                'id'
                            ],

                        'name' =>
                            $field[
                                'name'
                            ]
                    ],

                    $fields
                ),

            'sectors' =>
                array_values(
                    $sectors
                )
        ],

        'meta' => [
            'telemetry_rows' =>
                count(
                    $rows
                ),

            'sectors_with_data' =>
                count(
                    $latestBySector
                )
        ]
    ]);
} catch (
    Throwable $e
) {
    error_log(
        'Analytics API error: ' .
        $e->getMessage()
    );

    analyticsJson(
        [
            'status' =>
                'error',

            'message' =>
                'Failed to load analytics data.'
        ],
        500
    );
}
