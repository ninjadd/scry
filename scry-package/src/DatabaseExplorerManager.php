<?php

namespace Scry;

use Illuminate\Support\Manager;
use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Scry\Contracts\DatabaseInspector;
use Scry\Inspectors\MysqlInspector;
use Scry\Inspectors\PostgresInspector;
use InvalidArgumentException;

class DatabaseExplorerManager extends Manager
{
    /**
     * Get default driver name based on active database connection.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        $connectionName = $this->container['config']->get('database-manager.connection')
            ?? $this->container['config']->get('database.default');

        return $this->getDriverForConnection($connectionName);
    }

    /**
     * Get inspector for a specific named connection (e.g. 'pgsql', 'mysql').
     *
     * @param string|null $connectionName
     * @return DatabaseInspector
     */
    public function forConnection(?string $connectionName = null): DatabaseInspector
    {
        if (null === $connectionName) {
            return $this->driver();
        }

        $driverName = $this->getDriverForConnection($connectionName);
        $connection = $this->container->make(LaravelDatabaseManager::class)->connection($connectionName);

        return match ($driverName) {
            'pgsql' => new PostgresInspector($connection),
            'mysql' => new MysqlInspector($connection),
            default => throw new InvalidArgumentException("Unsupported driver [{$driverName}] for connection [{$connectionName}]."),
        };
    }

    /**
     * Resolve driver type for a named connection.
     */
    public function getDriverForConnection(string $connectionName): string
    {
        $driver = $this->container['config']->get("database.connections.{$connectionName}.driver");

        return match ($driver) {
            'pgsql', 'postgres', 'postgresql' => 'pgsql',
            'mysql', 'mariadb' => 'mysql',
            default => throw new InvalidArgumentException("Unsupported database driver [{$driver}] for connection [{$connectionName}]."),
        };
    }

    /**
     * Create PostgreSQL Inspector Driver instance.
     *
     * @return DatabaseInspector
     */
    protected function createPgsqlDriver(): DatabaseInspector
    {
        $connectionName = $this->container['config']->get('database-manager.connection');
        $connection = $this->container->make(LaravelDatabaseManager::class)->connection($connectionName);

        return new PostgresInspector($connection);
    }

    /**
     * Create MySQL Inspector Driver instance.
     *
     * @return DatabaseInspector
     */
    protected function createMysqlDriver(): DatabaseInspector
    {
        $connectionName = $this->container['config']->get('database-manager.connection');
        $connection = $this->container->make(LaravelDatabaseManager::class)->connection($connectionName);

        return new MysqlInspector($connection);
    }
}
