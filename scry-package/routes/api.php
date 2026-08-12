<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\ApiController;
use Scry\Http\Middleware\Authorize;

$path = config('scry.path', config('database-manager.path', 'scry'));
$middleware = array_merge(config('scry.middleware', config('database-manager.middleware', ['web'])), [Authorize::class]);

Route::group([
    'prefix' => $path . '/api',
    'middleware' => $middleware,
    'as' => 'scry.api.',
], function () {
    Route::get('/tables', [ApiController::class, 'tables'])->name('tables');
    Route::get('/tables/{table}/schema', [ApiController::class, 'schema'])->name('schema');
    Route::get('/tables/{table}/rows', [ApiController::class, 'rows'])->name('rows');
    Route::get('/tables/{table}/data', [ApiController::class, 'rows'])->name('data');
    Route::post('/query', [ApiController::class, 'query'])->name('query');
});

if ($path !== 'db-manager') {
    Route::group([
        'prefix' => 'db-manager/api',
        'middleware' => $middleware,
        'as' => 'scry.api.alias.',
    ], function () {
        Route::get('/tables', [ApiController::class, 'tables']);
        Route::get('/tables/{table}/schema', [ApiController::class, 'schema']);
        Route::get('/tables/{table}/rows', [ApiController::class, 'rows']);
        Route::get('/tables/{table}/data', [ApiController::class, 'rows']);
        Route::post('/query', [ApiController::class, 'query']);
    });
}
