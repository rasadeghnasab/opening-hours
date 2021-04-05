<?php

namespace Tests\Unit\Helpers;

use Illuminate\Support\Collection;
use Tests\TestCase;

class HelperTest extends TestCase
{
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
                        [
                            'from' => '00:00',
                            'to' => '24:00',
                            'status' => 1,
                            'day' => 3,
                        ]
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
                        [
                            'from' => '10:20',
                            'to' => '13:50',
                            'status' => 1,
                            'day' => 3,
                        ]
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
                        [
                            'from' => '08:00',
                            'to' => '12:00',
                            'status' => 1,
                            'day' => 3,
                        ],
                        [
                            'from' => '15:00',
                            'to' => '18:00',
                            'status' => 1,
                            'day' => 3,
                        ],
                        [
                            'from' => '19:00',
                            'to' => '21:00',
                            'status' => 1,
                            'day' => 3,
                        ],
                        [
                            'from' => '21:00',
                            'to' => '22:00',
                            'status' => 1,
                            'day' => 3,
                        ],
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
            ]
        ];
    }
}
