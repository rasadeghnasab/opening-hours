<?php

namespace Tests\Unit\Classes;

use App\Classes\DayPlan;
use App\Classes\ExceptionsHours;
use App\Models\OpenHourException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ExceptionHoursTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider applyExceptionsDataProvider
     *
     * @param Collection $plan
     * @param Collection $exceptions
     * @param Carbon $date
     * @param array $expected
     */
    public function exceptions_should_apply_to_day_plan(
        Collection $plan,
        Collection $exceptions,
        Carbon $date,
        array $expected
    ): void {
        OpenHourException::unguard();
        $exceptions = $exceptions->map(
            function ($exception) {
                return new OpenHourException($exception);
            }
        );
        OpenHourException::reguard();

        $plan = (new DayPlan($plan, $date))->fullPlan();
        $full_plan = (new ExceptionsHours($exceptions))->applyExceptions($plan, $date);

        $this->assertEquals($expected, $full_plan->toArray());
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function applyExceptionsDataProvider(): array
    {
        $date = Carbon::now();

        return [
            [
                'plan' => collect(
                    [
                        collect(
                            [
                                'from' => '08:00',
                                'to' => '18:00',
                                'status' => 1,
                                'day' => $date->dayOfWeek,
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
                'date' => Carbon::now()->setTime(00, 00),
                'expected' => [
                    [
                        "from" => "00:00",
                        "to" => "06:00:00",
                        "status" => 0,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "06:00:00",
                        "to" => "09:00:00",
                        "status" => 0,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "09:00:00",
                        "to" => "14:00:00",
                        "status" => 1,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "14:00:00",
                        "to" => "15:00:00",
                        "status" => 0,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "15:00:00",
                        "to" => "18:00",
                        "status" => 1,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "18:00",
                        "to" => "24:00",
                        "status" => 0,
                        'day' => $date->dayOfWeek,
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
                                'day' => $date->dayOfWeek,
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
                'date' => Carbon::now()->setTime(00, 00),
                'expected' => [
                    [
                        "from" => "00:00",
                        "to" => "06:00:00",
                        "status" => 0,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "06:00:00",
                        "to" => "09:00:00",
                        "status" => 0,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "09:00:00",
                        "to" => "18:00",
                        "status" => 1,
                        'day' => $date->dayOfWeek,
                    ],
                    [
                        "from" => "18:00",
                        "to" => "24:00",
                        "status" => 0,
                        'day' => $date->dayOfWeek,
                    ],
                ],
            ],
        ];
    }
}
