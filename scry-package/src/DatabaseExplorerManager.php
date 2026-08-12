<?php

namespace Scry;

use Illuminate\Support\Manager;
use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Scry\Contracts\DatabaseInspector;
use Scry\Exceptions\UnsupportedDriverException;
use Scry\Inspectors\MysqlInspector;
use Scry\Inspectors\PostgresInspector;

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
            ?? $this->container['config']->get('database-manager.connection')
            ?? $this->container['config']->get('database.default');

        return $this->getDriverForConnection($connectionName);
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
            ?? $this->container['config']->get('database-manager.connection')
            ?? $this->container['config']->get('database.default');

        $driverName = $this->getDriverForConnection($connectionName);
        $connection = $this->container->make(LaravelDatabaseManager::class)->connection($connectionName);

        return match ($driverName) {
            'pgsql' => new PostgresInspector($connection),
            'mysql' => new MysqlInspector($connection),
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
            'mysql', 'mariadb' => 'mysql',
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
}
