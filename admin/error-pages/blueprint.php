<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/corporate_tickets_controller.php');
        include('../company/controller/company_controller.php');
        include('../branch/controller/branch_controller.php');
        include('../branch-assets/controller/branch_assets_controller.php');
        require_once('../includes/autoloader.inc.php');
        $UserType = SessionCheck();
        setTimeZone();
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
	?>
    <meta charset="utf-8">
    <title>
        Corporate Tickets
    </title>
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">

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
        z-index: 1;
    }
    .tab_modal_heading h2 {
        font-size: 18px;
        text-align: center;
        color: #fff;
        font-weight: 500;
        margin-bottom: 20px;
    }
    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
    }

    .edit_header {
        background-color: #027dc1;
        color: #fff;
    }

    .form_submit {
        background-color: #2196f3;
        color: #fff;
        border: none;
        border-radius: 4px;
    }

    .edit_header .close {
        opacity: 1 !important;
        color: #fff;

    }

    .tab_modal_heading h2 {
        font-size: 18px;
        text-align: center;
        color: #fff;
        font-weight: 500;
        margin-bottom: 20px;
    }
    
    </style>
   
    
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
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
                
                    <?php
                        include('../includes/common_header.php');
                    ?>
                <main id="js-page-content" role="main" class="page-content">
                    
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
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/corporate-tickets.js"></script>
</body>
</html>


