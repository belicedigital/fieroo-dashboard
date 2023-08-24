<?php
use Illuminate\Support\Facades\Route;
use Fieroo\Dashboard\Controllers\DashboardController;

Route::group(['prefix' => 'admin', 'middleware' => ['web','auth']], function() {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::group(['prefix' => 'stats'], function() {
        Route::post('/events-participants', [DashboardController::class, 'getEventsParticipantsChart']);
        Route::post('/events-per-year', [DashboardController::class, 'getEventsPerYearChart']);
        Route::post('/events-payments', [DashboardController::class, 'getEventsPaymentsChart']);
    });
});