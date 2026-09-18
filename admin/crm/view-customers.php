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
        
        $sql = "SELECT * FROM crm_accounts WHERE IsActive = 1 ORDER BY ID DESC";
        $result = mysqli_query($conn, $sql);
        $customers = [];
        if($result && mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $customers[] = $row;
            }
        }
    ?>
    <meta charset="utf-8">
    <title>Manage CRM Customers</title>
    <meta name="description" content="View CRM Customers">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        .form_submit { background-color: #2196f3; color: #fff; border: none; border-radius: 4px; }
    </style>
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
                        <li class="breadcrumb-item active">Manage Customers</li>
                    </ol>
                    <div class="subheader">
                        <h1 class="subheader-title">
                            <i class='subheader-icon fal fa-building'></i> Manage Customers
                        </h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Customer List</h2>
                                    <div class="panel-toolbar">
                                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCustomerModal">Add New Customer</button>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table id="dt-basic-example" class="table table-bordered table-hover table-striped w-100">
                                            <thead class="bg-primary-600">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Company</th>
                                                    <th>Contact Person</th>
                                                    <th>Email</th>
                                                    <th>Mobile</th>
                                                    <th>Type</th>
                                                    <th>Created Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($customers as $c): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($c['ID']) ?></td>
                                                    <td><?= htmlspecialchars($c['AccountName']) ?></td>
                                                    <td><?= htmlspecialchars($c['ContactPerson']) ?></td>
                                                    <td><?= htmlspecialchars($c['Email']) ?></td>
                                                    <td><?= htmlspecialchars($c['Mobile']) ?></td>
                                                    <td><span class="badge badge-info"><?= htmlspecialchars($c['CustomerType']) ?></span></td>
                                                    <td><?= htmlspecialchars($c['CreatedDate']) ?></td>
                                                    <td>
                                                        <a href="edit-customer.php?id=<?= $c['ID'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="Edit Customer">
                                                            <i class="fal fa-edit"></i>
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

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <form action="action/add_customer.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">Company/Account Name <span class="text-danger">*</span></label>
                                <input type="text" name="AccountName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="ContactPerson" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="Email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="Mobile" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">Customer Type</label>
                                <select name="CustomerType" class="form-control">
                                    <option value="B2B">B2B</option>
                                    <option value="B2C">B2C</option>
                                    <option value="Enterprise">Enterprise</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Source</label>
                                <select name="Source" class="form-control">
                                    <option value="Website">Website</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Sales Team">Sales Team</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary form_submit">Save Customer</button>
                    </div>
                </form>
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
