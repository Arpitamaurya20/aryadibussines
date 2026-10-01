<?php session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/branch_assets_controller.php');
        include('../branch/controller/branch_controller.php');
        include('../manage-uom/controller/uom_controller.php');
        include('../manage-sub-categories/controller/sub_categories_controller.php');
        include('../manage-categories/controller/categories_controller.php');
        include('../includes/autoloader.inc.php');
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <style>
    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
    }
    .filter_view .select2-container
    {
        z-index: 1;
    }
    .branch-assets-modal-body {
        position: relative;
        min-height: 180px;
    }
    #branch_assets_modal_loader {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.85);
        z-index: 20;
        text-align: center;
        padding-top: 80px;
    }
    </style>
    <?php
    $UserType = SessionCheck();
    $TicketManager=false;
    $BranchID = -1;
    $CorporateID = -1;
    $nav=0;

    if(isset($_GET['nav']))
    {
        $nav=1;
    }
    else
    {
        if(isset($_SESSION['BranchID']))
        {
            $BranchID = $_SESSION['BranchID'];
        }
    }
    
    if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];

    }
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "Ticket Manager" || $UserType == "Admin")
            {
                $TicketManager = true;
            }
        }
    }
    
    
    $company_object = new Company($conn);
    $company_array = $company_object->setCompanyArray('All');

    $branch_obj = new Branch($conn);
    $corporate_branches = $branch_obj->setBranchArrayByCorporateID($CorporateID,'Active');

    $uofdata = getAllUOM($conn);
    $CategorieData = getAllCategories($conn);
    $SubCategorieData = getAllSubCategories($conn);
    $CreatedBy = $_SESSION['pb_username'];
    $filter_param = "?CorporateID=".$CorporateID."&BranchID=".$BranchID;
    

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
        Manage Branch Assets - <?=$ProductName;?>
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
                            <li class="breadcrumb-item"><a href="javascript:void(0);"><?=$ProductName;?></a></li>
                            <?php 
                            if($UserType == "Admin" || $TicketManager)
                            {
                            ?>
                            <li class="breadcrumb-item"><a href="../company/view-company">Manage Corporate</a></li>
                            <li class="breadcrumb-item"><a href="../branch/view-branch">Manage Branch</a></li>
                            <?php 
                            }
                            ?>
                            <li class="breadcrumb-item active">Manage Branch Assets</li>
                        </ol>
                        <?php
                        if($UserType == "Admin"||$UserType == "Sub Admin" || $TicketManager || ($CorporateID == 183 && $UserType == 'Corporate Admin')){
                        ?>
                        <a href="#" onclick="DownloadAssetsFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a>
                        <?php 
                        } 
                        ?>


                          <?php if($UserType == "Admin" || $TicketManager || ($CorporateID == 183 && $UserType == 'Corporate Admin')){ ?>
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#bulk_ticket_modal">
                                  Raise Bulk PPM Ticket
                                </button>
                        <?php } ?>
                    </div>

                     

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Branch Assets
                                    </h2>
                                    <?php
                                    if($UserType == "Admin"||$UserType == "Sub Admin" || $TicketManager){
                                    ?>
                                    <a href="#" onclick="OpenCSVmodal()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV File</a>
                                    <?php } ?>
                                    <?php
                                    if($UserType == "Admin" || $TicketManager || ($CorporateID == 183 && $UserType == 'Corporate Admin')){
                                    ?>
                                    <button type="button" onclick="FilterAssets();" class="btn btn-sm btn-primary mr-3">Search</button>
                                    <a href="#" onclick="ExportBranchAssetsData()" class="btn btn-info" style="margin-right:20px;">Export Data</a>
                                        <?php 
                                        if(!$nav)
                                        {
                                        ?>
                                        <a href="#" onclick="openBranch_modal()" class="btn btn-info"
                                            style="margin-right:20px;">Add Branch Assets</a>
                                        <?php 
                                        }
                                    } 
                                    ?>
                                </div>
                                <div class="row filter_view">
                                    <div class="col-xl-12">
                                        
                                        <div class="panel-hdr">

                                            <?php

                                            if($UserType != "Corporate Branch User" && $UserType != "Corporate Admin")
                                            {
                                            ?>

                                            <div class="col-2">
                                                <select class="form-control" name="filter_company_id" id="filter_company_id" onchange="BranchAssetsGetBranchesFromCorporateID(this);">
                                                    <option value="-1">Select Corporate Account</option>
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

                                            <div class="col-3" id="branches_filter_div">
                                                <select class="form-control" name="branch_name" id="branch_name">
                                                    <option value="-1">Select Branch</option>
                                                    <?php
                                                    foreach ($corporate_branches as $ID=>$branch) 
                                                    {

                                                        $BranchSite = $branch['BranchName']
                                                    ?>

                                                        <option value="<?php echo $ID;?>"> <?php echo $BranchSite; ?></option>

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
                                <!-- Filters -->
                                <?php
                                 include('./include/branch-assets-list-view.php')
                                 ; ?>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->

                   
                    <!-- Datatable Container --><!-- upload csv modal -->

          <div class="modal fade" id="upload_csv" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Upload Branch Asset CSV </h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                       <form id="uplaod_branch_csv">
                                            <input type="file" class="form-control" name="csvFile" accept=".csv">
                                            

                                            <div class="row justify-content-center mt-3">

                                                <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="upload_csv_btn"
                                                    onclick="UploadBranchassets_CSV()">Upload</a>

                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div> 
                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_branch_assets_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="branch_modal_title"> </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body branch-assets-modal-body">
                                    <div id="branch_assets_modal_loader">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="mt-2">Loading asset details...</div>
                                    </div>
                                    <form METHOD="POST" id="add_update_branch_assets_form">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-4 mt-3">
                                                    <label>Branch <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="branch_id"
                                                        name="branch_id">
                                                        <option value="-1">Search & Select</option>
                                                    </select>
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label>Name of Equipment<span class="text-danger">*</span> </label>
                                                    <input type="text" class="form-control" name="equipment_name"
                                                        id="equipment_name" placeholder="Enter Equipment Name">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Make </label>
                                                    <input type="text" class="form-control" name="make" id="make"
                                                        placeholder="Enter Make">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Model </label>
                                                    <input type="text" class="form-control" name="model" id="model"
                                                        placeholder="Enter Model">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> SNo </label>
                                                    <input type="text" class="form-control" name="serial_no"
                                                        id="serial_no" placeholder="Enter SNo">
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label>Capacity </label>
                                                    <input type="number" class="form-control" name="capacity"
                                                        id="capacity" placeholder="Enter Capacity">
                                                </div>


                                                <div class="col-4 mt-3">
                                                    <label> Qty <span class="text-danger">*</span></label>
                                                    <input type="number" onkeyup="MultiplyQty_Unit()"
                                                        class="form-control" name="quantity" id="quantity"
                                                        placeholder="Enter Qty">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> UoM </label>
                                                    <select class="select2 form-control w-100" id="uom" name="uom">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($uofdata as $uofdata)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $uofdata['ID'];?>">
                                                            <?php echo $uofdata['UOMName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> Unit Rate</label>
                                                    <input type="text" class="form-control" name="unit_rate"
                                                        id="unit_rate" onkeyup="MultiplyQty_Unit()"
                                                        placeholder="Enter Unit Rate">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Amount</label>
                                                    <input type="text" class="form-control" name="amount" id="amount"
                                                        placeholder="Enter Amount">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Manufacturing Year</label>
                                                    <br>
                                                    <input type="text" class="form-control" name="manufacturing_year"
                                                        id="manufacturing_year" placeholder="Enter Manufacturing Year">
                                                </div>



                                                <div class="col-4 mt-3">
                                                    <label> Age of Equipment ( years )<span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="equipment_age"
                                                        id="equipment_age" placeholder="Enter Equipment Age">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Type of Services</label>
                                                    <select class="select2 form-control w-100" id="service_type"
                                                        name="service_type">
                                                        <option value="-1">Search & Select</option>
                                                        <option value="CAMC">CAMC</option>
                                                        <option value="NCAMC">NCAMC</option>
                                                        <option value="NCMC">NCMC</option>
                                                    </select>
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Category</label>
                                                    <select onchange="SelectBranchAssetsCategories()" class="select2 form-control w-100" id="categories"
                                                        name="categories">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($CategorieData as $CategorieValue)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $CategorieValue['ID'];?>">
                                                            <?php echo $CategorieValue['CategoriesName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-4 mt-3" id="sub_categories_div" style="display:none;">
                                                    <label>Sub Category </label>
                                                    <select class="select2 form-control w-100" id="sub_categories"
                                                        name="sub_categories">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($SubCategorieData as $SubCategorieValue)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $SubCategorieValue['ID'];?>">
                                                            <?php echo $SubCategorieValue['SubCategoriesName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> TAT ( In Days )</label>
                                                    <input type="text" class="form-control" id="tat" name="tat"
                                                        placeholder="Enter TAT" />

                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> AMC Start Date </label>
                                                    <input type="text" class="form-control" name="amc_start_date"
                                                        id="amc_start_date" placeholder="Pick AMC Start Date">
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> AMC End Date </label>
                                                    <input type="text" class="form-control" name="amc_end_date"
                                                        id="amc_end_date" placeholder="Pick AMC End Date">
                                                </div>


                                                <div class="col-4 mt-3">
                                                    <label> SOW </label>
                                                    <div class="custom-file">
                                                        <input type="file" id="sow_img" name="sow_img"
                                                            class="form-control">
                                                    </div>

                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Floor Number</label>
                                                    <input type="text" class="form-control" name="floor_number"
                                                        id="floor_number" placeholder="Enter Floor Number">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Equipment Location</label>
                                                    <input type="text" class="form-control" id="equipment_location"
                                                        name="equipment_location"
                                                        placeholder="Enter Equipment Location" />

                                                </div>


                                                <div class="col-8 mt-3">
                                                    <label> Description</label>
                                                    <textarea class="form-control" name="description" id="description"
                                                        placeholder="Enter Description"></textarea>
                                                </div>

                                                                                                  <div class="col-4 mt-3">
                                                    <label>PPM Interval </label>
                                                    <select class="select2 form-control w-100" id="PPMInterval"
                                                        name="PPMInterval">
                                                        <option value="-1">Search & Select</option>
                                                        <option value="Monthly">Monthly</option>
                                                        <option value="Quterly">Quterly</option>
                                                        <option value="Half Yearly">Half Yearly</option>
                                                        <option value="Yearly">Yearly</option>
                                                        
                                                    </select>
                                                </div>

                                                <div class="col-8 mt-3">
                                                    <label>PPM Checklist (Category wise)</label>
                                                    <select class="select2 form-control w-100" id="asset_checklist_id"
                                                        name="asset_checklist_id">
                                                        <option value="-1">Select category first</option>
                                                    </select>
                                                    <small class="text-muted">Maps this asset to a dynamic PPM checklist. Company mapping remains unchanged.</small>
                                                </div>


                                            </div>

                                        </div>
                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button type="submit" class="btn btn-primary" id="branch_assets_btn"
                                            onclick="return AddUpdateBranchAssets()">Submit</button>
                                        <?php 
                                        if($UserType == "Admin" || $TicketManager)
                                        {
                                            ?>
                                            <button type="submit" class="btn btn-danger" id="branch_asset_disable_ppm_btn"
                                            onclick="return DisableAssetPPMTickets()">Disable PPM Tickets</button>
                                            <?php
                                        }
                                        ?>
                                    </form>
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
    <!-- END Page Wrapper -->


    <!-- Modal -->
    <div class="modal fade" id="raiseamcticket" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Raise AMC Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="raise_amc_tickets">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label for="message">Type your Message <span class="text-danger">*</span> </label>
                                    <br>
                                    <textarea name="Message" id="message" class="form-control w-100" cols="30" rows="5"
                                        placeholder="Type your message"></textarea>
                                </div>

                            </div>


                        </div>
                        <!--input type="hidden" name="CorporateID" id="corporate_modal_id" value=""-->
                        <input type="hidden" name="BranchID" id="branch_modal_id" value="" >
                        <input type="hidden" name="BranchAssetID" id="branch_asset_modal_id" value="">
                        <input type="hidden" name="Type" value="AMC">
                        <input type="hidden" name="CreatedBy" id="created_by_modal" value="">
                        <button type="submit" id="ticket_raise_btn" class="btn btn-primary" onclick="return RaiseAMCTickets()">Submit</button>
                    </form>
                </div>

            </div>
        </div>
    </div>



    <div class="modal fade" id="qr_image_modal" tabindex="-1">
    <div class="modal-dialog modal-lg"> <!-- changed to modal-lg -->
        <div class="modal-content">
            <div class="modal-header modal_header">
                <h5 class="modal-title">QR Code Image</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body text-center">
                <img id="qr_image_display" 
                     src="" 
                     style="max-width:100%; height:auto; border:1px solid #ddd; padding:15px; border-radius:8px;">
            </div>
        </div>
    </div>
</div>


    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/branch-assets.js?v=20260924"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
   <!--   <script>
        $(document).ready(function() {
            
            var i = 1;
            $('#view-branch-assets').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'include/branch-assets-list-post.php<?php echo $filter_param; ?>'
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
                        data: 'CompanyBranches',
                    },
                    {
                        data: 'EquipmentName'
                    },
                    {
                        data: 'Make_Model'
                    },
                    {
                        data: 'SNo'
                    },
                    {
                        data: 'Capacity'
                    },
                    {
                        data: 'ManufacturingYear'
                    },
                    {
                        data: 'ServiceType'
                    },
                    {
                        data: 'FloorNumber_EquipmentLocation'
                    },
                    {
                        data: 'PPM'
                    },
                    {
                        data: 'AMCTicket'
                    },
                    {
                        data: 'Update'
                    },
                    {
                        data: 'Action'
                    }


                ]


            });


             $("#nav_company_assets").addClass("active");

        });

    </script> -->


    <script>
    $(document).ready(function() {
        // Function to get URL parameter
        function getUrlParameter(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }

 /*       // Get the 'nav' parameter from the URL
        var nav = getUrlParameter('nav');

        console.log('nav',nav);
*/
        // Initialize DataTable columns
        var columns = [
            {
                "data": "id",
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'CompanyBranches'
            },
             {
                data: 'EquipmentID'
            },
            {
                data: 'EquipmentName'
            },
            {
                data: 'Make_Model'
            },
            {
                data: 'SNo'
            },
            {
                data: 'Capacity'
            },
            {
                data: 'ManufacturingYear'
            },
            {
                data: 'ServiceType'
            },
            {
                data: 'FloorNumber_EquipmentLocation'
            },
            {
                data: 'PPM'
            },
            {
                data: 'AMCTicket'
            },
            {
                data: 'QRImage'
            },
            {
                data: 'Update'
            },
            {
                data: 'Action'
            }
        ];

       /* // If nav is 1, add 'CompanyBranches' column at the second position
        if (nav == 1) {
            columns.splice(1, 0, {
                data: 'CompanyBranches'
            });
        }*/

        // Initialize DataTable with dynamic columns
        window.initBranchAssetsTable = function(tableSelector, isActive, extraQuery) {
            extraQuery = extraQuery || '';
            extraQuery = String(extraQuery).replace(/^\?/, '');
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().clear().destroy();
            }
            var ajaxUrl = 'include/branch-assets-list-post.php';
            var query = extraQuery;
            if (query) {
                query += '&';
            }
            query += 'IsActive=' + isActive;
            $(tableSelector).dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': ajaxUrl + '?' + query
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
        };

        var defaultQuery = '<?php echo ltrim($filter_param, "?"); ?>';
        window.initBranchAssetsTable('#view-branch-assets-active', 1, defaultQuery);
        window.initBranchAssetsTable('#view-branch-assets-inactive', 0, defaultQuery);

        $("#nav_company_assets").addClass("active");
        if($("#branch_name").length)
        {
            $("#branch_name").select2();
        }
        if($("#filter_company_id").length)
        {
            $("#filter_company_id").select2();
        }
        
    });

    
    function ShowQRImageModal(imgPath) {
    $("#qr_image_display").attr("src", imgPath);
    $("#qr_image_modal").modal("show");
}
</script>


    <div class="modal fade" id="bulk_ticket_modal" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header modal_header">
            <h5 class="modal-title">Raise Bulk PPM Ticket for All Assets</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="bulk_ticket_form" method="POST" onsubmit="return false;">
              <div class="form-group">
                <label for="bulk_ticket_date">Select Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="bulk_ticket_date" name="bulk_ticket_date" placeholder="Pick Date">
              </div>
              <input type="text" id="bulk_ticket_branch_id" name="branch_id" value="<?=$BranchID;?>" readonly>
              <button type="button" class="btn btn-primary" onclick="RaiseBulkTickets()">Submit</button>
            </form>
          </div>
        </div>
      </div>
    </div>
</body>


</html>
