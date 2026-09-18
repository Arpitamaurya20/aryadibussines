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
$resource = new ResourceLibrary($conn);
$allFolders = $resource->getAllFolders();
$navigation = new Navigation();
$navigation->setNavigation($_SESSION['pp_UserType']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <title>Access Logs - <?= $_ProductName ?> Portal</title>
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
        #accessLogTable_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        #accessLogTable_wrapper .dataTables_length select {
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
                            <h3 class="mb-0">Access Logs</h3>
                            <p class="text-muted mb-0">View resource library access logs and analytics</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item"><a href="../resources/view-resources.php">Resource Library</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Access Logs</li>
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
                                                <i class="bi bi-clock-history me-2"></i>Access Logs
                                            </h4>
                                            <small class="text-muted">Track all file and folder access</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Filters -->
                                    <div class="filter-section">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Filter by Folder</label>
                                                    <select class="form-control" id="filter_folder" onchange="applyFilters()">
                                                        <option value="">All Folders</option>
                                                        <?php foreach ($allFolders as $folder): ?>
                                                            <option value="<?= $folder['ID'] ?>"><?= htmlspecialchars($folder['folder_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Filter by Access Type</label>
                                                    <select class="form-control" id="filter_access_type" onchange="applyFilters()">
                                                        <option value="">All Types</option>
                                                        <option value="view">View</option>
                                                        <option value="download">Download</option>
                                                        <option value="stream">Stream</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Date From</label>
                                                    <input type="date" class="form-control" id="filter_date_from" onchange="applyFilters()">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Date To</label>
                                                    <input type="date" class="form-control" id="filter_date_to" onchange="applyFilters()">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="accessLogTable" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>File Name</th>
                                                    <th>Folder</th>
                                                    <th>User</th>
                                                    <th>IP Address</th>
                                                    <th>Access Type</th>
                                                    <th>Date & Time</th>
                                                    <th>Browser</th>
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
    
    <script>
        let accessLogTable;
        
        // Initialize DataTable
        $(document).ready(function() {
            accessLogTable = $('#accessLogTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../resources/action/resource-action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_access_logs_datatable';
                        d.folder_id = $('#filter_folder').val() || '';
                        d.access_type = $('#filter_access_type').val() || '';
                        d.date_from = $('#filter_date_from').val() || '';
                        d.date_to = $('#filter_date_to').val() || '';
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
                    { data: 'file_name' },
                    { data: 'folder_name' },
                    { data: 'user' },
                    { data: 'ip_address' },
                    { data: 'access_type' },
                    { data: 'access_datetime' },
                    { data: 'user_agent' }
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
                    emptyTable: "No access logs available",
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
            if (accessLogTable) {
                accessLogTable.ajax.reload();
            }
        }
        
        // Clear filters
        function clearFilters() {
            $('#filter_folder').val('');
            $('#filter_access_type').val('');
            $('#filter_date_from').val('');
            $('#filter_date_to').val('');
            applyFilters();
        }
    </script>
</body>
</html>

