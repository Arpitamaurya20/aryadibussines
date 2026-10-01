<?php
if (!isset($dppmBulkFormId)) {
    $dppmBulkFormId = 'dppm_bulk_items_form';
}
if (!isset($dppmBulkChecklistID)) {
    $dppmBulkChecklistID = 0;
}
if (!isset($dppmBulkChecklists)) {
    $dppmBulkChecklists = array();
}
if (!isset($dppmBulkRedirect)) {
    $dppmBulkRedirect = '';
}
if (!isset($dppmBulkFormAction)) {
    $dppmBulkFormAction = 'action/save_checklist_item.php';
}
$inputTypes = dynamicPPMInputTypes();
?>
<form method="post" action="<?php echo htmlspecialchars($dppmBulkFormAction); ?>" id="<?php echo htmlspecialchars($dppmBulkFormId); ?>">
    <?php if ((int) $dppmBulkChecklistID > 0) { ?>
        <input type="hidden" name="ChecklistID" value="<?php echo (int) $dppmBulkChecklistID; ?>">
        <div class="alert alert-light border mb-3 py-2">
            Adding items to: <strong><?php echo htmlspecialchars(isset($dppmBulkChecklistLabel) ? $dppmBulkChecklistLabel : ''); ?></strong>
        </div>
    <?php } else { ?>
        <div class="form-group">
            <label>Checklist *</label>
            <select name="ChecklistID" class="form-control dppm-select2" required>
                <option value="">Select Checklist</option>
                <?php foreach ($dppmBulkChecklists as $row) { ?>
                    <option value="<?php echo (int) $row['ID']; ?>"><?php echo htmlspecialchars($row['ChecklistCode'] . ' - ' . $row['ChecklistName']); ?></option>
                <?php } ?>
            </select>
        </div>
    <?php } ?>

    <div class="alert alert-info py-2">
        Add multiple items for the same checklist. Use rows below, or enter one item name per line in quick add.
    </div>

    <div class="form-group">
        <label>Quick Add Item Names (one per line)</label>
        <textarea name="ItemNames" class="form-control" rows="4" placeholder="Check leakage points&#10;Check water pressure&#10;Inspect pipe joints"></textarea>
        <small class="text-muted">Optional. Uses default input type, mandatory, unit, and default value below for all quick-add rows.</small>
    </div>

    <div class="form-row mb-2">
        <div class="col-md-3 form-group">
            <label>Default Input Type</label>
            <select name="DefaultInputType" class="form-control">
                <?php foreach ($inputTypes as $type) { ?>
                    <option value="<?php echo $type; ?>"<?php echo $type === 'text' ? ' selected' : ''; ?>><?php echo $type; ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3 form-group">
            <label>Default Mandatory</label>
            <select name="DefaultIsMandatory" class="form-control">
                <option value="0" selected>No</option>
                <option value="1">Yes</option>
            </select>
        </div>
        <div class="col-md-3 form-group">
            <label>Default Unit</label>
            <input type="text" name="DefaultUnitName" class="form-control" placeholder="V, PSI, etc.">
        </div>
        <div class="col-md-3 form-group">
            <label>Default Value</label>
            <input type="text" name="DefaultValue" class="form-control" placeholder="Pre-filled value">
        </div>
    </div>
    <div class="form-group">
        <label>Default Options (for dropdown)</label>
        <input type="text" name="DefaultOptionsJson" class="form-control" placeholder="OK,Not OK,N/A">
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Detailed Item Rows</h6>
        <div>
            <button type="button" class="btn btn-outline-primary btn-sm dppm-add-item-row">Add Row</button>
            <button type="button" class="btn btn-outline-primary btn-sm dppm-add-item-rows-5">Add 5 Rows</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-2">
            <thead class="bg-primary-600 text-white">
                <tr>
                    <th style="min-width:180px;">Item Name *</th>
                    <th style="min-width:90px;">Item Code</th>
                    <th style="min-width:100px;">Input Type</th>
                    <th style="min-width:70px;">Unit</th>
                    <th style="min-width:100px;">Default Value</th>
                    <th style="min-width:90px;">Mandatory</th>
                    <th style="min-width:120px;">Options</th>
                    <th style="min-width:70px;">Sort</th>
                    <th style="width:40px;"></th>
                </tr>
            </thead>
            <tbody class="dppm-item-rows"></tbody>
        </table>
    </div>

    <input type="hidden" name="items_json" class="dppm-items-json" value="">
    <input type="hidden" name="CreatedBy" value="<?php echo htmlspecialchars(dynamicPPMGetSessionUser()); ?>">
    <?php if ($dppmBulkRedirect !== '') { ?>
        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($dppmBulkRedirect); ?>">
    <?php } ?>
    <button type="submit" class="btn btn-success">Save Checklist Items</button>
</form>
