<?php

namespace Ksfraser\DataIO;

class FieldMappingService
{
    private array $savedMappings = [];
    private string $mappingTable = 'ksf_field_mappings';
    private ?\mysqli $db = null;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initDatabase();
    }

    public function loadFile(string $filePath, int $previewRows = 5): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $headers = [];
        $rows = [];
        
        match ($extension) {
            'csv' => [$headers, $rows] = $this->loadCsvPreview($filePath, $previewRows),
            'json' => [$headers, $rows] = $this->loadJsonPreview($filePath, $previewRows),
            'xlsx', 'xls' => [$headers, $rows] = $this->loadExcelPreview($filePath, $previewRows),
            'xml' => [$headers, $rows] = $this->loadXmlPreview($filePath, $previewRows),
            default => throw new \InvalidArgumentException("Unknown format: {$extension}"),
        };
        
        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_columns' => count($headers),
            'format' => $extension,
        ];
    }

    private function loadCsvPreview(string $filePath, int $previewRows): array
    {
        $handle = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($handle));
        
        $rows = [];
        while (count($rows) < $previewRows && ($row = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $row);
        }
        
        fclose($handle);
        return [$headers, $rows];
    }

    private function loadJsonPreview(string $filePath, int $previewRows): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (!is_array($data) || empty($data)) {
            return [[], []];
        }
        
        $headers = array_keys(is_array($data[0]) ? $data[0] : $data);
        $rows = array_slice($data, 0, $previewRows);
        
        return [$headers, $rows];
    }

    private function loadExcelPreview(string $filePath, int $previewRows): array
    {
        return [[], []];
    }

    private function loadXmlPreview(string $filePath, int $previewRows): array
    {
        $xml = simplexml_load_file($filePath);
        $firstChild = $xml->children()[0] ?? null;
        
        if (!$firstChild) {
            return [[], []];
        }
        
        $headers = array_keys((array) $firstChild);
        $rows = [];
        
        foreach (array_slice($xml->children(), 0, $previewRows) as $item) {
            $rows[] = (array) $item;
        }
        
        return [$headers, $rows];
    }

    public function createMapping(string $name, array $mapping, ?string $description = null): int
    {
        $this->initDatabase();
        
        $inputFields = json_encode(array_keys($mapping));
        $outputFields = json_encode(array_values($mapping));
        
        $sql = "INSERT INTO {$this->mappingTable} 
            (name, description, input_fields, output_fields, created_at)
            VALUES (
                '" . $this->db->real_escape_string($name) . "',
                '" . $this->db->real_escape_string($description ?? '') . "',
                '" . $this->db->real_escape_string($inputFields) . "',
                '" . $this->db->real_escape_string($outputFields) . "',
                NOW()
            )";
        
        $this->db->query($sql);
        
        return $this->db->insert_id;
    }

    public function updateMapping(int $id, array $mapping): bool
    {
        $this->initDatabase();
        
        $inputFields = json_encode(array_keys($mapping));
        $outputFields = json_encode(array_values($mapping));
        
        $sql = "UPDATE {$this->mappingTable}
            SET input_fields = '{$inputFields}',
                output_fields = '{$outputFields}',
                updated_at = NOW()
            WHERE id = {$id}";
        
        return $this->db->query($sql);
    }

    public function deleteMapping(int $id): bool
    {
        $this->initDatabase();
        
        $sql = "DELETE FROM {$this->mappingTable} WHERE id = {$id}";
        
        return $this->db->query($sql);
    }

    public function getMapping(int $id): ?array
    {
        $this->initDatabase();
        
        $sql = "SELECT * FROM {$this->mappingTable} WHERE id = {$id}";
        $result = $this->db->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        
        return $this->formatMapping($result->fetch_assoc());
    }

    public function getMappingByName(string $name): ?array
    {
        $this->initDatabase();
        
        $sql = "SELECT * FROM {$this->mappingTable} 
            WHERE name = '" . $this->db->real_escape_string($name) . "'";
        $result = $this->db->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        
        return $this->formatMapping($result->fetch_assoc());
    }

    public function listMappings(?string $module = null): array
    {
        $this->initDatabase();
        
        $sql = "SELECT * FROM {$this->mappingTable}";
        
        if ($module) {
            $sql .= " WHERE module = '" . $this->db->real_escape_string($module) . "'";
        }
        
        $sql .= " ORDER BY name";
        
        $result = $this->db->query($sql);
        $mappings = [];
        
        while ($row = $result->fetch_assoc()) {
            $mappings[] = $this->formatMapping($row);
        }
        
        return $mappings;
    }

    public function listMappingsForModule(string $moduleName, array $availableFields): array
    {
        $mappings = $this->listMappings($moduleName);
        
        $usable = [];
        foreach ($mappings as $mapping) {
            $mapping['fields'] = $this->filterMappingForFields($mapping['fields'], $availableFields);
            
            if (!empty($mapping['fields'])) {
                $usable[] = $mapping;
            }
        }
        
        return $usable;
    }

    private function filterMappingForFields(array $mapping, array $availableFields): array
    {
        $filtered = [];
        
        foreach ($mapping as $input => $output) {
            if (in_array($output, $availableFields)) {
                $filtered[$input] = $output;
            }
        }
        
        return $filtered;
    }

    public function autoMap(array $inputFields, array $outputFields): array
    {
        $mapping = [];
        
        foreach ($inputFields as $input) {
            $inputLower = strtolower($input);
            $inputClean = preg_replace('/[^a-z0-9]/', '', $inputLower);
            
            foreach ($outputFields as $output) {
                $outputLower = strtolower($output);
                $outputClean = preg_replace('/[^a-z0-9]/', '', $outputLower);
                
                if ($inputClean === $outputClean || 
                    strpos($outputLower, $inputLower) !== false ||
                    strpos($inputLower, $outputLower) !== false) {
                    $mapping[$input] = $output;
                    break;
                }
            }
        }
        
        return $mapping;
    }

    public function applyMapping(array $data, array $mapping): array
    {
        $result = [];
        
        foreach ($data as $row) {
            $mappedRow = [];
            
            foreach ($mapping as $inputField => $outputField) {
                $mappedRow[$outputField] = $row[$inputField] ?? null;
            }
            
            $result[] = $mappedRow;
        }
        
        return $result;
    }

    private function initDatabase(): void
    {
        if (!empty($this->db)) {
            return;
        }
        
        if (empty($this->config['fa_path'])) {
            return;
        }
        
        $dbFile = $this->config['fa_path'] . '/includes/db.inc';
        
        if (!file_exists($dbFile)) {
            return;
        }
        
        global $db;
        include_once $dbFile;
        
        $this->db = $db;
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->mappingTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            module VARCHAR(50),
            input_fields JSON NOT NULL,
            output_fields JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            created_by VARCHAR(50),
            UNIQUE KEY unique_name (name, module)
        )";
        
        $db->query($sql);
    }

    private function formatMapping(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'module' => $row['module'],
            'fields' => json_decode($row['output_fields'], true),
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}

class ImportWizard
{
    private ImportService $import;
    private $result = null;
    private array $config;
    private int $currentStep = 0;
    private ?string $filePath = null;
    private array $filePreview = [];
    private array $mapping = [];
    private ?int $mappingId = null;
    private FieldMappingService $mappingService;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->import = new ImportService($config);
        $this->mappingService = new FieldMappingService($config);
    }

    public function step(): int
    {
        return $this->currentStep;
    }

    public function loadFile(string $filePath): self
    {
        $this->filePath = $filePath;
        $this->filePreview = $this->mappingService->loadFile($filePath, 10);
        $this->currentStep = 1;
        
        return $this;
    }

    public function getFilePreview(): array
    {
        return $this->filePreview;
    }

    public function setMapping(array $mapping): self
    {
        $this->mapping = $mapping;
        $this->currentStep = 2;
        
        return $this;
    }

    public function useSavedMapping(int $mappingId): self
    {
        $savedMapping = $this->mappingService->getMapping($mappingId);
        
        if ($savedMapping) {
            $this->mappingId = $mappingId;
            $this->mapping = $savedMapping['fields'];
            $this->currentStep = 2;
        }
        
        return $this;
    }

    public function autoMap(array $targetFields): self
    {
        if (!empty($this->filePreview['headers'])) {
            $this->mapping = $this->mappingService->autoMap(
                $this->filePreview['headers'],
                $targetFields
            );
            $this->currentStep = 2;
        }
        
        return $this;
    }

    public function saveMapping(string $name, ?string $description = null): int
    {
        return $this->mappingService->createMapping($name, $this->mapping, $description);
    }

    public function execute(?callable $processor = null)
    {
        if (!$this->filePath || empty($this->mapping)) {
            $this->result = new ImportResult([], ['File or mapping not set']);
            return $this->result;
        }
        
        $this->import->setFieldMapping($this->mapping);
        
        $data = $this->import->fromCsv($this->filePath, $processor);
        
        $this->result = ['data' => $data, 'errors' => $this->import->getErrors(), 'count' => count($data)];
        
        $this->currentStep = 3;
        
        return $this->result;
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function listSavedMappings(string $module, array $availableFields): array
    {
        return $this->mappingService->listMappingsForModule($module, $availableFields);
    }

    public static function create(array $config = []): self
    {
        return new self($config);
    }
}