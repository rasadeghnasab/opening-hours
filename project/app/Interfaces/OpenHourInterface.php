<?php

namespace App\Interfaces;

use Carbon\Carbon;

interface OpenHourInterface
{
    public static function query();

    /**
     * Receives a carbon instance and returns the time representation for that table as a string
     * @param Carbon $date_time
     * @return string
     */
    public function convertTime(Carbon $date_time): string;
}
