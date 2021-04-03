<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenHour extends Model
{
    use HasFactory;

    protected $fillable = ['day', 'from', 'to'];

    /**
     * Retrieve any timeable entity
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function timeable()
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param Builder $query
     * @param Station $station
     * @param int $timestamp
     * @return Builder
     */
    public function scopeIsOpen(Builder $query, Station $station, int $timestamp)
    {
        // turn timestamp to day and time
        $carbon_time = Carbon::createFromTimestamp($timestamp);
        $day = $carbon_time->dayOfWeek;
        $time = $carbon_time->format('H:i:s');

        /**
         * Please note that this is here only for the review purposes.
         * ***** Sample query structure
         *
         * select * from "open_hours"
         * where (
         *  ("timeable_type" = 'stations' and "timeable_id" = '1')
         *  or ("timeable_type" = 'stores' and "timeable_id" = '1')
         *  or ("timeable_type" = 'tenants' and "timeable_id" = '1')
         * )
         * and "day" = '1'
         * and "from" <= '18:59:00'
         * and "to" >= '18:59:00'
         */
        return $query->where(
            function ($query) use ($station) {
                $query->orWhere(
                    function ($inner_query) use ($station) {
                        $inner_query->where('timeable_type', 'stations')
                            ->where('timeable_id', $station->id);
                    }
                );
                $query->orWhere(
                    function ($query) use ($station) {
                        $query->where('timeable_type', 'stores')
                            ->where('timeable_id', $station->store_id);
                    }
                );
                $query->orWhere(
                    function ($inner_query) use ($station) {
                        $inner_query->where('timeable_type', 'tenants')
                            ->where('timeable_id', $station->store->tenant_id);
                    }
                );
            }
        )
            ->where('day', $day)
            ->where('from', '<=', $time)
            ->where('to', '>=', $time);
    }
}
