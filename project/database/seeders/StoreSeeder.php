<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Tenant;
use Faker\Factory;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenants = Tenant::all();
        $faker = Factory::create();

        foreach ($tenants as $tenant) {
            Store::factory()->count(4)->create(
                [
                    'tenant_id' => $tenant->id,
                    'name' => sprintf('%s:%s', $tenant->name, $faker->name),
                ]
            );
        }
    }
}
