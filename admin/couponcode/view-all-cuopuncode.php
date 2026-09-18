<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
       include('../controllers/common_controllers.php');
        include('../branch/controller/branch_controller.php');
        include('../company/controller/company_controller.php');
        include('../branch-assets/controller/branch_assets_controller.php');
        require_once('../includes/autoloader.inc.php');
        $conn = _connectodb();
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
        $conn = _connectodb();
        $CorporateID = -1;
        $BranchID = -1;
        $ID = -1;
        $techx_admin = true;
        if($UserType == "Corporate Branch User")
        {
            $CorporateID = $_SESSION['Roles']['CorporateID'];
            $BranchID = $_SESSION['Roles']['BranchID'];
        }
        if($UserType == "Corporate Admin")
        {
            $CorporateID = $_SESSION['Roles']['CorporateID'];
        }
        //$total_projects = getTotatProjects($conn,$Enterpriseid);
        $StateManager = false;
        if(CheckRole($_SESSION,"State Corporate Lead") == true )
        {
            $StateManager = true;
        }
        if(isset($_SESSION['Roles']['EmployeeID']))
        {
            $Employee_ID = $_SESSION['Roles']['EmployeeID'];
        }
        $state_object = new State($conn);
        $sql_in_state_string = "";
        if($StateManager)
        {
            $state_array = $state_object->getStatesMapped_StateLead($Employee_ID);
        }
        else
        {
            $state_array = $state_object->setStateArray('Active');
        }
         
        ?>
    <meta charset="utf-8">
    
    <meta name="description" content="View PPM Tickets">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <style>
    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
    }
    .select2-container
    {
        z-index:1;
    }
    </style>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    

<?php
    
    if(isset($_GET['nav']))
    {
        $BranchID = -1;
    }
    else
    {
        if(isset($_SESSION['BranchID']))
        {
            $BranchID = $_SESSION['BranchID'];
            $CorporateID = GetBranchDetailsbyID($conn,$BranchID)['CompanyID'];
            // $ID = GetBranchAssetsbyID($conn,$BranchID)['BranchID'];
        }
    }
    $account_branch_manager = "no";
    $Employee_ID = -1;
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "Branch Account Manager")
            {
                $account_branch_manager = "yes";
                if(isset($_SESSION['Roles']['EmployeeID']))
                {
                    $Employee_ID = $_SESSION['Roles']['EmployeeID'];
                }
            }
        }
    }

    $CreatedBy = $_SESSION['pb_username'];
    $current_date = date('Y-m-d', strtotime('+365 days'));;
    $previous_date =  date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . " - " . $current_date;
    $filter_param = "?filter_date=".$date_range."&EmployeeID=".$Employee_ID;;

    $ppmticket = new Ppmtickets($conn);
    $status_array = $ppmticket->getPPMTicketStatusArray("All");

    // =======================
    // Current month PPM stats
    // =======================
    $raisedPPMCount   = 0;
    $closedPPMCount   = 0;
    $assignedPPMCount = 0;

    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');

    // Using PPMDate to determine "current month" tickets
    $ppm_stats_sql = "
        SELECT 
            COUNT(*) AS raised_ppm,
            SUM(CASE WHEN Status = 'Closed' THEN 1 ELSE 0 END) AS closed_ppm,
            SUM(CASE WHEN AssignedTo IS NOT NULL AND AssignedTo != '' THEN 1 ELSE 0 END) AS assigned_ppm
        FROM ppm_tickets
        WHERE PPMDate BETWEEN '$monthStart' AND '$monthEnd'
    ";

    if(isset($conn))
    {
        $ppm_stats_result = mysqli_query($conn, $ppm_stats_sql);
        if($ppm_stats_result && $ppm_stats_row = mysqli_fetch_assoc($ppm_stats_result))
        {
            $raisedPPMCount   = (int)$ppm_stats_row['raised_ppm'];
            $closedPPMCount   = (int)$ppm_stats_row['closed_ppm'];
            $assignedPPMCount = (int)$ppm_stats_row['assigned_ppm'];
        }
    }

    $company_object = new Company($conn);
    $company_array = $company_object->setCompanyArray('All');
    $ProductName = "Aryadibusiness";
    if($CorporateID == 183) 
    {
        $_product = "innov";
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo']))
    {
        $logoImg = $product_configuration['logo'];
    }  
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    $branch_object = new Branch($conn);
    if($UserType == "Corporate Admin")
    {
        $branch_array = $branch_object->setBranchArrayByCorporateID($CorporateID,'All');
    }
    else
    {
        $branch_array = $branch_object->setBranchArray('All');
    }
    ?>
    <title>
        Manage PPM Tickets - <?=$ProductName;?>
    </title>

</head>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
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
                        <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">View All Coupon Code</li>


                    </ol>
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <button class="btn btn-success mb-3" onclick="openAddCouponModal()">
                                <i class="fas fa-plus"></i> Add Coupon
                            </button>

                            <div id="panel-1" class="panel">
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-all-coupons"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>All Coupons</th>
                                                    <th>Discounts </th>
                                                    <th>Type</th>
                                                    <th>Action</th>
                                                    
                                                </tr>
                                            </thead>
                                            

                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>

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

    <div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal_header">
                <h5 class="modal-title" id="couponModalTitle">Add Coupon</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="coupon_id">

                <div class="form-group">
                    <label>Coupon Name</label>
                    <input type="text" id="coupon_name" class="form-control">
                </div>

                <div class="form-group">
                    <label>Discount</label>
                    <input type="number" id="discount" class="form-control">
                </div>

               <select id="discount_type" class="form-control">
                    <option value="">Select Type</option>
                    <option value="percent">Percent</option>
                    <option value="flat">Flat</option>
                </select>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveCoupon()">Save</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteCouponModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal_header">
                <h5 class="modal-title">Delete Coupon</h5>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="delete_coupon_id">
                <p>Are you sure you want to delete this coupon?</p>
                <button class="btn btn-danger" onclick="deleteCoupon()">Delete</button>
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

    <!-- END Page Wrapper -->

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/coupons.js"></script>

   <script>
    $(document).ready(function () {

        $('#view-all-coupons').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ordering: false,
            serverMethod: 'post',
            ajax: {
                url: 'ajax/view-all-coupons-post.php'
            },
            columnDefs: [
                { targets: [0], className: "text-center" },
                { targets: [4], className: "text-center" }
            ],
            columns: [
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'CouponName' },
                { data: 'Discount' },
                { data: 'Type' },
                { data: 'Action' }
            ]
        });

    });
    </script>


</body>


</html>