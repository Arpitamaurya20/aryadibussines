<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>
        Aryadibusiness Financial Dashboard
    </title>
    <meta name="description" content="Aryadibusiness Analytics Dashboard">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    $corporate_array = _getTableRecords($conn,'company','where 1');

    $CorporateID = -1;
    $BranchID = -1;
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
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
        .page-content .panel
        {
            margin-bottom: 0px;
        }
        .panel .panel-container .panel-content
        {
            padding: 0.5rem 0.5rem;
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Financial Dashboard</a></li>
                    </ol>
                    <?php
                    if($CorporateID == -1)
                    {
                    ?>
                        <div class="panel mb-2">  
                           
                                <div class="panel-content p-3">
                                    <div class="row">
                                        <div class="col-md-3 ">
                                            
                                            <select class="form-control w-100" id="corporate_name" name="corporate_name" onchange="LoadDailyTracker(this.value)">
                                                <option value="-1">Select Corporate</option>
                                                <?php
                                                foreach($corporate_array as $corporate)
                                                {
                                                ?>
                                                    <option value="<?php echo $corporate['ID'];?>">
                                                        <?php echo $corporate['CompanyName'];?>
                                                    </option>
                                                <?php
                                                }

                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                  
                        </div>
                    <?php
                    }
                    ?>
                    <div class="row mt-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 rounded overflow-hidden position-relative text-white mb-g" style="background-color: #045891 !important;">
                                <div class="">
                                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                        21.5k
                                        <small class="m-0 l-h-n">users signed up</small>
                                    </h3>
                                </div>
                                <i class="fal fa-user position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n1" style="font-size:6rem"></i>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 bg-warning-400 rounded overflow-hidden position-relative text-white mb-g">
                                <div class="">
                                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                        $10,203
                                        <small class="m-0 l-h-n">Visual Index Figure</small>
                                    </h3>
                                </div>
                                <i class="fal fa-gem position-absolute pos-right pos-bottom opacity-15  mb-n1 mr-n4" style="font-size: 6rem;"></i>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 bg-success-200 rounded overflow-hidden position-relative text-white mb-g">
                                <div class="">
                                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                        - 103.72
                                        <small class="m-0 l-h-n">Offset Balance Ratio</small>
                                    </h3>
                                </div>
                                <i class="fal fa-lightbulb position-absolute pos-right pos-bottom opacity-15 mb-n5 mr-n6" style="font-size: 8rem;"></i>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 bg-info-200 rounded overflow-hidden position-relative text-white mb-g">
                                <div class="">
                                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                        +40%
                                        <small class="m-0 l-h-n">Product level increase</small>
                                    </h3>
                                </div>
                                <i class="fal fa-globe position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4" style="font-size: 6rem;"></i>
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
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <!--script src="../js/statistics/chartjs/chartjs.bundle.js"></script-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src= "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
   
    <script src="../js/modules/analytics_dashboard.js"></script>
    <script>
    $(document).ready(function() {
      /*  $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_analytics_dashboard").addClass("active");
        $("#corporate_name").select2();

         $('#ad_branch_name').on('select2:select', function (e) {
                $(this).select2('close');
            });*/
    });
    
    </script>
    
</body>

</html>