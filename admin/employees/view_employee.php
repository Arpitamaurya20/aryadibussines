<?php session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/employee_controller.php');
    // include('../manage-site/controller/site_controller.php');
    include('../city/controller/city_controller.php');
    include('../state/controller/state_controller.php');
    include('../includes/autoloader.inc.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    
    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    <title>
        View Employee Detail
    </title>
    <meta name="description" content="View Schema">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="./css/edit-modal.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* Ultra-Premium Light Grey Theme Core */
    body {
        font-family: 'Inter', sans-serif !important;
        color: #1e293b;
    }
    
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Outfit', sans-serif !important;
        color: #0f172a;
    }

    /* Override breadcrumb for light mode */
    .breadcrumb-item, .breadcrumb-item a {
        color: #64748b !important;
    }
    .breadcrumb-item.active {
        color: #334155 !important;
    }

    /* Modern Glassmorphism Panel (Light Colorful) */
    #panel-employee {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%) !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        border-radius: 16px;
        box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.1), inset 0 1px 0 rgba(255, 255, 255, 1);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        overflow: hidden;
    }
    
    /* Sleek Animated Glowing Tabs (Light) */
    .nav-tabs {
        border-bottom: none;
        gap: 8px;
        padding: 16px 24px;
        background: rgba(241, 245, 249, 0.6);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    }
    
    .nav-tabs .nav-item {
        margin-bottom: 0;
    }
    
    .nav-tabs .nav-link {
        border: 1px solid transparent !important;
        border-radius: 30px;
        padding: 10px 20px !important;
        color: #475569 !important;
        font-weight: 600;
        font-size: 14px;
        letter-spacing: 0.3px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 8px;
        background: transparent;
    }
    
    .nav-tabs .nav-link:hover {
        background: rgba(255, 255, 255, 0.8);
        color: #1e293b !important;
        border-color: rgba(226, 232, 240, 1) !important;
        transform: translateY(-2px);
    }
    
    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        border-color: transparent !important;
    }
    
    .nav-tabs .nav-link.active i,
    .nav-tabs .nav-link.active span {
        color: #ffffff !important;
    }
    
    .nav-tabs .nav-link i,
    .nav-tabs .nav-link span {
        transition: color 0.3s ease;
        color: #475569 !important;
    }
    
    .nav-tabs .nav-link:hover i,
    .nav-tabs .nav-link:hover span {
        color: #1e293b !important;
    }
    
    .nav-tabs .nav-link i {
        font-size: 16px;
    }

    /* Content Area Padding */
    .tab-content {
        padding: 30px 24px;
        color: #334155;
    }
    
    /* Legacy styles retained for safety but modernized */
    .employee_details {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .employee_details label {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 0px;
        color: #475569;
    }
    .employee_details span {
        font-size: 13px;
        font-weight: 500;
        color: #1e293b;
    }
    .modal-image {
        width: 400px;
        height: 400px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: 1px solid rgba(226,232,240,0.8);
    }
    
    /* Modern Premium Glowing Buttons */
    .btn-premium {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white !important;
        border: none;
        border-radius: 8px;
        padding: 8px 24px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
    }
    
    .btn-premium-danger {
        background: linear-gradient(135deg, #ef4444 0%, #e11d48 100%);
        color: white !important;
        border: none;
        border-radius: 8px;
        padding: 8px 24px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2);
        transition: all 0.3s ease;
    }
    
    .btn-premium-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    }
    
    /* Light Colorful Theme Base Overrides */
    body, .page-wrapper {
        background: linear-gradient(135deg, #c7d2fe 0%, #a5b4fc 100%) !important;
    }
    .page-content {
        background: transparent !important;
    }
    </style>

</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<?php
$UserType = SessionCheck();
$ID = resolveViewEmployeeId($_SESSION, $_GET);
if ($ID <= 0) {
    header('Location: view-employees.php');
    exit;
}

if (!canViewEmployeeProfile($conn, $ID, $_SESSION)) {
    header('Location: ../dashboard/admin_dashboard.php');
    exit;
}

$_SESSION['EmployeeID'] = $ID;

$HR = false;
if(CheckRole($_SESSION,"HR") == true)
{
    $HR = true;
}
$employee_data = getEmployeeData($conn, $ID);
if (empty($employee_data) || !isset($employee_data['ID'])) {
    header('Location: view-employees.php');
    exit;
}
$EmployeeSupervisorId = $employee_data['Supervisor'];
$EmployeeSupervisorData = getEmployeeSupervisorData($conn,$EmployeeSupervisorId);
$employee_media = "media/";
$access_tab = false;
$division_array = getDivisionArray($conn);

$citydata = getAllCity($conn);
$citydata = json_decode($citydata,true);
$StateData=getAllStates($conn);

?>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->


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
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="view-employees">View Employees</a></li>
                        <li class="breadcrumb-item active">Employee</li>

                    </ol>
                    <?php
                    if($HR)
                    {
                        ?>
                        <input type="hidden" name="hr_role" id="hr_role" value="HR" />
                        <?php
                    }
                    else
                    {
                        ?>
                        <input type="hidden" name="hr_role" id="hr_role" value="" />
                        <?php
                    }

                    ?>
                    <input type="hidden" name="EmployeeID" id="EmployeeID" value="<?php echo $ID;?>" />


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Employee
                                    </h2>
                                </div>
                                <div id="panel-employee" class="panel mt-3">
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <div class="demo-v-spacing">

                                                <ul class="nav nav-tabs" role="tablist">
                                                    <li class="nav-item">
                                                        <a class="nav-link active fs-lg px-4" data-toggle="tab"
                                                            href="#details " role="tab">
                                                            <i class="fas fa-server"></i>
                                                            <span class="hidden-sm-down ml-1">Details </span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#roles"
                                                            role="tab">
                                                            <i class="fal fa-user"></i>
                                                            <span class="hidden-sm-down ml-1">Roles</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#access"
                                                            role="tab">
                                                            <i class="fal fa-cog"></i>
                                                            <span class="hidden-sm-down ml-1">Access</span>
                                                        </a>
                                                    </li>
                                                    <?php
                                                    if($HR || $UserType == "Admin")
                                                    {
                                                    ?>

                                                      <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#otheraccess"
                                                            role="tab">
                                                            <i class="fal fa-cog"></i>
                                                            <span class="hidden-sm-down ml-1">Other Access</span>
                                                        </a>
                                                    </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                                href="#salary" role="tab">
                                                                <i class="fal fa-cog"></i>
                                                                <span class="hidden-sm-down ml-1">Salary </span>
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                                href="#location" role="tab">
                                                                <i class="fal fa-map-marker-alt"></i>
                                                                <span class="hidden-sm-down ml-1">Location</span>
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                                href="#attendance" role="tab">
                                                                <i class="fal fa-calendar-alt"></i>
                                                                <span class="hidden-sm-down ml-1">Attendance </span>
                                                            </a>
                                                        </li>

                                                        <!--li class="nav-item">
                                                            <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                                href="#monthlysalary" role="tab">
                                                                <i class="fal fa-cog text-danger"></i>
                                                                <span class="hidden-sm-down ml-1">Monthly Salary </span>
                                                            </a>
                                                        </li-->
                                                        <li class="nav-item">
                                                            <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                                href="#employeeleave" role="tab">
                                                                <i class="fal fa-cog text-danger"></i>
                                                                <span class="hidden-sm-down ml-1">Employee Leave </span>
                                                            </a>
                                                        </li>
                                                    <?php
                                                    }
                                                    ?>
                                                </ul>

                                                <div class="tab-content">
                                                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                                                        <?php
                                                              include('includes/employee_details_tab.php');
                                                         ?>
                                                    </div>
                                                    <div class="tab-pane fade" id="roles" role="tabpanel">
                                                        <?php
                                                           include('includes/employee_role_tab.php');
                                                        ?>
                                                    </div>
                                                    <div class="tab-pane fade" id="access" role="tabpanel">
                                                        <?php
                                                            include('includes/employee_access_tab.php');
                                                        ?>
                                                    </div>
                                                    <?php
                                                    if($HR || $UserType == "Admin")
                                                    {
                                                    ?>

                                                     <div class="tab-pane fade" id="otheraccess" role="tabpanel">
                                                        <?php
                                                            include('includes/employee_other_access_tab.php');
                                                        ?>
                                                    </div>
                                                    
                                                    <div class="tab-pane fade" id="salary" role="tabpanel">
                                                        <?php
                                                              include('includes/employee_salary_tab.php');
                                                         ?>
                                                    </div>
                                                    <div class="tab-pane fade" id="location" role="tabpanel">
                                                        <?php
                                                              include('includes/employee_location_tab.php');
                                                         ?>
                                                    </div>
                                                     <div class="tab-pane fade" id="attendance" role="tabpanel">
                                                        <?php
                                                              include('includes/employee_attendance_tab.php');
                                                         ?>
                                                    </div>
                                                    <!--div class="tab-pane fade" id="monthlysalary" role="tabpanel">
                                                        <?php
                                                             // include('includes/employee_monthly_salary_tab.php');
                                                         ?>
                                                    </div-->
                                                    <div class="tab-pane fade" id="employeeleave" role="tabpanel">
                                                        <?php
                                                            include('includes/employee_leave_tab.php');
                                                         ?>
                                                    </div>
                                                    <?php
                                                    }
                                                    ?>
                                                </div> <!-- tab content -->
                                            </div> <!-- demo-v-spacing -->
                                        </div> <!-- panel-content -->
                                    </div> <!-- panel-container show -->
                                </div> <!-- panel-employee -->
                            </div> <!-- panel-1-->
                        </div> <!-- col-xl-12 -->
                    </div> <!-- row -->


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

    <!-- Button trigger modal -->


    <!-- END Page Wrapper -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/employee.js"></script>
    <script>
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_employees").addClass("active");
        var hr_role = $("#hr_role").val();
        if(hr_role == "HR")
        {
            var EmployeeID = $("#EmployeeID").val();
            GenerateEmployeeAttendanceDetails(EmployeeID);
        }
    });    
    </script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script>
    window.addEventListener('load', function () {
        if (typeof window.bootEmployeeLocationTab === 'function') {
            window.bootEmployeeLocationTab();
        }
    });
    </script>
</body>
</html>