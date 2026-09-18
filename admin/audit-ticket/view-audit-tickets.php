<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/audit_ticket_controller.php');
    setNavigation($_SESSION['Roles']);
    $UserType = SessionCheck();
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>Audit Tickets - TechXpert</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
    .at-filter-offcanvas { position: fixed; top: 0; right: -320px; width: 300px; height: 100%; background: #fff; z-index: 1050; box-shadow: -2px 0 8px rgba(0,0,0,.15); transition: right .3s; padding: 16px; }
    .at-filter-offcanvas.open { right: 0; }
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
                        <li class="breadcrumb-item active">Audit Tickets</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Corporate Audit Tickets</h2>
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm mr-2" onclick="atToggleFilter()"><i class="fa fa-filter"></i> Filters</button>
                                <a href="view-raise-audit-ticket" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Raise Ticket</a>
                            </div>
                        </div>
                        <?php include('./include/audit-ticket-list-view.php'); ?>
                    </div>

                    <div id="at_filter_offcanvas" class="at-filter-offcanvas">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Filters</h5>
                            <button type="button" class="close" onclick="atToggleFilter()">&times;</button>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="filter_status" class="form-control">
                                <option value="-1">All</option>
                                <?php foreach (auditTicketStatuses() as $st) { ?>
                                <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm btn-block" onclick="reloadAuditTicketTable()">Apply</button>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/audit-ticket.js"></script>
</body>
</html>
