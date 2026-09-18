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
    
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    

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
    .filter_label
    {
        font-size: 0.8em;
    }
    .panel-hdr
    {
        padding-bottom: 0.5em;
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
    $filter_param = "?filter_date=".$date_range."&CityLead=".$city_lead."&EmployeeID=".$Employee_ID."&techx_admin=".$techx_admin;

    // Get Cities Name for Filters
    $city_array = array();
    if($city_lead == "yes")
    {
        $where = " where CityName IN (".$sql_in_string.") ORDER BY CityName ASC";
        $city_array_raw = _getTableRecords($conn,'citydata',$where);
    }
    else
    {
        $city_array_raw = _getTableRecords($conn,'citydata',' where 1 ORDER BY CityName ASC');
    }

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
    $status_array = $corporateticket->getCorporateTicketFinanceStatusArray($conn);

    $company_object = new Company($conn);
    $company_array = $company_object->setCompanyArray('All');

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
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <title>
        Corporate Tickets - Finance
    </title>
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
                        <li class="breadcrumb-item active">Corporate Tickets</li>

                    </ol>

                    <!-- Status buttons -->
                    <div class="row" id="status_buttons_div">
                        
                    </div>

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <span>Corporate Tickets</span>
                                    </h2>
                                    <button type="button" onclick="FilterFinanceTickets();" class="btn btn-sm btn-primary ml-3">Search</button>
                                    <form id="export_form">
                                      
                                        <button type="button"  class="btn btn-sm btn-info dropdown-toggle ml-3" data-toggle="dropdown" id="export_data_button"> Export Data</button>
                                        <div class="dropdown-menu" style="">
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="ExportCorporateTicketsFinanceData();">Ticket Aggregated</a>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="ExportFinanceDataByQuotation();">Quotation Aggregated</a>
                                            <!-- <a class="dropdown-item" href="javascript:void(0)" onclick="ExportFinanceDataByQuotationTicket_V2();"> New </a> -->

                                        </div>
                                       <input type="hidden" name="CorporateID" value="<?php echo $CorporateID ?>">
                                       <input type="hidden" name="BranchID" value="<?php echo $BranchID ?>">
                                       <input type="hidden" name="filter_date_export" id="filter_date_export" value=""> 
                                       <input type="hidden" name="city_export" id="city_export" value="">
                                       <input type="hidden" name="branch_export" id="branch_export" value="">
                                       <input type="hidden" name="state_export" id="state_export" value=""> 
                                       <input type="hidden" name="status_export" id="status_export" value=""> 
                                       <input type="hidden" name="company_account_export" id="company_account_export" value="">
                                       <input type="hidden" name="EmployeeID" id="EmployeeID" value="<?php echo $Employee_ID; ?>">
                                       <input type="hidden" name="epoch_time" id="epoch_time" value="<?php echo time();?>">
                                       <input type="hidden" name="techx_admin" id="techx_admin" value="<?php echo $techx_admin;?>">
                                    </form>
                                    
                                </div> <!-- panel-hdr -->

                                <!-- Filters -->
                                <div class="row">
                                    <div class="col-xl-12">
                                        

                                            <div class="row p-3">

                                                <div class="col-2 ">
                                                    <label class="form-label filter_label">Raised Date</label>
                                                    <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">
                                                </div>
                                                <!--div class="col-2 ">
                                                    <label class="form-label filter_label">Closed Date</label>
                                                    <input type="text" class="form-control" id="filter_close_date" placeholder="Select date" value="<?php echo $date_range; ?>">
                                                </div-->
                                                <?php
                                                if($UserType != "Corporate Admin" && $UserType != "Corporate Branch User")
                                                {
                                                ?>
                                                <div class="col-2">
                                                    <input type="hidden" id="city_in_sql" value="<?php echo $sql_in_string; ?>" />
                                                    <label class="form-label filter_label">State</label>
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
                                                    <label class="form-label filter_label">City</label>
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
                                                if($techx_admin)
                                                {
                                                ?>
                                                <div class="col-2">
                                                     <label class="form-label filter_label">Account Status</label>
                                                    <select class="form-control" name="ticket_status" id="ticket_status">
                                                       
                                                        <option value="">Select Status</option>
                                                        <?php
                                                        foreach ($status_array as $key=>$e_status) 
                                                        {

                                                            $Status_name = $e_status;
                                                        ?>

                                                            <option value="<?php echo $key ?>"> <?php echo $Status_name; ?></option>

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

                                                <div class="col-2">
                                                    <label class="form-label filter_label">Corporate Account</label>
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
                                                    <label class="form-label filter_label">Branch</label>
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

                                            </div> <!-- row-->
                                       

                                    </div> <!-- col-xl-12 -->
                                </div> <!-- row -->
                                <!-- Filters -->

                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-corporate-tickets-finance"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Ticket ID</th>
                                                    <th>Corporate / Branch</th>
                                                    <th>Branch City</th>
                                                    <th>Type</th>
                                                    <th>Message</th>
                                                    <th>Date / Time</th>
                                                    <th>Status</th>
                                                    <?php 
                                                    if($techx_admin)
                                                    {
                                                        ?>
                                                        <th>Finance Status</th>
                                                        <?php
                                                    }
                                                    ?>
                                                    
                                                    <th>Price Summary</th>
                                                    <th>View Ticket</th>
                                                    
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
    <script src="../js/statistics/chartjs/chartjs.bundle.js"></script>
    <script>
    function ViewBookingDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                window.open("view-corporate-tickets-details.php?nav=finance",'_blank');
            }
        );
    }
     
    $(document).ready(function() {
        $("#nav_finance").addClass("active");
        $("#nav_finance").addClass("open");
        $("#nav_corporate_tickets_finance").addClass("active");
        var techx_admin = $("#techx_admin").val();
        if(techx_admin == true)
        {
            GenerateTicketFinanceDashboard();
        }
    });
    $(document).ready(function() 
    {
        var techx_admin = $("#techx_admin").val();
        console.log(techx_admin);
        var i = 1;
        var columns = [{
        "data": "id",
        render: function(data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
        }
        },
        {
            data: 'TicketID'
        },
        {
            data: 'Corporate_Branch'
        },
        {
            data: 'Branch_City'
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
            data: 'Prices'
        },
        {
            data: 'View_Details'
        }
    ];

    // Conditionally add the FinanceStatus column if techx_admin is true
    if (techx_admin) {
        columns.splice(8, 0, { // Insert FinanceStatus at the 9th position
            data: 'FinanceStatus'
        });
    }

    $('#view-corporate-tickets-finance').dataTable({
        responsive: true,
        'processing': true,
        'serverSide': true,
        'ordering': false,
        'serverMethod': 'post',
        'ajax': {
            'url': 'ajax/view-corporate-tickets-finance-post.php<?php echo $filter_param; ?>'
        },
        'columnDefs': [{
            "targets": [0],
            "className": "text-center"
        }],
        'columns': columns
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

        if($("#ticket_status").length)
        {
            $("#ticket_status").select2();
        }
    });

    function barChart()
    {
        $.post(
            "ajax/generate_top_states_revenue.php", {
                
            },
            function(data, status) {


                    var data_json = JSON.parse(data);

                    var barChartData = {
                    labels: data_json.states,
                    datasets: [
                    {
                        label: "Aryadibusiness Cost",
                        backgroundColor: '#2196F3',
                        borderColor: '#2196F3',
                        borderWidth: 1,
                        data: data_json.states_aryadibusiness_cost
                    },
                    {
                        label: "Customer Cost",
                        backgroundColor: '#1dc9b7',
                        borderColor: '#1dc9b7',
                        borderWidth: 1,
                        data: data_json.states_customer_cost
                    }]

                };
                var config = {
                    type: 'bar',
                    data: barChartData,
                    options:
                    {
                        responsive: true,
                        legend:
                        {
                            position: 'top',
                        },
                        title:
                        {
                            display: false,
                            text: 'Bar Chart'
                        },
                        scales:
                        {
                            xAxes: [
                            {
                                display: true,
                                scaleLabel:
                                {
                                    display: false,
                                    labelString: '6 months forecast'
                                },
                                gridLines:
                                {
                                    display: true,
                                    color: "#f2f2f2"
                                },
                                ticks:
                                {
                                    beginAtZero: true,
                                    fontSize: 11
                                }
                            }],
                            yAxes: [
                            {
                                display: true,
                                scaleLabel:
                                {
                                    display: false,
                                    labelString: 'Profit margin (approx)'
                                },
                                gridLines:
                                {
                                    display: true,
                                    color: "#f2f2f2"
                                },
                                ticks:
                                {
                                    beginAtZero: true,
                                    fontSize: 11
                                }
                            }]
                        }
                    }
                }
                new Chart($("#barChart > canvas").get(0).getContext("2d"), config);
                
            }
        );
        
    }

    function ExportFinanceDataByQuotationTicket_V2() {
        // alert("Hello");

    // copy filters
    $("#filter_date_export").val($("#filter_date").val() || "");
    $("#city_export").val($("#cityName").val() || "");
    $("#branch_export").val($("#branch_name").val() || "");
    $("#state_export").val($("#stateName").val() || "");
    $("#status_export").val($("#ticket_status").val() || "");
    $("#company_account_export").val($("#filter_company_id").val() || "");

    $("#export_data_button").html("Exporting...");

    $.ajax({
        url: "action/export_corporate_tickets_finance_by_quotation_ticket.php",
        type: "POST",
        data: $("#export_form").serialize(),
        success: function (data) {
          var epoch_time = document.getElementById("epoch_time").value;
          window.location.href = "/admin/corporate-tickets/report_finance"+epoch_time+".xls";
          document.getElementById("export_data_button").innerHTML = "Export Data";
      },
        error: function () {
            alert("Export failed");
            $("#export_data_button").html("Export Data");
        }
    });
}

    </script>




</body>


</html>

