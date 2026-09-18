<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/company_controller.php');
        include('../corporate/controller/corporate_controller.php');
        include('../includes/autoloader.inc.php');
        $UserType = SessionCheck();
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
        $employee = new Employee($conn);
        $employee_array = $employee->setEmployeeArray('Active');
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Company - Aryadibusiness
    </title>
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

    // $CorporateID = -1;
    // if(isset($_GET['nav']))
    // {
    //     $CorporateID = -1;
    // }
    // else
    // {
    //     if(isset($_SESSION['CorporateID']))
    //     {
    //         $CorporateID = $_SESSION['CorporateID'];
    //     }
    // }
    
    $CorporateHQID = -1;
    if(isset($_SESSION['CorporateHQID']) && !(isset($_GET['nav'])))
    {
        $CorporateHQID = $_SESSION['CorporateHQID'];
    }
	$CompanyArray = getAllCompaniesbyCompanyHQ($conn,$CorporateHQID);
    $CorporateArray = getAllCorporate($conn);
    $AllIndustryType= getAllIndustryType($conn);

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
                            <li class="breadcrumb-item active">Manage Company Accounts</li>

                        </ol>

                    <?php
                        if($UserType == "Admin"||"Sub Admin"){
                    ?>

                        <a href="#" onclick="DownloadCompanyDataAssetsFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a>
                        <?php } ?>
                    </div>
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Manage Company Accounts
                                    </h2>
                                    <!--form action="action/export_company.php" method="post" enctype="multipart/form-data">
                                        <label>Select Excel file:</label>
                                        <input type="file" name="excel_file">
                                        <br><br>
                                        <button type="submit">Import from Excel</button>
                                    </form-->
                                     <a href="#" onclick="ExportCompanyData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>
                                    <?php
                                    if($UserType == "Admin" || $TicketManager){
                                    ?>
                                    <a href="#" onclick="openCompany_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Company Account</a>
                                    <?php } ?>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-company"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Company Name </th>
                                                    <th>Industry Type</th>
                                                    <th>Contact Details</th>
                                                    <th>Tender</th>
                                                    <th>Update</th>
                                                    <?php
                                                     if($UserType == "Admin"){
                                                     ?>
                                                    <th>Status</th>
                                                    <?php } ?>
                                                    <th>Branches</th>
                                                    <th>Access</th>
                                                    <th>State GST</th>
                                                    <th>Rate Card</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($CompanyArray as $Company)
                                                	{

                                                	$ID  = $Company['ID'];
                                                    $status  = $Company['IsActive'];
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php
                                                    if($Company['CompanyName'] == "")
                                                     echo "NA";
                                                      else
                                                    echo $Company['CompanyName'];
                                                     ?></td>

                                                      <td><?php
                                                    if($Company['CompanyIndustryType'] == "")
                                                     echo "NA";
                                                      else
                                                    echo $Company['CompanyIndustryType'];
                                                     ?></td>


                                                    <td>
                                                        <?php
                                                         if($Company['CompanyEmail'] == "")
                                                         {
                                                            echo "Email - Not Set";
                                                         }
                                                         else
                                                         {
                                                            echo $Company['CompanyEmail'];
                                                         }
                                                         echo "<br>";
                                                        if($Company['CompanyPhone'] == "" && $Company['CompanyMobile'] == "")
                                                        {
                                                            echo "Not Set";
                                                        }
                                                        else
                                                        {
                                                            echo $Company['CompanyPhone'];
                                                            if($Company['CompanyMobile'] != "")
                                                            {
                                                                echo " / ";
                                                            }
                                                        }
                                                        if($Company['CompanyMobile'] == "")
                                                        {
                                                        }
                                                        else
                                                        {
                                                            echo $Company['CompanyMobile'];
                                                        }
                                                       
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        if($Company['CompanyTendor'] == "")
                                                            echo "NA";
                                                         else
                                                            echo $Company['CompanyTendor'];
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a onclick="UpdateCompany_modal('<?php echo $ID;?>')"
                                                            class="cursor-pointer"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                        </a>
                                                    </td>
                                                    <?php
                                                     if($UserType == "Admin")
                                                     {
                                                        if($status == 0)
                                                        {
                                                            $current_status = "Inactive";
                                                            $text = "Deactive";
                                                            $change_status_to = 1;
                                                        }
                                                        else
                                                        {
                                                            $current_status = "Active";
                                                            $text = "Active";
                                                            $change_status_to = 0;
                                                        }
                                                     ?>
                                                    <td>
                                                        <?php if($status == 0){ ?>
                                                        <button id="deactive"
                                                            onclick="ActiveDeactiveChange(<?php echo $ID;?>,<?php echo $change_status_to;?>)"
                                                            class="btn btn-dark btn-sm shadow-none waves-effect waves-dark"
                                                            title="Click to active" data-toggle="tooltip">
                                                            <?php echo $text; ?></button>
                                                        <?php }else{?>
                                                        <button id="deactive"
                                                            onclick="ActiveDeactiveChange(<?php echo $ID;?>,<?php echo $change_status_to;?>)"
                                                            class="btn btn-success btn-sm shadow-none waves-effect waves-dark"
                                                            title="Click to active" data-toggle="tooltip">
                                                            <?php echo $text; ?></button>
                                                        <?php } ?>
                                                    </td>
                                                    <?php 
                                                    } 
                                                    ?>
                                                    <!-- <td>
                                                        <a onclick="DeleteCompany('<?php echo $ID;?>')"
                                                            class="cursor-pointer"><i class="fal fa-trash"
                                                                aria-hidden="true"></i>
                                                        </a>
                                                    </td> -->
                                                    <td>

                                                        <a onclick="ViewBranches('<?php echo $ID;?>')">
                                                            <span class="badge badge-primary cursor-pointer">View
                                                                Branches</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if($Company['Access'] == 1)
                                                        {
                                                        ?>

                                                        <a onclick="OpenResetPasswordModal(<?php echo $ID;?>)">
                                                            <span class="badge badge-primary cursor-pointer">Reset
                                                                Password</span>
                                                        </a>
                                                        <?php
                                                        }
                                                        else
                                                        {
                                                            ?>
                                                        <a onclick="OpenSetAccessModal(<?php echo $ID;?>,'<?php echo $Company['CompanyPhone'];?>');"
                                                            class="cursor-pointer">
                                                            <span class="badge badge-primary cursor-pointer">Set
                                                                Access</span>
                                                        </a>
                                                        <?php
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>

                                                        <a onclick="ViewStateGST('<?php echo $ID;?>')">
                                                            <span class="badge badge-primary cursor-pointer">State GST</span>
                                                        </a>
                                                    </td>
                                                    <td>

                                                        <a onclick="ViewRateCard('<?php echo $ID;?>')">
                                                            <span class="badge badge-primary cursor-pointer">View
                                                                Rate Card</span>
                                                        </a>
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


                    <!-- Modal -->
    <div class="modal fade" id="add_edit_company_modal" role="dialog" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title" id="company_modal_title"> </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="add_update_company_form">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-6 mt-3">
                                    <label>Company Name <span class="text-danger">*</span> </label>
                                    <select class="select2 form-control w-100" id="corporate_name"
                                        name="corporate_name">
                                        <option value="-1">Search & Select</option>
                                        <?php

                                        foreach($CorporateArray as $corporate)
                                        {
                                            
                                        ?>
                                        <option value="<?php echo $corporate['ID'];?>">
                                            <?php echo $corporate['CorporateName'];?>
                                        </option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-6 mt-3">
                                    <label>Company Account Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="company_name"
                                        id="company_name" placeholder="Enter Company Name">
                                </div>

                                <div class="col-6 mt-3">
                                    <label>Company Account Email <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="company_email"
                                        id="company_email" placeholder="Enter Company Email">
                                </div>

                                <div class="col-6 mt-3">
                                    <label> Phone Number <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="company_phone"
                                        id="company_phone" placeholder="Enter Phone Number">
                                </div>

                                <div class="col-6 mt-3">
                                    <label>Mobile Number </label>
                                    <input type="number" class="form-control" name="company_mobile"
                                        id="company_mobile" placeholder="Enter Mobile Number">
                                </div>


                                <!--div class="col-6 mt-3">
                                    <label>Branches</label>
                                    <input type="text" class="form-control" name="company_branches"
                                        id="company_branches" placeholder="Enter Branches">
                                </div-->

                                <div class="col-6 mt-3">
                                    <label>Tendor<span class="text-danger">*</span></label>
                                    <select class="select2 form-control" multiple="multiple"
                                        name="company_tendor[]" onchange="AMCSelecte()"
                                        id="company_tendor" class="form-control">
                                        <option value="R&M">R&M</option>
                                        <option value="AMC">AMC</option>
                                        <option value="SUP">SUP</option>
                                    </select>
                                </div>

                                <div class="col-6 mt-3">
                                    <label>R&M TAT ( In Days )</label>
                                    <input type="text" class="form-control" name="company_tat"
                                        id="company_tat" placeholder="Enter R&M TAT">
                                </div>

                                <div class="col-6 mt-3">
                                    <label>PO/WO </label>
                                    <input type="text" class="form-control" name="company_po_wo"
                                        id="company_po_wo" placeholder="Enter PO/WO">
                                </div>
                                <div class="col-6 mt-3">
                                    <label> PO/WO Date </label>
                                    <input type="text" class="form-control" name="company_po_wo_date"
                                        id="company_po_wo_date" placeholder="Pick PO/WO Date">
                                </div>
                                <div class="col-6 mt-3">
                                    <label> Account Manager </label>
                                    <select class="select2 form-control w-100" id="account_manager"
                                        name="account_manager">
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
                                </div>


                            </div>
                            <div class="row mt-3">
                                <div class="col-6 mt-3">
                                    <label> Admin Username</label>
                                    <input type="text" class="form-control" name="admin_username"
                                        id="admin_username" placeholder="Enter Admin Username">
                                </div>

                                <div class="col-6 mt-3" id="corporate_password">
                                    <label>Admin Password </label>
                                    <input type="password" class="form-control" name="admin_password"
                                        id="admin_password" placeholder="Enter Admin Password">
                                </div>
                            </div>
                            <div class="row mt-3">

                            
                                
                                <!-- <div class="col-12 mt-1">
                                    <label> Tickets raised need Approval By Company Admin</label>
                                    <div class="custom-control custom-checkbox custom-control-inline ml-3">
                                        <input type="checkbox" class="custom-control-input" id="need_approval_by_company_admin" name="need_approval_by_company_admin">
                                        <label class="custom-control-label" for="need_approval_by_company_admin">Yes</label>
                                    </div>
                                </div> -->
                                <div class="col-12 mt-1">
                                    <label> Tickets raised needs Whats App message to Corporate Account</label>
                                    <div class="custom-control custom-checkbox custom-control-inline ml-3">
                                        <input type="checkbox" class="custom-control-input" id="need_wa_message" name="need_wa_message">
                                        <label class="custom-control-label" for="need_wa_message">Yes</label>
                                    </div>
                                </div>

                                <div class="col-6 mt-1">
                                       <label>Additional Priorities (seperated by comma)</label>
                                       <input type="text" class="form-control" name="additional_priorities" id="additional_priorities" placeholder="Enter Additional Prioirities if applicable">
                                </div>


                                <div class="col-6 mt-3">
                                    <label>Industry Type </label>
                                    <select class="select2 form-control w-100" id="corporate_industry"
                                        name="corporate_industry">
                                        <option value="-1">Search & Select</option>
                                        <?php

                                        foreach($AllIndustryType as $industry)
                                        {
                                            
                                        ?>
                                        <option value="<?php echo $industry['ID'];?>">
                                            <?php echo $industry['IndustryTypeName'];?>
                                        </option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                                              

                                
                            </div>

                        </div>
                        <input type="hidden" id="form_action" name="form_action" value="add" />
                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                        <button class="btn btn-primary" id="company_btn"
                            onclick="return AddUpdateCompany()">Submit</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

                    <!-- Reset Modal -->

                    <div class="modal fade" id="resetpassword_modal" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Reset Corporate Password</h2>
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
                                                <input type="hidden" name="reset_corporate_id" id="reset_corporate_id"
                                                    value="">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                                                    onclick="ResetPassword()">Save</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Reset Modal -->

                    <!-- Access Modal -->

                    <div class="modal fade" id="set_access_modal" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Set Corporate Access</h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                        <form id="set_access_form">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="password">Username</label>
                                                        <input type="text" name="set_access_username"
                                                            id="set_access_username" class="form-control"
                                                            placeholder="Enter Username">
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="re-password"> Password</label>
                                                        <input type="text" name="set_access_password"
                                                            id="set_access_password" class="form-control"
                                                            placeholder="Enter Password" required>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="set_access_corporate_id"
                                                    id="set_access_corporate_id" value="">
                                                <input type="hidden" name="set_access_phonenumber"
                                                    id="set_access_phonenumber" value="">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                                                    onclick="SetAccess()">Save</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Access Modal -->

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
    <script src="../js/modules/company.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
</body>


</html>