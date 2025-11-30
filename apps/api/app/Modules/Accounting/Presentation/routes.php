<?php

declare(strict_types=1);

use App\Modules\Accounting\Presentation\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounting Module API Routes
|--------------------------------------------------------------------------
|
| Chart of accounts and journal entry management.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function (): void {
    // Chart of Accounts
    Route::get('/accounts', [AccountController::class, 'index'])
        ->middleware('can:accounts.view')
        ->name('accounts.index');

    Route::get('/accounts/{account}', [AccountController::class, 'show'])
        ->middleware('can:accounts.view')
        ->name('accounts.show');

    Route::post('/accounts', [AccountController::class, 'store'])
        ->middleware('can:accounts.manage')
        ->name('accounts.store');

    Route::patch('/accounts/{account}', [AccountController::class, 'update'])
        ->middleware('can:accounts.manage')
        ->name('accounts.update');
});
