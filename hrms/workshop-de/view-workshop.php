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
$allCategories = $workshop->getAllWorkshopCategories();
$navigation = new Navigation();
$navigation->setNavigation($_SESSION['pp_UserType']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <title>Workshop Management - <?= $_ProductName ?> Portal</title>
    <?php
    include("../include/common-head.php");
    ?>
    
    <!-- Quill.js - Free Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <style>
        .workshop-image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .card-header.bg-white {
            background-color: #fff !important;
            padding: 1.5rem;
        }
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
        }
        #workshopTable_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        #workshopTable_wrapper .dataTables_length select {
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
        .mode-badge {
            font-size: 0.75rem;
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
                            <h3 class="mb-0">Session Management</h3>
                            <p class="text-muted mb-0">Manage your Session, and registrations</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Session</li>
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
                                                <i class="bi bi-calendar-event me-2"></i>All Session
                                            </h4>
                                            <small class="text-muted">View and manage all your sessions</small>
                                        </div>
                                        <button class="btn btn-primary btn-lg shadow-sm" onclick="AddWorkshop()">
                                            <i class="bi bi-plus-circle-fill me-2"></i>Add New Session
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="workshopTable" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Mode</th>
                                                    <th>Status</th>
                                                    <th>Session Date</th>
                                                    <th>Registrations</th>
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
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#workshopTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../workshop/action/workshop-action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_workshops_datatable';
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
                    { data: 'title' },
                    { data: 'category' },
                    { data: 'mode' },
                    { data: 'status' },
                    { data: 'session_date' },
                    { data: 'registrations' },
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
                    emptyTable: "No workshops available",
                    zeroRecords: "No matching records found"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function(settings) {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });
        });
        
        // Reload table function - will be used by workshop.js
        window.reloadWorkshopTable = function() {
            $('#workshopTable').DataTable().ajax.reload(null, false);
        };
        
        // Delete Workshop function - will be used by workshop.js
        window.DeleteWorkshop = function(workshopId) {
            if (confirm('Are you sure you want to delete this workshop?')) {
                $.ajax({
                    url: '../workshop/action/workshop-action.php',
                    type: 'POST',
                    data: {
                        action: 'delete_workshop',
                        workshop_id: workshopId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (!response.error) {
                            alert(response.message || 'Workshop deleted successfully');
                            if (typeof window.reloadWorkshopTable === 'function') {
                                window.reloadWorkshopTable();
                            }
                        } else {
                            alert(response.message || 'Failed to delete workshop');
                        }
                    },
                    error: function() {
                        alert('Error deleting workshop');
                    }
                });
            }
        };
    </script>
    
    <!-- Workshop Modal -->
    <div class="modal fade" id="workshopModal" tabindex="-1" aria-labelledby="workshopModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="workshopModalLabel">Add New Workshop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="workshopForm" onsubmit="return false;" enctype="multipart/form-data">
                    <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                        <input type="hidden" name="workshop_form_action" id="workshop_form_action" value="add">
                        <input type="hidden" name="workshop_form_id" id="workshop_form_id" value="">
                        
                        <ul class="nav nav-tabs" id="workshopTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Info</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="session-tab" data-bs-toggle="tab" data-bs-target="#session" type="button" role="tab">Session Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab">Schedule</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing" type="button" role="tab">Pricing</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="certification-tab" data-bs-toggle="tab" data-bs-target="#certification" type="button" role="tab">Certification</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">SEO</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">Settings</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="workshopTabContent">
                            <!-- Basic Info Tab -->
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Workshop Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="workshop_title" id="workshop_title" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Slug</label>
                                            <input type="text" class="form-control" name="slug" id="slug">
                                            <small class="text-muted">Auto-generated from title</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Short Description</label>
                                            <textarea class="form-control" name="short_description" id="short_description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Detailed Description</label>
                                            <div id="detailed_description_editor" style="height: 400px;"></div>
                                            <textarea class="form-control d-none" name="detailed_description" id="detailed_description"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <select class="form-control" name="category_id" id="category_id">
                                                <option value="">Select Category</option>
                                                <?php foreach ($allCategories as $category): ?>
                                                    <option value="<?= $category['ID'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tags</label>
                                            <input type="text" class="form-control" name="tags" id="tags" placeholder="tag1, tag2, tag3">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Thumbnail Image</label>
                                            <input type="file" class="form-control" name="thumbnail_image_file" id="thumbnail_image_file" accept="image/*">
                                            <input type="hidden" name="thumbnail_image" id="thumbnail_image">
                                            <div id="thumbnail_image_preview"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Gallery Images</label>
                                            <input type="file" class="form-control" name="gallery_images[]" id="gallery_images" multiple accept="image/*">
                                            <input type="hidden" name="gallery_images_existing" id="gallery_images_existing">
                                            <div id="gallery_preview" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Session Details Tab -->
                            <div class="tab-pane fade" id="session" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Workshop Mode <span class="text-danger">*</span></label>
                                            <select class="form-control" name="workshop_mode" id="workshop_mode" required>
                                                <option value="online">Online</option>
                                                <option value="offline">Offline</option>
                                                <option value="hybrid">Hybrid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Session Type</label>
                                            <select class="form-control" name="session_type" id="session_type">
                                                <option value="single">Single Session</option>
                                                <option value="multi">Multi Session</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Language</label>
                                            <select class="form-control" name="language" id="language">
                                                <option value="English">English</option>
                                                <option value="Hindi">Hindi</option>
                                                <option value="Bengali">Bengali</option>
                                                <option value="Tamil">Tamil</option>
                                                <option value="Telugu">Telugu</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Level</label>
                                            <select class="form-control" name="level" id="level">
                                                <option value="beginner">Beginner</option>
                                                <option value="intermediate">Intermediate</option>
                                                <option value="expert">Expert</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Online Fields -->
                                    <div class="col-12 online-fields">
                                        <hr>
                                        <h6>Online Session Details</h6>
                                    </div>
                                    <div class="col-md-6 online-fields">
                                        <div class="mb-3">
                                            <label class="form-label">Meeting Platform</label>
                                            <select class="form-control" name="meeting_platform" id="meeting_platform">
                                                <option value="">Select Platform</option>
                                                <option value="Zoom">Zoom</option>
                                                <option value="Google Meet">Google Meet</option>
                                                <option value="MS Teams">MS Teams</option>
                                                <option value="Webex">Webex</option>
                                                <option value="Custom">Custom</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 online-fields">
                                        <div class="mb-3">
                                            <label class="form-label">Meeting Link</label>
                                            <input type="url" class="form-control" name="meeting_link" id="meeting_link">
                                        </div>
                                    </div>
                                    <div class="col-md-6 online-fields">
                                        <div class="mb-3">
                                            <label class="form-label">Meeting ID</label>
                                            <input type="text" class="form-control" name="meeting_id" id="meeting_id">
                                        </div>
                                    </div>
                                    <div class="col-md-6 online-fields">
                                        <div class="mb-3">
                                            <label class="form-label">Meeting Passcode</label>
                                            <input type="text" class="form-control" name="meeting_passcode" id="meeting_passcode">
                                        </div>
                                    </div>
                                    <div class="col-md-12 online-fields">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="auto_send_meeting_link" id="auto_send_meeting_link" value="1" checked>
                                                <label class="form-check-label" for="auto_send_meeting_link">Auto-send meeting link to registrants</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Offline Fields -->
                                    <div class="col-12 offline-fields">
                                        <hr>
                                        <h6>Offline Session Details</h6>
                                    </div>
                                    <div class="col-md-12 offline-fields">
                                        <div class="mb-3">
                                            <label class="form-label">Venue Name</label>
                                            <input type="text" class="form-control" name="venue_name" id="venue_name">
                                        </div>
                                    </div>
                                    <div class="col-md-12 offline-fields">
                                        <div class="mb-3">
                                            <label class="form-label">Venue Full Address</label>
                                            <textarea class="form-control" name="venue_address" id="venue_address" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6 offline-fields">
                                        <div class="mb-3">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" name="city" id="city">
                                        </div>
                                    </div>
                                    <div class="col-md-6 offline-fields">
                                        <div class="mb-3">
                                            <label class="form-label">Google Map Link</label>
                                            <input type="url" class="form-control" name="google_map_link" id="google_map_link">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Schedule Tab -->
                            <div class="tab-pane fade" id="schedule" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Session Date</label>
                                            <input type="date" class="form-control" name="session_date" id="session_date">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" name="start_time" id="start_time">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" name="end_time" id="end_time">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Time Zone</label>
                                            <select class="form-control" name="time_zone" id="time_zone">
                                                <option value="IST">IST (Indian Standard Time)</option>
                                                <option value="UTC">UTC</option>
                                                <option value="EST">EST</option>
                                                <option value="PST">PST</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring" value="1">
                                                <label class="form-check-label" for="is_recurring">Recurring Sessions</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="recurring_fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Recurring Pattern</label>
                                            <select class="form-control" name="recurring_pattern" id="recurring_pattern">
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="recurring_fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Recurring End Date</label>
                                            <input type="date" class="form-control" name="recurring_end_date" id="recurring_end_date">
                                        </div>
                                    </div>
                                    
                                    <!-- Capacity & Registration -->
                                    <div class="col-12 mt-3">
                                        <hr>
                                        <h6>Capacity & Registration</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Total Seats / Max Participants</label>
                                            <input type="number" class="form-control" name="total_seats" id="total_seats" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Registration Deadline</label>
                                            <input type="datetime-local" class="form-control" name="registration_deadline" id="registration_deadline">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="show_seat_availability" id="show_seat_availability" value="1" checked>
                                                <label class="form-check-label" for="show_seat_availability">Show seat availability to users</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="auto_close_registration" id="auto_close_registration" value="1" checked>
                                                <label class="form-check-label" for="auto_close_registration">Auto-close registration when full</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_waiting_list" id="allow_waiting_list" value="1">
                                                <label class="form-check-label" for="allow_waiting_list">Allow Waiting List</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="waiting_list_fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Waiting List Capacity</label>
                                            <input type="number" class="form-control" name="waiting_list_capacity" id="waiting_list_capacity" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pricing Tab -->
                            <div class="tab-pane fade" id="pricing" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Pricing Type <span class="text-danger">*</span></label>
                                            <select class="form-control" name="pricing_type" id="pricing_type" required>
                                                <option value="free">Free</option>
                                                <option value="paid">Paid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 pricing-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Price (₹)</label>
                                            <input type="number" class="form-control" name="price" id="price" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6 pricing-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Discount Type</label>
                                            <select class="form-control" name="discount_type" id="discount_type">
                                                <option value="">No Discount</option>
                                                <option value="percent">Percentage</option>
                                                <option value="flat">Flat Amount</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 pricing-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Discount Value</label>
                                            <input type="number" class="form-control" name="discount_value" id="discount_value" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6 pricing-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Discount Start Date</label>
                                            <input type="date" class="form-control" name="discount_start_date" id="discount_start_date">
                                        </div>
                                    </div>
                                    <div class="col-md-6 pricing-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Discount End Date</label>
                                            <input type="date" class="form-control" name="discount_end_date" id="discount_end_date">
                                        </div>
                                    </div>
                                    <div class="col-md-12 pricing-fields" style="display: none;">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="coupon_code_support" id="coupon_code_support" value="1">
                                                <label class="form-check-label" for="coupon_code_support">Coupon Code Support</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 pricing-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Gateway</label>
                                            <select class="form-control" name="payment_gateway" id="payment_gateway">
                                                <option value="">Select Gateway</option>
                                                <option value="Razorpay">Razorpay</option>
                                                <option value="PayPal">PayPal</option>
                                                <option value="Stripe">Stripe</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Certification Tab -->
                            <div class="tab-pane fade" id="certification" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="provide_certificate" id="provide_certificate" value="1">
                                                <label class="form-check-label" for="provide_certificate">Provide Certificate</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12" id="certificate_fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Certificate Template (PDF)</label>
                                            <input type="file" class="form-control" name="certificate_template_file" id="certificate_template_file" accept=".pdf">
                                            <input type="hidden" name="certificate_template" id="certificate_template">
                                            <small class="text-muted">Upload PDF template for certificate</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12" id="certificate_fields" style="display: none;">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="auto_generate_certificate" id="auto_generate_certificate" value="1">
                                                <label class="form-check-label" for="auto_generate_certificate">Auto-generate certificate after completion</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Automation & Notifications -->
                                    <div class="col-12 mt-3">
                                        <hr>
                                        <h6>Automation & Notifications</h6>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="email_notification" id="email_notification" value="1" checked>
                                                <label class="form-check-label" for="email_notification">Email Notification to Registrants</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="sms_notification" id="sms_notification" value="1">
                                                <label class="form-check-label" for="sms_notification">SMS Notification</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="whatsapp_notification" id="whatsapp_notification" value="1">
                                                <label class="form-check-label" for="whatsapp_notification">WhatsApp Notification</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Reminder Before Session</label>
                                            <input type="number" class="form-control" name="reminder_before_session" id="reminder_before_session" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Reminder Unit</label>
                                            <select class="form-control" name="reminder_unit" id="reminder_unit">
                                                <option value="minutes">Minutes</option>
                                                <option value="hours" selected>Hours</option>
                                                <option value="days">Days</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="post_session_feedback" id="post_session_feedback" value="1" checked>
                                                <label class="form-check-label" for="post_session_feedback">Post-session Feedback Request</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SEO Tab -->
                            <div class="tab-pane fade" id="seo" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Meta Title</label>
                                            <input type="text" class="form-control" name="meta_title" id="meta_title">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Meta Description</label>
                                            <textarea class="form-control" name="meta_description" id="meta_description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Meta Keywords</label>
                                            <input type="text" class="form-control" name="meta_keywords" id="meta_keywords" placeholder="keyword1, keyword2, keyword3">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">OG Image</label>
                                            <input type="file" class="form-control" name="og_image_file" id="og_image_file" accept="image/*">
                                            <input type="hidden" name="og_image" id="og_image">
                                            <div id="og_image_preview"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Settings Tab -->
                            <div class="tab-pane fade" id="settings" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value="draft">Draft</option>
                                                <option value="published">Published</option>
                                                <option value="unlisted">Unlisted</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="show_on_homepage" id="show_on_homepage" value="1">
                                                <label class="form-check-label" for="show_on_homepage">Show on Homepage</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="featured" id="featured" value="1">
                                                <label class="form-check-label" for="featured">Featured Workshop</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="SaveWorkshop()">Save Workshop</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize Quill Editor
        $(document).ready(function() {
            if (typeof Quill !== 'undefined') {
                window.quillEditor = new Quill('#detailed_description_editor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'size': ['small', false, 'large', 'huge'] }],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'align': [] }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    }
                });
            }
            
            // Toggle recurring fields
            $('#is_recurring').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#recurring_fields').show();
                } else {
                    $('#recurring_fields').hide();
                }
            });
            
            // Toggle waiting list fields
            $('#allow_waiting_list').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#waiting_list_fields').show();
                } else {
                    $('#waiting_list_fields').hide();
                }
            });
            
            // Toggle certificate fields
            $('#provide_certificate').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#certificate_fields').show();
                } else {
                    $('#certificate_fields').hide();
                }
            });
        });
    </script>
    
    <script src="../workshop/workshop.js"></script>
</body>
</html>

