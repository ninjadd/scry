<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\HomeController;
use Scry\Http\Middleware\Authorize;

$path = config('scry.path', 'scry');
$middleware = array_merge(config('scry.middleware', ['web']), [Authorize::class]);

Route::group([
    'prefix' => $path,
    'middleware' => $middleware,
    'as' => 'scry.',
], function () {
    Route::get('/{view?}', HomeController::class)
        ->where('view', '(.*)')
        ->name('index');
});
