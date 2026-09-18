<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php
	include('../controllers/common_controllers.php');
	include('controller/reviews_controller.php');
	$UserType = SessionCheck();
	setNavigation($_SESSION['Roles']);
	$conn = _connectodb();
	$EnterpriseID = "";
	?>
	<meta charset="utf-8">
	<title>
		Add Review
	</title>
	<meta name="description" content="Create CFL  ">
	<?php
	include('../includes/common_head_content.php');
	?>
	<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
	<link rel="stylesheet" media="screen, print" href="../css/formplugins/dropzone/dropzone.css">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<?php
$UserType = SessionCheck();
$conn = _connectodb();
$username = $_SESSION['pb_username'];
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
						<li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
						<li class="breadcrumb-item"><a href="view-reviews">Reviews</a></li>
						<li class="breadcrumb-item active">Add </li>

					</ol>

					<!-- Main Creation Form -->
					<div id="panel-5" class="panel">
						<div class="panel-hdr">
							<h2>
								Add <span class="fw-300"><i>Review</i></span>
							</h2>

						</div>
						<div class="panel-container show">
							<div class="panel-content">
								<form id="enterprise_create_form" method="post" action="action/create_reviews_action" enctype="multipart/form-data">
									<div class="row">

										<div class="col-lg-4">
											<div class="form-group">
												<label class="form-label" for="name">Service</label>
												<select name="service_id" id="" class="form-control select2">
													<option value="">Select Service</option>
													<?php
													$serviceQuery = "select * from services";
													$sel = mysqli_query($conn, $serviceQuery);
													while ($Servicerow = mysqli_fetch_array($sel)) {
													?>
														<option value="<?php echo $Servicerow['ID'];; ?>"><?php echo $Servicerow['Name']; ?></option>
													<?php
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-lg-4">
											<div class="form-group">
												<label class="form-label" for="name">Random Page</label>
												<select name="random_page" id="" class="form-control select2">
													<option value="">Select Random Page</option>
													<?php
													$randomQuery = "select * from random_page";
													$Randomsel = mysqli_query($conn, $randomQuery);
													while ($RandomRow = mysqli_fetch_array($Randomsel)) {
													?>
														<option value="<?php echo $RandomRow['ID'];; ?>"><?php echo $RandomRow['url']; ?></option>
													<?php
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-lg-4">
											<div class="form-group">
												<label class="form-label" for="name">Location Service</label>
												<select name="location_service" id="" class="form-control select2">
													<option value="">Select Location Service</option>
													<?php
													$randomQuery = "select * from location_services";
													$locationsel = mysqli_query($conn, $randomQuery);
													while ($locationRow = mysqli_fetch_array($locationsel)) {
													?>
														<option value="<?php echo $locationRow['id'];; ?>"><?php echo $locationRow['title']; ?></option>
													<?php
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-lg-4">
											<div class="form-group">
												<label class="form-label" for="name">Client Name</label>
												<input type="text" id="client_name" name="client_name" class="form-control" placeholder="Client Name" required>
											</div>
										</div>
										<div class="col-lg-5">
											<div class="form-group">
												<label class="form-label" for="name">Image (85 × 92 px)</label>
												<input type="file" id="client_image" name="client_image" class="form-control" required onchange="validateimg(this)">
											</div>
										</div>
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="name">Review</label>
												<textarea id="review" name="review" class="form-control" placeholder="Review" required></textarea>
											</div>
										</div>
									</div>

									<div class="row mt-4">
										<div class="col-lg-12">
											<div class="form-group">
												<button type="submit" name="Submit" class="btn btn-primary">Create</button>
											</div>
										</div>
									</div>
								</form>
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
	<script src="../js/formplugins/dropzone/dropzone.js"></script>
	<script>
        $(document).ready(function() {
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_review").addClass("active");
			$("#nav_site_setting").addClass("active");
        });
    </script>

	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
	<script>
		$(document).ready(function() {
			$('#txtDescription').summernote({
				"height": 400
			});
		});
	</script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
		$(document).ready(function() {
			$('.select2').select2();
		});
	</script>




<script>
	function validateimg(ctrl) {
    var fileUpload = $("#client_image")[0];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(.jpg|.jpeg|.png|.gif|.webp)$");
    if (regex.test(fileUpload.value.toLowerCase())) {
        if (typeof (fileUpload.files) != "undefined") {
            var reader = new FileReader();
            reader.readAsDataURL(fileUpload.files[0]);
            reader.onload = function (e) {
                var image = new Image();
                image.src = e.target.result;
                image.onload = function () {
                    var height = this.height;
                    var width = this.width;
                    console.log(this);
                    if ((height == 100) && (width == 100 )) {
                        return true;
                    }
                    alert("Height and Width must not exceed 100*100.");
					$('#client_image').val('');
                    return false;
                };
            }
        } else {
            alert("This browser does not support HTML5.");
            return false;
        }
    } else {
        alert("Please select a valid Image file.");
        return false;
    }
}
</script>
</body>


</html>