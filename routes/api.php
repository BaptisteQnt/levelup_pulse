<?php

use App\Http\Controllers\API\AuthTokenController;
use App\Http\Controllers\API\GameRatingController as ApiGameRatingController;
use App\Http\Controllers\API\StatsController;
use App\Http\Controllers\API\CompatibilityScanHardwareController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::post('compatibility-scans/{compatibilityScan}/hardware', [CompatibilityScanHardwareController::class, 'store'])
        ->middleware('throttle:compatibility-scan-upload')
        ->name('compatibility-scans.hardware.store');

    Route::post('auth/token', [AuthTokenController::class, 'store'])->name('auth.token.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::get('stats', [StatsController::class, 'index'])->name('stats');
        Route::get('games/rating', [StatsController::class, 'gameRating'])->name('games.rating');

        Route::prefix('games/{game:slug}')->group(function () {

            Route::get('rating', [ApiGameRatingController::class, 'show'])->name('games.ratings.show');
            Route::post('rating', [ApiGameRatingController::class, 'store'])->name('games.ratings.store');
            Route::put('rating', [ApiGameRatingController::class, 'update'])->name('games.ratings.update');
            Route::delete('rating', [ApiGameRatingController::class, 'destroy'])->name('games.ratings.destroy');
        });

    });
});
