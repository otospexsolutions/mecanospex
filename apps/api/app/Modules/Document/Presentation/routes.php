<?php

declare(strict_types=1);

use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Document\Presentation\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Module API Routes
|--------------------------------------------------------------------------
|
| Document management routes for quotes, orders, invoices, etc.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function (): void {
    // Quotes
    Route::get('/quotes', function (Request $request) {
        return app(DocumentController::class)->index($request, DocumentType::Quote);
    })->middleware('can:quotes.view')->name('quotes.index');

    Route::get('/quotes/{quote}', function (Request $request, string $quote) {
        return app(DocumentController::class)->show($request, DocumentType::Quote, $quote);
    })->middleware('can:quotes.view')->name('quotes.show');

    Route::post('/quotes', function (Request $request) {
        return app(DocumentController::class)->store(
            app(\App\Modules\Document\Presentation\Requests\CreateDocumentRequest::class),
            DocumentType::Quote
        );
    })->middleware('can:quotes.create')->name('quotes.store');

    Route::patch('/quotes/{quote}', function (Request $request, string $quote) {
        return app(DocumentController::class)->update(
            app(\App\Modules\Document\Presentation\Requests\UpdateDocumentRequest::class),
            DocumentType::Quote,
            $quote
        );
    })->middleware('can:quotes.update')->name('quotes.update');

    Route::delete('/quotes/{quote}', function (Request $request, string $quote) {
        return app(DocumentController::class)->destroy($request, DocumentType::Quote, $quote);
    })->middleware('can:quotes.delete')->name('quotes.destroy');

    // Sales Orders
    Route::get('/orders', function (Request $request) {
        return app(DocumentController::class)->index($request, DocumentType::SalesOrder);
    })->middleware('can:orders.view')->name('orders.index');

    Route::get('/orders/{order}', function (Request $request, string $order) {
        return app(DocumentController::class)->show($request, DocumentType::SalesOrder, $order);
    })->middleware('can:orders.view')->name('orders.show');

    Route::post('/orders', function (Request $request) {
        return app(DocumentController::class)->store(
            app(\App\Modules\Document\Presentation\Requests\CreateDocumentRequest::class),
            DocumentType::SalesOrder
        );
    })->middleware('can:orders.create')->name('orders.store');

    Route::patch('/orders/{order}', function (Request $request, string $order) {
        return app(DocumentController::class)->update(
            app(\App\Modules\Document\Presentation\Requests\UpdateDocumentRequest::class),
            DocumentType::SalesOrder,
            $order
        );
    })->middleware('can:orders.update')->name('orders.update');

    Route::delete('/orders/{order}', function (Request $request, string $order) {
        return app(DocumentController::class)->destroy($request, DocumentType::SalesOrder, $order);
    })->middleware('can:orders.delete')->name('orders.destroy');

    // Invoices
    Route::get('/invoices', function (Request $request) {
        return app(DocumentController::class)->index($request, DocumentType::Invoice);
    })->middleware('can:invoices.view')->name('invoices.index');

    Route::get('/invoices/{invoice}', function (Request $request, string $invoice) {
        return app(DocumentController::class)->show($request, DocumentType::Invoice, $invoice);
    })->middleware('can:invoices.view')->name('invoices.show');

    Route::post('/invoices', function (Request $request) {
        return app(DocumentController::class)->store(
            app(\App\Modules\Document\Presentation\Requests\CreateDocumentRequest::class),
            DocumentType::Invoice
        );
    })->middleware('can:invoices.create')->name('invoices.store');

    Route::patch('/invoices/{invoice}', function (Request $request, string $invoice) {
        return app(DocumentController::class)->update(
            app(\App\Modules\Document\Presentation\Requests\UpdateDocumentRequest::class),
            DocumentType::Invoice,
            $invoice
        );
    })->middleware('can:invoices.update')->name('invoices.update');

    Route::delete('/invoices/{invoice}', function (Request $request, string $invoice) {
        return app(DocumentController::class)->destroy($request, DocumentType::Invoice, $invoice);
    })->middleware('can:invoices.delete')->name('invoices.destroy');

    // Credit Notes
    Route::get('/credit-notes', function (Request $request) {
        return app(DocumentController::class)->index($request, DocumentType::CreditNote);
    })->middleware('can:credit-notes.view')->name('credit-notes.index');

    Route::get('/credit-notes/{creditNote}', function (Request $request, string $creditNote) {
        return app(DocumentController::class)->show($request, DocumentType::CreditNote, $creditNote);
    })->middleware('can:credit-notes.view')->name('credit-notes.show');

    Route::post('/credit-notes', function (Request $request) {
        return app(DocumentController::class)->store(
            app(\App\Modules\Document\Presentation\Requests\CreateDocumentRequest::class),
            DocumentType::CreditNote
        );
    })->middleware('can:credit-notes.create')->name('credit-notes.store');

    // Delivery Notes
    Route::get('/delivery-notes', function (Request $request) {
        return app(DocumentController::class)->index($request, DocumentType::DeliveryNote);
    })->middleware('can:deliveries.view')->name('delivery-notes.index');

    Route::get('/delivery-notes/{deliveryNote}', function (Request $request, string $deliveryNote) {
        return app(DocumentController::class)->show($request, DocumentType::DeliveryNote, $deliveryNote);
    })->middleware('can:deliveries.view')->name('delivery-notes.show');

    Route::post('/delivery-notes', function (Request $request) {
        return app(DocumentController::class)->store(
            app(\App\Modules\Document\Presentation\Requests\CreateDocumentRequest::class),
            DocumentType::DeliveryNote
        );
    })->middleware('can:deliveries.create')->name('delivery-notes.store');
});
