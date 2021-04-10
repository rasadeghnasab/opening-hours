<?php

namespace App\Models;

use App\Interfaces\OpenHourInterface;
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

    public function __construct(Collection $open_hours)
    {
        $this->open_hours = $open_hours;
        $this->timeline = collect();
    }

    public function timeline(): Collection
    {
        return $this->timeline->sortBy('from');
    }

    public function generate(Carbon $from, Carbon $to): self
    {
        $period = CarbonPeriod::create($from, $to);
//        dump($from->toDateTimeString() . ' | ' . $to->toDateTimeString());

        foreach ($period as $date) {
//            dump($date->toDateTimeString() . '----------------------' . $date->dayOfWeek);
            $day_open_hours = $this->open_hours[$date->dayOfWeek] ?? collect();

            foreach ($day_open_hours as $open_hour) {
                if ($this->hour_should_not_add($open_hour, $date, $from, $to)) {
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

    public function applyExceptions(Collection $exceptions): self
    {
        $grouped_exceptions = $exceptions->groupBy('status');

        if (isset($grouped_exceptions[1])) {
            $this->addOpenExceptions($grouped_exceptions[1]);
        }

        if (isset($grouped_exceptions[0])) {
            $this->removeClosedExceptions($grouped_exceptions[0]);
        }

        return $this;
    }

    /**
     * @param OpenHourInterface $open_hour
     * @param Carbon $date
     * @param Carbon $from
     * @param Carbon $to
     * @return bool
     */
    private function hour_should_not_add(OpenHourInterface $open_hour, Carbon $date, Carbon $from, Carbon $to): bool
    {
        $format = 'Y-m-d';

        return ($date->format($format) === $from->format($format) && $open_hour->from < $from->toTimeString())
            || ($date->format($format) === $to->format($format) && $open_hour->to > $to->toTimeString());
    }

    /**
     * @param Collection $open_exceptions
     * @return void
     */
    private function addOpenExceptions(Collection $open_exceptions): void
    {
        $this->timeline->merge($open_exceptions)->sortBy('from');

        $this->timeline = $this->removeOverlaps($this->timeline);
    }

    /**
     * @param Collection $close_exceptions
     * @return void
     */
    private function removeClosedExceptions(Collection $close_exceptions): void
    {
        $close_timeline = collect();
        $timeline = $this->timeline;

        for ($i = 0; $i < $timeline->count() + 1; $i++) {
            $from = isset($timeline[$i - 1]) ? $timeline[$i - 1]['to'] : 0;
            $to = isset($timeline[$i]) ? $timeline[$i]['from'] : PHP_INT_MAX;

            $close_timeline->push(['from' => $from, 'to' => $to]);
        }

        $close_timeline = $close_timeline->merge($close_exceptions->toArray());
        $close_timeline->sortBy('from')->toArray();

        $this->timeline = $this->removeOverlaps($close_timeline);

        $excluded_timeline = collect();
        for ($i = 1; $i < $close_timeline->count(); $i++) {
            $excluded_timeline->push(
                [
                    'from' => $close_timeline[$i - 1]['to'],
                    'to' => $close_timeline[$i]['from']
                ]
            );
        }

        $this->timeline = $excluded_timeline;
    }

    /**
     * Remove date and time overlaps from a given timeline
     * @param Collection $timeline
     * @return Collection
     */
    private function removeOverlaps(Collection $timeline): Collection
    {
        $stack = collect();

        foreach ($timeline as $current_item) {
            if ($last_item = $stack->pop()) {
                if ($current_item['from'] <= $last_item['to']) {
                    $current_item['from'] = $last_item['from'];
                    $current_item['to'] = max($last_item['to'], $current_item['to']);
                    $stack->push($current_item);
                    continue;
                }
                $stack->push($last_item);
            }
            $stack->push($current_item);
        }

        return $stack;
    }

    public function toDateTime()
    {
        $new_timeline = [];
        $format = 'Y-m-d H:i:s';
        foreach($this->timeline->sortBy('from') as $item) {
            $new_timeline[] = [
                'from' => date($format, $item['from']),
                'to' => date($format, $item['to']),
            ];
        }

        return $new_timeline;
    }
}
