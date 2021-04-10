<?php

namespace App\Traits;

use App\Models\TimeablePriority;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HoursScopeTrait
{
    public function scopeOrderByPriority($query): Builder
    {
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

        return $query->orderByRaw(DB::raw($priorities));
    }

    /**
     * @param $query
     * @param Carbon $date_time
     * @return Builder
     */
    public function scopeInTime($query, Carbon $date_time): Builder
    {
        $time = $this->convertTime($date_time);

        return $query->where(
            function ($query) use ($time) {
                $query->where('from', '<=', $time)
                    ->where('to', '>=', $time);
            }
        );
    }

    /**
     * @param $query
     * @param Carbon $date_time
     * @return Builder
     */
    public function scopeIsAfter($query, Carbon $date_time): Builder
    {
        return $query->where('from', '>=', $this->convertTime($date_time));
    }
}
