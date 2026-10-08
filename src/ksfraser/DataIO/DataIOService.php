<?php

namespace Ksfraser\DataIO;

class DataIOService
{
    private ImportService $import;
    private ExportService $export;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->import = new ImportService($config);
        $this->export = new ExportService($config);
    }

    public static function create(array $config = []): self
    {
        return new self($config);
    }

    public function import(string $filePath, ?callable $processor = null): ImportResult
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $data = match ($extension) {
            'csv' => $this->import->fromCsv($filePath, $processor),
            'json' => $this->import->fromJson($filePath, $processor),
            'xlsx', 'xls' => $this->import->fromExcel($filePath, $processor),
            'xml' => $this->import->fromXml($filePath, $processor),
            default => [],
        };

        return new ImportResult($data, $this->import->getErrors());
    }

    public function export(array $data, string $filePath, ?array $headers = null): bool
    {
        if (empty($data)) {
            return false;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'csv' => $this->export->toCsv($data, $filePath, $headers),
            'json' => $this->export->toJson($data, $filePath),
            'xlsx', 'xls' => $this->export->toExcel($data, $filePath, $headers),
            'xml' => $this->export->toXml($data, $filePath),
            'html' => file_put_contents($filePath, $this->export->toHtml($data, $headers)) !== false,
            default => false,
        };
    }

    public function stream(array $data, string $format, ?array $headers = null): void
    {
        match ($format) {
            'csv' => $this->export->streamCsv($data, $headers),
            'json' => $this->export->streamJson($data),
            default => throw new \InvalidArgumentException("Unknown format: {$format}"),
        };
    }

    public function importFromDatabase(\mysqli $db, string $table, ?callable $processor = null): ImportResult
    {
        $result = $db->query("SELECT * FROM {$table}");
        
        if (!$result) {
            return new ImportResult([], ["Query failed: " . $db->error]);
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $processed = $processor ? $processor($row) : $row;
            if ($processed !== false) {
                $data[] = $processed;
            }
        }

        return new ImportResult($data, []);
    }

    public function exportToDatabase(\mysqli $db, array $data, string $table, array $keyField = ['id']): int
    {
        $imported = 0;
        
        foreach ($data as $row) {
            $exists = true;
            
            foreach ($keyField as $key) {
                if (!isset($row[$key])) {
                    $exists = false;
                    break;
                }
            }
            
            if ($exists) {
                $where = [];
                foreach ($keyField as $key) {
                    $where[] = "{$key} = '" . $db->real_escape_string($row[$key]) . "'";
                }
                $check = $db->query("SELECT 1 FROM {$table} WHERE " . implode(' AND ', $where));
                $exists = $check && $check->num_rows > 0;
            }
            
            if ($exists) {
                $sets = [];
                foreach ($row as $key => $value) {
                    $sets[] = "{$key} = '" . $db->real_escape_string($value) . "'";
                }
                $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE " . implode(' AND ', $where);
            } else {
                $cols = implode(', ', array_keys($row));
                $values = implode(', ', array_map(fn($v) => "'" . $db->real_escape_string($v) . "'", array_values($row)));
                $sql = "INSERT INTO {$table} ({$cols}) VALUES ({$values})";
            }
            
            if ($db->query($sql)) {
                $imported++;
            }
        }
        
        return $imported;
    }

    public function setFieldMapping(array $mapping): self
    {
        $this->import->setFieldMapping($mapping);
        return $this;
    }

    public function setDefaults(array $defaults): self
    {
        $this->import->setDefaults($defaults);
        return $this;
    }

    public function setValidators(array $validators): self
    {
        $this->import->setValidators($validators);
        return $this;
    }

    public function getImportService(): ImportService
    {
        return $this->import;
    }

    public function getExportService(): ExportService
    {
        return $this->export;
    }
}

class ImportResult
{
    public array $data;
    public array $errors;
    public int $count;

    public function __construct(array $data, array $errors = [])
    {
        $this->data = $data;
        $this->errors = $errors;
        $this->count = count($data);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function first(): ?array
    {
        return $this->data[0] ?? null;
    }
}