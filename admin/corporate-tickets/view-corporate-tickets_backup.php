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
        $UserType = SessionCheck();
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
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
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
	$corporate_tickets_array = getAllCorporateTickets($conn,$CorporateID,$BranchID);

    $corporate_array = getAllCompanies($conn);
    $company_array_key = generateArraywithKey($corporate_array);

    //print_r($company_array_key);

    $branches = getAllBranches($conn,-1);
    $branch_array_key = generateArraywithKey($branches);

    $branch_assets = getAllBranchAssets($conn,-1);
    $branch_assets_array_key = generateArraywithKey($branch_assets);
    //print_r($branch_assets_array_key);
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
                                    <form id="export_form">
                                      <a href="#" onclick="ExportCorporateTicketData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>  
                                       <input type="hidden" name="CorporateID" value="<?php echo $CorporateID ?>">
                                       <input type="hidden" name="BranchID" value="<?php echo $BranchID ?>">  
                                    </form>
                                    
                                </div>
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
                                                    <th>Type</th>
                                                    <th>Branch Asset </th>
                                                    <th>Message</th>
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
                                                <?php
                                                	$i=1;

                                                	foreach($corporate_tickets_array as $corporate_tickets)
                                                	{
                                                    $id  = $corporate_tickets['ID'];

                                                    $CorporateID = $corporate_tickets['CorporateID'];
                                                    if(isset($company_array_key[$CorporateID]))
                                                    {
                                                        $Corporate = $company_array_key[$CorporateID]['CompanyName'];
                                                    }
                                                    else
                                                    {
                                                        continue;
                                                    }

                                                    $BranchID = $corporate_tickets['BranchID'];
                                                    if(isset($branch_array_key[$BranchID]['BranchSite']))
                                                    {
                                                	   $Branch = $branch_array_key[$BranchID]['BranchSite'];
                                                    }
                                                    else
                                                    {
                                                        continue;
                                                    }
                                                    $BranchAsset = "N.A.";
                                                    if($corporate_tickets['BranchAssetID'] != -1)
                                                    {
                                                        $BranchAssetID = $corporate_tickets['BranchAssetID'];
                                                        $BranchAsset = $branch_assets_array_key[$BranchAssetID]['EquipmentName'];
                                                    }
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $corporate_tickets['TicketID']; ?></td>
                                                    <td><?php echo $Corporate."<br>".$Branch; ?></td>
                                                    <td><?php echo $corporate_tickets['Type']; ?></td>
                                                    <td><?php echo $BranchAsset; ?></td>
                                                    <td><?php echo $corporate_tickets['Message']; ?></td>
                                                    <td>
                                                        <?php echo $corporate_tickets['CreatedDate']; ?> / <br>
                                                        <?php echo $corporate_tickets['CreatedTime']; ?>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-danger cursor-pointer"><?php echo $corporate_tickets['Status']; ?></span>

                                                    </td>
                                                    <td>
                                                        <a onclick="ViewBookingDetails(<?php echo $id; ?>)">
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
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/corporate-tickets.js"></script>

    <script>
    function ViewBookingDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                BasicURLRouter("view-corporate-tickets-details.php");
            }
        );
    }
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
                    data: 'Corporate_Branch'
                },
                {
                    data: 'Type'
                },
                {
                    data: 'BranchAsset'
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
                    data: 'View Details'
                }
            ]


        });
    });



    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_employees").addClass("active");
    });
    </script>

</body>


</html>