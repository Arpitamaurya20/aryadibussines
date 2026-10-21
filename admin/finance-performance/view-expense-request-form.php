<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'new_request');
$UserType = $fpActor['user_type'];
$fpEditing = isset($_GET['id']) && ctype_digit((string) $_GET['id']) && (int) $_GET['id'] > 0;

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
    <title><?php echo $fpEditing ? 'Edit Request' : 'New Request'; ?> - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-items th { font-size: 11.5px; white-space: nowrap; vertical-align: middle; }
        #fp-items td { padding: 4px; vertical-align: top; }
        #fp-items .form-control { font-size: 12px; padding: 4px 6px; height: 30px; }
        #fp-items .fp-col-category { min-width: 210px; }
        #fp-items .fp-col-desc { min-width: 200px; }
        #fp-items .fp-col-date { min-width: 135px; }
        #fp-items .fp-col-text { min-width: 130px; }
        #fp-items .fp-col-money { min-width: 105px; }
        #fp-items .fp-line-hint { font-size: 11px; margin-top: 2px; }
        .fp-type-option { cursor: pointer; }
        .fp-type-option.active { border-color: #2196f3 !important; background: #f2f8ff; }
        .fp-derived { background: #f7f7f7; border-radius: 4px; padding: 6px 10px; min-height: 34px; }
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
                        <li class="breadcrumb-item"><a href="view-expense-requests.php">Expense Booking</a></li>
                        <li class="breadcrumb-item active" id="fp-breadcrumb-title"><?php echo $fpEditing ? 'Edit Request' : 'New Request'; ?></li>
                    </ol>

                    <div id="fp-form-loading" class="text-center text-muted p-5">Loading...</div>
                    <div id="fp-no-office" class="alert alert-warning" style="display:none">
                        You are not assigned to any active branch office, so you cannot raise expense requests yet. Ask your administrator to assign you under
                        Finance &amp; Performance &gt; Settings &gt; Branch Offices.
                    </div>

                    <form id="fp-request-form" novalidate style="display:none">
                        <input type="hidden" name="ID" id="fp-req-id" value="0">
                        <input type="hidden" name="RowVersion" id="fp-req-version" value="0">

                        <div class="alert alert-danger" id="fp-error-summary" style="display:none"></div>

                        <div class="panel">
                            <div class="panel-hdr">
                                <h2 id="fp-form-title">New expense request</h2>
                                <div class="panel-toolbar"><span class="badge badge-secondary mr-3" id="fp-req-status" style="display:none"></span></div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <label class="mb-1">Request type <span class="text-danger">*</span></label>
                                    <div class="form-row mb-1" id="fp-type-options"></div>
                                    <div class="text-danger small mb-2" data-for="RequestType"></div>
                                    <div class="alert alert-info py-2" id="fp-recurring-note" style="display:none"></div>

                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="fp-req-office">Branch office <span class="text-danger">*</span></label>
                                            <select class="form-control" name="OfficeID" id="fp-req-office"></select>
                                            <div class="invalid-feedback" data-for="OfficeID"></div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Legal entity</label>
                                            <div class="fp-derived" id="fp-req-company">-</div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>State</label>
                                            <div class="fp-derived" id="fp-req-state">-</div>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted mt-n2 mb-3">Legal entity and state come from the branch office and are checked again on the server.</small>

                                    <div id="fp-direct-box" class="border rounded p-3 mb-3" style="display:none">
                                        <h6 class="mb-2">Who is paid, and by when</h6>
                                        <div class="form-row">
                                            <div class="col-md-8" id="fp-req-vendor"></div>
                                            <div class="form-group col-md-4">
                                                <label for="fp-req-due">Due date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="DueDate" id="fp-req-due">
                                                <small class="form-text text-muted">Date by which the vendor must be paid (from the bill). Needed to submit.</small>
                                                <div class="invalid-feedback" data-for="DueDate"></div>
                                            </div>
                                        </div>
                                        <small class="text-muted">Head Office pays this vendor directly after approval; part payments are allowed. Line items with an empty vendor use this payee.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="fp-req-purpose">Purpose <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="Purpose" id="fp-req-purpose" rows="2" maxlength="1000"></textarea>
                                        <div class="invalid-feedback" data-for="Purpose"></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="fp-req-from">Period from</label>
                                            <input type="date" class="form-control" name="PeriodFrom" id="fp-req-from">
                                            <div class="invalid-feedback" data-for="PeriodFrom"></div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="fp-req-to">Period to</label>
                                            <input type="date" class="form-control" name="PeriodTo" id="fp-req-to">
                                            <div class="invalid-feedback" data-for="PeriodTo"></div>
                                        </div>
                                        <div class="form-group col-md-6 d-flex align-items-end">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" name="IsRecurring" id="fp-req-recurring" value="1">
                                                <label class="custom-control-label" for="fp-req-recurring">Recurring expense (for example monthly rent); needs a period</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="fp-req-remarks">Remarks</label>
                                        <textarea class="form-control" name="Remarks" id="fp-req-remarks" rows="2" maxlength="1000"></textarea>
                                        <div class="invalid-feedback" data-for="Remarks"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-hdr">
                                <h2>Line items</h2>
                                <div class="panel-toolbar">
                                    <button type="button" class="btn btn-outline-primary btn-sm mr-3" id="fp-add-line"><i class="fa-solid fa-plus mr-1"></i>Add line</button>
                                </div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div class="text-danger small mb-2" data-for="items"></div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0" id="fp-items">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Category *</th>
                                                    <th>Description *</th>
                                                    <th id="fp-date-head">Expense date *</th>
                                                    <th>Vendor</th>
                                                    <th>Vendor GSTIN</th>
                                                    <th>Invoice no.</th>
                                                    <th>Invoice date</th>
                                                    <th class="text-right">Amount (INR) *</th>
                                                    <th class="text-right">of which tax</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                                <tr><th colspan="8" class="text-right">Total</th><th class="text-right" id="fp-total">0.00</th><th colspan="2"></th></tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <small class="text-muted" id="fp-type-hint"></small>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-hdr"><h2>Attachments</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div id="fp-attach-locked" class="text-muted">Save the draft first, then attach bills, receipts, invoices or quotations to each line.</div>
                                    <div id="fp-attach-area" style="display:none">
                                        <div class="form-row align-items-end">
                                            <div class="form-group col-md-3">
                                                <label for="fp-attach-line">For line</label>
                                                <select class="form-control" id="fp-attach-line"></select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="fp-attach-type">Document type <span class="text-danger">*</span></label>
                                                <select class="form-control" id="fp-attach-type"></select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="fp-attach-notes">Note</label>
                                                <input type="text" class="form-control" id="fp-attach-notes" maxlength="255" placeholder="Optional, for example 'quotation from second vendor'">
                                            </div>
                                        </div>
                                        <div class="form-row align-items-end mb-3">
                                            <div class="form-group col-md-9 mb-0">
                                                <label for="fp-attach-file">File (PDF, JPG, PNG or WEBP, up to <span id="fp-attach-max"></span>)</label>
                                                <input type="file" class="form-control" id="fp-attach-file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                                            </div>
                                            <div class="form-group col-md-3 mb-0">
                                                <button type="button" class="btn btn-outline-primary btn-block" id="fp-attach-upload"><i class="fa-solid fa-paperclip mr-1"></i>Upload</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="thead-light"><tr><th>Line</th><th>Type</th><th>File</th><th>Note</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
                                                <tbody id="fp-attach-list"></tbody>
                                            </table>
                                        </div>
                                        <small class="form-text text-muted">Files are stored privately. Only you, your approvers and Finance can open them.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap mb-4">
                            <a href="view-expense-requests.php" class="btn btn-secondary mr-2 mb-2">Back to My Requests</a>
                            <button type="button" class="btn btn-outline-danger mr-auto mb-2" id="fp-cancel-request" style="display:none">Cancel request</button>
                            <button type="button" class="btn btn-outline-primary mr-2 mb-2" id="fp-save-draft"><i class="fa-solid fa-floppy-disk mr-1"></i>Save draft</button>
                            <button type="button" class="btn btn-primary mb-2" id="fp-submit"><i class="fa-solid fa-paper-plane mr-1"></i>Submit for approval</button>
                        </div>
                    </form>

                    <div class="modal fade" id="fp-reason-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header" style="background-color:#003f88;color:#fff">
                                    <h5 class="modal-title" id="fp-reason-title">Confirm</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:1"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p id="fp-reason-text" class="mb-2"></p>
                                    <label for="fp-reason-input" id="fp-reason-label">Reason</label>
                                    <textarea class="form-control" id="fp-reason-input" rows="3" maxlength="500"></textarea>
                                    <div class="invalid-feedback">Please give a reason (at least 5 characters).</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Back</button>
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
    <script src="../js/modules/fp_common.js"></script>
    <script src="../js/modules/fp_vendor_picker.js"></script>
    <script src="../js/modules/fp_request_form.js"></script>
</body>
</html>
