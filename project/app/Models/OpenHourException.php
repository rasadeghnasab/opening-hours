<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OpenHourException extends Model
{
    use HasFactory;

    protected $fillable = ['from', 'to', 'status', 'comment'];

    protected $dates = [
        'from',
        'to'
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param Builder $query
     * @param Station $station
     * @param int $timestamp
     * @return Builder|Model|object|null
     */
    public function scopeExists(Builder $query, Station $station, int $timestamp)
    {
        // turn timestamp to carbon time
        $carbon_time = Carbon::createFromTimestamp($timestamp);

        $timeables_priority = TimeablePriority::all();
        $priorities = implode(
            ",",
            $timeables_priority->pluck('name')->reduce(
                function ($carry, $priority) {
                    $carry[] = "timeable_type='${priority}' DESC";
                    return $carry;
                },
                []
            )
        );

        /**
         * return     * Please note that this is here only for the review purposes.
         * ***** Sample query structure
         *
         * select * from "open_hours"
         * where (
         *  ("timeable_type" = 'stations' and "timeable_id" = '300')
         *  or ("timeable_type" = 'stores' and "timeable_id" = '10')
         *  or ("timeable_type" = 'tenants' and "timeable_id" = '1')
         * )
         * and "from" <= '2021-04-10 18:59:00'
         * and "to" >= '2021-04-10 18:59:00'
         * order by timeable_type=stations DESC, timeable_type=store DESC, timeable_type=tenants DESC
         */
        return $query->where(
            function ($query) use ($station) {
                $query->where(
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
            ->where(
                function ($query) use ($carbon_time) {
                    $query->where('from', '<=', $carbon_time)
                        ->where('to', '>=', $carbon_time);
                }
            )
            ->orderByRaw(DB::raw($priorities));
    }
}
