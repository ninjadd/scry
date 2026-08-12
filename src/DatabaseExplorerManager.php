<?php

namespace Scry\DatabaseManager;

use Illuminate\Support\Manager;
use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Scry\DatabaseManager\Contracts\DatabaseInspector;
use Scry\DatabaseManager\Inspectors\MysqlInspector;
use Scry\DatabaseManager\Inspectors\PostgresInspector;
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

        $driver = $this->container['config']->get("database.connections.{$connectionName}.driver");

        return match ($driver) {
            'pgsql', 'postgres', 'postgresql' => 'pgsql',
            'mysql', 'mariadb' => 'mysql',
            default => throw new InvalidArgumentException("Unsupported database driver [{$driver}]."),
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
