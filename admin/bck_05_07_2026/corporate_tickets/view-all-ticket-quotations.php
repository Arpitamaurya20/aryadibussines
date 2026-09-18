<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
        require_once('../includes/autoloader.inc.php');
        $UserType = SessionCheck();
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
        $core = new Core();
		?>
    <meta charset="utf-8">
   
    <meta name="description" content="View Quotations">
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
    .select2-container--readonly .select2-selection {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    </style>
    <?php
    $color_qsap = "#FFA500";
    $color_qr = "#DC3545";
    $color_qa = "#28A745";
    $corporate_user = false;
    $corporate_account_admin = false;
    $CorporateID = -1;
    $BranchID = -1;
    $CompanyID = -1;
    $TicketManager = false;
    if($UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $corporate_account_admin = true;
        $CorporateID = $CompanyID = $_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = $CompanyID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "Ticket Manager")
            {
                $TicketManager = true;
                $UserType = "Ticket Manager";
            }
        }
    }

    if(!$corporate_user)
    {
        $CompanyID = -1;
    }
    if(isset($_SESSION['CompanyID']))
    {
        $CorporateID = $CompanyID = $_SESSION['CompanyID'];
    }
    if($UserType == "Admin" || $UserType == "Ticket Manager")
    {
        $CorporateID = $CompanyID = -1;
    }
    $filter_param = "?CompanyID=".$CompanyID."&UserType=".$UserType;
    $ProductName = "TechXpert";
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
        Manage Quotations - <?=$ProductName;?>
    </title>
    <?php 
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "TechXpert")
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
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">Manage Quotations</li>

                    </ol>   
                        <?php
                            if($UserType == "Admin"||$UserType == "Sub Admin"){
                        ?>
                        <!--a href="#" onclick="DownloadBranchFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a-->
                        <?php } ?>
                    </div>

                                
                    <div class="row" id="status_buttons_div">
                                    
                    </div>
                    
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Quotations
                                    </h2>
                                    <!--button type="button" onclick="FilterQuotations();" class="btn btn-sm btn-primary ml-3 mr-3">Search</button-->
                                    <!--a href="#" onclick="OpenCSVmodal()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV File</a-->
                                </div>

                                            <!-- Status buttons -->
                                
                               
                                <div class="row">
                                    <div class="col-xl-12">
                                        
                                        <div class="panel-hdr">
                                            <div class="col-4" style="width:100%;z-index: 1!important;">
                                                <select class="form-control" name="filter_quotation_status" id="filter_quotation_status">
                                                    <option value="">Select Status</option>
                                                    <option value="Quote Sent Approval Pending">Quote Sent Approval Pending</option>
                                                    <option value="Quote Rejected by Client">Quote Rejected by Client</option>
                                                    <option value="Quote Approved">Quote Approved</option>
                                                </select>
                                            </div>
                                            <div class="col-2">
                                                 <button type="button" onclick="FilterQuotations();" class="btn btn-sm btn-primary ml-3 mr-3">Search</button>
                                            </div>
                                            <div class="col-6">
                                                
                                            </div>
                                            
                                            
                                            <input type="hidden" id="CorporateID" value="<?php echo $CorporateID; ?>" />
                                            <input type="hidden" id="UserType" value="<?php echo $UserType; ?>" />
                                            
                                        </div>

                                    </div>
                                </div> 
                                <form id="export_form">
                                    <input type="hidden" name="CorporateID" id="CorporateID_filter" value="<?php echo $CorporateID; ?>" />
                                    <input type="hidden" name="UserType" value="<?php echo $UserType; ?>" />
                                </form>
               
                                
                                 <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-all-quotations" 
                                        class="table table-bordered table-hover table-striped w-100">
                                            
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Ticket Number</th>
                                                    <th>Branch Site</th>
                                                    <th>Category / Sub Category </th>
                                                    <th>Created Date / Time</th>
                                                    <th>Status</th>
                                                    <th>Cost</th>
                                                    <th>View</th>
                                                </tr>
                                            </thead>

                                        </table>
                                        
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div>
                            <!-- panel-1 -->
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
    <script src="../js/modules/corporate-booking.js"></script>
    <script src="../js/modules/view-quotations.js"></script>
       <script>
        $(document).ready(function() {
            var i = 1;
            $('#view-all-quotations').dataTable({
                 responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/view-all-quotations-post.php<?=$filter_param;?>'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                "order": [
                    [1, 'asc']
                ],
                'columns': [{
                        "data": "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'TicketNumber'
                    },
                   
                    {
                        data: 'BranchSite'
                    },
                    {
                        data: 'Category_SubCategory'
                    },
                    {
                        data: 'CreatedDate_Time'
                    },
                    {
                        data: 'Status'
                    },
                    {
                        data: 'Cost'
                    },
                    {
                        data: 'View'
                    }
                ]


            });
            $("#nav_finance").addClass("active");
            $("#nav_finance").addClass("open");
            $("#nav_ticket_quotations").addClass("active");
            var CorporateID = $("#CorporateID_filter").val();
            GenerateQuotationsDashboard_Analytics(CorporateID);
        });
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
        function FilterQuotations()
        {
            var table = $('#view-all-quotations').DataTable();
            table.destroy();
            var param = "";
            var filter_quotation_status_object = document.getElementById("filter_quotation_status");
            if(filter_quotation_status_object !== null)
            {
                var filter_quotation_status = document.getElementById("filter_quotation_status").value;
                param = param+"&quotation_status="+filter_quotation_status;
            }

            
            var columns = [{
                        "data": "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'TicketNumber'
                    },
                    {
                        data: 'BranchSite'
                    },
                    {
                        data: 'Category_SubCategory'
                    },
                    {
                        data: 'CreatedDate_Time'
                    },
                    {
                        data: 'Status'
                    },
                    {
                        data: 'Cost'
                    },
                    {
                        data: 'View'
                    }
                ];
 

            $('#view-all-quotations').dataTable({
                 responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/view-all-quotations-post.php<?=$filter_param;?>'+param
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                "order": [
                    [1, 'asc']
                ],
                'columns': columns


            });
        }
     </script>
</body>


</html>

