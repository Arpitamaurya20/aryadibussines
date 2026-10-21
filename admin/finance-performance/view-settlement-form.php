<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'settlements');
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
    <title>Advance Settlement - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-lines th { font-size: 11.5px; white-space: nowrap; vertical-align: middle; }
        #fp-lines td { padding: 4px; vertical-align: top; }
        #fp-lines .form-control { font-size: 12px; padding: 4px 6px; height: 30px; }
        #fp-lines .fp-col-category { min-width: 210px; }
        #fp-lines .fp-col-desc { min-width: 200px; }
        #fp-lines .fp-col-date { min-width: 135px; }
        #fp-lines .fp-col-text { min-width: 130px; }
        #fp-lines .fp-col-money { min-width: 105px; }
        #fp-lines .fp-line-hint { font-size: 11px; margin-top: 2px; }
        .fp-figure .small { color: #6c757d; }
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
                        <li class="breadcrumb-item"><a href="view-settlements.php">Settlements</a></li>
                        <li class="breadcrumb-item active" id="fp-breadcrumb-title">Settle advance</li>
                    </ol>

                    <div id="fp-form-loading" class="text-center text-muted p-5">Loading...</div>

                    <form id="fp-settlement-form" novalidate style="display:none">
                        <div class="alert alert-danger" id="fp-error-summary" style="display:none"></div>
                        <div class="alert alert-warning" id="fp-return-reason" style="display:none"></div>

                        <div class="panel">
                            <div class="panel-hdr">
                                <h2 id="fp-form-title">Settle advance</h2>
                                <div class="panel-toolbar"><span class="badge badge-secondary mr-3" id="fp-claim-status" style="display:none"></span></div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div class="mb-2" id="fp-advance-info"></div>
                                    <div class="row text-center border rounded py-2 mx-0 mb-3 fp-figure">
                                        <div class="col-6 col-md"><div class="small">Advance paid to you</div><strong id="fp-fig-paid">-</strong></div>
                                        <div class="col-6 col-md"><div class="small">Expenses already accepted</div><strong id="fp-fig-accepted">-</strong></div>
                                        <div class="col-6 col-md"><div class="small">Cash you returned</div><strong id="fp-fig-refund">-</strong></div>
                                        <div class="col-6 col-md"><div class="small">Extra paid to you</div><strong id="fp-fig-additional">-</strong></div>
                                        <div class="col-12 col-md"><div class="small">Position now</div><strong id="fp-fig-balance">-</strong></div>
                                    </div>
                                    <p class="small text-muted mb-3">
                                        List what you actually spent from this advance and attach the bill for each line.
                                        Your branch manager checks the claim first, then Head Office Finance accepts each line.
                                        If you spent less than the advance, return the rest to Finance; if you spent more, Finance pays you the difference.
                                    </p>
                                    <div class="form-group mb-0">
                                        <label for="fp-stl-notes">Notes</label>
                                        <textarea class="form-control" name="Notes" id="fp-stl-notes" rows="2" maxlength="1000" placeholder="Optional, for example 'second part of the trip expenses'"></textarea>
                                        <div class="invalid-feedback" data-for="Notes"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-hdr">
                                <h2>Expenses paid from the advance</h2>
                                <div class="panel-toolbar">
                                    <button type="button" class="btn btn-outline-primary btn-sm mr-3" id="fp-add-line"><i class="fa-solid fa-plus mr-1"></i>Add line</button>
                                </div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div class="text-danger small mb-2" data-for="lines"></div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0" id="fp-lines">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Category *</th>
                                                    <th>Description *</th>
                                                    <th>Expense date *</th>
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
                                                <tr><th colspan="8" class="text-right">Total claimed</th><th class="text-right" id="fp-total">0.00</th><th colspan="2"></th></tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-hdr"><h2>Bills and receipts</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div id="fp-attach-locked" class="text-muted">Save the draft first, then attach the bill, receipt or invoice for each line.</div>
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
                                                <input type="text" class="form-control" id="fp-attach-notes" maxlength="255" placeholder="Optional">
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
                                        <small class="form-text text-muted">Files are stored privately. Only you, your branch manager and Finance can open them after you submit.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap mb-4">
                            <a href="#" class="btn btn-secondary mr-2 mb-2" id="fp-back">Back to the advance</a>
                            <button type="button" class="btn btn-outline-danger mr-auto mb-2" id="fp-cancel-settlement" style="display:none">Cancel settlement</button>
                            <button type="button" class="btn btn-outline-primary mr-2 mb-2 ml-auto" id="fp-save-draft"><i class="fa-solid fa-floppy-disk mr-1"></i>Save draft</button>
                            <button type="button" class="btn btn-primary mb-2" id="fp-submit"><i class="fa-solid fa-paper-plane mr-1"></i>Submit for branch review</button>
                        </div>
                    </form>

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
    <script src="../js/modules/fp_settlement_form.js"></script>
</body>
</html>
