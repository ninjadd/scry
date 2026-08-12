<?php

namespace Scry\Inspectors;

class MysqlInspector extends AbstractInspector
{
    /**
     * Get all base tables in the current MySQL database with relation sizes and row estimates.
     */
    public function getTables(): array
    {
        $dbName = $this->connection->getDatabaseName();

        $query = "
            SELECT 
                TABLE_NAME AS name,
                (DATA_LENGTH + INDEX_LENGTH) AS size_bytes,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
                TABLE_ROWS AS estimated_rows
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME ASC;
        ";

        return array_map(function ($row) {
            $sizeBytes = (int) ($row->size_bytes ?? 0);
            $sizeFormatted = match (true) {
                $sizeBytes >= 1048576 => number_format($sizeBytes / 1048576, 2) . ' MB',
                $sizeBytes >= 1024 => number_format($sizeBytes / 1024, 2) . ' kB',
                default => $sizeBytes . ' B',
            };

            return [
                'name' => $row->name,
                'size' => $sizeFormatted,
                'size_bytes' => $sizeBytes,
                'rows' => max(0, (int) $row->estimated_rows),
            ];
        }, $this->connection->select($query, [$dbName]));
    }

    /**
     * Get column schema definitions for a specific MySQL table.
     * Output structure is normalized to match PostgresInspector.
     */
    public function getTableSchema(string $table): array
    {
        $dbName = $this->connection->getDatabaseName();

        $indexes = $this->getTableIndexes($table);
        $foreignKeys = $this->getTableForeignKeys($table);

        $primaryColumns = array_column(
            array_filter($indexes, fn($idx) => !empty($idx['is_primary'])),
            'column_name'
        );

        $fkColumns = array_column($foreignKeys, 'column_name');

        $columnsQuery = "
            SELECT 
                COLUMN_NAME AS name,
                DATA_TYPE AS data_type,
                COLUMN_TYPE AS full_type,
                IS_NULLABLE AS nullable,
                COLUMN_DEFAULT AS default_value,
                EXTRA AS extra
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION ASC;
        ";
        $columnsRaw = $this->connection->select($columnsQuery, [$dbName, $table]);

        $columns = array_map(function ($col) use ($primaryColumns, $fkColumns) {
            return [
                'name' => $col->name,
                'data_type' => $col->data_type,
                'full_type' => $col->full_type,
                'nullable' => $col->nullable === 'YES',
                'default_value' => $col->default_value,
                'is_primary' => in_array($col->name, $primaryColumns),
                'is_foreign_key' => in_array($col->name, $fkColumns),
                'extra' => $col->extra,
            ];
        }, $columnsRaw);

        return [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }

    /**
     * Get index names, columns involved, uniqueness, and primary key status for a MySQL table.
     */
    public function getTableIndexes(string $table): array
    {
        $dbName = $this->connection->getDatabaseName();

        $indexesQuery = "
            SELECT 
                INDEX_NAME AS index_name,
                COLUMN_NAME AS column_name,
                NON_UNIQUE AS non_unique,
                INDEX_TYPE AS index_type
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY INDEX_NAME ASC, SEQ_IN_INDEX ASC;
        ";
        $indexesRaw = $this->connection->select($indexesQuery, [$dbName, $table]);

        return array_map(function ($idx) {
            return [
                'index_name' => $idx->index_name,
                'column_name' => $idx->column_name,
                'is_unique' => (int) $idx->non_unique === 0,
                'is_primary' => $idx->index_name === 'PRIMARY',
            ];
        }, $indexesRaw);
    }

    /**
     * Get foreign key relationships for a MySQL table from key_column_usage.
     */
    public function getTableForeignKeys(string $table): array
    {
        $dbName = $this->connection->getDatabaseName();

        $foreignKeysQuery = "
            SELECT 
                CONSTRAINT_NAME AS constraint_name,
                COLUMN_NAME AS column_name,
                REFERENCED_TABLE_NAME AS foreign_table_name,
                REFERENCED_COLUMN_NAME AS foreign_column_name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY CONSTRAINT_NAME ASC;
        ";
        $foreignKeysRaw = $this->connection->select($foreignKeysQuery, [$dbName, $table]);

        return array_map(function ($fk) {
            return [
                'constraint_name' => $fk->constraint_name,
                'column_name' => $fk->column_name,
                'foreign_table_name' => $fk->foreign_table_name,
                'foreign_column_name' => $fk->foreign_column_name,
            ];
        }, $foreignKeysRaw);
    }
}
