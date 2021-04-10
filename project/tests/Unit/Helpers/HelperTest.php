<?php

namespace Tests\Unit\Helpers;

use App\Models\OpenHourException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class HelperTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider weekDayNumberDataProvider
     * @param int $day
     * @param array $expected
     */
    public function weekDaysNumberStartFrom_test(int $day, array $expected): void
    {
        $this->assertEquals(weekDaysNumberStartFrom($day), $expected);
    }

    /**
     * @test
     * @dataProvider dayPlanDataProvider
     *
     * @param Collection $day_times
     * @param Collection $expected
     */
    public function dayPlan_test(Collection $day_times, Collection $expected): void
    {
        $this->assertEquals($expected->toArray(), dayPlan($day_times)->toArray());
    }

    /**
     * @test
     * @dataProvider applyExceptionsDataProvider
     *
     * @param Collection $plan
     * @param Collection $exceptions
     * @param array $expected
     */
    public function applyExceptions_test(Collection $plan, Collection $exceptions, array $expected): void
    {
        OpenHourException::unguard();
        $exceptions = $exceptions->map(
            function ($exception) {
                return new OpenHourException($exception);
            }
        );
        OpenHourException::reguard();

        $plan = dayPlan($plan);
        $result = applyExceptions($plan, $exceptions);

        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function applyExceptionsDataProvider(): array
    {
        return [
            [
                'plan' => collect(
                    [
                        collect(
                            [
                                'from' => '08:00',
                                'to' => '18:00',
                                'status' => 1,
                            ]
                        )
                    ]
                ),
                'exceptions' => collect(
                    [
                        [
                            'from' => Carbon::now()->setTime(6, 00),
                            'to' => Carbon::now()->setTime(9, 00),
                            'status' => 0,
                            'comment' => 'simple_comment',
                            'timeable_type' => 'stations'
                        ],
                        [
                            'from' => Carbon::now()->setTime(14, 00),
                            'to' => Carbon::now()->setTime(15, 00),
                            'status' => 0,
                            'comment' => 'simple_comment',
                            'timeable_type' => 'stations'
                        ],
                    ]
                ),
                'expected' => [
                    [
                        "from" => "00:00",
                        "to" => "06:00:00",
                        "status" => 0,
                        'day' => null,
                    ],
                    [
                        "from" => "06:00:00",
                        "to" => "09:00:00",
                        "status" => 0,
                        'day' => 6,
                    ],
                    [
                        "from" => "09:00:00",
                        "to" => "14:00:00",
                        "status" => 1,
                        'day' => null,
                    ],
                    [
                        "from" => "14:00:00",
                        "to" => "15:00:00",
                        "status" => 0,
                        'day' => 6
                    ],
                    [
                        "from" => "15:00:00",
                        "to" => "18:00",
                        "status" => 1,
                        'day' => null,
                    ],
                    [
                        "from" => "18:00",
                        "to" => "24:00",
                        "status" => 0,
                        "day" => null,
                    ],
                ],
            ],

            [
                'plan' => collect(
                    [
                        collect(
                            [
                                'from' => '08:00',
                                'to' => '18:00',
                                'status' => 1,
                            ]
                        )
                    ]
                ),
                'exceptions' => collect(
                    [
                        [
                            'from' => Carbon::now()->setTime(6, 00),
                            'to' => Carbon::now()->setTime(9, 00),
                            'status' => 0,
                            'comment' => 'simple_comment',
                            'timeable_type' => 'stations'
                        ],
                    ]
                ),
                'expected' => [
                    [
                        "from" => "00:00",
                        "to" => "06:00:00",
                        "status" => 0,
                        'day' => null,
                    ],
                    [
                        "from" => "06:00:00",
                        "to" => "09:00:00",
                        "status" => 0,
                        'day' => 6,
                    ],
                    [
                        "from" => "09:00:00",
                        "to" => "18:00",
                        "status" => 1,
                        'day' => null,
                    ],
                    [
                        "from" => "18:00",
                        "to" => "24:00",
                        "status" => 0,
                        "day" => null,
                    ],
                ],
            ],
        ];
    }

    public function weekDayNumberDataProvider(): array
    {
        return [
            [
                'day' => 0,
                'expected' => [0, 1, 2, 3, 4, 5, 6]
            ],
            [
                'day' => 1,
                'expected' => [1, 2, 3, 4, 5, 6, 0]
            ],
            [
                'day' => 2,
                'expected' => [2, 3, 4, 5, 6, 0, 1]
            ],
            [
                'day' => 3,
                'expected' => [3, 4, 5, 6, 0, 1, 2]
            ],
            [
                'day' => 4,
                'expected' => [4, 5, 6, 0, 1, 2, 3]
            ],
            [
                'day' => 5,
                'expected' => [5, 6, 0, 1, 2, 3, 4]
            ],
            [
                'day' => 6,
                'expected' => [6, 0, 1, 2, 3, 4, 5]
            ],
        ];
    }

    public function dayPlanDataProvider(): array
    {
        return [
            [
                'day_times' => collect([]),
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '24:00',
                            'status' => 0,
                            'day' => null
                        ]
                    ]
                )
            ],

            [
                'day_times' => collect(
                    [
                        collect(
                            [
                                'from' => '00:00',
                                'to' => '24:00',
                                'status' => 1,
                                'day' => 3,
                            ]
                        )
                    ]
                ),
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '24:00',
                            'status' => 1,
                            'day' => 3,
                        ]
                    ]
                )
            ],

            [
                'day_times' => collect(
                    [
                        collect(
                            [
                                'from' => '10:20',
                                'to' => '13:50',
                                'status' => 1,
                                'day' => 3,
                            ]
                        )
                    ]
                ),
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '10:20',
                            'status' => 0,
                            'day' => 3,
                        ],
                        [
                            'from' => '10:20',
                            'to' => '13:50',
                            'status' => 1,
                            'day' => 3,
                        ],
                        [
                            'from' => '13:50',
                            'to' => '24:00',
                            'status' => 0,
                            'day' => 3,
                        ]
                    ]
                )
            ],

            [
                'day_times' => collect(
                    [
                        collect(
                            [
                                'from' => '08:00',
                                'to' => '12:00',
                                'status' => 1,
                                'day' => 3,
                            ]
                        ),
                        collect(
                            [
                                'from' => '21:00',
                                'to' => '22:00',
                                'status' => 1,
                                'day' => 3,
                            ]
                        ),
                        collect(
                            [
                                'from' => '19:00',
                                'to' => '21:00',
                                'status' => 1,
                                'day' => 3,
                            ]
                        ),
                        collect(
                            [
                                'from' => '15:00',
                                'to' => '18:00',
                                'status' => 1,
                                'day' => 3,
                            ]
                        ),
                    ]
                ),
                'expected' => collect(
                    [

                        [
                            "from" => "00:00",
                            "to" => "08:00",
                            "status" => 0,
                            'day' => 3,
                        ],
                        [
                            "from" => "08:00",
                            "to" => "12:00",
                            "status" => 1,
                            'day' => 3,
                        ],
                        [
                            "from" => "12:00",
                            "to" => "15:00",
                            "status" => 0,
                            'day' => 3,
                        ],
                        [
                            "from" => "15:00",
                            "to" => "18:00",
                            "status" => 1,
                            'day' => 3,
                        ],
                        [
                            "from" => "18:00",
                            "to" => "19:00",
                            "status" => 0,
                            'day' => 3,
                        ],
                        [
                            "from" => "19:00",
                            "to" => "21:00",
                            "status" => 1,
                            'day' => 3,
                        ],
                        [
                            "from" => "21:00",
                            "to" => "22:00",
                            "status" => 1,
                            'day' => 3,
                        ],
                        [
                            "from" => "22:00",
                            "to" => "24:00",
                            "status" => 0,
                            'day' => 3,
                        ],
                    ]
                )
            ],

            [
                'day_times' => collect(
                    [
                        collect(
                            [
                                'from' => '04:00',
                                'to' => '19:00',
                                'status' => 1,
                                'day' => 2,
                            ]
                        ),
                        collect(
                            [
                                'from' => '08:00',
                                'to' => '18:00',
                                'status' => 1,
                                'day' => 2,
                            ]
                        ),
                    ]
                ),
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '04:00',
                            'status' => 0,
                            'day' => 2,
                        ],
                        [
                            'from' => '04:00',
                            'to' => '19:00',
                            'status' => 1,
                            'day' => 2,
                        ],
                        [
                            'from' => '19:00',
                            'to' => '24:00',
                            'status' => 0,
                            'day' => 2,
                        ],
                    ]
                )
            ],
        ];
    }
}
