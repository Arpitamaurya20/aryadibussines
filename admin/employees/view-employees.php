<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/employee_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>Employees</title>
    <meta name="description" content="View Employees">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    #js-page-content { font-family: 'Inter', 'Segoe UI', sans-serif; }

    /* Premium panel styling */
    .panel { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
    .panel-hdr { border-bottom: 1px solid #f1f5f9; padding: 15px 20px; background: #fff; display: flex; align-items: center; }
    .panel-hdr h2 { color: #1e293b; font-size: 16px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
    
    /* Sleek buttons */
    .btn { font-weight: 600; border-radius: 6px; padding: 6px 14px; font-size: 13px; transition: all 0.2s; }
    .btn-outline-secondary { color: #475569; border-color: #cbd5e1; background: #fff; }
    .btn-outline-secondary:hover { background: #f8fafc; color: #1e293b; border-color: #94a3b8; }
    .btn-primary { background-color: #003f88 !important; border-color: #003f88 !important; color: #fff; }
    .btn-primary:hover { background-color: #002d62 !important; border-color: #002d62 !important; box-shadow: 0 4px 12px rgba(0,63,136,0.3); }

    /* Table styling */
    table.dataTable { border-collapse: collapse !important; width: 100% !important; margin-top: 15px !important; }
    table.dataTable thead th { background: #f8fafc; color: #475569; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 15px; border-bottom: 2px solid #e2e8f0; border-top: none; }
    table.dataTable tbody td { font-size: 13.5px; color: #334155; padding: 12px 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    table.dataTable tbody tr:hover { background-color: #f8fafc !important; }
    
    .dataTables_wrapper .dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 10px; font-size: 13px; outline: none; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: #003f88; box-shadow: 0 0 0 3px rgba(0,63,136,0.1); }
    .dataTables_paginate .paginate_button { border-radius: 6px !important; font-size: 12px !important; font-weight: 600 !important; border: 1px solid #e2e8f0 !important; padding: 4px 10px !important; background: #fff !important; }
    .dataTables_paginate .paginate_button.current { background: #003f88 !important; color: #fff !important; border-color: #003f88 !important; }
    .dataTables_paginate .paginate_button:hover:not(.current) { background: #f1f5f9 !important; color: #003f88 !important; }

    /* Specific overrides */
    .modal_header { background-color: #003f88; color: #fff; }
    .modal_header button { opacity: 1; color: #fff; }
    </style>
</head>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255" crossorigin="anonymous"></script>
<?php
$UserType = SessionCheck();
$employeesdata = getAllemployees($conn);
$employeesdata = json_decode($employeesdata, true);
if (isset($_GET['action']) && $_GET['action'] == 'active' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $employees_query = "UPDATE employees SET IsActive=0 WHERE ID=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $employees_query);
    header("location:view-employees.php");
}
if (isset($_GET['action']) && $_GET['action'] == 'deactive' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $employees_query1 = "UPDATE employees SET IsActive=1 WHERE ID=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $employees_query1);
    header("location:view-employees.php");
}
$filter_param = "?UserType=".$UserType;
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                        <ol class="breadcrumb page-breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard">Aryadibusiness</a></li>
                            <li class="breadcrumb-item active">View Employees</li>
                        </ol>
                        <?php if($UserType == "Admin" || $UserType == "Sub Admin"){ ?>
                        <a href="#" onclick="DownloadEmployeeDataAssetsFileFormat()" class="btn btn-outline-secondary shadow-sm">
                            <i class="fal fa-file-download"></i> Download Bulk Template
                        </a>
                        <?php } ?>
                    </div>

                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <i class="fal fa-users mr-2" style="color:#003f88;"></i>
                                        View Employees
                                    </h2>
                                    <div style="display:flex;gap:10px;align-items:center;">
                                        <?php if($UserType == "Admin" || $UserType == "Sub Admin"){ ?>
                                        <a href="#" onclick="ExportEmployeeData()" class="btn btn-outline-secondary shadow-sm">
                                            <i class="fal fa-file-export"></i> Export Data
                                        </a>
                                        <?php } ?>
                                        <a href="add-employees" class="btn btn-primary shadow-sm" style="background-color: #003f88; border-color: #003f88;">
                                            <i class="fal fa-user-plus"></i> Add Employee
                                        </a>
                                    </div>
                                </div>
                                <div class="vt-table-wrap">
                                    <?php include("./ajax/employee-list-view.php") ?>
                                </div>
                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div><!-- row -->
                </main>

                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php') ?>
            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/employee.js"></script>
    <script>
        $(document).ready(function() {
            $('#view-employees').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/employee-list-post.php<?php echo $filter_param; ?>'
                },
                'columnDefs': [{ "targets": [0], "className": "text-center" }],
                'columns': [
                    { "data": "id", render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'Name' },
                    { data: 'ID' },
                    { data: 'Department' },
                    { data: 'ContactDetails' },
                    { data: 'Identidy' },
                    { data: 'Vendor' },
                    { data: 'View' },
                    { data: 'CreatedBy' },
                    { data: 'Status' }
                ]
            });
            $("#js-nav-menu").addClass("active").addClass("open");
            $("#nav_employees").addClass("active");
        });
    </script>
</body>
</html>
