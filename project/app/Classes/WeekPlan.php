<?php

namespace App\Classes;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class WeekPlan
{
    /**
     * @var Collection
     */
    private Collection $plan;

    public function __construct(Collection $plan)
    {
        $this->plan = $plan->sortBy('from')->groupBy('day');
    }

    /**
     * @param Carbon $date
     * @return DayPlan
     */
    public function day(Carbon $date): DayPlan
    {
        $day_plan = $this->plan[$date->dayOfWeek] ?? collect();

        return new DayPlan($day_plan, $date);
    }
}
