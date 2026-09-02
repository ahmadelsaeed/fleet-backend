<?php

use App\Http\Controllers\API\V1\Auth\LoginController;
use App\Http\Controllers\API\V1\Auth\LogoutController;
use App\Http\Controllers\API\V1\Auth\RegisterController;
use App\Http\Controllers\API\V1\BookingController;
use App\Http\Controllers\API\V1\ProfileController;
use App\Http\Controllers\API\V1\StationController;
use App\Http\Controllers\API\V1\TripController;
use Illuminate\Support\Facades\Route;

// ── Auth (public) ────────────────────────────────────────────────────────────
Route::post('register', RegisterController::class);
Route::post('login', LoginController::class);

// ── Public resources ─────────────────────────────────────────────────────────
Route::get('stations', [StationController::class, 'index']);

Route::prefix('trips')->group(function () {
    Route::get('/', [TripController::class, 'index']);
    Route::get('{trip}', [TripController::class, 'show']);
    Route::get('{trip}/available-seats', [TripController::class, 'availableSeats']);
});

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', LogoutController::class);
    Route::get('me', [ProfileController::class, 'me']);

    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('{booking}', [BookingController::class, 'show']);
        Route::delete('{booking}', [BookingController::class, 'destroy']);
    });
});
