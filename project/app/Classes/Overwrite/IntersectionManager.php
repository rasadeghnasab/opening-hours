<?php

namespace App\Classes\Overwrite;

use App\Interfaces\IntersectionInterface;
use App\Models\OpenHourException;
use Carbon\Carbon;

class IntersectionManager
{
    private array $INTERSECTION_TYPES = [
        '100' => InsideInteraction::class,
        '111' => FullIntersection::class,
        '110' => LeftIntersection::class,
        '101' => RightIntersection::class,
        '000' => NoIntersection::class,
    ];

    /**
     * @var Carbon
     */
    private Carbon $date;

    private array $exception;

    private array $time_slot;

    private string $next_start;

    private bool $is_last_item;

    public function __construct(
        OpenHourException $exception,
        Carbon $date,
        array $time_slot,
        string $next_start,
        bool $last_item
    ) {
        $this->date = $date;
        $this->time_slot = $time_slot;
        $this->next_start = $next_start;
        $this->is_last_item = $last_item;

        $this->exception = [
            'from' => $exception->from->toDateString() < $date->toDateString()
                ? "00:00" : $exception->from->toTimeString(),
            'to' => $exception->to->toDateString() > $date->toDateString()
                ? '24:00' : $exception->to->toTimeString()
        ];
    }

    public function slot(): IntersectionInterface
    {
        return $this->instantiateSlot();
    }

    private function instantiateSlot(): IntersectionInterface
    {
        $left_intersection = $this->next_start < $this->exception['from'] ? $this->next_start : null;
        $right_intersection = $this->time_slot['to'] < $this->exception['to'] ? null : $this->time_slot['to'];
        $has_intersection = $this->hasIntersection();

        if (!$has_intersection) {
            $left_intersection = $right_intersection = null;
        }

        $intersection_range = ['from' => $left_intersection, 'to' => $right_intersection];

        $index = sprintf('%d%d%d', $has_intersection, is_string($left_intersection), is_string($right_intersection));
        if (!isset($this->INTERSECTION_TYPES[$index])) {
            die('intersection not exists');
        }

        $INTERSECTION_CLASS = $this->INTERSECTION_TYPES[$index];

        return new $INTERSECTION_CLASS(
            $intersection_range, $this->exception, (array)$this->time_slot,
            $this->date, $this->is_last_item, $this->next_start
        );
    }

    private function hasIntersection(): bool
    {
        return
            ($this->time_slot['from'] < $this->exception['to']) &&
            ($this->time_slot['to'] > $this->exception['from']);
    }
}
