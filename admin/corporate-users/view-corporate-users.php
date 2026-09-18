<?php @session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/corporate_users_controller.php');
        include('../includes/autoloader.inc.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
		$conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    
    <meta name="description" content="Corporate Users Management - Aryadibusiness">
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
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    $corporate_users = getAllCorporateUsers($conn,$CorporateID);
    $ProductName = "Aryadibusiness";
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
        Corporate Users Management - <?=$ProductName;?>
    </title>
    <?php 
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    ?>
</head>


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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">User Management </li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        User Management</span>
                                    </h2>
                                    <a onclick="OpenCorporateUserModal()" class="btn btn-info text-white"
                                        style="margin-right:20px;">Add User</a>   
                                    
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-corporate-users"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Phonenumber</th>
                                                    <th>Email</th>
                                                    <!-- <th>Approval Range</th> -->
                                                    <th>Update </th>
                                                    <!-- <th>Delete</th> -->
                                                    <th>Access</th>
                                                   
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i=1;
                                                foreach($corporate_users as $corporate_user)
                                                {
                                                    extract($corporate_user);
                                                    echo "<tr>";
                                                    echo "<td>".$i."</td>";
                                                    echo "<td>".$Name."</td>";
                                                    echo "<td>".$Phonenumber."</td>";
                                                    echo "<td>".$Email."</td>";
                                                    /*echo "<td>&#8377;".$ApprovalMinRange." - &#8377;".$ApprovalMaxRange."</td>";*/
                                                    echo "<td><a onclick='UpdateCorporateUser($ID)'
                                                            class='cursor-pointer'><i class='fal fa-edit'
                                                                aria-hidden='true'></i></a></td>";
                                                    /*echo "<td><a onclick='DeleteCorporateUser($ID)'><i
                                                                class='fal fa-trash' aria-hidden='true'></i></a></td>";*/
                                                    echo "<td><span class='badge badge-primary cursor-pointer' onclick='ResetCorporatePass($ID)'>Reset Password</span></td>";            
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


     <!-- Modal -->
    <div class="modal fade" id="corporate_user_modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title" id="corporate_user_heading">Corpoate User Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="add_update_corporate_user_form">

                        <div class="row">

                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="message">Name</label>
                                        <br>
                                        <input type="text" class="form-control" name="corporate_user_name" id="corporate_user_name">
                                    </div>
                                    
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="message">Phonenumber</label>
                                        <br>
                                        <input type="text" class="form-control" name="corporate_user_phonenumber" id="corporate_user_phonenumber">
                                    </div>
                                    
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="message">Email (Will be used as username)</label>
                                        <br>
                                        <input type="text" class="form-control" name="corporate_user_email" id="corporate_user_email">
                                    </div>
                                    
                                </div>
                                <div class="col-6" id="password_col">
                                    <div class="form-group">
                                        <label for="message">Password </label>
                                        <br>
                                        <input type="text" class="form-control" name="corporate_user_password" id="corporate_user_password">
                                    </div>
                                    
                                </div>

                                <!--div class="col-6">
                                    <div class="form-group">
                                        <label for="message">Approval Cost (Minimum)</label>
                                        <br>
                                        <input type="text" class="form-control" name="corporate_user_approval_minimum" id="corporate_user_approval_minimum" onkeydown="return isNumber(event)">
                                    </div>
                                    
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="message">Approval Cost (Maximum)</label>
                                        <br>
                                        <input type="text" class="form-control" name="corporate_user_approval_maximum" id="corporate_user_approval_maximum">
                                    </div>
                                    
                                </div-->
                                <div class="col-12">
                                   <div class="form-group">
                                    <input type="hidden" name="form_id" id="form_id" value="">
                                    <input type="hidden" name="form_action" id="form_action" value="add">
                                    <input type="hidden" name="CorporateID" id="CorporateID" value="<?php echo $CorporateID;?>">
                                    <a onclick="return AddUpdateCorporateUser()" id="corporate_user_modal_submit_text" class="btn btn-info text-white">Save</a>
                                </div> 
                                </div>
                                
                        </div>

                        

                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- reset passord  -->

    <div class="modal fade" id="resetpassword_modal" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Reset Corporate User Password</h2>
                                    </div>
                                    <button type="button" style="opacity:1; color:#fff;" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                        <form id="reset_corp_user_password_form">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="password">Password</label>
                                                        <input type="text" name="reset_corporate_passsword" id="reset_corporate_passsword"
                                                            class="form-control" placeholder="Enter Password">
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="re-password">Re-type
                                                            Password</label>
                                                        <input type="text" name="reset_corporate_confirm_passsword"
                                                            id="reset_corporate_confirm_passsword" class="form-control"
                                                            placeholder="Re-type Password" required>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="reset_corporate_id" id="reset_corporate_id"
                                                    value="">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="form_btn bg-primary form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                                                    onclick="ResetCorporateUserPassword()" id="corp_user_btn">Save</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/corporate-users.js"></script>

</body>


</html>