<?php

namespace Scry\Inspectors;

class SqliteInspector extends AbstractInspector
{
    /**
     * Get all base tables in SQLite database.
     */
    public function getTables(): array
    {
        $rows = $this->connection->select("
            SELECT name 
            FROM sqlite_master 
            WHERE type='table' AND name NOT LIKE 'sqlite_%' 
            ORDER BY name ASC;
        ");

        $dbPath = $this->connection->getDatabaseName();
        $totalSizeBytes = (file_exists($dbPath) && is_file($dbPath)) ? filesize($dbPath) : 0;

        return array_map(function ($row) use ($totalSizeBytes) {
            $tableName = $row->name;
            $rowCount = 0;
            try {
                $rowCount = $this->connection->table($tableName)->count();
            } catch (\Throwable $e) {
                // fallback
            }

            $sizeFormatted = match (true) {
                $totalSizeBytes >= 1048576 => number_format($totalSizeBytes / 1048576, 2) . ' MB',
                $totalSizeBytes >= 1024 => number_format($totalSizeBytes / 1024, 2) . ' kB',
                default => $totalSizeBytes . ' B',
            };

            return [
                'name' => $tableName,
                'size' => $sizeFormatted,
                'size_bytes' => $totalSizeBytes,
                'rows' => $rowCount,
            ];
        }, $rows);
    }

    /**
     * Get column schema for SQLite table.
     */
    public function getTableSchema(string $table): array
    {
        $indexes = $this->getTableIndexes($table);
        $foreignKeys = $this->getTableForeignKeys($table);

        $primaryColumns = array_column(
            array_filter($indexes, fn($idx) => !empty($idx['is_primary'])),
            'column_name'
        );

        $columnsRaw = $this->connection->select("PRAGMA table_info(" . $this->wrapIdentifier($table) . ");");

        foreach ($columnsRaw as $col) {
            if (!empty($col->pk) && !in_array($col->name, $primaryColumns)) {
                $primaryColumns[] = $col->name;
            }
        }

        $fkColumns = array_column($foreignKeys, 'column_name');

        $columns = array_map(function ($col) use ($primaryColumns, $fkColumns) {
            $isPk = in_array($col->name, $primaryColumns);
            return [
                'name' => $col->name,
                'data_type' => strtolower(preg_replace('/\s*\(.*\)/', '', $col->type ?? 'text')),
                'full_type' => strtoupper($col->type ?? 'TEXT'),
                'nullable' => (int)($col->notnull ?? 0) === 0,
                'default_value' => $col->dflt_value,
                'is_primary' => $isPk,
                'is_foreign_key' => in_array($col->name, $fkColumns),
                'extra' => $isPk ? 'PRIMARY KEY' : '',
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
     * Get table indexes for SQLite table.
     */
    public function getTableIndexes(string $table): array
    {
        $indexesRaw = $this->connection->select("PRAGMA index_list(" . $this->wrapIdentifier($table) . ");");
        $indexes = [];

        foreach ($indexesRaw as $idx) {
            $indexName = $idx->name;
            $isUnique = (int)($idx->unique ?? 0) === 1;
            $isPrimary = ($idx->origin ?? '') === 'pk';

            $infoRaw = $this->connection->select("PRAGMA index_info(" . $this->wrapIdentifier($indexName) . ");");
            foreach ($infoRaw as $info) {
                $indexes[] = [
                    'index_name' => $indexName,
                    'column_name' => $info->name,
                    'is_unique' => $isUnique,
                    'is_primary' => $isPrimary,
                ];
            }
        }

        return $indexes;
    }

    /**
     * Get foreign keys for SQLite table.
     */
    public function getTableForeignKeys(string $table): array
    {
        $fkRaw = $this->connection->select("PRAGMA foreign_key_list(" . $this->wrapIdentifier($table) . ");");

        return array_map(function ($fk) {
            return [
                'constraint_name' => 'fk_' . ($fk->id ?? 0),
                'column_name' => $fk->from,
                'foreign_table_name' => $fk->table,
                'foreign_column_name' => $fk->to,
            ];
        }, $fkRaw);
    }

    /**
     * Get SQLite server & storage stats.
     */
    public function getServerStats(): array
    {
        $dbPath = $this->connection->getDatabaseName();
        $sizeBytes = (file_exists($dbPath) && is_file($dbPath)) ? filesize($dbPath) : 0;
        $sizeFormatted = match (true) {
            $sizeBytes >= 1048576 => number_format($sizeBytes / 1048576, 2) . ' MB',
            $sizeBytes >= 1024 => number_format($sizeBytes / 1024, 2) . ' kB',
            default => $sizeBytes . ' B',
        };

        $versionRaw = $this->connection->select("SELECT sqlite_version() AS version;");
        $version = $versionRaw[0]->version ?? 'SQLite';

        return [
            'database_name' => basename($dbPath),
            'driver' => 'sqlite',
            'version' => $version,
            'storage_size' => $sizeFormatted,
            'storage_size_bytes' => $sizeBytes,
            'total_connections' => 1,
            'active_connections' => 1,
            'idle_connections' => 0,
        ];
    }

    public function getDatabases(): array
    {
        $rows = $this->connection->select("PRAGMA database_list;");
        return array_map(fn($row) => $row->name ?? 'main', $rows);
    }

    public function copyTable(string $sourceTable, string $targetTable, bool $copyData = true): bool
    {
        $source = $this->wrapIdentifier($sourceTable);
        $target = $this->wrapIdentifier($targetTable);

        $this->connection->statement("CREATE TABLE {$target} AS SELECT * FROM {$source} WHERE 1=0");
        if ($copyData) {
            $this->connection->statement("INSERT INTO {$target} SELECT * FROM {$source}");
        }
        return true;
    }

    public function getViews(): array
    {
        $rows = $this->connection->select("
            SELECT name 
            FROM sqlite_master 
            WHERE type='view' 
            ORDER BY name ASC;
        ");

        return array_map(fn($r) => ['name' => $r->name], $rows);
    }

    public function getTriggers(): array
    {
        $rows = $this->connection->select("
            SELECT name, tbl_name AS table_name 
            FROM sqlite_master 
            WHERE type='trigger' 
            ORDER BY name ASC;
        ");

        return array_map(fn($r) => [
            'name' => $r->name,
            'event' => 'TRIGGER',
            'table_name' => $r->table_name,
            'timing' => 'AFTER',
        ], $rows);
    }

    public function optimizeTable(string $table): bool
    {
        return $this->connection->statement("VACUUM;");
    }

    protected function wrapIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
