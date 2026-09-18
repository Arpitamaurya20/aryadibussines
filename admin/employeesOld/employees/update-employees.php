<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

	<?php
	include('../controllers/common_controllers.php');
	include('controller/reviews_controller.php');
	include('../city/controller/city_controller.php');
	$conn = _connectodb();

	$UserType = SessionCheck();
	//$total_projects = getTotatProjects($conn,$EnterpriseID);
	?>
	<meta charset="utf-8">
	<title>
		Update Employees
	</title>
	<meta name="description" content="Update Schema">
	<?php
	include('../includes/common_head_content.php');
	?>
	<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
</head>
<?php
$UserType = SessionCheck();

if (!($UserType == "Admin")) {
	header('Location:../authentication/login.php');
}
$conn = _connectodb();
$username = $_SESSION['pb_username'];
$id = $_GET['id'];

$getreviews = getSinglereviews($conn, $id);
$getreviews = json_decode($getreviews, true);
foreach ($getreviews as $key => $getreviewsvalue) {
	extract($getreviewsvalue);
	//print_r($getSchemavalue);
}
include('../Services/controller/service_controller.php');
$getAllServices = getAllServices($conn);
$getAllServices = json_decode($getAllServices, true);



include('../random-page/controller/random-page-controller.php');
$getAllrandomPage = getAllrandomPage($conn);
$getAllrandomPage = json_decode($getAllrandomPage, true);


include('../location-service/controller/location_service_controller.php');
$getAlllocation_service = getAlllocation_service($conn);
$getAlllocation_service = json_decode($getAlllocation_service, true);
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<!-- DOC: script to save and load page settings -->
	<?php include('../js/theme_settings.js'); ?>
	<!-- BEGIN Page Wrapper -->

	<div class="page-wrapper">
		<div class="page-inner">
			<?php
			if ($UserType == "Admin")
				include('../navigation/admin_navigation.php');
			?>
			<div class="page-content-wrapper">
				<!-- BEGIN Page Header -->
				<?php
				include('../includes/common_header.php');
				//$UserId=getUserClfId($conn,$getSchemavalue['Username']);
				//$UserId = json_decode($UserId,true);

				//foreach ($UserId  as $id => $Uservalue) {
				//	extract($Uservalue);
				//	}
				//	$NewUserId=$Uservalue['UserID'];
				?>
				<!-- END Page Header -->
				<!-- BEGIN Page Content -->
				<!-- the #js-page-content id is needed for some plugins to initialize -->
				<main id="js-page-content" role="main" class="page-content">
					<ol class="breadcrumb page-breadcrumb">
						<li class="breadcrumb-item"><a href="javascript:void(0);">TechXpert</a></li>
						<li class="breadcrumb-item"><a href="view-employees.php">Employees</a></li>
						<li class="breadcrumb-item active">Update Employees</li>

					</ol>


					<!-- Main Creation Form -->
					<div id="panel-5" class="panel">
						<div class="panel-hdr">
							<h2>
								Update <span class="fw-300"><i>Employees</i></span>
							</h2>

						</div>
						<div class="panel-container show">
							<div class="panel-content">
								<div class="panel-tag">
									Update the information below <br>

								</div>
								<form id="enterprise_create_form" method="post" action="action/update_reviews_action.php" enctype="multipart/form-data">

									<div class="row">
										<div class="col-lg-4">
											<div class="form-group">
												<label class="form-label" for="name">Service</label>
												<input type="hidden" id="txtId" name="txtId" class="form-control" value="<?php echo $ID; ?>">
												<select name="service_id" id="" class="form-control select2">
												    <option value="">None</option>
													<?php
													foreach ($getAllServices as $key => $getAllServices) {
														extract($getAllServices);
													?>
														<option <?php if ($service_id == $getAllServices['ID']) {
																	echo "selected";
																} ?> value="<?php echo $ID ?>"> <?php echo $Name ?></option>

													<?php }  ?>


												</select>
											</div>
										</div>

										<div class="col-lg-4">
											<div class="form-group">
												<label class="form-label" for="name">Random Page</label>

												<select name="random_page" id="" class="form-control select2">
												    <option value="">None</option>
													<?php
													foreach ($getAllrandomPage as $key => $getAllrandomPage) {
														extract($getAllrandomPage);
													?>
														<option <?php if ($random_page == $getAllrandomPage['ID']) {
																	echo "selected";
																} ?> value="<?php echo $ID ?>"> <?php echo $title ?></option>

													<?php }  ?>


												</select>
											</div>
										</div>
										<div class="col-lg-4">
											<div class="form-group">
												<label class="form-label" for="name">Location Service</label>

												<select name="location_service" id="" class="form-control select2">
												    <option value="">None</option>
													<?php
													foreach ($getAlllocation_service as $key => $getAlllocation_service) {
														extract($getAlllocation_service);
													?>
														<option <?php if ($location_service == $getAlllocation_service['id']) {
																	echo "selected";
																} ?> value="<?php echo $id ?>"> <?php echo $title ?></option>

													<?php }  ?>


												</select>
											</div>
										</div>
										<div class="col-lg-3">
											<div class="form-group">
												<label class="form-label" for="name">Client Name</label>

												<input type="text" id="client_name" name="client_name" required class="form-control" placeholder="Name" value="<?php echo $getreviewsvalue['client_name']; ?>">
											</div>
										</div>



										<div class="col-lg-5">
											<div class="form-group">
												<label class="form-label" for="image">Client Image</label>
												<br>
												<?php
												if ($getreviewsvalue['client_image'] != '') { ?>
													<img src="../media/reviews/<?php echo $getreviewsvalue['client_image']; ?>" style="width: 70px;">
												<?php
												}
												?>
												<br>
												<input type="file" name="client_image" id="client_image" class="form-control-file mt-2" onchange="validateimg(this)">

											</div>
										</div>
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="name">Review</label>
												<textarea name="review" id="review" class="form-control mt-2"><?php echo $getreviewsvalue['review']; ?></textarea>
											</div>
										</div>

										<div class="row mt-4">
											<div class="col-lg-12">
												<div class="form-group">
													<button type="submit" name="Submit" class="btn btn-primary">Update</button>
												</div>
											</div>
										</div>

									</div>

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
	<script>
		$(document).ready(function() {
			$("#nav_schema").addClass("active");
			$("#nav_schema").addClass("open");
			$("#nav_view_schema").addClass("active");
		});
	</script>
	<script>
		function updateSchema() {
			var title = document.getElementById("title").value;
			if (title == "") {
				noboAlert("Please Enter Title");
				return false;
			}
			var description = document.getElementById("description").value;
			if (description == "") {
				noboAlert("Please Enter Description");
				return false;
			}

			var shortdescription = document.getElementById("shortdescription").value;
			if (shortdescription == "") {
				noboAlert("Please Enter Shortdescription");
				return false;
			}

			var formData = new FormData(document.querySelector('form'));

			$.ajax({
				url: "action/update_schema_action.php",
				type: 'POST',
				data: formData,
				async: false,
				success: function(data) {
					//alert(data);
					var response = JSON.parse(data);
					if (response.error == true) {
						alert(response.message);
					} else {
						alert(response.message)
						BasicURLRouter('../schema/view-schema.php');
					}

				},
				cache: false,
				contentType: false,
				processData: false
			});

			return false;
		}
	</script>
	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
	<script>
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_employees").addClass("active");
    });
    </script>
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
				if (typeof(fileUpload.files) != "undefined") {
					var reader = new FileReader();
					reader.readAsDataURL(fileUpload.files[0]);
					reader.onload = function(e) {
						var image = new Image();
						image.src = e.target.result;
						image.onload = function() {
							var height = this.height;
							var width = this.width;
							console.log(this);
							if ((height == 100) && (width == 100)) {
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