<?php

namespace Tests\Feature\API;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenHoursControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $uri = '/api/v1/open_hours';

    /**
     * @test
     */
    public function timable_should_be_valid()
    {
        $timeable_type = 'a_wrong_timeblae';
        $timeable_id = 1;

        $response = $this->json('POST',
                                sprintf('%s/%s/%s', $this->uri, $timeable_type, $timeable_id)
        );

        $response->assertStatus(409);
    }
}
