<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('controller/hc_tickets_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $hc = new Hcticket($conn);
    $tickets = $hc->listTickets();
    $state_list = hc_ticket_state_list($conn);

    $statTotal = count($tickets);
    $statNew = 0;
    $statAssigned = 0;
    foreach ($tickets as $t) {
        $st = strtolower($t['status'] ?? '');
        if (in_array($st, ['new', 'raised'], true)) {
            $statNew++;
        }
        if ((int) ($t['assigned_to'] ?? 0) > 0) {
            $statAssigned++;
        }
    }
    ?>
    <meta charset="utf-8">
    <title>Home Care Tickets</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" href="css/hc-tickets.css">
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content hc-module">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">Home Care Tickets</li>
                    </ol>

                    <div class="hc-page-header">
                        <div>
                            <h1>Home Care Tickets</h1>
                            <p class="hc-subtitle">Raise, assign, and track service tickets end to end</p>
                        </div>
                        <button type="button" class="btn btn-primary" id="hc_btn_raise_ticket">
                            <span class="hc-btn-text"><i class="fal fa-plus-circle mr-1"></i> Raise ticket</span>
                        </button>
                    </div>

                    <div class="hc-stats">
                        <div class="hc-stat-card">
                            <div class="hc-stat-value"><?= (int) $statTotal; ?></div>
                            <div class="hc-stat-label">Total tickets</div>
                        </div>
                        <div class="hc-stat-card">
                            <div class="hc-stat-value"><?= (int) $statNew; ?></div>
                            <div class="hc-stat-label">Open / new</div>
                        </div>
                        <div class="hc-stat-card">
                            <div class="hc-stat-value"><?= (int) $statAssigned; ?></div>
                            <div class="hc-stat-label">Assigned</div>
                        </div>
                    </div>

                    <div class="hc-card">
                        <div class="hc-card-header">
                            <span class="font-weight-bold text-dark">All tickets</span>
                        </div>
                        <div class="hc-card-body flush">
                            <table id="hc_tickets_table" class="table table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th>Ticket code</th>
                                        <th>Name</th>
                                        <th>City</th>
                                        <th>State</th>
                                        <th>Purpose</th>
                                        <th>Timeline</th>
                                        <th>Booking</th>
                                        <th>Status</th>
                                        <th>Created by</th>
                                        <th>Updated</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $t) {
                                        $timeline = ($t['timeline_type'] ?? '') === 'custom'
                                            ? 'Custom'
                                            : 'Immediate';
                                        $booking = trim(($t['booking_date'] ?? '') . ' ' . ($t['booking_time'] ?? ''));
                                        ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['ticket_code'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($t['name'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($t['city'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($t['state_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars(ucfirst($t['purpose'] ?? '')); ?></td>
                                        <td><?= htmlspecialchars($timeline); ?></td>
                                        <td><?= htmlspecialchars($booking); ?></td>
                                        <td><?= hc_status_badge($t['status'] ?? 'new'); ?></td>
                                        <td><?= htmlspecialchars($t['created_by'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($t['updated_at'] ?? $t['created_at'] ?? '—'); ?></td>
                                        <td>
                                            <a class="hc-btn-outline btn btn-sm" href="<?= htmlspecialchars(hc_ticket_detail_url((int) ($t['id'] ?? 0))); ?>">
                                                <i class="fal fa-eye mr-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php include('includes/raise-ticket-modal.php'); ?>

    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="js/hc-tickets.js"></script>
    <script>
        $('#hc_tickets_table').dataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: { search: '', searchPlaceholder: 'Search tickets…' }
        });
    </script>
</body>
</html>
