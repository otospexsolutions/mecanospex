<?php

declare(strict_types=1);

use App\Modules\Dashboard\Presentation\Controllers\DashboardController;
use App\Modules\Identity\Presentation\Middleware\SetPermissionsTeam;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Module API Routes
|--------------------------------------------------------------------------
|
| Dashboard statistics and overview data.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum', SetPermissionsTeam::class])->group(function (): void {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->name('dashboard.stats');
});
