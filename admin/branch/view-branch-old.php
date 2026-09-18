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
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Branch - Aryadibusiness
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
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php

	$UserType = SessionCheck();
    $corporate_user = false;
    $CorporateID = -1;
    $BranchID = -1;
    $CompanyID = -1;
    if($UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = $CompanyID = $_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = $CompanyID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
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

    $company_array = getAllCompanies($conn,$CompanyID);
    $company_array_key = generateArraywithKey($company_array);
    //print_r($company_array_key);

    $city_array = getAllCity($conn);
    $city_array_key = json_decode($city_array, true);

    $state_array = getAllStates($conn);
    $nav=0;
    if(isset($_GET['nav']))
    {
        $nav = 1;
    }

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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
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
                            if($UserType == "Admin"){
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
                                    if($UserType == "Admin"){
                                    ?>
                                    <a href="#" onclick="ExportBranchData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>

                                    <a href="#" onclick="openBranch_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Branch Account</a>
                                    <?php } ?>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-branch"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <?php 
                                            if(sizeof($BranchArray) > 0)
                                            {
                                            ?>
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Company </th>
                                                    <th>Branch Name</th>
                                                    <th>Mobile / Alternate</th>

                                                    <th>City / State</th>
                                                    <th>Site Incharge</th>
                                                    <?php
                                                     if($UserType == "Admin"){
                                                     ?>
                                                    <th>Update</th>
                                                    
                                                    <th>Action</th>
                                                    <?php } ?>
                                                    <th>ARC Items</th>
                                                    <th>Branch Assets</th>
                                                    <th>Access</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($BranchArray as $Branch)
                                                	{

                                                	$ID  = $Branch['ID'];
                                                    if($ID != $BranchID && $UserType == "Corporate Branch User")
                                                        continue;
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php
                                                        if($Branch['CompanyID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $CompanyID_temp = $Branch['CompanyID'];
                                                            $CompanyName = $company_array_key[$CompanyID_temp]['CompanyName'];
                                                            echo $CompanyName;
                                                        }
                                                      ?></td>
                                                    <td><?php
                                                     if($Branch['BranchSite'] == "")
                                                     echo "NA";
                                                      else
                                                    echo $Branch['BranchSite'];
                                                      ?></td>


                                                    <td>
                                                        <?php
                                                        if($Branch['BranchMobile'] == "")
                                                        echo "NA";
                                                         else
                                                       echo $Branch['BranchMobile'];
                                                        ?>
                                                        /
                                                        <?php
                                                        if($Branch['BranchLandline'] == "")
                                                        echo "NA";
                                                         else
                                                       echo $Branch['BranchLandline'];
                                                        ?>
                                                    </td>



                                                    <td>
                                                        <?php
                                                        if($Branch['BranchCity'] == "")
                                                        echo "NA";
                                                         else
                                                       echo $Branch['BranchCity'];
                                                        ?>
                                                        /
                                                        <?php
                                                        if($Branch['BranchState'] == "")
                                                        echo "NA";
                                                         else
                                                       echo $Branch['BranchState'];
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        if($Branch['SiteIncharge'] == "")
                                                        echo "NA";
                                                         else
                                                       echo $Branch['SiteIncharge'];
                                                        ?>
                                                    </td>


                                                    <?php
                                                     if($UserType == "Admin"){
                                                     ?>
                                                    <td>
                                                        <a onclick="UpdateBranch_modal('<?php echo $ID;?>')"
                                                            class="cursor-pointer"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                    </td>
                                                   
                                                    <td>
                                                        <a onclick="DeleteBranch('<?php echo $ID;?>')"
                                                            class="cursor-pointer"><i class="fal fa-trash"
                                                                aria-hidden="true"></i>
                                                    </td>
                                                    <?php } ?>

                                                    <td>
                                                        <a href="../branch-arc-items/view-branch-arc-items.php">
                                                            <span class="badge badge-primary cursor-pointer">View
                                                                ARC</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a onclick="ViewBranchAssets('<?php echo $ID;?>')">
                                                            <span class="badge badge-primary cursor-pointer">View
                                                                Assets</span>
                                                        </a>
                                                    </td>
                                                    <td>

                                                        <a onclick="OpenResetPasswordModal('<?php echo $ID;?>')">
                                                            <span class="badge badge-primary cursor-pointer">Reset
                                                                Password</span>
                                                        </a>
                                                    </td>

                                                </tr>
                                                <?php
                                                	$i++;
                                                	}
                                                    
                                                	?>
                                            </tbody>

                                        </table>
                                        <?php
                                            }
                                            else
                                            {
                                                echo "<h2>Currently there are no branches to show</h2>";
                                            }
                                        ?>
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div><!-- panel-1 -->
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
                                                            if($CompanyID == $company['ID'])
                                                                $selected = "selected";
                                                            else
                                                            {
                                                                if($nav==0)
                                                                    continue;
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
                                    </form>
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

                                                <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white"
                                                    onclick="ResetPassword()">Save</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Reset Modal -->

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
</body>


</html>