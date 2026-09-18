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

    $token = isset($_GET['t']) ? $_GET['t'] : '';
    $ticketId = hc_ticket_decode_id($token);
    $hc = new Hcticket($conn);
    $ticket = $ticketId > 0 ? $hc->getTicketById($ticketId) : null;
    if (!$ticket) {
        header('Location: ' . hc_ticket_list_url());
        exit;
    }

    $technician_list = hc_ticket_technician_list($conn);
    $ticketStatus = $ticket['status'] ?? 'new';
    ?>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?> — Home Care</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars(hc_ticket_list_url()); ?>">Home Care Tickets</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?></li>
                    </ol>

                    <div class="hc-detail-hero">
                        <div>
                            <a href="<?= htmlspecialchars(hc_ticket_list_url()); ?>" class="hc-back-link mb-2 d-inline-block">
                                <i class="fal fa-arrow-left"></i> Back to tickets
                            </a>
                            <h1><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?></h1>
                            <div class="hc-hero-meta">
                                <?= htmlspecialchars($ticket['name'] ?? ''); ?>
                                <?php if (!empty($ticket['city'])) { ?>
                                    · <?= htmlspecialchars($ticket['city']); ?>
                                <?php } ?>
                                · Booked <?= htmlspecialchars($ticket['booking_date'] ?? ''); ?>
                            </div>
                        </div>
                        <div><?= hc_status_badge($ticketStatus); ?></div>
                    </div>

                    <div class="hc-card">
                        <ul class="nav nav-tabs hc-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#hc_details" role="tab">
                                    <i class="fal fa-file-alt mr-1"></i> Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#hc_assignment" role="tab">
                                    <i class="fal fa-user-hard-hat mr-1"></i> Assignment &amp; Status
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#hc_history" role="tab">
                                    <i class="fal fa-history mr-1"></i> History
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active hc-tab-panel" id="hc_details" role="tabpanel">
                                <?php include('includes/hc_ticket_details_tab.php'); ?>
                            </div>
                            <div class="tab-pane fade hc-tab-panel" id="hc_assignment" role="tabpanel">
                                <?php include('includes/hc_ticket_assignment_tab.php'); ?>
                            </div>
                            <div class="tab-pane fade hc-tab-panel" id="hc_history" role="tabpanel">
                                <?php include('includes/hc_ticket_history_tab.php'); ?>
                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="js/hc-ticket-detail.js"></script>
</body>
</html>
