<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\HomeController;
use Scry\Http\Middleware\Authorize;

Route::group([
    'prefix' => config('database-manager.path', 'db-manager'),
    'middleware' => array_merge(config('database-manager.middleware', ['web']), [Authorize::class]),
    'as' => 'database-manager.',
], function () {
    Route::get('/{view?}', HomeController::class)
        ->where('view', '(.*)')
        ->name('index');
});
