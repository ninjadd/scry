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

    /**
     * Get MySQL server stats (storage size, active connections).
     */
    public function getServerStats(): array
    {
        $dbName = $this->connection->getDatabaseName();

        $sizeRaw = $this->connection->select("
            SELECT 
                SUM(DATA_LENGTH + INDEX_LENGTH) AS size_bytes
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?;
        ", [$dbName]);

        $sizeBytes = (int) ($sizeRaw[0]->size_bytes ?? 0);
        $sizeFormatted = match (true) {
            $sizeBytes >= 1048576 => number_format($sizeBytes / 1048576, 2) . ' MB',
            $sizeBytes >= 1024 => number_format($sizeBytes / 1024, 2) . ' kB',
            default => $sizeBytes . ' B',
        };

        $connectionsRaw = $this->connection->select("SHOW PROCESSLIST;");
        $totalConnections = count($connectionsRaw);
        $activeConnections = count(array_filter($connectionsRaw, fn($conn) => ($conn->Command ?? '') !== 'Sleep'));
        $idleConnections = $totalConnections - $activeConnections;

        $versionRaw = $this->connection->select("SELECT VERSION() AS version;");
        $version = $versionRaw[0]->version ?? 'MySQL';

        return [
            'database_name' => $dbName,
            'driver' => 'mysql',
            'version' => $version,
            'storage_size' => $sizeFormatted,
            'storage_size_bytes' => $sizeBytes,
            'total_connections' => $totalConnections,
            'active_connections' => $activeConnections,
            'idle_connections' => $idleConnections,
        ];
    }

    public function getDatabases(): array
    {
        $rows = $this->connection->select("SHOW DATABASES;");
        return array_map(fn($row) => $row->Database ?? current((array)$row), $rows);
    }

    public function renameTable(string $table, string $newName): bool
    {
        $sql = "RENAME TABLE " . $this->wrapIdentifier($table) . " TO " . $this->wrapIdentifier($newName);
        return $this->connection->statement($sql);
    }

    public function copyTable(string $sourceTable, string $targetTable, bool $copyData = true): bool
    {
        $source = $this->wrapIdentifier($sourceTable);
        $target = $this->wrapIdentifier($targetTable);

        $this->connection->statement("CREATE TABLE {$target} LIKE {$source}");
        if ($copyData) {
            $this->connection->statement("INSERT INTO {$target} SELECT * FROM {$source}");
        }
        return true;
    }

    public function getViews(): array
    {
        $dbName = $this->connection->getDatabaseName();
        $rows = $this->connection->select("
            SELECT TABLE_NAME AS name 
            FROM information_schema.VIEWS 
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME ASC;
        ", [$dbName]);

        return array_map(fn($r) => ['name' => $r->name], $rows);
    }

    public function getTriggers(): array
    {
        $dbName = $this->connection->getDatabaseName();
        $rows = $this->connection->select("
            SELECT 
                TRIGGER_NAME AS name, 
                EVENT_MANIPULATION AS event, 
                EVENT_OBJECT_TABLE AS table_name, 
                ACTION_TIMING AS timing 
            FROM information_schema.TRIGGERS 
            WHERE TRIGGER_SCHEMA = ?
            ORDER BY TRIGGER_NAME ASC;
        ", [$dbName]);

        return array_map(fn($r) => [
            'name' => $r->name,
            'event' => $r->event,
            'table_name' => $r->table_name,
            'timing' => $r->timing,
        ], $rows);
    }

    public function getProcedures(): array
    {
        $dbName = $this->connection->getDatabaseName();
        $rows = $this->connection->select("
            SELECT 
                ROUTINE_NAME AS name, 
                ROUTINE_TYPE AS type, 
                DATA_TYPE AS return_type 
            FROM information_schema.ROUTINES 
            WHERE ROUTINE_SCHEMA = ?
            ORDER BY ROUTINE_NAME ASC;
        ", [$dbName]);

        return array_map(fn($r) => [
            'name' => $r->name,
            'type' => $r->type,
            'return_type' => $r->return_type,
        ], $rows);
    }

    public function hasUserManagementPrivileges(): bool
    {
        try {
            $grants = $this->connection->select("SHOW GRANTS FOR CURRENT_USER();");
            $grantsStr = strtoupper(json_encode($grants));

            return str_contains($grantsStr, 'ALL PRIVILEGES') || str_contains($grantsStr, 'CREATE USER') || str_contains($grantsStr, 'GRANT OPTION');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getUsers(): array
    {
        if (!$this->hasUserManagementPrivileges()) {
            return [];
        }

        try {
            $users = $this->connection->select("SELECT User AS user, Host AS host FROM mysql.user ORDER BY User ASC;");
            return array_map(fn($u) => ['user' => $u->user, 'host' => $u->host], $users);
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function wrapIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
