<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('controller/hr_tickets_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    hr_ticket_require_employee_access();
    $conn = _connectodb();
    $hr = new Hrticket($conn);
    $employeeId = hr_ticket_session_employee_id();
    $tickets = $hr->listTicketsForEmployee($employeeId);

    $statOpen = 0;
    $statActive = 0;
    $statResolved = 0;
    foreach ($tickets as $t) {
        $st = strtolower($t['status'] ?? '');
        if ($st === 'open') $statOpen++;
        if (in_array($st, ['in_progress', 'pending_employee_response', 'on_hold'], true)) $statActive++;
        if (in_array($st, ['resolved', 'closed'], true)) $statResolved++;
    }
    ?>
    <meta charset="utf-8">
    <title>My HR Tickets</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" href="css/hr-tickets.css">
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content hr-module">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">My HR Tickets</li>
                    </ol>

                    <div class="hr-page-header">
                        <div>
                            <h1>My HR Tickets</h1>
                            <p class="hr-subtitle">Raise and track HR requests, payment queries, and benefits concerns</p>
                        </div>
                        <button type="button" class="btn btn-primary" id="hr_btn_raise_ticket">
                            <i class="fal fa-plus-circle mr-1"></i> Raise ticket
                        </button>
                    </div>

                    <div class="hr-stats">
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= count($tickets); ?></div><div class="hr-stat-label">Total</div></div>
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= (int) $statOpen; ?></div><div class="hr-stat-label">Open</div></div>
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= (int) $statActive; ?></div><div class="hr-stat-label">In progress</div></div>
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= (int) $statResolved; ?></div><div class="hr-stat-label">Resolved / closed</div></div>
                    </div>

                    <div class="hr-card">
                        <div class="hr-card-header"><strong>Your tickets</strong></div>
                        <div class="hr-card-body flush">
                            <table id="hr_my_tickets_table" class="table table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Assigned HR</th>
                                        <th>Updated</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $t) { ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['ticket_code'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($t['subject'] ?? ''); ?></td>
                                        <td><?= hr_ticket_category_badge($t['category'] ?? ''); ?></td>
                                        <td><?= hr_ticket_status_badge($t['status'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($t['assigned_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($t['updated_at'] ?? $t['created_at'] ?? ''); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(hr_ticket_detail_url((int) ($t['id'] ?? 0))); ?>">
                                                <i class="fal fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php include('includes/raise-ticket-modal.php'); ?>
    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/hr-tickets.js"></script>
</body>
</html>
