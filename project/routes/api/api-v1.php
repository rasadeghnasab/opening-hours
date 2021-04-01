<?php

use App\Http\Controllers\{StationsController, StoresController, TenantsController};
use Illuminate\Support\Facades\Route;

Route::apiResources(
    [
        'tenants' => TenantsController::class,
        'tenants.stores' => StoresController::class,
        'stores.stations' => StationsController::class
    ]
);

