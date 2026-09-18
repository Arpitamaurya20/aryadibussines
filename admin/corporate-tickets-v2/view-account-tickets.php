<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
		include('../controllers/common_controllers.php');
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
    </style>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php

	$UserType = SessionCheck();
    $account_manager = "no";
    $Employee_ID = -1;
    $sql_in_string = "";
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "Account Manager")
            {
                $account_manager = "yes";
                if(isset($_SESSION['Roles']['EmployeeID']))
                {
                    $Employee_ID = $_SESSION['Roles']['EmployeeID'];
                }
            }
        }
    }

    $company = new Company($conn);
    $company_array_mapped_raw = $company->getMappedAccountsofAccountManager($Employee_ID);
    $company_array_mapped = array();
    foreach($company_array_mapped_raw as $company_mapped)
    {
        array_push($company_array_mapped,$company_mapped['ID']);
    }
    $sql_in_account = "'" . implode("', '", $company_array_mapped) . "'";

    //print_r($branch_assets_array_key);
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . " - " . $current_date;
    $filter_param = "?filter_date=".$date_range."&EmployeeID=".$Employee_ID;

    $corporateticket = new Corporateticket($conn);
    $status_array = $corporateticket->getCorporateTicketStatusArray($conn);

    // Get Company Branches for Filters
    $branch_object = new Branch($conn);
    $branch_array = $branch_object->setBranchArrayforAccountManager($sql_in_account);

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
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Corporate Tickets</li>

                    </ol>

                    

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Corporate Tickets</span>
                                    </h2>
                                    <button type="button" onclick="FilterAccountTickets();" class="btn btn-sm btn-primary ml-3">Search</button>
                                    <form id="export_form">
                                      
                                        <button type="button" onclick="ExportAccountCorporateTicketData();" class="btn btn-sm btn-info ml-3">Export Data</button>
                                       <input type="hidden" name="filter_date_export" id="filter_date_export" value=""> 
                                       <input type="hidden" name="branch_export" id="branch_export" value="">
                                       <input type="hidden" name="status_export" id="status_export" value=""> 
                                       <input type="hidden" name="company_account_export" id="company_account_export" value="">
                                       <input type="hidden" name="EmployeeID" id="EmployeeID" value="<?php echo $Employee_ID; ?>">
                                    </form>
                                    
                                </div>

                                <!-- Filters -->
                                <div class="row">
                                    <div class="col-xl-12">
                                        
                                        <div class="panel-hdr">

                                            <div class="col-2 ">
                                                <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">
                                            </div>
                                            
                                            
                                            
                                           
                                            <div class="col-2">
                                                <select class="form-control" name="ticket_status" id="ticket_status">
                                                    <option value="">Select Status</option>
                                                    <?php
                                                    foreach ($status_array as $e_status) 
                                                    {

                                                        $Status_name = $e_status['Status']
                                                    ?>

                                                        <option value="<?php echo $Status_name ?>"> <?php echo $Status_name; ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-2">
                                                <select class="form-control" name="filter_company_id" id="filter_company_id" onchange="GetBranchesFromCorporateID(this);">
                                                    <option value="">Select Corporate Account</option>
                                                    <?php
                                                    foreach ($company_array_mapped_raw as $e_company) 
                                                    {
                                                        $ID = $e_company['ID'];
                                                        $Company_Account_Name = $e_company['CompanyName']
                                                    ?>

                                                        <option value="<?php echo $ID ?>"> <?php echo $Company_Account_Name; ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                           

                                            <div class="col-2" id="branches_filter_div">
                                                <select class="form-control" name="branch_name" id="branch_name">
                                                    <option value="">Select Branch</option>
                                                    <?php
                                                    foreach ($branch_array as $branch) 
                                                    {

                                                        $BranchSite = $branch['BranchSite']
                                                    ?>

                                                        <option value="<?php echo $BranchSite ?>"> <?php echo $BranchSite; ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                           

                                        </div>

                                    </div> <!-- col-xl-12 -->
                                </div> <!-- row -->
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
                                                    <th>Corporate / Branch</th>
                                                    <th>Branch City</th>
                                                    <th>Type</th>
                                                    <th>Message</th>
                                                    <th>Date / Time</th>
                                                    <th>Status</th>
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
                'url': 'ajax/view-account-tickets-post.php<?php echo $filter_param; ?>'
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
                    data: 'View_Details'
                }
            ]


        });

        $('#filter_date').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
    });
    </script>

</body>


</html>