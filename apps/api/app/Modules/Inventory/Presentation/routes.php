<?php

declare(strict_types=1);

use App\Modules\Inventory\Presentation\Controllers\LocationController;
use App\Modules\Inventory\Presentation\Controllers\StockLevelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Module API Routes
|--------------------------------------------------------------------------
|
| Stock management, locations, and inventory operations.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function (): void {
    // Locations
    Route::get('/locations', [LocationController::class, 'index'])
        ->middleware('can:inventory.view')
        ->name('locations.index');

    Route::get('/locations/{location}', [LocationController::class, 'show'])
        ->middleware('can:inventory.view')
        ->name('locations.show');

    Route::post('/locations', [LocationController::class, 'store'])
        ->middleware('can:inventory.adjust')
        ->name('locations.store');

    // Stock Levels
    Route::get('/stock-levels', [StockLevelController::class, 'index'])
        ->middleware('can:inventory.view')
        ->name('stock-levels.index');

    Route::get('/stock-levels/{product}/{location}', [StockLevelController::class, 'show'])
        ->middleware('can:inventory.view')
        ->name('stock-levels.show');
});
