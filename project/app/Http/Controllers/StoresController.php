<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stores\StoreShowRequest;
use App\Http\Requests\Stores\StoreStoreRequest;
use App\Models\Store;
use App\Models\Tenant;

class StoresController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Tenant $tenant
     * @return \Illuminate\Http\Response
     */
    public function index(Tenant $tenant)
    {
        return response($tenant->stores()->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreStoreRequest $request
     * @param Tenant $tenant
     * @return \Illuminate\Http\Response
     */
    public function store(StoreStoreRequest $request, Tenant $tenant)
    {
        $store = new Store($request->only('name'));

        $tenant = $tenant->stores()->save($store);

        return response(
            [
                'message' => 'Store created successfully.',
                'data' => ['store' => $tenant],
            ],
            201
        );
        //
    }

    /**
     * Display the specified resource.
     *
     * @param StoreShowRequest $request
     * @param \App\Models\Store $store
     * @return \Illuminate\Http\Response
     */
    public function show(StoreShowRequest $request, Store $store)
    {
        return response(
            [
                'message' => 'Model retrieved successfully.',
                'data' => [
                    'store' => $store
                ]
            ]
        );
    }
}
