<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('../company/controller/company_controller.php');
    include('controller/tender_rfq_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    if (!isset($_Nav_Tender_RFQ) || !$_Nav_Tender_RFQ) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access denied.';
        exit;
    }
    $trfq = new TenderRfq($conn);
    $tickets = $trfq->listTickets(tender_rfq_session_roles(), $UserType);
    ?>
    <meta charset="utf-8">
    <title>Tender RFQ Tickets</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
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
                        <li class="breadcrumb-item active">Tender RFQ</li>
                    </ol>
                    <div id="panel-1" class="panel">
                        <div class="panel-hdr d-flex justify-content-between align-items-center">
                            <h2>Tender <span class="fw-300"><i>RFQ tickets</i></span></h2>
                            <a class="btn btn-primary" href="add-tender-rfq-ticket">Raise RFQ ticket</a>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table id="trfq_table" class="table table-bordered table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>Quotation ref</th>
                                            <th>Title</th>
                                            <th>Customer</th>
                                            <th>Rows</th>
                                            <th>Created</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $t) {
                                            $pid = $trfq->ticketPublicId($t['id']);
                                            $qref = $t['quotation_ref'] !== '' && $t['quotation_ref'] !== null
                                                ? htmlspecialchars($t['quotation_ref'])
                                                : htmlspecialchars($trfq->defaultQuotationRef($t['id']));
                                            ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pid); ?></td>
                                            <td><?= $qref; ?></td>
                                            <td><?= htmlspecialchars($t['title'] ?? ''); ?></td>
                                            <td><?= htmlspecialchars($t['customer_name'] ?? ''); ?></td>
                                            <td><?= (int) ($t['import_row_count'] ?? 0); ?></td>
                                            <td><?= htmlspecialchars($t['created_at'] ?? ''); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="view-tender-rfq-ticket-detail?id=<?= (int) $t['id']; ?>">View</a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
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
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script>
        $('#trfq_table').dataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']]
        });
    </script>
</body>
</html>
