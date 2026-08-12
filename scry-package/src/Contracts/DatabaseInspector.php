<?php

namespace Scry\Contracts;

interface DatabaseInspector
{
    /**
     * Get a list of all tables in the database with basic metadata.
     *
     * @return array
     */
    public function getTables(): array;

    /**
     * Get column details for a specific table.
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
     * Insert a new row into the table.
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insertRow(string $table, array $data): bool;

    /**
     * Update an existing row in the table using primary key criteria.
     *
     * @param string $table
     * @param array $primaryKey Key-value pairs identifying the target row
     * @param array $data Key-value pairs of columns to update
     * @return bool
     */
    public function updateRow(string $table, array $primaryKey, array $data): bool;

    /**
     * Delete a row from the table using primary key criteria.
     *
     * @param string $table
     * @param array $primaryKey Key-value pairs identifying the target row
     * @return bool
     */
    public function deleteRow(string $table, array $primaryKey): bool;

    /**
     * Get server-level performance metrics, storage usage, and active connection statistics.
     *
     * @return array
     */
    public function getServerStats(): array;

    /**
     * Execute a raw SQL query against the connection.
     *
     * @param string $query
     * @return array
     */
    public function executeQuery(string $query): array;
}
