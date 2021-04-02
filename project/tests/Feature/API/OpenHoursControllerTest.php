<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenHoursControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $uri = '/api/v1/open_hours';

    protected array $timeables;

    public function setUp(): void
    {
        parent::setUp();

        $this->timeables = config('timeables');
    }

    /**
     * @test
     */
    public function timeable_should_be_valid(): void
    {
        $valid_timeables = array_keys($this->timeables);
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
     * @dataProvider store_input_data_provider
     *
     * @param array $data
     * @param array $expected
     */
    public function open_hour_input_values_must_be_valid(array $data, array $expected): void
    {
        $valid_timeables = array_keys($this->timeables);

        $response = $this->json(
            'POST',
            sprintf('%s/%s/%s', $this->uri, $valid_timeables[array_rand($valid_timeables)], 1),
            $data
        );

        $response->assertStatus($expected['status'])
            ->assertJson($expected['result']);
    }

    /**
     * @test
     */
    public function should_returns_404_if_timeable_not_found(): void
    {
        $timeables = $this->timeables;

        foreach ($timeables as $timeable => $timeable_class) {
            $$timeable = $timeable_class::factory()->create();

            try {
                $$timeable->delete();
            } catch (\Exception $exception) {
                $this->assertTrue(false, 'There is a problem with deleting models');
            }
            $response = $this->json(
                'POST',
                sprintf('%s/%s/%s', $this->uri, $timeable, $$timeable->id),
                []
            );

            $response->assertStatus(404);
        }
    }

    /**
     * @test
     */
    public function an_open_hour_should_submitted_successfully_using_valid_data(): void
    {
        $timeables = $this->timeables;

        $data = [
            'day' => 4,
            'from' => '09:10',
            'to' => '18:20',
        ];

        foreach ($timeables as $timeable => $timeable_class) {
            $$timeable = $timeable_class::factory()->create();

            $response = $this->json(
                'POST',
                sprintf('%s/%s/%s', $this->uri, $timeable, $$timeable->id),
                $data
            );

            $response->assertStatus(200)
                ->assertJson(
                    [
                        'data' => [
                            'open_hour' => $data
                        ]
                    ]
                );
        }
    }

    /**
     * Data provider for input validation
     *
     * @return \array[][]
     */
    public function store_input_data_provider(): array
    {
        $valid_from = '09:00';
        $valid_to = '18:00';

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
