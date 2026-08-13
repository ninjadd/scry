<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\HomeController;
use Scry\Http\Middleware\Authorize;

$path = config('scry.path', config('database-manager.path', 'scry'));
$middleware = array_merge(config('scry.middleware', config('database-manager.middleware', ['web'])), [Authorize::class]);

Route::group([
    'prefix' => $path,
    'middleware' => $middleware,
    'as' => 'scry.',
], function () {
    Route::get('/{view?}', HomeController::class)
        ->where('view', '(.*)')
        ->name('index');
});

if ($path !== 'db-manager') {
    Route::group([
        'prefix' => 'db-manager',
        'middleware' => $middleware,
        'as' => 'scry.alias.',
    ], function () {
        Route::get('/{view?}', HomeController::class)
            ->where('view', '(.*)');
    });
}
