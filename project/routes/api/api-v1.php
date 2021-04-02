<?php

use App\Http\Controllers\{OpenHoursController, StationsController, StoresController, TenantsController};
use Illuminate\Support\Facades\Route;

Route::apiResources(
    [
        'tenants' => TenantsController::class,
        'tenants.stores' => StoresController::class,
        'stores.stations' => StationsController::class
    ],
    [
        'only' => ['index', 'store', 'show']
    ]
);

Route::post('open_hours/{timeable}/{timeable_id}/', [OpenHoursController::class, 'store']);

