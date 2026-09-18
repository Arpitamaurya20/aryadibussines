<?php @session_start(); ?>
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
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Admin Requests - TechXpert
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

    .approval-request-tabs .nav-link {
        border: 1px solid #dee2e6;
        margin-right: 8px;
        border-radius: 4px;
        color: #495057;
    }

    .approval-request-tabs .nav-link.active {
        background: #003f88;
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
	$Ticketdata = getAllPeindingTickets($conn,$CorporateID);
	$QuoteApprovalData = getAllPendingCompanyAdminQuotations($conn,$CorporateID);
    $ticketApprovalCount = is_array($Ticketdata) ? count($Ticketdata) : 0;
    $quoteApprovalCount = is_array($QuoteApprovalData) ? count($QuoteApprovalData) : 0;
    $activeApprovalTab = (isset($_GET['tab']) && $_GET['tab'] == 'quote') ? 'quote' : 'ticket';


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
                        <li class="breadcrumb-item active">Admin Requests </li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Admin Requests</span>
                                    </h2>
                                    <a href="view-approval-rejected-tickets.php" class="btn btn-info"
                                        style="margin-right:20px;">View Cancelled Tickets</a>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <ul class="nav nav-tabs approval-request-tabs mb-3" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo ($activeApprovalTab == 'ticket') ? 'active' : ''; ?>"
                                                    data-toggle="tab" href="#ticket-approval-requests" role="tab">
                                                    Ticket Approvals
                                                    <span class="badge badge-light ml-1"><?php echo $ticketApprovalCount; ?></span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo ($activeApprovalTab == 'quote') ? 'active' : ''; ?>"
                                                    data-toggle="tab" href="#quote-approval-requests" role="tab">
                                                    Quote Approvals
                                                    <span class="badge badge-light ml-1"><?php echo $quoteApprovalCount; ?></span>
                                                </a>
                                            </li>
                                        </ul>

                                        <div class="tab-content">
                                            <div class="tab-pane fade <?php echo ($activeApprovalTab == 'ticket') ? 'show active' : ''; ?>" id="ticket-approval-requests" role="tabpanel">
                                                <table id="view-approval-pending"
                                                    class="table table-bordered table-hover table-striped w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Ticket ID</th>
                                                            <th>Corporate / Branch</th>
                                                            <th>Type</th>
                                                            <th>Branch Asset </th>
                                                            <th>Raised By</th>
                                                            <th>Date / Time</th>
                                                            <th>Status</th>
                                                            <th>Details</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                            $i=1;

                                                            foreach($Ticketdata as $Ticketvalue)
                                                            {
                                                            $id  = $Ticketvalue['ID'];

                                                            $ticketCorporateID = $Ticketvalue['CorporateID'];
                                                            $Corporate = isset($company_array_key[$ticketCorporateID]) ? $company_array_key[$ticketCorporateID]['CompanyName'] : 'N.A.';

                                                            $ticketBranchID = $Ticketvalue['BranchID'];
                                                            $Branch = isset($branch_array_key[$ticketBranchID]) ? $branch_array_key[$ticketBranchID]['BranchSite'] : 'N.A.';
                                                            $BranchAsset = "N.A.";
                                                            if($Ticketvalue['BranchAssetID'] != -1)
                                                            {
                                                                $BranchAssetID = $Ticketvalue['BranchAssetID'];
                                                                $BranchAsset = isset($branch_assets_array_key[$BranchAssetID]) ? $branch_assets_array_key[$BranchAssetID]['EquipmentName'] : 'N.A.';
                                                            }
                                                            ?>
                                                        <tr>
                                                            <td><?php echo $i; ?></td>
                                                            <td><?php echo $Ticketvalue['TicketID']; ?></td>
                                                            <td><?php echo $Corporate."<br>".$Branch; ?></td>
                                                            <td><?php echo $Ticketvalue['Type']; ?></td>
                                                            <td><?php echo $BranchAsset; ?></td>
                                                            <td><?php echo $Ticketvalue['CreatedBy']; ?></td>
                                                            <td>
                                                                <?php echo $Ticketvalue['CreatedDate']; ?> / <br>
                                                                <?php echo $Ticketvalue['CreatedTime']; ?>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-danger cursor-pointer"><?php echo $Ticketvalue['Status']; ?></span>

                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-info cursor-pointer" onclick="OpenTicketDetails(<?php echo $id; ?>)">Open Details</span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                  <span class="btn m-2 ml-0 btn-info cursor-pointer" onclick="ChangeApproval(<?php echo $id; ?>)"><i class="fa fa-check" aria-hidden="true"></i></span>
                                                                 <span class="btn btn-danger cursor-pointer" onclick="RejectTicket(<?php echo $id; ?>)"><i class="fa fa-times" aria-hidden="true"></i></span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                            $i++;
                                                            }
                                                            ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="tab-pane fade <?php echo ($activeApprovalTab == 'quote') ? 'show active' : ''; ?>" id="quote-approval-requests" role="tabpanel">
                                                <table id="view-quote-approval-pending"
                                                    class="table table-bordered table-hover table-striped w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Ticket ID</th>
                                                            <th>Corporate / Branch</th>
                                                            <th>Service</th>
                                                            <th>Raised By</th>
                                                            <th>Date / Time</th>
                                                            <th>Quote Amount</th>
                                                            <th>Status</th>
                                                            <th>Details</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                            $j=1;
                                                            foreach($QuoteApprovalData as $Quotevalue)
                                                            {
                                                            $quoteTicketID = (int)$Quotevalue['CorporateTicketID'];
                                                            ?>
                                                        <tr>
                                                            <td><?php echo $j; ?></td>
                                                            <td><?php echo $Quotevalue['TicketNumber']."<br>".$Quotevalue['ClientTicketID']; ?></td>
                                                            <td><?php echo $Quotevalue['CompanyName']."<br>".$Quotevalue['BranchSite']; ?></td>
                                                            <td><?php echo $Quotevalue['Service']."<br>".$Quotevalue['Subservice']; ?></td>
                                                            <td><?php echo $Quotevalue['CreatedBy']; ?></td>
                                                            <td>
                                                                <?php echo $Quotevalue['CreatedDate']; ?> / <br>
                                                                <?php echo $Quotevalue['CreatedTime']; ?>
                                                            </td>
                                                            <td><?php echo "Rs. ".number_format((float)$Quotevalue['Cost'],2); ?></td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-warning cursor-pointer"><?php echo $Quotevalue['QuotationStatus']; ?></span>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-primary cursor-pointer" onclick="OpenQuoteDetails(<?php echo $quoteTicketID; ?>)">Open Quotation</span>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                            $j++;
                                                            }
                                                            ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
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


     <!-- Modal -->
    <div class="modal fade" id="viewticketdetails" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Ticket Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="raise_amc_tickets">

                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label for="message">Service Type</label>
                                    <br>
                                    <input disabled type="text" class="form-control" name="" id="service_type">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label for="message">Message</label>
                                    <br>
                                    <input disabled type="text" class="form-control" name="" id="message">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/approval-pending.js"></script>

</body>


</html>