<?php

namespace App\Models;

use App\Interfaces\OpenHourInterface;
use App\Interfaces\TimeableInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

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
    public function isOpen($timestamp): bool
    {
        $date_time = Carbon::createFromTimestamp($timestamp);
        $open_hour = App::make(OpenHourInterface::class);

        return $this->openHourMainQuery($open_hour, $date_time->format('H:i:s'))
            ->where('day', $date_time->dayOfWeek)
            ->exists();
    }

    /**
     * Return the station current status
     * @param int $timestamp
     * @return bool
     */
    public function currentState(int $timestamp): bool
    {
        $open_hour_exception = $this->exceptions($timestamp)->first();

        return $open_hour_exception->status ?? $this->isOpen($timestamp);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param int $timestamp
     * @return Builder|Model|object|null
     */
    public function exceptions(int $timestamp): Builder
    {
        // turn timestamp to carbon time
        $date_time = Carbon::createFromTimestamp($timestamp);

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
         * Please note that this is here only for the review purposes.
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
        $open_hour_exception = App::make(OpenHourInterface::class, ['exception']);

        return $this
            ->openHourMainQuery($open_hour_exception, $date_time->toDateTimeString())
            ->orderByRaw(DB::raw($priorities));
    }

    private function openHourMainQuery(OpenHourInterface $open_hour, string $time): Builder
    {
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
        return $open_hour->query()->where(
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
            ->where(
                function ($query) use ($time) {
                    $query->where('from', '<=', $time)
                        ->where('to', '>=', $time);
                }
            );
    }
}
