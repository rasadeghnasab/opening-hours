<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Store;
use Illuminate\Http\Request;

class StationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Store $store
     * @return \Illuminate\Http\Response
     */
    public function index(Store $store)
    {
        return response($store->stations()->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Store $store
     * @return \Illuminate\Http\Response
     */
    public function store(Store $store)
    {
        $station = new Station();

        $store->stations()->save($station);

        return response(
            [
                'message' => 'Tenant created successfully.',
                'data' => ['station' => $station],
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Station  $station
     * @return \Illuminate\Http\Response
     */
    public function show(Station $station)
    {
        return response(
            [
                'message' => 'Model retrieved successfully.',
                'data' => [
                    'tenant' => $station
                ]
            ]
        );
    }
}
