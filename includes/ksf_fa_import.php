<?php

if (!defined('KSF_FA_IMPORT_INCLUDED')) {
    define('KSF_FA_IMPORT_INCLUDED', true);
    
    function ksf_fa_import_handler()
    {
        $page = $_GET['page'] ?? '';
        $proc = $_POST['proc'] ?? '';
        
        if ($proc !== 'ksf_fa_import') {
            return;
        }
        
        $module = $_POST['module'] ?? '';
        $step = $_GET['import_step'] ?? 1;
        
        global $db;
        include_once INCLUDES . '/db.inc';
        
        switch ($step) {
            case 2:
                ksf_fa_import_step2();
                break;
            case 3:
                ksf_fa_import_step3();
                break;
            case 4:
                ksf_fa_import_step4($module);
                break;
        }
    }
    
    function ksf_fa_import_step2()
    {
        $file = $_FILES['import_file'] ?? null;
        
        if (!$file || empty($file['tmp_name'])) {
            display_error('Please select a file.');
            return;
        }
        
        $content = file_get_contents($file['tmp_name']);
        $headers = [];
        $sample = [];
        
        $handle = fopen('php://memory', 'r');
        fwrite($handle, $content);
        rewind($handle);
        
        $firstRow = fgetcsv($handle);
        $headers = array_map('trim', $firstRow);
        
        $secondRow = fgetcsv($handle);
        if ($secondRow) {
            $sample = array_combine($headers, $secondRow);
        }
        
        fclose($handle);
        
        $_SESSION['ksf_import_data'] = [
            'content' => base64_encode($content),
            'headers' => $headers,
            'sample' => $sample,
        ];
        
        wp_redirect(add_query_arg('import_step', 2));
        exit;
    }
    
    function ksf_fa_import_step3()
    {
        $mapping = $_POST['mapping'] ?? [];
        
        if (empty($mapping)) {
            display_error('Please map at least one field.');
            return;
        }
        
        $mapping_json = json_encode($mapping, JSON_FORCE_OBJECT);
        $_SESSION['ksf_import_data']['mapping'] = $mapping_json;
        
        if (!empty($_POST['save_mapping'])) {
            $name = $_POST['mapping_name'] ?? 'Unnamed';
            $module = $_POST['module'] ?? '';
            
            try {
                $svc = new \Ksfraser\DataIO\FieldMappingService();
                $svc->createMapping($name, $mapping, $module);
                display_notification("Mapping '{$name}' saved.");
            } catch (\Exception $e) {
                display_error('Could not save mapping: ' . $e->getMessage());
            }
        }
        
        wp_redirect(add_query_arg('import_step', 3));
        exit;
    }
    
    function ksf_fa_import_step4($module)
    {
        $data = $_SESSION['ksf_import_data'] ?? [];
        
        if (empty($data['content']) || empty($data['mapping'])) {
            display_error('Import data not found. Please start over.');
            return;
        }
        
        $content = base64_decode($data['content']);
        $tempfile = sys_get_temp_dir() . '/ksf_import_' . time() . '.csv';
        file_put_contents($tempfile, $content);
        
        $mapping = json_decode($data['mapping'], true);
        $processor = apply_filters("ksf_{$module}_import_processor", null);
        
        $wizard = \Ksfraser\DataIO\ImportWizard::create();
        $wizard->loadFile($tempfile);
        $wizard->setMapping($mapping);
        
        $result = $wizard->execute($processor);
        
        $imported = count($result['data'] ?? []);
        
        display_notification("Successfully imported {$imported} records.");
        
        $_SESSION['ksf_import_data'] = null;
    }
    
    add_hook('ksf_fa_import_handler', 'ksf_fa_import_handler');
}

function ksf_fa_render_import_page($module, $target_fields, $title = 'Import Data')
{
    $step = $_GET['import_step'] ?? 1;
    $data = $_SESSION['ksf_import_data'] ?? [];
    
    $headers = $data['headers'] ?? [];
    $sample = $data['sample'] ?? [];
    $mapping = json_decode($data['mapping'] ?? '{}', true);
    
    $mappingSvc = new \Ksfraser\DataIO\FieldMappingService();
    $autoMapping = $mappingSvc->autoMap($headers, $target_fields);
    
    global $page_title;
    $page_title = $title;
    
    row_start();
    box_start($title);
    
    if ($step == 1) {
        ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="proc" value="ksf_fa_import">
            <input type="hidden" name="module" value="<?php echo $module; ?>">
            <input type="hidden" name="import_step" value="2">
            
            <div class="form-group">
                <label class="required">File (CSV/JSON)</label>
                <input type="file" name="import_file" accept=".csv,.json" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="has_header" value="1" checked>
                    First row is header
                </label>
            </div>
            
            <button type="submit" class="button">Next: Map Fields</button>
        </form>
        <?php
    } elseif ($step == 2 && !empty($headers)) {
        ?>
        <form method="post">
            <input type="hidden" name="proc" value="ksf_fa_import">
            <input type="hidden" name="module" value="<?php echo $module; ?>">
            <input type="hidden" name="import_step" value="3">
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>File Column</th>
                        <th>Sample</th>
                        <th></th>
                        <th>Target Field</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($headers as $field): ?>
                    <tr>
                        <td><?php echo $field; ?></td>
                        <td><small><?php echo $sample[$field] ?? ''; ?></small></td>
                        <td>→</td>
                        <td>
                            <select name="mapping[<?php echo $field; ?>]">
                                <option value="">-- Skip --</option>
                                <?php foreach ($target_fields as $tf): ?>
                                <option value="<?php echo $tf; ?>" <?php echo ($autoMapping[$field] ?? '') === $tf ? 'selected' : ''; ?>>
                                    <?php echo $tf; ?>
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
                    Save mapping
                </label>
                <input type="text" name="mapping_name" placeholder="Mapping name">
            </div>
            
            <button type="submit" class="button">Next: Review</button>
            <a href="?" class="button">Back</a>
        </form>
        <?php
    } elseif ($step == 3 && !empty($mapping)) {
        $wizard = \Ksfraser\DataIO\ImportWizard::create();
        $tempfile = sys_get_temp_dir() . '/ksf_import_' . time() . '.csv';
        file_put_contents($tempfile, base64_decode($data['content']));
        
        $wizard->loadFile($tempfile);
        $wizard->setMapping($mapping);
        $result = $wizard->execute();
        
        $_SESSION['ksf_import_preview'] = $result['data'] ?? [];
        ?>
        <form method="post">
            <input type="hidden" name="proc" value="ksf_fa_import">
            <input type="hidden" name="module" value="<?php echo $module; ?>">
            <input type="hidden" name="import_step" value="4">
            
            <p class="message">Ready to import <?php echo count($result['data'] ?? []); ?> records</p>
            
            <button type="submit" class="button button-primary">Confirm Import</button>
            <a href="?import_step=2" class="button">Back</a>
        </form>
        <?php
    } elseif ($step == 4) {
        $preview = $_SESSION['ksf_import_preview'] ?? [];
        ?>
        <p class="message">Import complete!</p>
        
        <a href="?" class="button">Import Another</a>
        <?php
    }
    
    box_end();
    row_end();
}