<?php

namespace App\Classes;

use App\Classes\Overwrite\IntersectionManager;
use App\Models\OpenHourException;
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

    private Collection $timeables_priority;

    public function __construct(Collection $exceptions)
    {
        $this->exceptions = collect();
        $this->addExceptions($exceptions);
        $this->timeables_priority = collect();
    }

    public function addPriorities(Collection $timeable_priorities): self
    {
        foreach ($timeable_priorities as $timeable_priority) {
            $this->addTimeablePriority($timeable_priority);
        }

        return $this;
    }

    /**
     * @param Collection $exceptions
     * @return $this|void
     */
    public function addExceptions(Collection $exceptions): self
    {
        foreach ($exceptions as $exception) {
            $this->addException($exception);
        }

        return $this;
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
            $exceptions = $exceptions->groupBy('timeable_type');
            $timeables_priority =
                $this->timeables_priority->isEmpty() ?
                    $exceptions->keys() :
                    $this->timeables_priority;

            foreach ($timeables_priority as $timeable_name) {
                if (!isset($exceptions[$timeable_name])) {
                    continue;
                }

                $day_plan = $this->overwriteTimes($day_plan, $exceptions[$timeable_name]);
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

        $next_start = '00:00';
        foreach ($exceptions as $index => $exception) {
            $is_last_item = $exceptions->count() - 1 === $index;

            foreach ($day_plan as $time_range) {
                $intersection_manager = new IntersectionManager(
                    $exception, $this->date, $time_range, $next_start, $is_last_item
                );

                $slot_manager = $intersection_manager->slot();

                $output = $output->merge($slot_manager->output());
                $next_start = $slot_manager->nextStart();

                if ($slot_manager->shouldBreak()) {
                    break;
                }
            }
        }

        $output = $output->merge(
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

        return $output;
    }

    /**
     * @param OpenHourException $exception
     */
    private function addException(OpenHourException $exception): void
    {
        $this->exceptions->push($exception);
    }

    private function addTimeablePriority(TimeablePriority $timeable_priority): void
    {
        $this->timeables_priority->put($timeable_priority->priority, $timeable_priority->name);

        $this->timeables_priority = $this->timeables_priority->sortKeys();
    }

}
