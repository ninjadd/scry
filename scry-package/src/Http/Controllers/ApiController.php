<?php

namespace Scry\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Scry\DatabaseExplorerManager;
use Scry\Exceptions\UnsupportedDriverException;
use Scry\Services\SqlRunner;
use PDOException;
use Throwable;

class ApiController extends Controller
{
    public function __construct(
        protected DatabaseExplorerManager $manager,
        protected SqlRunner $sqlRunner
    ) {}

    /**
     * GET /scry/api/tables
     * Returns array of all tables with connection meta.
     */
    public function tables(Request $request): JsonResponse
    {
        $connection = $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            $activeConn = $connection ?? config('database.default');

            return response()->json([
                'connection' => $activeConn,
                'driver' => $this->manager->getDriverForConnection($activeConn),
                'tables' => $inspector->getTables(),
                'available_connections' => array_keys(config('database.connections', [])),
            ]);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/tables/{table}/schema
     * Returns column, index, and foreign key metadata for a specific table.
     */
    public function schema(string $table, Request $request): JsonResponse
    {
        $connection = $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            return response()->json($inspector->getTableSchema($table));
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/tables/{table}/rows
     * Accepts page, per_page, sort_by, sort_dir, connection params and returns paginated row data.
     */
    public function rows(string $table, Request $request): JsonResponse
    {
        $connection = $request->query('connection');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 25);
        $sortBy = $request->query('sort_by');
        $sortDir = $request->query('sort_dir', 'asc');

        try {
            $inspector = $this->manager->forConnection($connection);
            return response()->json($inspector->getPaginatedRows($table, $page, $perPage, $sortBy, $sortDir));
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /scry/api/tables/{table}/rows
     * Inserts a new row into the table.
     */
    public function insertRow(string $table, Request $request): JsonResponse
    {
        $request->validate([
            'data' => 'required|array',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $data = $request->input('data');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->insertRow($table, $data);

            return response()->json([
                'success' => $success,
                'message' => 'Row inserted successfully.',
            ], 201);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PDOException $e) {
            return response()->json([
                'error' => 'Database error: ' . $e->getMessage(),
                'code' => $e->getCode(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * PUT /scry/api/tables/{table}/rows
     * Updates an existing row in the table by primary key.
     */
    public function updateRow(string $table, Request $request): JsonResponse
    {
        $request->validate([
            'primary_key' => 'required|array',
            'data' => 'required|array',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $primaryKey = $request->input('primary_key');
        $data = $request->input('data');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->updateRow($table, $primaryKey, $data);

            return response()->json([
                'success' => $success,
                'message' => 'Row updated successfully.',
            ]);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PDOException $e) {
            return response()->json([
                'error' => 'Database error: ' . $e->getMessage(),
                'code' => $e->getCode(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /scry/api/tables/{table}/rows
     * Deletes a row from the table by primary key.
     */
    public function deleteRow(string $table, Request $request): JsonResponse
    {
        $request->validate([
            'primary_key' => 'required|array',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection') ?? $request->query('connection');
        $primaryKey = $request->input('primary_key') ?? json_decode($request->query('primary_key', '{}'), true);

        if (empty($primaryKey)) {
            return response()->json(['error' => 'Primary key condition is required for deletion.'], 422);
        }

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->deleteRow($table, $primaryKey);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Row deleted successfully.' : 'No row matched primary key condition.',
            ]);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PDOException $e) {
            return response()->json([
                'error' => 'Database error: ' . $e->getMessage(),
                'code' => $e->getCode(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/sql/execute
     * Executes raw SQL query via SqlRunner service and returns type-detected results.
     */
    public function executeSql(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $sql = $request->input('query');

        try {
            $result = $this->sqlRunner->execute($sql, $connection);
            $status = isset($result['error']) ? 422 : 200;

            return response()->json($result, $status);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /scry/api/query (Alias for executeSql)
     */
    public function query(Request $request): JsonResponse
    {
        return $this->executeSql($request);
    }
}
