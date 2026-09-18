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
        $categories_obj = new Categories($conn);
        $categories_array = $categories_obj->getAllCategories();
        $core = new Core();
        $uom_array = $core->_getTableRecords($conn,'manage_uom',' where 1');
        $nav = 0;
        if(isset($_GET['nav']))
        {
            $nav = 1;
        }
        $state_obj = new State($conn);
        $state_array = $state_obj->SetStateArray("All");
		?>
    <meta charset="utf-8">
    <title>
        Manage Rate Card - Aryadibusiness
    </title>
    <meta name="description" content="View Rate Card">
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
    
    
</head>
<?php
    
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
    $filter_param = "?nav=".$nav."&CompanyID=".$CompanyID."&UserType=".$UserType;

	?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->
    <input type="hidden" id="user_access" value="<?php echo $UserType; ?>" />
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <?php
                        if($CompanyID == -1)
                        {
                            ?>
                        <li class="breadcrumb-item active"><a href="../company/view-company">Manage Corporates Accounts</a>
                        </li>
                        <?php
                        }
                        ?>
                        <li class="breadcrumb-item active">Manage State GST Details</li>

                    </ol>   
                        <?php
                            if($UserType == "Admin"||$UserType == "Sub Admin"){
                        ?>
                        <!--a href="#" onclick="DownloadBranchFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a-->
                        <?php } ?>
                    </div>

                                

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View State GST Details
                                    </h2>
                                    <button type="button" onclick="FilterRateCard();" class="btn btn-sm btn-primary ml-3 mr-3">Search</button>
                                    <!--a href="#" onclick="OpenCSVmodal()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV File</a-->
                                    <?php 
                                    if($UserType == "Admin" || $TicketManager || $UserType == "Corporate Admin")
                                    {
                                    ?>
                                    <!--a href="#" onclick="ExportBranchData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a-->
                                    <?php
                                    }
                                    if($UserType == "Admin" || $TicketManager)
                                    {
                                    ?>
                                    <a href="#" onclick="openStateGSTModal('Add');" class="btn btn-info" style="margin-right:20px;">Add</a>
                                        
                                    <?php 
                                    } 
                                    ?>

                                </div>
                               
                                <div class="row">
                                    <div class="col-xl-12">
                                        
                                        <div class="panel-hdr">
                                            
                                            
                                        </div>

                                    </div> <!-- col-xl-12 -->
                                </div> <!-- row -->
                                <!-- Filters -->
                               
                                
                                 <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-state-gst" class="table table-bordered table-hover table-striped w-100">
                                            
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Company Account</th>
                                                    <th>State</th>
                                                    <th>GST No.</th>
                                                    <th>Address</th>
                                                    <th>Action</th>
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
    <script src="../js/modules/state-gst.js"></script>
       <script>
        $(document).ready(function() {
            var i = 1;
            $('#view-state-gst').dataTable({
                 responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'action/state-gst-list-post.php<?=$filter_param;?>'
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
                        data: 'CompanyName'
                    },
                    {
                        data: 'State'
                    },
                    {
                        data: 'GST'
                    },
                    {
                        data: 'Address'
                    },
                    {
                        data: 'Action'
                    },
                ]

            });

            $("#filter_category").select2();
        });
    

    </script>
</body>


</html>


 <div class="modal fade" id="stateGSTModal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Update State GST</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="add_update_company_state_gst">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-12">
                                        <label for="ChangeStatus">State</label>
                                        <select name="state" id="state" class="form-control select2 w-100">
                                            <option value="-1">Please Select</option>
                                            <option value="General">Default</option>
                                            <?php
                                            foreach($state_array as $ID=>$state)
                                            {
                                            ?>
                                                <option value="<?php echo $state['StateName'];?>"><?php echo $state['StateName'];?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>

                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <label for="corporate_gst">GST N0. <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="state_gst" id="state_gst" placeholder="Enter GST N0.">
                                    </div>

                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <label for="corporate_address">Billing Address </label> <br>
                                        <textarea class="form-control w-100" name="state_gst_address" id="state_gst_address" cols="30" rows="5" placeholder="Enter Company Address"></textarea>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="form_action" name="form_action" value="add">
                            <input type="hidden" id="form_id" name="form_id" value="-1">
                        
                        </form>
                
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="SaveStateGST();" id="saveRows">Save</button>
                </div>
            </div>
        </div>
    </div>