<?php

namespace Scry\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class ExportService
{
    /**
     * Export dataset to CSV string format.
     *
     * @param array $rows
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

    /**
     * Export dataset to XML format.
     *
     * @param string $table
     * @param array $rows
     * @return string
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
     * Export dataset to PDF binary string via dompdf.
     *
     * @param string $table
     * @param array $rows
     * @return string
     */
    public function exportPdf(string $table, array $rows): string
    {
        if (empty($rows)) {
            $html = "<h3>No records to export for {$table}</h3>";
        } else {
            $firstRow = (array)$rows[0];
            $cols = array_keys($firstRow);

            $html = "<!DOCTYPE html><html><head><style>
                body { font-family: sans-serif; font-size: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
                th { background-color: #384950; color: white; }
                h2 { color: #b91c5c; }
            </style></head><body>";
            $html .= "<h2>Scry Table Export: " . htmlspecialchars($table) . "</h2>";
            $html .= "<table><thead><tr>";
            foreach ($cols as $c) {
                $html .= "<th>" . htmlspecialchars($c) . "</th>";
            }
            $html .= "</tr></thead><tbody>";
            foreach ($rows as $r) {
                $html .= "<tr>";
                foreach ($cols as $c) {
                    $v = ((array)$r)[$c] ?? '';
                    $formatted = is_array($v) || is_object($v) ? json_encode($v) : (string)$v;
                    $html .= "<td>" . htmlspecialchars($formatted) . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</tbody></table></body></html>";
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Export dataset to Word HTML stream (.doc).
     *
     * @param string $table
     * @param array $rows
     * @return string
     */
    public function exportWord(string $table, array $rows): string
    {
        if (empty($rows)) {
            return "<html><body><h3>No records to export for {$table}</h3></body></html>";
        }

        $firstRow = (array)$rows[0];
        $cols = array_keys($firstRow);

        $html = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
        $html .= "<head><title>Scry Data Export - {$table}</title><style>
            body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #b91c5c; padding: 6px; text-align: left; }
            th { background-color: #384950; color: #ffffff; font-weight: bold; }
            h2 { color: #b91c5c; }
        </style></head><body>";

        $html .= "<h2>Scry Database Export: " . htmlspecialchars($table) . "</h2>";
        $html .= "<table><thead><tr>";
        foreach ($cols as $c) {
            $html .= "<th>" . htmlspecialchars($c) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($rows as $r) {
            $html .= "<tr>";
            foreach ($cols as $c) {
                $v = ((array)$r)[$c] ?? '';
                $formatted = is_array($v) || is_object($v) ? json_encode($v) : (string)$v;
                $html .= "<td>" . htmlspecialchars($formatted) . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table></body></html>";
        return $html;
    }

    /**
     * Export dataset to OpenDocument XML stream (.odt).
     *
     * @param string $table
     * @param array $rows
     * @return string
     */
    public function exportOdt(string $table, array $rows): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<office:document-content xmlns:office=\"urn:oasis:names:tc:opendocument:xmlns:office:1.0\" xmlns:table=\"urn:oasis:names:tc:opendocument:xmlns:table:1.0\" xmlns:text=\"urn:oasis:names:tc:opendocument:xmlns:text:1.0\">\n";
        $xml .= "  <office:body>\n";
        $xml .= "    <office:text>\n";
        $xml .= "      <text:h text:outline-level=\"1\">Scry Table Export: " . htmlspecialchars($table) . "</text:h>\n";
        $xml .= "      <table:table table:name=\"" . htmlspecialchars($table) . "\">\n";

        if (!empty($rows)) {
            $firstRow = (array)$rows[0];
            $cols = array_keys($firstRow);

            $xml .= "        <table:table-row>\n";
            foreach ($cols as $c) {
                $xml .= "          <table:table-cell><text:p text:style-name=\"Table_20_Header\">" . htmlspecialchars($c) . "</text:p></table:table-cell>\n";
            }
            $xml .= "        </table:table-row>\n";

            foreach ($rows as $r) {
                $xml .= "        <table:table-row>\n";
                foreach ($cols as $c) {
                    $v = ((array)$r)[$c] ?? '';
                    $formatted = is_array($v) || is_object($v) ? json_encode($v) : (string)$v;
                    $xml .= "          <table:table-cell><text:p>" . htmlspecialchars($formatted) . "</text:p></table:table-cell>\n";
                }
                $xml .= "        </table:table-row>\n";
            }
        }

        $xml .= "      </table:table>\n";
        $xml .= "    </office:text>\n";
        $xml .= "  </office:body>\n";
        $xml .= "</office:document-content>\n";

        return $xml;
    }

    /**
     * Export dataset to LaTeX tabular format.
     *
     * @param string $table
     * @param array $rows
     * @return string
     */
    public function exportLatex(string $table, array $rows): string
    {
        if (empty($rows)) {
            return "% No records for table {$table}\n";
        }

        $firstRow = (array)$rows[0];
        $cols = array_keys($firstRow);
        $colCount = count($cols);

        $latex = "% Scry LaTeX Export for table {$table}\n";
        $latex .= "\\begin{table}[h!]\n\\centering\n";
        $latex .= "\\begin{tabular}{" . str_repeat('l', $colCount) . "}\n\\hline\n";

        $latex .= implode(' & ', array_map(fn($c) => str_replace('_', '\\_', $c), $cols)) . " \\\\\n\\hline\n";

        foreach ($rows as $r) {
            $vals = [];
            foreach ($cols as $c) {
                $v = ((array)$r)[$c] ?? '';
                $valStr = is_array($v) || is_object($v) ? json_encode($v) : (string)$v;
                $vals[] = str_replace(['_', '&', '%'], ['\\_', '\\&', '\\%'], $valStr);
            }
            $latex .= implode(' & ', $vals) . " \\\\\n";
        }

        $latex .= "\\hline\n\\end{tabular}\n\\caption{Data dump of " . str_replace('_', '\\_', $table) . "}\n\\end{table}\n";
        return $latex;
    }
}
