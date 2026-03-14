<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SvgController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(route('dashboard'));
})->name('home');

Route::get('/invoices/{invoice}/stream', [InvoiceController::class, 'stream'])
    ->name('invoices.stream');
Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'download'])
    ->name('invoices.pdf');

Route::get('/svg/render', [SvgController::class, 'render'])
        ->name('svg.render');

require __DIR__.'/settings.php';
