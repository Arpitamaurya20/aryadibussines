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
    <title>Resource Folders - <?= $_ProductName ?> Portal</title>
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
        #folderTable_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        #folderTable_wrapper .dataTables_length select {
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
                            <h3 class="mb-0">Resource Folders</h3>
                            <p class="text-muted mb-0">Manage resource library folders and their settings</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item"><a href="../resources/view-resources.php">Resource Library</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Folders</li>
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
                                                <i class="bi bi-folder-fill me-2"></i>All Folders
                                            </h4>
                                            <small class="text-muted">Create and manage resource folders</small>
                                        </div>
                                        <button class="btn btn-primary btn-lg shadow-sm" onclick="AddFolder()">
                                            <i class="bi bi-plus-circle-fill me-2"></i>Add New Folder
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="folderTable" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Folder Name</th>
                                                    <th>Slug</th>
                                                    <th>Description</th>
                                                    <th>Visibility</th>
                                                    <th>Parent Folder</th>
                                                    <th>Files Count</th>
                                                    <th>Total Size</th>
                                                    <th>Created Date</th>
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
    
    <script src="../resources/folder.js"></script>
    
    <script>
        let folderTable;
        
        // Initialize DataTable
        $(document).ready(function() {
            folderTable = $('#folderTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../resources/action/resource-action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_folders_datatable';
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
                    { data: 'folder_name' },
                    { data: 'slug' },
                    { data: 'description' },
                    { data: 'visibility' },
                    { data: 'parent_folder' },
                    { data: 'files_count' },
                    { data: 'total_size' },
                    { data: 'created_date' },
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
                    emptyTable: "No folders available",
                    zeroRecords: "No matching records found"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function(settings) {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });
        });
        
        // Reload table function
        window.reloadFolderTable = function() {
            if (folderTable) {
                folderTable.ajax.reload(null, false);
            }
        };
    </script>
    
    <!-- Folder Modal -->
    <div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="folderModalLabel">Add New Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="folderForm" onsubmit="return false;">
                    <div class="modal-body">
                        <input type="hidden" name="folder_form_action" id="folder_form_action" value="add">
                        <input type="hidden" name="folder_form_id" id="folder_form_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Folder Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="folder_name" id="folder_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="folder_description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Visibility <span class="text-danger">*</span></label>
                            <select class="form-control" name="visibility" id="folder_visibility" required>
                                <option value="private">Private (Admin Only)</option>
                                <option value="public">Public (Anyone Can Access)</option>
                            </select>
                            <small class="text-muted">Public folders are accessible without login. Private folders require admin login.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Parent Folder (Optional)</label>
                            <select class="form-control" name="parent_id" id="folder_parent_id">
                                <option value="">Root (No Parent)</option>
                                <?php foreach ($allFolders as $folder): ?>
                                    <option value="<?= $folder['ID'] ?>"><?= htmlspecialchars($folder['folder_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" class="form-control" name="display_order" id="folder_display_order" value="0" min="0">
                            <small class="text-muted">Lower numbers appear first</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="SaveFolder()">Save Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

