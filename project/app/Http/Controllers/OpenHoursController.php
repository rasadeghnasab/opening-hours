<?php

namespace App\Http\Controllers;

use App\Classes\ExceptionsHours;
use App\Classes\Timeline;
use App\Classes\WeekPlan;
use App\Http\Requests\OpenHours\CheckStationStatusRequest;
use App\Http\Requests\OpenHours\OpenHourStoreRequest;
use App\Interfaces\TimeableInterface;
use App\Models\OpenHour;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Http\Response;

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
     * @return \Illuminate\Http\Response
     */
    public function stateCheck(CheckStationStatusRequest $request, Station $station): Response
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
    public function nextStateChange(CheckStationStatusRequest $request, Station $station): Response
    {
        $timestamp = $request->get('timestamp');
        $current_state = $station->state($timestamp);
        $from = Carbon::createFromTimestamp($timestamp);

        $exceptions = $station
            ->exceptions()
            // midnight
            ->isAfter($from->clone()->setTime(00, 00))
            ->get();

        $open_hours = $station->openHours()->orderBy('from')->get()->groupBy('day');

        // If we found a state change in the exceptions we can make sure the state will change on that time MAX
        // Otherwise we should search a complete week for the state change
        $first_change =
            $exceptions
                ->where('status', !$current_state)
                ->where('from', '>=', $from->toDateTimeString())
                ->first()->from
            ?? $from->clone()->addWeek();

        $timeline = (new Timeline(new WeekPlan($open_hours)))
            ->addExceptions(new ExceptionsHours($exceptions))
            ->generate($from, $first_change);

        $next_change = $timeline->nextStateChange($current_state);

        $result = null;
        $message = sprintf('The station will be always %s', $current_state ? 'on' : 'off');
        if ($next_change) {
            $message = sprintf(
                'The station state will change to %s on %s',
                !$current_state ? 'on' : 'off',
                $next_change->toDateTimeString()
            );
            $result = $next_change->timestamp;
        }

        return response(
            [
                'data' => $result,
                'message' => $message,
                'current_time' => $from->toDateTimeString(),
                'current_state' => $current_state
            ]
        );
    }
}
