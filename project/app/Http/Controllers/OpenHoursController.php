<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenHours\CheckStationStatusRequest;
use App\Http\Requests\OpenHours\OpenHourStoreRequest;
use App\Interfaces\TimeableInterface;
use App\Models\OpenHour;
use App\Models\Station;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class OpenHoursController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param OpenHourStoreRequest $request
     * @param string $timeable_type
     * @param TimeableInterface $timeable
     * @return \Illuminate\Http\Response
     */
    public function store(OpenHourStoreRequest $request, string $timeable_type, TimeableInterface $timeable)
    {
        $open_hour = new OpenHour($request->only('day', 'from', 'to'));

        $open_hour->timeable()->associate($timeable)->save();

        return response(
            [
                'data' => [
                    'open_hour' => $open_hour
                ]
            ]
        );
    }

    /**
     * Check the specified station status.
     *
     * @param CheckStationStatusRequest $request
     * @param Station $station
     * @return string
     */
    public function stateCheck(CheckStationStatusRequest $request, Station $station)
    {
        return response(['data' => $station->state($request->get('timestamp'))]);
    }

    /**
     * Check for the next time that station state changes,
     * it should be timestamp or null
     *
     * @param CheckStationStatusRequest $request
     * @param Station $station
     * @return \Illuminate\Http\Response
     */
    public function nextStateChange(CheckStationStatusRequest $request, Station $station)
    {
        $timestamp = $request->get('timestamp');
        $current_state = $station->state($timestamp);
        $date_time = Carbon::createFromTimestamp($timestamp);

        $next_change = null;
        $message = sprintf('The station will be always %s', $current_state ? 'on' : 'off');

        $exceptions = $station
            ->exceptions()
            ->isAfter($date_time->clone()->setTime(00, 00))
            ->get();

        $open_hours = $station->openHours()->orderBy('from')->get()->groupBy('day');

        $first_change_timestamp =
            $exceptions->where('status', !$current_state)->where('from', '>=', $date_time->toDateTimeString())->first()->from
            ?? $date_time->clone()->addWeek();

        $period = CarbonPeriod::create($date_time, $first_change_timestamp);
        $result = null;
        $diff = $period->count();
        foreach ($period as $index => $date) {
            if($index > 0) {
                $date_time->setTime(00, 00);
            }
            $day_plan = dayPlan($open_hours[$date->dayOfWeek] ?? collect());
            $exceptionsBetween = $exceptions->filter(
                function ($exception) use ($date, $index, $diff) {
                    $start = $date->clone()->setTime(00, 00);
                    $end = $date->clone()->setTime(24, 00);

                    if($index === $diff) {
                        // last date should end before the start time
                        $end->setTime($date->hour, $date->minute);
                    }

                    return $exception->from->gte($start) && $exception->from->lt($end);
                }
            );
            $full_day_plan = $day_plan;
            if ($exceptionsBetween->count()) {
                $full_day_plan = applyExceptions(
                    $day_plan,
                    $exceptionsBetween
                );
            }

            $changed = $full_day_plan->filter(
                function ($plan) use ($current_state, $date_time) {
                    return $plan['status'] != $current_state && $plan['from'] >= $date_time->toTimeString();
                }
            )->first();

            if ($changed) {
                $changed = $date->setTime(...explode(':', $changed['from']));
                $result = $changed->timestamp;
                $message = sprintf('The station state will change to %s on %s',
                                   !$current_state ? 'on' : 'off', $changed->toDateString());

                break;
            }
        }

        return response(
            [
                'data' => $result,
                'message' => $message,
            ]
        );
    }
}
