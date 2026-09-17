(() => {
  'use strict';

  const POLL_INTERVAL = 5000;
  const MAX_LIVE_POINTS = 500;
  const POINT_SPACING = 52;
  const MIN_CHART_WIDTH = 760;
  const CHART_HEIGHT = 330;
  const COMPACT_CHART_HEIGHT = 280;

  const elements = {
    field: document.getElementById('analyticsField'),
    sector: document.getElementById('analyticsSector'),
    node: document.getElementById('analyticsNode'),
    period: document.getElementById('analyticsPeriod'),
    scope: document.getElementById('analyticsScope'),
    summary: document.getElementById('analyticsSummary'),
    grid: document.querySelector('.analytics-grid'),
    soil: document.getElementById('soilChart'),
    nutrients: document.getElementById('nutrientChart'),
    environment: document.getElementById('environmentChart'),
    irrigation: document.getElementById('irrigationChart'),
    condition: document.getElementById('conditionChart'),
    conditionLegend: document.getElementById('conditionLegend'),
    empty: document.getElementById('analyticsEmpty')
  };

  if (!elements.field) {
    return;
  }

  let filters = {
    fields: [],
    sectors: []
  };

  let charts = {
    soil: null,
    nutrients: null,
    environment: null,
    irrigation: null
  };

  let lastResult = null;
  let pollTimer = null;
  let requestInFlight = false;

  const defaults = {
    moisture: 'Moisture (%)',
    ph: 'pH',
    nitrogen: 'Nitrogen',
    phosphorus: 'Phosphorus',
    potassium: 'Potassium',
    temperature: 'Temperature',
    humidity: 'Humidity',
    minutes: 'min',
    healthy: 'Healthy',
    attention: 'Needs attention',
    critical: 'Critical',
    sectors: 'sectors'
  };

  const t = (
    key,
    fallback
  ) => {
    if (
      typeof window.t ===
      'function'
    ) {
      const value =
        window.t(key);

      if (
        typeof value ===
          'string' &&
        value.trim() !== '' &&
        value !== key
      ) {
        return value;
      }
    }

    return fallback || key;
  };

  const escapeHtml = (
    value
  ) =>
    String(
      value ?? ''
    )
      .replaceAll(
        '&',
        '&amp;'
      )
      .replaceAll(
        '<',
        '&lt;'
      )
      .replaceAll(
        '>',
        '&gt;'
      )
      .replaceAll(
        '"',
        '&quot;'
      )
      .replaceAll(
        "'",
        '&#039;'
      );

  const numeric = (
    value
  ) => {
    if (
      value === null ||
      value === undefined ||
      value === ''
    ) {
      return null;
    }

    const number =
      Number(value);

    return Number.isFinite(
      number
    )
      ? number
      : null;
  };

  const formatNumber = (
    value,
    digits = 1
  ) => {
    const number =
      numeric(value);

    if (
      number === null
    ) {
      return '--';
    }

    return number
      .toFixed(digits)
      .replace(
        /\.0+$/,
        ''
      );
  };

  function normalizeSeries(
    series
  ) {
    return (
      series || []
    ).map(
      (day) => ({
        date:
          String(
            day.date
          ),

        soil_moisture:
          numeric(
            day.soil_moisture
          ),

        soil_ph:
          numeric(
            day.soil_ph
          ),

        nitrogen:
          numeric(
            day.nitrogen
          ),

        phosphorus:
          numeric(
            day.phosphorus
          ),

        potassium:
          numeric(
            day.potassium
          ),

        temperature:
          numeric(
            day.temperature
          ),

        humidity:
          numeric(
            day.humidity
          ),

        irrigation_minutes:
          numeric(
            day.irrigation_minutes
          )
      })
    );
  }

  function normalizeReadings(
    readings
  ) {
    return (
      readings || []
    )
      .map(
        (reading) => ({
          id:
            Number(
              reading.id
            ),

          timestamp:
            String(
              reading.timestamp
            ),

          soil_moisture:
            numeric(
              reading.soil_moisture
            ),

          soil_ph:
            numeric(
              reading.soil_ph
            ),

          nitrogen:
            numeric(
              reading.nitrogen
            ),

          phosphorus:
            numeric(
              reading.phosphorus
            ),

          potassium:
            numeric(
              reading.potassium
            ),

          temperature:
            numeric(
              reading.temperature
            ),

          humidity:
            numeric(
              reading.humidity
            )
        })
      )
      .filter(
        (reading) =>
          Number.isFinite(
            reading.id
          ) &&
          reading.timestamp !==
            ''
      )
      .sort(
        (a, b) =>
          a.id -
          b.id
      );
  }

  function toTimestamp(
    value
  ) {
    const parsed =
      Date.parse(
        String(
          value
        ).replace(
          ' ',
          'T'
        )
      );

    return Number.isFinite(
      parsed
    )
      ? parsed
      : null;
  }

  function readingPoints(
    readings,
    key
  ) {
    return readings
      .map(
        (reading) => [
          toTimestamp(
            reading.timestamp
          ),
          reading[key]
        ]
      )
      .filter(
        (point) =>
          point[0] !== null
      );
  }

  function plotWidth(
    pointCount
  ) {
    return Math.max(
      MIN_CHART_WIDTH,
      pointCount *
        POINT_SPACING
    );
  }

  function getColors() {
    const styles =
      getComputedStyle(
        document.body
      );

    const dark =
      document.body.classList.contains(
        'dark-theme'
      );

    return {
      title:
        styles
          .getPropertyValue(
            '--title-color'
          )
          .trim() ||
        (
          dark
            ? '#f4f7f4'
            : '#17261f'
        ),

      text:
        styles
          .getPropertyValue(
            '--text-color'
          )
          .trim() ||
        (
          dark
            ? '#bac5be'
            : '#66746d'
        ),

      surface:
        styles
          .getPropertyValue(
            '--surface-color'
          )
          .trim() ||
        (
          dark
            ? '#28312a'
            : '#ffffff'
        ),

      grid:
        dark
          ? 'rgba(255,255,255,.08)'
          : 'rgba(110,130,115,.16)',

      axis:
        dark
          ? 'rgba(255,255,255,.22)'
          : 'rgba(110,130,115,.25)',

      primary:
        '#7DA417',

      ph:
        '#EC4899',

      nitrogen:
        '#8B5CF6',

      phosphorus:
        '#06B6D4',

      potassium:
        '#F59E0B',

      temp:
        '#F7263B',

      humidity:
        '#61B8E4',

      attention:
        '#EAB308',

      critical:
        '#EF4444'
    };
  }

  function getLabels() {
    return {
      moisture:
        t(
          'analytics.legend.soil_moisture',
          defaults.moisture
        ),

      ph:
        t(
          'analytics.legend.soil_ph',
          defaults.ph
        ),

      nitrogen:
        t(
          'analytics.legend.nitrogen',
          defaults.nitrogen
        ),

      phosphorus:
        t(
          'analytics.legend.phosphorus',
          defaults.phosphorus
        ),

      potassium:
        t(
          'analytics.legend.potassium',
          defaults.potassium
        ),

      temperature:
        t(
          'analytics.legend.temperature',
          defaults.temperature
        ),

      humidity:
        t(
          'analytics.legend.humidity',
          defaults.humidity
        ),

      minutes:
        t(
          'analytics.units.minutes',
          defaults.minutes
        ),

      healthy:
        t(
          'analytics.condition.healthy',
          defaults.healthy
        ),

      attention:
        t(
          'analytics.condition.attention',
          defaults.attention
        ),

      critical:
        t(
          'analytics.condition.critical',
          defaults.critical
        ),

      sectors:
        t(
          'analytics.condition.sectors',
          defaults.sectors
        )
    };
  }

  function chartOptions(
    pointCount,
    height = CHART_HEIGHT,
    scrollToLatest = true
  ) {
    const c =
      getColors();

    Highcharts.setOptions({
      time: {
        useUTC:
          false
      }
    });

    return {
      chart: {
        backgroundColor:
          'transparent',

        height,

        spacing: [
          4,
          4,
          28,
          4
        ],

        animation:
          false,

        reflow:
          true,

        scrollablePlotArea: {
          minWidth:
            plotWidth(
              pointCount
            ),

          scrollPositionX:
            scrollToLatest
              ? 1
              : 0
        }
      },

      credits: {
        enabled:
          false
      },

      exporting: {
        enabled:
          false
      },

      accessibility: {
        enabled:
          false
      },

      title: {
        text:
          null
      },

      xAxis: {
        type:
          'datetime',

        tickPixelInterval:
          95,

        lineColor:
          c.axis,

        tickColor:
          c.axis,

        labels: {
          style: {
            color:
              c.text,

            fontSize:
              '10px',

            fontWeight:
              '500'
          },

          formatter() {
            const span =
              this.axis.max -
              this.axis.min;

            if (
              span <=
              172800000
            ) {
              return Highcharts.dateFormat(
                '%H:%M',
                this.value
              );
            }

            return Highcharts.dateFormat(
              '%b %e',
              this.value
            );
          }
        }
      },

      legend: {
        align:
          'left',

        verticalAlign:
          'top',

        layout:
          'horizontal',

        margin:
          8,

        itemDistance:
          14,

        symbolWidth:
          12,

        symbolHeight:
          8,

        itemStyle: {
          color:
            c.text,

          fontSize:
            '11px',

          fontWeight:
            '500'
        },

        itemHoverStyle: {
          color:
            c.title
        }
      },

      tooltip: {
        shared:
          true,

        useHTML:
          true,

        backgroundColor:
          c.surface,

        borderColor:
          c.axis,

        borderRadius:
          10,

        shadow:
          true,

        style: {
          color:
            c.title,

          fontSize:
            '12px'
        },

        formatter() {
          const title =
            Highcharts.dateFormat(
              '%A, %b %e, %H:%M:%S',
              this.x
            );

          const rows =
            this.points
              .filter(
                (point) =>
                  point.y !==
                    null &&
                  point.y !==
                    undefined
              )
              .map(
                (point) => `
                  <div class="analytics-tooltip-row">
                    <span>
                      <i
                        style="background:${point.color}">
                      </i>

                      ${escapeHtml(
                        point.series.name
                      )}
                    </span>

                    <strong>
                      ${escapeHtml(
                        formatNumber(
                          point.y
                        )
                      )}
                    </strong>
                  </div>
                `
              )
              .join('');

          return `
            <div class="analytics-tooltip">
              <div class="analytics-tooltip-date">
                ${escapeHtml(
                  title
                )}
              </div>

              ${rows}
            </div>
          `;
        }
      },

      plotOptions: {
        series: {
          animation:
            false,

          connectNulls:
            false,

          states: {
            hover: {
              lineWidthPlus:
                1
            }
          }
        },

        areaspline: {
          lineWidth:
            2,

          fillOpacity:
            0.15,

          marker: {
            enabled:
              true,

            radius:
              3,

            lineWidth:
              1.5
          }
        }
      }
    };
  }

  function axis(
    c,
    min,
    max,
    ticks,
    labelColor,
    opposite = false
  ) {
    const result = {
      title: {
        text:
          null
      },

      gridLineColor:
        c.grid,

      gridLineWidth:
        1,

      lineColor:
        c.axis,

      tickColor:
        c.axis,

      opposite,

      labels: {
        style: {
          color:
            labelColor,

          fontSize:
            '10px',

          fontWeight:
            '600'
        }
      }
    };

    if (
      min !==
      undefined
    ) {
      result.min =
        min;
    }

    if (
      max !==
      undefined
    ) {
      result.max =
        max;
    }

    if (
      Array.isArray(
        ticks
      )
    ) {
      result.tickPositions =
        ticks;
    }

    return result;
  }

  function setSelectOptions(
    select,
    items,
    allKey,
    selected
  ) {
    select.innerHTML =
      `<option value="all">${escapeHtml(
        t(
          allKey
        )
      )}</option>`;

    const seen =
      new Set();

    items.forEach(
      (item) => {
        const id =
          String(
            item.id
          );

        if (
          seen.has(id)
        ) {
          return;
        }

        seen.add(id);

        const selectedAttr =
          id ===
          String(
            selected
          )
            ? ' selected'
            : '';

        select.insertAdjacentHTML(
          'beforeend',
          `
            <option
              value="${escapeHtml(id)}"${selectedAttr}>
              ${escapeHtml(
                item.name
              )}
            </option>
          `
        );
      }
    );
  }

  function populateFilters(
    fieldValue,
    sectorValue,
    nodeValue
  ) {
    setSelectOptions(
      elements.field,
      filters.fields,
      'analytics.filters.all_fields',
      fieldValue
    );

    const sectors =
      filters.sectors.filter(
        (sector) =>
          fieldValue ===
            'all' ||
          String(
            sector.field_id
          ) ===
            String(
              fieldValue
            )
      );

    setSelectOptions(
      elements.sector,
      sectors,
      'analytics.filters.all_sectors',
      sectorValue
    );

    const currentSector =
      elements.sector.value;

    const nodes =
      sectors
        .filter(
          (sector) =>
            currentSector ===
              'all' ||
            String(
              sector.id
            ) ===
              String(
                currentSector
              )
        )
        .flatMap(
          (sector) =>
            sector.nodes.map(
              (node) => ({
                id:
                  node,

                name:
                  node
              })
            )
        );

    setSelectOptions(
      elements.node,
      nodes,
      'analytics.filters.all_nodes',
      nodeValue
    );
  }

  function renderSummary(
    result
  ) {
    const readings =
      normalizeReadings(
        result.readings
      );

    const latest =
      readings.length
        ? readings[
            readings.length -
              1
          ]
        : null;

    const cards = [
      {
        value:
          Number(
            result.meta
              ?.telemetry_rows ||
              0
          ),

        label:
          t(
            'analytics.summary.telemetry',
            'Telemetry records'
          )
      },

      {
        value:
          Number(
            result.meta
              ?.sectors_with_data ||
              0
          ),

        label:
          t(
            'analytics.summary.sectors',
            'Sectors with data'
          )
      },

      {
        value:
          latest?.soil_moisture ==
          null
            ? '--'
            : `${formatNumber(
                latest.soil_moisture
              )}%`,

        label:
          t(
            'analytics.summary.latest_moisture',
            'Latest moisture'
          )
      },

      {
        value:
          latest?.temperature ==
          null
            ? '--'
            : `${formatNumber(
                latest.temperature
              )}°C`,

        label:
          t(
            'analytics.summary.latest_temperature',
            'Latest temperature'
          )
      }
    ];

    elements.summary.innerHTML =
      cards
        .map(
          (card) => `
            <div class="analytics-summary-card">
              <strong>
                ${escapeHtml(
                  card.value
                )}
              </strong>

              <span>
                ${escapeHtml(
                  card.label
                )}
              </span>
            </div>
          `
        )
        .join('');
  }

  function getScrollElement(
    chart
  ) {
    if (
      !chart?.renderTo
    ) {
      return null;
    }

    return (
      chart.renderTo.querySelector(
        '.highcharts-scrolling'
      ) ||
      null
    );
  }

  function getScrollState(
    chart
  ) {
    const scroll =
      getScrollElement(
        chart
      );

    if (
      !scroll
    ) {
      return {
        left:
          0,

        atLatest:
          true
      };
    }

    const max =
      Math.max(
        0,
        scroll.scrollWidth -
          scroll.clientWidth
      );

    return {
      left:
        scroll.scrollLeft,

      atLatest:
        max -
          scroll.scrollLeft <=
        48
    };
  }

  function restoreScroll(
    chart,
    state,
    forceLatest = false
  ) {
    const scroll =
      getScrollElement(
        chart
      );

    if (
      !scroll
    ) {
      return;
    }

    requestAnimationFrame(
      () => {
        const max =
          Math.max(
            0,
            scroll.scrollWidth -
              scroll.clientWidth
          );

        if (
          forceLatest ||
          state.atLatest
        ) {
          scroll.scrollLeft =
            max;

          return;
        }

        scroll.scrollLeft =
          Math.min(
            state.left,
            max
          );
      }
    );
  }

  function updateScrollableArea(
    chart,
    pointCount,
    state
  ) {
    if (
      !chart
    ) {
      return;
    }

    const width =
      plotWidth(
        pointCount
      );

    chart.update(
      {
        chart: {
          scrollablePlotArea: {
            minWidth:
              width,

            scrollPositionX:
              state.atLatest
                ? 1
                : 0
          }
        }
      },
      false,
      false
    );

    requestAnimationFrame(
      () => {
        restoreScroll(
          chart,
          state
        );
      }
    );
  }

  function createCharts(
    result
  ) {
    const c =
      getColors();

    const l =
      getLabels();

    const readings =
      normalizeReadings(
        result.readings
      ).slice(
        -MAX_LIVE_POINTS
      );

    const daily =
      normalizeSeries(
        result.series
      );

    charts.soil =
      Highcharts.chart(
        elements.soil,
        Highcharts.merge(
          chartOptions(
            readings.length
          ),
          {
            yAxis: [
              axis(
                c,
                0,
                100,
                [
                  0,
                  25,
                  50,
                  75,
                  100
                ],
                c.primary
              ),

              axis(
                c,
                0,
                14,
                [
                  0,
                  2,
                  4,
                  6,
                  8,
                  10,
                  12,
                  14
                ],
                c.ph,
                true
              )
            ],

            series: [
              {
                id:
                  'soil-moisture',

                type:
                  'areaspline',

                name:
                  l.moisture,

                data:
                  readingPoints(
                    readings,
                    'soil_moisture'
                  ),

                yAxis:
                  0,

                color:
                  c.primary,

                fillColor:
                  'rgba(125, 164, 23, 0.15)',

                marker: {
                  symbol:
                    'circle',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.primary
                }
              },

              {
                id:
                  'soil-ph',

                type:
                  'areaspline',

                name:
                  l.ph,

                data:
                  readingPoints(
                    readings,
                    'soil_ph'
                  ),

                yAxis:
                  1,

                color:
                  c.ph,

                fillColor:
                  'rgba(236, 72, 153, 0.12)',

                dashStyle:
                  'ShortDash',

                marker: {
                  symbol:
                    'diamond',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.ph
                }
              }
            ]
          }
        )
      );

    charts.nutrients =
      Highcharts.chart(
        elements.nutrients,
        Highcharts.merge(
          chartOptions(
            readings.length
          ),
          {
            yAxis:
              axis(
                c,
                0,
                200,
                [
                  0,
                  50,
                  100,
                  150,
                  200
                ],
                c.nitrogen
              ),

            series: [
              {
                id:
                  'nutrient-nitrogen',

                type:
                  'areaspline',

                name:
                  l.nitrogen,

                data:
                  readingPoints(
                    readings,
                    'nitrogen'
                  ),

                color:
                  c.nitrogen,

                fillColor:
                  'rgba(139, 92, 246, 0.11)',

                marker: {
                  symbol:
                    'circle',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.nitrogen
                }
              },

              {
                id:
                  'nutrient-phosphorus',

                type:
                  'areaspline',

                name:
                  l.phosphorus,

                data:
                  readingPoints(
                    readings,
                    'phosphorus'
                  ),

                color:
                  c.phosphorus,

                fillColor:
                  'rgba(6, 182, 212, 0.11)',

                dashStyle:
                  'ShortDash',

                marker: {
                  symbol:
                    'diamond',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.phosphorus
                }
              },

              {
                id:
                  'nutrient-potassium',

                type:
                  'areaspline',

                name:
                  l.potassium,

                data:
                  readingPoints(
                    readings,
                    'potassium'
                  ),

                color:
                  c.potassium,

                fillColor:
                  'rgba(245, 158, 11, 0.11)',

                marker: {
                  symbol:
                    'square',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.potassium
                }
              }
            ]
          }
        )
      );

    charts.environment =
      Highcharts.chart(
        elements.environment,
        Highcharts.merge(
          chartOptions(
            readings.length
          ),
          {
            yAxis: [
              axis(
                c,
                undefined,
                undefined,
                undefined,
                c.temp
              ),

              axis(
                c,
                0,
                100,
                [
                  0,
                  25,
                  50,
                  75,
                  100
                ],
                c.humidity,
                true
              )
            ],

            series: [
              {
                id:
                  'environment-temperature',

                type:
                  'areaspline',

                name:
                  l.temperature,

                data:
                  readingPoints(
                    readings,
                    'temperature'
                  ),

                yAxis:
                  0,

                color:
                  c.temp,

                fillColor:
                  'rgba(247, 38, 59, 0.12)',

                marker: {
                  symbol:
                    'circle',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.temp
                }
              },

              {
                id:
                  'environment-humidity',

                type:
                  'areaspline',

                name:
                  l.humidity,

                data:
                  readingPoints(
                    readings,
                    'humidity'
                  ),

                yAxis:
                  1,

                color:
                  c.humidity,

                fillColor:
                  'rgba(97, 184, 228, 0.12)',

                dashStyle:
                  'ShortDash',

                marker: {
                  symbol:
                    'diamond',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.humidity
                }
              }
            ]
          }
        )
      );

    const irrigation =
      daily.map(
        (day) => [
          Date.parse(
            `${day.date}T00:00:00`
          ),

          day.irrigation_minutes
        ]
      );

    const irrigationMax =
      Math.max(
        10,
        ...daily.map(
          (day) =>
            day.irrigation_minutes ||
            0
        )
      );

    charts.irrigation =
      Highcharts.chart(
        elements.irrigation,
        Highcharts.merge(
          chartOptions(
            daily.length,
            COMPACT_CHART_HEIGHT,
            false
          ),
          {
            legend: {
              enabled:
                false
            },

            yAxis:
              axis(
                c,
                0,
                irrigationMax,
                undefined,
                c.primary
              ),

            series: [
              {
                id:
                  'irrigation-minutes',

                type:
                  'areaspline',

                name:
                  l.minutes,

                data:
                  irrigation,

                color:
                  c.primary,

                fillColor:
                  'rgba(125, 164, 23, 0.15)',

                marker: {
                  symbol:
                    'circle',

                  fillColor:
                    c.surface,

                  lineColor:
                    c.primary
                }
              }
            ]
          }
        )
      );
  }

  function updateCharts(
    result
  ) {
    const readings =
      normalizeReadings(
        result.readings
      ).slice(
        -MAX_LIVE_POINTS
      );

    const daily =
      normalizeSeries(
        result.series
      );

    const soilState =
      getScrollState(
        charts.soil
      );

    const nutrientState =
      getScrollState(
        charts.nutrients
      );

    const environmentState =
      getScrollState(
        charts.environment
      );

    updateSeries(
      charts.soil,
      'soil-moisture',
      readingPoints(
        readings,
        'soil_moisture'
      )
    );

    updateSeries(
      charts.soil,
      'soil-ph',
      readingPoints(
        readings,
        'soil_ph'
      )
    );

    updateSeries(
      charts.nutrients,
      'nutrient-nitrogen',
      readingPoints(
        readings,
        'nitrogen'
      )
    );

    updateSeries(
      charts.nutrients,
      'nutrient-phosphorus',
      readingPoints(
        readings,
        'phosphorus'
      )
    );

    updateSeries(
      charts.nutrients,
      'nutrient-potassium',
      readingPoints(
        readings,
        'potassium'
      )
    );

    updateSeries(
      charts.environment,
      'environment-temperature',
      readingPoints(
        readings,
        'temperature'
      )
    );

    updateSeries(
      charts.environment,
      'environment-humidity',
      readingPoints(
        readings,
        'humidity'
      )
    );

    updateSeries(
      charts.irrigation,
      'irrigation-minutes',
      daily.map(
        (day) => [
          Date.parse(
            `${day.date}T00:00:00`
          ),

          day.irrigation_minutes
        ]
      )
    );

    charts.soil.redraw(
      false
    );

    charts.nutrients.redraw(
      false
    );

    charts.environment.redraw(
      false
    );

    charts.irrigation.redraw(
      false
    );

    updateScrollableArea(
      charts.soil,
      readings.length,
      soilState
    );

    updateScrollableArea(
      charts.nutrients,
      readings.length,
      nutrientState
    );

    updateScrollableArea(
      charts.environment,
      readings.length,
      environmentState
    );

    requestAnimationFrame(
      () => {
        restoreScroll(
          charts.soil,
          soilState
        );

        restoreScroll(
          charts.nutrients,
          nutrientState
        );

        restoreScroll(
          charts.environment,
          environmentState
        );
      }
    );

    const irrigationMax =
      Math.max(
        10,
        ...daily.map(
          (day) =>
            day.irrigation_minutes ||
            0
        )
      );

    charts.irrigation
      ?.yAxis[0]
      .setExtremes(
        0,
        irrigationMax,
        false,
        false
      );
  }

  function updateSeries(
    chart,
    id,
    data
  ) {
    const series =
      chart?.get(
        id
      );

    if (
      !series
    ) {
      return;
    }

    series.setData(
      data,
      false,
      false,
      false
    );
  }

  function renderCondition(
    condition
  ) {
    const c =
      getColors();

    const l =
      getLabels();

    const values = [
      Number(
        condition.healthy ||
          0
      ),

      Number(
        condition.attention ||
          0
      ),

      Number(
        condition.critical ||
          0
      )
    ];

    const total =
      values.reduce(
        (sum, value) =>
          sum +
          value,
        0
      );

    const layout =
      elements.condition.closest(
        '.condition-layout'
      );

    if (
      total ===
      0
    ) {
      layout?.classList.add(
        'condition-layout--empty'
      );

      elements.condition.innerHTML =
        `
          <div class="condition-no-data">
            ${escapeHtml(
              t(
                'analytics.empty.no_sectors',
                'No sectors with recent telemetry.'
              )
            )}
          </div>
        `;

      elements.conditionLegend.innerHTML =
        '';

      return;
    }

    layout?.classList.remove(
      'condition-layout--empty'
    );

    const radius =
      50;

    const circumference =
      2 *
      Math.PI *
      radius;

    const palette = [
      c.primary,
      c.attention,
      c.critical
    ];

    const names = [
      l.healthy,
      l.attention,
      l.critical
    ];

    let offset =
      0;

    const rings =
      values
        .map(
          (value, index) => {
            const length =
              circumference *
              (
                value /
                total
              );

            const ring =
              `
                <circle
                  cx="70"
                  cy="70"
                  r="${radius}"
                  fill="none"
                  stroke="${palette[index]}"
                  stroke-width="20"
                  stroke-linecap="round"
                  stroke-dasharray="${length} ${circumference - length}"
                  stroke-dashoffset="${-offset}"
                  transform="rotate(-90 70 70)">
                </circle>
              `;

            offset +=
              length;

            return ring;
          }
        )
        .join('');

    elements.condition.innerHTML =
      `
        <svg
          viewBox="0 0 140 140"
          aria-hidden="true">

          <circle
            cx="70"
            cy="70"
            r="${radius}"
            fill="none"
            stroke="${c.grid}"
            stroke-width="20">
          </circle>

          ${rings}

          <circle
            cx="70"
            cy="70"
            r="37"
            fill="${c.surface}">
          </circle>

          <text
            x="70"
            y="67"
            text-anchor="middle"
            fill="${c.title}"
            font-size="22"
            font-weight="700">
            ${total}
          </text>

          <text
            x="70"
            y="84"
            text-anchor="middle"
            fill="${c.text}"
            font-size="10">
            ${escapeHtml(
              l.sectors
            )}
          </text>
        </svg>
      `;

    elements.conditionLegend.innerHTML =
      values
        .map(
          (value, index) => `
            <div class="condition-legend-row">
              <span>
                <i
                  class="condition-dot"
                  style="background:${palette[index]}">
                </i>

                ${escapeHtml(
                  names[index]
                )}
              </span>

              <strong>
                ${Math.round(
                  (
                    value /
                    total
                  ) *
                  100
                )}%
              </strong>
            </div>
          `
        )
        .join('');
  }

  function renderScope(
    result
  ) {
    const scope =
      result.scope ||
      {};

    const parts = [
      scope.field_name,
      scope.sector_name,
      scope.node_id
    ].filter(
      Boolean
    );

    elements.scope.innerHTML =
      `
        <i
          class="ri-focus-3-line"
          aria-hidden="true">
        </i>

        <span>
          ${escapeHtml(
            parts.join(
              ' · '
            ) ||
            t(
              'analytics.scope.all',
              'All farms'
            )
          )}
        </span>
      `;
  }

  function render(
    result
  ) {
    const hasTelemetry =
      Number(
        result.meta
          ?.telemetry_rows ||
          0
      ) > 0;

    elements.empty.hidden =
      hasTelemetry;

    elements.grid.hidden =
      !hasTelemetry;

    elements.summary.hidden =
      !hasTelemetry;

    if (
      !hasTelemetry ||
      typeof Highcharts ===
        'undefined'
    ) {
      destroyCharts();

      return;
    }

    renderSummary(
      result
    );

    renderScope(
      result
    );

    if (
      !charts.soil
    ) {
      createCharts(
        result
      );

      renderCondition(
        result.condition ||
          {}
      );

      return;
    }

    updateCharts(
      result
    );

    renderCondition(
      result.condition ||
        {}
    );
  }

  function buildUrl() {
    const params =
      new URLSearchParams({
        days:
          elements.period
            .value ||
          '7',

        _:
          String(
            Date.now()
          )
      });

    if (
      elements.field.value !==
      'all'
    ) {
      params.set(
        'field_id',
        elements.field.value
      );
    }

    if (
      elements.sector.value !==
      'all'
    ) {
      params.set(
        'sector_id',
        elements.sector.value
      );
    }

    if (
      elements.node.value !==
      'all'
    ) {
      params.set(
        'node_id',
        elements.node.value
      );
    }

    return (
      `/api/analytics?${params.toString()}`
    );
  }

  async function loadAnalytics() {
    if (
      requestInFlight
    ) {
      return;
    }

    requestInFlight =
      true;

    try {
      const response =
        await fetch(
          buildUrl(),
          {
            method:
              'GET',

            credentials:
              'same-origin',

            cache:
              'no-store',

            headers: {
              Accept:
                'application/json',

              'Cache-Control':
                'no-cache'
            }
          }
        );

      if (
        !response.ok
      ) {
        throw new Error(
          `HTTP ${response.status}`
        );
      }

      const result =
        await response.json();

      if (
        result.status !==
        'success'
      ) {
        throw new Error(
          result.message ||
          'Analytics request failed.'
        );
      }

      filters =
        result.filters ||
        {
          fields: [],
          sectors: []
        };

      populateFilters(
        elements.field.value,
        elements.sector.value,
        elements.node.value
      );

      lastResult =
        result;

      render(
        result
      );
    } catch (
      error
    ) {
      console.error(
        'Analytics loading failed:',
        error
      );
    } finally {
      requestInFlight =
        false;
    }
  }

  function startPolling() {
    if (
      pollTimer
    ) {
      clearInterval(
        pollTimer
      );
    }

    pollTimer =
      window.setInterval(
        () => {
          if (
            !document.hidden
          ) {
            loadAnalytics();
          }
        },
        POLL_INTERVAL
      );
  }

  function destroyCharts() {
    Object.values(
      charts
    ).forEach(
      (chart) => {
        chart?.destroy?.();
      }
    );

    charts = {
      soil: null,
      nutrients: null,
      environment: null,
      irrigation: null
    };
  }

  function rebuildAfterFilterChange() {
    destroyCharts();
    loadAnalytics();
  }

  function refreshForThemeOrLanguage() {
    if (
      !lastResult
    ) {
      return;
    }

    destroyCharts();

    render(
      lastResult
    );

    populateFilters(
      elements.field.value,
      elements.sector.value,
      elements.node.value
    );
  }

  function bindFilters() {
    elements.field.addEventListener(
      'change',
      () => {
        populateFilters(
          elements.field.value,
          'all',
          'all'
        );

        rebuildAfterFilterChange();
      }
    );

    elements.sector.addEventListener(
      'change',
      () => {
        populateFilters(
          elements.field.value,
          elements.sector.value,
          'all'
        );

        rebuildAfterFilterChange();
      }
    );

    elements.node.addEventListener(
      'change',
      rebuildAfterFilterChange
    );

    elements.period.addEventListener(
      'change',
      rebuildAfterFilterChange
    );
  }

  document.addEventListener(
    'languageChanged',
    refreshForThemeOrLanguage
  );

  document.addEventListener(
    'themeChanged',
    refreshForThemeOrLanguage
  );

  document.addEventListener(
    'visibilitychange',
    () => {
      if (
        !document.hidden
      ) {
        loadAnalytics();
      }
    }
  );

  window.addEventListener(
    'beforeunload',
    () => {
      if (
        pollTimer
      ) {
        clearInterval(
          pollTimer
        );
      }

      destroyCharts();
    }
  );

  document.addEventListener(
    'DOMContentLoaded',
    () => {
      bindFilters();
      loadAnalytics();
      startPolling();
    }
  );
})();
