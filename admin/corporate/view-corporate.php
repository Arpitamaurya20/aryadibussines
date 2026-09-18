<?php @session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/corporate_controller.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
        $conn = _connectodb();

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

    $CorporatesArray = getAllCorporate($conn);

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
                        <li class="breadcrumb-item active">Company HQ </li>

                    </ol>
                    <?php if($UserType=="Admin"||"Sub Admin") { ?>
                    <a href="#" onclick="DownloadCorporateFilesFormat(); return false;" class="btn btn-success" 
                            style="margin-right:20px;">Download Template for Bulk Upload</a><?php } ?>
                        </div>
                        


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2> View Company HQ </h2>
                                    <?php if($UserType=="Admin"||"Sub Admin") { ?>
                                    <a href="#" onclick="UploadCompanyCsv()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV Data</a>
                                    <?php } ?>
                                    <a href="#" onclick="opencorporate_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Company</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-corporate"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Company HQ Name </th>
                                                    <th>Company GST</th>
                                                    <th>Company Address </th>
                                                    <th>Companies</th>
                                                    <th>Update</th>
                                                    <th>Delete</th>
                                                    <th>Access</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($CorporatesArray as $Corporate)
                                                	{

                                                	   $ID  = $Corporate['ID'];
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $Corporate['CorporateName']; ?></td>
                                                    <td><?php echo $Corporate['CorporateGST']; ?></td>
                                                    <td>
                                                        <?php
                                                            if($Corporate['CoporateAddress'] == -1)
                                                                echo "Not Set" ;
                                                            else
                                                            {
                                                                echo $Corporate['CoporateAddress'];
                                                            }
                                                        ?>
                                                    </td>
                                                     <td>

                                                        <a onclick="ViewCompanyAccounts('<?php echo $ID;?>')">
                                                            <span class="badge badge-primary cursor-pointer">View
                                                                Company Accounts</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a onclick="UpdateCorporate_modal('<?php echo $ID;?>')"
                                                            class="cursor-pointer"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                    </td>
                                                    <td>
                                                        <a onclick="DeleteCorporate('<?php echo $ID;?>')"><i
                                                                class="fal fa-trash" aria-hidden="true"></i>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if($Corporate['Access'] == 1)
                                                        {
                                                        ?>

                                                        <a onclick="OpenResetPasswordModalCorporate(<?php echo $ID;?>)">
                                                            <span class="badge badge-primary cursor-pointer">Reset Password</span>
                                                        </a>
                                                        <?php
                                                        }
                                                        else
                                                        {
                                                            ?>
                                                        <a onclick="OpenSetAccessModalCorporate(<?php echo $ID;?>)"
                                                            class="cursor-pointer">
                                                            <span class="badge badge-primary cursor-pointer">Set Access</span>
                                                        </a>
                                                        <?php
                                                        }
                                                        ?>
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
                    <div class="modal fade" id="add_edit_corporate_modal"  role="dialog"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="corporate_heading">Add Company HQ </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_corporate_form">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label for="corporate_name">Company HQ Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="corporate_name"
                                                        id="corporate_name" placeholder="Enter Company Name">
                                                </div>

                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="corporate_gst">Company GST N0. <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="corporate_gst"
                                                        id="corporate_gst" placeholder="Enter Company GST N0.">
                                                </div>

                                            </div>


                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="corporate_address">Company Address </label> <br>
                                                    <textarea class="form-control w-100" name="corporate_address" id="corporate_address" cols="30" rows="5" placeholder="Enter Company Address"></textarea>
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="corporate_username">Company Username</label> <br>
                                                    <input type="text" class="form-control" name="corporate_username"
                                                        id="corporate_username" placeholder="Enter Company Username.">
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="corporate_password">Company Password</label> <br>
                                                    <input type="text" class="form-control" name="corporate_password"
                                                        id="corporate_password" placeholder="Enter Company Password">
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button id="company_btn" class="btn btn-primary"
                                            onclick="return AddUpdateCorporate()">Submit</button>
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

    <!-- company csv uploader  -->

    <div class="modal fade" id="upload_company_csv" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Upload Company ARC CSV </h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                       <form id="uplaod_company_arc_csv" method="POST" action="action/importcsv.php" enctype="multipart/form-data">
                                            <input type="file" class="form-control" name="csvFile" id="csvFile" accept=".csv">
                                            

                                            <div class="row justify-content-center mt-3">

                                                <!-- <input class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" type="submit" value="Upload" onclick="UploadCompanyARC_CSV()" id="upload_csv_btn"> -->

                                                <button class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="upload_csv_btn"
                                                    onclick="UploadCompanyARC_CSV()">Upload</button> 

                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div> 

                    <!-- Reset Modal -->

                    <div class="modal fade" id="resetpassword_modal_company" tabindex="-1" role="dialog"
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
                                    <div class="reset_password_company">
                                        <form id="reset_password_form_company">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="password">Password</label>
                                                        <input type="text" name="reset_passsword_company" id="reset_passsword_company"
                                                            class="form-control" placeholder="Enter Password">
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="re-password">Re-type
                                                            Password</label>
                                                        <input type="text" name="confirm_passsword_company"
                                                            id="confirm_passsword_company" class="form-control"
                                                            placeholder="Re-type Password" required>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="reset_company_id" id="reset_company_id"
                                                    value="">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="form_btn btn-primary form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                                                    onclick="ResetPasswordCorporate()">Save</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- Reset Modal -->

                    <!-- Access Modal -->

                    <div class="modal fade" id="set_access_modal_company" tabindex="-1" role="dialog"
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
                                    <div class="reset_password_company">
                                        <form id="set_access_form_company">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="password">Username</label>
                                                        <input type="text" name="set_access_username_company"
                                                            id="set_access_username_company" class="form-control"
                                                            placeholder="Enter Username">
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="re-password"> Password</label>
                                                        <input type="text" name="set_access_password_company"
                                                            id="set_access_password_company" class="form-control"
                                                            placeholder="Enter Password" required>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="set_access_company_id"
                                                    id="set_access_company_id" value="">
                                                <input type="hidden" name="set_access_phonenumber"
                                                    id="set_access_phonenumber" value="">
                                            </div>                                            

                                            <div class="row justify-content-center mt-3">

                                                <a class="form_btn btn-primary form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                                                    onclick="SetAccessCorporate()">Save</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Access Modal -->


    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');

       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/corporate.js"></script>


</body>


</html>