<?php

namespace App\Classes;

use App\Models\TimeablePriority;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExceptionsHours
{
    /**
     * @var Collection
     */
    private Collection $exceptions;

    /**
     * @var Carbon
     */
    private Carbon $date;

    public function __construct(Collection $exceptions)
    {
        $this->exceptions = $exceptions;
    }

    /**
     * @param Collection $day_plan
     * @param Carbon $date
     * @return Collection
     */
    public function applyExceptions(Collection $day_plan, Carbon $date): Collection
    {
        $this->date = $date;
        $exceptions = $this->findDateExceptions();
        if ($exceptions->count()) {
            $timeables_priority = TimeablePriority::orderBy('priority')->get();
            $exceptions = $exceptions->groupBy('timeable_type');

            foreach ($timeables_priority as $timeable) {
                if (!isset($exceptions[$timeable->name])) {
                    continue;
                }

                $day_plan = $this->overwriteTimes($day_plan, $exceptions[$timeable->name]);
            }
        }

        return $day_plan;
    }

    /**
     * @return Collection
     */
    private function findDateExceptions(): Collection
    {
        return $this->exceptions->filter(
            function ($exception) {
                $start = $this->date->clone()->setTime(00, 00);
                $end = $this->date->clone()->setTime(24, 00);

                return $exception->from->lte($end) && $exception->to->gte($start);
            }
        );
    }

    /**
     * @param Collection $day_plan
     * @param Collection $exceptions
     * @return Collection
     */
    private function overwriteTimes(Collection $day_plan, Collection $exceptions): Collection
    {
        $output = collect();
        $exceptions = $exceptions->sortBy('from');
        $day_plan = $day_plan->sortBy('from');

        $start = '00:00';
        foreach ($exceptions as $index => $exception) {
            $exception_from = $exception->from->toDateString() < $this->date->toDateString()
                ? "00:00" : $exception->from->toTimeString();

            $exception_to = $exception->to->toDateString() > $this->date->toDateString()
                ? '24:00' : $exception->to->toTimeString();

            foreach ($day_plan as $time_range) {
                // exception and time_range have intersect
                $has_intersect =
                    ($time_range['from'] < $exception_to) &&
                    ($time_range['to'] > $exception_from);

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
                function ($exception) {
                    $exception_from = $exception->from->toDateString() < $this->date->toDateString()
                        ? "00:00" : $exception->from->toTimeString();
                    $exception_to = $exception->to->toDateString() > $this->date->toDateString() ?
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
