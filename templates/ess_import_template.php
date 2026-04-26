<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?php echo $title ?? 'Import Data'; ?></strong>
    </div>
    <div class="panel-body">
        
        <?php if ($step == 1): ?>
        
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="proc" value="ksf_import">
            <input type="hidden" name="module" value="<?php echo $module; ?>">
            <input type="hidden" name="import_step" value="2">
            
            <div class="form-group">
                <label><?php echo _('Select File'); ?> *</label>
                <input type="file" name="import_file" accept=".csv,.json,.xlsx,.xls" required>
            </div>
            
            <div class="form-group">
                <label><?php echo _('File Format'); ?></label>
                <select name="file_format">
                    <option value=""><?php echo _('Auto-detect'); ?></option>
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                    <option value="xlsx">Excel</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="has_header" value="1" checked>
                    <?php echo _('First row contains headers'); ?>
                </label>
            </div>
            
            <button type="submit" class="button"><?php echo _('Next: Map Fields'); ?></button>
        </form>
        
        <?php elseif ($step == 2): ?>
        
        <form method="post">
            <input type="hidden" name="proc" value="ksf_import">
            <input type="hidden" name="module" value="<?php echo $module; ?>">
            <input type="hidden" name="import_step" value="3">
            <input type="hidden" name="import_file_content" value="<?php echo base64_encode($previewContent); ?>">
            
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
                        <td><small><?php echo $fileSamples[$field] ?? ''; ?></small></td>
                        <td>→</td>
                        <td>
                            <select name="mapping[<?php echo $field; ?>]">
                                <option value="">-- Skip --</option>
                                <?php foreach ($targetFields as $tf): ?>
                                <option value="<?php echo $tf; ?>" 
                                    <?php echo ($autoMapping[$field] ?? '') === $tf ? 'selected' : ''; ?>>
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
                    <?php echo _('Save mapping for future use'); ?>
                </label>
                <input type="text" name="mapping_name" placeholder="<?php echo _('Mapping name'); ?>">
            </div>
            
            <button type="submit" class="button"><?php echo _('Next: Review'); ?></button>
            <a href="?import_step=1" class="button"><?php echo _('Back'); ?></a>
        </form>
        
        <?php elseif ($step == 3): ?>
        
        <form method="post">
            <input type="hidden" name="proc" value="ksf_import">
            <input type="hidden" name="module" value="<?php echo $module; ?>">
            <input type="hidden" name="import_step" value="4">
            <input type="hidden" name="mapping" value="<?php echo base64_encode($mapping); ?>">
            
            <p><strong><?php echo sprintf(_('Ready to import %d records'), $recordCount); ?></strong></p>
            
            <table class="table table-bordered table-condensed">
                <thead>
                    <tr>
                        <?php foreach ($targetFields as $tf): ?>
                        <th><?php echo $tf; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($previewData, 0, 10) as $row): ?>
                    <tr>
                        <?php foreach ($targetFields as $tf): ?>
                        <td><?php echo $row[$tf] ?? ''; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <button type="submit" class="button button-primary"><?php echo _('Confirm Import'); ?></button>
            <a href="?import_step=2" class="button"><?php echo _('Back'); ?></a>
        </form>
        
        <?php elseif ($step == 4): ?>
        
        <div class="updated">
            <p><strong><?php echo sprintf(_('Successfully imported %d records'), $recordCount); ?></strong></p>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="error">
            <p><?php echo _('Errors:'); ?></p>
            <ul>
                <?php foreach ($errors as $err): ?>
                <li><?php echo $err; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <a href="?import_step=1" class="button"><?php echo _('Import Another'); ?></a>
        
        <?php endif; ?>
        
    </div>
</div>

<style>
.ksf-import-ui .table th { background: #f5f5f5; }
.ksf-import-ui .form-group { margin-bottom: 1em; }
.ksf-import-ui label { display: block; font-weight: bold; }
.ksf-import-ui input[type="file"] { padding: 5px; }
</style>