<?php
// In routes/web.php

use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Console\Output\BufferedOutput;

Route::controller(ItemController::class)->group(function () {
    Route::get('/',  'index')->name('items.index');
    Route::post('/generate-items', 'generate')->name('items.generate');
    Route::post('/clear-items', 'clear')->name('items.clear');
    Route::post('/save-settings','saveSettings')->name('items.saveSettings');

});

Route::get('/fetch', function () {
    $output = new BufferedOutput();
    Artisan::call('app:fetch-comments', [], $output);
    return '<pre>' . $output->fetch() . '</pre>';
});

Route::get('/fetch/{count}', function (int $count) {
    $output = new BufferedOutput();
    Artisan::call('app:fetch-comments', ['--count' => $count], $output);
    return '<pre>' . $output->fetch() . '</pre>';
});
