<?php

namespace Scry\Support;

class SqlSafety
{
    /**
     * Wrap a database identifier (table/column/index/constraint name) in the
     * correct per-driver quote characters, escaping any embedded quote char
     * so the identifier can't break out into surrounding SQL.
     */
    public static function quoteIdentifier(string $driver, string $name): string
    {
        return match ($driver) {
            'sqlsrv' => '[' . str_replace(']', ']]', $name) . ']',
            'mysql', 'mariadb' => '`' . str_replace('`', '``', $name) . '`',
            default => '"' . str_replace('"', '""', $name) . '"',
        };
    }

    /**
     * Escape a value for use as a single-quoted SQL string literal (ANSI SQL
     * string-literal escaping: double up embedded single quotes).
     */
    public static function quoteLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
