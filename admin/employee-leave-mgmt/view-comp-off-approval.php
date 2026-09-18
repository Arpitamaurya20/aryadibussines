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
    $pending = _getSQLRecords($conn, "SELECT c.*, e.Name AS employee_name FROM employee_comp_off c
        INNER JOIN employees e ON e.ID = c.employee_id
        WHERE c.status = 'Pending' ORDER BY c.id DESC");
    ?>
    <meta charset="utf-8">
    <title>Comp-off Approval</title>
    <?php include('../includes/common_head_content.php'); ?>
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
                        <li class="breadcrumb-item active">Comp-off Approval</li>
                    </ol>
                    <div class="panel">
                        <div class="panel-hdr"><h2>Pending Comp-off</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table class="table table-bordered">
                                    <thead><tr><th>Employee</th><th>Work Date</th><th>Credit</th><th>Reason</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php if (empty($pending)) { ?>
                                        <tr><td colspan="5" class="text-center text-muted">No pending requests</td></tr>
                                    <?php } foreach ($pending as $p) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['employee_name']) ?></td>
                                            <td><?= htmlspecialchars($p['work_date']) ?></td>
                                            <td><?= (float)$p['credit_days'] ?></td>
                                            <td><?= htmlspecialchars($p['reason'] ?? '') ?></td>
                                            <td>
                                                <button class="btn btn-xs btn-success elm-co-approve" data-id="<?= (int)$p['id'] ?>">Approve</button>
                                                <button class="btn btn-xs btn-danger elm-co-reject" data-id="<?= (int)$p['id'] ?>">Reject</button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
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
    (function(){
        var base = '../employee-leave-mgmt/action/';
        $('.elm-co-approve').on('click', function(){
            var id = $(this).data('id');
            $.post(base + 'approve-comp-off.php', { comp_off_id: id, approve: 1 }, function(r){
                alert(r.message); if(!r.error) location.reload();
            }, 'json');
        });
        $('.elm-co-reject').on('click', function(){
            var reason = prompt('Rejection reason (optional):') || '';
            var id = $(this).data('id');
            $.post(base + 'approve-comp-off.php', { comp_off_id: id, approve: 0, reason: reason }, function(r){
                alert(r.message); if(!r.error) location.reload();
            }, 'json');
        });
    })();
    </script>
</body>
</html>
