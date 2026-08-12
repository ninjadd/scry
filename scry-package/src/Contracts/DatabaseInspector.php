<?php

namespace Scry\Contracts;

interface DatabaseInspector
{
    /**
     * Get a list of all tables in the database with basic metadata (name, size, row count).
     *
     * @return array
     */
    public function getTables(): array;

    /**
     * Get column details for a specific table (name, data_type, nullable, default_value, is_primary, is_foreign_key).
     *
     * @param string $table
     * @return array
     */
    public function getTableSchema(string $table): array;

    /**
     * Get index names, columns involved, and uniqueness for a specific table.
     *
     * @param string $table
     * @return array
     */
    public function getTableIndexes(string $table): array;

    /**
     * Get foreign key relationships for a specific table.
     *
     * @param string $table
     * @return array
     */
    public function getTableForeignKeys(string $table): array;

    /**
     * Get paginated row data from a specific table along with total count.
     *
     * @param string $table
     * @param int $page
     * @param int $perPage
     * @param string|null $sortBy
     * @param string $sortDir
     * @return array
     */
    public function getPaginatedRows(
        string $table,
        int $page = 1,
        int $perPage = 25,
        ?string $sortBy = null,
        string $sortDir = 'asc'
    ): array;

    /**
     * Execute a raw SQL query against the connection.
     *
     * @param string $query
     * @return array
     */
    public function executeQuery(string $query): array;
}
