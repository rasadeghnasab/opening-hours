<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $stores = Store::all();

        Station::unguard();
        foreach ($stores as $store) {
            Station::factory()->count(10)->create(
                [
                    'store_id' => $store->id,
                ]
            );
        }
    }
}
