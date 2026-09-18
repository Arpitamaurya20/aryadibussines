<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	   include('../controllers/common_controllers.php');
        include('controller/ppm_controller.php');
        include('controller/state_manager_verification_controller.php');
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
        $StateManager = userHasStateCorporateLeadAccess($_SESSION);
        $psmvCanAccess = psmv_canUserVerifyTicket($_SESSION);
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --eam-bg-gradient: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
        --eam-primary: #2563eb;
        --eam-primary-hover: #1d4ed8;
        --eam-secondary: #475569;
        --eam-card-bg: rgba(255, 255, 255, 0.95);
        --eam-card-border: rgba(226, 232, 240, 0.8);
        --eam-text-main: #0f172a;
        --eam-text-muted: #64748b;
        --eam-accent-raised: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        --eam-accent-closed: linear-gradient(135deg, #10b981 0%, #047857 100%);
        --eam-accent-assigned: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
        --eam-shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.03), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
        --eam-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -6px rgba(0, 0, 0, 0.03);
    }

    body.mod-bg-1.desktop {
        font-family: 'Inter', sans-serif !important;
    }

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

    .psmv-ticket-id-cell .fa-check-circle,
    .psmv-ticket-id-cell .fa-times-circle {
        font-size: 1rem;
        vertical-align: middle;
    }

    #psmvVerificationModal .custom-control-label {
        cursor: pointer;
    }

    #psmvVerificationModal .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }

    /* EAM Redesign CSS overrides */
    .eam-title {
        font-family: 'Outfit', sans-serif !important;
        font-weight: 700 !important;
        color: var(--eam-text-main) !important;
    }

    .eam-stat-card {
        background: var(--eam-card-bg);
        border: 1px solid var(--eam-card-border);
        border-radius: 12px !important;
        padding: 20px !important;
        box-shadow: var(--eam-shadow-soft);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .eam-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }

    .eam-stat-card.raised::before { background: var(--eam-accent-raised); }
    .eam-stat-card.closed::before { background: var(--eam-accent-closed); }
    .eam-stat-card.assigned::before { background: var(--eam-accent-assigned); }

    .eam-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--eam-shadow-hover);
    }

    .eam-stat-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        transition: all 0.3s ease;
    }

    .eam-stat-card.raised .eam-stat-icon-wrapper {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
    .eam-stat-card.closed .eam-stat-icon-wrapper {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    .eam-stat-card.assigned .eam-stat-icon-wrapper {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .eam-stat-card:hover .eam-stat-icon-wrapper {
        transform: scale(1.08);
    }

    .panel {
        background: var(--eam-card-bg) !important;
        border: 1px solid var(--eam-card-border) !important;
        box-shadow: var(--eam-shadow-soft) !important;
        border-radius: 12px !important;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .panel-hdr {
        border-bottom: 1px solid var(--eam-card-border) !important;
        background: transparent !important;
        padding: 16px 20px !important;
        height: auto !important;
        min-height: auto !important;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .panel-hdr h2 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.15rem !important;
        font-weight: 600 !important;
        color: var(--eam-text-main) !important;
        margin: 0 !important;
    }

    .eam-filter-bar {
        padding: 16px 20px;
        background: rgba(248, 250, 252, 0.6);
        border-bottom: 1px solid var(--eam-card-border);
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }

    .eam-filter-item {
        flex: 1 1 180px;
        min-width: 140px;
    }

    .form-control, select.form-control {
        height: 38px !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 6px 12px !important;
        font-size: 13px !important;
        color: var(--eam-text-main) !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--eam-primary) !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
    }

    .select2-container--default .select2-selection--single {
        height: 38px !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        padding-left: 12px !important;
        color: var(--eam-text-main) !important;
        font-size: 13px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }

    .btn {
        height: 38px;
        border-radius: 8px !important;
        font-weight: 500 !important;
        font-size: 13px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 16px !important;
        transition: all 0.2s ease;
    }
    .btn-primary {
        background-color: var(--eam-primary) !important;
        border-color: var(--eam-primary) !important;
    }
    .btn-primary:hover {
        background-color: var(--eam-primary-hover) !important;
        border-color: var(--eam-primary-hover) !important;
    }
    .btn-warning {
        background-color: #fffbeb !important;
        color: #b45309 !important;
        border: 1px solid #fde68a !important;
    }
    .btn-warning:hover {
        background-color: #fef3c7 !important;
    }
    .btn-info {
        background-color: #f0fdf4 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0 !important;
    }
    .btn-info:hover {
        background-color: #dcfce7 !important;
    }

    /* Modern Table design overrides */
    .table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100% !important;
    }
    .table thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 600 !important;
        text-transform: uppercase;
        font-size: 11px !important;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0 !important;
        border-top: none !important;
        padding: 12px 16px !important;
    }
    .table tbody tr {
        transition: background-color 0.15s ease;
    }
    .table tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .table tbody td {
        padding: 12px 16px !important;
        vertical-align: middle !important;
        border-top: 1px solid #e2e8f0 !important;
        color: var(--eam-text-main) !important;
        font-size: 13.5px !important;
    }

    /* Badge details styling */
    .badge {
        font-weight: 600 !important;
        padding: 5px 10px !important;
        border-radius: 6px !important;
        text-transform: uppercase;
        font-size: 10.5px !important;
        letter-spacing: 0.3px;
    }
    .badge-primary, .badge-assigned {
        background-color: #dbeafe !important;
        color: #1e40af !important;
    }
    .badge-success, .badge-closed {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
    }
    .badge-warning, .badge-raised {
        background-color: #fef3c7 !important;
        color: #92400e !important;
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
    SUM(CASE WHEN Status = 'Raised' THEN 1 ELSE 0 END)   AS raised_ppm,
    SUM(CASE WHEN Status = 'Assigned' THEN 1 ELSE 0 END) AS assigned_ppm,
    SUM(CASE WHEN Status = 'Closed' THEN 1 ELSE 0 END)   AS closed_ppm
FROM ppm_tickets
WHERE IsActive = 1
AND PPMDate BETWEEN '$monthStart' AND '$monthEnd';

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
// --------

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
                        <li class="breadcrumb-item active">View PPM Tickets</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2 class="eam-title">
                                        <i class="fal fa-clipboard-list mr-2 text-primary"></i> View PPM Tickets
                                    </h2>
                                    <div class="panel-toolbar d-flex align-items-center" style="gap: 10px; flex-wrap: wrap;">
                                        <?php
                                        if($CorporateID == -1)
                                        {
                                        ?>
                                        <a href="../auto-ppm/view-auto-ppm-assets" target="_blank" class="btn btn-warning btn-sm">
                                            <i class="fal fa-file-invoice mr-2"></i> View Auto PPM Assets
                                        </a>
                                        <?php
                                        }
                                        ?>

                                        <button type="button" onclick="FilterPPMTickets();" class="btn btn-primary btn-sm">
                                            <i class="fal fa-search mr-2"></i> Search
                                        </button>
                                        
                                        <?php
                                        if($CorporateID == -1)
                                        {
                                        ?>
                                        <form id="export_form" class="d-flex align-items-center" style="margin: 0; gap: 12px; flex-wrap: wrap;">
                                            <div class="d-flex align-items-center" style="gap: 8px;">
                                                <div class="custom-control custom-radio custom-control-inline m-0">
                                                    <input type="radio" class="custom-control-input" name="date_type_export" id="ppm_date" value="ppm_date" checked>
                                                    <label class="custom-control-label font-weight-bold text-muted" for="ppm_date" style="font-size: 13px;">PPM Date</label>
                                                </div>
                                                <div class="custom-control custom-radio custom-control-inline m-0">
                                                    <input type="radio" class="custom-control-input" name="date_type_export" id="close_date" value="close_date">
                                                    <label class="custom-control-label font-weight-bold text-muted" for="close_date" style="font-size: 13px;">Close Date</label>
                                                </div>
                                            </div>
                                            <button type="button" onclick="ExportPPMTicketsData();" class="btn btn-info btn-sm">
                                                <i class="fal fa-download mr-2"></i> Export Data
                                            </button>
                                            <input type="hidden" name="filter_date_export" id="filter_date_export" value="">  
                                            <input type="hidden" name="status_export" id="status_export" value=""> 
                                            <input type="hidden" name="state_export" id="state_export" value="">
                                            <input type="hidden" name="company_account_export" id="company_account_export" value="<?php echo ($CorporateID == 183) ? 183 : ''; ?>">
                                            <input type="hidden" name="EmployeeID" id="EmployeeID" value="<?php echo $Employee_ID; ?>">
                                        </form>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                </div>

                                <?php
                                if($CorporateID == -1)
                                {
                                ?>
                                <!-- Monthly PPM Stats Cards -->
                                <div class="row mb-3 mt-3 px-3">
                                    <div class="col-md-4">
                                        <div class="eam-stat-card raised">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="text-uppercase small text-muted font-weight-bold mb-1">Raised PPM (Month)</div>
                                                    <div class="h2 font-weight-bold mb-0 text-dark">
                                                        <?= $raisedPPMCount; ?>
                                                    </div>
                                                </div>
                                                <div class="eam-stat-icon-wrapper">
                                                    <i class="fal fa-arrow-up"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-3 mt-md-0">
                                        <div class="eam-stat-card closed">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="text-uppercase small text-muted font-weight-bold mb-1">Closed PPM (Month)</div>
                                                    <div class="h2 font-weight-bold mb-0 text-dark">
                                                        <?= $closedPPMCount; ?>
                                                    </div>
                                                </div>
                                                <div class="eam-stat-icon-wrapper">
                                                    <i class="fal fa-check-circle"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-3 mt-md-0">
                                        <div class="eam-stat-card assigned">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="text-uppercase small text-muted font-weight-bold mb-1">Assigned PPM (Month)</div>
                                                    <div class="h2 font-weight-bold mb-0 text-dark">
                                                        <?= $assignedPPMCount; ?>
                                                    </div>
                                                </div>
                                                <div class="eam-stat-icon-wrapper">
                                                    <i class="fal fa-user-check"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                }
                                ?>

                                <!-- Filters -->
                                <div class="eam-filter-bar">
                                    <div class="eam-filter-item">
                                        <label class="form-label text-muted small font-weight-bold mb-1" for="filter_date">Date Range</label>
                                        <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">
                                    </div>
                                    <div class="eam-filter-item">
                                        <label class="form-label text-muted small font-weight-bold mb-1" for="ticket_status">Status</label>
                                        <select class="form-control" name="ticket_status" id="ticket_status">
                                            <option value="">Select Status</option>
                                            <?php
                                            foreach ($status_array as $e_status) 
                                            {
                                                $Status_name = $e_status['Status'];
                                                $Status_name_display = $Status_name;

                                                if($Status_name == 'Planned' && $CorporateID != -1)
                                                {
                                                    continue;
                                                }
                                                if($CorporateID == 183 && $Status_name_display == "Hold by Aryadibusiness")
                                                {
                                                    $Status_name_display = "Hold by Aryadibusiness";
                                                }
                                            ?>
                                                <option value="<?php echo $Status_name_display ?>"> <?php echo $Status_name; ?></option>
                                            <?php 
                                            }  
                                            ?>
                                        </select>
                                    </div>
                                    <?php
                                    if($UserType != "Corporate Admin" && $UserType != "Corporate Branch User")
                                    {
                                    ?>
                                    <div class="eam-filter-item">
                                        <label class="form-label text-muted small font-weight-bold mb-1" for="stateName">State</label>
                                        <select class="form-control" name="stateName" id="stateName">
                                            <option value="">Select State Name</option>
                                            <?php
                                            foreach ($state_array as $state) 
                                            {
                                                $StateName = $state['StateName']
                                            ?>
                                                <option value="<?php echo $StateName ?>"> <?php echo $StateName ?></option>
                                            <?php 
                                            }  
                                            ?>
                                        </select>
                                    </div>
                                    <?php
                                    }
                                    
                                    if($CorporateID == -1)
                                    {
                                    ?>
                                    <div class="eam-filter-item" style="flex-grow: 1.5;">
                                        <label class="form-label text-muted small font-weight-bold mb-1" for="filter_company_id">Corporate Account</label>
                                        <select class="form-control" name="filter_company_id" id="filter_company_id" onchange="ppm_GetBranchesFromCorporateID(this);">
                                            <option value="">Select Corporate Account</option>
                                            <?php
                                            foreach ($company_array as $CompanyID=>$e_company) 
                                            {
                                                $Company_Account_Name = $e_company['CompanyName']
                                            ?>
                                                <option value="<?php echo $CompanyID ?>"> <?php echo $Company_Account_Name; ?></option>
                                            <?php 
                                            }  
                                            ?>
                                        </select>
                                    </div>
                                    <?php
                                    }
                                    if($UserType != "Corporate Branch User")
                                    {
                                    ?>
                                    <div class="eam-filter-item" id="branches_filter_div">
                                        <label class="form-label text-muted small font-weight-bold mb-1" for="branch_name">Branch</label>
                                        <select class="form-control" name="branch_name" id="branch_name">
                                            <option value="">Select Branch</option>
                                            <?php
                                            foreach ($branch_array as $ID=>$branch) 
                                            {
                                                $BranchSite = $branch['BranchName'];
                                            ?>
                                                <option value="<?php echo $ID ?>"> <?php echo $BranchSite; ?></option>
                                            <?php 
                                            }  
                                            ?>
                                        </select>
                                    </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-all-ppm-tickets"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Ticket ID</th>
                                                    <th>Branch Asset </th>
                                                    <th>Corporate</th>
                                                    <th>Branch</th>
                                                    <th>PPM Date</th>
                                                    <th>Status</th>
                                                    <th>View Ticket</th>
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
    <!-- END Page Wrapper -->

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/ppm-tickets-all.js"></script>
    <?php if ($psmvCanAccess) { ?>
    <script src="../js/modules/ppm-state-manager-verification.js?v=20260706"></script>
    <?php } ?>

    <script>
    var psmvCanAccess = <?php echo $psmvCanAccess ? 'true' : 'false'; ?>;

    function getPpmTicketDataTableColumns() {
        var cols = [
            {
                "data": "id",
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'TicketID' },
            { data: 'BranchAsset' },
            { data: 'Corporate' },
            { data: 'Branch' },
            { data: 'PPM_Date' },
            { data: 'Status' },
            { data: 'View_Ticket' },
            { data: 'Action' }
        ];
        return cols;
    }

    $(document).ready(function() 
    {
        var i = 1;
        $('#view-all-ppm-tickets').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'ajax/view-ppm-tickets-post.php<?php echo $filter_param; ?>'
            },
            'columnDefs': [{
                "targets": [0],
                "className": "text-center"
            }],
            'columns': getPpmTicketDataTableColumns()
        });
        $('#filter_date').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });

        if($("#filter_company_id").length)
        {
            $("#filter_company_id").select2();
        }
        if($("#branch_name").length)
        {
            $("#branch_name").select2();
        }
        $("#nav_ppm_tickets").addClass("active");
    });


    </script>

<?php if ($psmvCanAccess) { ?>
<div class="modal fade" id="psmvVerificationModal" tabindex="-1" role="dialog" aria-labelledby="psmvVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header modal_header">
                <h5 class="modal-title" id="psmvVerificationModalLabel">State Manager PPM Ticket Verification</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="psmv_ticket_pk" value="">
                <input type="hidden" id="psmv_is_verified" value="0">
                <p class="mb-3"><strong>Ticket ID:</strong> <span id="psmv_ticket_id_display"></span></p>
                <div id="psmvVerifiedInfo" class="alert alert-success" style="display:none;"></div>
                <form id="psmvChecklistForm" onsubmit="return psmvSubmitVerification();">
                    <p class="text-muted small mb-3">Confirm each item below (Yes only). All items must be checked to verify the ticket.</p>
                    <div id="psmvChecklistContainer"></div>
                    <div id="psmvPoSection" class="border-top pt-3 mt-2">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input psmv-po-available" id="psmv_CustomerPoAvailable" name="CustomerPoAvailable" value="1">
                            <label class="custom-control-label" for="psmv_CustomerPoAvailable">Is PO number available? <strong>(Yes)</strong></label>
                        </div>
                        <div id="psmvPoRemarksWrap" class="form-group mb-0" style="display:none;">
                            <label for="psmv_CustomerPoRemarks">PO number / remarks</label>
                            <textarea class="form-control" id="psmv_CustomerPoRemarks" name="CustomerPoRemarks" rows="2" placeholder="Write PO number and related details..."></textarea>
                        </div>
                    </div>
                    <div class="text-right mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="psmvSubmitBtn" disabled>Verify Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php } ?>

</body>


</html>