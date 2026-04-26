<?php

namespace Ksfraser\DataIO;

class ImportUI
{
    private array $config;
    private string $uploadDir;
    private array $steps = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->uploadDir = $config['upload_dir'] ?? wp_upload_dir()['path'] ?? '/tmp';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public static function create(array $config = []): self
    {
        return new self($config);
    }

    public function renderShortcode(array $targetFields, string $module): string
    {
        ob_start();
        
        $step = $_GET['import_step'] ?? 1;
        $mappingId = $_GET['mapping'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
            $this->handleFileUpload($targetFields, $module);
            return ob_get_clean();
        }
        
        switch ($step) {
            case 1:
                $this->renderStep1SelectFile();
                break;
            case 2:
                $this->renderStep2MapFields($targetFields, $mappingId);
                break;
            case 3:
                $this->renderStep3Review($targetFields);
                break;
            case 4:
                $this->renderStep4Results();
                break;
        }
        
        return ob_get_clean();
    }

    private function renderStep1SelectFile(): void
    {
        ?>
        <div class="ksf-import-wizard" id="ksf-import-step-1">
            <h2>Step 1: Select File</h2>
            <p>Upload a CSV, JSON, or Excel file to import.</p>
            
            <form method="post" enctype="multipart/form-data" class="ksf-import-form">
                <input type="hidden" name="import_step" value="2">
                
                <div class="form-group">
                    <label>Import File *</label>
                    <input type="file" name="import_file" accept=".csv,.json,.xlsx,.xls,.xml" required>
                </div>
                
                <div class="form-group">
                    <label>File Format</label>
                    <select name="file_format" id="file_format">
                        <option value="">Auto-detect</option>
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                        <option value="xlsx">Excel</option>
                        <option value="xml">XML</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="has_header" value="1" checked>
                        First row contains headers
                    </label>
                </div>
                
                <button type="submit" class="button button-primary">Next: Map Fields</button>
            </form>
            
            <?php $this->renderSavedMappingsSelect(); ?>
        </div>
        <?php
    }

    private function renderSavedMappingsSelect(): void
    {
        $mappings = $this->listSavedMappings($this->config['module'] ?? 'default');
        
        if (empty($mappings)) {
            return;
        }
        ?>
        <div class="ksf-saved-mappings">
            <h3>Or use a saved mapping</h3>
            <form method="get">
                <input type="hidden" name="import_step" value="2">
                <select name="mapping">
                    <option value="">Select a saved mapping...</option>
                    <?php foreach ($mappings as $m): ?>
                    <option value="<?php echo esc_attr($m['id']); ?>">
                        <?php echo esc_html($m['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button">Use Mapping</button>
            </form>
        </div>
        <?php
    }

    private function renderStep2MapFields(array $targetFields, ?int $mappingId): void
    {
        $fileData = $this->getFileData();
        
        if (!$fileData) {
            echo '<p>Please upload a file first.</p>';
            return;
        }
        
        $inputFields = $fileData['headers'];
        $savedMapping = $mappingId ? $this->getMapping($mappingId) : null;
        $autoMapping = (new FieldMappingService($this->config))->autoMap($inputFields, $targetFields);
        
        ?>
        <div class="ksf-import-wizard" id="ksf-import-step-2">
            <h2>Step 2: Map Fields</h2>
            <p>Map your file columns to the target fields.</p>
            
            <form method="post" class="ksf-import-form">
                <input type="hidden" name="import_step" value="3">
                <input type="hidden" name="import_file" value="<?php echo esc_attr($fileData['filename']); ?>">
                
                <table class="ksf-field-mapping">
                    <thead>
                        <tr>
                            <th>File Column</th>
                            <th>Sample Value</th>
                            <th>→</th>
                            <th>Target Field</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inputFields as $field): $sample = $fileData['samples'][$field] ?? ''; ?>
                        <tr>
                            <td><?php echo esc_html($field); ?></td>
                            <td><small><?php echo esc_html($sample); ?></small></td>
                            <td>→</td>
                            <td>
                                <select name="mapping[<?php echo esc_attr($field); ?>]">
                                    <option value="">-- Skip --</option>
                                    <?php foreach ($targetFields as $target): $selected = ($savedMapping['fields'][$field] ?? $autoMapping[$field] ?? '') === $target ? 'selected' : ''; ?>
                                    <option value="<?php echo esc_attr($target); ?>" <?php echo $selected; ?>>
                                        <?php echo esc_html($target); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="save_mapping" value="1">
                        Save this mapping for future use
                    </label>
                    <input type="text" name="mapping_name" placeholder="Mapping name...">
                </div>
                
                <button type="submit" class="button button-primary">Next: Review</button>
                <a href="?import_step=1" class="button">Back</a>
            </form>
        </div>
        <?php
    }

    private function renderStep3Review(array $targetFields): void
    {
        $fileData = $this->getFileData();
        $mapping = $_POST['mapping'] ?? [];
        
        if (empty($mapping)) {
            echo '<p>Please provide field mappings.</p>';
            return;
        }
        
        $wizard = ImportWizard::create($this->config);
        $wizard->loadFile($fileData['filepath']);
        $wizard->setMapping($mapping);
        
        $preview = $wizard->execute();
        $previewData = array_slice($preview['data'] ?? [], 0, 5);
        
        ?>
        <div class="ksf-import-wizard" id="ksf-import-step-3">
            <h2>Step 3: Review</h2>
            <p>Review the first few records before importing.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <?php foreach ($targetFields as $tf): ?>
                        <th><?php echo esc_html($tf); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewData as $row): ?>
                    <tr>
                        <?php foreach ($targetFields as $tf): ?>
                        <td><?php echo esc_html($row[$tf] ?? ''); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>Total records to import: <?php echo count($preview['data'] ?? []); ?></p>
            
            <form method="post" class="ksf-import-form">
                <input type="hidden" name="import_step" value="4">
                <input type="hidden" name="import_file" value="<?php echo esc_attr($fileData['filename']); ?>">
                <input type="hidden" name="mapping" value="<?php echo esc_attr(json_encode($mapping)); ?>">
                
                <?php if (!empty($_POST['save_mapping'])): ?>
                <input type="hidden" name="save_mapping" value="1">
                <input type="hidden" name="mapping_name" value="<?php echo esc_attr($_POST['mapping_name']); ?>">
                <?php endif; ?>
                
                <button type="submit" class="button button-primary">Confirm Import</button>
                <a href="?import_step=2" class="button">Back</a>
            </form>
        </div>
        <?php
    }

    private function renderStep4Results(): void
    {
        $fileData = $this->getFileData();
        $mapping = json_decode($_POST['mapping'] ?? '{}', true);
        
        if (!empty($_POST['save_mapping']) && !empty($_POST['mapping_name'])) {
            $mappingService = new FieldMappingService($this->config);
            $mappingService->createMapping($_POST['mapping_name'], $mapping, $this->config['module']);
        }
        
        $wizard = ImportWizard::create($this->config);
        $wizard->loadFile($fileData['filepath']);
        $wizard->setMapping($mapping);
        
        $processor = $this->config['processor'] ?? null;
        $result = $wizard->execute($processor);
        
        ?>
        <div class="ksf-import-wizard" id="ksf-import-step-4">
            <h2>Step 4: Results</h2>
            
            <?php if (!empty($result['errors'])): ?>
            <div class="error">
                <p>Errors occurred:</p>
                <ul>
                    <?php foreach ($result['errors'] as $err): ?>
                    <li><?php echo esc_html($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="updated">
                <p>
                    <strong>Successfully imported <?php echo count($result['data'] ?? []); ?> records.</strong>
                </p>
            </div>
            
            <a href="?import_step=1" class="button">Import Another File</a>
        </div>
        <?php
    }

    private function handleFileUpload(array $targetFields, string $module): void
    {
        if (empty($_FILES['import_file']['tmp_name'])) {
            return;
        }
        
        $file = $_FILES['import_file'];
        $filename = sanitize_file_name($file['name']);
        $filepath = $this->uploadDir . '/' . $filename;
        
        move_uploaded_file($file['tmp_name'], $filepath);
        
        $wizard = ImportWizard::create($this->config);
        $preview = $wizard->loadFile($filepath)->getFilePreview();
        
        set_transient("ksf_import_{$module}", [
            'filename' => $filename,
            'filepath' => $filepath,
            'headers' => $preview['headers'],
            'samples' => $preview['rows'][0] ?? [],
        ], HOUR_IN_SECONDS);
        
        wp_redirect(add_query_arg('import_step', 2));
        exit;
    }

    private function getFileData(): ?array
    {
        $module = $this->config['module'] ?? 'default';
        return get_transient("ksf_import_{$module}");
    }

    private function listSavedMappings(string $module): array
    {
        return (new FieldMappingService($this->config))->listMappingsForModule(
            $module,
            $this->config['target_fields'] ?? []
        );
    }

    private function getMapping(int $id): ?array
    {
        return (new FieldMappingService($this->config))->getMapping($id);
    }

    public function registerShortcode(string $shortcode, array $targetFields, string $module): void
    {
        add_shortcode($shortcode, function() use ($targetFields, $module) {
            return $this->renderShortcode($targetFields, $module);
        });
    }
}

class ESSImportHandler
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function handleImport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['proc'])) {
            return;
        }

        $proc = $_POST['proc'] ?? '';

        if ($proc !== 'ksf_import') {
            return;
        }

        $module = $_POST['module'] ?? '';
        $file = $_FILES['import_file'] ?? null;

        if (!$file || empty($file['tmp_name'])) {
            global $Ajax;
            $Ajax->add_js_alert('Please select a file to import.');
            return;
        }

        $mapping = $_POST['mapping'] ?? [];
        
        if (empty($mapping)) {
            global $Ajax;
            $Ajax->add_js_alert('Please map fields before importing.');
            return;
        }

        $filepath = $file['tmp_name'];
        
        try {
            $wizard = ImportWizard::create($this->config);
            $wizard->loadFile($filepath);
            $wizard->setMapping($mapping);
            
            $processor = $this->config['processor'] ?? null;
            $result = $wizard->execute($processor);
            
            $message = sprintf(
                'Successfully imported %d records.',
                count($result['data'] ?? [])
            );
            
            global $Ajax;
            $Ajax->add_js_alert($message);
            
        } catch (\Exception $e) {
            global $Ajax;
            $Ajax->add_js_alert('Import failed: ' . $e->getMessage());
        }
    }

    public function renderImportBlock(array $targetFields, string $module): string
    {
        ob_start();
        
        $step = $_GET['import_step'] ?? 1;
        
        include('import/import_basic.php');
        
        return ob_get_clean();
    }
}