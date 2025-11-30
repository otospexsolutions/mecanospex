<?php

declare(strict_types=1);

use App\Modules\Treasury\Presentation\Controllers\PaymentMethodController;
use App\Modules\Treasury\Presentation\Controllers\PaymentRepositoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Treasury Module API Routes
|--------------------------------------------------------------------------
|
| Payment methods, repositories, instruments, and payment operations.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function (): void {
    // Payment Methods
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])
        ->middleware('can:treasury.view')
        ->name('payment-methods.index');

    Route::get('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'show'])
        ->middleware('can:treasury.view')
        ->name('payment-methods.show');

    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])
        ->middleware('can:treasury.manage')
        ->name('payment-methods.store');

    Route::patch('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])
        ->middleware('can:treasury.manage')
        ->name('payment-methods.update');

    // Payment Repositories
    Route::get('/payment-repositories', [PaymentRepositoryController::class, 'index'])
        ->middleware('can:repositories.view')
        ->name('payment-repositories.index');

    Route::get('/payment-repositories/{repository}', [PaymentRepositoryController::class, 'show'])
        ->middleware('can:repositories.view')
        ->name('payment-repositories.show');

    Route::get('/payment-repositories/{repository}/balance', [PaymentRepositoryController::class, 'balance'])
        ->middleware('can:repositories.view')
        ->name('payment-repositories.balance');

    Route::post('/payment-repositories', [PaymentRepositoryController::class, 'store'])
        ->middleware('can:repositories.manage')
        ->name('payment-repositories.store');

    Route::patch('/payment-repositories/{repository}', [PaymentRepositoryController::class, 'update'])
        ->middleware('can:repositories.manage')
        ->name('payment-repositories.update');
});
