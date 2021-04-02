<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenants\TenantStoreRequest;
use App\Models\Tenant;

class TenantsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Tenant::paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(TenantStoreRequest $request)
    {
        $tenant = Tenant::create($request->only('name'));

        return response(
            [
                'message' => 'Tenant created successfully.',
                'data' => ['tenant' => $tenant],
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Tenant $tenant
     * @return \Illuminate\Http\Response
     */
    public function show(Tenant $tenant)
    {
        return response(
            [
                'message' => 'Model retrieved successfully.',
                'data' => [
                    'tenant' => $tenant
                ]
            ]
        );
    }
}
