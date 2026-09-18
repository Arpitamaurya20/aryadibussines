<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/branch_controller.php');
        include('../company/controller/company_controller.php');
        include('../city/controller/city_controller.php');
        include('../state/controller/state_controller.php');
        require_once('../includes/autoloader.inc.php');
        $UserType = SessionCheck();
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
        $employee = new Employee($conn);
        $employee_array = $employee->setEmployeeArray('Active');
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    
    <meta name="description" content="View Company Branches">
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
    #panel-1 .select2-container
    {
        z-index: 1;
    }

    </style>
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
            }
        }
    }


    if(isset($_GET['nav']) && !$corporate_user)
    {
        $CompanyID = -1;
    }
    if(isset($_SESSION['CompanyID']))
    {
        $CorporateID = $CompanyID = $_SESSION['CompanyID'];
    }
    $BranchArray = getAllBranches($conn,$CompanyID);

    $company_array = getAllCompanies($conn);
    $company_array_key = generateArraywithKey($company_array);
    //print_r($company_array_key);

    $city_array = getAllCity($conn);
    $city_array_key = json_decode($city_array, true);

    $city_array_raw = _getTableRecords($conn,'citydata',' where 1 ORDER BY CityName ASC');

    $state_object = new State($conn);
    $state_array_raw = $state_object->setStateArray('Active');
    

    $state_array = getAllStates($conn);
    $nav=0;
    if(isset($_GET['nav']))
    {
        $nav = 1;
    }

    $company_object = new Company($conn);
    $company_array_raw = $company_object->setCompanyArray('All');

    $filter_param = "?nav=".$nav."&CorporateID=".$CorporateID."&CompanyID=".$CompanyID."&BranchID=".$BranchID."&UserType=".$UserType;
    $user_access = $UserType;
    if($TicketManager)
    {
        $user_access = "Ticket Manager";
    }
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
    <title>
        Manage Branches - <?=$ProductName;?>
    </title>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>


<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->
    <input type="hidden" id="user_access" value="<?php echo $user_access; ?>" />
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
                        <?php
                        if($CompanyID == -1)
                        {
                            ?>
                        <li class="breadcrumb-item active"><a href="../company/view-company">Manage Corporates
                                Accounts</a>
                        </li>
                        <?php
                        }
                        ?>
                        <li class="breadcrumb-item active">Manage Branches Accounts</li>

                    </ol>   
                        <?php
                            if($UserType == "Admin"||$UserType == "Sub Admin"|| $TicketManager){
                        ?>
                        <a href="#" onclick="DownloadBranchFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a>
                        <?php } ?>
                    </div>

                                

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Branches Accounts
                                    </h2>

                                    
                                    <?php
                                    if($UserType == "Admin"||$UserType == "Sub Admin"|| $TicketManager)
                                    {
                                    ?>
                                    <button type="button" onclick="FilterBranches();" class="btn btn-sm btn-primary ml-3 mr-3">Search</button>
                                    <a href="#" onclick="OpenCSVmodal()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV File</a>
                                    <?php 
                                    } 
                                    if($UserType == "Admin" || $TicketManager || $UserType == "Corporate Admin")
                                    {
                                    ?>
                                    <!-- <a href="#" onclick="ExportBranchData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a> -->

                                         <a href="action/export_corporate_branch_assets"  class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>
                                    <?php
                                    }
                                    if($UserType == "Admin" || $TicketManager || ($CorporateID == 183 && $corporate_account_admin))
                                    {
                                    ?>
                                    <a href="#" onclick="openBranch_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Branch Account</a>
                                        
                                    <?php 
                                    } 
                                    ?>

                                </div>
                                <?php 
                                if($UserType == "Admin")
                                {
                                ?>
                                <div class="row">
                                    <div class="col-xl-12">
                                        
                                        <div class="panel-hdr">
                                            <div class="col-2">
                                                
                                                <select class="form-control" name="stateName" id="stateName" onchange="GetCitiesfromState(this);">
                                                    <option value="">Select State Name</option>
                                                    <?php
                                                    foreach ($state_array_raw as $state) 
                                                    {
                                                        $StateName = $state['StateName']
                                                    ?>

                                                        <option value="<?php echo $StateName ?>"> <?php echo $StateName ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
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
                                            <div class="col-2" style="width:100%;z-index: 1!important;">
                                                <select class="form-control" name="filter_company_id" id="filter_company_id">
                                                    <option value="">Select Corporate Account</option>
                                                    <?php
                                                    foreach ($company_array_raw as $filter_companyID=>$e_company) 
                                                    {

                                                        $Company_Account_Name = $e_company['CompanyName']
                                                    ?>

                                                        <option value="<?php echo $filter_companyID ?>"> <?php echo $Company_Account_Name; ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                            <input type="hidden" id="nav" value="<?php echo $nav; ?>" />
                                            <input type="hidden" id="CorporateID" value="<?php echo $CorporateID; ?>" />
                                            <input type="hidden" id="CompanyID" value="<?php echo $CompanyID; ?>" />
                                            <input type="hidden" id="BranchID" value="<?php echo $BranchID; ?>" />
                                            <input type="hidden" id="UserType" value="<?php echo $UserType; ?>" />
                                        </div>

                                    </div> <!-- col-xl-12 -->
                                </div> <!-- row -->
                                <!-- Filters -->
                               
                               <?php
                                }
                                 include('./include/branch-list-view.php');
                                ?>

                            </div>
                            <!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_branch_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="branch_modal_title"> </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_branch_form">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-4 mt-3">
                                                    <label>Company <span class="text-danger">*</span> </label>
                                                    <select class="select2 form-control w-100" id="branch_company"
                                                        name="branch_company">
                                                        <option value="-1">Search & Select</option>
                                                        <?php
                                                        foreach($company_array as $company)
                                                        {
                                                            $selected = "";
                                                            if ($nav != 1) {     
                                                                if($CompanyID == $company['ID'])
                                                                    $selected = "selected";
                                                                else
                                                                {
                                                                    if($nav==0)
                                                                        continue;
                                                                }
                                                            } 
                                                        ?>
                                                        <option value="<?php echo $company['ID'];?>"
                                                            <?php echo $selected; ?>>
                                                            <?php echo $company['CompanyName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label>Branch Site / Name <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control" name="branch_name"
                                                        id="branch_name" placeholder="Enter Branch Site / Name ">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Branch Code </label>
                                                    <input type="text" class="form-control" name="branch_code"
                                                        id="branch_code" placeholder="Enter Branch Code">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Contact Email <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="branch_email"
                                                        id="branch_email" placeholder="Enter Contact Email ">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Mobile Number <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="branch_mobile"
                                                        id="branch_mobile" placeholder="Enter Mobile Number">
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label>Alternate Number </label>
                                                    <input type="number" class="form-control" name="branch_landline"
                                                        id="branch_landline" placeholder="Enter Alternate Number">
                                                </div>


                                                <div class="col-4 mt-3">
                                                    <label> Postal Code </label>
                                                    <input type="number" class="form-control" name="branch_postal_code"
                                                        id="branch_postal_code" placeholder="Enter Postal Code">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> State / Province <span class="text-danger">*</span></label>
                                                    <select onchange="SelectState()" class="select2 form-control w-100" id="branch_state"
                                                        name="branch_state">
                                                        <option value="-1">Search & Select State / Province </option>
                                                        <?php

                                                        foreach($state_array as $state)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $state['StateName'];?>">
                                                            <?php echo $state['StateName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                

                                                <div class="col-4 mt-3">
                                                    <?php 
                                                    if(!$corporate_account_admin)
                                                    {
                                                    ?>
                                                    <label> Branch Account Manager </label>
                                                    <select class="select2 form-control w-100" id="account_branch_manager"
                                                        name="account_branch_manager">
                                                        <option value="-1">Search & Select</option>
                                                        <?php
                                                        foreach($employee_array as $EmployeeID=>$employee)
                                                        {
                                                        ?>
                                                            <option value="<?php echo $EmployeeID;?>">
                                                                <?php echo $employee['Name'];?>
                                                            </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php
                                                    }
                                                    ?>
                                                </div>

                                                <div class="col-4 mt-3" id="city_div" style="display:none;">
                                                    <label> City / District <span class="text-danger">*</span></label>

                                                    <select class="select2 form-control w-100" id="branch_city"
                                                        name="branch_city">
                                                        <option value="-1">Search & Select City / District</option>
                                                        <?php

                                                        foreach($city_array_key as $city)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $city['CityName'];?>">
                                                            <?php echo $city['CityName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Latitude </label>
                                                    <input type="text" class="form-control" name="branch_latitude"
                                                        id="branch_latitude" placeholder="Enter Branch Latitude">
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> Longitude </label>
                                                    <input type="text" class="form-control" name="branch_longitude"
                                                        id="branch_longitude" placeholder="Enter Branch Longitude">
                                                </div>

                                                
                                                <div class="col-6 mt-3">
                                                    <label>Address Line 1</label>
                                                    <br>
                                                    <textarea name="branch_address_1" id="branch_address_1" cols="30"
                                                        rows="2" class="w-100 form-control"></textarea>
                                                </div>

                                                <div class="col-6 mt-3">
                                                    <label>Address Line 2</label>
                                                    <br>
                                                    <textarea name="branch_address_2" id="branch_address_2" cols="30"
                                                        rows="2" class="w-100 form-control"></textarea>
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> Site Incharge</label>
                                                    <input type="text" class="form-control" name="site_incharge"
                                                        id="site_incharge" placeholder="Enter Site Incharge">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Site UserName</label>
                                                    <input type="text" class="form-control" name="branch_username"
                                                        id="branch_username" placeholder="Enter Site UserName">
                                                </div>

                                                <div class="col-4 mt-3" id="site_password">
                                                    <label> Site Password </label>
                                                    <input type="password" class="form-control" name="branch_password"
                                                        id="branch_passwords" placeholder="Enter Site Password">
                                                </div>

                                            </div>

                                        </div>
                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button type="submit" id="branch_btn"  class="btn btn-primary"
                                            onclick="return AddUpdateBranch()">Submit</button>
                                        <?php
                                        if($UserType == "Admin" || $TicketManager)
                                        {
                                        ?>
                                        <button type="submit" id="branch_btn_merge"  class="btn btn-warning"
                                            onclick="return MergeBranch()">Merge Branch</button>

                                         <button type="submit" id="branch_btn_merge"  class="btn btn-danger"
                                            onclick="return DisableBranchPPMTickets()">Disable PPM Tickets</button>
                                        <?php
                                        }
                                        ?>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>

                     <!-- upload csv modal             -->

                    <div class="modal fade" id="upload_csv" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Upload Branch CSV </h2>
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
                                                    onclick="UploadBranch_CSV()">Upload</a>

                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>     

                    <!-- Reset Modal -->

                    <div class="modal fade" id="reset_branch_password" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Reset Branch Password</h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                        <form id="reset_password_form">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="password">Password</label>
                                                        <input type="text" name="reset_passsword" id="reset_passsword"
                                                            class="form-control" placeholder="Enter Password">
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="re-password">Re-type
                                                            Password</label>
                                                        <input type="text" name="reset_confirm_passsword"
                                                            id="reset_confirm_passsword" class="form-control"
                                                            placeholder="Re-type Password" required>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="reset_password_username" name="reset_password_username" value="">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="reset_password_btn"
                                                    onclick="ResetPassword()">Save</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Reset Modal -->

                    <!-- Merge Branch Modal -->
                    <div class="modal fade" id="merge_branch_modal" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Merge Branch</h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-group" id="company_branch_select_div">
                                                    <label class="form-label">Branch</label>
                                                    <select class="select2 form-control w-100" id="to_be_merged_branch"
                                                        name="to_be_merged_branch">
                                                        <option value="-1">Search & Select</option>
                                                        <?php
                                                        foreach($company_array as $company)
                                                        {
                                                            $selected = "";
                                                            if ($nav != 1) {     
                                                                if($CompanyID == $company['ID'])
                                                                    $selected = "selected";
                                                                else
                                                                {
                                                                    if($nav==0)
                                                                        continue;
                                                                }
                                                            }
                                                        ?>
                                                        <option value="<?php echo $company['ID'];?>"
                                                            <?php echo $selected; ?>>
                                                            <?php echo $company['CompanyName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row justify-content-center mt-3">
                                            <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="reset_password_btn"
                                                onclick="MergeBranchAction()">Save</a>
                                        </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Merge Branch Modal Ends -->

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
    <script src="../js/modules/branch.js"></script>
       <script>
        $(document).ready(function() {
            var i = 1;
            $('#view-branch').dataTable({
                 responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'include/branch-list-post.php<?php echo $filter_param; ?>'
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
                        data: 'BranchName'
                    },
                    {
                        data: 'Mobile_Alternate'
                    },
                    {
                        data: 'City_State'
                    },
                    {
                        data: 'Branch_Assets'
                    },
                    // {
                    //     data: 'ViewARC'
                    // },
                    {
                        data: 'ViewSparePart'
                    },
                    {
                        data: 'Access'
                    },
                    <?php 
                    if($UserType == "Admin" || $corporate_account_admin || $TicketManager)
                    {
                        ?>
                        {
                            data: 'Update'
                        },
                        <?php
                        if($UserType == "Admin")
                        { 
                        ?>
                            {
                                data: 'Action'
                            },
                        <?php
                        }

                    }
                    ?>
                    {
                        data: 'SiteIncharge'
                    }


                ]


            });
            $('#filter_company_id').select2();
            $('#stateName').select2();
            $('#cityName').select2();
        });

    </script>
</body>


</html>