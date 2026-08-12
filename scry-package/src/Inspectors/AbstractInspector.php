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

        if ($sortBy) {
            $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortBy, $direction);
        }

        $items = $query->forPage($page, $perPage)->get()->toArray();

        return [
            'table' => $table,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / max(1, $perPage)),
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
}
