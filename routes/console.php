<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:clean-barcodes')->daily();

Schedule::call(function () {
    $main = database_path('database.sqlite');
    $backup = database_path('backups/database.sqlite');

    if (file_exists($backup)) {
        copy($backup, $main);
    }
})->daily();
