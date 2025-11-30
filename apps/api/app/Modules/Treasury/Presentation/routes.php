<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Middleware\SetPermissionsTeam;
use App\Modules\Treasury\Presentation\Controllers\PaymentController;
use App\Modules\Treasury\Presentation\Controllers\PaymentInstrumentController;
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

Route::prefix('api/v1')->middleware(['auth:sanctum', SetPermissionsTeam::class])->group(function (): void {
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

    // Payment Instruments
    Route::get('/payment-instruments', [PaymentInstrumentController::class, 'index'])
        ->middleware('can:instruments.view')
        ->name('payment-instruments.index');

    Route::get('/payment-instruments/{instrument}', [PaymentInstrumentController::class, 'show'])
        ->middleware('can:instruments.view')
        ->name('payment-instruments.show');

    Route::post('/payment-instruments', [PaymentInstrumentController::class, 'store'])
        ->middleware('can:instruments.create')
        ->name('payment-instruments.store');

    Route::post('/payment-instruments/{instrument}/deposit', [PaymentInstrumentController::class, 'deposit'])
        ->middleware('can:instruments.transfer')
        ->name('payment-instruments.deposit');

    Route::post('/payment-instruments/{instrument}/clear', [PaymentInstrumentController::class, 'clear'])
        ->middleware('can:instruments.clear')
        ->name('payment-instruments.clear');

    Route::post('/payment-instruments/{instrument}/bounce', [PaymentInstrumentController::class, 'bounce'])
        ->middleware('can:instruments.clear')
        ->name('payment-instruments.bounce');

    Route::post('/payment-instruments/{instrument}/transfer', [PaymentInstrumentController::class, 'transfer'])
        ->middleware('can:instruments.transfer')
        ->name('payment-instruments.transfer');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])
        ->middleware('can:payments.view')
        ->name('payments.index');

    Route::get('/payments/{payment}', [PaymentController::class, 'show'])
        ->middleware('can:payments.view')
        ->name('payments.show');

    Route::post('/payments', [PaymentController::class, 'store'])
        ->middleware('can:payments.create')
        ->name('payments.store');
});
