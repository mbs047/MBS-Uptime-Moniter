<?php

use App\Http\Controllers\Api\ProbeRegistrationController;
use App\Http\Controllers\Api\StatusIncidentsController;
use App\Http\Controllers\Api\StatusServicesController;
use App\Http\Controllers\Api\StatusSummaryController;
use App\Http\Controllers\Auth\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::prefix('status')->group(function (): void {
    Route::get('/summary', StatusSummaryController::class);
    Route::get('/services', StatusServicesController::class);
    Route::get('/incidents', StatusIncidentsController::class);
    Route::post('/subscribers', [SubscriberController::class, 'store']);
});

Route::post('/integrations/probes/register', ProbeRegistrationController::class);
