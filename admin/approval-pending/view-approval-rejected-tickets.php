<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/approval_pending_controller.php');
        include('../company/controller/company_controller.php');
        include('../branch/controller/branch_controller.php');
        include('../branch-assets/controller/branch_assets_controller.php');
        $UserType = SessionCheck();
        $sessionRoles = isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) ? $_SESSION['Roles'] : array();
        if(!isset($sessionRoles['EmployeeRoles']) || !is_array($sessionRoles['EmployeeRoles']))
        {
            $sessionRoles['EmployeeRoles'] = array($UserType);
        }
        setNavigation($sessionRoles);
		$conn = _connectodb();
		?>
    <meta charset="utf-8">
    <title>
        Approval & Pending - TechXpert
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
<?php

	$UserType = SessionCheck();

	$APPA = false;
	if($UserType == "")
	{
		$APPA = true;
	}

    $CorporateID = -1;
    if($UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = isset($sessionRoles['CorporateID']) ? $sessionRoles['CorporateID'] : ($_SESSION['CorporateID'] ?? -1);
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = isset($sessionRoles['CorporateID']) ? $sessionRoles['CorporateID'] : ($_SESSION['CorporateID'] ?? -1);
        $BranchID = isset($sessionRoles['BranchID']) ? $sessionRoles['BranchID'] : ($_SESSION['BranchID'] ?? -1);
    }
	$Ticketdata = getAllRejectedTickets($conn,$CorporateID);


    $corporate_array = getAllCompanies($conn);
    $company_array_key = generateArraywithKey($corporate_array);

    //print_r($company_array_key);

    $branches = getAllBranches($conn,-1);
    $branch_array_key = generateArraywithKey($branches);

    $BranchAssetArray = getAllBranchAssetsList($conn,-1);
    $branch_assets_array_key = generateArraywithKey($BranchAssetArray);
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">Cancelled Tickets </li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Cancelled Tickets</span>
                                    </h2>
                                    <a href="view-approval-pending-tickets.php" class="btn btn-info"
                                        style="margin-right:20px;">Back</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-approval-pending"
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $i=1;
                                                    echo "<pre>";
                                                    foreach($Ticketdata as $Ticketvalue)
                                                    {
                                                    $id  = $Ticketvalue['ID'];
                                                    $CorporateID = $Ticketvalue['CorporateID'];
                                                    $Corporate = $company_array_key[$CorporateID]['CompanyName'];

                                                    $BranchID = $Ticketvalue['BranchID'];
                                                    $Branch = $branch_array_key[$BranchID]['BranchSite'];
                                                    $BranchAsset = "N.A.";
                                                    if($Ticketvalue['BranchAssetID'] != -1)
                                                    {
                                                        $BranchAssetID = $Ticketvalue['BranchAssetID'];
                                                        $BranchAsset = $branch_assets_array_key[$BranchAssetID]['EquipmentName'];
                                                    }
                                                        
                                                    ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $Ticketvalue['TicketID']; ?></td>
                                                    <td><?php echo $Corporate."<br>".$Branch; ?></td>
                                                    <td><?php echo $Ticketvalue['Type']; ?></td>
                                                    <td><?php echo $BranchAsset; ?></td>
                                                    <td><?php echo $Ticketvalue['Message']; ?></td>
                                                    <td>
                                                        <?php echo $Ticketvalue['CreatedDate']; ?> / <br>
                                                        <?php echo $Ticketvalue['CreatedTime']; ?>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-danger cursor-pointer"><?php echo $Ticketvalue['Status']; ?></span>

                                                    </td>
                                                    
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
    <script src="../js/modules/approval-pending.js"></script>

</body>


</html>