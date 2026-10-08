<?php

function ksf_render_import_page($module, $target_fields, $title = 'Import Data', $processor = null)
{
    $step = $_GET['import_step'] ?? 1;
    $preview = null;
    $mapping = $_POST['mapping'] ?? [];
    $errors = [];
    
    if ($step == 2 && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $file_content = base64_decode($_POST['import_file_content'] ?? '');
        $tempfile = sys_get_temp_dir() . '/ksf_import_' . time() . '.csv';
        file_put_contents($tempfile, $file_content);
        
        $wizard = \Ksfraser\DataIO\ImportWizard::create();
        $preview = $wizard->loadFile($tempfile)->getFilePreview();
        $preview['filepath'] = $tempfile;
    }
    
    if ($step == 3 && !empty($_POST['mapping'])) {
        $mapping = $_POST['mapping'];
        $file_content = base64_decode($_POST['import_file_content'] ?? '');
        $tempfile = sys_get_temp_dir() . '/ksf_import_' . time() . '.csv';
        file_put_contents($tempfile, $file_content);
        
        $wizard = \Ksfraser\DataIO\ImportWizard::create();
        $wizard->loadFile($tempfile);
        $wizard->setMapping($mapping);
        $preview = $wizard->execute();
    }
    
    if ($step == 4 && !empty($_POST['mapping'])) {
        $mapping_json = $_POST['mapping'] ?? '[]';
        $mapping = json_decode(base64_decode($mapping_json), true);
        
        if (!empty($_POST['save_mapping']) && !empty($_POST['mapping_name'])) {
            $svc = new \Ksfraser\DataIO\FieldMappingService();
            $svc->createMapping($_POST['mapping_name'], $mapping, $module);
        }
        
        global $db;
        include_once INCLUDES . '/db.inc';
        
        $wizard = \Ksfraser\DataIO\ImportWizard::create(['fa_path' => PLUGINS . '/..']);
        $tempfile = sys_get_temp_dir() . '/ksf_import_' . time() . '.csv';
        file_put_contents($tempfile, base64_decode($_POST['import_file_content'] ?? ''));
        
        if ($processor && is_callable($processor)) {
            $result = $wizard->setMapping($mapping)->execute($processor);
        } else {
            $result = $wizard->setMapping($mapping)->execute();
        }
    }
    
    page_start();
    box_start($title);
    ?>
    <div class="ksf-import-ui">
    
    <?php if ($step == 1): ?>
    
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="proc" value="ksf_import">
        <input type="hidden" name="import_step" value="2">
        
        <div class="form-group">
            <label><?php echo _('Select File'); ?> *</label>
            <input type="file" name="import_file" accept=".csv,.json" required>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="has_header" value="1" checked>
                <?php echo _('First row contains headers'); ?>
            </label>
        </div>
        
        <button type="submit" class="button"><?php echo _('Next: Map Fields'); ?></button>
    </form>
    
    <?php elseif ($step == 2 && $preview): ?>
    
    <?php 
    $fileHeaders = $preview['headers'] ?? [];
    $mappingSvc = new \Ksfraser\DataIO\FieldMappingService();
    $autoMapping = $mappingSvc->autoMap($fileHeaders, $target_fields);
    ?>
    
    <form method="post">
        <input type="hidden" name="proc" value="ksf_import">
        <input type="hidden" name="import_step" value="3">
        <input type="hidden" name="import_file_content" value="<?php echo base64_encode(file_get_contents($preview['filepath'])); ?>">
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><?php echo _('File Column'); ?></th>
                    <th><?php echo _('Sample'); ?></th>
                    <th></th>
                    <th><?php echo _('Target Field'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fileHeaders as $field): ?>
                <tr>
                    <td><?php echo $field; ?></td>
                    <td><small><?php echo $preview['rows'][0][$field] ?? ''; ?></small></td>
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
                <?php echo _('Save mapping'); ?>
            </label>
            <input type="text" name="mapping_name" placeholder="<?php echo _('Mapping name'); ?>">
        </div>
        
        <button type="submit" class="button"><?php echo _('Next: Review'); ?></button>
        <a href="?" class="button"><?php echo _('Back'); ?></a>
    </form>
    
    <?php elseif ($step == 3 && $preview): ?>
    
    <form method="post">
        <input type="hidden" name="proc" value="ksf_import">
        <input type="hidden" name="import_step" value="4">
        <input type="hidden" name="mapping" value="<?php echo base64_encode(json_encode($mapping)); ?>">
        <input type="hidden" name="import_file_content" value="<?php echo base64_encode(file_get_contents($preview['filepath'])); ?>">
        
        <?php if (!empty($_POST['save_mapping'])): ?>
        <input type="hidden" name="save_mapping" value="1">
        <input type="hidden" name="mapping_name" value="<?php echo htmlspecialchars($_POST['mapping_name']); ?>">
        <?php endif; ?>
        
        <p><strong><?php echo sprintf(_('Ready to import %d records'), count($preview['data'])); ?></strong></p>
        
        <button type="submit" class="button button-primary"><?php echo _('Confirm Import'); ?></button>
        <a href="?import_step=2" class="button"><?php echo _('Back'); ?></a>
    </form>
    
    <?php elseif ($step == 4 && !empty($result)): ?>
    
    <div class="updated">
        <p><strong><?php echo sprintf(_('Imported %d records'), count($result['data'])); ?></strong></p>
    </div>
    
    <a href="?" class="button"><?php echo _('Import Another'); ?></a>
    
    <?php endif; ?>
    
    </div>
    <?php
    box_end();
}

function ksf_import_menu_entry($module, $label)
{
    add_menu_entry($module, $label, '', "{$module}_import");
}

function ksf_import_menu_proc($module)
{
    if (($_GET['page'] ?? '') === "{$module}_import") {
        $fields = apply_filters("ksf_{$module}_import_fields", []);
        ksf_render_import_page($module, $fields);
    }
}