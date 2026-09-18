<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/corporate_audit_controller.php');
    setNavigation($_SESSION['Roles']);
    $UserType = SessionCheck();
    $conn = _connectodb();
    $masterAudits = getAllCorporateMasterAudits($conn, false);
    $subAudits = getAllCorporateMasterSubAudits($conn, 0, false);
    $fieldTypes = corporateAuditFieldTypes();
    ?>
    <meta charset="utf-8">
    <title>Master Checklist - Corporate Audit - Aryadibusiness</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <?php include('./include/audit-ui-styles.php'); ?>
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item">Corporate Audit</li>
                        <li class="breadcrumb-item active">Master Checklist</li>
                    </ol>
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>Master Audit Checklist / Checkpoints</h2>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <button type="button" class="btn btn-outline-primary btn-sm mr-2" onclick="caToggleFilterCanvas();">
                                            <i class="fa fa-filter"></i> Filters
                                        </button>
                                        <span class="ca-filter-summary mr-3" id="ca_checklist_filter_summary">All records</span>
                                        <button type="button" class="btn btn-primary btn-sm mr-2" onclick="reloadChecklistTable();">
                                            <i class="fa fa-search"></i> Search
                                        </button>
                                        <a href="#" onclick="openChecklistModal(); return false;" class="btn btn-info btn-sm mr-1">Add Checkpoint</a>
                                        <a href="#" onclick="openBulkChecklistModal(); return false;" class="btn btn-success btn-sm">
                                            <i class="fa fa-list-ul"></i> Bulk Add Checkpoints
                                        </a>
                                    </div>
                                </div>
                                <?php include('./include/master-checklist-list-view.php'); ?>
                            </div>
                        </div>
                    </div>

                    <div id="ca_filter_offcanvas" class="ca-filter-offcanvas">
                        <div class="offcanvas-header">
                            <h5 class="mb-0">Checklist Filters</h5>
                            <button type="button" class="close" onclick="caCloseFilterCanvas();">&times;</button>
                        </div>
                        <div class="offcanvas-body">
                            <div class="form-group">
                                <label>Master Audit</label>
                                <select class="form-control ca-audit-select" id="filter_checklist_master_id" data-placeholder="All master audits" onchange="loadChecklistSubAudits(true);">
                                    <option value="0">All Master Audits</option>
                                    <?php foreach ($masterAudits as $audit) { ?>
                                    <option value="<?php echo (int) $audit['ID']; ?>"><?php echo htmlspecialchars($audit['AuditName']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sub Audit</label>
                                <select class="form-control ca-audit-select" id="filter_checklist_sub_id" data-placeholder="All sub audits">
                                    <option value="0">All Sub Audits</option>
                                    <?php foreach ($subAudits as $sub) { ?>
                                    <option value="<?php echo (int) $sub['ID']; ?>" data-master="<?php echo (int) $sub['MasterAuditID']; ?>">
                                        <?php echo htmlspecialchars($sub['SubAuditName']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Field Type</label>
                                <select class="form-control ca-audit-select" id="filter_checklist_field_type" data-placeholder="All field types">
                                    <option value="">All Field Types</option>
                                    <?php foreach ($fieldTypes as $key => $label) { ?>
                                    <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Mandatory</label>
                                <select class="form-control ca-audit-select" id="filter_checklist_mandatory" data-placeholder="All">
                                    <option value="-1">All</option>
                                    <option value="1">Mandatory Only</option>
                                    <option value="0">Optional Only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control ca-audit-select" id="filter_checklist_status" data-placeholder="All statuses">
                                    <option value="-1">All Statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="offcanvas-footer d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetChecklistFilters();">Reset</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="applyChecklistFilters();">Apply Filters</button>
                        </div>
                    </div>

                    <div class="modal fade" id="checklist_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="checklist_modal_title">Add Checkpoint</h5>
                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <form id="checklist_form">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Master Audit</label>
                                                    <select class="form-control ca-audit-select" id="checklist_master_audit_id" data-placeholder="Select master audit" onchange="filterChecklistSubAuditsInModal(true);">
                                                        <option value="0">Select Master Audit</option>
                                                        <?php foreach ($masterAudits as $audit) { ?>
                                                        <option value="<?php echo (int) $audit['ID']; ?>"><?php echo htmlspecialchars($audit['AuditName']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Sub Audit Module *</label>
                                                    <select class="form-control ca-audit-select" name="sub_audit_id" id="sub_audit_id" data-placeholder="Select sub audit">
                                                        <option value="0">Select Sub Audit</option>
                                                        <?php foreach ($subAudits as $sub) { ?>
                                                        <option value="<?php echo (int) $sub['ID']; ?>" data-master="<?php echo (int) $sub['MasterAuditID']; ?>">
                                                            <?php echo htmlspecialchars($sub['SubAuditName']); ?>
                                                        </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Checkpoint Name *</label>
                                            <textarea class="form-control" name="checkpoint_name" id="checkpoint_name" rows="3" placeholder="e.g. Input voltage level at main panel (2–3 lines allowed)"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Description / Instructions</label>
                                            <textarea class="form-control" name="checkpoint_description" id="checkpoint_description" rows="2"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Field Type</label>
                                                    <select class="form-control ca-audit-select" name="field_type" id="field_type" data-placeholder="Select field type" onchange="toggleChecklistOptionsField()">
                                                        <?php foreach ($fieldTypes as $key => $label) { ?>
                                                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Ideal Value</label>
                                                    <input type="text" class="form-control" name="ideal_value" id="ideal_value" placeholder="e.g. 230">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Unit</label>
                                                    <input type="text" class="form-control" name="unit" id="unit" placeholder="V, A, kW">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Min Value</label>
                                                    <input type="text" class="form-control" name="min_value" id="min_value">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Max Value</label>
                                                    <input type="text" class="form-control" name="max_value" id="max_value">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Sort Order</label>
                                                    <input type="number" class="form-control" name="sort_order" id="checklist_sort_order" value="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group" id="options_json_group" style="display:none;">
                                            <label>Dropdown Options (comma separated)</label>
                                            <input type="text" class="form-control" name="options_json" id="options_json" placeholder="Good, Average, Poor">
                                        </div>
                                        <div class="form-group">
                                            <label>Help Text</label>
                                            <input type="text" class="form-control" name="help_text" id="help_text">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Mandatory</label>
                                                    <select class="form-control ca-audit-select" name="is_mandatory" id="is_mandatory" data-placeholder="Select">
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select class="form-control ca-audit-select" name="is_active" id="checklist_is_active" data-placeholder="Select status">
                                                        <option value="1">Active</option>
                                                        <option value="0">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="form_action" id="checklist_form_action" value="add">
                                        <input type="hidden" name="form_id" id="checklist_form_id" value="">
                                        <button type="button" class="btn btn-primary" onclick="saveChecklist()">Save Checkpoint</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="bulk_checklist_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title">Bulk Add Checkpoints</h5>
                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info py-2">
                                        Select sub audit once, then add multiple checkpoint rows below. Empty rows are skipped on save.
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label>Master Audit</label>
                                            <select class="form-control ca-audit-select" id="bulk_master_audit_id" data-placeholder="Select master audit" onchange="filterBulkChecklistSubAudits(true);">
                                                <option value="0">Select Master Audit</option>
                                                <?php foreach ($masterAudits as $audit) { ?>
                                                <option value="<?php echo (int) $audit['ID']; ?>"><?php echo htmlspecialchars($audit['AuditName']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Sub Audit Module *</label>
                                            <select class="form-control ca-audit-select" id="bulk_sub_audit_id" data-placeholder="Select sub audit">
                                                <option value="0">Select Sub Audit</option>
                                                <?php foreach ($subAudits as $sub) { ?>
                                                <option value="<?php echo (int) $sub['ID']; ?>" data-master="<?php echo (int) $sub['MasterAuditID']; ?>">
                                                    <?php echo htmlspecialchars($sub['SubAuditName']); ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Default Field Type (new rows)</label>
                                            <select class="form-control ca-audit-select" id="bulk_default_field_type" data-placeholder="Default field type">
                                                <?php foreach ($fieldTypes as $key => $label) { ?>
                                                <option value="<?php echo $key; ?>"<?php echo $key === 'text' ? ' selected' : ''; ?>><?php echo $label; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label>Default Mandatory</label>
                                            <select class="form-control ca-audit-select" id="bulk_default_mandatory" data-placeholder="Default mandatory">
                                                <option value="1">Yes</option>
                                                <option value="0" selected>No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Default Status</label>
                                            <select class="form-control ca-audit-select" id="bulk_default_status" data-placeholder="Default status">
                                                <option value="1" selected>Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end justify-content-end flex-wrap">
                                            <button type="button" class="btn btn-outline-primary btn-sm mr-1 mb-1" onclick="addBulkChecklistRows(1);">
                                                <i class="fa fa-plus"></i> Add Row
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm mr-1 mb-1" onclick="addBulkChecklistRows(5);">
                                                <i class="fa fa-plus"></i> Add 5 Rows
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm mb-1" onclick="clearBulkChecklistRows();">
                                                Clear Rows
                                            </button>
                                        </div>
                                    </div>
                                    <div class="bulk-checklist-scroll">
                                        <table class="table table-bordered table-sm bulk-checklist-table mb-0">
                                            <thead class="bg-primary-600 text-white">
                                                <tr>
                                                    <th class="bulk-row-num">#</th>
                                                    <th class="bulk-name-col">Checkpoint Name *</th>
                                                    <th class="bulk-desc-col">Description</th>
                                                    <th style="min-width:110px;">Field Type</th>
                                                    <th style="min-width:90px;">Ideal</th>
                                                    <th style="min-width:70px;">Unit</th>
                                                    <th style="min-width:70px;">Min</th>
                                                    <th style="min-width:70px;">Max</th>
                                                    <th style="min-width:90px;">Mandatory</th>
                                                    <th style="min-width:70px;">Sort</th>
                                                    <th style="width:50px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="bulk_checklist_rows"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <span class="text-muted mr-auto" id="bulk_checklist_row_count">0 rows</span>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-success" onclick="saveBulkChecklist();">
                                        <i class="fa fa-save"></i> Save All Checkpoints
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/corporate-audit-common.js"></script>
    <script>window.caChecklistFieldTypes = <?php echo json_encode($fieldTypes); ?>;</script>
    <script src="../js/modules/corporate-audit-checklist.js"></script>
    <script>$(document).ready(function () { initChecklistPage(); });</script>
</body>
</html>
