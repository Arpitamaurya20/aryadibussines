<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../controllers/common_controllers.php');
        include('controller/crm_controller.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
        $conn = _connectodb();
        $quotes = getAllQuotations($conn);
    ?>
    <meta charset="utf-8">
    <title>Manage Quotations</title>
    <meta name="description" content="View Quotations">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
</head>
<body class="mod-bg-1">
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">CRM</a></li>
                        <li class="breadcrumb-item active">Quotations</li>
                    </ol>
                    <div class="subheader">
                        <h1 class="subheader-title">
                            <i class='subheader-icon fal fa-file-invoice-dollar'></i> Manage Quotations
                        </h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Quotation List</h2>
                                    <div class="panel-toolbar">
                                        <a href="add-quotation.php" class="btn btn-primary btn-sm">Create New Quotation</a>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table id="dt-basic-example" class="table table-bordered table-hover table-striped w-100">
                                            <thead class="bg-primary-600">
                                                <tr>
                                                    <th>Quote #</th>
                                                    <th>Date</th>
                                                    <th>Lead / Company</th>
                                                    <th>Total Amount</th>
                                                    <th>Status</th>
                                                    <th>Valid Until</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($quotes as $q): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($q['QuoteNumber']) ?></td>
                                                    <td><?= htmlspecialchars($q['QuoteDate']) ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($q['LeadName']) ?>
                                                        <?= $q['AccountName'] ? '<br><small class="text-muted">'.$q['AccountName'].'</small>' : '' ?>
                                                    </td>
                                                    <td>&#8377; <?= number_format($q['GrandTotal'], 2) ?></td>
                                                    <td><span class="badge badge-info"><?= htmlspecialchars($q['Status']) ?></span></td>
                                                    <td><?= htmlspecialchars($q['ValidUntil']) ?></td>
                                                    <td>
                                                        <a href="generate_quotation_pdf.php?id=<?= $q['ID'] ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-icon" title="View PDF">
                                                            <i class="fal fa-file-pdf"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php 
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php'); 
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            $('#dt-basic-example').dataTable({
                responsive: true
            });
        });
    </script>
</body>
</html>
