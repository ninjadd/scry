<?php

namespace Scry;

use Illuminate\Support\Manager;
use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Scry\Contracts\DatabaseInspector;
use Scry\Exceptions\UnsupportedDriverException;
use Scry\Inspectors\MariadbInspector;
use Scry\Inspectors\MysqlInspector;
use Scry\Inspectors\PostgresInspector;
use Scry\Inspectors\SqliteInspector;
use Scry\Inspectors\SqlsrvInspector;

class DatabaseExplorerManager extends Manager
{
    /**
     * Get default driver name based on active database connection.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        $connectionName = $this->container['config']->get('scry.connection')
            ?? $this->container['config']->get('database.default');

        return $this->getDriverForConnection($connectionName);
    }

    /**
     * Get list of database connections configured and currently reachable/usable.
     *
     * @return array
     */
    public function getAvailableConnections(): array
    {
        $allConnections = array_keys($this->container['config']->get('database.connections', []));
        $usable = [];

        foreach ($allConnections as $name) {
            try {
                $this->getDriverForConnection($name);
                $config = $this->container['config']->get("database.connections.{$name}", []);
                $driver = $config['driver'] ?? '';

                if (in_array($driver, ['sqlite', 'sqlite3'])) {
                    $database = $config['database'] ?? '';
                    if ($database !== ':memory:' && !empty($database) && !file_exists($database)) {
                        continue;
                    }
                } else {
                    $host = $config['host'] ?? null;
                    $port = $config['port'] ?? match ($driver) {
                        'pgsql', 'postgres' => 5432,
                        'mysql', 'mariadb' => 3306,
                        'sqlsrv', 'mssql' => 1433,
                        default => null,
                    };

                    if ($host && $port) {
                        $fp = @fsockopen($host, (int) $port, $errno, $errstr, 0.2);
                        if (!$fp) {
                            continue;
                        }
                        fclose($fp);
                    }
                }

                $connection = $this->container->make(LaravelDatabaseManager::class)->connection($name);
                $connection->getPdo();
                $usable[] = $name;
            } catch (\Throwable) {
                continue;
            }
        }

        if (empty($usable)) {
            $default = $this->container['config']->get('database.default');
            if ($default) {
                $usable[] = $default;
            }
        }

        return array_values(array_unique($usable));
    }

    /**
     * Resolve DatabaseInspector instance for a specific connection name.
     * Defaults to the host application's default connection if null.
     *
     * @param string|null $connectionName
     * @return DatabaseInspector
     */
    public function connection(?string $connectionName = null): DatabaseInspector
    {
        return $this->forConnection($connectionName);
    }

    /**
     * Resolve DatabaseInspector instance for a specific connection name.
     *
     * @param string|null $connectionName
     * @return DatabaseInspector
     */
    public function forConnection(?string $connectionName = null): DatabaseInspector
    {
        $connectionName = $connectionName
            ?? $this->container['config']->get('scry.connection')
            ?? $this->container['config']->get('database.default');

        $driverName = $this->getDriverForConnection($connectionName);
        $connection = $this->container->make(LaravelDatabaseManager::class)->connection($connectionName);

        return match ($driverName) {
            'pgsql' => new PostgresInspector($connection),
            'mysql' => new MysqlInspector($connection),
            'mariadb' => new MariadbInspector($connection),
            'sqlite' => new SqliteInspector($connection),
            'sqlsrv' => new SqlsrvInspector($connection),
            default => throw UnsupportedDriverException::forDriver($driverName, $connectionName),
        };
    }

    /**
     * Resolve driver type for a named connection.
     *
     * @param string $connectionName
     * @return string
     * @throws UnsupportedDriverException
     */
    public function getDriverForConnection(string $connectionName): string
    {
        $driver = $this->container['config']->get("database.connections.{$connectionName}.driver");

        return match ($driver) {
            'pgsql', 'postgres', 'postgresql' => 'pgsql',
            'mysql' => 'mysql',
            'mariadb' => 'mariadb',
            'sqlite', 'sqlite3' => 'sqlite',
            'sqlsrv', 'mssql', 'sqlserver' => 'sqlsrv',
            default => throw UnsupportedDriverException::forDriver((string) $driver, $connectionName),
        };
    }

    /**
     * Create PostgreSQL Inspector Driver instance.
     *
     * @return DatabaseInspector
     */
    protected function createPgsqlDriver(): DatabaseInspector
    {
        return $this->forConnection();
    }

    /**
     * Create MySQL Inspector Driver instance.
     *
     * @return DatabaseInspector
     */
    protected function createMysqlDriver(): DatabaseInspector
    {
        return $this->forConnection();
    }

    /**
     * Create MariaDB Inspector Driver instance.
     *
     * @return DatabaseInspector
     */
    protected function createMariadbDriver(): DatabaseInspector
    {
        return $this->forConnection();
    }

    /**
     * Create SQLite Inspector Driver instance.
     *
     * @return DatabaseInspector
     */
    protected function createSqliteDriver(): DatabaseInspector
    {
        return $this->forConnection();
    }

    /**
     * Create SQL Server Inspector Driver instance.
     *
     * @return DatabaseInspector
     */
    protected function createSqlsrvDriver(): DatabaseInspector
    {
        return $this->forConnection();
    }
}
