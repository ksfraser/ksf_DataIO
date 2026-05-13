# ksf_DataIO - Test Plan

## Document Information
- **Module**: ksf_DataIO (Data Import/Export)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Introduction

### 1.1 Purpose
This test plan defines the testing strategy for the ksf_DataIO module, ensuring all import/export functionality works correctly.

### 1.2 Scope
- Import service tests
- Export service tests
- Field mapping tests
- Validation tests
- Wizard workflow tests

### 1.3 Test Environment
- **PHP**: 8.0+
- **Testing Framework**: PHPUnit
- **Dependencies**: PhpSpreadsheet for Excel tests

---

## 2. Testing Strategy

### 2.1 Test Levels

| Level | Description | Target |
|-------|-------------|--------|
| Unit | Individual method tests | 100% |
| Integration | Service flow tests | Core methods |
| Performance | Large file tests | Key operations |

### 2.2 Test Types

| Type | Description |
|------|-------------|
| Functional | Feature verification |
| Edge Cases | Empty files, invalid data |
| Format | All supported formats |

---

## 3. Test Cases

### 3.1 ImportService Tests (TC-IMP)

#### TC-IMP-001: CSV Import Basic
**Preconditions**: Valid CSV file exists  
**Test Steps**:
1. Create ImportService
2. Call fromCsv with valid file
3. Verify returned array

**Expected Result**: Array of row arrays

**Priority**: Critical

---

#### TC-IMP-002: CSV Import with Mapping
**Preconditions**: CSV with "First,Last" columns  
**Test Steps**:
1. Create ImportService
2. Call setFieldMapping(['First' => 'first_name'])
3. Call fromCsv
4. Verify mapped field names

**Expected Result**: Rows have 'first_name' key

**Priority**: High

---

#### TC-IMP-003: CSV Import with Defaults
**Preconditions**: CSV without status column  
**Test Steps**:
1. Create ImportService
2. Call setDefaults(['status' => 'pending'])
3. Call fromCsv
4. Verify default applied

**Expected Result**: Rows have status='pending'

**Priority**: High

---

#### TC-IMP-004: CSV Import Empty Rows Skip
**Preconditions**: CSV with empty rows  
**Test Steps**:
1. Configure skip_empty_rows
2. Call fromCsv
3. Verify empty rows skipped

**Expected Result**: No empty rows in result

**Priority**: Medium

---

#### TC-IMP-005: CSV Import Column Mismatch
**Preconditions**: CSV with mismatched columns  
**Test Steps**:
1. Call fromCsv with malformed data
2. Verify error collected

**Expected Result**: Error in getErrors()

**Priority**: Medium

---

#### TC-IMP-006: CSV Import with Callback
**Preconditions**: Valid CSV  
**Test Steps**:
1. Create callback that modifies data
2. Call fromCsv with callback
3. Verify callback executed

**Expected Result**: Callback applied to rows

**Priority**: High

---

#### TC-IMP-007: JSON Import Valid
**Preconditions**: Valid JSON array file  
**Test Steps**:
1. Call fromJson
2. Verify array returned

**Expected Result**: Parsed JSON as array

**Priority**: High

---

#### TC-IMP-008: JSON Import Invalid
**Preconditions**: Invalid JSON file  
**Test Steps**:
1. Call fromJson with bad JSON
2. Verify error collected

**Expected Result**: Error in getErrors()

**Priority**: High

---

#### TC-IMP-009: XML Import Valid
**Preconditions**: Valid XML file  
**Test Steps**:
1. Call fromXml(file, 'items', 'item')
2. Verify array returned

**Expected Result**: XML converted to array

**Priority**: Medium

---

#### TC-IMP-010: Excel Import
**Preconditions**: Valid .xlsx file  
**Test Steps**:
1. Call fromExcel
2. Verify array returned

**Expected Result**: Excel data as array

**Priority**: High

---

### 3.2 ExportService Tests (TC-EXP)

#### TC-EXP-001: Export to CSV
**Preconditions**: Data array available  
**Test Steps**:
1. Create ExportService
2. Call toCsv(data, tempPath)
3. Verify file exists and contains data

**Expected Result**: CSV file created

**Priority**: Critical

---

#### TC-EXP-002: Export CSV with Headers
**Preconditions**: Data array available  
**Test Steps**:
1. Call toCsv with headers parameter
2. Verify first row has headers

**Expected Result**: Headers in first row

**Priority**: High

---

#### TC-EXP-003: Export CSV Custom Delimiter
**Preconditions**: Data array available  
**Test Steps**:
1. Create ExportService with delimiter=';'
2. Call toCsv
3. Verify semicolons used

**Expected Result**: Semicolon-delimited file

**Priority**: Medium

---

#### TC-EXP-004: Export CSV with BOM
**Preconditions**: Data array available  
**Test Steps**:
1. Create ExportService with bom=true
2. Call toCsv
3. Verify UTF-8 BOM at start

**Expected Result**: BOM present in file

**Priority**: Medium

---

#### TC-EXP-005: Export to JSON
**Preconditions**: Data array available  
**Test Steps**:
1. Call toJson(data, tempPath)
2. Verify valid JSON file

**Expected Result**: Valid JSON file

**Priority**: High

---

#### TC-EXP-006: Export to JSON Pretty
**Preconditions**: Data array available  
**Test Steps**:
1. Call toJson with pretty=true
2. Verify formatted JSON

**Expected Result**: Pretty-printed JSON

**Priority**: Low

---

#### TC-EXP-007: Export to XML
**Preconditions**: Data array available  
**Test Steps**:
1. Call toXml(data, tempPath)
2. Verify valid XML file

**Expected Result**: Valid XML file

**Priority**: Medium

---

#### TC-EXP-008: Export to HTML Table
**Preconditions**: Data array available  
**Test Steps**:
1. Call toHtml(data)
2. Verify HTML table returned

**Expected Result**: HTML string with table

**Priority**: Low

---

### 3.3 FieldMappingService Tests (TC-MAP)

#### TC-MAP-001: Auto-Map Exact Match
**Preconditions**: Matching field names  
**Test Steps**:
1. Call autoMap(['name'], ['name'])
2. Verify mapping created

**Expected Result**: {name: name}

**Priority**: High

---

#### TC-MAP-002: Auto-Map Partial Match
**Preconditions**: Partial name matches  
**Test Steps**:
1. Call autoMap(['FirstName'], ['firstname'])
2. Verify matching

**Expected Result**: {FirstName: firstname}

**Priority**: High

---

#### TC-MAP-003: Auto-Map Case Insensitive
**Preconditions**: Different case names  
**Test Steps**:
1. Call autoMap(['NAME'], ['name'])
2. Verify match

**Expected Result**: Case-insensitive match

**Priority**: High

---

#### TC-MAP-004: Load File Preview
**Preconditions**: CSV file exists  
**Test Steps**:
1. Call loadFile with previewRows=5
2. Verify headers and rows returned

**Expected Result**: Headers + 5 rows

**Priority**: High

---

#### TC-MAP-005: Create Mapping
**Preconditions**: Database connection  
**Test Steps**:
1. Call createMapping(name, mapping)
2. Verify mapping saved

**Expected Result**: Mapping ID returned

**Priority**: High

---

#### TC-MAP-006: Get Mapping
**Preconditions**: Mapping exists  
**Test Steps**:
1. Create mapping
2. Call getMapping with ID
3. Verify mapping returned

**Expected Result**: Mapping array with 'fields' key

**Priority**: High

---

#### TC-MAP-007: Delete Mapping
**Preconditions**: Mapping exists  
**Test Steps**:
1. Create mapping
2. Call deleteMapping
3. Verify deleted

**Expected Result**: Mapping gone from database

**Priority**: Medium

---

#### TC-MAP-008: List Mappings for Module
**Preconditions**: Mappings exist  
**Test Steps**:
1. Call listMappingsForModule(module, fields)
2. Verify filtered list

**Expected Result**: Only compatible mappings

**Priority**: Medium

---

### 3.4 Validation Tests (TC-VAL)

#### TC-VAL-001: Regex Validation Pass
**Preconditions**: ImportService configured  
**Test Steps**:
1. Set validator ['phone' => '/^[0-9]+$/']
2. Import row with valid phone
3. Verify no error

**Expected Result**: No error for valid phone

**Priority**: High

---

#### TC-VAL-002: Regex Validation Fail
**Preconditions**: ImportService configured  
**Test Steps**:
1. Set validator ['phone' => '/^[0-9]+$/']
2. Import row with letters in phone
3. Verify error collected

**Expected Result**: Error in getErrors()

**Priority**: High

---

#### TC-VAL-003: Enum Validation Pass
**Preconditions**: ImportService configured  
**Test Steps**:
1. Set validator ['status' => ['pending', 'done']]
2. Import row with 'pending'
3. Verify no error

**Expected Result**: No error for valid status

**Priority**: High

---

#### TC-VAL-004: Enum Validation Fail
**Preconditions**: ImportService configured  
**Test Steps**:
1. Set validator ['status' => ['pending', 'done']]
2. Import row with 'invalid'
3. Verify error collected

**Expected Result**: Error for invalid status

**Priority**: High

---

#### TC-VAL-005: Callable Validator
**Preconditions**: ImportService configured  
**Test Steps**:
1. Set validator ['total' => fn($v) => $v > 0]
2. Import row with total <= 0
3. Verify error collected

**Expected Result**: Error for failed validation

**Priority**: Medium

---

### 3.5 Wizard Tests (TC-WIZ)

#### TC-WIZ-001: Wizard Initial State
**Preconditions**: ImportWizard created  
**Test Steps**:
1. Check step()
2. Verify returns 0

**Expected Result**: Initial state is 0

**Priority**: High

---

#### TC-WIZ-002: Wizard Load File
**Preconditions**: Valid file exists  
**Test Steps**:
1. Call loadFile(filePath)
2. Check step()
3. Verify returns 1

**Expected Result**: Step is 1 after load

**Priority**: High

---

#### TC-WIZ-003: Wizard Get Preview
**Preconditions**: File loaded  
**Test Steps**:
1. Load file
2. Call getFilePreview()
3. Verify has headers and rows

**Expected Result**: Preview data returned

**Priority**: High

---

#### TC-WIZ-004: Wizard Set Mapping
**Preconditions**: File loaded  
**Test Steps**:
1. Load file
2. Call setMapping(mapping)
3. Check step()

**Expected Result**: Step is 2

**Priority**: High

---

#### TC-WIZ-005: Wizard Auto-Map
**Preconditions**: File loaded  
**Test Steps**:
1. Load file
2. Call autoMap(targetFields)
3. Verify mapping set

**Expected Result**: Mapping generated

**Priority**: High

---

#### TC-WIZ-006: Wizard Execute
**Preconditions**: File and mapping set  
**Test Steps**:
1. Load file
2. Set mapping
3. Call execute()
4. Verify result

**Expected Result**: Import result returned

**Priority**: High

---

## 4. Performance Tests

### 4.1 Import Performance
| Test | Size | Target |
|------|------|--------|
| CSV import | 10,000 rows | < 5s |
| JSON import | 1MB | < 1s |
| Excel import | 5,000 rows | < 30s |

### 4.2 Export Performance
| Test | Size | Target |
|------|------|--------|
| CSV export | 10,000 rows | < 3s |
| JSON export | 10,000 rows | < 2s |
| Excel export | 5,000 rows | < 15s |

---

## 5. Test Data

### 5.1 Sample CSV
```csv
first_name,last_name,email,phone
John,Doe,john@example.com,5551234567
Jane,Smith,jane@example.com,5559876543
```

### 5.2 Sample JSON
```json
[
  {"first_name": "John", "last_name": "Doe", "email": "john@example.com"},
  {"first_name": "Jane", "last_name": "Smith", "email": "jane@example.com"}
]
```

### 5.3 Sample XML
```xml
<?xml version="1.0"?>
<contacts>
  <contact>
    <first_name>John</first_name>
    <last_name>Doe</last_name>
    <email>john@example.com</email>
  </contact>
</contacts>
```

---

## 6. Test Execution

### 6.1 Run Commands
```bash
# Run all tests
./vendor/bin/phpunit

# Run import tests only
./vendor/bin/phpunit tests/Unit/ImportServiceTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### 6.2 Pass Criteria
| Test Type | Required |
|-----------|----------|
| Unit Tests | 100% |
| Integration Tests | 95% |

---

## 7. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| PhpSpreadsheet not installed | Medium | High | Graceful degradation |
| Large file memory issues | Medium | High | Stream processing |
| Encoding problems | Low | Medium | UTF-8 validation |

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*