<?php
@session_start();
require_once('../include/autoloader.inc.php');
$conf = new Conf();
$_ProductName = $conf->_ProductName;
$_ProductLogo = $conf->_ProductLogo;

// Session check MUST be done before any HTML output
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$UserType = $session->SessionCheck_redirect();

$User_ID = '';
if (isset($_SESSION['UserID'])) {
    $User_ID = $_SESSION['UserID'];
}

$core = new Core();
$workshop = new Workshop($conn);
$allWorkshops = $workshop->getAllWorkshops();
$navigation = new Navigation();
$navigation->setNavigation($_SESSION['pp_UserType']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <title>Workshop Registrations - <?= $_ProductName ?> Portal</title>
    <?php
    include("../include/common-head.php");
    ?>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <style>
        .card-header.bg-white {
            background-color: #fff !important;
            padding: 1.5rem;
        }
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
        }
        #registrationTable_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        #registrationTable_wrapper .dataTables_length select {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 2rem 0.375rem 0.75rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        .card.shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">
        <?php
        include("../navigation/top-header.php");
        include("../navigation/side-navigation.php");
        ?>
        
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Workshop Registrations</h3>
                            <p class="text-muted mb-0">Manage workshop registrations and attendance</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item"><a href="../workshop/view-workshop.php">Workshop</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Registrations</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="app-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="card-title mb-0">
                                                <i class="bi bi-people-fill me-2"></i>All Registrations
                                            </h4>
                                            <small class="text-muted">View and manage all workshop registrations</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Filters -->
                                    <div class="filter-section">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Filter by Workshop</label>
                                                    <select class="form-control" id="filter_workshop" onchange="applyFilters()">
                                                        <option value="">All Workshops</option>
                                                        <?php foreach ($allWorkshops as $ws): ?>
                                                            <option value="<?= $ws['ID'] ?>"><?= htmlspecialchars($ws['workshop_title']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Filter by Payment Status</label>
                                                    <select class="form-control" id="filter_payment" onchange="applyFilters()">
                                                        <option value="">All Status</option>
                                                        <option value="pending">Pending</option>
                                                        <option value="paid">Paid</option>
                                                        <option value="failed">Failed</option>
                                                        <option value="refunded">Refunded</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Filter by Attendance</label>
                                                    <select class="form-control" id="filter_attendance" onchange="applyFilters()">
                                                        <option value="">All</option>
                                                        <option value="1">Attended</option>
                                                        <option value="0">Not Attended</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button class="btn btn-secondary w-100" onclick="clearFilters()">Clear Filters</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="registrationTable" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Workshop</th>
                                                    <th>Participant Name</th>
                                                    <th>Email</th>
                                                    <th>Mobile</th>
                                                    <th>City</th>
                                                    <th>Payment Status</th>
                                                    <th>Amount</th>
                                                    <th>Registration Date</th>
                                                    <th>Attended</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include("../include/common-footer.php"); ?>
    </div>
    
    <?php include("../include/common-script.php"); ?>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <script src="../workshop/registrations.js"></script>
    
    <script>
        let registrationTable;
        
        // Initialize DataTable
        $(document).ready(function() {
            registrationTable = $('#registrationTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../workshop/action/workshop-action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_registrations_datatable';
                        d.workshop_id = $('#filter_workshop').val() || '';
                        d.payment_status = $('#filter_payment').val() || '';
                        d.attended = $('#filter_attendance').val() || '';
                        d.draw = d.draw || 1;
                    },
                    dataSrc: function(json) {
                        if (json.data) {
                            return json.data;
                        }
                        return [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        console.error('Response:', xhr.responseText);
                    }
                },
                columns: [
                    { data: 'ID' },
                    { data: 'workshop_title' },
                    { data: 'participant_name' },
                    { data: 'participant_email' },
                    { data: 'participant_mobile' },
                    { data: 'participant_city' },
                    { data: 'payment_status' },
                    { data: 'amount' },
                    { data: 'registration_date' },
                    { data: 'attended' },
                    { 
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                responsive: true,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No registrations available",
                    zeroRecords: "No matching records found"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function(settings) {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });
        });
        
        // Apply filters
        function applyFilters() {
            if (registrationTable) {
                registrationTable.ajax.reload();
            }
        }
        
        // Clear filters
        function clearFilters() {
            $('#filter_workshop').val('');
            $('#filter_payment').val('');
            $('#filter_attendance').val('');
            applyFilters();
        }
        
        // Reload table function
        window.reloadRegistrationTable = function() {
            if (registrationTable) {
                registrationTable.ajax.reload(null, false);
            }
        };
    </script>
    
    <!-- Registration Detail Modal -->
    <div class="modal fade" id="registrationModal" tabindex="-1" aria-labelledby="registrationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registrationModalLabel">Registration Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="registrationForm" onsubmit="return false;">
                    <div class="modal-body">
                        <input type="hidden" name="registration_id" id="registration_id" value="">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Participant Name</strong></label>
                                    <p id="detail_participant_name" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Email</strong></label>
                                    <p id="detail_participant_email" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Mobile</strong></label>
                                    <p id="detail_participant_mobile" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>City</strong></label>
                                    <p id="detail_participant_city" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Workshop</strong></label>
                                    <p id="detail_workshop_title" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Registration Date</strong></label>
                                    <p id="detail_registration_date" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Payment Status</strong></label>
                                    <p id="detail_payment_status" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Payment Amount</strong></label>
                                    <p id="detail_payment_amount" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Transaction ID</strong></label>
                                    <p id="detail_transaction_id" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Payment Date</strong></label>
                                    <p id="detail_payment_date" class="form-control-plaintext"></p>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <hr>
                                <h6>Update Registration</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Status</label>
                                    <select class="form-control" name="payment_status" id="payment_status">
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="failed">Failed</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Transaction ID</label>
                                    <input type="text" class="form-control" name="transaction_id" id="transaction_id">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Amount (₹)</label>
                                    <input type="number" class="form-control" name="payment_amount" id="payment_amount" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="datetime-local" class="form-control" name="payment_date" id="payment_date">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="attended" id="attended" value="1">
                                        <label class="form-check-label" for="attended">Mark as Attended</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Feedback Rating (1-5)</label>
                                    <input type="number" class="form-control" name="feedback_rating" id="feedback_rating" min="1" max="5">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Feedback Comment</label>
                                    <textarea class="form-control" name="feedback_comment" id="feedback_comment" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="certificate_issued" id="certificate_issued" value="1">
                                        <label class="form-check-label" for="certificate_issued">Certificate Issued</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="UpdateRegistration()">Update Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

