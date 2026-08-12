<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\HomeController;
use Scry\Http\Middleware\Authorize;

Route::group([
    'prefix' => config('scry.path', 'scry'),
    'middleware' => array_merge(config('scry.middleware', ['web']), [Authorize::class]),
    'as' => 'scry.',
], function () {
    Route::get('/{view?}', HomeController::class)
        ->where('view', '(.*)')
        ->name('index');
});
