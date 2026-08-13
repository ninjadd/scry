<?php

namespace Scry\Inspectors;

use Illuminate\Database\ConnectionInterface;
use Scry\Contracts\DatabaseInspector;

abstract class AbstractInspector implements DatabaseInspector
{
    public function __construct(
        protected ConnectionInterface $connection
    ) {}

    public function getPaginatedRows(
        string $table,
        int $page = 1,
        int $perPage = 25,
        ?string $sortBy = null,
        string $sortDir = 'asc'
    ): array {
        $query = $this->connection->table($table);

        $total = $query->count();

        $effectiveSortBy = $sortBy;
        if (!$effectiveSortBy) {
            try {
                $schema = $this->getTableSchema($table);
                foreach ($schema['columns'] ?? [] as $col) {
                    if ($col['is_primary'] ?? false) {
                        $effectiveSortBy = $col['name'];
                        break;
                    }
                }
                if (!$effectiveSortBy && !empty($schema['columns'])) {
                    $effectiveSortBy = $schema['columns'][0]['name'];
                }
            } catch (\Throwable $e) {
                // fallback
            }
        }

        if ($effectiveSortBy) {
            $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($effectiveSortBy, $direction);
        }

        $items = $query->forPage($page, $perPage)->get()->toArray();

        $lastPage = (int) ceil($total / max(1, $perPage));

        return [
            'table' => $table,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'meta' => [
                'page' => $page,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
            'data' => $items,
        ];
    }

    public function insertRow(string $table, array $data): bool
    {
        return $this->connection->table($table)->insert($data);
    }

    public function updateRow(string $table, array $primaryKey, array $data): bool
    {
        $query = $this->connection->table($table);

        foreach ($primaryKey as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->update($data) >= 0;
    }

    public function deleteRow(string $table, array $primaryKey): bool
    {
        $query = $this->connection->table($table);

        foreach ($primaryKey as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->delete() > 0;
    }

    public function getDatabases(): array
    {
        return [$this->connection->getDatabaseName()];
    }

    public function createDatabase(string $name, ?string $charset = null, ?string $collation = null): bool
    {
        $sql = "CREATE DATABASE " . $this->wrapIdentifier($name);
        return $this->connection->statement($sql);
    }

    public function dropDatabase(string $name): bool
    {
        $sql = "DROP DATABASE " . $this->wrapIdentifier($name);
        return $this->connection->statement($sql);
    }

    public function dropTable(string $table): bool
    {
        $sql = "DROP TABLE IF EXISTS " . $this->wrapIdentifier($table);
        return $this->connection->statement($sql);
    }

    public function renameTable(string $table, string $newName): bool
    {
        $sql = "ALTER TABLE " . $this->wrapIdentifier($table) . " RENAME TO " . $this->wrapIdentifier($newName);
        return $this->connection->statement($sql);
    }

    public function copyTable(string $sourceTable, string $targetTable, bool $copyData = true): bool
    {
        $source = $this->wrapIdentifier($sourceTable);
        $target = $this->wrapIdentifier($targetTable);

        $this->connection->statement("CREATE TABLE {$target} (LIKE {$source} INCLUDING ALL)");
        if ($copyData) {
            $this->connection->statement("INSERT INTO {$target} SELECT * FROM {$source}");
        }
        return true;
    }

    public function truncateTable(string $table): bool
    {
        $sql = "TRUNCATE TABLE " . $this->wrapIdentifier($table);
        return $this->connection->statement($sql);
    }

    public function optimizeTable(string $table): bool
    {
        return true;
    }

    public function getViews(): array
    {
        return [];
    }

    public function getTriggers(): array
    {
        return [];
    }

    public function getProcedures(): array
    {
        return [];
    }

    public function hasUserManagementPrivileges(): bool
    {
        return false;
    }

    public function getUsers(): array
    {
        return [];
    }

    public function executeQuery(string $query): array
    {
        $startTime = microtime(true);
        $results = $this->connection->select($query);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'execution_time_ms' => $executionTime,
            'row_count' => count($results),
            'data' => $results,
        ];
    }

    protected function wrapIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
