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
    <title>Manage Employee Assets - Aryadibusiness</title>
    <?php include('../includes/common_head_content.php'); ?>
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        body.modal-open #add_assets_modal,
        body.modal-open #public_link_modal,
        body.modal-open #asset_hold_status_modal { z-index: 1060 !important; }
        body.modal-open .modal-backdrop { z-index: 1050 !important; }
        body.modal-open #js-page-content .select2-container,
        body.modal-open #js-page-content .select2-dropdown { z-index: 1 !important; }
        #add_assets_modal .select2-container,
        #add_assets_modal .select2-dropdown { z-index: 1065 !important; }
        .category-cell { min-width: 220px; vertical-align: top !important; }
        .category-select-wrap { min-width: 200px; }
        .asset-rows-table-wrap { overflow: visible !important; }
        .select2-dropdown.asset-ack-category-dropdown {
            min-width: 260px !important;
            z-index: 10050 !important;
            border: 1px solid #ced4da;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        .select2-dropdown.asset-ack-category-dropdown .select2-search--dropdown { padding: 8px; }
        .select2-dropdown.asset-ack-category-dropdown .select2-search__field {
            width: 100% !important;
            box-sizing: border-box;
            border: 1px solid #ced4da !important;
            border-radius: 4px;
            padding: 6px 10px;
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

    $employeeId = isset($_GET['EmployeeID']) ? (int) $_GET['EmployeeID'] : 0;
    $conn = _connectodb();
    $employee = getEmployeeAssetAckEmployee($conn, $employeeId);
    if (!$employee) {
        header('Location: view-employee-asset-acknowledgement.php');
        exit;
    }

    $assets = getEmployeeCompanyAssets($conn, $employeeId, true);
    $historyAssets = getEmployeeCompanyAssetHistory($conn, $employeeId);
    $summary = getEmployeeAssetAckSummaryCounts($conn, $employeeId);
    $asset_categories = getEmployeeAssetCategories();
    $hold_statuses = getEmployeeAssetHoldStatuses();
    $publicUrl = buildEmployeeAssetAckPublicUrl($employeeId);
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
                        <li class="breadcrumb-item"><a href="view-employee-asset-acknowledgement.php">Asset Acknowledgement</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($employee['Name']); ?></li>
                    </ol>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="panel">
                                <div class="panel-hdr"><h2>Employee Details</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content p-3">
                                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($employee['Name']); ?></p>
                                        <p class="mb-1"><strong>Employee No.:</strong> <?php echo htmlspecialchars($employee['EmployeeNumber']); ?></p>
                                        <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($employee['Department']); ?></p>
                                        <p class="mb-0"><strong>Designation:</strong> <?php echo htmlspecialchars($employee['Designation']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="panel">
                                <div class="panel-hdr"><h2>Summary</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content p-3">
                                        <p class="mb-1">With Employee: <strong><?php echo (int) $summary['total_assets']; ?></strong></p>
                                        <p class="mb-1">Pending: <strong class="text-warning"><?php echo (int) $summary['pending_assets']; ?></strong></p>
                                        <p class="mb-1">Acknowledged: <strong class="text-success"><?php echo (int) $summary['acknowledged_assets']; ?></strong></p>
                                        <p class="mb-0">History: <strong class="text-info"><?php echo (int) $summary['history_assets']; ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel mb-2">
                        <div class="panel-hdr">
                            <h2>Assets With Employee</h2>
                            <div class="panel-toolbar">
                                <button type="button" class="btn btn-sm btn-outline-primary mr-1" id="btn_show_public_link">Public Link</button>
                                <button type="button" class="btn btn-sm btn-primary" id="btn_add_more_assets"><i class="fa fa-plus"></i> Add Assets</button>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Serial / Asset ID</th>
                                                <th>Allocation Date</th>
                                                <th>Hold Status</th>
                                                <th>Acknowledgement</th>
                                                <th>Acknowledged On</th>
                                                <th>Source</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($assets)) { ?>
                                                <tr><td colspan="9" class="text-center text-muted">No assets currently with this employee.</td></tr>
                                            <?php } else { foreach ($assets as $asset) {
                                                $holdStatus = $asset['AssetHoldStatus'] ?? 'Assigned';
                                            ?>
                                                <tr data-asset-id="<?php echo (int) $asset['ID']; ?>">
                                                    <td><?php echo htmlspecialchars($asset['AssetCategory']); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['AssetDescription']); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['AssetSerialNumber']); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['AllocationDate']); ?></td>
                                                    <td><?php echo formatEmployeeAssetHoldStatusBadge($holdStatus); ?></td>
                                                    <td>
                                                        <?php if ($asset['AcknowledgementStatus'] === 'Pending') { ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php } else { ?>
                                                            <span class="badge badge-success">Acknowledged</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($asset['AcknowledgedDate'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['Source']); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-xs btn-outline-primary btn-change-asset-status"
                                                            data-id="<?php echo (int) $asset['ID']; ?>"
                                                            data-description="<?php echo htmlspecialchars($asset['AssetDescription'], ENT_QUOTES); ?>">
                                                            Update Status
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php } } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel mb-2">
                        <div class="panel-hdr">
                            <h2>Asset History</h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <p class="text-muted small mb-3">Assets no longer with the employee — returned, lost, damaged, or retired.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Serial / Asset ID</th>
                                                <th>Allocation Date</th>
                                                <th>Final Status</th>
                                                <th>Status Date</th>
                                                <th>Remarks</th>
                                                <th>Updated By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($historyAssets)) { ?>
                                                <tr><td colspan="8" class="text-center text-muted">No asset history yet.</td></tr>
                                            <?php } else { foreach ($historyAssets as $asset) {
                                                $holdStatus = $asset['AssetHoldStatus'] ?? '';
                                                $labels = getEmployeeAssetHoldStatuses();
                                                $statusLabel = isset($labels[$holdStatus]) ? $labels[$holdStatus] : $holdStatus;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($asset['AssetCategory']); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['AssetDescription']); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['AssetSerialNumber']); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['AllocationDate']); ?></td>
                                                    <td><?php echo formatEmployeeAssetHoldStatusBadge($holdStatus); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['HoldStatusDate'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['HoldStatusRemarks'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($asset['HoldStatusUpdatedBy'] ?? '-'); ?></td>
                                                </tr>
                                            <?php } } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="asset_hold_status_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Update Asset Status</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>Asset:</strong> <span id="hold_status_asset_label">-</span></p>
                    <p class="text-muted small mb-3">Use this when the asset is no longer with the employee. The record will move to Asset History.</p>
                    <div class="form-group">
                        <label>New Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="hold_status_select">
                            <option value="">Select status</option>
                            <?php foreach ($hold_statuses as $statusKey => $statusLabel) { ?>
                                <option value="<?php echo htmlspecialchars($statusKey); ?>"><?php echo htmlspecialchars($statusLabel); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Remarks</label>
                        <textarea class="form-control" id="hold_status_remarks" rows="3" placeholder="Optional notes (return date, condition, handover details, etc.)"></textarea>
                    </div>
                    <input type="hidden" id="hold_status_asset_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btn_save_asset_hold_status">Save Status</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_assets_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Add More Assets</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Asset Rows</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn_add_asset_row"><i class="fa fa-plus"></i> Add Row</button>
                    </div>
                    <div class="table-responsive asset-rows-table-wrap">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Serial / Asset ID</th>
                                    <th>Allocation Date</th>
                                    <th>Remarks</th>
                                    <th></th>
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

    <div class="modal fade" id="public_link_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Public Asset Form Link</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="input-group">
                        <input type="text" class="form-control" id="public_link_input" value="<?php echo htmlspecialchars($publicUrl); ?>" readonly>
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
            employeeId: <?php echo (int) $employeeId; ?>,
            categories: <?php echo json_encode($asset_categories); ?>,
            holdStatuses: <?php echo json_encode($hold_statuses); ?>,
            managePage: true
        };
    </script>
    <?php include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/employee-asset-acknowledgement.js"></script>
</body>
</html>
