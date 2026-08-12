<?php

namespace Scry\Services;

class ExportService
{
    /**
     * Export dataset to CSV string format.
     *
     * @param array $rows Array of associative arrays or stdClass objects
     * @return string
     */
    public function exportCsv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        $firstRow = (array) $rows[0];
        $headers = array_keys($firstRow);
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            $rowArray = (array) $row;
            $formatted = array_map(function ($value) {
                if (is_array($value) || is_object($value)) {
                    return json_encode($value);
                }
                return $value;
            }, $rowArray);

            fputcsv($output, $formatted);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export dataset to SQL INSERT statement dump string.
     *
     * @param string $table
     * @param array $rows
     * @param string $driver
     * @return string
     */
    public function exportSql(string $table, array $rows, string $driver = 'mysql'): string
    {
        if (empty($rows)) {
            return "-- No records to export for table {$table}\n";
        }

        $quoteChar = $driver === 'pgsql' ? '"' : '`';
        $firstRow = (array) $rows[0];
        $columns = array_keys($firstRow);

        $escapedColumns = array_map(fn($col) => "{$quoteChar}{$col}{$quoteChar}", $columns);
        $columnsSql = implode(', ', $escapedColumns);

        $sql = "-- Scry Database Dump for table {$quoteChar}{$table}{$quoteChar}\n";
        $sql .= "-- Driver: {$driver}\n\n";

        foreach ($rows as $row) {
            $rowArray = (array) $row;
            $values = [];

            foreach ($columns as $col) {
                $val = $rowArray[$col] ?? null;

                if ($val === null) {
                    $values[] = 'NULL';
                } else if (is_bool($val)) {
                    $values[] = $val ? 'TRUE' : 'FALSE';
                } else if (is_numeric($val)) {
                    $values[] = $val;
                } else if (is_array($val) || is_object($val)) {
                    $jsonStr = addslashes(json_encode($val));
                    $values[] = "'{$jsonStr}'";
                } else {
                    $escaped = addslashes((string) $val);
                    $values[] = "'{$escaped}'";
                }
            }

            $valuesSql = implode(', ', $values);
            $sql .= "INSERT INTO {$quoteChar}{$table}{$quoteChar} ({$columnsSql}) VALUES ({$valuesSql});\n";
        }

        return $sql;
    }
}
