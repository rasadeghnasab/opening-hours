<?php

namespace App\Classes;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class Timeline
{
    /**
     * @var Collection
     */
    private Collection $open_hours;

    /**
     * @var Collection
     */
    private Collection $timeline;
    /**
     * @var Carbon
     */
    private Carbon $from;
    /**
     * @var Carbon
     */
    private Carbon $to;

    public function __construct(Collection $open_hours, Carbon $from, Carbon $to)
    {
        $this->open_hours = $open_hours;
        $this->from = $from;
        $this->to = $to;
        $this->timeline = collect();
    }

    public function timeline(): Collection
    {
        return $this->timeline->sortBy('from');
    }
    public function generate(): self
    {
        $period = CarbonPeriod::create($this->from, $this->to);

        foreach ($period as $date) {
            $day_open_hours = $this->open_hours[$date->dayOfWeek] ?? collect();

            foreach ($day_open_hours as $open_hour) {
                if ($this->hour_should_not_add($open_hour, $date, $this->from, $this->to)) {
                    continue;
                }

                $format = 'Y-m-d H:i:s';
                $this->timeline->push(
                    [
                        'from' => $date->setTime(...explode(':', $open_hour->from))->timestamp,
                        'to' => $date->setTime(...explode(':', $open_hour->to))->timestamp,
//                        'from' => date($format, $date->setTime(...explode(':', $open_hour->from))->timestamp),
//                        'to' => date($format, $date->setTime(...explode(':', $open_hour->to))->timestamp),
                    ]
                );
            }
        }

        return $this;
    }
}
