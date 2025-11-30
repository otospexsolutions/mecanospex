<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Middleware\SetPermissionsTeam;
use App\Modules\Inventory\Presentation\Controllers\LocationController;
use App\Modules\Inventory\Presentation\Controllers\StockLevelController;
use App\Modules\Inventory\Presentation\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Module API Routes
|--------------------------------------------------------------------------
|
| Stock management, locations, and inventory operations.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum', SetPermissionsTeam::class])->group(function (): void {
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

    // Stock Movements
    Route::get('/stock-movements', [StockMovementController::class, 'index'])
        ->middleware('can:inventory.view')
        ->name('stock-movements.index');

    Route::post('/stock-movements/receive', [StockMovementController::class, 'receive'])
        ->middleware('can:inventory.receive')
        ->name('stock-movements.receive');

    Route::post('/stock-movements/issue', [StockMovementController::class, 'issue'])
        ->middleware('can:inventory.adjust')
        ->name('stock-movements.issue');

    Route::post('/stock-movements/transfer', [StockMovementController::class, 'transfer'])
        ->middleware('can:inventory.transfer')
        ->name('stock-movements.transfer');

    Route::post('/stock-movements/adjust', [StockMovementController::class, 'adjust'])
        ->middleware('can:inventory.adjust')
        ->name('stock-movements.adjust');
});
