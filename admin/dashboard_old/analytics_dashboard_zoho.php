<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
<head>
    <meta charset="utf-8">
    
    <meta name="description" content="Aryadibusiness Analytics Dashboard">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    
    $core->setTimeZone();
    $corporate_array = _getTableRecords($conn,'company','where 1');
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-365 days'));
    $data = array();
    $data['start_date'] = $previous_date;
    $data['end_date'] = $current_date;
    $date_range = $previous_date . " - " . $current_date;

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
    if(isset($_SESSION['Roles']['EmployeeID']))
    {
        $Employee_ID = $_SESSION['Roles']['EmployeeID'];
    }

    $BranchAccountManager = false;
    $sql_in_branch_account_string = "";
    if(CheckRole($_SESSION,"Branch Account Manager") == true )
    {
        $BranchAccountManager = true;
        $branch_obj = new Branch($conn);
        $branches_array = $branch_obj->getMappedAccountBranchesofAccountBranchManager($Employee_ID);
        $branches_array_mapped = array();
        foreach($branches_array as $i_branch)
        {
            array_push($branches_array_mapped,$i_branch['ID']);
        }
        $sql_in_branch_account_string = "'" . implode("', '", $branches_array_mapped) . "'";
    }

    // STate Manager Login
    $StateManager = false;
    if(CheckRole($_SESSION,"State Corporate Lead") == true )
    {
        $StateManager = true;
    }
    
    $state_object = new State($conn);
    $sql_in_state_string = "";
    
    if($StateManager)
    {
        $state_array = $state_object->getStatesMapped_StateLead($Employee_ID);
        $state_array_mapped = array();
        foreach($state_array as $state_mapped)
        {
            array_push($state_array_mapped,$state_mapped['StateName']);
        }
        $sql_in_state_string = "'" . implode("', '", $state_array_mapped) . "'";
        $sql_in_branch_account_string = "";
    }
    if($sql_in_state_string != "")
    {
        $where = " where IsActive = 1 and ID IN (Select CompanyID from branch where BranchState IN (".$sql_in_state_string."))";
        $corporate_array = _getTableRecords($conn,'company',$where);
    }
    else
    {
        if($BranchAccountManager)
        {
            $where = " where IsActive = 1 and ID IN (Select CompanyID from branch where ID IN (".$sql_in_branch_account_string."))";
            $corporate_array = _getTableRecords($conn,'company',$where);
        }
        else
        {
            $corporate_array = _getTableRecords($conn,'company','where IsActive = 1');
        }
    }
    

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
    ?>
    <title>
        <?=$ProductName;?> Analytics Dashboard
    </title>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
       
       
        /* Modern Colorful Card Design - 3-4 cards per row, Wider Cards */
        #status_buttons_div {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        #status_buttons_div .panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: none;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        #status_buttons_div .panel-hdr {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        #status_buttons_div .panel-hdr h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }
        
        #status_buttons_div .panel-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        #status_buttons_div .panel-content {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
        }
        
        /* 3 cards per row - proper wider layout */
        #status_buttons_div .row {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 20px !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        @media (max-width: 1400px) {
            #status_buttons_div .row {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 18px !important;
            }
        }
        
        @media (max-width: 1200px) {
            #status_buttons_div .row {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (max-width: 768px) {
            #status_buttons_div .row {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
        }
        
        #status_buttons_div .col-3 {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            flex: none !important;
        }
        
        /* Compact cards with proper sizing */
        #status_buttons_div .d-flex {
            background: white;
            border-radius: 12px;
            padding: 0 !important;
            min-height: 140px;
            max-height: 160px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            border: 1px solid rgba(0,0,0,0.05);
            width: 100% !important;
        }
        
        /* Full height colorful gradient background */
        #status_buttons_div .d-flex::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            height: 100%;
            background: var(--card-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
            transition: all 0.3s;
            z-index: 1;
            opacity: 0.95;
        }
        
        #status_buttons_div .d-flex:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
        }
        
        #status_buttons_div .d-flex:hover::before {
            opacity: 1;
        }
        
        /* Horizontal layout container - Icon left, Label middle, Badges right */
        #status_buttons_div .px-3 {
            padding: 18px 20px !important;
            position: relative;
            z-index: 2;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            height: 100%;
            gap: 18px;
            min-height: 140px;
        }
        
        /* Chart/Icon on left - compact size */
        #status_buttons_div .js-easy-pie-chart {
            width: 70px !important;
            height: 70px !important;
            min-width: 70px !important;
            position: relative;
            z-index: 3;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            padding: 8px;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            margin: 0 !important;
        }
        
        #status_buttons_div .js-easy-pie-chart canvas {
            width: 70px !important;
            height: 70px !important;
        }
        
        /* Status label in middle - properly sized visible text */
        #status_buttons_div .text-muted {
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            margin-top: -83px !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.3;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 8px rgba(0,0,0,0.4), 0 1px 2px rgba(0,0,0,0.5);
            flex: 1;
            display: block !important;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            /* padding: 0 12px; */
        }
        
        /* Badge container on right */
        #status_buttons_div .ml-auto {
            margin-left: auto !important;
            margin-right: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            position: relative !important;
            z-index: 2;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px;
            align-items: flex-end;
            justify-content: center;
            flex-shrink: 0;
        }
        
        #status_buttons_div .d-inline-flex.flex-column {
            align-items: flex-end;
            gap: 8px;
        }
        
        /* Count badge - properly sized */
        /* #status_buttons_div .badge:first-of-type {
            font-size: 18px !important;
            font-weight: 800 !important;
            padding: 10px 1px !important;
            border-radius: 10px !important;
            min-width: auto !important;
            text-align: center;
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.4);
            border: 2px solid rgba(255, 255, 255, 0.4) !important;
            line-height: 1;
            transition: all 0.3s;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: -0.5px;
        } */
        
        /* Percentage badge - properly sized */
        /* #status_buttons_div .badge:last-of-type {
            font-size: 14px !important;
            font-weight: 700 !important;
            padding: 6px 14px !important;
            border-radius: 6px !important;
            min-width: auto !important;
            text-align: center;
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.35) !important;
            backdrop-filter: blur(12px);
            box-shadow: 0 3px 12px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
            border: 2px solid rgba(255, 255, 255, 0.5) !important;
            line-height: 1;
            transition: all 0.3s;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
         */
        #status_buttons_div .d-flex:hover .badge:first-of-type {
            transform: scale(1.03);
            background: rgba(255, 255, 255, 0.4) !important;
        }
        
        #status_buttons_div .d-flex:hover .badge:last-of-type {
            transform: scale(1.02);
            background: rgba(255, 255, 255, 0.45) !important;
        }
        
        /* Status icon - centered in chart, properly sized */
        #status_buttons_div .status-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 30px;
            color: #ffffff;
            z-index: 4;
            pointer-events: none;
            text-shadow: 0 2px 6px rgba(0,0,0,0.4);
            filter: drop-shadow(0 2px 3px rgba(0,0,0,0.3));
        }
        
        #status_buttons_div .js-easy-pie-chart {
            position: relative;
        }
        
        /* Remove any nested flex issues */
        #status_buttons_div .px-3 > * {
            margin-left: 0 !important;
        }
        
        #status_buttons_div .d-flex.align-items-center {
            align-items: center !important;
        }


         /* ============================
   ZOHO ANALYTICS STYLE CARDS
   ============================ */

.quotation-summary-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
    margin-top: 25px;
}

.quotation-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 26px 28px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #eef1f5;
    transition: all .25s ease;
}

.quotation-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 26px rgba(0,0,0,0.09);
}

.quotation-title {
    font-size: 18px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 18px;
}

.total-cost-label {
    font-size: 15px;
    font-weight: 500;
    color: #4a5568;
}

.total-cost-value {
    font-size: 22px;
    font-weight: 800;
    padding: 6px 12px;
    border-radius: 8px;
    color: #fff;
    margin-left: 10px;
}

/* Colors for totals */
.total-green    { background: #16a34a; }
.total-orange   { background: #f59e0b; }
.total-red      { background: #dc2626; }

.cost-list {
    margin-top: 14px;
    padding-left: 18px;
}

.cost-list li {
    font-size: 14px;
    margin-bottom: 6px;
    color: #4a5568;
}

/* Responsive */
@media(max-width: 1200px) {
    .quotation-summary-container {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media(max-width: 768px) {
    .quotation-summary-container {
        grid-template-columns: 1fr;
    }
}

          
        
    </style>
    <?php 
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
</head>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" id="UserType" value="<?php echo $UserType;?>" />
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics Dashboard</a></li>

                    </ol>
                    <?php 
                    if($StateManager)
                    {
                        ?>
                       
                            
                    <div class="alert alert-primary">
                        <div class="d-table w-100">
                            <div class="d-table-cell align-top width-6">
                                <span class="icon-stack icon-stack-lg">
                                    <i class="base base-6 icon-stack-3x opacity-100 color-primary-500"></i>
                                    <i class="base base-10 icon-stack-2x opacity-100 color-primary-300 fa-flip-vertical"></i>
                                    <i class="fal fa-info icon-stack-1x opacity-100 color-white"></i>
                                </span>
                            </div>
                            <div class="d-table-cell pl-1">
                                <span class="h5">State Manager Dashboard</span>
                                <br> States Managed - 
                                <?php 
                                foreach($state_array_mapped as $i_state)
                                {
                                    ?>
                                   
                                    <span class="badge badge-info"><?php echo $i_state;?></span>
                                                    
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    ?>
                    <input type="hidden" id="sql_in_state_string" value="<?php echo $sql_in_state_string;?>">
                    <input type="hidden" id="sql_in_branch_account_string" value="<?php echo $sql_in_branch_account_string;?>">
                    <div class="panel mb-2">  
                            <div class="panel-content p-3">
                                <div class="row">
                                <?php
                                if($CorporateID == -1)
                                {
                                ?>
                                    
                                    <div class="col-md-3 ">
                                       
                                        <select class="select2 form-control w-100" id="corporate_name" name="corporate_name" onchange="RefreshBranchAnalytics(this.value)">
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
                                            
                                <?php
                                }
                                ?>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">         
                                </div>
                                <div class="col-md-3" id="state_region_view">
                                            
                                </div>
                                <div class="col-md-3">
                                    <button type="button" onclick="GenerateDashboard(<?php echo $CorporateID;?>);" class="btn btn-sm btn-primary ml-3 waves-effect waves-themed">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="analytics_corporate_dashboard" >

                        <!-- Status buttons -->
                        <div class="row" id="status_buttons_div">
                            
                        </div>
                        <div class="spinner-grow rounded-0 text-danger mb-5" role="status" id="status_buttons_div_loader" style="margin-left: 48%;display:none;">
                            <span class="sr-only">Loading...</span>
                        </div>

                        <div class="row" id="quotation_dashboard_div">
                                    
                        </div>
                       
                            <div id="panel-rnm" class="row panel" style="margin-bottom:1% ;display:none;">
                                <div class="panel-hdr">
                                    <h2>
                                        Detailed Status
                                    </h2>
                                </div>

                                <div class="row" style="margin:0px;">
                                    <div class="col-lg-4 col-xl-4 panel" id="corporate_branch_panel">  
                                    </div>
                                    <div class="col-lg-8 col-xl-8" id="corporate_branch_rest_panel">
                                        <div class="row panel" id="corporate_name_ticket_count_fetch">
                                            
                                        </div>
                                        <div class="row border">
                                            
                                            <div class="col-lg-6 col-xl-6" id="type_status_view">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-xl-6 panel" id="ticket_status_bar_graph" style="display: none;">
                                                <!-- <div class="panel-container show">
                                                    <div class="panel-content"> -->
                                                        <canvas id="ticket_status_graph_id" height="300px;"></canvas>
                                                    <!-- </div>
                                                </div> -->
                                            </div>
                                            <div class="col-lg-6 col-xl-6 panel" id="type_status_pie_region_graph" style="display:none;">
                                                <canvas id="region_status_graph_id" height="300px;"></canvas>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 col-xl-12" id="state_wise_ticket_count" style="display:none;">
                                                <canvas id="state_wise_ticket_graph_id" height="150px;"></canvas>
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
    
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <!--script src="../js/statistics/chartjs/chartjs.bundle.js"></script-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src= "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
   
    <script src="../js/modules/analytics_dashboard.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/statistics/easypiechart/easypiechart.bundle.js"></script>
    <script>
    // Modern Card Enhancement Function
    function enhanceStatusCards() {
        setTimeout(function() {
            $('#status_buttons_div .d-flex').each(function() {
                const $card = $(this);
                const $badge = $card.find('.badge:first-of-type');
                let badgeColor = $badge.css('background-color');
                
                // Convert RGB to hex
                let color = badgeColor;
                if (badgeColor && badgeColor.startsWith('rgb')) {
                    const rgb = badgeColor.match(/\d+/g);
                    if (rgb && rgb.length >= 3) {
                        const r = parseInt(rgb[0]);
                        const g = parseInt(rgb[1]);
                        const b = parseInt(rgb[2]);
                        color = '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
                    }
                }
                
                // Color gradient mapping
                const gradientMap = {
                    '#ff0000': 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                    '#ff0022': 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                    '#10b981': 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    '#f59e0b': 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                    '#3b82f6': 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                    '#8b5cf6': 'linear-gradient(135deg, #2b95d6 0%, #045891 100%)',
                    '#ec4899': 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)',
                    '#fbbf24': 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)',
                    '#6b7280': 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)',
                    '#6366f1': 'linear-gradient(135deg, #2b95d6 0%, #045891 100%)',
                    '#ffc107': 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)',
                    '#ff9800': 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
                };
                
                let gradient = gradientMap[color.toLowerCase()] || `linear-gradient(135deg, ${color} 0%, ${color}CC 100%)`;
                
                // Get status text for icon
                const statusText = $card.find('.text-muted').text().trim().toLowerCase();
                let iconClass = 'fa-chart-line';
                
                if (statusText.includes('closed') || statusText.includes('complete')) {
                    iconClass = 'fa-check-circle';
                    if (!gradientMap[color.toLowerCase()]) gradient = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                } else if (statusText.includes('overdue') || statusText.includes('cancel')) {
                    iconClass = 'fa-exclamation-triangle';
                    if (!gradientMap[color.toLowerCase()]) gradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                } else if (statusText.includes('progress') || statusText.includes('assigned')) {
                    iconClass = 'fa-clock';
                    if (!gradientMap[color.toLowerCase()]) gradient = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                } else if (statusText.includes('approved') || statusText.includes('quote approved')) {
                    iconClass = 'fa-check-square';
                    if (!gradientMap[color.toLowerCase()]) gradient = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                } else if (statusText.includes('pending') || statusText.includes('raised')) {
                    iconClass = 'fa-hourglass-half';
                    if (!gradientMap[color.toLowerCase()]) gradient = 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)';
                } else if (statusText.includes('hold')) {
                    iconClass = 'fa-pause-circle';
                    if (!gradientMap[color.toLowerCase()]) gradient = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';
                } else if (statusText.includes('rejected')) {
                    iconClass = 'fa-times-circle';
                    if (!gradientMap[color.toLowerCase()]) gradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                } else if (statusText.includes('closure') || statusText.includes('submitted')) {
                    iconClass = 'fa-check-double';
                    gradient = 'linear-gradient(135deg, #2b95d6 0%, #045891 100%)';
                }
                
                // Apply gradient
                $card.css('--card-gradient', gradient);
                
                // Add icon if not exists
                if ($card.find('.status-icon').length === 0) {
                    const $chart = $card.find('.js-easy-pie-chart');
                    $chart.append(`<i class="fal ${iconClass} status-icon"></i>`);
                }
            });
        }, 600);
    }
    
    // Override GenerateStatusButtons
    const originalGenerateStatusButtons = window.GenerateStatusButtons;
    window.GenerateStatusButtons = function(CorporateID, state_filter, region_filter, filter_date) {
        var sql_in_state_string = $("#sql_in_state_string").val();
        var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
        document.getElementById("status_buttons_div_loader").style.display = "block";
        document.getElementById("status_buttons_div").style.display = "none";
        
        $.post("ajax/get_status_buttons.php", {
            filter_date: filter_date,
            sql_in_state_string: sql_in_state_string,
            sql_in_branch_account_string: sql_in_branch_account_string,
            CorporateID: CorporateID,
            state_filter: state_filter,
            region_filter: region_filter
        }, function (data, status) {
            document.getElementById("status_buttons_div_loader").style.display = "none";
            document.getElementById("status_buttons_div").innerHTML = data;        
            document.getElementById("status_buttons_div").style.display = "";
            
            // Initialize pie charts
            $('.js-easy-pie-chart').each(function() {
                const $chart = $(this);
                const $card = $chart.closest('.d-flex');
                let badgeColor = $card.find('.badge:first-of-type').css('background-color');
                
                // Convert to hex if RGB
                if (badgeColor && badgeColor.startsWith('rgb')) {
                    const rgb = badgeColor.match(/\d+/g);
                    if (rgb && rgb.length >= 3) {
                        const r = parseInt(rgb[0]);
                        const g = parseInt(rgb[1]);
                        const b = parseInt(rgb[2]);
                        badgeColor = '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
                    }
                }
                
                if (!badgeColor || badgeColor === 'transparent') {
                    badgeColor = '#ffffff';
                }
                
                $(this).easyPieChart({
                    size: 80,
                    barColor: badgeColor,
                    trackColor: 'rgba(255, 255, 255, 0.2)',
                    scaleColor: 'rgba(255, 255, 255, 0.3)',
                    scaleLength: 3,
                    lineWidth: 8,
                    lineCap: 'round',
                    animate: {
                        duration: 1500,
                        enabled: true
                    }
                });
            });
            
            // Enhance cards
            enhanceStatusCards();
        });
    };
    
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_analytics_dashboard").addClass("active");
        if($("#corporate_name").length)
        {
            $("#corporate_name").select2();
        }
        $('#filter_date').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        $('#ad_branch_name').on('select2:select', function (e) {
            $(this).select2('close');
        });
    }); 
    RefreshBranchAnalytics(<?php echo $CorporateID; ?>);
    
    </script>
    
</body>

</html>