<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../controllers/common_controllers.php');
        include('controller/employee_asset_acknowledgement_controller.php');
        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>Employee Asset Acknowledgement - Aryadibusiness</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        body.modal-open #assign_assets_modal,
        body.modal-open #public_link_modal,
        body.modal-open #add_assets_modal { z-index: 1060 !important; }
        body.modal-open .modal-backdrop { z-index: 1050 !important; }
        body.modal-open #js-page-content .select2-container,
        body.modal-open #js-page-content .select2-dropdown { z-index: 1 !important; }
        #assign_assets_modal .select2-container,
        #assign_assets_modal .select2-dropdown,
        #add_assets_modal .select2-container,
        #add_assets_modal .select2-dropdown { z-index: 1065 !important; }
        .select2-container .select2-selection--single { min-height: 31px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 29px;
        }
        .category-cell { min-width: 220px; vertical-align: top !important; }
        .category-select-wrap { min-width: 200px; }
        .asset-rows-table-wrap,
        .new-assets-table-wrap { overflow: visible !important; }
        .select2-dropdown.asset-ack-category-dropdown {
            min-width: 260px !important;
            z-index: 10050 !important;
            border: 1px solid #ced4da;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        .select2-dropdown.asset-ack-category-dropdown .select2-search--dropdown {
            padding: 8px;
        }
        .select2-dropdown.asset-ack-category-dropdown .select2-search__field {
            width: 100% !important;
            box-sizing: border-box;
            border: 1px solid #ced4da !important;
            border-radius: 4px;
            padding: 6px 10px;
            font-family: 'Poppins', sans-serif;
        }
        .select2-dropdown.asset-ack-category-dropdown .select2-results__option {
            white-space: normal;
            padding: 8px 12px;
        }
    </style>
</head>
<?php
    $UserType = SessionCheck();
    $roles = $_SESSION['Roles'] ?? array();
    if (!hasEmployeeAssetAckAdminAccess($roles)) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }

    $conn = _connectodb();
    $core = new Core();
    $employees_array = $core->_getTableRecords($conn, 'employees', 'where IsActive = 1 and Vendor = 0 ORDER BY Name ASC');
    $asset_categories = getEmployeeAssetCategories();
?>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard.php">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Employee Asset Acknowledgement</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        <strong>Company Asset Acknowledgement:</strong> Assign company assets to employees (laptop, tools, etc.), share a public link for self-declaration, and track acknowledgement status. Employees with pending assets are blocked from the portal until they confirm possession.
                    </div>

                    <div class="panel mb-2">
                        <div class="panel-hdr">
                            <h2>Employee Asset Records</h2>
                            <div class="panel-toolbar">
                                <button type="button" class="btn btn-sm btn-primary" id="btn_open_assign_assets">
                                    <i class="fa fa-plus"></i> Assign Assets
                                </button>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Employee</label>
                                        <select class="select2 form-control w-100" id="employee_filter" data-placeholder="Search employee...">
                                            <option value="-1">All Employees</option>
                                            <?php foreach ($employees_array as $emp) {
                                                $empLabel = trim($emp['Name'] . ' | ' . $emp['EmployeeNumber'] . ' | ' . $emp['Department']);
                                            ?>
                                                <option value="<?php echo (int) $emp['ID']; ?>"><?php echo htmlspecialchars($empLabel); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Acknowledgement Status</label>
                                        <select class="select2 form-control w-100" id="status_filter" data-placeholder="All statuses">
                                            <option value="-1">All</option>
                                            <option value="HasAssets">Has Assets</option>
                                            <option value="Pending">Pending Acknowledgement</option>
                                            <option value="Acknowledged">Fully Acknowledged</option>
                                            <option value="NoAssets">No Assets Assigned</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary" id="btn_filter_asset_ack">Apply</button>
                                    </div>
                                </div>
                                <table id="employee_asset_ack_table" class="table table-bordered table-hover table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Emp. No.</th>
                                            <th>Department</th>
                                            <th>Total Assets</th>
                                            <th>Pending</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <!-- Assign / Multi-add assets modal -->
    <div class="modal fade" id="assign_assets_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Assign Company Assets</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Employee <span class="text-danger">*</span></label>
                        <select class="select2 form-control w-100" id="assign_employee_id" data-placeholder="Search employee by name or number">
                            <option value="">Select employee</option>
                            <?php foreach ($employees_array as $emp) {
                                $empLabel = trim($emp['Name'] . ' | ' . $emp['EmployeeNumber'] . ' | ' . $emp['Department']);
                            ?>
                                <option value="<?php echo (int) $emp['ID']; ?>"><?php echo htmlspecialchars($empLabel); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Asset Rows</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn_add_asset_row"><i class="fa fa-plus"></i> Add Row</button>
                    </div>
                    <div class="table-responsive asset-rows-table-wrap">
                        <table class="table table-sm table-bordered" id="asset_rows_table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:14%">Category</th>
                                    <th style="width:22%">Asset Description</th>
                                    <th style="width:16%">Serial / Asset ID</th>
                                    <th style="width:12%">Allocation Date</th>
                                    <th style="width:22%">Remarks</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody id="asset_rows_body"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btn_save_assigned_assets">Save Assets</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Public link modal -->
    <div class="modal fade" id="public_link_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Public Asset Form Link</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Share this link with the employee. They can view, add, and acknowledge their assigned company assets without logging in. Link expires in 30 days.</p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="public_link_input" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" type="button" id="btn_copy_public_link">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.TechXpertAssetAck = {
            categories: <?php echo json_encode($asset_categories); ?>
        };
    </script>
    <?php include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/employee-asset-acknowledgement.js"></script>
</body>
</html>
