<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

	<?php
	include('../controllers/common_controllers.php');
	$UserType = SessionCheck();
	setNavigation($_SESSION['Roles']);
	include('controller/banners_controller.php');
	$conn = _connectodb();
	$EnterpriseID = "";
	$UserType = SessionCheck();
	//$total_projects = getTotatProjects($conn,$EnterpriseID);
	?>
	<meta charset="utf-8">
	<title>
		Add banners
	</title>
	<meta name="description" content="Add banners">
	<?php
	include('../includes/common_head_content.php');
	?>
	<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
	<style>
		.error {
			color: #fd4f4f;
			margin-top: 10px;
		}
	</style>
</head>
<?php
$UserType = SessionCheck();
$conn = _connectodb();
$username = $_SESSION['pb_username'];
//$getClf=getClfProfile($conn,$username);
//$getClf = json_decode($getClf,true);
//foreach ($getClf as $key => $getClfvalue) {
//extract($getClfvalue);
//print_r($getClfvalue);
//}
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
						<li class="breadcrumb-item"><a href="javascript:void(0);">Aryadibusiness</a></li>
						<li class="breadcrumb-item"><a href="view-banners">View banners </a></li>
						<li class="breadcrumb-item active">Add banners</li>

					</ol>


					<!-- Main Creation Form -->
					<div id="panel-5" class="panel">
						<div class="panel-hdr">
							<h2>
								Add <span class="fw-300"><i>Banners</i></span>
							</h2>
						</div>
						<div class="panel-container show">
							<div class="panel-content">
								<div class="panel-tag">
									Add the information below to create the banners <br>
								</div>
								<form id="enterprise_create_form" name="bannersForm" method="post" action="action/create_banners_action" enctype="multipart/form-data">
									<div class="row">

										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="image">Banner</label>
												<input type="file" id="banner" name="banner" class="form-control" required>
											</div>
										</div>
									</div>

									<div class="row mt-4">
										<div class="col-lg-12">
											<div class="form-group">
												<button type="submit" class="btn btn-blue" style="float:right;">Create</button>
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
	<script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js"></script>
	<script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.js"></script>
	<script>
        $(document).ready(function() {
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_banners").addClass("active");
			$("#nav_site_setting").addClass("active");
        });
    </script>


	<script>
		$("#enterprise_create_form").validate({
			rules: {
				banner: {
					required: true,
					extension: "jpg|jpeg|png|ico|bmp|webp"
				}
			},
			messages: {

				banner: {
					required: 'Please choose image.',
					extension: 'Please choose valid image.',
				}
			},
			submitHandler: function(form) {
				form.submit();
			}
		});
	</script>
</body>


</html>