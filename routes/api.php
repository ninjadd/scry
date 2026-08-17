<?php

use Illuminate\Support\Facades\Route;
use Scry\Http\Controllers\ApiController;
use Scry\Http\Middleware\Authorize;

$path = config('scry.path', 'scry');
$middleware = array_merge(config('scry.middleware', ['web']), [Authorize::class]);

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
    Route::get('/server/slow-queries', [ApiController::class, 'slowQueries'])->name('server.slow_queries');
    Route::post('/server/kill-process', [ApiController::class, 'killProcess'])->name('server.kill_process');

    // Routines & Triggers
    Route::get('/views', [ApiController::class, 'views'])->name('views');
    Route::get('/triggers', [ApiController::class, 'triggers'])->name('triggers');
    Route::post('/triggers', [ApiController::class, 'createTrigger'])->name('triggers.create');
    Route::get('/procedures', [ApiController::class, 'procedures'])->name('procedures');
    Route::post('/routines', [ApiController::class, 'createRoutine'])->name('routines.create');

    // Users & Privileges
    Route::get('/users', [ApiController::class, 'users'])->name('users');
    Route::post('/users', [ApiController::class, 'createUser'])->name('users.create');
    Route::post('/users/privileges', [ApiController::class, 'manageUserPrivileges'])->name('users.privileges');

    // Global Search & Import/Export
    Route::get('/search', [ApiController::class, 'globalSearch'])->name('search');
    Route::post('/import', [ApiController::class, 'importFile'])->name('import');
    Route::get('/export/{table}', [ApiController::class, 'exportTable'])->name('export');

    // Tables & Rows DDL
    Route::get('/tables', [ApiController::class, 'tables'])->name('tables');
    Route::post('/tables', [ApiController::class, 'createTable'])->name('tables.create');
    Route::post('/tables/copy', [ApiController::class, 'copyTable'])->name('tables.copy');
    Route::put('/tables/{table}/rename', [ApiController::class, 'renameTable'])->name('tables.rename');
    Route::delete('/tables/{table}', [ApiController::class, 'dropTable'])->name('tables.drop');
    Route::post('/tables/{table}/truncate', [ApiController::class, 'truncateTable'])->name('tables.truncate');
    Route::post('/tables/{table}/optimize', [ApiController::class, 'optimizeTable'])->name('tables.optimize');

    Route::get('/schema/full', [ApiController::class, 'fullSchema'])->name('schema.full');
    Route::get('/schema/relationships', [ApiController::class, 'schemaRelationships'])->name('schema.relationships');
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
