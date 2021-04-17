<?php

namespace App\Classes\Overwrite;

use App\Interfaces\IntersectionInterface;
use Carbon\Carbon;

class RightIntersection implements IntersectionInterface
{
    private array $intersection_range;
    private array $exception;
    private array $time_slot;
    /**
     * @var Carbon
     */
    private Carbon $date;
    private bool $is_last_item;
    private string $next_start;

    public function __construct(
        array $intersection_range,
        array $exception,
        array $time_slot,
        Carbon $date,
        bool $is_last_item,
        string $next_start
    ) {
        $this->intersection_range = $intersection_range;
        $this->exception = $exception;
        $this->time_slot = $time_slot;
        $this->date = $date;
        $this->is_last_item = $is_last_item;
        $this->next_start = $next_start;
    }

    public function shouldBreak(): bool
    {
        return !$this->is_last_item;
    }

    public function output(): array
    {
        if ($this->is_last_item) {
            return [
                [
                    'from' => $this->exception['to'],
                    'to' => $this->time_slot['to'],
                    'status' => $this->time_slot['status'],
                    'day' => $this->date->dayOfWeek,
                ]
            ];
        }

        return [];
    }

    public function nextStart(): string
    {
        return $this->exception['to'];
    }
}
