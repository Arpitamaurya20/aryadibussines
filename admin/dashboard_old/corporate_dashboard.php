<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>
        Aryadibusiness Dashboard
    </title>
    <meta name="description" content="Aryadibusiness Dashboard">
    <?php
    include('../controllers/common_controllers.php');
    include('controller/dashboard_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    include('../includes/common_head_content.php');
    $conn = _connectodb();
    $CorporateID = -1;
    $BranchID = -1;
    if($UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    //echo "Corporate ".$CorporateID." Branch".$BranchID;
    $where = " where IsActive = 1";
    $status_array = _getTableRecords($conn,'corporate_tickets_status',$where);
    $TicketStatusArray = getTicketStatus($conn,$CorporateID,$BranchID);
    $ChartData = getChartData($conn,$status_array,$TicketStatusArray);
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
    .nav-footer {
        height: unset !important;
    }
    .slimScrollDiv {
        background: #0581c1 !important;
    }
    .l-h-n {
        line-height: normal;
        padding: 10px;
        text-align: center;
        font-size: 20px;
    }
    .bg-primary-300 {
        background-color: #03A9F4 !important;
    }
    .bg-danger-200 {
        background-color: #d71771 !important;
    }
    .bg-success-200 {
        background-color: #007266 !important;
    }
    .bg-warning-200 {
        background-color: #edac20 !important;
    }
    </style>
</head>
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                    </ol>
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="row">
                                <?php
                                $filter = " where CorporateID = $CorporateID and IsActive = 1";
                                if($BranchID != -1)
                                {
                                    $filter = $filter." and BranchID = $BranchID";
                                }
                                if($_Nav_Corporate_Tickets)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-dark rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/tickets.jpg) center center no-repeat;">
                                        <div class="">
                                            <a href="../corporate-tickets/view-corporate-tickets" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                   
                                                    $num_bookings = _getTotalRows($conn,'corporate_tickets',$filter);
                                                ?>
                                                    <?php echo $num_bookings; ?>
                                                    <small class="m-0 l-h-n">Tickets </small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                if($_Nav_Corporate_Branches && $BranchID == -1)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-dark rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/branches.jpg) center center no-repeat;">
                                        <div class="">
                                            <a href="../branch/view-branch" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                    $filter = " where CompanyID = $CorporateID and IsActive = 1";
                                                    $num_branches = _getTotalRows($conn,'branch',$filter);
                                                ?>
                                                    <?php echo $num_branches; ?>
                                                    <small class="m-0 l-h-n">Branches </small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                }

                                ?>
                            </div>
                        </div>
                        
                        <!-- Analytics -->
                        <div class="col-xl-12">
                                <div class="row">
                                    <div class="col-xl-6">
                                        <div id="panel-6" class="panel">
                                            <div class="panel-hdr">
                                                <h2>
                                                    Status <span class="fw-300"><i>Analytics</i></span>
                                                </h2>
                                                
                                            </div>
                                            <div class="panel-container show">
                                                <div class="panel-content">
                                                    
                                                    <div id="pieChart">
                                                        <canvas style="width:100%; height:300px;"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div id="panel-4" class="panel">
                                            <div class="panel-hdr">
                                                <h2>
                                                    Ticket <span class="fw-300"><i>Stats</i></span>
                                                </h2>
                                            </div>
                                            <div class="panel-container show">
                                                <div class="panel-content">
                                                    
                                                    <table class="table table-bordered m-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Status</th>
                                                                <th>Number</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php 
                                                            foreach($status_array as $status)
                                                            {
                                                                $Status = $status['Status'];
                                                                $td_id = $Status."-id";
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $Status; ?></td>
                                                                <td id="<?php echo $td_id;?>"><?php echo $TicketStatusArray[$Status]; ?></td>
                                                            </tr>
                                                            <?php
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div> <!-- table panel -->
                                    </div>
                                </div>
                            </div>
                        <!-- Analytics -->
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
    <script src="../js/statistics/chartjs/chartjs.bundle.js"></script>
    <script>
        /* pie chart */
            var pieChart = function()
            {
                var config = {
                    type: 'pie',
                    data:
                    {
                        datasets: [
                        {
                            data: <?php echo json_encode($ChartData['stat_data']); ?>,
                            backgroundColor: <?php echo json_encode($ChartData['bg_color']); ?>,
                            label: 'Status' // for legend
                        }],
                        labels: <?php echo json_encode($ChartData['stat_array']); ?>
                    },
                    options:
                    {
                        responsive: true,
                        legend:
                        {
                            display: true,
                            position: 'right',
                        }
                    }
                };
                new Chart($("#pieChart > canvas").get(0).getContext("2d"), config);
            }
            /* pie chart -- end */
         $(document).ready(function() {
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_dashboard").addClass("active");
        });
    </script>
    <script>
   
    $(document).ready(function() {
        $("#nav_dashboard").addClass("active");
        pieChart();
    });
    /* bar chart -- end */
    </script>
</body>
</html>