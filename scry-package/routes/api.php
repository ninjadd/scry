<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\DatabaseController;
use Scry\Http\Middleware\Authorize;

Route::group([
    'prefix' => config('scry.path', 'scry') . '/api',
    'middleware' => array_merge(config('scry.middleware', ['web']), [Authorize::class]),
    'as' => 'scry.api.',
], function () {
    Route::get('/tables', [DatabaseController::class, 'tables'])->name('tables');
    Route::get('/tables/{table}/schema', [DatabaseController::class, 'schema'])->name('schema');
    Route::get('/tables/{table}/data', [DatabaseController::class, 'data'])->name('data');
    Route::post('/query', [DatabaseController::class, 'query'])->name('query');
});
