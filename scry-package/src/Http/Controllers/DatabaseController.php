<?php

namespace Scry\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Scry\DatabaseExplorerManager;

class DatabaseController extends Controller
{
    public function __construct(protected DatabaseExplorerManager $manager)
    {
    }

    protected function getInspector(Request $request)
    {
        $connection = $request->get('connection');
        return $this->manager->forConnection($connection);
    }

    public function tables(Request $request)
    {
        $connectionName = $request->get('connection')
            ?? config('database-manager.connection')
            ?? config('database.default');

        $inspector = $this->getInspector($request);

        return response()->json([
            'connection' => $connectionName,
            'driver' => $this->manager->getDriverForConnection($connectionName),
            'tables' => $inspector->getTables(),
            'available_connections' => array_keys(config('database.connections', [])),
        ]);
    }

    public function schema(Request $request, string $table)
    {
        return response()->json(
            $this->getInspector($request)->getTableSchema($table)
        );
    }

    public function data(Request $request, string $table)
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 25);
        $sortBy = $request->get('sort_by');
        $sortDir = $request->get('sort_dir', 'asc');

        return response()->json(
            $this->getInspector($request)->getPaginatedRows($table, $page, $perPage, $sortBy, $sortDir)
        );
    }

    public function query(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        return response()->json(
            $this->getInspector($request)->executeQuery($request->input('query'))
        );
    }
}
