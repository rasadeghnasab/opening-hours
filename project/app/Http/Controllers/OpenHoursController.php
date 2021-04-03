<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenHours\CheckStationStatusRequest;
use App\Http\Requests\OpenHours\OpenHourStoreRequest;
use App\Interfaces\TimeableInterface;
use App\Models\OpenHour;
use App\Models\OpenHourException;
use App\Models\Station;

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
        $timestamp = $request->get('timestamp');

        if($open_hour_exception = $station->exceptions($timestamp)->first()) {
            return response(['data' => (bool) $open_hour_exception->status]);
        }

        return response(['data' => $station->isOpen($timestamp)]);
    }
}
