<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SvgController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/run-seeder', function (Request $request) {

    if ($request->input('code') !== 8974) {
        abort(404);
    }
    Artisan::command('migrate:fresh', function () {});
    Artisan::command('db:seed', function () {});
});

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
