<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenHours\OpenHourStoreRequest;
use App\Interfaces\TimeableInterface;
use App\Models\OpenHour;
use Illuminate\Http\Request;

class OpenHoursController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

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
     * Display the specified resource.
     *
     * @param \App\Models\OpenHour $openHour
     * @return \Illuminate\Http\Response
     */
    public function show(OpenHour $openHour)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\OpenHour $openHour
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OpenHour $openHour)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\OpenHour $openHour
     * @return \Illuminate\Http\Response
     */
    public function destroy(OpenHour $openHour)
    {
        //
    }
}
