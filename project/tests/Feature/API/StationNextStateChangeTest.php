<?php

namespace Tests\Feature\API;

use App\Interfaces\TimeableInterface;
use App\Models\OpenHour;
use App\Models\OpenHourException;
use App\Models\Station;
use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationNextStateChangeTest extends TestCase
{
    use RefreshDatabase;

    protected string $uri = '/api/v1/open_hours/stations/%s/state/next';

    protected array $timeables;

    /**
     * @test
     * @dataProvider timestamps_data_provider
     *
     * @param string|int $timestamp
     * @param int $expected
     * @param array $result
     */
    public function timestamp_should_be_in_valid_U_format($timestamp, int $expected, array $result): void
    {
        $station = Station::factory()->create();
        $response = $this->json(
            'GET',
            sprintf('%s?timestamp=%s', sprintf($this->uri, $station->id), $timestamp),
        );

        $response->assertStatus($expected);

        if ($result) {
            $response->assertJson($result);
        }
    }

    /**
     * @test
     */
    public function should_return_a_valid_timestamp_as_station_next_state_change()
    {
        $station = Station::factory()->create();
        $timestamp = time();
        $response = $this->json(
            'GET',
            sprintf('%s?timestamp=%s', sprintf($this->uri, $station->id), $timestamp),
        );

        $result = $response->json('result');

        // the valid result should be timestamp || null
        $true = (strtotime(date('d-m-Y H:i:s', $result)) === (int)$result) || is_null($result);

        $this->assertTrue($true, 'result is a timestamp');
    }

    /**
     * @test
     * @dataProvider station_status_data_provider
     *
     * @param array $preparation_data
     * @param array $tests
     */
    public function should_return_station_next_state_change_timestamp(array $preparation_data, array $tests): void
    {
        $station = $this->create_entities_open_hours_and_exceptions($preparation_data);

        foreach ($tests as $test) {
            $response = $this->json(
                'GET',
                sprintf('%s?timestamp=%s', sprintf($this->uri, $station->id), $test['timestamp']),
            );

            $response->assertStatus(200);
            $this->assertEquals(strtotime($test['expected']), $response->json('data'));
        }
    }

    /**
     * Provides data for station status check
     */
    public function station_status_data_provider(): array
    {
        $data = [];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ]
                    ],
                ]
            ],
            'tests' => [
                ['timestamp' => strtotime('today 7:00'), 'expected' => 'today 08:00'],
                ['timestamp' => strtotime('today 12:00'), 'expected' => 'today 18:00'],
                ['timestamp' => strtotime('today 18:00'), 'expected' => sprintf('next %s 8:00', date('l'))],
            ]
        ];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ],
                    ],
                ],
                'store' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '04:00',
                            'to' => '19:00',
                        ]
                    ]
                ]
            ],
            'tests' => [
                ['timestamp' => strtotime('today 3:00'), 'expected' => 'today 04:00'],
                ['timestamp' => strtotime('today 12:00'), 'expected' => 'today 19:00'],
                ['timestamp' => strtotime('today 18:30'), 'expected' => 'today 19:00'],
                ['timestamp' => strtotime('today 19:30'), 'expected' => sprintf('next %s 4:00', date('l'))],
            ]
        ];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ],
                    ],
                ],
                'store' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '04:00',
                            'to' => '19:00',
                        ]
                    ]
                ],
                'tenant' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '03:00',
                            'to' => '20:00',
                        ]
                    ]
                ]
            ],
            'tests' => [
                ['timestamp' => strtotime('today 2:00'), 'expected' => 'today 03:00'],
                ['timestamp' => strtotime('today 03:00'), 'expected' => 'today 20:00'],
                ['timestamp' => strtotime('today 12:00'), 'expected' => 'today 20:00'],
                ['timestamp' => strtotime('today 18:00'), 'expected' => 'today 20:00'],
                ['timestamp' => strtotime('today 19:00'), 'expected' => 'today 20:00'],
                ['timestamp' => strtotime('today 21:00'), 'expected' => sprintf('next %s 3:00', date('l'))],
            ]
        ];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 06:00'),
                            'to' => strtotime('today 09:00'),
                            'status' => 0,
                            'comment' => 'simple_comment',
                        ],
                        [
                            'from' => strtotime('today 14:00'),
                            'to' => strtotime('today 15:00'),
                            'status' => 0,
                            'comment' => 'simple_comment',
                        ],
                        [
                            'from' => sprintf('+1 week 06:00'),
                            'to' => sprintf('+1 week 09:00'),
                            'status' => 0,
                            'comment' => 'simple_comment',
                        ]
                    ]
                ],
            ],
            'tests' => [
                ['timestamp' => strtotime('today 4:00'), 'expected' => 'today 09:00'],
                ['timestamp' => strtotime('today 09:00'), 'expected' => 'today 09:00'],
                ['timestamp' => strtotime('today 09:01'), 'expected' => 'today 14:00'],
                ['timestamp' => strtotime('today 14:00'), 'expected' => 'today 15:00'],
                ['timestamp' => strtotime('today 15:00'), 'expected' => 'today 15:00'],
                ['timestamp' => strtotime('today 15:01'), 'expected' => 'today 18:00'],
                ['timestamp' => strtotime('today 19:00'), 'expected' => '+1 week 9:00'],
                ['timestamp' => strtotime('+1 week 09:00'), 'expected' => '+1 week 09:00'],
                ['timestamp' => strtotime('+1 week 09:01'), 'expected' => '+1 week 18:00'],
            ]
        ];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ],
                    ],
                ],
                'store' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '09:00',
                            'to' => '19:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 14:00'),
                            'to' => strtotime('today 15:00'),
                            'status' => 0,
                            'comment' => 'simple_comment',
                        ],
                    ]
                ],
                'tenant' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '04:00',
                            'to' => '21:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 14:00'),
                            'to' => strtotime('today 15:00'),
                            'status' => 1,
                            'comment' => 'simple_comment',
                        ],
                    ]
                ],
            ],
            'tests' => [
                ['timestamp' => strtotime('today 4:00'), 'expected' => 'today 14:00'],
                ['timestamp' => strtotime('today 10:00'), 'expected' => 'today 14:00'],
                ['timestamp' => strtotime('today 14:30'), 'expected' => 'today 15:00'],
                ['timestamp' => strtotime('today 15:00'), 'expected' => 'today 15:00'],
                ['timestamp' => strtotime('today 15:01'), 'expected' => 'today 21:00'],
                ['timestamp' => strtotime('today 15:00'), 'expected' => 'today 15:00'],
            ]
        ];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 16:00'),
                            'to' => strtotime('today 19:00'),
                            'status' => 0,
                            'comment' => 'simple_comment',
                        ],
                    ]
                ],
                'store' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '09:00',
                            'to' => '19:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 16:00'),
                            'to' => strtotime('today 19:00'),
                            'status' => 1,
                            'comment' => 'simple_comment',
                        ],
                    ]
                ],
                'tenant' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '04:00',
                            'to' => '21:00',
                        ],
                    ],
                ],
            ],
            'tests' => [
                ['timestamp' => strtotime('today 4:00'), 'expected' => 'today 16:00'],
                ['timestamp' => strtotime('today 10:00'), 'expected' => 'today 16:00'],
                ['timestamp' => strtotime('today 16:30'), 'expected' => 'today 19:00'],
                ['timestamp' => strtotime('today 19:00'), 'expected' => 'today 19:00'],
                ['timestamp' => strtotime('today 19:01'), 'expected' => 'today 21:00'],
            ]
        ];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 16:00'),
                            'to' => strtotime('today 19:00'),
                            'status' => 0,
                            'comment' => 'simple_comment',
                        ],
                    ]
                ],
                'store' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '09:00',
                            'to' => '19:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 16:00'),
                            'to' => strtotime('today 19:00'),
                            'status' => 1,
                            'comment' => 'simple_comment',
                        ],
                    ]
                ],
                'tenant' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '04:00',
                            'to' => '21:00',
                        ],
                    ],
                ],
            ],
            'tests' => [
                ['timestamp' => strtotime('today 4:00'), 'expected' => 'today 16:00'],
                ['timestamp' => strtotime('today 10:00'), 'expected' => 'today 16:00'],
                ['timestamp' => strtotime('today 16:30'), 'expected' => 'today 19:00'],
                ['timestamp' => strtotime('today 19:00'), 'expected' => 'today 19:00'],
                ['timestamp' => strtotime('today 19:01'), 'expected' => 'today 21:00'],
            ]
        ];

        $data[] = [
            'preparation' => [
                'station' => [
                    'open_hours' => [
                        [
                            'day' => date('w'),
                            'from' => '08:00',
                            'to' => '18:00',
                        ],
                    ],
                    'exceptions' => [
                        [
                            'from' => strtotime('today 16:00'),
                            'to' => strtotime('+2 days 19:00'),
                            'status' => 1,
                            'comment' => 'simple_comment',
                        ],
                    ]
                ],
            ],
            'tests' => [
                ['timestamp' => strtotime('today 4:00'), 'expected' => 'today 08:00'],
                ['timestamp' => strtotime('today 10:00'), 'expected' => '+2 days 19:00'],
                ['timestamp' => strtotime('+1 day 16:30'), 'expected' => '+2 days 19:00'],
                ['timestamp' => strtotime('+3 days 19:00'), 'expected' => '+1 week 08:00'],
            ]
        ];

        return $data;
    }

    /**
     * Add open hours and exceptions for the given timeables
     *
     * @param $timeables
     * @return TimeableInterface
     */
    private function create_entities_open_hours_and_exceptions($timeables): TimeableInterface
    {
        $tenant = Tenant::factory()->create();
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $station = Station::factory()->create(['store_id' => $store->id]);

        foreach ($timeables as $timeable_type => $data) {
            if (isset($data['open_hours']) && !empty($data['open_hours'])) {
                $open_hours = array_map(
                    function ($open_hour) {
                        return new OpenHour($open_hour);
                    },
                    $data['open_hours']
                );
                $$timeable_type->times()->saveMany($open_hours);
            }

            if (isset($data['exceptions']) && !empty($data['exceptions'])) {
                $exceptions = array_map(
                    function ($exception) {
                        return new OpenHourException($exception);
                    },
                    $data['exceptions']
                );
                $$timeable_type->exceptionTimes()->saveMany($exceptions);
            }
        }

        return $station;
    }

    /**
     * Provides data for the timestamp samples
     *
     * @return array[]
     */
    public function timestamps_data_provider(): array
    {
        $error = [
            "timestamp" => [
                "The timestamp does not match the format U."
            ],
        ];

        return [
            [
                'timestamp' => 'invalid_timestamp',
                'status' => 422,
                'result' => ['errors' => $error]
            ],
            [
                'timestamp' => '6165654654u',
                'status' => 422,
                'result' => ['errors' => $error]
            ],
            [
                'timestamp' => 'i6165654654',
                'status' => 422,
                'result' => ['errors' => $error]
            ],
            [
                'timestamp' => time(),
                'status' => 200,
                'result' => []
            ],
        ];
    }

}
