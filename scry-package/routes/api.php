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
    // Database Operations
    Route::get('/databases', [ApiController::class, 'databases'])->name('databases');
    Route::post('/databases', [ApiController::class, 'createDatabase'])->name('databases.create');
    Route::delete('/databases', [ApiController::class, 'dropDatabase'])->name('databases.drop');

    // Server Stats & Tuning
    Route::get('/server/stats', [ApiController::class, 'serverStats'])->name('server.stats');
    Route::get('/server/tuning', [ApiController::class, 'tuningSuggestions'])->name('server.tuning');

    // Routines & Triggers
    Route::get('/views', [ApiController::class, 'views'])->name('views');
    Route::get('/triggers', [ApiController::class, 'triggers'])->name('triggers');
    Route::get('/procedures', [ApiController::class, 'procedures'])->name('procedures');

    // Users & Privileges
    Route::get('/users', [ApiController::class, 'users'])->name('users');

    // Global Search & Import/Export
    Route::get('/search', [ApiController::class, 'globalSearch'])->name('search');
    Route::post('/import', [ApiController::class, 'importFile'])->name('import');
    Route::get('/export/{table}', [ApiController::class, 'exportTable'])->name('export');

    // Tables & Rows
    Route::get('/tables', [ApiController::class, 'tables'])->name('tables');
    Route::get('/tables/{table}/schema', [ApiController::class, 'schema'])->name('schema');
    Route::get('/tables/{table}/rows', [ApiController::class, 'rows'])->name('rows');
    Route::get('/tables/{table}/data', [ApiController::class, 'rows'])->name('data');

    // Row-Level CRUD Routes
    Route::post('/tables/{table}/rows', [ApiController::class, 'insertRow'])->name('rows.insert');
    Route::put('/tables/{table}/rows', [ApiController::class, 'updateRow'])->name('rows.update');
    Route::delete('/tables/{table}/rows', [ApiController::class, 'deleteRow'])->name('rows.delete');

    // SQL Execution Routes
    Route::post('/sql/execute', [ApiController::class, 'executeSql'])->name('sql.execute');
    Route::post('/query', [ApiController::class, 'query'])->name('query');
});

if ($path !== 'db-manager') {
    Route::group([
        'prefix' => 'db-manager/api',
        'middleware' => $middleware,
        'as' => 'scry.api.alias.',
    ], function () {
        Route::get('/databases', [ApiController::class, 'databases']);
        Route::post('/databases', [ApiController::class, 'createDatabase']);
        Route::delete('/databases', [ApiController::class, 'dropDatabase']);

        Route::get('/server/stats', [ApiController::class, 'serverStats']);
        Route::get('/server/tuning', [ApiController::class, 'tuningSuggestions']);

        Route::get('/views', [ApiController::class, 'views']);
        Route::get('/triggers', [ApiController::class, 'triggers']);
        Route::get('/procedures', [ApiController::class, 'procedures']);

        Route::get('/users', [ApiController::class, 'users']);

        Route::get('/search', [ApiController::class, 'globalSearch']);
        Route::post('/import', [ApiController::class, 'importFile']);
        Route::get('/export/{table}', [ApiController::class, 'exportTable']);

        Route::get('/tables', [ApiController::class, 'tables']);
        Route::get('/tables/{table}/schema', [ApiController::class, 'schema']);
        Route::get('/tables/{table}/rows', [ApiController::class, 'rows']);
        Route::get('/tables/{table}/data', [ApiController::class, 'rows']);

        Route::post('/tables/{table}/rows', [ApiController::class, 'insertRow']);
        Route::put('/tables/{table}/rows', [ApiController::class, 'updateRow']);
        Route::delete('/tables/{table}/rows', [ApiController::class, 'deleteRow']);

        Route::post('/sql/execute', [ApiController::class, 'executeSql']);
        Route::post('/query', [ApiController::class, 'query']);
    });
}
