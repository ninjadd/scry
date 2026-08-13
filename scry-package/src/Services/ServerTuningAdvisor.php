<?php

namespace Scry\Services;

use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;

class ServerTuningAdvisor
{
    public function __construct(
        protected LaravelDatabaseManager $dbManager
    ) {}

    /**
     * Analyze database status variables and generate optimization suggestions.
     */
    public function analyze(?string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        $driver = config("database.connections.{$connectionName}.driver", 'pgsql');

        if (!in_array($driver, ['mysql', 'mariadb'])) {
            return [
                'driver' => $driver,
                'suggestions' => [
                    [
                        'category' => 'General',
                        'title' => 'Tuning Recommendations',
                        'severity' => 'info',
                        'recommendation' => 'Server tuning recommendations are optimized for MySQL/MariaDB database engines.',
                    ]
                ]
            ];
        }

        $connection = $this->dbManager->connection($connectionName);
        $suggestions = [];

        try {
            $statusRaw = $connection->select("SHOW GLOBAL STATUS;");
            $varsRaw = $connection->select("SHOW GLOBAL VARIABLES;");

            $status = [];
            foreach ($statusRaw as $s) {
                $sArr = (array)$s;
                $key = $sArr['Variable_name'] ?? $sArr['variable_name'] ?? null;
                if ($key) $status[$key] = $sArr['Value'] ?? $sArr['value'] ?? null;
            }

            $vars = [];
            foreach ($varsRaw as $v) {
                $vArr = (array)$v;
                $key = $vArr['Variable_name'] ?? $vArr['variable_name'] ?? null;
                if ($key) $vars[$key] = $vArr['Value'] ?? $vArr['value'] ?? null;
            }

            $bufferPoolSize = (int)($vars['innodb_buffer_pool_size'] ?? 0);
            if ($bufferPoolSize < 134217728) {
                $suggestions[] = [
                    'category' => 'InnoDB',
                    'title' => 'Small InnoDB Buffer Pool Size',
                    'severity' => 'warning',
                    'recommendation' => 'innodb_buffer_pool_size is set to ' . round($bufferPoolSize / 1048576, 1) . 'MB. Consider increasing to 60-80% of available RAM on dedicated DB servers.',
                ];
            }

            $slowQueries = (int)($status['Slow_queries'] ?? 0);
            if ($slowQueries > 0) {
                $suggestions[] = [
                    'category' => 'Queries',
                    'title' => 'Slow Queries Detected',
                    'severity' => 'warning',
                    'recommendation' => "{$slowQueries} slow queries recorded. Check indexing on frequently queried columns or enable slow_query_log.",
                ];
            }

            $createdTmpDiskTables = (int)($status['Created_tmp_disk_tables'] ?? 0);
            $createdTmpTables = (int)($status['Created_tmp_tables'] ?? 1);
            $tmpRatio = round(($createdTmpDiskTables / max(1, $createdTmpTables)) * 100, 1);

            if ($tmpRatio > 25) {
                $suggestions[] = [
                    'category' => 'Memory',
                    'title' => 'High Temporary Disk Table Ratio (' . $tmpRatio . '%)',
                    'severity' => 'warning',
                    'recommendation' => 'Consider increasing tmp_table_size and max_heap_table_size to prevent in-memory temp tables spilling to disk.',
                ];
            }

            if (empty($suggestions)) {
                $suggestions[] = [
                    'category' => 'Health',
                    'title' => 'Optimal Configuration',
                    'severity' => 'success',
                    'recommendation' => 'Database server status variables are operating within optimal parameters.',
                ];
            }
        } catch (\Throwable $e) {
            $suggestions[] = [
                'category' => 'Error',
                'title' => 'Analysis Failed',
                'severity' => 'info',
                'recommendation' => 'Could not fetch status variables: ' . $e->getMessage(),
            ];
        }

        return [
            'driver' => $driver,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Get active processes / slow queries.
     */
    public function getSlowQueries(?string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        $driver = config("database.connections.{$connectionName}.driver", 'pgsql');
        $connection = $this->dbManager->connection($connectionName);

        $processes = [];

        if (in_array($driver, ['mysql', 'mariadb'])) {
            try {
                $raw = $connection->select("SHOW FULL PROCESSLIST;");
                foreach ($raw as $r) {
                    $arr = (array)$r;
                    $processes[] = [
                        'pid' => $arr['Id'] ?? $arr['id'] ?? 0,
                        'user' => $arr['User'] ?? $arr['user'] ?? 'unknown',
                        'host' => $arr['Host'] ?? $arr['host'] ?? '',
                        'db' => $arr['db'] ?? $arr['DB'] ?? '',
                        'command' => $arr['Command'] ?? $arr['command'] ?? '',
                        'time' => (int)($arr['Time'] ?? $arr['time'] ?? 0),
                        'state' => $arr['State'] ?? $arr['state'] ?? '',
                        'info' => $arr['Info'] ?? $arr['info'] ?? '',
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        } elseif ($driver === 'pgsql') {
            try {
                $raw = $connection->select("
                    SELECT pid, usename AS user, client_addr AS host, datname AS db, state,
                           EXTRACT(EPOCH FROM (now() - query_start))::integer AS time,
                           query AS info
                    FROM pg_stat_activity
                    WHERE state != 'idle'
                    ORDER BY query_start ASC
                ");
                foreach ($raw as $r) {
                    $arr = (array)$r;
                    $processes[] = [
                        'pid' => $arr['pid'] ?? 0,
                        'user' => $arr['user'] ?? 'unknown',
                        'host' => (string)($arr['host'] ?? 'local'),
                        'db' => $arr['db'] ?? '',
                        'command' => 'Query',
                        'time' => (int)($arr['time'] ?? 0),
                        'state' => $arr['state'] ?? '',
                        'info' => $arr['info'] ?? '',
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return [
            'driver' => $driver,
            'processes' => $processes,
        ];
    }

    /**
     * Terminate / Kill a database process by PID.
     */
    public function killProcess(int $pid, ?string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        $driver = config("database.connections.{$connectionName}.driver", 'pgsql');
        $connection = $this->dbManager->connection($connectionName);

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $connection->statement("KILL {$pid};");
        } elseif ($driver === 'pgsql') {
            $connection->select("SELECT pg_terminate_backend({$pid});");
        } else {
            throw new \InvalidArgumentException("Process termination is not supported for {$driver}.");
        }

        return [
            'success' => true,
            'message' => "Process {$pid} terminated successfully.",
        ];
    }
}
