# ksf_DataIO - Functional Requirements

## Document Information
- **Module**: ksf_DataIO (Data Import/Export)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Overview

### 1.1 Purpose
This document defines the functional requirements for the ksf_DataIO module, providing comprehensive data import and export capabilities.

### 1.2 Scope
- Multi-format file import (CSV, JSON, Excel, XML)
- Flexible field mapping with templates
- Data validation and error reporting
- Multi-format export
- Import wizard workflow

---

## 2. Import Requirements

### 2.1 CSV Import (FR-IMP-001)
**Requirement**: The system shall import data from CSV files.

**Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| filePath | string | required | Path to CSV file |
| callback | callable | null | Row processing callback |
| delimiter | string | , | Field delimiter |

**Process**:
1. Open file handle
2. Read and parse header row
3. For each data row:
   - Check column count matches
   - Combine with headers as associative array
   - Apply field mapping
   - Apply default values
   - Validate row
   - Execute callback if provided
   - Add to results
4. Collect errors
5. Return all rows

**Output**: Array of processed rows

**Priority**: Critical

### 2.2 JSON Import (FR-IMP-002)
**Requirement**: The system shall import data from JSON files.

**Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| filePath | string | required | Path to JSON file |
| callback | callable | null | Row processing callback |

**Validation**:
- File must contain valid JSON array
- Each array element must be an object

**Output**: Array of processed rows

**Priority**: High

### 2.3 Excel Import (FR-IMP-003)
**Requirement**: The system shall import data from Excel files.

**Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| filePath | string | required | Path to Excel file |
| callback | callable | null | Row processing callback |

**Requirements**:
- Support .xlsx format
- Support .xls format
- First row used as headers
- Uses PhpSpreadsheet library

**Output**: Array of processed rows

**Priority**: High

### 2.4 XML Import (FR-IMP-004)
**Requirement**: The system shall import data from XML files.

**Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| filePath | string | required | Path to XML file |
| rootElement | string | 'item' | Container element |
| callback | callable | null | Row processing callback |

**Process**:
1. Parse XML string
2. Iterate over rootElement children
3. Convert each to associative array
4. Apply mapping and validation

**Output**: Array of processed rows

**Priority**: Medium

---

## 3. Field Mapping

### 3.1 Set Field Mapping (FR-MAP-001)
**Requirement**: The system shall allow setting field mappings.

**Format**:
```php
$mapping = [
    'SourceField1' => 'TargetField1',
    'SourceField2' => 'TargetField2',
];
```

**Behavior**:
- Maps source field names to target field names
- Unknown source fields ignored
- Missing target fields use null

**Priority**: Critical

### 3.2 Auto-Map Fields (FR-MAP-002)
**Requirement**: The system shall auto-generate field mappings.

**Algorithm**:
1. Normalize input field names (lowercase, remove special chars)
2. Normalize output field names
3. Compare for:
   - Exact match
   - Input contains output
   - Output contains input

**Output**: Array of best matches

**Priority**: High

### 3.3 Set Default Values (FR-MAP-003)
**Requirement**: The system shall apply default values.

**Format**:
```php
$defaults = [
    'status' => 'pending',
    'source' => 'import',
];
```

**Behavior**:
- Defaults applied after field mapping
- Row values override defaults

**Priority**: High

---

## 4. Validation

### 4.1 Set Validators (FR-VAL-001)
**Requirement**: The system shall validate imported data.

**Validator Types**:

| Type | Format | Description |
|------|--------|-------------|
| Regex | `/pattern/` | preg_match pattern |
| Array | `[val1, val2]` | Must be in list |
| Callable | `function($value, $row)` | Custom validation |

**Example**:
```php
$validators = [
    'email' => FILTER_VALIDATE_EMAIL,
    'status' => ['pending', 'active', 'completed'],
    'phone' => '/^\+?[0-9]{10,15}$/',
    'custom' => function($value, $row) {
        return $value > $row['min'];
    },
];
```

**Priority**: High

### 4.2 Validation Error Handling (FR-VAL-002)
**Requirement**: The system shall collect and report validation errors.

**Error Format**:
```php
"Row {rowNumber}: Invalid {fieldName} = {value}"
```

**Behavior**:
- Errors collected in array
- Processing continues after errors
- Errors retrievable via getErrors()

**Priority**: High

---

## 5. Mapping Templates

### 5.1 Create Mapping (FR-TEMP-001)
**Requirement**: The system shall save field mappings as templates.

**Input**:
| Parameter | Type | Description |
|-----------|------|-------------|
| name | string | Template name |
| mapping | array | Field mapping array |
| description | string | Optional description |

**Process**:
1. JSON encode input fields (keys)
2. JSON encode output fields (values)
3. Insert into database
4. Return mapping ID

**Output**: Mapping ID

**Priority**: High

### 5.2 Load Mapping (FR-TEMP-002)
**Requirement**: The system shall load saved mappings.

**Methods**:
- `getMapping(int $id)` - By ID
- `getMappingByName(string $name)` - By name
- `listMappings(?string $module)` - List all (optional filter)

**Output**: Array with `fields` key containing mapping

**Priority**: High

### 5.3 Delete Mapping (FR-TEMP-003)
**Requirement**: The system shall delete saved mappings.

**Input**: Mapping ID

**Output**: Boolean success

**Priority**: Medium

### 5.4 List Mappings for Module (FR-TEMP-004)
**Requirement**: The system shall list mappings filtered by available fields.

**Purpose**: Only show mappings compatible with target module

**Input**:
| Parameter | Type | Description |
|-----------|------|-------------|
| module | string | Module name |
| availableFields | array | Fields available in module |

**Output**: Array of mappings with filtered fields

**Priority**: Medium

---

## 6. Export Requirements

### 6.1 CSV Export (FR-EXP-001)
**Requirement**: The system shall export data to CSV format.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| data | array | Data to export |
| filePath | string | Output file path |
| headers | array | Column headers (optional) |
| delimiter | string | CSV delimiter |

**Features**:
- Optional UTF-8 BOM
- Configurable delimiter
- Header row

**Output**: Boolean success

**Priority**: Critical

### 6.2 JSON Export (FR-EXP-002)
**Requirement**: The system shall export data to JSON format.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| data | array | Data to export |
| filePath | string | Output file path |
| pretty | bool | Pretty print |

**Output**: Boolean success

**Priority**: High

### 6.3 Excel Export (FR-EXP-003)
**Requirement**: The system shall export data to Excel format.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| data | array | Data to export |
| filePath | string | Output file path |
| headers | array | Column headers |
| styles | array | Optional styling |

**Features**:
- Auto column width
- Date formatting
- Number formatting

**Output**: Boolean success

**Priority**: High

### 6.4 XML Export (FR-EXP-004)
**Requirement**: The system shall export data to XML format.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| data | array | Data to export |
| filePath | string | Output file path |
| rootElement | string | Root element name |
| itemElement | string | Item element name |

**Output**: Boolean success

**Priority**: Medium

### 6.5 HTML Export (FR-EXP-005)
**Requirement**: The system shall export data as HTML table.

**Output**: HTML string (not file)

**Priority**: Low

### 6.6 PDF Export (FR-EXP-006)
**Requirement**: The system shall export data to PDF format.

**Method**: Uses Excel as intermediate, converts to PDF via Dompdf

**Output**: Boolean success

**Priority**: Low

### 6.7 Streaming Export (FR-EXP-007)
**Requirement**: The system shall stream export directly to browser.

**Methods**:
- `streamCsv(array $data, ?array $headers)` - Output CSV
- `streamJson(array $data)` - Output JSON

**Behavior**:
- Sets appropriate headers
- Outputs directly to browser
- Exits after output

**Priority**: Medium

---

## 7. Import Wizard

### 7.1 Wizard States (FR-WIZ-001)
**Requirement**: The system shall track wizard state.

| Step | State | Description |
|------|-------|-------------|
| 0 | Initial | No file loaded |
| 1 | File Loaded | Preview available |
| 2 | Mapping Set | Mapping configured |
| 3 | Complete | Import executed |

**Priority**: High

### 7.2 Wizard Flow (FR-WIZ-002)
**Requirement**: The system shall guide through import steps.

**Steps**:
1. `loadFile()` - Load and preview file
2. `setMapping()` or `autoMap()` - Configure mapping
3. `execute()` - Run import
4. `getResult()` - Get results

**Priority**: High

### 7.3 Saved Mapping Usage (FR-WIZ-003)
**Requirement**: The system shall allow using saved mappings.

**Method**: `useSavedMapping(int $mappingId)`

**Behavior**:
- Loads saved mapping from database
- Sets wizard to step 2
- Returns self for chaining

**Priority**: Medium

---

## 8. Service Interfaces

### 8.1 ImportService

```php
class ImportService
{
    public function __construct(array $config = []);
    public function fromCsv(string $filePath, ?callable $callback = null): array;
    public function fromJson(string $filePath, ?callable $callback = null): array;
    public function fromExcel(string $filePath, ?callable $callback = null): array;
    public function fromXml(string $filePath, string $rootElement = 'item', ?callable $callback = null): array;
    public function setFieldMapping(array $mapping): self;
    public function setDefaults(array $defaults): self;
    public function setValidators(array $validators): self;
    public function getErrors(): array;
    public function hasErrors(): bool;
}
```

### 8.2 ExportService

```php
class ExportService
{
    public function __construct(array $config = []);
    public function toCsv(array $data, string $filePath, ?array $headers = null): bool;
    public function toJson(array $data, string $filePath, bool $pretty = true): bool;
    public function toExcel(array $data, string $filePath, ?array $headers = null, ?array $styles = null): bool;
    public function toXml(array $data, string $filePath, string $rootElement = 'items', string $itemElement = 'item'): bool;
    public function toHtml(array $data, ?array $headers = null): string;
    public function toPdf(array $data, string $filePath, ?array $headers = null): bool;
    public function streamCsv(array $data, ?array $headers = null): void;
    public function streamJson(array $data): void;
}
```

### 8.3 FieldMappingService

```php
class FieldMappingService
{
    public function __construct(array $config = []);
    public function loadFile(string $filePath, int $previewRows = 5): array;
    public function createMapping(string $name, array $mapping, ?string $description = null): int;
    public function updateMapping(int $id, array $mapping): bool;
    public function deleteMapping(int $id): bool;
    public function getMapping(int $id): ?array;
    public function getMappingByName(string $name): ?array;
    public function listMappings(?string $module = null): array;
    public function listMappingsForModule(string $moduleName, array $availableFields): array;
    public function autoMap(array $inputFields, array $outputFields): array;
    public function applyMapping(array $data, array $mapping): array;
}
```

---

## 9. Error Codes

| Code | Error | Description |
|------|-------|-------------|
| E001 | FILE_NOT_FOUND | File path doesn't exist |
| E002 | PARSE_ERROR | File format error |
| E003 | COLUMN_MISMATCH | Row column count doesn't match headers |
| E004 | JSON_INVALID | Invalid JSON format |
| E005 | XML_PARSE_ERROR | XML parsing failed |
| E006 | EXCEL_NOT_SUPPORTED | PhpSpreadsheet not available |

---

## 10. Non-Functional Requirements

### 10.1 Performance
- CSV parse: > 10,000 rows/second
- JSON parse: < 1 second for 1MB file
- Excel parse: < 30 seconds for 5,000 rows

### 10.2 File Size Limits
| Format | Recommended Limit |
|--------|-------------------|
| CSV | 50MB |
| JSON | 20MB |
| Excel | 10MB |
| XML | 20MB |

### 10.3 Compatibility
- PHP 8.0+
- PhpSpreadsheet 1.29+

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*