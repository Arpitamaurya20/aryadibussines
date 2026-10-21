<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'offices');
$UserType = $fpActor['user_type'];

setNavigation($_SESSION['Roles'] ?? array());

function fp_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Branch Offices - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        #fp-office-table td, #fp-assignment-table td { vertical-align: middle; font-size: 12.5px; }
    </style>
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
                        <li class="breadcrumb-item">Finance &amp; Performance</li>
                        <li class="breadcrumb-item">Settings</li>
                        <li class="breadcrumb-item active">Branch Offices</li>
                    </ol>

                    <div class="alert alert-info">
                        Branch offices are TechXpert's own operating offices (not client branch sites). Each office belongs to one legal entity and state.
                        Employees raise expense requests only against offices they are assigned to, and branch managers see requests for the offices they manage.
                    </div>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Branch Offices</h2>
                            <div class="panel-toolbar">
                                <button type="button" class="btn btn-info btn-sm mr-3" id="fp-office-add"><i class="fa-solid fa-plus mr-1"></i>Add office</button>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table id="fp-office-table" class="table table-bordered table-hover table-striped w-100">
                                    <thead class="thead-light">
                                        <tr><th>Code</th><th>Office</th><th>Legal entity</th><th>State / City</th><th>Requesters</th><th>Managers</th><th>Requests</th><th>Status</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-office-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-office-modal-title">Add office</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <form id="fp-office-form" novalidate>
                                    <div class="modal-body">
                                        <input type="hidden" name="ID" id="fp-office-id" value="0">
                                        <input type="hidden" name="RowVersion" id="fp-office-version" value="0">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="fp-office-code">Office code <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control text-uppercase" name="OfficeCode" id="fp-office-code" maxlength="20" autocomplete="off">
                                                <div class="invalid-feedback" data-for="OfficeCode"></div>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label for="fp-office-name">Office name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="OfficeName" id="fp-office-name" maxlength="150" autocomplete="off">
                                                <div class="invalid-feedback" data-for="OfficeName"></div>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="fp-office-company">Legal entity <span class="text-danger">*</span></label>
                                                <select class="form-control" name="CompanyID" id="fp-office-company"></select>
                                                <div class="invalid-feedback" data-for="CompanyID"></div>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="fp-office-state">State <span class="text-danger">*</span></label>
                                                <select class="form-control" name="StateID" id="fp-office-state"></select>
                                                <div class="invalid-feedback" data-for="StateID"></div>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="fp-office-city">City</label>
                                                <select class="form-control" name="CityID" id="fp-office-city"></select>
                                                <div class="invalid-feedback" data-for="CityID"></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted mt-n2 mb-2" id="fp-office-lock-note" style="display:none">Legal entity and state are locked because requests already exist for this office.</small>
                                        <div class="form-group">
                                            <label for="fp-office-address">Address</label>
                                            <textarea class="form-control" name="Address" id="fp-office-address" rows="2" maxlength="500"></textarea>
                                            <div class="invalid-feedback" data-for="Address"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="fp-office-save">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-assignment-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-assignment-title">Employees</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-row align-items-end mb-3">
                                        <div class="col-md-6">
                                            <label for="fp-assign-employee">Add employee</label>
                                            <select class="form-control" id="fp-assign-employee" style="width:100%"></select>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                <input type="checkbox" class="custom-control-input" id="fp-assign-request" checked>
                                                <label class="custom-control-label" for="fp-assign-request">Can raise requests</label>
                                            </div>
                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                <input type="checkbox" class="custom-control-input" id="fp-assign-manager">
                                                <label class="custom-control-label" for="fp-assign-manager">Branch manager</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary btn-block" id="fp-assign-add">Assign</button>
                                        </div>
                                    </div>
                                    <table id="fp-assignment-table" class="table table-sm table-bordered w-100">
                                        <thead class="thead-light"><tr><th>Employee</th><th>Designation</th><th class="text-center">Can raise requests</th><th class="text-center">Branch manager</th><th>Status</th><th>Actions</th></tr></thead>
                                        <tbody></tbody>
                                    </table>
                                    <p class="small text-muted mb-0">An employee assigned to more than one office as a requester chooses the office on each request. Branch managers see submitted requests for their offices.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-reason-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-reason-title">Confirm</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p id="fp-reason-text" class="mb-2"></p>
                                    <label for="fp-reason-input" id="fp-reason-label">Reason</label>
                                    <textarea class="form-control" id="fp-reason-input" rows="3" maxlength="500"></textarea>
                                    <div class="invalid-feedback">Please give a reason (at least 5 characters).</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger" id="fp-reason-confirm">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/fp_common.js"></script>
    <script src="../js/modules/fp_offices.js"></script>
</body>
</html>
