<?php

echo "=== DataIO Library Demo ===\n\n";

$demoData = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active'],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'active'],
    ['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com', 'status' => 'inactive'],
];

spl_autoload_register(function ($class) {
    $prefix = 'Ksfraser\\DataIO\\';
    $base_dir = __DIR__ . '/src/Ksfraser/DataIO/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use Ksfraser\DataIO\ExportService;
use Ksfraser\DataIO\ImportService;

echo "1. CSV Export:\n";
$exporter = new ExportService(['delimiter' => ',', 'bom' => true]);
$exporter->toCsv($demoData, '/tmp/demo_export.csv', ['id', 'name', 'email', 'status']);
echo file_get_contents('/tmp/demo_export.csv') . "\n";

echo "2. JSON Export:\n";
$exporter->toJson($demoData, '/tmp/demo_export.json');
echo file_get_contents('/tmp/demo_export.json') . "\n";

echo "3. CSV Import:\n";
$importer = new ImportService();
$importer->setFieldMapping(['Email' => 'email', 'Name' => 'name']);
$importer->setDefaults(['status' => 'new']);

$testCsv = "name,email,salary\nJohn Doe,john@example.com,50000\nJane Smith,jane@example.com,60000\n";
file_put_contents('/tmp/test_import.csv', $testCsv);

$imported = $importer->fromCsv('/tmp/test_import.csv');
echo "Imported " . count($imported) . " rows:\n";
print_r($imported);

echo "\n4. Field Mapping & Defaults:\n";
$testCsv2 = "title,email\nMr,john@example.com\nMs,jane@example.com\n";
file_put_contents('/tmp/test2.csv', $testCsv2);

$importer2 = new ImportService();
$importer2->setFieldMapping(['title' => 'prefix', 'email' => 'email']);
$importer2->setDefaults(['status' => 'active', 'source' => 'import']);

$imported2 = $importer2->fromCsv('/tmp/test2.csv');
foreach ($imported2 as $row) {
    echo "  - {$row['prefix']} {$row['email']} [{$row['status']}]\n";
}

echo "\n=== Done ===\n";