<?php

namespace Scry\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Scry\DatabaseExplorerManager;
use Scry\Exceptions\UnsupportedDriverException;

class DatabaseController extends Controller
{
    public function __construct(
        protected DatabaseExplorerManager $manager
    ) {}

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

    public function data(string $table, Request $request): JsonResponse
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

    public function query(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $sql = $request->input('query');

        try {
            $inspector = $this->manager->forConnection($connection);
            return response()->json($inspector->executeQuery($sql));
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
