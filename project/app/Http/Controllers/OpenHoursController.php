<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenHours\CheckStationStatusRequest;
use App\Http\Requests\OpenHours\OpenHourStoreRequest;
use App\Interfaces\TimeableInterface;
use App\Models\OpenHour;
use App\Models\Station;
use App\Models\Timeline;
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
            $exceptions
                ->where('status', !$current_state)
                ->where('from', '>=', $date_time->toDateTimeString())
                ->first()->from
            ?? $date_time->clone()->addWeek();

        $timeline = (new Timeline($open_hours))
            ->generate($date_time, $first_change_timestamp)
            ->applyExceptions($exceptions)
            ->toDateTime();
//            ->timeline();
//        dump('timestamp');
        dd($timeline);

//        while (count($timeline) > 0) {
        $next_state_timestamp = null;
        $message = sprintf('The station will be always %s', $current_state ? 'on' : 'off');

//        dd($timeline);
        foreach ($timeline as $state) {
            if ($current_state && $date_time->timestamp > $state['from'] && $date_time->getTimestamp() < $state['to']) {
                $next_state_timestamp = $state['to'];
                break;
            }
            if (!$current_state && $date_time->timestamp < $state['from']) {
                $next_state_timestamp = $state['to'];
                break;
            }
        }
        dd($timeline);

        if ($next_state_timestamp) {
            $message = sprintf(
                'The station state will change to %s on %s',
                !$current_state ? 'on' : 'off',
                date('Y-m-d H:i:s', $next_state_timestamp)
            );
        }

        dd(
            [
                'data' => $next_state_timestamp,
                'message' => $message,
            ]
        );

        return response(
            [
                'data' => $next_state_timestamp,
                'message' => $message,
            ]
        );
        dump('timeline');
        dd($timeline);

        return 'result';

        $period = CarbonPeriod::create($date_time, $first_change_timestamp);
        $result = null;
        $diff = $period->count();

        foreach ($period as $index => $date) {
            if ($index > 0) {
                $date_time->setTime(00, 00);
            }
            $day_plan = dayPlan($open_hours[$date->dayOfWeek] ?? collect());
            $day_exceptions = $exceptions->filter(
                function ($exception) use ($date, $index, $diff) {
                    $start = $date->clone()->setTime(00, 00);
                    $end = $date->clone()->setTime(24, 00);

                    if ($index === $diff) {
                        // last date should end before the start time
                        $end->setTime($date->hour, $date->minute);
                    }

                    return ($exception->from->gte($start) && $exception->from->lt($end))
                        || ($exception->from->lte($date) || $exception->to->gte($date));
                }
            );
            $full_day_plan = $day_plan;
            if ($day_exceptions->count()) {
                $full_day_plan = applyExceptions(
                    $day_plan,
                    $day_exceptions,
                    $date
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
                $message = sprintf(
                    'The station state will change to %s on %s',
                    !$current_state ? 'on' : 'off',
                    $changed->toDateTimeString()
                );

                break;
            }
        }

        return response(
            [
                'data' => $result,
                'message' => $message,
                'date_time' => $date_time->toDateTimeString()
            ]
        );
    }
}
