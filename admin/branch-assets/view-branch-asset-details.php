<?php session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    require_once('../includes/autoloader.inc.php');
    $conn = _connectodb();
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    
    <meta name="description" content="View Equipment Details">
    <?php
    include('../includes/common_head_content.php');
    ?>

        <?php

        $EquipmentID = "N.A.";
        if(!isset($_SESSION['EquipmentID']))
        {
        }
        else
        {
            $EquipmentID = $_SESSION['EquipmentID'];
        }
        $core = new Core();
        $CorporateID = -1;
        if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin")
	    {
	        $corporate_user = true;
	        $CorporateID = $_SESSION['Roles']['CorporateID'];

	    }
        
        $ProductName = "TechXpert";
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
        ?>
    <title>
        View Equipment Details - <?=$ProductName;?>
    </title>
        <?php 
        if(isset($product_configuration['favicon']))
        {
            ?>
            <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
            <?php
        }
        if($ProductName != "TechXpert")
        {
            include("../css/client_generated_css.php");
        }
        ?>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>



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
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item"><a href="view-branch-assets.php">View Assets</a></li>
                        <li class="breadcrumb-item active">Asset Details</li>

                    </ol>
                    <input type="hidden" id="TicketManager_Access" value="<?php echo $TicketManager;?>">


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Asset Details</span>
                                    </h2>

                                </div>


                                <div id="panel-2" class="panel mt-3">

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
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#history " role="tab">
                                                            <i class="fas fa-server"></i>
                                                            <span class="hidden-sm-down ml-1">History </span>
                                                        </a>
                                                    </li>
                                                    
                                                   
                                                </ul>

                                                <div class="tab-content">
                                                    <div class="tab-pane fade show active" id="details" role="tabpanel">

                                                        <?php
                                                              include('include/asset_details_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade show active" id="history" role="tabpanel">

                                                        <?php
                                                              include('include/asset_history.php');
                                                         ?>

                                                    </div>
                                                    
                                                    
                                                </div>

                                            </div>
                                        </div>
                                    </div>
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

    <!-- Button trigger modal -->


    <!-- END Page Wrapper -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/branch-assets.js"></script>
    
    <script type="text/javascript">
         $("#booking_status").select2();
         $(document).ready(function() 
        {
             $("#nav_company_assets").addClass("active");
        });
    </script>



</body>


</html>