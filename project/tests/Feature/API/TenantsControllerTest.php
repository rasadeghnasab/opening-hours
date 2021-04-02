<?php

namespace Tests\Feature\API;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $uri = '/api/v1/tenants';

    /**
     *
     */

    /**
     * @test
     */
    public function tenant_name_is_mandatory(): void
    {
        $response = $this->json('POST', $this->uri, []);

        $response->assertStatus(422)
            ->assertJson(
                [
                    "message" => "The given data was invalid.",
                    "errors" => [
                        "name" => [
                            "The name field is required."
                        ]
                    ]
                ]
            );
    }

    /**
     * @test
     */
    public function tenant_can_be_created_successfully(): void
    {
        $response = $this->json(
            'POST',
            $this->uri,
            [
                'name' => 'tenant_01_name'
            ]
        );

        $response->assertStatus(201)
            ->assertJson(
                [
                    'message' => 'Tenant created successfully.',
                ]
            );
    }

    /**
     * @test
     * @dataProvider tenants_list_data
     *
     * @param int $count
     */
    public function get_first_10_tenants(int $count): void
    {
        Tenant::factory()->count($count)->create();

        $response = $this->json('GET', $this->uri);

        $response->assertStatus(200)
            ->assertJsonCount($count, 'data');
    }

    /**
     * @test
     */
    public function return_404_if_tenant_doesnot_exist(): void
    {
        $response = $this->json('GET', sprintf('%s/%s', $this->uri, 'wrong_tenant_id'));

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function return_tenant_if_exists(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->json('GET', sprintf('%s/%s', $this->uri, $tenant->id));

        $response->assertStatus(200)
            ->assertJson(
                [
                    'data' =>
                        [
                            'tenant' => $tenant->toArray()
                        ]
                ]
            );
    }

    /**
     * Data for tenants list check
     * NOTE: please note that this is a simple listing method for more complex requirements we should write more complex
     * data provider
     *
     * @return \int[][]
     */
    public function tenants_list_data(): array
    {
        return [
            [
                'count' => 10,
            ],
            [
                'count' => 3,
            ],
            [
                'count' => 5
            ]
        ];
    }
}
