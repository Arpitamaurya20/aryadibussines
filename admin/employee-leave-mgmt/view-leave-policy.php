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
    elm_require_hr_access();
    $conn = _connectodb();
    $elm = new Employeeleavemgmt($conn);
    $policy = $elm->getPolicy();
    ?>
    <meta charset="utf-8">
    <title>Leave Policy Settings</title>
    <?php include('../includes/common_head_content.php'); ?>
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
                        <li class="breadcrumb-item active">Leave Policy</li>
                    </ol>
                    <div class="panel">
                        <div class="panel-hdr"><h2>Company Leave Policy</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <form id="elm_policy_form">
                                    <div class="row">
                                        <?php
                                        $fields = [
                                            'cl_monthly_quota' => 'CL per month',
                                            'sl_monthly_quota' => 'SL per month',
                                            'cl_annual_quota' => 'CL financial-year cap',
                                            'sl_annual_quota' => 'SL financial-year cap',
                                            'annual_total_quota' => 'Total financial-year cap',
                                            'advance_leave_max_days' => 'Max advance days (per type/year)',
                                            'carry_forward_max_per_type' => 'Max carry/month (0 = all unused carries)',
                                            'comp_off_validity_days' => 'Comp-off validity (days)',
                                            'min_notice_days_cl' => 'CL min notice (days)',
                                            'min_notice_days_sl' => 'SL min notice (days)',
                                            'max_consecutive_days' => 'Max consecutive days',
                                            'leave_year_start_month' => 'Financial year start month (4=April)',
                                        ];
                                        foreach ($fields as $key => $label) {
                                            $val = htmlspecialchars($policy[$key] ?? '');
                                        ?>
                                        <div class="col-md-4 form-group">
                                            <label><?= $label ?></label>
                                            <input type="text" name="<?= $key ?>" class="form-control" value="<?= $val ?>">
                                        </div>
                                        <?php } ?>
                                        <?php
                                        $toggles = [
                                            'half_day_enabled' => 'Half day',
                                            'sandwich_rule_enabled' => 'Sandwich rule',
                                            'sandwich_include_weekly_off' => 'Sandwich includes weekly off',
                                            'sandwich_include_holidays' => 'Sandwich includes holidays',
                                            'advance_leave_enabled' => 'Advance leave',
                                            'carry_forward_enabled' => 'Carry forward',
                                            'comp_off_enabled' => 'Comp-off',
                                            'unpaid_leave_enabled' => 'Unpaid leave (LWP)',
                                            'comp_off_requires_approval' => 'Comp-off needs approval',
                                        ];
                                        foreach ($toggles as $key => $label) {
                                            $checked = in_array(strtolower($policy[$key] ?? ''), ['1','true','yes'], true) ? 'checked' : '';
                                        ?>
                                        <div class="col-md-4 form-group">
                                            <div class="custom-control custom-checkbox mt-4">
                                                <input type="hidden" name="<?= $key ?>" value="0">
                                                <input type="checkbox" class="custom-control-input" id="<?= $key ?>" name="<?= $key ?>" value="1" <?= $checked ?>>
                                                <label class="custom-control-label" for="<?= $key ?>"><?= $label ?></label>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0">
                                        Annual caps and accrual reset use the <strong>financial year</strong> (default April–March).
                                        Set start month to <code>4</code> for India FY. Monthly balance rows use calendar months; carry-forward resets at FY start.
                                    </p>
                                    <button type="button" class="btn btn-primary" id="elm_save_policy">Save Policy</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <script>
    $('#elm_save_policy').on('click', function(){
        $.post('../employee-leave-mgmt/action/save-policy.php', $('#elm_policy_form').serialize(), function(r){
            alert(r.message || 'Saved');
        }, 'json');
    });
    </script>
</body>
</html>
