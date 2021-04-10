<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateTimeablesPriorityData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('timeables_priority')->insert(
            [
                [
                    'name' => 'tenants',
                    'priority' => 1,
                    "created_at" =>  Carbon::now(),
                    "updated_at" => Carbon::now(),
                ],
                [
                    'name' => 'stores',
                    'priority' => 2,
                    "created_at" =>  Carbon::now(),
                    "updated_at" => Carbon::now(),
                ],
                [
                    'name' => 'stations',
                    'priority' => 3,
                    "created_at" =>  Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('timeables_priority')->delete();
    }
}
