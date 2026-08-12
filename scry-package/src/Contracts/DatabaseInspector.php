<?php

namespace Scry\Contracts;

interface DatabaseInspector
{
    /**
     * Get a list of all tables in the database with basic stats.
     *
     * @return array
     */
    public function getTables(): array;

    /**
     * Get detailed schema structure of a specific table (columns, indexes, primary keys, foreign keys).
     *
     * @param string $table
     * @return array
     */
    public function getTableSchema(string $table): array;

    /**
     * Get paginated row data from a specific table.
     *
     * @param string $table
     * @param int $page
     * @param int $perPage
     * @param string|null $sortBy
     * @string $sortDir
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
     * Execute a query against the active database driver.
     *
     * @param string $query
     * @return array
     */
    public function executeQuery(string $query): array;
}
