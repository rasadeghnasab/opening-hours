<?php

namespace Tests\Unit\Classes;

use App\Classes\DayPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DayPlanTest extends TestCase
{
    /**
     * @test
     * @dataProvider dayPlanDataProvider
     *
     * @param Collection $day_plan
     * @param Carbon $date
     * @param Collection $expected
     */
    public function can_generate_full_day_plan_for_a_day(Collection $day_plan, Carbon $date, Collection $expected): void
    {
        $day_plan = (new DayPlan($day_plan, $date))->fullPlan();

        $this->assertEquals($expected->toArray(), $day_plan->toArray());
    }

    public function dayPlanDataProvider(): array
    {
        $date = Carbon::now();

        return [
            [
                'day_times' => collect([]),
                'date' => $date,
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '24:00',
                            'status' => 0,
                            'day' => $date->dayOfWeek
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
                                'day' => $date->dayOfWeek,
                            ]
                        )
                    ]
                ),
                'date' => $date,
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '24:00',
                            'status' => 1,
                            'day' => $date->dayOfWeek,
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
                                'day' => $date->dayOfWeek,
                            ]
                        )
                    ]
                ),
                'date' => $date,
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '10:20',
                            'status' => 0,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            'from' => '10:20',
                            'to' => '13:50',
                            'status' => 1,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            'from' => '13:50',
                            'to' => '24:00',
                            'status' => 0,
                            'day' => $date->dayOfWeek,
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
                                'day' => $date->dayOfWeek,
                            ]
                        ),
                        collect(
                            [
                                'from' => '21:00',
                                'to' => '22:00',
                                'status' => 1,
                                'day' => $date->dayOfWeek,
                            ]
                        ),
                        collect(
                            [
                                'from' => '19:00',
                                'to' => '21:00',
                                'status' => 1,
                                'day' => $date->dayOfWeek,
                            ]
                        ),
                        collect(
                            [
                                'from' => '15:00',
                                'to' => '18:00',
                                'status' => 1,
                                'day' => $date->dayOfWeek,
                            ]
                        ),
                    ]
                ),
                'date' => $date,
                'expected' => collect(
                    [

                        [
                            "from" => "00:00",
                            "to" => "08:00",
                            "status" => 0,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            "from" => "08:00",
                            "to" => "12:00",
                            "status" => 1,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            "from" => "12:00",
                            "to" => "15:00",
                            "status" => 0,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            "from" => "15:00",
                            "to" => "18:00",
                            "status" => 1,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            "from" => "18:00",
                            "to" => "19:00",
                            "status" => 0,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            "from" => "19:00",
                            "to" => "21:00",
                            "status" => 1,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            "from" => "21:00",
                            "to" => "22:00",
                            "status" => 1,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            "from" => "22:00",
                            "to" => "24:00",
                            "status" => 0,
                            'day' => $date->dayOfWeek,
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
                                'day' => $date->dayOfWeek,
                            ]
                        ),
                        collect(
                            [
                                'from' => '08:00',
                                'to' => '18:00',
                                'status' => 1,
                                'day' => $date->dayOfWeek,
                            ]
                        ),
                    ]
                ),
                'date' => $date,
                'expected' => collect(
                    [
                        [
                            'from' => '00:00',
                            'to' => '04:00',
                            'status' => 0,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            'from' => '04:00',
                            'to' => '19:00',
                            'status' => 1,
                            'day' => $date->dayOfWeek,
                        ],
                        [
                            'from' => '19:00',
                            'to' => '24:00',
                            'status' => 0,
                            'day' => $date->dayOfWeek,
                        ],
                    ]
                )
            ],

        ];
    }
}
