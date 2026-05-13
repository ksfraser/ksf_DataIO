<?php

namespace Ksfraser\DataIO\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\DataIO\ImportService;
use Ksfraser\DataIO\ExportService;
use Ksfraser\DataIO\ImportWizard;
use Ksfraser\DataIO\FieldMappingService;
use Ksfraser\DataIO\DataIOService;
use Ksfraser\DataIO\ImportUI;

class ImportServiceTest extends TestCase
{
    private $testFile;
    private $testData = [
        ['id' => 1, 'name' => 'John', 'email' => 'john@test.com'],
        ['id' => 2, 'name' => 'Jane', 'email' => 'jane@test.com'],
        ['id' => 3, 'name' => 'Bob', 'email' => 'bob@test.com'],
    ];

    protected function setUp(): void
    {
        $this->testFile = sys_get_temp_dir() . '/ksf_test_' . time() . '.csv';
        $handle = fopen($this->testFile, 'w');
        fputcsv($handle, ['id', 'name', 'email']);
        foreach ($this->testData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testLoadCsvFile(): void
    {
        $service = new ImportService();
        $result = $service->fromCsv($this->testFile);

        $this->assertCount(3, $result);
        $this->assertEquals('John', $result[0]['name']);
    }

    public function testFieldMapping(): void
    {
        $service = new ImportService();
        $service->setFieldMapping([
            'Name' => 'name',
            'Email' => 'email',
        ]);

        $testFile = sys_get_temp_dir() . '/map_test.csv';
        file_put_contents($testFile, "Name,Email\nJohn Doe,john@test.com\n");

        $result = $service->fromCsv($testFile);
        $this->assertEquals('John Doe', $result[0]['name']);
        
        unlink($testFile);
    }

    public function testDefaultValues(): void
    {
        $service = new ImportService();
        $service->setDefaults(['status' => 'new', 'active' => 1]);

        $testFile = sys_get_temp_dir() . '/default_test.csv';
        file_put_contents($testFile, "name\nJohn\n");

        $result = $service->fromCsv($testFile);
        $this->assertEquals('new', $result[0]['status']);
        $this->assertEquals(1, $result[0]['active']);
        
        unlink($testFile);
    }

    public function testSkipEmptyRows(): void
    {
        $service = new ImportService(['skip_empty_rows' => true]);

        $testFile = sys_get_temp_dir() . '/skip_empty.csv';
        file_put_contents($testFile, "name,email\nJohn,john@test.com\n\n\nJane,jane@test.com\n");

        $result = $service->fromCsv($testFile);
        $this->assertCount(2, $result);
        
        unlink($testFile);
    }

    public function testJsonImport(): void
    {
        $testFile = sys_get_temp_dir() . '/json_test.json';
        file_put_contents($testFile, json_encode($this->testData));

        $service = new ImportService();
        $result = $service->fromJson($testFile);

        $this->assertCount(3, $result);
        $this->assertEquals('john@test.com', $result[0]['email']);
        
        unlink($testFile);
    }
}

class ExportServiceTest extends TestCase
{
    private $testData = [
        ['id' => 1, 'name' => 'John', 'email' => 'john@test.com'],
        ['id' => 2, 'name' => 'Jane', 'email' => 'jane@test.com'],
    ];

    public function testExportToCsv(): void
    {
        $service = new ExportService(['delimiter' => ',']);
        $testFile = sys_get_temp_dir() . '/export_test.csv';

        $result = $service->toCsv($this->testData, $testFile, ['id', 'name', 'email']);

        $this->assertTrue($result);
        $this->assertFileExists($testFile);

        $content = file_get_contents($testFile);
        $this->assertStringContainsString('John', $content);
        $this->assertStringContainsString('Jane', $content);

        unlink($testFile);
    }

    public function testExportToJson(): void
    {
        $service = new ExportService();
        $testFile = sys_get_temp_dir() . '/export_test.json';

        $result = $service->toJson($this->testData, $testFile);

        $this->assertTrue($result);
        
        $content = json_decode(file_get_contents($testFile), true);
        $this->assertCount(2, $content);
        
        unlink($testFile);
    }

    public function testExportWithBom(): void
    {
        $service = new ExportService(['bom' => true]);
        $testFile = sys_get_temp_dir() . '/bom_test.csv';

        $service->toCsv($this->testData, $testFile);

        $content = file_get_contents($testFile);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        unlink($testFile);
    }
}

class ImportWizardTest extends TestCase
{
    public function testLoadFileAndPreview(): void
    {
        $testFile = sys_get_temp_dir() . '/wizard_test.csv';
        file_put_contents($testFile, "name,email\nJohn,john@test.com\nJane,jane@test.com\n");

        $wizard = ImportWizard::create();
        $preview = $wizard->loadFile($testFile)->getFilePreview();

        $this->assertCount(2, $preview['headers']);
        $this->assertContains('name', $preview['headers']);
        $this->assertCount(2, $preview['rows']);

        unlink($testFile);
    }

    public function testSetMapping(): void
    {
        $testFile = sys_get_temp_dir() . '/mapping_test.csv';
        file_put_contents($testFile, "name,email\nJohn,john@test.com\n");

        $wizard = ImportWizard::create();
        $wizard->loadFile($testFile);
        $wizard->setMapping(['name' => 'full_name', 'email' => 'mail']);

        $mapping = $wizard->getMapping();
        $this->assertEquals('full_name', $mapping['name']);
        $this->assertEquals('mail', $mapping['email']);

        unlink($testFile);
    }

    public function testAutoMap(): void
    {
        $inputFields = ['FirstName', 'LastName', 'EmailAddress'];
        $targetFields = ['first_name', 'last_name', 'email'];

        $wizard = ImportWizard::create();
        $wizard->loadFile('/dev/null'); // dummy
        $wizard->autoMap($targetFields);

        $mapping = $wizard->getMapping();
        $this->assertNotEmpty($mapping);
    }

    public function testExecuteWithProcessor(): void
    {
        $testFile = sys_get_temp_dir() . '/processor_test.csv';
        file_put_contents($testFile, "name,email\nJohn,john@test.com\n");

        $wizard = ImportWizard::create();
        $wizard->loadFile($testFile);
        $wizard->setMapping(['name' => 'name', 'email' => 'email']);

        $result = $wizard->execute(function($row) {
            $row['name'] = strtoupper($row['name']);
            return $row;
        });

        $this->assertEquals('JOHN', $result['data'][0]['name']);

        unlink($testFile);
    }
}

class FieldMappingServiceTest extends TestCase
{
    public function testAutoMapFuzzyMatching(): void
    {
        $input = ['first_name', 'last_name', 'email_addr'];
        $target = ['first_name', 'last_name', 'email'];

        $svc = new FieldMappingService();
        $mapping = $svc->autoMap($input, $target);

        $this->assertEquals('first_name', $mapping['first_name']);
        $this->assertEquals('last_name', $mapping['last_name']);
        $this->assertEquals('email', $mapping['email_addr']);
    }

    public function testApplyMapping(): void
    {
        $data = [
            ['Name' => 'John', 'Email' => 'john@test.com'],
            ['Name' => 'Jane', 'Email' => 'jane@test.com'],
        ];

        $mapping = ['Name' => 'name', 'Email' => 'email'];

        $svc = new FieldMappingService();
        $result = $svc->applyMapping($data, $mapping);

        $this->assertEquals('John', $result[0]['name']);
        $this->assertEquals('john@test.com', $result[0]['email']);
    }
}

class DataIOServiceTest extends TestCase
{
    public function testAutoDetectFormat(): void
    {
        $testFile = sys_get_temp_dir() . '/autodetect.csv';
        file_put_contents($testFile, "id,name\n1,John\n");

        $service = DataIOService::create();
        $result = $service->import($testFile);

        $this->assertNotEmpty($result->data);
        $this->assertFalse($result->hasErrors());

        unlink($testFile);
    }

    public function testChainOperations(): void
    {
        $testCsv = sys_get_temp_dir() . '/chain.csv';
        file_put_contents($testCsv, "name,email\nJohn,john@test.com\n");

        $service = DataIOService::create();
        $service->setFieldMapping(['Name' => 'name']);
        
        $imported = $service->import($testCsv, function($row) {
            $row['name'] = strtoupper($row['name']);
            return $row;
        });

        $expFile = sys_get_temp_dir() . '/chain_exp.csv';
        $service->export($imported->data, $expFile, ['name']);

        $this->assertFileExists($expFile);
        
        $content = file_get_contents($expFile);
        $this->assertStringContainsString('JOHN', $content);

        unlink($testCsv);
        unlink($expFile);
    }
}

class ImportUITest extends TestCase
{
    public function testRenderStep1(): void
    {
        $ui = ImportUI::create([
            'module' => 'test',
            'target_fields' => ['id', 'name'],
        ]);

        ob_start();
        $ui->renderShortcode(['id', 'name'], 'test');
        $output = ob_get_clean();

        $this->assertStringContainsString('Step 1', $output);
        $this->assertStringContainsString('Select File', $output);
    }

    public function testRegisterShortcode(): void
    {
        $ui = ImportUI::create();
        $ui->registerShortcode('test_import', ['id', 'name'], 'test');

        $this->assertTrue(shortcode_exists('test_import'));
    }
}

// PHPUnit bootstrap require_once __DIR__ . '/../vendor/autoload.php';
// phpunit -c phpunit.xml