<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenHours\CheckStationStatusRequest;
use App\Http\Requests\OpenHours\OpenHourStoreRequest;
use App\Interfaces\TimeableInterface;
use App\Models\OpenHour;
use App\Models\Station;
use Carbon\Carbon;

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
    public function check(CheckStationStatusRequest $request, Station $station)
    {
        return response(['data' => $station->currentState($request->get('timestamp'))]);
    }

    public function nextStateChange(CheckStationStatusRequest $request, Station $station)
    {
        $timestamp = $request->get('timestamp');
        $current_state = $station->currentState($timestamp);
        $date_time = Carbon::createFromTimestamp($timestamp);

        $next_change = null;
        $message = 'it will never change';

        $changed_due_exception = $station
            ->exceptions()
            ->status(!$current_state)
            ->isAfter($date_time)
            ->first();

        if ($changed_due_exception) {
            $next_change = $changed_due_exception->from->timestamp;
            $message = sprintf('The state will change on %s', $changed_due_exception->from->toDateTimeString());
        }

        $open_hours = $station->openHours()->get()->groupBy('day');

        $week_days_numbers = weekDaysNumberStartFrom($date_time->dayOfWeek);
        $week_days_names = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        for ($i = 0; $i <= count($week_days_numbers); $i++) {
            $week_day_index = $i < count($week_days_numbers) ? $i : 0;
            $day_open_hours = $open_hours[$week_days_numbers[$week_day_index]] ?? collect();

            /**** Have to be changed ****/

            $start_time = '00:00';
            $end_time = '24:00';
            $day_name = sprintf('next %s', $week_days_names[$week_days_numbers[$week_day_index]]);

            if ($week_day_index === 0 and $i < 7) {
                $start_time = $date_time->format('H:i');
                $end_time = '24:00';
                $day_name = 'today';
            } else {
                if ($i === 7) {
                    $end_time = $date_time->format('H:i');
                }
            }

            $changed_due_open_hour = dayPlan($day_open_hours, $start_time, $end_time)->filter(
                function ($plan) use ($current_state, $date_time, $i) {
                    return $plan['status'] != $current_state && ($plan['from'] > $date_time->format('H:i') || $i > 1);
                }
            )->first();

            if ($changed_due_open_hour) {
                $first_change_timestamp = strtotime(
                    sprintf('%s %s', $day_name, $changed_due_open_hour['from']),
                    $date_time->timestamp
                );
            }

            if (
                (!$changed_due_exception && $changed_due_open_hour && $first_change_timestamp > $date_time->timestamp)
                || ($changed_due_open_hour && $changed_due_exception && $first_change_timestamp < $changed_due_exception->from->timestamp)
            ) {
                $next_change = strtotime(sprintf('%s %s', $day_name, $changed_due_open_hour['from']));
                $message = sprintf('The state will change on %s', date('Y-m-d H:i:s', $next_change));
                break;
            }
        }

        return response(
            [
                'data' => $next_change,
                'message' => $message,
            ]
        );
    }
}
