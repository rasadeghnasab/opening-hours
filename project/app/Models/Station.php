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

        return $this->openHourMainQuery($open_hour)
            ->where('day', $date_time->dayOfWeek)
            ->fromTime($date_time)
            ->exists();
    }

    /**
     * Return the station current status
     * @param int $timestamp
     * @return bool
     */
    public function currentState(int $timestamp): bool
    {
        $date_time = Carbon::createFromTimestamp($timestamp);

        $open_hour_exception = $this->exceptions()
            ->fromTime($date_time)
            ->orderByPriority()
            ->first();

        return $open_hour_exception->status ?? $this->isOpen($timestamp);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @return Builder|Model|object|null
     */
    private function exceptions(): Builder
    {
        $open_hour_exception = App::make(OpenHourInterface::class, ['exception']);

        return $this->openHourMainQuery($open_hour_exception);
    }

    private function openHourMainQuery(OpenHourInterface $open_hour): Builder
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
         */
        return $open_hour->where(
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
        );
    }
}
