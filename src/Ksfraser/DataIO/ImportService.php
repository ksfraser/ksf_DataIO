<?php

namespace Ksfraser\DataIO;

class ImportService
{
    private array $config = [];
    private array $errors = [];
    private array $fieldMapping = [];
    private array $defaults = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function fromCsv(string $filePath, ?callable $callback = null): array
    {
        if (!file_exists($filePath)) {
            $this->errors[] = "File not found: {$filePath}";
            return [];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->errors[] = "Cannot open file: {$filePath}";
            return [];
        }

        $headers = fgetcsv($handle, 0, $this->config['delimiter'] ?? ',');
        $headers = array_map('trim', $headers);

        $results = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $this->config['delimiter'] ?? ',')) !== false) {
            $rowNumber++;
            $rowData = array_combine($headers, $row);

            if ($rowData === false) {
                $this->errors[] = "Row {$rowNumber}: Column count mismatch";
                continue;
            }

            $rowData = $this->applyFieldMapping($rowData);
            $rowData = $this->applyDefaults($rowData);
            $rowData = $this->validate($rowData, $rowNumber);

            if (!empty($this->config['skip_empty_rows'])) {
                if (empty(array_filter($rowData))) {
                    continue;
                }
            }

            if ($callback) {
                $result = $callback($rowData, $rowNumber);
                if ($result !== false) {
                    $results[] = $result;
                }
            } else {
                $results[] = $rowData;
            }
        }

        fclose($handle);
        return $results;
    }

    public function fromJson(string $filePath, ?callable $callback = null): array
    {
        $content = file_get_contents($filePath);
        if (!$content) {
            $this->errors[] = "Cannot read file: {$filePath}";
            return [];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = "Invalid JSON: " . json_last_error_msg();
            return [];
        }

        if (!is_array($data)) {
            $this->errors[] = "JSON must be an array";
            return [];
        }

        $results = [];
        foreach ($data as $index => $row) {
            $row = $this->applyFieldMapping($row);
            $row = $this->applyDefaults($row);
            $row = $this->validate($row, $index);

            if ($callback) {
                $result = $callback($row, $index);
                if ($result !== false) {
                    $results[] = $result;
                }
            } else {
                $results[] = $row;
            }
        }

        return $results;
    }

    public function fromExcel(string $filePath, ?callable $callback = null): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $this->errors[] = "PhpSpreadsheet not installed";
            return [];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $headers = $worksheet->toArray(0);
            $headers = array_shift($headers);

            $results = [];
            $rows = $worksheet->toArray(1);

            foreach ($rows as $rowNumber => $row) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $rowData = array_combine($headers, $row);
                $rowData = $this->applyFieldMapping($rowData);
                $rowData = $this->applyDefaults($rowData);
                $rowData = $this->validate($rowData, $rowNumber);

                if ($callback) {
                    $result = $callback($rowData, $rowNumber);
                    if ($result !== false) {
                        $results[] = $result;
                    }
                } else {
                    $results[] = $rowData;
                }
            }

            return $results;
        } catch (\Exception $e) {
            $this->errors[] = "Excel error: " . $e->getMessage();
            return [];
        }
    }

    public function fromXml(string $filePath, string $rootElement = 'item', ?callable $callback = null): array
    {
        $content = file_get_contents($filePath);
        if (!$content) {
            $this->errors[] = "Cannot read file: {$filePath}";
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if (!$xml) {
            $errors = libxml_get_errors();
            $this->errors[] = "XML parse error: " . $errors[0]->message ?? 'Unknown';
            libxml_clear_errors();
            return [];
        }

        $results = [];
        $index = 0;

        foreach ($xml->{$rootElement} as $item) {
            $row = (array) $item;
            $row = $this->applyFieldMapping($row);
            $row = $this->applyDefaults($row);
            $row = $this->validate($row, $index);

            if ($callback) {
                $result = $callback($row, $index);
                if ($result !== false) {
                    $results[] = $result;
                }
            } else {
                $results[] = $row;
            }
            $index++;
        }

        return $results;
    }

    public function setFieldMapping(array $mapping): self
    {
        $this->fieldMapping = $mapping;
        return $this;
    }

    public function setDefaults(array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    public function setValidators(array $validators): self
    {
        $this->config['validators'] = $validators;
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    private function applyFieldMapping(array $data): array
    {
        if (empty($this->fieldMapping)) {
            return $data;
        }

        $mapped = [];
        foreach ($data as $key => $value) {
            $newKey = $this->fieldMapping[$key] ?? $key;
            $mapped[$newKey] = $value;
        }

        return $mapped;
    }

    private function applyDefaults(array $data): array
    {
        return array_merge($this->defaults, $data);
    }

    private function validate(array $data, int $rowNumber): array
    {
        if (empty($this->config['validators'])) {
            return $data;
        }

        foreach ($this->config['validators'] as $field => $validator) {
            if (!isset($data[$field])) {
                if (isset($this->defaults[$field])) {
                    continue;
                }
                $this->errors[] = "Row {$rowNumber}: Missing required field: {$field}";
                continue;
            }

            $value = $data[$field];
            $isValid = true;

            if (is_callable($validator)) {
                $isValid = $validator($value, $data);
            } elseif (is_regex($validator)) {
                $isValid = preg_match($validator, $value);
            } elseif (is_array($validator)) {
                $isValid = in_array($value, $validator);
            }

            if (!$isValid) {
                $this->errors[] = "Row {$rowNumber}: Invalid {$field} = " . var_export($value, true);
            }
        }

        return $data;
    }
}