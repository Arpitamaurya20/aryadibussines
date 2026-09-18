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
        $leads = getAllLeads($conn);
    ?>
    <meta charset="utf-8">
    <title>Manage CRM Leads</title>
    <meta name="description" content="View CRM Leads">
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
                        <li class="breadcrumb-item active">Manage Leads</li>
                    </ol>
                    <div class="subheader">
                        <h1 class="subheader-title">
                            <i class='subheader-icon fal fa-users'></i> Manage Leads
                        </h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Leads List</h2>
                                    <div class="panel-toolbar">
                                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addLeadModal">Add New Lead</button>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table id="dt-basic-example" class="table table-bordered table-hover table-striped w-100">
                                            <thead class="bg-primary-600">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Lead Name</th>
                                                    <th>Company</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Status</th>
                                                    <th>Created Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($leads as $lead): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($lead['ID']) ?></td>
                                                    <td><?= htmlspecialchars($lead['LeadName']) ?></td>
                                                    <td><?= htmlspecialchars($lead['CompanyName']) ?></td>
                                                    <td><?= htmlspecialchars($lead['Email']) ?></td>
                                                    <td><?= htmlspecialchars($lead['Phone']) ?></td>
                                                    <td><span class="badge badge-info"><?= htmlspecialchars($lead['LeadStatus']) ?></span></td>
                                                    <td><?= htmlspecialchars($lead['CreatedDate']) ?></td>
                                                    <td>
                                                        <a href="view-lead-details.php?id=<?= $lead['ID'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="View Details">
                                                            <i class="fal fa-eye"></i>
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

    <!-- Add Lead Modal -->
    <div class="modal fade" id="addLeadModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Add New Lead</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <form action="action/add_lead.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">Lead Name <span class="text-danger">*</span></label>
                                <input type="text" name="LeadName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="CompanyName" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="Email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="Phone" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">Lead Source</label>
                                <select name="LeadSource" class="form-control">
                                    <option value="Website">Website</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Social Media">Social Media</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="LeadStatus" class="form-control">
                                    <option value="New">New</option>
                                    <option value="Contacted">Contacted</option>
                                    <option value="Qualified">Qualified</option>
                                    <option value="Lost">Lost</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary form_submit">Save Lead</button>
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
