<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenHoursControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $uri = '/api/v1/open_hours';

    /**
     * @test
     */
    public function timeable_should_be_valid(): void
    {
        $valid_timeables = array_keys(config('timeables'));
        $timeable_type = 'a_wrong_timeable';
        $timeable_id = 1;

        $response = $this->json(
            'POST',
            sprintf('%s/%s/%s', $this->uri, $timeable_type, $timeable_id)
        );

        $this->assertNotContains($timeable_type, $valid_timeables);
        $response->assertStatus(405);
    }

    /**
     * @test
     * @dataProvider store_input_data
     *
     * @param array $data
     * @param array $expected
     */
    public function open_hour_posted_values_must_be_valid(array $data, array $expected): void
    {
        $valid_timeables = array_keys(config('timeables'));

        $response = $this->json(
            'POST',
            sprintf('%s/%s/%s', $this->uri, $valid_timeables[array_rand($valid_timeables)], 1),
            $data
        );

        $response->assertStatus($expected['status'])
            ->assertJson($expected['result']);
    }

    /**
     * Data provider for input validation
     *
     * @return \array[][]
     */
    public function store_input_data()
    {
        $valid_from = '09:00';
        $valid_to = '18:00';

        $valid_data = [
            "day" => 1,
            "from" => $valid_from,
            "to" => $valid_to,
        ];

        return [
            [
                'data' => [],
                'expected' => [
                    'status' => 422,
                    'result' => [
                        'errors' => [
                            'day' => [
                                'The day field is required.',
                            ],
                            'from' => [
                                'The from field is required.',
                            ],
                            'to' => [
                                'The to field is required.',
                            ]
                        ]

                    ]
                ]
            ],
            [
                'data' => [
                    'day' => 8,
                    'from' => $valid_from,
                    'to' => $valid_to
                ],
                'expected' => [
                    'status' => 422,
                    'result' => [
                        'errors' => [
                            'day' => [
                                'The selected day is invalid.'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'day' => 6,
                    'from' => '12:60',
                    'to' => '24:00',
                ],
                'expected' => [
                    'status' => 422,
                    'result' => [
                        'errors' => [
                            'from' => [
                                'The from does not match the format H:i.',
                            ],
                            'to' => [
                                'The to does not match the format H:i.',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'day' => 6,
                    'from' => '12:20',
                    'to' => '10:50',
                ],
                'expected' => [
                    'status' => 422,
                    'result' => [
                        'errors' => [
                            'to' => [
                                'The to must be a date after from.',
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
