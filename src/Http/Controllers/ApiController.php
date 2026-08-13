<?php

namespace Scry\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Scry\DatabaseExplorerManager;
use Scry\Exceptions\UnsupportedDriverException;
use Scry\Services\ExportService;
use Scry\Services\GlobalSearchService;
use Scry\Services\ImportService;
use Scry\Services\ServerTuningAdvisor;
use Scry\Services\SqlRunner;
use PDOException;
use Throwable;

class ApiController extends Controller
{
    public function __construct(
        protected DatabaseExplorerManager $manager,
        protected SqlRunner $sqlRunner,
        protected ExportService $exportService,
        protected ImportService $importService,
        protected GlobalSearchService $searchService,
        protected ServerTuningAdvisor $tuningAdvisor
    ) {}

    /**
     * GET /scry/api/databases
     */
    public function databases(Request $request): JsonResponse
    {
        $connection = $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            return response()->json([
                'current_database' => config("database.connections.{$connection}.database", config('database.connections.pgsql.database')),
                'databases' => $inspector->getDatabases(),
            ]);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /scry/api/databases
     */
    public function createDatabase(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string']);
        $connection = $request->input('connection');
        $name = $request->input('name');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->createDatabase($name);
            return response()->json(['success' => $success, 'message' => "Database {$name} created successfully."], 201);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /scry/api/databases
     */
    public function dropDatabase(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string']);
        $connection = $request->input('connection') ?? $request->query('connection');
        $name = $request->input('name') ?? $request->query('name');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->dropDatabase($name);
            return response()->json(['success' => $success, 'message' => "Database {$name} dropped successfully."]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/tables
     * Interactive table creation endpoint.
     */
    public function createTable(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'columns' => 'required|array|min:1',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $tableName = $request->input('name');
        $cols = $request->input('columns');

        $colDefs = [];
        $driver = $this->manager->getDriverForConnection($connection ?? config('database.default'));
        $openQuote = match ($driver) {
            'sqlsrv' => '[',
            'mysql', 'mariadb' => '`',
            default => '"',
        };
        $closeQuote = match ($driver) {
            'sqlsrv' => ']',
            'mysql', 'mariadb' => '`',
            default => '"',
        };

        foreach ($cols as $col) {
            $colName = $col['name'] ?? null;
            $type = strtoupper($col['type'] ?? 'VARCHAR(255)');
            $nullable = !empty($col['nullable']) ? 'NULL' : 'NOT NULL';
            $autoInc = '';
            $pk = !empty($col['is_primary']) ? 'PRIMARY KEY' : '';

            if (!empty($col['auto_increment'])) {
                if ($driver === 'pgsql') {
                    $type = 'BIGSERIAL';
                } elseif ($driver === 'sqlite') {
                    $type = 'INTEGER';
                    $autoInc = 'AUTOINCREMENT';
                } elseif ($driver === 'sqlsrv') {
                    $autoInc = 'IDENTITY(1,1)';
                } else {
                    $autoInc = 'AUTO_INCREMENT';
                }
            }

            if ($colName) {
                $colDefs[] = "{$openQuote}{$colName}{$closeQuote} {$type} {$nullable} {$autoInc} {$pk}";
            }
        }

        $sql = "CREATE TABLE {$openQuote}{$tableName}{$closeQuote} (\n  " . implode(",\n  ", $colDefs) . "\n);";

        try {
            $res = $this->sqlRunner->execute($sql, $connection);
            if (isset($res['error'])) {
                return response()->json(['error' => $res['error']], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Table {$tableName} created successfully.",
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/tables/copy
     */
    public function copyTable(Request $request): JsonResponse
    {
        $request->validate([
            'source_table' => 'required|string',
            'target_table' => 'required|string',
            'copy_data' => 'nullable|boolean',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $source = $request->input('source_table');
        $target = $request->input('target_table');
        $copyData = (bool)$request->input('copy_data', true);

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->copyTable($source, $target, $copyData);

            return response()->json([
                'success' => $success,
                'message' => "Table {$source} copied to {$target} successfully.",
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * PUT /scry/api/tables/{table}/rename
     */
    public function renameTable(string $table, Request $request): JsonResponse
    {
        $request->validate([
            'new_name' => 'required|string',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $newName = $request->input('new_name');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->renameTable($table, $newName);

            return response()->json([
                'success' => $success,
                'message' => "Table {$table} renamed to {$newName} successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /scry/api/tables/{table}
     */
    public function dropTable(string $table, Request $request): JsonResponse
    {
        $connection = $request->input('connection') ?? $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->dropTable($table);

            return response()->json([
                'success' => $success,
                'message' => "Table {$table} dropped successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/tables/{table}/truncate
     */
    public function truncateTable(string $table, Request $request): JsonResponse
    {
        $connection = $request->input('connection') ?? $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->truncateTable($table);

            return response()->json([
                'success' => $success,
                'message' => "Table {$table} truncated successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/tables/{table}/optimize
     */
    public function optimizeTable(string $table, Request $request): JsonResponse
    {
        $connection = $request->input('connection') ?? $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            $success = $inspector->optimizeTable($table);

            return response()->json([
                'success' => $success,
                'message' => "Table {$table} optimized/vacuumed successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/users
     * Interactive MySQL user creation.
     */
    public function createUser(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'host' => 'required|string',
            'password' => 'required|string',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $user = $request->input('username');
        $host = $request->input('host');
        $pwd = addslashes($request->input('password'));

        $sql = "CREATE USER '{$user}'@'{$host}' IDENTIFIED BY '{$pwd}';";

        try {
            $res = $this->sqlRunner->execute($sql, $connection);
            if (isset($res['error'])) {
                return response()->json(['error' => $res['error']], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "MySQL User '{$user}'@'{$host}' created successfully.",
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/users/privileges
     * Interactive GRANT / REVOKE privileges.
     */
    public function manageUserPrivileges(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'host' => 'required|string',
            'action' => 'required|in:grant,revoke',
            'privileges' => 'required|array',
            'database' => 'nullable|string',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $user = $request->input('username');
        $host = $request->input('host');
        $action = strtoupper($request->input('action'));
        $privs = implode(', ', $request->input('privileges'));
        $db = $request->input('database', '*');
        $target = $db === '*' ? '*.*' : "`{$db}`.*";

        $sql = $action === 'GRANT'
            ? "GRANT {$privs} ON {$target} TO '{$user}'@'{$host}';"
            : "REVOKE {$privs} ON {$target} FROM '{$user}'@'{$host}';";

        try {
            $res = $this->sqlRunner->execute($sql, $connection);
            if (isset($res['error'])) {
                return response()->json(['error' => $res['error']], 422);
            }

            $this->sqlRunner->execute("FLUSH PRIVILEGES;", $connection);

            return response()->json([
                'success' => true,
                'message' => "Privileges {$action}ed for '{$user}'@'{$host}' successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/routines
     */
    public function createRoutine(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:PROCEDURE,FUNCTION',
            'body' => 'required|string',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $sql = $request->input('body');

        try {
            $res = $this->sqlRunner->execute($sql, $connection);
            if (isset($res['error'])) {
                return response()->json(['error' => $res['error']], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Routine created successfully.",
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/triggers
     */
    public function createTrigger(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'table' => 'required|string',
            'timing' => 'required|in:BEFORE,AFTER',
            'event' => 'required|in:INSERT,UPDATE,DELETE',
            'body' => 'required|string',
            'connection' => 'nullable|string',
        ]);

        $connection = $request->input('connection');
        $name = $request->input('name');
        $table = $request->input('table');
        $timing = $request->input('timing');
        $event = $request->input('event');
        $body = $request->input('body');

        $sql = "CREATE TRIGGER `{$name}` {$timing} {$event} ON `{$table}` FOR EACH ROW BEGIN\n{$body}\nEND;";

        try {
            $res = $this->sqlRunner->execute($sql, $connection);
            if (isset($res['error'])) {
                return response()->json(['error' => $res['error']], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Trigger `{$name}` created successfully.",
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /scry/api/views
     */
    public function views(Request $request): JsonResponse
    {
        $connection = $request->query('connection');
        try {
            $inspector = $this->manager->forConnection($connection);
            return response()->json(['views' => $inspector->getViews()]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/triggers
     */
    public function triggers(Request $request): JsonResponse
    {
        $connection = $request->query('connection');
        try {
            $inspector = $this->manager->forConnection($connection);
            return response()->json(['triggers' => $inspector->getTriggers()]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/procedures
     */
    public function procedures(Request $request): JsonResponse
    {
        $connection = $request->query('connection');
        try {
            $inspector = $this->manager->forConnection($connection);
            return response()->json(['procedures' => $inspector->getProcedures()]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/users
     */
    public function users(Request $request): JsonResponse
    {
        $connection = $request->query('connection');
        try {
            $inspector = $this->manager->forConnection($connection);
            $hasPrivs = $inspector->hasUserManagementPrivileges();
            $users = $hasPrivs ? $inspector->getUsers() : [];

            return response()->json([
                'has_privileges' => $hasPrivs,
                'users' => $users,
                'message' => $hasPrivs ? 'User list retrieved.' : 'Active database user does not hold elevated user management privileges (GRANT OPTION / CREATE USER).',
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/server/tuning
     */
    public function tuningSuggestions(Request $request): JsonResponse
    {
        $connection = $request->query('connection');
        try {
            return response()->json($this->tuningAdvisor->analyze($connection));
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/search?q=...
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1']);
        $connection = $request->query('connection');
        $term = $request->query('q');

        try {
            return response()->json($this->searchService->search($term, $connection));
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/import
     */
    public function importFile(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:sql,csv',
            'content' => 'required|string',
            'table' => 'nullable|string',
            'connection' => 'nullable|string',
        ]);

        $type = $request->input('type');
        $content = $request->input('content');
        $table = $request->input('table');
        $connection = $request->input('connection');

        try {
            if ($type === 'sql') {
                $res = $this->importService->importSql($content, $connection);
            } else {
                if (!$table) {
                    return response()->json(['error' => 'Target table parameter is required for CSV import.'], 422);
                }
                $res = $this->importService->importCsv($table, $content, $connection);
            }

            return response()->json($res);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /scry/api/server/stats
     */
    public function serverStats(Request $request): JsonResponse
    {
        $connection = $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            $stats = $inspector->getServerStats();
            $stats['available_connections'] = array_keys(config('database.connections', []));

            return response()->json($stats);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /scry/api/server/slow-queries
     */
    public function slowQueries(Request $request): JsonResponse
    {
        $connection = $request->query('connection');
        try {
            return response()->json($this->tuningAdvisor->getSlowQueries($connection));
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /scry/api/server/kill-process
     */
    public function killProcess(Request $request): JsonResponse
    {
        $request->validate(['pid' => 'required']);
        $pid = (int)$request->input('pid');
        $connection = $request->input('connection');

        try {
            return response()->json($this->tuningAdvisor->killProcess($pid, $connection));
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /scry/api/export/{table}
     */
    public function exportTable(string $table, Request $request)
    {
        $connection = $request->query('connection');
        $format = strtolower($request->query('format', 'csv'));

        try {
            $inspector = $this->manager->forConnection($connection);
            $driver = $this->manager->getDriverForConnection($connection ?? config('database.default'));
            
            $rowsData = $inspector->getPaginatedRows($table, 1, 5000);
            $rows = $rowsData['data'] ?? [];

            if ($format === 'csv') {
                return response()->streamDownload(function () use ($rows) {
                    $this->exportService->streamCsv($rows);
                }, "{$table}_export.csv", [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Cache-Control' => 'no-cache, private',
                ]);
            }

            switch ($format) {
                case 'doc':
                case 'word':
                    $content = $this->exportService->exportWord($table, $rows);
                    $contentType = 'application/msword';
                    $filename = "{$table}_export.doc";
                    break;
                case 'odt':
                    $content = $this->exportService->exportOdt($table, $rows);
                    $contentType = 'application/vnd.oasis.opendocument.text';
                    $filename = "{$table}_export.odt";
                    break;
                case 'sql':
                    $content = $this->exportService->exportSql($table, $rows, $driver);
                    $contentType = 'text/plain';
                    $filename = "{$table}_dump.sql";
                    break;
                case 'xml':
                    $content = $this->exportService->exportXml($table, $rows);
                    $contentType = 'application/xml';
                    $filename = "{$table}_export.xml";
                    break;
                case 'pdf':
                    $content = $this->exportService->exportPdf($table, $rows);
                    $contentType = 'application/pdf';
                    $filename = "{$table}_export.pdf";
                    break;
                case 'latex':
                    $content = $this->exportService->exportLatex($table, $rows);
                    $contentType = 'text/plain';
                    $filename = "{$table}_export.tex";
                    break;
                case 'json':
                default:
                    $content = json_encode($rows, JSON_PRETTY_PRINT);
                    $contentType = 'application/json';
                    $filename = "{$table}_export.json";
                    break;
            }

            return response($content, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'no-cache, private',
            ]);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /scry/api/tables
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
     * GET /scry/api/schema/full
     * Bulk fetch all table schemas in a single HTTP request to avoid N+1 frontend requests.
     */
    public function fullSchema(Request $request): JsonResponse
    {
        $connection = $request->query('connection');

        try {
            $inspector = $this->manager->forConnection($connection);
            $tablesData = $inspector->getTables();
            $schemas = [];

            foreach ($tablesData as $t) {
                $tableName = $t['name'] ?? null;
                if ($tableName) {
                    $schemas[] = $inspector->getTableSchema($tableName);
                }
            }

            return response()->json([
                'connection' => $connection ?? config('database.default'),
                'schemas' => $schemas,
            ]);
        } catch (UnsupportedDriverException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /scry/api/tables/{table}/schema
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
     * POST /scry/api/query
     */
    public function query(Request $request): JsonResponse
    {
        return $this->executeSql($request);
    }
}
