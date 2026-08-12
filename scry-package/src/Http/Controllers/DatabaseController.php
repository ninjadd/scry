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

    public function tables()
    {
        return response()->json([
            'driver' => $this->manager->getDefaultDriver(),
            'tables' => $this->manager->driver()->getTables(),
        ]);
    }

    public function schema(string $table)
    {
        return response()->json(
            $this->manager->driver()->getTableSchema($table)
        );
    }

    public function data(Request $request, string $table)
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 25);
        $sortBy = $request->get('sort_by');
        $sortDir = $request->get('sort_dir', 'asc');

        return response()->json(
            $this->manager->driver()->getPaginatedRows($table, $page, $perPage, $sortBy, $sortDir)
        );
    }

    public function query(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        return response()->json(
            $this->manager->driver()->executeQuery($request->input('query'))
        );
    }
}
