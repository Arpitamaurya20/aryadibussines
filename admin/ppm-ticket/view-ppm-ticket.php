<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	  include('../controllers/common_controllers.php');
        include('controller/ppm_controller.php');
        include('../branch/controller/branch_controller.php');
        include('../company/controller/company_controller.php');
        include('../branch-assets/controller/branch_assets_controller.php');
        include('../includes/autoloader.inc.php');
	  $conn = _connectodb();
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
		$conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    
    <meta name="description" content="View Schema">
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
    </style>
    <?php
    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;
    $ID = $_SESSION['BranchAssetsID'];
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

    if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }


    $CreatedBy = $_SESSION['pb_username'];

    // echo $CorporateID."<br>";
    // echo $BranchID."<br>";
    // echo $ID."<br>";

    $ppm_tickets_array = getAllPPMTickets($conn,$CorporateID,$BranchID,$ID);
    // print_r($ppm_tickets_array);
    // die();

    $corporate_array = getAllCompanies($conn);
    $company_array_key = generateArraywithKey($corporate_array);

    $branches = getAllBranches($conn,-1);
    $branch_array_key = generateArraywithKey($branches);

    $branch_assets = getAllBranchAssets($conn,-1);
    $branch_assets_array_key = generateArraywithKey($branch_assets);

    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) 
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
    ?>
    <title>
        Manage PPM - <?=$ProductName;?>
    </title>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>


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
                        <li class="breadcrumb-item"><a href="../branch-assets/view-branch-assets">Manage Branch
                                Assets</a></li>
                        <li class="breadcrumb-item active">Manage PPM</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View PPM
                                    </h2>
                                    <a href="#" onclick="ExportPPMTicketData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>

                                    <a href="#" onclick="openPPM_modal(<?php echo $ID; ?>,<?php echo $BranchID;?>,<?php echo $CorporateID; ?>,'<?php echo $CreatedBy;?>')" class="btn btn-info"
                                        style="margin-right:20px;">Add</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-ppm"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                     <th>#</th>
                                                    <th>Ticket ID</th>
                                                    <th>Branch Asset </th>
                                                    <th>Corporate / Branch</th>
                                                    <th>PPM Date</th>
                                                    <th>Date / Time</th>
                                                    <th>Status</th>
                                                    <th>View Ticket</th>
                                                    <?php
                                                    if($techx_admin)
                                                    {
                                                    ?>
                                                        <th>Delete</th>
                                                    <?php
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                     <?php
                                                    $i=1;

                                                    foreach($ppm_tickets_array as $ppm_tickets)
                                                    {
                                                    $id  = $ppm_tickets['ID'];

                                                    $CorporateID = $ppm_tickets['CorporateID'];
                                                    $Corporate = $company_array_key[$CorporateID]['CompanyName'];

                                                    $BranchID = $ppm_tickets['BranchID'];
                                                    $Branch = $branch_array_key[$BranchID]['BranchSite'];
                                                    $BranchAsset = "N.A.";
                                                    if($ppm_tickets['BranchAssetID'] != -1)
                                                    {
                                                        $BranchAssetID = $ppm_tickets['BranchAssetID'];
                                                        $BranchAsset = $branch_assets_array_key[$BranchAssetID]['EquipmentName'];
                                                    }
                                                    ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $ppm_tickets['TicketID']; ?></td>
                                                    <td><?php echo $BranchAsset; ?></td>
                                                    <td><?php echo $Corporate."<br>".$Branch; ?></td>
                                                    
                                                    <td><?php echo $ppm_tickets['PPMDate']; ?></td>
                                                    <td>
                                                        <?php echo $ppm_tickets['CreatedDate']; ?> / <br>
                                                        <?php echo $ppm_tickets['CreatedTime']; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge cursor-pointer  <?php echo ($ppm_tickets['Status'] === 'Closed') ? 'badge-danger' : 'badge-success'; ?>"><?php echo $ppm_tickets['Status']; ?></span>

                                                    </td>
                                                    <td>
                                                        <a onclick="ViewPPMTicketDetails(<?php echo $id; ?>)">
                                                            <span class="badge badge-primary cursor-pointer">View
                                                            Ticket</span>
                                                        </a>
                                                    </td>
                                                    <?php
                                                    if($techx_admin)
                                                    {
                                                    ?>
                                                    <td><a onclick="DeleteCustomerDetail('<?php echo $id;?>')"><i
                                                                class="fal fa-trash" aria-hidden="true"></i>
                                                    </td>
                                                    <?php
                                                    }
                                                    ?>
                                                </tr>
                                                <?php
                                                    $i++;
                                                    }
                                                    ?>

                                                </tr>
                                            </tbody>

                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_arc_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="branch_modal_title"> </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="raise_ppm_ticket">
                                        <div class="form-group">
                                            <div class="row align-items-center">
                                                <!-- <div class="col-7 mt-3">
                                                    <label>Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="name" id="name"
                                                        placeholder="Enter Name">
                                                </div> -->
                                                <div class="col-10 mt-3">
                                                    <label>No.of PPM Days<span class="text-danger">*</span> </label>
                                                    <input type="text" class="form-control" name="number_of_days"
                                                        id="number_of_days" placeholder="Enter No.of PPM Days">
                                                </div>
                                                <div class="col-2 mt-2">
                                                    <a href="#" onclick="addFields()" class="btn btn-info"
                                                        style="margin-right:20px; margin-top:30px;">Add</a>
                                                </div>
                                            </div>

                                            <div class="row align-items-center" id="field_container">
                                            </div>

                                        </div>
                                        <input type="hidden" name="CorporateID" id="corporate_modal_id" value="">
                                        <input type="hidden" name="BranchID" id="branch_modal_id" value="">
                                        <input type="hidden" name="BranchAssetID" id="branch_asset_modal_id" value="">
                                        <input type="hidden" name="Type" value="AMC">
                                        <input type="hidden" name="CreatedBy" id="created_by_modal" value="">
                                        <button type="submit" id="saving_btn" style="display:none;" class="btn btn-primary" onclick="return RaisePPMTickets()">Save</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>

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
    <script src="../js/modules/ppm-ticket.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>

    <script type="text/javascript">
    $(document).ready(function() 
    {
         $("#nav_ppm_tickets").addClass("active");
    });


    </script>

</body>


</html>