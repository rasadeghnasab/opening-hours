<?php

namespace App\Classes;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class DayPlan
{
    /**
     * @var Collection
     */
    private Collection $plan;
    /**
     * @var Carbon
     */
    private Carbon $date;

    public function __construct(Collection $plan, Carbon $date)
    {
        $this->plan = $plan->sortBy('from');
        $this->date = $date;
    }

    public function fullPlan($start_time = '00:00', $end_time = '24:00'): Collection
    {
        $next_start = $end_time;
        $output = collect();
        $min = $this->plan->min('from');
        $max = $this->plan->max('to');
        $day = $this->date->dayOfWeek;

        if ($min > $start_time) {
            $output->push(
                [
                    'from' => $start_time,
                    'to' => $min,
                    'status' => 0,
                    'day' => $day,
                ]
            );
            $next_start = $min;
        }

        foreach ($this->plan as $time_range) {
            if ($time_range['from'] > $next_start) {
                $output->push(
                    [
                        'from' => $next_start,
                        'to' => $time_range['from'],
                        'status' => 0,
                        'day' => $day,
                    ]
                );
            }

            if ($time_range['from'] >= $next_start) {
                $output->push($time_range->only('from', 'to', 'day', 'status'));
                $next_start = $time_range['to'];
            }
        }

        if ($max < $end_time) {
            $output->push(
                [
                    'from' => $max ?? $start_time,
                    'to' => $end_time,
                    'status' => 0,
                    'day' => $day,
                ]
            );
        }

        if ($output->isEmpty() && !$this->plan->isEmpty()) {
            $output = $output->merge($this->plan);
        }

        return $output;
        $full_day_plan = collect();
        $min = $this->plan->min('from');
        $max = $this->plan->max('to');
        $day = $this->date->dayOfWeek;
        $next_start = $start_time;

        if ($min > $start_time) {
            $full_day_plan->push(
                [
                    'from' => $start_time,
                    'to' => $min,
                    'status' => 0,
                    'day' => $day,
                ]
            );
        }

        foreach ($this->plan as $time_range) {
            if ($next_start >= $end_time) {
                break;
            }

            if ($time_range['from'] > $next_start) {
                $full_day_plan->push(
                    [
                        'from' => $next_start,
                        'to' => $time_range['from'],
                        'status' => 0,
                        'day' => $day,
                    ]
                );
            }

            if ($time_range['from'] >= $next_start) {
                $full_day_plan->push(
                    [
                        'from' => $time_range['from'],
                        'to' => $time_range['to'],
                        'status' => $time_range['status'],
                        'day' => $day,
                    ]
                );
                $next_start = $time_range['to'];
            }
        }

        if ($max < $end_time) {
            $full_day_plan->push(
                [
                    'from' => $max ?? $start_time,
                    'to' => $end_time,
                    'status' => 0,
                    'day' => $day,
                ]
            );
        }

        if ($full_day_plan->isEmpty() && !$this->plan->isEmpty()) {
            $full_day_plan = $full_day_plan->merge($this->plan);
        }

        return $full_day_plan;
    }
}
