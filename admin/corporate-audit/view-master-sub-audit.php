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
    ?>
    <meta charset="utf-8">
    <title>Master Sub Audit - Corporate Audit - TechXpert</title>
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item">Corporate Audit</li>
                        <li class="breadcrumb-item active">Master Sub Audit</li>
                    </ol>
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>Master Sub Audit Modules</h2>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <button type="button" class="btn btn-outline-primary btn-sm mr-2" onclick="caToggleFilterCanvas();">
                                            <i class="fa fa-filter"></i> Filters
                                        </button>
                                        <span class="ca-filter-summary mr-3" id="ca_sub_audit_filter_summary">All records</span>
                                        <button type="button" class="btn btn-primary btn-sm mr-2" onclick="reloadSubAuditTable();">
                                            <i class="fa fa-search"></i> Search
                                        </button>
                                        <a href="#" onclick="openMasterSubAuditModal(); return false;" class="btn btn-info btn-sm">Add Sub Audit</a>
                                    </div>
                                </div>
                                <?php include('./include/master-sub-audit-list-view.php'); ?>
                            </div>
                        </div>
                    </div>

                    <div id="ca_filter_offcanvas" class="ca-filter-offcanvas">
                        <div class="offcanvas-header">
                            <h5 class="mb-0">Sub Audit Filters</h5>
                            <button type="button" class="close" onclick="caCloseFilterCanvas();">&times;</button>
                        </div>
                        <div class="offcanvas-body">
                            <div class="form-group">
                                <label>Master Audit</label>
                                <select class="form-control ca-audit-select" id="filter_master_audit_id" data-placeholder="All master audits">
                                    <option value="0">All Master Audits</option>
                                    <?php foreach ($masterAudits as $audit) { ?>
                                    <option value="<?php echo (int) $audit['ID']; ?>"><?php echo htmlspecialchars($audit['AuditName']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control ca-audit-select" id="filter_sub_audit_status" data-placeholder="All statuses">
                                    <option value="-1">All Statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="offcanvas-footer d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetSubAuditFilters();">Reset</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="applySubAuditFilters();">Apply Filters</button>
                        </div>
                    </div>

                    <div class="modal fade" id="master_sub_audit_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="master_sub_audit_modal_title">Add Sub Audit</h5>
                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <form id="master_sub_audit_form" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label>Master Audit *</label>
                                            <select class="form-control ca-audit-select" name="master_audit_id" id="master_audit_id" data-placeholder="Select master audit">
                                                <option value="0">Select Master Audit</option>
                                                <?php foreach ($masterAudits as $audit) { ?>
                                                <option value="<?php echo (int) $audit['ID']; ?>"><?php echo htmlspecialchars($audit['AuditName']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Sub Audit Name *</label>
                                                    <input type="text" class="form-control" name="sub_audit_name" id="sub_audit_name" placeholder="e.g. UPS Power Supply">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Sort Order</label>
                                                    <input type="number" class="form-control" name="sort_order" id="sub_sort_order" value="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea class="form-control" name="sub_audit_description" id="sub_audit_description" rows="2"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Icon Class</label>
                                                    <input type="text" class="form-control" name="icon_class" id="sub_icon_class" placeholder="fa-solid fa-plug">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Upload Icon Image</label>
                                                    <input type="file" class="form-control" name="icon_image" id="sub_icon_image" accept="image/*">
                                                    <div id="sub_icon_preview" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select class="form-control ca-audit-select" name="is_active" id="sub_is_active" data-placeholder="Select status">
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="form_action" id="sub_form_action" value="add">
                                        <input type="hidden" name="form_id" id="sub_form_id" value="">
                                        <button type="button" class="btn btn-primary" onclick="saveMasterSubAudit()">Save</button>
                                    </form>
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
    <script src="../js/modules/corporate-audit-sub.js"></script>
    <script>$(document).ready(function () { initMasterSubAuditPage(); });</script>
</body>
</html>
