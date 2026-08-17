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
     * Get list of databases on the connected server instance.
     *
     * @return array
     */
    public function getDatabases(): array;

    /**
     * Create a new database.
     *
     * @param string $name
     * @param string|null $charset
     * @param string|null $collation
     * @return bool
     */
    public function createDatabase(string $name, ?string $charset = null, ?string $collation = null): bool;

    /**
     * Drop a database.
     *
     * @param string $name
     * @return bool
     */
    public function dropDatabase(string $name): bool;

    /**
     * Drop a table.
     *
     * @param string $table
     * @return bool
     */
    public function dropTable(string $table): bool;

    /**
     * Rename a table.
     *
     * @param string $table
     * @param string $newName
     * @return bool
     */
    public function renameTable(string $table, string $newName): bool;

    /**
     * Copy a table structure and optionally its data.
     *
     * @param string $sourceTable
     * @param string $targetTable
     * @param bool $copyData
     * @return bool
     */
    public function copyTable(string $sourceTable, string $targetTable, bool $copyData = true): bool;

    /**
     * Get database views.
     *
     * @return array
     */
    public function getViews(): array;

    /**
     * Get database triggers.
     *
     * @return array
     */
    public function getTriggers(): array;

    /**
     * Get database stored procedures and functions.
     *
     * @return array
     */
    public function getProcedures(): array;

    /**
     * Check if active connection user holds elevated user management privileges.
     *
     * @return bool
     */
    public function hasUserManagementPrivileges(): bool;

    /**
     * Get database users and their granted privileges (if permitted).
     *
     * @return array
     */
    public function getUsers(): array;

    /**
     * Get relational schema map with tables, primary keys, foreign keys, and relationships.
     *
     * @return array
     */
    public function getSchemaRelationships(): array;

    /**
     * Execute a raw SQL query against the connection.
     *
     * @param string $query
     * @return array
     */
    public function executeQuery(string $query): array;
}
