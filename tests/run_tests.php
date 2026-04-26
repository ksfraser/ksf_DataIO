#!/usr/bin/env php
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DataIO Integration Tests ===\n\n";

require_once __DIR__ . '/../src/Ksfraser/DataIO/ImportService.php';
require_once __DIR__ . '/../src/Ksfraser/DataIO/ExportService.php';
require_once __DIR__ . '/../src/Ksfraser/DataIO/ImportWizard.php';
require_once __DIR__ . '/../src/Ksfraser/DataIO/DataIOService.php';

use Ksfraser\DataIO\ImportService;
use Ksfraser\DataIO\ExportService;
use Ksfraser\DataIO\ImportWizard;
use Ksfraser\DataIO\DataIOService;
use Ksfraser\DataIO\FieldMappingService;

if (!defined('TB_PREF')) define('TB_PREF', '');

if (!function_exists('db_escape')) {
    function db_escape($val) { return "'" . addslashes($val) . "'"; }
}

$passed = 0;
$failed = 0;

function test($name, $condition, $message = '') {
    global $passed, $failed;
    if ($condition) {
        echo "\033[32m✓\033[0m $name\n";
        $passed++;
    } else {
        echo "\033[31m✗\033[0m $name" . ($message ? " - $message" : "") . "\n";
        $failed++;
    }
}

function ksf_test_autoload($class) {
    $prefix = 'Ksfraser\\DataIO\\';
    $base = __DIR__ . '/../src/Ksfraser/DataIO/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $rel = substr($class, $len);
    $file = $base . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) require $file;
}

spl_autoload_register('ksf_test_autoload');

$testData = [['id' => 1, 'name' => 'John', 'email' => 'john@test.com'], ['id' => 2, 'name' => 'Jane', 'email' => 'jane@test.com']];
$testFile = sys_get_temp_dir() . '/ksf_test.csv';
$h = fopen($testFile, 'w'); fputcsv($h, ['id', 'name', 'email']); foreach ($testData as $r) fputcsv($h, $r); fclose($h);

echo "=== ImportService Tests ===\n";
$svc = new ImportService(); $r = $svc->fromCsv($testFile); 
test('Load CSV', count($r) >= 2);
test('First record has data', isset($r[0]['name']) && $r[0]['name'] === 'John');

$svc2 = new ImportService(); $svc2->setFieldMapping(['Name' => 'name']);
file_put_contents($testFile, "Name,Email\nJohn Doe,john@test.com\n");
$r2 = $svc2->fromCsv($testFile);
test('Field Mapping', $r2[0]['name'] === 'John Doe');

echo "\n=== ExportService Tests ===\n";
$es = new ExportService(); $ef = sys_get_temp_dir() . '/export.csv';
$es->toCsv($testData, $ef, ['id', 'name', 'email']);
$c = file_get_contents($ef);
test('Export CSV', strpos($c, 'John') !== false);

echo "\n=== ImportWizard Tests ===\n";
$w = ImportWizard::create(); $p = $w->loadFile($testFile)->getFilePreview();
test('Load Preview - returns array', is_array($p));

$w2 = ImportWizard::create(); $w2->loadFile($testFile); $w2->setMapping(['name' => 'full_name']);
$m = $w2->getMapping();
test('Set Mapping', $m['name'] === 'full_name');

echo "\n=== FieldMappingService Tests ===\n";
$fm = new FieldMappingService();
$a = $fm->autoMap(['first_name', 'email_addr'], ['first_name', 'email']);
test('AutoMap fuzzy', $a['first_name'] === 'first_name' && $a['email_addr'] === 'email');

$d = [['Name' => 'John']]; $ap = $fm->applyMapping($d, ['Name' => 'name']);
test('Apply Mapping', $ap[0]['name'] === 'John');

echo "\n=== DataIOService Tests ===\n";
$dio = DataIOService::create(); $dr = $dio->import($testFile);
test('Import works', count($dr->data) >= 1 || isset($dr->first()['name']));

echo "\n=== Results ===\n";
echo "\033[32mPassed: $passed\033[0m\n";
echo $failed > 0 ? "\033[31mFailed: $failed\033[0m\n" : "\033[32mFailed: 0\033[0m\n";

@unlink($testFile); @unlink($ef);
exit($failed > 0 ? 1 : 0);