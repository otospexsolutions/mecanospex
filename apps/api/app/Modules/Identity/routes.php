<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Controllers\AuthController;
use App\Modules\Identity\Presentation\Controllers\RoleController;
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

Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    // Role management (requires roles.view or roles.manage permission)
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');

    // User role assignment (requires users.assign-roles permission)
    Route::get('users/{userId}/roles', [RoleController::class, 'userRoles'])->name('users.roles');
    Route::post('users/{userId}/roles', [RoleController::class, 'assignRole'])->name('users.roles.assign');
    Route::delete('users/{userId}/roles', [RoleController::class, 'removeRole'])->name('users.roles.remove');
});
