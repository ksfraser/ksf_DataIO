# ksf_DataIO - Architecture

## Document Information
- **Module**: ksf_DataIO (Data Import/Export)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Architecture Overview

### 1.1 Design Principles
The ksf_DataIO module follows these architectural principles:

1. **Service-Oriented**: Separate services for import/export
2. **Strategy Pattern**: Multiple format handlers
3. **Template Method**: Wizard with hookable steps
4. **Repository Pattern**: Mapping storage

### 1.2 Technology Stack
- **PHP**: 8.0+ with strict typing
- **Database**: MySQL 5.7+ via FrontAccounting
- **Excel Processing**: PhpSpreadsheet
- **Architecture**: Service Layer with Wizard Pattern

---

## 2. Directory Structure

```
ksf_DataIO/
├── composer.json
├── phpunit.xml
├── demo_wizard.php              # Demo of import wizard
├── demo.php                     # Demo script
├── includes/                    # FA includes (future)
├── src/
│   └── Ksfraser/
│       └── DataIO/
│           ├── ImportService.php     # Core import logic
│           ├── ExportService.php     # Core export logic
│           ├── ImportWizard.php      # Wizard + FieldMappingService
│           ├── FAModuleImport.php   # FA module integration (future)
│           └── ImportUI.php         # UI helpers (future)
├── templates/                   # Import templates (future)
├── tests/                       # Unit tests
│   └── Unit/
└── ProjectDcs/
    ├── Business Requirements.md
    ├── Architecture.md
    ├── Functional Requirements.md
    ├── Use Case.md
    ├── Test Plan.md
    └── UAT Plan.md
```

---

## 3. Class Architecture

### 3.1 ImportService
```php
namespace Ksfraser\DataIO;

class ImportService
{
    private array $config = [];
    private array $errors = [];
    private array $fieldMapping = [];
    private array $defaults = [];

    public function __construct(array $config = []);
    
    // Import methods
    public function fromCsv(string $filePath, ?callable $callback = null): array;
    public function fromJson(string $filePath, ?callable $callback = null): array;
    public function fromExcel(string $filePath, ?callable $callback = null): array;
    public function fromXml(string $filePath, string $rootElement = 'item', ?callable $callback = null): array;
    
    // Configuration
    public function setFieldMapping(array $mapping): self;
    public function setDefaults(array $defaults): self;
    public function setValidators(array $validators): self;
    
    // Error handling
    public function getErrors(): array;
    public function hasErrors(): bool;
    
    // Private helpers
    private function applyFieldMapping(array $data): array;
    private function applyDefaults(array $data): array;
    private function validate(array $data, int $rowNumber): array;
}
```

### 3.2 ExportService
```php
namespace Ksfraser\DataIO;

class ExportService
{
    private array $config = [];
    private array $headers = [];

    public function __construct(array $config = []);
    
    // Export methods
    public function toCsv(array $data, string $filePath, ?array $headers = null): bool;
    public function toJson(array $data, string $filePath, bool $pretty = true): bool;
    public function toExcel(array $data, string $filePath, ?array $headers = null, ?array $styles = null): bool;
    public function toXml(array $data, string $filePath, string $rootElement = 'items', string $itemElement = 'item'): bool;
    public function toHtml(array $data, ?array $headers = null): string;
    public function toPdf(array $data, string $filePath, ?array $headers = null): bool;
    
    // Streaming methods
    public function streamCsv(array $data, ?array $headers = null): void;
    public function streamJson(array $data): void;
}
```

### 3.3 FieldMappingService
```php
namespace Ksfraser\DataIO;

class FieldMappingService
{
    private array $savedMappings = [];
    private string $mappingTable = 'ksf_field_mappings';
    private ?\mysqli $db = null;
    private array $config;

    public function __construct(array $config = []);
    
    // File preview
    public function loadFile(string $filePath, int $previewRows = 5): array;
    
    // Mapping CRUD
    public function createMapping(string $name, array $mapping, ?string $description = null): int;
    public function updateMapping(int $id, array $mapping): bool;
    public function deleteMapping(int $id): bool;
    public function getMapping(int $id): ?array;
    public function getMappingByName(string $name): ?array;
    public function listMappings(?string $module = null): array;
    public function listMappingsForModule(string $moduleName, array $availableFields): array;
    
    // Auto-mapping
    public function autoMap(array $inputFields, array $outputFields): array;
    
    // Apply mapping
    public function applyMapping(array $data, array $mapping): array;
    
    // Private helpers
    private function initDatabase(): void;
    private function formatMapping(array $row): array;
    private function filterMappingForFields(array $mapping, array $availableFields): array;
}
```

### 3.4 ImportWizard
```php
namespace Ksfraser\DataIO;

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

    public function __construct(array $config = []);
    
    // Workflow methods
    public function step(): int;
    public function loadFile(string $filePath): self;
    public function getFilePreview(): array;
    public function setMapping(array $mapping): self;
    public function useSavedMapping(int $mappingId): self;
    public function autoMap(array $targetFields): self;
    public function saveMapping(string $name, ?string $description = null): int;
    public function execute(?callable $processor = null);
    
    // Getters
    public function getMapping(): array;
    public function getResult();
    public function listSavedMappings(string $module, array $availableFields): array;
    
    // Factory
    public static function create(array $config = []): self;
}
```

---

## 4. Data Flow Diagrams

### 4.1 Import Flow
```
[File Upload]
      |
      v
[File Type Detection]
      |
      v
[Parse File] --> [Preview Generation]
      |                    |
      v                    v
[Field Mapping] <-- [User Selection]
      |
      v
[Validation] --> [Error Report]
      |
      v
[Row Processing Callback]
      |
      v
[Database Insert/Batch]
      |
      v
[Import Result]
```

### 4.2 Field Mapping Flow
```
[Source Headers] --> [Input Fields]
                            |
                            v
                    [Auto-Map Algorithm]
                            |
                            v
                    [Mapping Matrix]
                            |
                            v
                    [User Confirmation]
                            |
                            v
                    [Save Template?]
                            |
                            v
                    [Apply to Import]
```

### 4.3 Export Flow
```
[Data Query]
      |
      v
[Header Extraction]
      |
      v
[Format Selection]
      |
      +---> [CSV] --> [File Write]
      |
      +---> [JSON] --> [JSON Encode] --> [File Write]
      |
      +---> [Excel] --> [PhpSpreadsheet] --> [File Write]
      |
      +---> [XML] --> [SimpleXML] --> [File Write]
```

---

## 5. Database Schema

### 5.1 ksf_field_mappings Table
```sql
CREATE TABLE ksf_field_mappings (
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
);
```

---

## 6. Configuration Options

### 6.1 ImportService Config
```php
$config = [
    'delimiter' => ',',           // CSV delimiter
    'skip_empty_rows' => true,    // Skip empty rows
    'validators' => [             // Field validators
        'email' => FILTER_VALIDATE_EMAIL,
        'phone' => '/^\+?[0-9]+$/',
        'status' => ['pending', 'active', 'completed'],
    ],
];
```

### 6.2 ExportService Config
```php
$config = [
    'delimiter' => ',',           // CSV delimiter
    'bom' => true,                // Add UTF-8 BOM
    'filename' => 'export',      // Default filename
];
```

---

## 7. Auto-Mapping Algorithm

### 7.1 Algorithm Steps
1. Normalize field names (lowercase, remove spaces/special chars)
2. For each input field:
   a. Compare against each output field
   b. Check exact match after normalization
   c. Check if input contains output
   d. Check if output contains input
   e. If match found, add to mapping
3. Return mapping array

### 7.2 Example
```
Input:  ["First Name", "Last Name", "Email Address", "Phone"]
Output: ["firstname", "lastname", "email", "phone"]

Normalized Input: ["firstname", "lastname", "emailaddress", "phone"]
Normalized Output: ["firstname", "lastname", "email", "phone"]

Matching:
- "firstname" == "firstname" ✓
- "lastname" == "lastname" ✓
- "emailaddress" contains "email" ✓
- "phone" == "phone" ✓

Result: {
    "First Name" => "firstname",
    "Last Name" => "lastname",
    "Email Address" => "email",
    "Phone" => "phone"
}
```

---

## 8. Error Handling

### 8.1 Import Errors
| Error Type | Description | Handling |
|------------|-------------|----------|
| File not found | File doesn't exist | Return empty array, log error |
| Parse error | Invalid format | Collect error, continue |
| Validation error | Invalid field value | Collect error, continue row |
| Column mismatch | Row has wrong columns | Collect error, skip row |

### 8.2 Export Errors
| Error Type | Handling |
|------------|----------|
| No data | Return false |
| Write permission | Return false, log error |
| Excel library missing | Return false, suggest install |

---

## 9. Performance Considerations

### 9.1 Memory Management
- Process large files in chunks
- Use generators for row-by-row processing
- Limit preview to configured number of rows

### 9.2 Database Operations
- Batch inserts for bulk imports
- Use prepared statements
- Transaction wrapping for atomicity

### 9.3 Optimization Strategies
- Lazy loading of file preview
- Cached mapping templates
- Streaming export for large datasets

---

## 10. Extension Points

### 10.1 Custom Format Handler
```php
interface ImportFormatHandler
{
    public function canHandle(string $filePath): bool;
    public function parse(string $filePath, callable $callback): array;
}
```

### 10.2 Custom Validator
```php
// Register in ImportService
$import->setValidators([
    'custom_field' => function($value, $row) {
        return customValidation($value);
    }
]);
```

### 10.3 Import Hook
```php
// After each row
add_hook('dataio_import_row', function($row, $rowNumber) {
    // Custom processing
    return $row;
});
```

---

## 11. Security Considerations

### 11.1 File Validation
- Verify file extension
- Check MIME type
- Limit file size
- Validate UTF-8 encoding

### 11.2 SQL Injection Prevention
- Use prepared statements for mapping storage
- Escape user input in file paths

### 11.3 Path Traversal
- Validate file paths
- Restrict upload directory
- Sanitize output paths

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*