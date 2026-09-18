<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

	<?php
	include('../controllers/common_controllers.php');
	include('../city/controller/city_controller.php');
	include('controller/random-page-controller.php');
	include('../Services/controller/service_controller.php');
	$conn = _connectodb();

	$UserType = SessionCheck();
	//$total_projects = getTotatProjects($conn,$EnterpriseID);
	?>
	<meta charset="utf-8">
	<title>
		Update Random Page
	</title>
	<meta name="description" content="Update Random Page">
	<?php
	include('../includes/common_head_content.php');
	?>
	<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<?php
$UserType = SessionCheck();

if (!($UserType == "Admin")) {
	header('Location:../authentication/login.php');
}
$conn = _connectodb();
$username = $_SESSION['pb_username'];
$ID = $_GET['ID'];
$getCitydata = getAllCity($conn);
$getCitydata = json_decode($getCitydata, true);

$getAllServices = getAllServices($conn);
$getAllServices = json_decode($getAllServices, true);

$getService = getSingleRandomPage($conn, $ID);
$getService = json_decode($getService, true);
foreach ($getService as $key => $getServicevalue) {
	extract($getServicevalue);
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
				?>
				<main id="js-page-content" role="main" class="page-content">
					<ol class="breadcrumb page-breadcrumb">
						<li class="breadcrumb-item"><a href="javascript:void(0);">TechXpert</a></li>
						<li class="breadcrumb-item"><a href="view-random-page.php">View Random Pages</a></li>
						<li class="breadcrumb-item active">Update</li>
					</ol>
					<!-- Main Creation Form -->
					<div id="panel-5" class="panel">
						<div class="panel-hdr">
							<h2>
								Update <span class="fw-300"><i>Service</i></span>
							</h2>
						</div>
						<div class="panel-container show">
							<div class="panel-content">
								<div class="panel-tag">
									Update the information below <br>

								</div>
								<form id="enterprise_create_form" method="post" action="action/update-random-page.php" enctype="multipart/form-data">
									<input type="hidden" name="ID" value="<?php echo $ID ?>">
									<div class="row">
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="title">Title</label>
												<input type="text" id="title" name="title" class="form-control" placeholder="Title" required value="<?php echo $title ?>">
											</div>
										</div>
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="meta_title">Meta Title</label>
												<input type="text" id="meta_title" name="meta_title" class="form-control" placeholder="Meta Title" required value="<?php echo $meta_title ?>">
											</div>
										</div>
									</div>
									<div class="row">
									<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="url">Url</label>
												<input type="text" id="url" name="url" class="form-control" placeholder="URL" required value="<?php echo $url ?>">
											</div>
										</div>
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="meta_keyword">Meta Keyword</label>
												<input type="text" id="meta_keyword" name="meta_keyword" class="form-control" placeholder="Meta Keyword" value="<?php echo $meta_keyword ?>">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="meta_description">Meta Description</label>
												<textarea id="meta_description" name="meta_description" class="form-control" rows="4" cols="5" required><?php echo $meta_description ?></textarea>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="description">Description</label>
												<textarea id="description" name="description" class="form-control" required><?php echo $description ?></textarea>
											</div>
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
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
		$(document).ready(function() {
			$('.select2').select2();
		});
	</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.2.0/tinymce.min.js" integrity="sha256-u2athPw1LMXR9Wx/7pt5l4LtyirEcmRCEPZdtLltAJo=" crossorigin="anonymous"></script>
	<script>
		tinymce.init({
			selector: 'textarea[name=description]',

			plugins: [
				'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
				'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
				'table emoticons template paste help'
			],
			toolbar: 'undo redo | styleselect| fontselect  | bold italic | alignleft aligncenter alignright alignjustify |' +
				' bullist numlist outdent indent | link image | print preview media fullpage | ' +
				'forecolor backcolor emoticons | help',
			menubar: 'file edit view insert format tools table help',
			font_formats: "Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Oswald=oswald; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats",
			content_style: "@import url('https://fonts.googleapis.com/css2?family=Oswald&display=swap'); body { font-family: Oswald; }",
			height: 500
		});
	</script>
</body>


</html>