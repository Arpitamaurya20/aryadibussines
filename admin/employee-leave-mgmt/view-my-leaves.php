<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('controller/employee_leave_mgmt_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    elm_require_employee_access();
    $conn = _connectodb();
    $elm = new Employeeleavemgmt($conn);
    $employeeId = elm_session_employee_id();
    $balance = $elm->getBalanceSummary($employeeId);
    $leaves = $elm->listEmployeeLeaves($employeeId, 50);
    $compOffs = $elm->listCompOff($employeeId);
    $policy = $balance['policy'] ?? [];
    $fy = $balance['financial_year'] ?? [];
    $fyLabel = htmlspecialchars((string) ($fy['financial_year_label'] ?? ''));
    ?>
    <meta charset="utf-8">
    <title>My Leave</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" href="css/employee-leave-mgmt.css">
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
                        <li class="breadcrumb-item active">My Leave</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        <strong>Policy:</strong> <?= (float)($policy['cl_monthly_quota']??1) ?> CL + <?= (float)($policy['sl_monthly_quota']??1) ?> SL per month
                        (<?= (float)($policy['annual_total_quota']??24) ?>/financial year).
                        <?php if ($fyLabel !== '') { ?>
                        <strong><?= $fyLabel ?></strong>
                        (<?= htmlspecialchars((string)($fy['financial_year_start']??'')) ?> – <?= htmlspecialchars((string)($fy['financial_year_end']??'')) ?>).
                        <?php } ?>
                        Unused balance carries forward month to month from your joining date or FY start.
                        <?php if (!empty($balance['joining_date'])) { ?>
                        <br><small>Joining: <?= htmlspecialchars($balance['joining_date']) ?>
                        — accrued <?= (int)($balance['accrual_months']??0) ?> month(s) in this financial year
                        (from <?= htmlspecialchars((string)($balance['accrual_from']??'')) ?>).</small>
                        <?php } ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card elm-balance-card p-3">
                                <div class="elm-stat-value"><?= number_format((float)($balance['monthly']['CL']['available']??0), 1) ?></div>
                                <div class="elm-stat-label">CL available</div>
                                <small class="text-muted">This month + carry: <?= number_format((float)($balance['monthly']['CL']['entitled']??0), 1) ?> + <?= number_format((float)($balance['monthly']['CL']['carried_in']??0), 1) ?> carried</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card elm-balance-card sl p-3">
                                <div class="elm-stat-value"><?= number_format((float)($balance['monthly']['SL']['available']??0), 1) ?></div>
                                <div class="elm-stat-label">SL available</div>
                                <small class="text-muted">This month + carry: <?= number_format((float)($balance['monthly']['SL']['entitled']??0), 1) ?> + <?= number_format((float)($balance['monthly']['SL']['carried_in']??0), 1) ?> carried</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card elm-balance-card p-3" style="border-left-color:#8e44ad;">
                                <div class="elm-stat-value"><?= number_format((float)($balance['total_available']??0), 1) ?></div>
                                <div class="elm-stat-label">Total CL + SL</div>
                                <small class="text-muted">FY cap left: <?= number_format((float)(($balance['annual']['cl_remaining']??0)+($balance['annual']['sl_remaining']??0)), 1) ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card elm-balance-card comp p-3">
                                <div class="elm-stat-value"><?= number_format((float)($balance['comp_off']['available']??0), 1) ?></div>
                                <div class="elm-stat-label">Comp-off balance</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#elm_apply_modal">
                            <i class="fal fa-plus-circle mr-1"></i> Apply Leave
                        </button>
                        <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#elm_compoff_modal">
                            <i class="fal fa-briefcase mr-1"></i> Request Comp-off
                        </button>
                    </div>

                    <div class="panel">
                        <div class="panel-hdr"><h2>Leave History</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table class="table table-bordered table-hover" id="elm_leave_table">
                                    <thead>
                                        <tr>
                                            <th>Type</th><th>From</th><th>To</th><th>Days</th><th>Duration</th>
                                            <th>Status</th><th>Reason</th><th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($leaves as $lv) {
                                        $lv = elm_format_leave_row($lv);
                                        $canCancel = in_array($lv['Status'] ?? '', ['Pending', 'SupervisorApproved'], true) && empty($lv['CancelledAt']);
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($lv['TypeOfLeave'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($lv['FromDate'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($lv['ToDate'] ?? '') ?></td>
                                            <td><?= number_format((float)$lv['DisplayDays'], 1) ?>
                                                <?php if (!empty($lv['SandwichNote'])) { ?><br><span class="elm-sandwich-flag"><?= $lv['SandwichNote'] ?></span><?php } ?>
                                                <?php if (!empty($lv['IsAdvanceLeave'])) { ?><br><span class="badge badge-warning">Advance</span><?php } ?>
                                            </td>
                                            <td><?= htmlspecialchars($lv['Duration'] ?? '') ?></td>
                                            <td><?= elm_leave_status_badge($lv['Status'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($lv['ReasonOfLeave'] ?? '') ?></td>
                                            <td>
                                                <?php if ($canCancel) { ?>
                                                <button class="btn btn-xs btn-outline-danger elm-cancel-btn" data-id="<?= (int)$lv['ID'] ?>">Cancel</button>
                                                <?php } else { echo '—'; } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($compOffs)) { ?>
                    <div class="panel mt-3">
                        <div class="panel-hdr"><h2>Comp-off Requests</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>Work Date</th><th>Credit</th><th>Status</th><th>Expires</th><th>Reason</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($compOffs as $co) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($co['work_date']) ?></td>
                                            <td><?= (float)$co['credit_days'] ?></td>
                                            <td><?= elm_leave_status_badge($co['status']) ?></td>
                                            <td><?= htmlspecialchars($co['expires_at'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($co['reason'] ?? '') ?></td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <!-- Apply Leave Modal -->
    <div class="modal fade" id="elm_apply_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Apply Leave</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="elm_validate_result" class="alert d-none"></div>
                    <form id="elm_apply_form">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Leave Type</label>
                                <select name="TypeOfLeave" id="elm_type" class="form-control" required>
                                    <option value="CL">CL — Casual Leave</option>
                                    <option value="SL">SL — Sick Leave</option>
                                    <option value="COMPOFF">Comp-off</option>
                                    <option value="UNPAID">Unpaid Leave (LWP)</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Duration</label>
                                <select name="Duration" id="elm_duration" class="form-control">
                                    <option value="Full Day">Full Day</option>
                                    <option value="Half Day">Half Day</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>From Date</label>
                                <input type="date" name="FromDate" id="elm_from" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>To Date</label>
                                <input type="date" name="ToDate" id="elm_to" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <p class="elm-policy-note mb-2">
                                    <strong>Tip:</strong> Only <em>working days</em> count (weekly offs/holidays excluded).
                                    To apply <strong>5 CL</strong> with <strong>6 available</strong>, choose about 5 working days in one stretch
                                    (e.g. Mon–Fri in July). Max <?= (int)($policy['max_consecutive_days'] ?? 10) ?> leave days per application.
                                    <?php if (!empty($policy['unpaid_leave_enabled'])) { ?>
                                    If CL/SL balance is exhausted, choose <strong>Unpaid Leave (LWP)</strong> — no balance needed; salary may be deducted after HR approval.
                                    <?php } ?>
                                </p>
                            </div>
                            <div class="col-md-6 form-group d-none" id="elm_half_wrap">
                                <label>Half Day Session</label>
                                <select name="HalfDaySession" id="elm_half_session" class="form-control">
                                    <option value="first_half">First Half</option>
                                    <option value="second_half">Second Half</option>
                                </select>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>Reason</label>
                                <textarea name="ReasonOfLeave" id="elm_reason" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="elm_btn_validate">Check Balance</button>
                    <button type="button" class="btn btn-primary" id="elm_btn_apply">Submit Leave</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Comp-off Modal -->
    <div class="modal fade" id="elm_compoff_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Request Comp-off</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="elm-policy-note">Claim comp-off when you worked on a public holiday or weekly off (attendance punch required).</p>
                    <form id="elm_compoff_form">
                        <div class="form-group">
                            <label>Work Date (holiday / weekly off)</label>
                            <input type="date" name="work_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Credit Days</label>
                            <select name="credit_days" class="form-control">
                                <option value="1">1 Full Day</option>
                                <option value="0.5">0.5 Half Day</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reason / Work done</label>
                            <textarea name="reason" class="form-control" rows="2" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="elm_btn_compoff">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/employee-leave-mgmt.js"></script>
</body>
</html>
