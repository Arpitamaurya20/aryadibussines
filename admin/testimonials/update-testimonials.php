<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

	<?php
	include('../controllers/common_controllers.php');
	include('controller/testimonials_controller.php');
	include('../city/controller/city_controller.php');
	 $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
	//$total_projects = getTotatProjects($conn,$EnterpriseID);
	?>
	<meta charset="utf-8">
	<title>
		User Testimonials
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

$gettestimonials = getSingletestimonials($conn, $id);
$gettestimonials = json_decode($gettestimonials, true);
foreach ($gettestimonials as $key => $gettestimonialsvalue) {
	extract($gettestimonialsvalue);
	//print_r($getSchemavalue);
}

?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
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
						<li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
						<li class="breadcrumb-item"><a href="view-testimonials">View Testimonials</a></li>
						<li class="breadcrumb-item active">Update Testimonial</li>

					</ol>


					<!-- Main Creation Form -->
					<div id="panel-5" class="panel">
						<div class="panel-hdr">
							<h2>
								Update <span class="fw-300"><i>Testimonial</i></span>
							</h2>

						</div>
						<div class="panel-container show">
							<div class="panel-content">
								<div class="panel-tag">
									Update the information below <br>

								</div>
								<form id="enterprise_create_form" method="post" action="update_testimonials_action" enctype="multipart/form-data">

									<div class="row">


										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="name">Name</label>
												<input type="hidden" id="txtId" name="txtId" class="form-control" value="<?php echo $id; ?>">
												<input type="text" id="name" name="name" class="form-control" placeholder="Name" value="<?php echo $gettestimonialsvalue['name']; ?>">
											</div>
										</div>

										<div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="Description">Testimonal Description
                                                </label>
                                                <input type="text" id="decs" name="decs" class="form-control"
                                                    value="<?php echo $gettestimonialsvalue['decs']; ?>">
                                            </div>
                                        </div>


										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="image">Image</label>
												<br>
												<?php
												if ($gettestimonialsvalue['image'] != '') { ?>
													<img src="../media/testimonials/<?php echo $gettestimonialsvalue['image']; ?>" style="width: 70px;">
												<?php
												}
												?>

												<br>
												<input type="file" name="image" id="image" class="form-control-file mt-2">

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
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_testimonal").addClass("active");
            $("#nav_site_setting").addClass("active");
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
			$('#txtDescription').summernote({
				"height": 400
			});
		});
	</script>
</body>


</html>