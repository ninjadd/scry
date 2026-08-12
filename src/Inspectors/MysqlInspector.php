<?php

namespace Scry\DatabaseManager\Inspectors;

class MysqlInspector extends AbstractInspector
{
    public function getTables(): array
    {
        $dbName = $this->connection->getDatabaseName();

        $query = "
            SELECT 
                TABLE_NAME AS name,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
                TABLE_ROWS AS estimated_rows
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME ASC;
        ";

        return array_map(function ($row) use ($dbName) {
            return [
                'name' => $row->name,
                'size' => ($row->size_mb ?? 0) . ' MB',
                'rows' => (int) $row->estimated_rows,
            ];
        }, $this->connection->select($query, [$dbName]));
    }

    public function getTableSchema(string $table): array
    {
        $dbName = $this->connection->getDatabaseName();

        // Columns
        $columnsQuery = "
            SELECT 
                COLUMN_NAME AS name,
                COLUMN_TYPE AS type,
                IS_NULLABLE AS nullable,
                COLUMN_DEFAULT AS default_value,
                EXTRA AS extra
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION ASC;
        ";
        $columns = $this->connection->select($columnsQuery, [$dbName, $table]);

        // Indexes
        $indexesQuery = "
            SELECT 
                INDEX_NAME AS index_name,
                COLUMN_NAME AS column_name,
                NON_UNIQUE AS non_unique,
                INDEX_TYPE AS index_type
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?;
        ";
        $indexes = $this->connection->select($indexesQuery, [$dbName, $table]);

        return [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
        ];
    }
}
