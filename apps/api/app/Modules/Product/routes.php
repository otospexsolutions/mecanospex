<?php

declare(strict_types=1);

use App\Modules\Product\Presentation\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Product Module API Routes
|--------------------------------------------------------------------------
|
| Product/catalog management routes.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function () {
    // Product CRUD with permission middleware
    Route::get('products', [ProductController::class, 'index'])
        ->middleware('can:products.view')
        ->name('products.index');

    Route::get('products/{product}', [ProductController::class, 'show'])
        ->middleware('can:products.view')
        ->name('products.show');

    Route::post('products', [ProductController::class, 'store'])
        ->middleware('can:products.create')
        ->name('products.store');

    Route::patch('products/{product}', [ProductController::class, 'update'])
        ->middleware('can:products.update')
        ->name('products.update');

    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('can:products.delete')
        ->name('products.destroy');
});
