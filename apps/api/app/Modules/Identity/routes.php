<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Identity Module API Routes
|--------------------------------------------------------------------------
|
| Authentication and user management routes.
|
*/

Route::prefix('api/v1/auth')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
    });
});
