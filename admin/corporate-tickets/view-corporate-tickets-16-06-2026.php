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
    <?php

    $UserType = SessionCheck();
    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;
    if($UserType == "Corporate Admin")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $techx_admin = false;
    }
    if($UserType == "Corporate Branch User")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
        $techx_admin = false;
    }
    if($UserType == "Corporate User")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $techx_admin = false;
    }
    $city_lead = "no";
    $Employee_ID = -1;
    $sql_in_string = "";
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "City Corporate Lead")
            {
                $city_lead = "yes";
                if(isset($_SESSION['Roles']['EmployeeID']))
                {
                    $Employee_ID = $_SESSION['Roles']['EmployeeID'];
                    $city = new City($conn);
                    $cities_array_mapped_raw = $city->getMappedCitiesofCityLead($Employee_ID,'Corporate');
                    $cities_array_mapped = array();
                    foreach($cities_array_mapped_raw as $city_mapped)
                    {
                        array_push($cities_array_mapped,$city_mapped['CityName']);
                    }
                    $sql_in_string = "'" . implode("', '", $cities_array_mapped) . "'";
                }
            }
        }
    }
    //print_r($branch_assets_array_key);
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . " - " . $current_date;
    $filter_param = "?filter_date=".$date_range."&CityLead=".$city_lead."&EmployeeID=".$Employee_ID;

    

    $branch_object = new Branch($conn);
    if($city_lead == "yes")
    {
        $branch_array = $branch_object->setBranchesInCityArray('All',$sql_in_string);
    }
    else
    {
        if($UserType == "Corporate Admin")
        {
            $branch_array = $branch_object->setBranchArrayByCorporateID($CorporateID,'All');
        }
        else
        {
            $branch_array = $branch_object->setBranchArray('All');
        }
    }
    $state_object = new State($conn);
    if($city_lead == "yes")
    {
        $state_array = $state_object->getStateArrayInCityArray($sql_in_string);
    }
    else
    {
        $state_array = $state_object->setStateArray('Active');
    }
    $corporateticket = new Corporateticket($conn);
    $status_array = $corporateticket->getCorporateTicketStatusArray($conn);

    $company_object = new Company($conn);
    $company_array = $company_object->setCompanyArray('All');

    $TicketManager = false;
    if(CheckRole($_SESSION,"Ticket Manager") == true )
    {
        $TicketManager = true;
    }

    $StateManager = false;
    if(CheckRole($_SESSION,"State Corporate Lead") == true )
    {
        $StateManager = true;
    }
    if(isset($_SESSION['Roles']['EmployeeID']))
    {
        $Employee_ID = $_SESSION['Roles']['EmployeeID'];
    }
    if($StateManager)
    {
        $state_array = $state_object->getStatesMapped_StateLead($Employee_ID);
        $state_array_mapped = array();
        foreach($state_array as $state_mapped)
        {
            array_push($state_array_mapped,$state_mapped['ID']);
        }
        $sql_in_state_string = "'" . implode("', '", $state_array_mapped) . "'";
    }

    // Get Cities Name for Filters
    $city_array = array();
    if($city_lead == "yes")
    {
        $where = " where StateName IN (".$sql_in_string.") ORDER BY CityName ASC";
        $city_array_raw = _getTableRecords($conn,'citydata',$where);
    }
    else if($StateManager)
    {
        $where = " where StateName IN (".$sql_in_state_string.") ORDER BY CityName ASC";
        $city_array_raw = _getTableRecords($conn,'citydata',$where);
    }
    else
    {
        $city_array_raw = _getTableRecords($conn,'citydata',' where 1 ORDER BY CityName ASC');
    }

    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) 
    {
        $_product = "innov";
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo']))
    {
        $logoImg = "innov_logo.png";
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
    
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<?php
$categories_obj = new Categories($conn);
$categories = $categories_obj->getAllCategories();


?>

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
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">Corporate Tickets</li>

                    </ol>
                    <?php 
                    if($UserType == "Admin" || $TicketManager)
                    {
                    ?>
                    <!-- <a href="#" onclick="DownloadCorporateTicketsFileFormat()" class="btn btn-success" style="margin-right:20px;">Download Template for Bulk Upload</a>   -->

                     <div class="d-flex gap-3 mt-3">

                       <a href="./auto-dashboard-setting" class="btn btn-primary">
                            <i class="fas fa-cog me-2"></i>
                            Auto Setting
                        </a>


                        <a style="margin-left: 10px;"href="#" onclick="DownloadCorporateTicketsFileFormat()" 
                           class="btn btn-success">
                           Download Template for Bulk Upload
                        </a>

                        

                    </div>

                    <?php 
                    }
                    ?>   
                    </div>               

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Corporate Tickets</span>
                                    </h2>
                                    <?php 
                                    if($CorporateID == -1)
                                    {
                                    ?>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="finance_not_placed">
                                        <label class="custom-control-label" for="finance_not_placed">Cost not Placed</label>
                                    </div>
                                    <?php
                                    }
                                    ?>
                                    <button type="button" onclick="FilterTickets('<?php echo $UserType; ?>');" class="btn btn-sm btn-primary ml-3">Search</button>
                                    <form id="export_form">
                                      
                                        <button type="button" onclick="ExportCorporateTicketData();" class="btn btn-sm btn-info ml-3" id="export_button">Export Data</button>
                                        <?php 
                                        {
                                            if($UserType == "Admin" || $TicketManager)
                                            {
                                            ?>
                                            <a href="#" onclick="OpenTicketImportmodal()" class="btn btn-sm btn-info ml-3 mr-2">Upload CSV File</a>
                                            <?php
                                            }
                                        }
                                        ?>
                                        
                                       <input type="hidden" name="CorporateID" value="<?php echo $CorporateID ?>">
                                       <input type="hidden" name="BranchID" value="<?php echo $BranchID ?>">
                                       <input type="hidden" name="filter_date_export" id="filter_date_export" value=""> 
                                       <input type="hidden" name="city_export" id="city_export" value=""> 
                                       <input type="hidden" name="branch_export" id="branch_export" value="">
                                       <input type="hidden" name="state_export" id="state_export" value=""> 
                                       <input type="hidden" name="status_export" id="status_export" value=""> 
                                       <input type="hidden" name="company_account_export" id="company_account_export" value="">
                                       <input type="hidden" name="EmployeeID" id="EmployeeID" value="<?php echo $Employee_ID; ?>">
                                       <input type="hidden" name="export_finance_not_placed" id="export_finance_not_placed" value="0">
                                    </form>
                                    
                                </div>

                                <!-- Filters -->
                                <div class="row">
                                    <div class="col-xl-12">
                                        
                                        <div class="panel-hdr">

                                            <div class="col-2 ">
                                                <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">
                                            </div>
                                            <?php
                                            if($UserType != "Corporate Admin" && $UserType != "Corporate Branch User")
                                            {
                                            ?>
                                            <div class="col-2">
                                                <input type="hidden" id="city_in_sql" value="<?php echo $sql_in_string; ?>" />
                                                <select class="form-control" name="stateName" id="stateName" onchange="GetCitiesfromState(this);">
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
                                            if($UserType != "Corporate Admin" && $UserType != "Corporate Branch User")
                                            {
                                            ?>
                                            <div class="col-2" id="branches_cities_div">
                                                <select class="form-control" name="cityName" id="cityName">
                                                    <option value="">Select City Name</option>
                                                    <?php
                                                    foreach ($city_array_raw as $city) 
                                                    {
                                                        $CityName = $city['CityName']
                                                    ?>

                                                        <option value="<?php echo $CityName ?>"> <?php echo $CityName ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                            <?php
                                            }
                                            ?>
                                            <div class="col-2">
                                                <select class="form-control" name="ticket_status" id="ticket_status">
                                                    <option value="">Select Status</option>
                                                    <?php
                                                    foreach ($status_array as $e_status) 
                                                    {

                                                        $Status_name = $e_status['Status'];
                                                        $Status_name_display = $Status_name;
                                                        if($Status_name_display == "Hold by Aryadibusiness" && $CorporateID == 183)
                                                        {
                                                            $Status_name_display = "Hold by Aryadibusiness";
                                                        }
                                                    ?>

                                                        <option value="<?php echo $Status_name ?>"> <?php echo $Status_name_display; ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <?php
                                            if($UserType != "Corporate Admin" && $UserType != "Corporate Branch User")
                                            {
                                            ?>

                                            <div class="col-2">
                                                <select class="form-control" name="filter_company_id" id="filter_company_id" onchange="GetBranchesFromCorporateID(this);">
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

                                            <div class="col-2" id="branches_filter_div">
                                                <select class="form-control" name="branch_name" id="branch_name">
                                                    <option value="">Select Branch</option>
                                                    <?php
                                                    foreach ($branch_array as $branch) 
                                                    {

                                                        $BranchSite = $branch['BranchName']
                                                    ?>

                                                        <option value="<?php echo $BranchSite ?>"> <?php echo $BranchSite; ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                            <?php
                                            }
                                            ?>


                                        </div>

                                    </div> <!-- col-xl-12 -->
                                </div> <!-- row -->
                                <div class="row">
                                     <div class="col-xl-12 mx-1 mt-2">
                                  <div class="col-2" id="branches_filter_div">
                                    <select class="form-control" name="categories" id="categories">
                                        <option value="">Select Category</option>
                                        <?php
                                        foreach ($categories as $cat) 
                                        {

                                            $CategoriesName = $cat['CategoriesName'];
                                        ?>

                                            <option value="<?php echo $CategoriesName ?>"> <?php echo $CategoriesName; ?></option>

                                        <?php 
                                        }  
                                        ?>
                                    </select>
                                </div>
                                </div>
                            </div>
                                <!-- Filters -->

                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-corporate-tickets"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Ticket ID</th>
                                                    <th>Client Ticket</th>
                                                    <th>Corporate / Branch</th>
                                                    <th>Type</th>
                                                    <th>Message</th>
                                                    <th>Date / Time</th>
                                                    <th>Status</th>
                                                    <th>View Ticket</th>
                                                    <?php 
                                                    if($UserType == "Admin" || $UserType == "Ticket Manager")
                                                    {
                                                    ?>
                                                        <th>Delete</th>
                                                    <?php 
                                                    }
                                                    ?>

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


                    <!-- Modal -->
                    <!-- <div class="modal fade" id="addstatus" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="exampleModalLabel">Add Booking </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_booking_status">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Booking Status</label>
                                            <input type="text" class="form-control" name="status_name"
                                                placeholder="Enter Booking Status">

                                        </div>

                                        <button type="submit" class="btn btn-primary"
                                            onclick="return AddBookingStatus()">Submit</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div> -->

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

    <script>
    function ViewBookingDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                window.open("view-corporate-tickets-details.php",'_blank');
            }
        );
    }
     
    $(document).ready(function() {
        /*$("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_employees").addClass("active");*/
    });
    $(document).ready(function() 
    {
        var i = 1;
        $('#view-corporate-tickets').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'ajax/view-corporate-tickets-post.php<?php echo $filter_param; ?>'
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
                    data: 'TicketID'
                },
                {
                    data: 'ClientTicketID'
                },
                {
                    data: 'Corporate_Branch'
                },
                {
                    data: 'Type'
                },
                {
                    data: 'Message'
                },
                {
                    data: 'Date_Time'
                },
                {
                    data: 'Status'
                },
                {
                    data: 'View_Details'
                }
                <?php 
                if($UserType == "Admin" || $UserType == "Ticket Manager")
                {
                ?>
                ,
                {
                    data: 'Delete'
                }
                <?php 
                }
                ?>

            ]


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
        if($("#cityName").length)
        {
            $("#cityName").select2();
        }
        if($("#stateName").length)
        {
            $("#stateName").select2();
        }
        $("#nav_corporate_tickets").addClass("active");
    });
    </script>

</body>


</html>



<!-- upload csv modal             -->

<div class="modal fade" id="upload_import_tickets_csv" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header pb-0 edit_header">
                <div class="tab_modal_heading">
                    <h2>Upload Corporate Tickets CSV </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="reset_password">
                   <form id="uplaod_corporate_tickets_csv">
                        <input type="file" class="form-control" name="csvFile" accept=".csv">
                        

                        <div class="row justify-content-center mt-3">

                            <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="upload_csv_btn"
                                onclick="UploadCorporateTickets_CSV()">Upload</a>

                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div> 