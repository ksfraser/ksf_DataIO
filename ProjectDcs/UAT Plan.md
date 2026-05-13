# ksf_DataIO - UAT Plan

## Document Information
- **Module**: ksf_DataIO (Data Import/Export)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Introduction

### 1.1 Purpose
This UAT Plan defines user acceptance tests for the ksf_DataIO module from an end-user perspective.

### 1.2 Scope
- CSV import/export
- JSON import/export
- Excel import/export
- Field mapping and templates
- Import wizard workflow

### 1.3 Test Environment
- **PHP**: 8.0+
- **Browser**: Chrome/Firefox latest
- **Files**: Sample CSV, JSON, Excel files

### 1.4 Stakeholders
- Data Entry Clerks
- System Administrators
- Report Analysts
- Developers

---

## 2. UAT Test Cases

### 2.1 CSV Import (UAT-CSV)

#### UAT-CSV-001: Import Basic CSV
**Objective**: Verify basic CSV import works

**Test Scenario**:
1. Create CSV file with 10 customer records
2. Use ImportService to import
3. Verify data imported correctly

**Expected Result**: Data array returned with 10 items

**Acceptance Criteria**:
- [ ] CSV parsed correctly
- [ ] Headers detected
- [ ] Data matches source file

---

#### UAT-CSV-002: Import CSV with Custom Delimiter
**Objective**: Verify semicolon delimiter works

**Test Scenario**:
1. Create CSV with semicolon delimiter
2. Configure ImportService delimiter
3. Import file
4. Verify parsed correctly

**Expected Result**: Data correctly parsed

**Acceptance Criteria**:
- [ ] Semicolons recognized
- [ ] No extra columns
- [ ] Data intact

---

#### UAT-CSV-003: CSV Import with Empty Rows
**Objective**: Verify empty rows handled

**Test Scenario**:
1. Create CSV with empty rows
2. Enable skip_empty_rows
3. Import file

**Expected Result**: Empty rows skipped

**Acceptance Criteria**:
- [ ] Empty rows not in result
- [ ] Data rows complete
- [ ] No errors

---

### 2.2 JSON Import (UAT-JSON)

#### UAT-JSON-001: Import Valid JSON
**Objective**: Verify JSON import works

**Test Scenario**:
1. Create JSON file with customer array
2. Import via fromJson()
3. Verify data returned

**Expected Result**: Array of customer objects

**Acceptance Criteria**:
- [ ] JSON parsed
- [ ] All records imported
- [ ] Structure preserved

---

#### UAT-JSON-002: Import Invalid JSON
**Objective**: Verify error handling for bad JSON

**Test Scenario**:
1. Create file with invalid JSON
2. Import via fromJson()
3. Check getErrors()

**Expected Result**: Error collected, empty result

**Acceptance Criteria**:
- [ ] Error reported
- [ ] No crash
- [ ] Meaningful error message

---

### 2.3 Excel Import (UAT-EXCEL)

#### UAT-EXCEL-001: Import Excel File
**Objective**: Verify Excel import works

**Test Scenario**:
1. Create Excel file with data
2. Import via fromExcel()
3. Verify data returned

**Expected Result**: Data array from spreadsheet

**Acceptance Criteria**:
- [ ] Excel file read
- [ ] Headers extracted
- [ ] Data rows imported

---

#### UAT-EXCEL-002: Import Large Excel
**Objective**: Verify performance with 1000 rows

**Test Scenario**:
1. Create Excel with 1000 rows
2. Import file
3. Verify completion time < 30s

**Expected Result**: All rows imported

**Acceptance Criteria**:
- [ ] Completes in reasonable time
- [ ] No memory errors
- [ ] All rows present

---

### 2.4 Field Mapping (UAT-MAP)

#### UAT-MAP-001: Set Field Mapping
**Objective**: Verify custom mapping works

**Test Scenario**:
1. CSV has columns FirstName, LastName
2. Set mapping to first_name, last_name
3. Import file
4. Verify mapped field names

**Expected Result**: Data has mapped keys

**Acceptance Criteria**:
- [ ] Mapping applied
- [ ] Original keys replaced
- [ ] Data values unchanged

---

#### UAT-MAP-002: Auto-Map Fields
**Objective**: Verify auto-mapping suggestions

**Test Scenario**:
1. CSV has columns fname, lname, emailaddr
2. Target fields are firstname, lastname, email
3. Call autoMap()
4. Verify mapping suggested

**Expected Result**: Mapping with matches

**Acceptance Criteria**:
- [ ] fname->firstname matched
- [ ] lname->lastname matched
- [ ] emailaddr->email matched

---

#### UAT-MAP-003: Save Mapping Template
**Objective**: Verify mapping can be saved

**Test Scenario**:
1. Create mapping
2. Call createMapping(name, mapping)
3. Retrieve mapping by name
4. Verify matches

**Expected Result**: Mapping saved and retrieved

**Acceptance Criteria**:
- [ ] Mapping saved to DB
- [ ] Can be retrieved
- [ ] Data matches

---

#### UAT-MAP-004: Use Saved Mapping
**Objective**: Verify saved mapping can be applied

**Test Scenario**:
1. Load saved mapping
2. Apply to import
3. Verify mapping works

**Expected Result**: Import uses saved mapping

**Acceptance Criteria**:
- [ ] Mapping loaded
- [ ] Applied to import
- [ ] Correct result

---

### 2.5 Validation (UAT-VAL)

#### UAT-VAL-001: Validate Required Fields
**Objective**: Verify required field validation

**Test Scenario**:
1. Set validator for email field
2. Import row without email
3. Verify error collected

**Expected Result**: Validation error reported

**Acceptance Criteria**:
- [ ] Error captured
- [ ] Row still processed
- [ ] Error message clear

---

#### UAT-VAL-002: Validate Email Format
**Objective**: Verify email validation

**Test Scenario**:
1. Set email validator
2. Import row with "notanemail"
3. Verify error

**Expected Result**: Email format error

**Acceptance Criteria**:
- [ ] Invalid email caught
- [ ] Valid email accepted
- [ ] Error message identifies field

---

#### UAT-VAL-003: Review Import Errors
**Objective**: Verify error collection and retrieval

**Test Scenario**:
1. Import data with multiple errors
2. After import, call getErrors()
3. Review error list

**Expected Result**: All errors listed

**Acceptance Criteria**:
- [ ] All errors captured
- [ ] Row numbers correct
- [ ] Errors meaningful

---

### 2.6 Export (UAT-EXPORT)

#### UAT-EXPORT-001: Export to CSV
**Objective**: Verify CSV export works

**Test Scenario**:
1. Create data array
2. Export to CSV
3. Open file and verify content

**Expected Result**: CSV file with data

**Acceptance Criteria**:
- [ ] File created
- [ ] Headers present
- [ ] Data correct

---

#### UAT-EXPORT-002: Export to Excel
**Objective**: Verify Excel export works

**Test Scenario**:
1. Create data array
2. Export to Excel
3. Open file in Excel app

**Expected Result**: Excel file with data

**Acceptance Criteria**:
- [ ] File created
- [ ] Opens in Excel
- [ ] Data correct

---

#### UAT-EXPORT-003: Export to JSON
**Objective**: Verify JSON export works

**Test Scenario**:
1. Create data array
2. Export to JSON
3. Verify JSON valid

**Expected Result**: Valid JSON file

**Acceptance Criteria**:
- [ ] Valid JSON
- [ ] All records present
- [ ] Structure correct

---

#### UAT-EXPORT-004: Export CSV with Custom Delimiter
**Objective**: Verify delimiter customization

**Test Scenario**:
1. Create ExportService with delimiter=';'
2. Export to CSV
3. Verify semicolons used

**Expected Result**: Semicolon-delimited file

**Acceptance Criteria**:
- [ ] Delimiter applied
- [ ] File parses correctly
- [ ] No data corruption

---

### 2.7 Import Wizard (UAT-WIZ)

#### UAT-WIZ-001: Complete Import Workflow
**Objective**: Verify full wizard flow

**Test Scenario**:
1. Create ImportWizard
2. Load file
3. Preview data
4. Set mapping
5. Execute import
6. Review results

**Expected Result**: Complete import workflow

**Acceptance Criteria**:
- [ ] Step progression works
- [ ] Preview shows data
- [ ] Mapping applied
- [ ] Import completes
- [ ] Results accurate

---

#### UAT-WIZ-002: Use Saved Mapping in Wizard
**Objective**: Verify saved mapping in wizard

**Test Scenario**:
1. Create ImportWizard
2. Load file
3. Call useSavedMapping(id)
4. Execute import

**Expected Result**: Saved mapping applied

**Acceptance Criteria**:
- [ ] Mapping loaded
- [ ] Applied to import
- [ ] Correct result

---

#### UAT-WIZ-003: Auto-Map in Wizard
**Objective**: Verify auto-mapping in wizard

**Test Scenario**:
1. Create ImportWizard
2. Load file
3. Call autoMap(targetFields)
4. Execute import

**Expected Result**: Auto-mapped import

**Acceptance Criteria**:
- [ ] Suggestions provided
- [ ] Can be used directly
- [ ] Import works

---

### 2.8 XML Import (UAT-XML)

#### UAT-XML-001: Import Valid XML
**Objective**: Verify XML import works

**Test Scenario**:
1. Create XML file with data
2. Import via fromXml()
3. Verify data returned

**Expected Result**: Data array from XML

**Acceptance Criteria**:
- [ ] XML parsed
- [ ] Elements extracted
- [ ] Data correct

---

## 3. Sign-Off Criteria

### 3.1 Test Completion Metrics
- **Total UAT Test Cases**: 20+
- **Passed**: [ ]
- **Failed**: [ ]
- **Pass Rate**: [ ]%

### 3.2 Critical Path Tests (Must Pass)
- [ ] Basic CSV import
- [ ] Field mapping
- [ ] Basic CSV export
- [ ] Import wizard flow

### 3.3 Sign-Off Table
| Test Area | Tester | Date | Result |
|-----------|--------|------|--------|
| CSV Import | | | Pass/Fail |
| JSON Import | | | Pass/Fail |
| Excel Import | | | Pass/Fail |
| Field Mapping | | | Pass/Fail |
| Validation | | | Pass/Fail |
| Export | | | Pass/Fail |
| Import Wizard | | | Pass/Fail |
| XML Import | | | Pass/Fail |

---

## 4. Defect Reporting

### 4.1 Severity Levels
- **Critical**: Data loss, corruption
- **High**: Feature not working
- **Medium**: Feature partially working
- **Low**: Cosmetic issue

### 4.2 Defect Report Template
```
ID: [Number]
Test Case: [UAT-XXX-###]
Environment: [Details]
Expected: [What should happen]
Actual: [What happened]
Severity: [Critical/High/Medium/Low]
Tester: [Name]
Date: [Date]
```

---

## 5. Success Criteria

### 5.1 Go/No-Go Decision
Module passes UAT when:
1. 100% critical test cases pass
2. 90% overall test cases pass
3. No Critical defects open
4. Business sign-off obtained

### 5.2 Issue Resolution
| Severity | Resolution |
|----------|------------|
| Critical | Must fix before release |
| High | Should fix before release |
| Medium | Release OK with known issues |
| Low | Can defer |

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*