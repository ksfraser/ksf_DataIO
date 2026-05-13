# ksf_DataIO - Use Case

## Document Information
- **Module**: ksf_DataIO (Data Import/Export)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Use Case Overview

### 1.1 Actors
| Actor | Description |
|-------|-------------|
| Data Entry Clerk | Imports standard data files |
| System Administrator | Manages imports, creates templates |
| Report Analyst | Exports data for analysis |
| Developer | Integrates DataIO services |

### 1.2 Use Case Categories
- Import Operations
- Export Operations
- Mapping Management
- Wizard Workflow

---

## 2. Import Use Cases

### UC-IMP-001: Import CSV File

**Actor**: Data Entry Clerk  
**Trigger**: User uploads CSV file

**Pre-conditions**:
- CSV file exists and is accessible
- File is valid CSV format

**Steps**:
1. User creates ImportService instance
2. User optionally sets config (delimiter, etc.)
3. User calls fromCsv(filePath)
4. Service parses file
5. Service applies mappings and defaults
6. Service validates each row
7. Service returns array of rows

**Post-conditions**:
- Rows returned as array
- Errors collected for review

**Error Handling**:
- File not found: Return empty array, log error
- Parse error: Collect error, continue processing

---

### UC-IMP-002: Import with Callback

**Actor**: Data Entry Clerk  
**Trigger**: User wants row-by-row processing

**Pre-conditions**:
- CSV file exists

**Steps**:
1. User creates ImportService
2. User defines callback function
3. User calls fromCsv(filePath, callback)
4. For each row:
   - Callback receives (rowData, rowNumber)
   - Callback can modify or reject row
   - If callback returns false, row skipped
5. Return array of callback-processed rows

**Callback Example**:
```php
$result = $import->fromCsv('data.csv', function($row, $num) {
    if (empty($row['email'])) {
        return false; // Skip rows without email
    }
    $row['email'] = strtolower($row['email']);
    return $row;
});
```

**Post-conditions**:
- Only valid rows returned
- Processing can be cancelled per-row

---

### UC-IMP-003: Import JSON File

**Actor**: Data Entry Clerk  
**Trigger**: User imports JSON data

**Pre-conditions**:
- JSON file exists and contains array

**Steps**:
1. User calls fromJson(filePath)
2. Service reads file content
3. Service json_decode() content
4. Service validates result is array
5. Service applies mappings and validation
6. Return processed rows

**Post-conditions**:
- JSON data converted to rows

**Failure Scenarios**:
- F1: Invalid JSON → Collect error, return empty
- F2: Not an array → Collect error, return empty

---

### UC-IMP-004: Import Excel File

**Actor**: Data Entry Clerk  
**Trigger**: User imports Excel spreadsheet

**Pre-conditions**:
- Excel file exists (.xlsx or .xls)
- PhpSpreadsheet is installed

**Steps**:
1. User calls fromExcel(filePath)
2. Service loads workbook via PhpSpreadsheet
3. Service reads active sheet
4. Service extracts headers from first row
5. Service extracts data from remaining rows
6. Service converts to row arrays
7. Service applies mappings and validation
8. Return processed rows

**Post-conditions**:
- Excel data converted to rows

**Failure Scenarios**:
- F1: PhpSpreadsheet missing → Return empty, error logged
- F2: Invalid file → Exception thrown

---

### UC-IMP-005: Import XML File

**Actor**: Data Entry Clerk  
**Trigger**: User imports XML data

**Pre-conditions**:
- XML file exists and is well-formed

**Steps**:
1. User calls fromXml(filePath, 'items', 'item')
2. Service reads and parses XML
3. Service iterates over item elements
4. Service converts each to associative array
5. Service applies mappings and validation
6. Return processed rows

**Post-conditions**:
- XML data converted to rows

---

### UC-IMP-006: Import with Field Mapping

**Actor**: Data Entry Clerk  
**Trigger**: User needs to map source to target fields

**Pre-conditions**:
- Import data has different field names than target

**Steps**:
1. User creates ImportService
2. User calls setFieldMapping(['Source' => 'Target'])
3. User calls fromCsv(filePath)
4. Service maps source fields to target fields
5. Service returns rows with target field names

**Post-conditions**:
- Rows have target field names

---

## 3. Export Use Cases

### UC-EXP-001: Export to CSV

**Actor**: Report Analyst  
**Trigger**: User needs CSV export

**Pre-conditions**:
- Data array available

**Steps**:
1. User creates ExportService
2. User optionally sets config (delimiter)
3. User calls toCsv(data, filePath)
4. Service writes CSV file
5. Return boolean success

**Post-conditions**:
- CSV file created

---

### UC-EXP-002: Export to Excel

**Actor**: Report Analyst  
**Trigger**: User needs Excel spreadsheet

**Pre-conditions**:
- Data array available
- PhpSpreadsheet installed

**Steps**:
1. User calls toExcel(data, filePath, headers)
2. Service creates spreadsheet
3. Service writes headers
4. Service writes data rows
5. Service auto-sizes columns
6. Service saves file
7. Return boolean success

**Post-conditions**:
- Excel file created

---

### UC-EXP-003: Stream CSV to Browser

**Actor**: Report Analyst  
**Trigger**: User wants to download without file

**Pre-conditions**:
- Data array available

**Steps**:
1. User calls streamCsv(data, headers)
2. Service sets HTTP headers
3. Service outputs CSV directly
4. Script exits

**Post-conditions**:
- File downloaded to browser

---

### UC-EXP-004: Export to JSON

**Actor**: Developer  
**Trigger**: User needs JSON data

**Pre-conditions**:
- Data array available

**Steps**:
1. User calls toJson(data, filePath, pretty: true)
2. Service json_encode() data
3. Service writes to file
4. Return boolean success

**Post-conditions**:
- JSON file created

---

### UC-EXP-005: Export to XML

**Actor**: Integration Developer  
**Trigger**: User needs XML data

**Pre-conditions**:
- Data array available

**Steps**:
1. User calls toXml(data, filePath, 'records', 'record')
2. Service creates SimpleXMLElement
3. Service adds root element
4. For each row: Service adds item element
5. Service saves file
6. Return boolean success

**Post-conditions**:
- XML file created

---

## 4. Mapping Management Use Cases

### UC-MAP-001: Save Field Mapping

**Actor**: System Administrator  
**Trigger**: User wants to save mapping template

**Pre-conditions**:
- FieldMappingService initialized

**Steps**:
1. User creates mapping array
2. User calls createMapping(name, mapping, description)
3. Service JSON encodes mapping
4. Service inserts into database
5. Service returns mapping ID

**Post-conditions**:
- Mapping saved to database
- Can be retrieved later

---

### UC-MAP-002: Load Saved Mapping

**Actor**: System Administrator  
**Trigger**: User wants to use saved mapping

**Pre-conditions**:
- Mapping exists in database

**Steps**:
1. User calls getMapping(id) or getMappingByName(name)
2. Service retrieves from database
3. Service formats mapping array
4. Return mapping array

**Post-conditions**:
- Mapping array available for use

---

### UC-MAP-003: Auto-Map Fields

**Actor**: Data Entry Clerk  
**Trigger**: User wants system to suggest mapping

**Pre-conditions**:
- Input and output fields available

**Steps**:
1. User calls autoMap(inputFields, outputFields)
2. Service normalizes field names
3. Service compares fields
4. Service returns matches

**Post-conditions**:
- Suggested mapping returned

---

### UC-MAP-004: List Available Mappings

**Actor**: Data Entry Clerk  
**Trigger**: User wants to see available templates

**Pre-conditions**:
- Some mappings saved

**Steps**:
1. User calls listMappings() or listMappingsForModule(module, fields)
2. Service queries database
3. Service formats results
4. Return array of mappings

**Post-conditions**:
- List of templates displayed

---

### UC-MAP-005: Delete Mapping Template

**Actor**: System Administrator  
**Trigger**: User wants to remove old template

**Pre-conditions**:
- Mapping exists

**Steps**:
1. User calls deleteMapping(id)
2. Service deletes from database
3. Return boolean success

**Post-conditions**:
- Mapping removed

---

## 5. Import Wizard Use Cases

### UC-WIZ-001: Complete Import Workflow

**Actor**: Data Entry Clerk  
**Trigger**: User wants guided import

**Pre-conditions**:
- Import file exists

**Steps**:
1. User creates ImportWizard
2. Step 1: User calls loadFile(filePath)
   - Service loads and previews file
   - Returns headers and sample rows
3. Step 2: User sets mapping
   - Option A: User calls autoMap(targetFields)
   - Option B: User calls useSavedMapping(id)
   - Option C: User calls setMapping(customMapping)
4. Step 3: User optionally saves mapping
   - User calls saveMapping(name, description)
5. Step 4: User executes import
   - User calls execute(callback)
   - Service processes all rows
   - Returns result with count and errors
6. User reviews results

**Post-conditions**:
- Import completed
- Results available for review

---

### UC-WIZ-002: Use Saved Mapping in Wizard

**Actor**: Data Entry Clerk  
**Trigger**: User wants to reuse template

**Pre-conditions**:
- Saved mapping exists

**Steps**:
1. User calls useSavedMapping(mappingId)
2. Wizard loads saved mapping
3. Wizard state set to step 2
4. User proceeds to execute

**Post-conditions**:
- Mapping applied
- No manual mapping needed

---

## 6. Validation Use Cases

### UC-VAL-001: Set Field Validators

**Actor**: Developer  
**Trigger**: User needs data validation

**Pre-conditions**:
- ImportService created

**Steps**:
1. User creates validators array
2. User calls setValidators(validators)
3. During import, validators applied to each row

**Post-conditions**:
- Invalid rows flagged in errors

---

### UC-VAL-002: Review Import Errors

**Actor**: Data Entry Clerk  
**Trigger**: User wants to see validation errors

**Pre-conditions**:
- Import completed with errors

**Steps**:
1. User calls getErrors()
2. Service returns error array
3. User reviews errors

**Error Format**: "Row {n}: {message}"

**Post-conditions**:
- Errors displayed for correction

---

## 7. Use Case Summary

| UC ID | Use Case | Actor | Priority |
|-------|----------|-------|----------|
| UC-IMP-001 | Import CSV File | Data Entry Clerk | Critical |
| UC-IMP-002 | Import with Callback | Data Entry Clerk | High |
| UC-IMP-003 | Import JSON File | Data Entry Clerk | High |
| UC-IMP-004 | Import Excel File | Data Entry Clerk | High |
| UC-IMP-005 | Import XML File | Data Entry Clerk | Medium |
| UC-IMP-006 | Import with Field Mapping | Data Entry Clerk | High |
| UC-EXP-001 | Export to CSV | Report Analyst | Critical |
| UC-EXP-002 | Export to Excel | Report Analyst | High |
| UC-EXP-003 | Stream CSV to Browser | Report Analyst | High |
| UC-EXP-004 | Export to JSON | Developer | Medium |
| UC-EXP-005 | Export to XML | Developer | Medium |
| UC-MAP-001 | Save Field Mapping | Admin | High |
| UC-MAP-002 | Load Saved Mapping | Admin | High |
| UC-MAP-003 | Auto-Map Fields | Data Entry Clerk | High |
| UC-MAP-004 | List Available Mappings | Data Entry Clerk | Medium |
| UC-MAP-005 | Delete Mapping Template | Admin | Low |
| UC-WIZ-001 | Complete Import Workflow | Data Entry Clerk | Critical |
| UC-WIZ-002 | Use Saved Mapping | Data Entry Clerk | High |
| UC-VAL-001 | Set Field Validators | Developer | High |
| UC-VAL-002 | Review Import Errors | Data Entry Clerk | High |

---

## 8. Sequence Diagrams

### 8.1 CSV Import Sequence
```
User           ImportService      File System
  |                 |                 |
  |--fromCsv()----->|                 |
  |                |--fopen()-------->|
  |                |<--handle---------|
  |                |--fgetcsv()------>|
  |                |<--row------------|
  |                |                  |
  |                |--applyMapping()--|
  |                |--validate()-----|
  |                |                  |
  |                |--callback()?---->|
  |                |<--processed------|
  |                |                  |
  |                |--fgetcsv()------>|
  |                |<--null-----------|
  |                |                  |
  |                |--fclose()------->|
  |<--rows---------|                  |
```

### 8.2 Export to Excel Sequence
```
User           ExportService      PhpSpreadsheet
  |                 |                   |
  |--toExcel()----->|                   |
  |                |--new Spreadsheet()|
  |                |<--spreadsheet-----|
  |                |                   |
  |                |--write headers-->|
  |                |--write rows----->|
  |                |                   |
  |                |--createWriter()-->|
  |                |--save()----------->|
  |                |                   |
  |<--true---------|                   |
```

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*