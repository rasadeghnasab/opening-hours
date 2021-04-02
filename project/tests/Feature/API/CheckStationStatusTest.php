<?php

namespace Tests\Feature\API;

use App\Models\OpenHour;
use App\Models\Station;
use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckStationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected string $uri = '/api/v1/open_hours/stations';

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
            sprintf('%s/%s?timestamp=%s', $this->uri, $station->id, $timestamp)
        );

        $response->assertStatus($expected);

        if ($result) {
            $response->assertJson($result);
        }
    }

    /**
     * @test
     */
    public function should_return_a_boolean_as_station_status_check()
    {
        $station = Station::factory()->create();
        $timestamp = time();
        $response = $this->json(
            'GET',
            sprintf('%s/%s?timestamp=%s', $this->uri, $station->id, $timestamp)
        );

        $this->assertIsBool($response->json('data'));
    }

    /**
     * @test
     *
     * @dataProvider station_status_data_provider
     */
    public function should_return_correct_boolean_based_on_station_status($timeable_type, $timestamp, $status, $message): void
    {
        list($tenant, $store, $station) = $this->create_open_hour_for_station_status_check($timeable_type);

        $response = $this->json(
            'GET',
            sprintf('%s/%s?timestamp=%s', $this->uri, $station->id, $timestamp)
        );

        $response->assertStatus(200);
        $response->assertJson(['data' => $status]);
    }

    /**
     * Provides data for station status check
     */
    public function station_status_data_provider(): array
    {
        // valid values
        $day = 'monday';
        $from = '09:00';

        $timeables = ['stations', 'stores', 'tenants'];
        $data = [];

        foreach ($timeables as $timeable) {
            $data[] = [
                $timeable,
                strtotime("next ${day} 12:00"),
                true,
                'valid day and time',
            ];
            $data[] = [
                $timeable,
                strtotime("next ${day} ${from}"),
                true,
                'valid day and edge opening time',
            ];
            $data[] = [
                $timeable,
                strtotime("next ${day} 18:59:59"),
                true,
                'valid day and edge closing time',
            ];
            $data[] = [
                $timeable,
                strtotime("next sunday 12:00"),
                false,
                'invalid day',
            ];
            $data[] = [
                $timeable,
                strtotime("next ${day} 08:59"),
                false,
                'invalid opening time',
            ];
            $data[] = [
                $timeable,
                strtotime("next ${day} 19:01"),
                false,
                'invalid closing time',
            ];
            $data[] = [
                $timeable,
                strtotime("next tuesday 19:01"),
                false,
                'invalid day and closing time',
            ];
        }

        return $data;
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

    /**
     * Add open hours for tenant, store, or station
     *
     * @param $timeable_type
     * @return array
     */
    private function create_open_hour_for_station_status_check($timeable_type): array
    {
        $tenants = Tenant::factory()->create();
        $stores = Store::factory()->create(['tenant_id' => $tenants->id]);
        $stations = Station::factory()->create(['store_id' => $stores->id]);

        if (!$timeable_type) {
            return [$tenants, $stores, $stations];
        }

        $day = 1; // monday
        $from = '09:00';
        $to = '19:00';

        OpenHour::unguard();
        OpenHour::create(
            [
                'day' => $day,
                'from' => $from,
                'to' => $to,
                'timeable_type' => $timeable_type,
                'timeable_id' => $$timeable_type->id,
            ]
        );
        OpenHour::reguard();

        return [$tenants, $stores, $stations];
    }

}
