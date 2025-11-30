<?php

declare(strict_types=1);

use App\Modules\Accounting\Presentation\Controllers\AccountController;
use App\Modules\Accounting\Presentation\Controllers\JournalEntryController;
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

    // Journal Entries
    Route::get('/journal-entries', [JournalEntryController::class, 'index'])
        ->middleware('can:journal.view')
        ->name('journal-entries.index');

    Route::get('/journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])
        ->middleware('can:journal.view')
        ->name('journal-entries.show');

    Route::post('/journal-entries', [JournalEntryController::class, 'store'])
        ->middleware('can:journal.create')
        ->name('journal-entries.store');

    Route::post('/journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post'])
        ->middleware('can:journal.post')
        ->name('journal-entries.post');
});
