<?php

namespace Scry\Inspectors;

use Illuminate\Support\Facades\DB;

class PostgresInspector extends AbstractInspector
{
    /**
     * Get all base tables in the PostgreSQL database schema with relation sizes and row count estimates.
     */
    public function getTables(): array
    {
        $query = "
            SELECT 
                t.table_name AS name,
                pg_size_pretty(pg_total_relation_size(quote_ident(t.table_schema) || '.' || quote_ident(t.table_name))) AS size,
                pg_total_relation_size(quote_ident(t.table_schema) || '.' || quote_ident(t.table_name)) AS size_bytes,
                COALESCE(c.reltuples::bigint, 0) AS estimated_rows
            FROM information_schema.tables t
            LEFT JOIN pg_catalog.pg_namespace n ON n.nspname = t.table_schema
            LEFT JOIN pg_catalog.pg_class c ON c.relname = t.table_name AND c.relnamespace = n.oid
            WHERE t.table_schema NOT IN ('pg_catalog', 'information_schema')
              AND t.table_type = 'BASE TABLE'
            ORDER BY t.table_name ASC;
        ";

        $rows = $this->connection->select($query);

        return array_map(function ($row) {
            return [
                'name' => $row->name,
                'size' => $row->size ?? '0 B',
                'size_bytes' => (int) $row->size_bytes,
                'rows' => max(0, (int) $row->estimated_rows),
            ];
        }, $rows);
    }

    /**
     * Get column schema definitions for a specific PostgreSQL table.
     * Supports jsonb, uuid, timestamptz, array types, default values, primary keys, and foreign keys.
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
                c.column_name AS name,
                c.data_type AS type,
                c.udt_name AS format,
                c.is_nullable AS nullable,
                c.column_default AS default_value,
                c.character_maximum_length AS max_length
            FROM information_schema.columns c
            WHERE c.table_schema NOT IN ('pg_catalog', 'information_schema')
              AND c.table_name = ?
            ORDER BY c.ordinal_position ASC;
        ";
        $columnsRaw = $this->connection->select($columnsQuery, [$table]);

        $columns = array_map(function ($col) use ($primaryColumns, $fkColumns) {
            $formattedType = match ($col->format) {
                'jsonb' => 'jsonb',
                'uuid' => 'uuid',
                'timestamptz' => 'timestamp with time zone',
                'timestamp' => 'timestamp without time zone',
                'bool' => 'boolean',
                'int8' => 'bigint',
                'int4' => 'integer',
                'int2' => 'smallint',
                'text' => 'text',
                'varchar' => $col->max_length ? "character varying({$col->max_length})" : "character varying",
                default => str_starts_with($col->format, '_') ? substr($col->format, 1) . '[]' : $col->format,
            };

            return [
                'name' => $col->name,
                'data_type' => $col->format ?? $col->type,
                'full_type' => $formattedType,
                'nullable' => $col->nullable === 'YES',
                'default_value' => $col->default_value,
                'is_primary' => in_array($col->name, $primaryColumns),
                'is_foreign_key' => in_array($col->name, $fkColumns),
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
     * Get index names, columns involved, uniqueness, and primary key status for a PostgreSQL table.
     */
    public function getTableIndexes(string $table): array
    {
        $indexesQuery = "
            SELECT
                i.relname AS index_name,
                a.attname AS column_name,
                ix.indisunique AS is_unique,
                ix.indisprimary AS is_primary
            FROM pg_catalog.pg_class t
            JOIN pg_catalog.pg_index ix ON t.oid = ix.indrelid
            JOIN pg_catalog.pg_class i ON i.oid = ix.indexrelid
            JOIN pg_catalog.pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
            JOIN pg_catalog.pg_namespace n ON n.oid = t.relnamespace
            WHERE n.nspname NOT IN ('pg_catalog', 'information_schema')
              AND t.relname = ?
            ORDER BY i.relname ASC;
        ";
        $indexesRaw = $this->connection->select($indexesQuery, [$table]);

        return array_map(function ($idx) {
            return [
                'index_name' => $idx->index_name,
                'column_name' => $idx->column_name,
                'is_unique' => (bool) $idx->is_unique,
                'is_primary' => (bool) $idx->is_primary,
            ];
        }, $indexesRaw);
    }

    /**
     * Get foreign key relationships for a PostgreSQL table from pg_constraint.
     */
    public function getTableForeignKeys(string $table): array
    {
        $foreignKeysQuery = "
            SELECT
                con.conname AS constraint_name,
                att2.attname AS column_name,
                cl.relname AS foreign_table_name,
                att.attname AS foreign_column_name
            FROM pg_catalog.pg_constraint con
            JOIN pg_catalog.pg_class tbl ON tbl.oid = con.conrelid
            JOIN pg_catalog.pg_class cl ON cl.oid = con.confrelid
            JOIN pg_catalog.pg_attribute att ON att.attrelid = con.confrelid AND att.attnum = ANY(con.confkey)
            JOIN pg_catalog.pg_attribute att2 ON att2.attrelid = con.conrelid AND att2.attnum = ANY(con.conkey)
            WHERE con.contype = 'f'
              AND tbl.relname = ?;
        ";
        $foreignKeysRaw = $this->connection->select($foreignKeysQuery, [$table]);

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
     * Get PostgreSQL server stats (storage size, connection metrics from pg_stat_activity).
     */
    public function getServerStats(): array
    {
        $dbName = $this->connection->getDatabaseName();

        $sizeRaw = $this->connection->select("
            SELECT pg_size_pretty(pg_database_size(?)) AS size, pg_database_size(?) AS size_bytes;
        ", [$dbName, $dbName]);

        $dbSize = $sizeRaw[0]->size ?? '0 B';
        $dbSizeBytes = (int) ($sizeRaw[0]->size_bytes ?? 0);

        $connectionsRaw = $this->connection->select("
            SELECT 
                COUNT(*) AS total_connections,
                COUNT(*) FILTER (WHERE state = 'active') AS active_connections,
                COUNT(*) FILTER (WHERE state = 'idle') AS idle_connections
            FROM pg_stat_activity
            WHERE datname = ?;
        ", [$dbName]);

        $totalConnections = (int) ($connectionsRaw[0]->total_connections ?? 0);
        $activeConnections = (int) ($connectionsRaw[0]->active_connections ?? 0);
        $idleConnections = (int) ($connectionsRaw[0]->idle_connections ?? 0);

        $versionRaw = $this->connection->select("SELECT version();");
        $version = $versionRaw[0]->version ?? 'PostgreSQL';

        return [
            'database_name' => $dbName,
            'driver' => 'pgsql',
            'version' => $version,
            'storage_size' => $dbSize,
            'storage_size_bytes' => $dbSizeBytes,
            'total_connections' => $totalConnections,
            'active_connections' => $activeConnections,
            'idle_connections' => $idleConnections,
        ];
    }
}
