<?php

namespace Scry\Services;

class ExportService
{
    /**
     * Export dataset to CSV string format.
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
     * Stream dataset to CSV directly to output handle.
     */
    public function streamCsv(array $rows, $outputHandle = null): void
    {
        if (empty($rows)) {
            return;
        }

        $handle = $outputHandle ?? fopen('php://output', 'w');

        $firstRow = (array) $rows[0];
        $headers = array_keys($firstRow);
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $rowArray = (array) $row;
            $formatted = array_map(function ($value) {
                if (is_array($value) || is_object($value)) {
                    return json_encode($value);
                }
                return $value;
            }, $rowArray);

            fputcsv($handle, $formatted);
        }
    }

    /**
     * Export dataset to SQL INSERT statement dump string.
     */
    public function exportSql(string $table, array $rows, string $driver = 'mysql', array $options = []): string
    {
        $includeDrop = !empty($options['drop_table']);
        $openQuote = match ($driver) {
            'sqlsrv' => '[',
            'mysql', 'mariadb' => '`',
            default => '"',
        };
        $closeQuote = match ($driver) {
            'sqlsrv' => ']',
            'mysql', 'mariadb' => '`',
            default => '"',
        };

        $sql = "-- Scry Database Dump for table {$openQuote}{$table}{$closeQuote}\n";
        $sql .= "-- Driver: {$driver}\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        if ($includeDrop) {
            $sql .= "DROP TABLE IF EXISTS {$openQuote}{$table}{$closeQuote};\n\n";
        }

        if (empty($rows)) {
            $sql .= "-- No records to export for table {$table}\n";
            return $sql;
        }

        $firstRow = (array) $rows[0];
        $columns = array_keys($firstRow);

        $escapedColumns = array_map(fn($col) => "{$openQuote}{$col}{$closeQuote}", $columns);
        $columnsSql = implode(', ', $escapedColumns);

        foreach ($rows as $row) {
            $rowArray = (array) $row;
            $values = [];

            foreach ($columns as $col) {
                $val = $rowArray[$col] ?? null;

                if ($val === null) {
                    $values[] = 'NULL';
                } elseif (is_bool($val)) {
                    $values[] = $val ? 'TRUE' : 'FALSE';
                } elseif (is_numeric($val)) {
                    $values[] = $val;
                } elseif (is_array($val) || is_object($val)) {
                    $jsonStr = addslashes(json_encode($val));
                    $values[] = "'{$jsonStr}'";
                } else {
                    $escaped = addslashes((string) $val);
                    $values[] = "'{$escaped}'";
                }
            }

            $valuesSql = implode(', ', $values);
            $sql .= "INSERT INTO {$openQuote}{$table}{$closeQuote} ({$columnsSql}) VALUES ({$valuesSql});\n";
        }

        return $sql;
    }

    /**
     * Export dataset to XML format.
     */
    public function exportXml(string $table, array $rows): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<table name=\"" . htmlspecialchars($table) . "\">\n";

        foreach ($rows as $row) {
            $xml .= "  <record>\n";
            foreach ((array)$row as $key => $val) {
                $formattedVal = is_array($val) || is_object($val) ? json_encode($val) : (string)$val;
                $xml .= "    <" . htmlspecialchars($key) . ">" . htmlspecialchars($formattedVal) . "</" . htmlspecialchars($key) . ">\n";
            }
            $xml .= "  </record>\n";
        }

        $xml .= "</table>\n";
        return $xml;
    }

    /**
     * Export dataset to JSON format.
     */
    public function exportJson(string $table, array $rows): string
    {
        return json_encode([
            'table' => $table,
            'exported_at' => date('Y-m-d H:i:s'),
            'count' => count($rows),
            'data' => array_map(fn($r) => (array) $r, $rows),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
