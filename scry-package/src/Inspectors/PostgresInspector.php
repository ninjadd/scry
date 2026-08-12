<?php

namespace Scry\Inspectors;

class PostgresInspector extends AbstractInspector
{
    /**
     * Get all base tables in the public schema with relation size and row count estimates.
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
     * Get detailed column details (name, data_type, nullable, default_value, is_primary, is_foreign_key).
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
            return [
                'name' => $col->name,
                'data_type' => $col->format ?? $col->type,
                'full_type' => $col->max_length ? "{$col->type}({$col->max_length})" : $col->type,
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
     * Get index information for a specific table (index_name, column_name, is_unique, is_primary).
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
     * Get foreign key relationship definitions for a specific table.
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
            JOIN pg_catalog.pg_attribute att2 ON att2.attrelid = con.conrelid AND att2.attnum = ANY(con.confkey)
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
}
