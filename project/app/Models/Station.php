<?php

namespace App\Models;

use App\Interfaces\TimeableInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Station extends Model implements TimeableInterface
{
    use HasFactory;

    public function times(): MorphMany
    {
        return $this->morphMany(OpenHour::class, 'timeable');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return string
     */
    public function parent(): string
    {
        return 'store';
    }

    /**
     * @param $timestamp
     * @return mixed
     */
    public function isOpen($timestamp)
    {
        // turn timestamp to day and time
        $carbon_time = Carbon::createFromTimestamp($timestamp);
        $day = $carbon_time->dayOfWeek;
        $time = $carbon_time->format('H:i:s');

        // preload store and tenant to make the query more faster
        $this->load('store', 'store.tenant');

        /**
         * Please note that this is here only for the review purposes.
         * ***** Sample query structure
         *
         * select * from "open_hours"
         * where (
         *  ("timeable_type" = 'stations' and "timeable_id" = '300')
         *  or ("timeable_type" = 'stores' and "timeable_id" = '10')
         *  or ("timeable_type" = 'tenants' and "timeable_id" = '1')
         * )
         * and "day" = '1'
         * and "from" <= '18:59:00'
         * and "to" >= '18:59:00'
         */
        return $this->times()->getRelated()->where(
            function ($query) {
                // dynamically create the query
                // ** NOTE: this gives us the power to add more entities in future without
                // adding any other logic to our query **
                $child = $this;
                while ($child) {
                    $parent = $child->parent();
                    $query->orWhere(
                        function ($inner_query) use ($child) {
                            $inner_query->where('timeable_type', $child->getMorphClass())
                                ->where('timeable_id', $child->id);
                        }
                    );
                    $child = $child[$parent] ?? null;
                }
            }
        )
            ->where('day', $day)
            ->where('from', '<=', $time)
            ->where('to', '>=', $time)
            ->exists();
    }
}
