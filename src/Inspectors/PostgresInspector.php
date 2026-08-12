<?php

namespace Scry\DatabaseManager\Inspectors;

class PostgresInspector extends AbstractInspector
{
    public function getTables(): array
    {
        $query = "
            SELECT 
                t.table_name AS name,
                pg_size_pretty(pg_total_relation_size('\"' || t.table_schema || '\".\"' || t.table_name || '\"')) AS size,
                COALESCE(c.reltuples::bigint, 0) AS estimated_rows
            FROM information_schema.tables t
            LEFT JOIN pg_class c ON c.relname = t.table_name
            WHERE t.table_schema = 'public'
              AND t.table_type = 'BASE TABLE'
            ORDER BY t.table_name ASC;
        ";

        return array_map(function ($row) {
            return [
                'name' => $row->name,
                'size' => $row->size ?? '0 B',
                'rows' => (int) $row->estimated_rows,
            ];
        }, $this->connection->select($query));
    }

    public function getTableSchema(string $table): array
    {
        // Columns
        $columnsQuery = "
            SELECT 
                column_name AS name,
                data_type AS type,
                is_nullable AS nullable,
                column_default AS default_value,
                character_maximum_length AS max_length
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = ?
            ORDER BY ordinal_position ASC;
        ";
        $columns = $this->connection->select($columnsQuery, [$table]);

        // Indexes
        $indexesQuery = "
            SELECT
                i.relname AS index_name,
                a.attname AS column_name,
                ix.indisunique AS is_unique,
                ix.indisprimary AS is_primary
            FROM pg_class t
            JOIN pg_index ix ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
            JOIN pg_namespace n ON n.oid = t.relnamespace
            WHERE n.nspname = 'public' AND t.relname = ?;
        ";
        $indexes = $this->connection->select($indexesQuery, [$table]);

        return [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
        ];
    }
}
