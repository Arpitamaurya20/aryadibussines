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
    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    <title>
        Employees
    </title>
    <meta name="description" content="View Employees">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
</head>
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
            <?php
            include('../navigation/admin_navigation.php');
            ?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php
                include('../includes/common_header.php');
                ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                        <ol class="breadcrumb page-breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard">TechXpert</a></li>
                            <li class="breadcrumb-item active">View Employees</li>
                        </ol>

                        <?php
                            if($UserType == "Admin" || $UserType == "Sub Admin"){
                        ?>
                        <a href="#" onclick="DownloadEmployeeDataAssetsFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a>
                        <?php } ?>
                    </div>
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Employees</span>
                                    </h2>
                                    <?php if($UserType == "Admin" ||$UserType == "Sub Admin"){ ?>
                                    <a href="#" onclick="ExportEmployeeData()" class="btn btn-info" style="margin-right:20px;">Export Data</a>
                                    <?php } ?> 
                                    <a href="add-employees" class="btn btn-info" style="margin-right:20px;">Add Employee</a>
                                </div>
                                <?php include("./ajax/employee-list-view.php") ?>
                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->
                </main>
                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
                include('../includes/common_footer.php')
                ?>
                <!-- END Page Footer -->
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
            var i = 1;
            $('#view-employees').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/employee-list-post.php<?php echo $filter_param; ?>'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                
                'columns': [{
                        "data": "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'Name'
                    },
                    {
                        data: 'ID'
                    },
                    {
                        data: 'Department'
                    },
                    {
                        data: 'ContactDetails'
                    },
                    {
                        data: 'Identidy'
                    },
                    {
                        data: 'Vendor'
                    },
                    {
                        data: 'View'
                    },
                    {
                        data: 'CreatedBy'
                    },
                    {
                        data: 'Status'
                    }


                ]


            });
        });



    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_employees").addClass("active");
    });
    </script>
    <script>
    $(document).ready(function() {
        // $('#view-employees').dataTable({
        //     responsive: true,
        //     "ordering": false
        // });
        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });
        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });
    });
    </script>
</body>

</html>