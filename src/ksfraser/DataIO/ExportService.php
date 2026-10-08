<?php

namespace Ksfraser\DataIO;

class ExportService
{
    private array $config = [];
    private array $headers = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function toCsv(array $data, string $filePath, ?array $headers = null): bool
    {
        if (empty($data)) {
            return false;
        }

        $headers = $headers ?? array_keys(reset($data));
        $handle = fopen($filePath, 'w');

        if (!$handle) {
            return false;
        }

        if ($this->config['bom'] ?? false) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        fputcsv($handle, $headers, $this->config['delimiter'] ?? ',');

        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row[$header] ?? '';
            }
            fputcsv($handle, $csvRow, $this->config['delimiter'] ?? ',');
        }

        fclose($handle);
        return true;
    }

    public function toJson(array $data, string $filePath, bool $pretty = true): bool
    {
        $options = $pretty ? JSON_PRETTY_PRINT : 0;
        $json = json_encode($data, $options);

        return file_put_contents($filePath, $json) !== false;
    }

    public function toExcel(array $data, string $filePath, ?array $headers = null, ?array $styles = null): bool
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return false;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = $headers ?? array_keys(reset($data));
        $this->headers = $headers;

        $row = 1;
        $col = 'A';

        foreach ($headers as $header) {
            $cell = $col . $row;
            $sheet->setCellValue($cell, $header);
            
            if ($styles['header'] ?? null) {
                $sheet->getStyle($cell)->applyFromArray($styles['header']);
            }
            $col++;
        }

        $row = 2;
        foreach ($data as $record) {
            $col = 'A';
            foreach ($headers as $header) {
                $cell = $col . $row;
                $value = $record[$header] ?? '';
                
                if ($value instanceof \DateTime) {
                    $sheet->setCellValue($cell, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($value));
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
                } elseif (is_numeric($value) && !empty($styles['number'])) {
                    $sheet->setCellValue($cell, $value);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($styles['number']);
                } else {
                    $sheet->setCellValue($cell, $value);
                }
                
                $col++;
            }
            $row++;
        }

        foreach (range('A', $col) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        try {
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function toXml(array $data, string $filePath, string $rootElement = 'items', string $itemElement = 'item'): bool
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $rootElement . '/>');

        foreach ($data as $record) {
            $item = $xml->addChild($itemElement);
            
            foreach ($record as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                
                $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $key);
                $item->addChild($safeKey, htmlspecialchars((string) $value));
            }
        }

        return $xml->asXML($filePath) !== false;
    }

    public function toHtml(array $data, ?array $headers = null): string
    {
        $headers = $headers ?? (empty($data) ? [] : array_keys(reset($data)));
        
        $html = '<table class="data-export">' . "\n";
        $html .= '<thead><tr>';
        
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        
        $html .= '</tr></thead>' . "\n";
        $html .= '<tbody>';
        
        foreach ($data as $record) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<td>' . htmlspecialchars($record[$header] ?? '') . '</td>';
            }
            $html .= '</tr>' . "\n";
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }

    public function toPdf(array $data, string $filePath, ?array $headers = null): bool
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return false;
        }

        if (!$this->toExcel($data, $filePath . '.xlsx', $headers)) {
            return false;
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath . '.xlsx');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Dompdf');
        
        try {
            $writer->save($filePath);
            unlink($filePath . '.xlsx');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function streamCsv(array $data, ?array $headers = null): void
    {
        $filename = ($this->config['filename'] ?? 'export') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        if ($this->config['bom'] ?? false) {
            echo "\xEF\xBB\xBF";
        }

        $handle = fopen('php://output', 'w');
        $headers = $headers ?? array_keys(reset($data));
        
        fputcsv($handle, $headers, $this->config['delimiter'] ?? ',');
        
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row[$header] ?? '';
            }
            fputcsv($handle, $csvRow, $this->config['delimiter'] ?? ',');
        }
        
        fclose($handle);
        exit;
    }

    public function streamJson(array $data): void
    {
        $filename = ($this->config['filename'] ?? 'export') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}