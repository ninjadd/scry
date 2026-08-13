<?php

namespace Scry\Inspectors;

class SqlsrvInspector extends AbstractInspector
{
    /**
     * Get all base tables in SQL Server database with storage sizes and estimated rows.
     */
    public function getTables(): array
    {
        $query = "
            SELECT 
                t.name AS name,
                p.rows AS estimated_rows,
                SUM(a.total_pages) * 8 * 1024 AS size_bytes
            FROM sys.tables t
            INNER JOIN sys.indexes i ON t.object_id = i.object_id
            INNER JOIN sys.partitions p ON i.object_id = p.object_id AND i.index_id = p.index_id
            INNER JOIN sys.allocation_units a ON p.partition_id = a.container_id
            WHERE t.is_ms_shipped = 0 AND i.index_id < 2
            GROUP BY t.name, p.rows
            ORDER BY t.name ASC;
        ";

        try {
            $rows = $this->connection->select($query);
        } catch (\Throwable $e) {
            // Fallback query if sys views restricted
            $rows = $this->connection->select("
                SELECT TABLE_NAME AS name, 0 AS estimated_rows, 0 AS size_bytes 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME ASC;
            ");
        }

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
                'rows' => max(0, (int) ($row->estimated_rows ?? 0)),
            ];
        }, $rows);
    }

    /**
     * Get column schema definitions for SQL Server table.
     */
    public function getTableSchema(string $table): array
    {
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
                CASE 
                    WHEN CHARACTER_MAXIMUM_LENGTH IS NOT NULL 
                    THEN DATA_TYPE + '(' + CAST(CHARACTER_MAXIMUM_LENGTH AS VARCHAR) + ')'
                    ELSE DATA_TYPE 
                END AS full_type,
                IS_NULLABLE AS nullable,
                COLUMN_DEFAULT AS default_value,
                COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') AS is_identity
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION ASC;
        ";
        $columnsRaw = $this->connection->select($columnsQuery, [$table]);

        $columns = array_map(function ($col) use ($primaryColumns, $fkColumns) {
            $isPk = in_array($col->name, $primaryColumns);
            return [
                'name' => $col->name,
                'data_type' => strtolower($col->data_type),
                'full_type' => strtoupper($col->full_type),
                'nullable' => $col->nullable === 'YES',
                'default_value' => $col->default_value,
                'is_primary' => $isPk,
                'is_foreign_key' => in_array($col->name, $fkColumns),
                'extra' => (int)($col->is_identity ?? 0) === 1 ? 'IDENTITY' : '',
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
     * Get index names, columns involved, uniqueness for SQL Server table.
     */
    public function getTableIndexes(string $table): array
    {
        $indexesQuery = "
            SELECT 
                i.name AS index_name,
                c.name AS column_name,
                i.is_unique AS is_unique,
                i.is_primary_key AS is_primary
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            INNER JOIN sys.tables t ON i.object_id = t.object_id
            WHERE t.name = ?
            ORDER BY i.name ASC, ic.key_ordinal ASC;
        ";

        try {
            $indexesRaw = $this->connection->select($indexesQuery, [$table]);
            return array_map(function ($idx) {
                return [
                    'index_name' => $idx->index_name,
                    'column_name' => $idx->column_name,
                    'is_unique' => (bool) $idx->is_unique,
                    'is_primary' => (bool) $idx->is_primary,
                ];
            }, $indexesRaw);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get foreign key relationships for SQL Server table.
     */
    public function getTableForeignKeys(string $table): array
    {
        $fkQuery = "
            SELECT 
                fk.name AS constraint_name,
                tp.name AS column_name,
                tr.name AS foreign_table_name,
                cp.name AS foreign_column_name
            FROM sys.foreign_keys fk
            INNER JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
            INNER JOIN sys.tables t ON fk.parent_object_id = t.object_id
            INNER JOIN sys.columns tp ON fkc.parent_object_id = tp.object_id AND fkc.parent_column_id = tp.column_id
            INNER JOIN sys.tables tr ON fk.referenced_object_id = tr.object_id
            INNER JOIN sys.columns cp ON fkc.referenced_object_id = cp.object_id AND fkc.referenced_column_id = cp.column_id
            WHERE t.name = ?
            ORDER BY fk.name ASC;
        ";

        try {
            $fkRaw = $this->connection->select($fkQuery, [$table]);
            return array_map(function ($fk) {
                return [
                    'constraint_name' => $fk->constraint_name,
                    'column_name' => $fk->column_name,
                    'foreign_table_name' => $fk->foreign_table_name,
                    'foreign_column_name' => $fk->foreign_column_name,
                ];
            }, $fkRaw);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get SQL Server stats.
     */
    public function getServerStats(): array
    {
        $dbName = $this->connection->getDatabaseName();

        $versionRaw = $this->connection->select("SELECT @@VERSION AS version;");
        $version = $versionRaw[0]->version ?? 'SQL Server';

        $totalConnections = 0;
        try {
            $connRaw = $this->connection->select("SELECT COUNT(*) AS total FROM sys.dm_exec_sessions WHERE is_user_process = 1;");
            $totalConnections = (int)($connRaw[0]->total ?? 0);
        } catch (\Throwable $e) {
            // Ignore
        }

        return [
            'database_name' => $dbName,
            'driver' => 'sqlsrv',
            'version' => $version,
            'storage_size' => 'N/A',
            'storage_size_bytes' => 0,
            'total_connections' => $totalConnections,
            'active_connections' => $totalConnections,
            'idle_connections' => 0,
        ];
    }

    public function getDatabases(): array
    {
        try {
            $rows = $this->connection->select("SELECT name FROM sys.databases WHERE database_id > 4 ORDER BY name ASC;");
            return array_map(fn($r) => $r->name, $rows);
        } catch (\Throwable $e) {
            return [$this->connection->getDatabaseName()];
        }
    }

    public function copyTable(string $sourceTable, string $targetTable, bool $copyData = true): bool
    {
        $source = $this->wrapIdentifier($sourceTable);
        $target = $this->wrapIdentifier($targetTable);

        if ($copyData) {
            $this->connection->statement("SELECT * INTO {$target} FROM {$source}");
        } else {
            $this->connection->statement("SELECT TOP 0 * INTO {$target} FROM {$source}");
        }
        return true;
    }

    public function getViews(): array
    {
        $rows = $this->connection->select("SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.VIEWS ORDER BY TABLE_NAME ASC;");
        return array_map(fn($r) => ['name' => $r->name], $rows);
    }

    public function getTriggers(): array
    {
        try {
            $rows = $this->connection->select("
                SELECT name, OBJECT_NAME(parent_id) AS table_name 
                FROM sys.triggers 
                WHERE is_ms_shipped = 0 
                ORDER BY name ASC;
            ");
            return array_map(fn($r) => [
                'name' => $r->name,
                'event' => 'TRIGGER',
                'table_name' => $r->table_name ?? '',
                'timing' => 'AFTER',
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getProcedures(): array
    {
        $rows = $this->connection->select("
            SELECT ROUTINE_NAME AS name, ROUTINE_TYPE AS type, DATA_TYPE AS return_type 
            FROM INFORMATION_SCHEMA.ROUTINES 
            ORDER BY ROUTINE_NAME ASC;
        ");

        return array_map(fn($r) => [
            'name' => $r->name,
            'type' => $r->type,
            'return_type' => $r->return_type ?? 'VOID',
        ], $rows);
    }

    protected function wrapIdentifier(string $name): string
    {
        return '[' . str_replace(']', ']]', $name) . ']';
    }
}
