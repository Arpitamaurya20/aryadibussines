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
    $conn = _connectodb();

    $token = isset($_GET['t']) ? $_GET['t'] : '';
    $ticketId = hr_ticket_decode_id($token);
    $hr = new Hrticket($conn);
    $ticket = $ticketId > 0 ? $hr->getTicketById($ticketId) : null;

    if (!$ticket || !hr_ticket_user_can_view($_SESSION, $ticket)) {
        header('Location: ' . hr_ticket_my_list_url());
        exit;
    }

    $canManage = hr_ticket_user_can_manage($_SESSION, $ticket);
    $isOwner = hr_ticket_session_employee_id() === (int) ($ticket['employee_id'] ?? 0);
    $hr_reps = hr_ticket_hr_representative_list($conn);
    ?>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?> — HR Ticket</title>
    <?php include('../includes/common_head_content.php'); ?>
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
                        <?php if ($canManage) { ?>
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars(hr_ticket_manage_list_url()); ?>">HR Tickets</a></li>
                        <?php } else { ?>
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars(hr_ticket_my_list_url()); ?>">My HR Tickets</a></li>
                        <?php } ?>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?></li>
                    </ol>

                    <div class="hr-detail-hero">
                        <div>
                            <a href="<?= htmlspecialchars($canManage ? hr_ticket_manage_list_url() : hr_ticket_my_list_url()); ?>" class="hr-back-link d-inline-block mb-2">
                                <i class="fal fa-arrow-left"></i> Back
                            </a>
                            <h1><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?></h1>
                            <div class="hr-hero-meta">
                                <?= htmlspecialchars($ticket['subject'] ?? ''); ?>
                                · <?= htmlspecialchars($ticket['employee_name'] ?? ''); ?>
                            </div>
                        </div>
                        <div><?= hr_ticket_status_badge($ticket['status'] ?? ''); ?></div>
                    </div>

                    <div class="hr-card">
                        <ul class="nav nav-tabs hr-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#hr_details" role="tab">Details</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#hr_comments" role="tab">Comments</a>
                            </li>
                            <?php if ($canManage) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#hr_assignment" role="tab">Assignment &amp; Status</a>
                            </li>
                            <?php } ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#hr_history" role="tab">History</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active hr-tab-panel" id="hr_details" role="tabpanel">
                                <?php include('includes/hr_ticket_details_tab.php'); ?>
                            </div>
                            <div class="tab-pane fade hr-tab-panel" id="hr_comments" role="tabpanel">
                                <?php include('includes/hr_ticket_comments_tab.php'); ?>
                            </div>
                            <?php if ($canManage) { ?>
                            <div class="tab-pane fade hr-tab-panel" id="hr_assignment" role="tabpanel">
                                <?php include('includes/hr_ticket_assignment_tab.php'); ?>
                            </div>
                            <?php } ?>
                            <div class="tab-pane fade hr-tab-panel" id="hr_history" role="tabpanel">
                                <?php include('includes/hr_ticket_history_tab.php'); ?>
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
    <script src="../js/modules/hr-ticket-detail.js"></script>
</body>
</html>
