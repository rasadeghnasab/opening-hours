<?php

use App\Models\TimeablePriority;
use Carbon\Carbon;
use Illuminate\Support\Collection;

if (!function_exists('weekDaysNumberStartFrom')) {
    /**
     * Gets a week day number and
     * returns all the week days numbers starting from the given day
     *
     * @param int $start_day
     * @return array
     */
    function weekDaysNumberStartFrom(int $start_day): array
    {
        $ranges = array_merge(range($start_day, 6), range(0, $start_day - 1));

        return array_splice($ranges, 0, 7);
    }
}

if (!function_exists('dayPlan')) {
    /**
     * Returns a list of open and close hours in a given day
     *
     * @param Collection $day_times
     * @param string $start_time
     * @param string $end_time
     * @return Collection
     */
    function dayPlan(Collection $day_times, string $start_time = '00:00', string $end_time = '24:00'): Collection
    {
//        $day_times->push([
//            'from' => $start_time,
//            'to' => $end_time
//                         ]);
//        dump($day_times->pluck('from')->sort());
//        dd($day_times->pluck('to')->sort());
        $froms = $day_times->pluck('from')->sort();
        $tos = $day_times->pluck('to')->sort();
//        dd($all);

        $time_ranges = $froms->combine($tos);
        dd($time_ranges);
//        dd($time_ranges->map(function($time_range, $index) {
//            dd($index,$time_range);
//        }));

        return 'h';
        $day = $day_times->whereNotNull('day')->first()['day'] ?? null;
        $next_start = $end_time;
        $output = collect();
        $min = $day_times->min('from');
        $max = $day_times->max('to');

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

        foreach ($day_times->sortBy('from') as $time_range) {
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

        if ($output->isEmpty() && !$day_times->isEmpty()) {
            $output = $output->merge($day_times);
        }

        return $output;
    }
}

if (!function_exists('applyExceptions')) {
    function applyExceptions(Collection $plan, Collection $exceptions, Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();

        $timeables_priority = TimeablePriority::orderBy('priority')->get();
        $exceptions = $exceptions->groupBy('timeable_type');

        foreach ($timeables_priority as $timeable) {
            if (!isset($exceptions[$timeable->name])) {
                continue;
            }

            $plan = overwriteTimes($plan, $exceptions[$timeable->name], $date);
        }

        return $plan;
    }
}

if (!function_exists('overwriteTimes')) {
    function overwriteTimes(Collection $plan, Collection $exceptions, Carbon $date): Collection
    {
        $output = collect();
        $exceptions = $exceptions->sortBy('from');
        $plan = $plan->sortBy('from');

        $start = '00:00';
        foreach ($exceptions as $index => $exception) {
            $exception_from = $exception->from->toDateString() < $date->toDateString(
            ) ? "00:00" : $exception->from->toTimeString();
            $exception_to = $exception->to->toDateString() > $date->toDateString(
            ) ? '24:00' : $exception->to->toTimeString();

            foreach ($plan as $time_range) {
                // exception and time_range have intersect
                $has_intersect = ($time_range['from'] < $exception_to) && ($time_range['to'] > $exception_from);

                if (!$has_intersect) {
                    if ($start <= $time_range['from']) {
                        $output->push($time_range);
                        $start = $time_range['to'];
                    }
                    continue;
                }

                $from = $start < $exception_from ? $start : null;
                $to = $time_range['to'] < $exception_to ? null : $time_range['to'];

                if (is_null($from) && is_null($to)) {
                    $start = $time_range['to'];
                    continue;
                } elseif ($from && $to) {
                    $output->push(
                        [
                            'from' => $from,
                            'to' => $exception_from,
                            'status' => $time_range['status'],
                            'day' => $time_range['day'] ?? null,
                        ]
                    );
                    $output->push(
                        [
                            'from' => $exception_to,
                            'to' => $to,
                            'status' => $time_range['status'],
                            'day' => $time_range['day'] ?? null,
                        ]
                    );
                    $start = $to;
                    continue;
                } elseif ($from && is_null($to)) {
                    $output->push(
                        [
                            'from' => $from,
                            'to' => $exception_from,
                            'status' => $time_range['status'],
                            'day' => $time_range['day'] ?? null,
                        ]
                    );
                    $start = $time_range['to'];
                } elseif (is_null($from) && $to) {
                    $start = $exception_to;

                    if ($exceptions->count() - 1 === $index) {
                        $output->push(
                            [
                                'from' => $exception_to,
                                'to' => $time_range['to'],
                                'status' => $time_range['status'],
                                'day' => $time_range['day'] ?? null,
                            ]
                        );
                        continue;
                    }
                    break;
                }
            }
        }

        return $output->merge(
            $exceptions->map(
                function ($exception) use ($date) {
                    $exception_from = $exception->from->toDateString() < $date->toDateString()
                        ? "00:00" : $exception->from->toTimeString();
                    $exception_to = $exception->to->toDateString() > $date->toDateString() ?
                        '24:00' : $exception->to->toTimeString();

                    return [
                        'from' => $exception_from,
                        'to' => $exception_to,
                        'status' => $exception->status,
                        'day' => $exception->from->dayOfWeek
                    ];
                }
            )
        )->filter(
            function ($time_range) {
                return $time_range['from'] !== $time_range['to'];
            }
        )->sortBy('from')->values();
    }
}
