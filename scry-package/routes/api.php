<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\DatabaseController;
use Scry\Http\Middleware\Authorize;

$path = config('scry.path', config('database-manager.path', 'scry'));
$middleware = array_merge(config('scry.middleware', config('database-manager.middleware', ['web'])), [Authorize::class]);

Route::group([
    'prefix' => $path . '/api',
    'middleware' => $middleware,
    'as' => 'scry.api.',
], function () {
    Route::get('/tables', [DatabaseController::class, 'tables'])->name('tables');
    Route::get('/tables/{table}/schema', [DatabaseController::class, 'schema'])->name('schema');
    Route::get('/tables/{table}/data', [DatabaseController::class, 'data'])->name('data');
    Route::post('/query', [DatabaseController::class, 'query'])->name('query');
});

if ($path !== 'db-manager') {
    Route::group([
        'prefix' => 'db-manager/api',
        'middleware' => $middleware,
        'as' => 'scry.api.alias.',
    ], function () {
        Route::get('/tables', [DatabaseController::class, 'tables']);
        Route::get('/tables/{table}/schema', [DatabaseController::class, 'schema']);
        Route::get('/tables/{table}/data', [DatabaseController::class, 'data']);
        Route::post('/query', [DatabaseController::class, 'query']);
    });
}
