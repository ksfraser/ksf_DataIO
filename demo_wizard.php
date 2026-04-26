<?php

echo "=== Import Wizard 2-Step Workflow Demo ===\n\n";

spl_autoload_register(function ($class) {
    $prefix = 'Ksfraser\\DataIO\\';
    $base_dir = __DIR__ . '/src/Ksfraser/DataIO/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use Ksfraser\DataIO\ImportWizard;
use Ksfraser\DataIO\FieldMappingService;

file_put_contents('/tmp/employees.csv', "EmpNo,FullName,Email,Department,HireDate\n101,John Smith,john@company.com,IT,2023-01-15\n102,Jane Doe,jane@company.com,HR,2023-02-20\n103,Bob Wilson,bob@company.com,Sales,2023-03-10\n");

echo "STEP 1: Load File & Preview\n";
$wizard = ImportWizard::create();
$preview = $wizard->loadFile('/tmp/employees.csv')->getFilePreview();

echo "Input columns: " . implode(', ', $preview['headers']) . "\n";
echo "Preview rows:\n";
foreach ($preview['rows'] as $i => $row) {
    echo "  Row " . ($i + 1) . ": " . json_encode($row) . "\n";
}

echo "\nSTEP 2: Map Fields\n";
$targetFields = ['employee_id', 'name', 'email', 'dept', 'hire_date'];

echo "Target FA fields: " . implode(', ', $targetFields) . "\n";

$mapping = [
    'EmpNo' => 'employee_id',
    'FullName' => 'name',
    'Email' => 'email',
    'Department' => 'dept',
    'HireDate' => 'hire_date',
];

$wizard->setMapping($mapping);

echo "Mapping created:\n";
foreach ($mapping as $input => $output) {
    echo "  {$input} -> {$output}\n";
}

echo "\nAuto-Mapping Example:\n";
$testInput = ['first_name', 'last_name', 'email_addr'];
$testTarget = ['first_name', 'last_name', 'email'];
$autoMap = (new FieldMappingService())->autoMap($testInput, $testTarget);
echo json_encode($autoMap, JSON_PRETTY_PRINT) . "\n";

echo "\nSTEP 3: Import\n";
$imported = $wizard->execute();
echo "Imported " . ($imported['count'] ?? count($imported['data'] ?? [])) . " records\n";

foreach ($imported['data'] ?? [] as $record) {
    echo "  " . json_encode($record) . "\n";
}

echo "\n=== Done ===\n";