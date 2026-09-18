<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
    <head>

		<?php
		include('../controllers/common_controllers.php');
		include('controller/cfl_controller.php');
		include('../blocks/controller/block_controller.php');


		 $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$EnterpriseID);
		?>
        <meta charset="utf-8">
        <title>
           Profile
        </title>
        <meta name="description" content="Profile">
        <?php
        include('../includes/common_head_content.php');
        ?>
		<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    </head>
	<?php
	$UserType = SessionCheck();

	if(!($UserType == "CFL"))
	{
		header('Location:../authentication/login.php');
	}
	$conn = _connectodb();
	$username = $_SESSION['pb_username'];

	$getClf=getClfProfile($conn,$username);
	$getClf = json_decode($getClf,true);
	foreach ($getClf as $key => $getClfvalue) {
		extract($getClfvalue);
	}
	//GET BLOCK DATA
	$getBlockdata=getAllBlock($conn);
	$getBlockdata = json_decode($getBlockdata,true);



	?>
    <body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
        <!-- DOC: script to save and load page settings -->
         <?php include('../js/theme_settings.js');?>
        <!-- BEGIN Page Wrapper -->

        <div class="page-wrapper">
            <div class="page-inner">
                 <?php
                    if($UserType == "CFL")
                        include('../navigation/cfl_navigation.php');
                ?>
                <div class="page-content-wrapper">
                    <!-- BEGIN Page Header -->
                    <?php
                        include('../includes/common_header.php');
                        $UserId=getUserClfId($conn,$username);
						$UserId = json_decode($UserId,true);

						foreach ($UserId  as $id => $Uservalue) {
							extract($Uservalue);
						}
						$NewUserId=$Uservalue['UserID'];
                    ?>
                    <!-- END Page Header -->
                    <!-- BEGIN Page Content -->
                    <!-- the #js-page-content id is needed for some plugins to initialize -->
                    <main id="js-page-content" role="main" class="page-content">
                        <ol class="breadcrumb page-breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Cholamandalam</a></li>
                            <li class="breadcrumb-item"><a href="profile">Profile</a></li>
                            <li class="breadcrumb-item active">Update Profile</li>

                        </ol>


						<!-- Main Creation Form -->
						<div id="panel-5" class="panel">
							<div class="panel-hdr">
								<h2>
									Update <span class="fw-300"><i>Profile</i></span>
								</h2>
								 <div class="col-md-3 text-white ">
                                  <a data-toggle="modal" class="btn btn-blue" data-target="#example-modal-backdrop-transparent">Reset Password</a>
                                 </div>
							</div>
							<div class="panel-container show">
								<div class="panel-content">
									<div class="panel-tag">
										Update the information below to create the Profile <br>

									</div>
									<form id="enterprise_create_form">

										<div class="row">
											<div class="col-lg-6">
												<div class="form-group">
                                                    <label class="form-label" for="name">Name</label>
                                                    <input type="text" id="name" name="name" value="<?php echo $getClfvalue['Name']; ?>"class="form-control">
                                                </div>
											</div>
											<div class="col-lg-6">
												<div class="form-group">
                                                    <label class="form-label" for="username">Username</label>
                                                    <input type="text" id="username"  value="<?php echo $getClfvalue['Username']; ?>" name="username" class="form-control">
                                                </div>
											</div>
											<div class="col-lg-6">
												<div class="form-group">
                                                    <label class="form-label" for="phonenumber">Phone Number</label>
                                                    <input type="text" id="phonenumber" value="<?php echo $getClfvalue['PhoneNumber']; ?>" name="phonenumber" class="form-control">
                                                </div>
											</div>

											<div class="col-lg-6">
												<div class="form-group">
                                                    <label class="form-label" for="block_id">Block </label>
                                                    <select id="block_id" name="block_id" class="form-control">
                                                    	<option value="0">Select Block</option>
                                                    	<?php
                                                    	foreach ($getBlockdata as $key => $getBlockdatavalue) {
																extract($getBlockdatavalue);
														if($getBlockdatavalue['BlockId']==$getClfvalue['AssociatedBlock']){
														  ?>
														  <option value="<?php echo $BlockId ?>" selected> <?php echo $BlockName ?></option>
														  <?php
														} else { ?>
                                                    	  <option value="<?php echo $BlockId ?>"> <?php echo $BlockName ?></option>
                                                          <?php } } ?>
                                                    </select>

                                                </div>
											</div>
											<div class="col-lg-6">
												<div class="form-group">

                                                    <input type="hidden" id="Role" value="CFL" name="Role" class="form-control">
                                                </div>
											</div>
										</div>


										<input type="hidden" name="clfID" value="<?php echo $ClfID ?>">
										<input type="hidden" name="NewUserId" value="<?php echo $Uservalue['UserID'] ?>">

										<div class="row mt-4">
											<div class="col-lg-12">
												<div class="form-group">
													<button type="button" class="btn btn-success waves-effect waves-themed" style="float:right;" onclick="updateclf()">Update CFL</button>
												</div>
											</div>
										</div>

								</div>
							</div>
						</div>
						<input type="hidden" id="active_search" value="" />


                    </main>

                    <!-- this overlay is activated only when mobile menu is triggered -->
                    <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div> <!-- END Page Content -->
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
        <?php
        include('modal/resetpassword.php');
        ?>
	   <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
	   <script>
        $(document).ready(function() {
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_testimonal").addClass("active");
            $("#nav_site_setting").addClass("active");
        });
    </script>

    </body>


</html>


