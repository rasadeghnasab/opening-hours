<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Store::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $tenant = Tenant::factory()->create();

        return [
            'name' => sprintf('%s:%s', $tenant->name, $this->faker->name),
            'tenant_id' => $tenant->id,
        ];
    }
}
