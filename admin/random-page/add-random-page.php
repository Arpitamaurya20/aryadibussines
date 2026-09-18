<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<?php
	include('../controllers/common_controllers.php');
	include('../city/controller/city_controller.php');
	include('../Services/controller/service_controller.php');
	$conn = _connectodb();
	$EnterpriseID = "";
	$UserType = SessionCheck();
	$getCitydata = getAllCity($conn);
	$getCitydata = json_decode($getCitydata, true);

	$getAllServices = getAllServices($conn);
	$getAllServices = json_decode($getAllServices, true);
	?>
	<meta charset="utf-8">
	<title>
		Add Random Page
	</title>
	<meta name="description" content="Create CFL  ">
	<?php
	include('../includes/common_head_content.php');
	?>
	<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
	<link rel="stylesheet" media="screen, print" href="../css/formplugins/dropzone/dropzone.css">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<style>
		#faqBox,
		#subserviceBox,
		#brandBox,
		#keywordbox {
			background: #f4f4f442;
			padding: 25px;
			margin: 5px;
		}

		.divider {
			border-bottom: 1px solid #e1e1e1;
			margin-bottom: 25px;
		}
	</style>
</head>
<?php
$UserType = SessionCheck();

if (!($UserType == "Admin")) {
	header('Location:../authentication/login.php');
}
$conn = _connectodb();
$username = $_SESSION['pb_username'];
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
	<!-- DOC: script to save and load page settings -->
	<?php include('../js/theme_settings.js'); ?>
	<!-- BEGIN Page Wrapper -->
	<script>
		function remove_more(id) {
			alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
				$('#box' + id).fadeOut(600, function() {
					$('#box' + id).remove();
				});
			}, function() {
				alertify.error('Cancel')
			});
		}

		function remove_brand(id) {

			alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
				$('#brandbox' + id).fadeOut(600, function() {
					$('#brandbox' + id).remove();
				});
			}, function() {
				alertify.error('Cancel')
			});


		}

		function remove_subservice(id) {
			alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
				$('#subservicebox' + id).fadeOut(600, function() {
					$('#subservicebox' + id).remove();
				});
			}, function() {
				alertify.error('Cancel')
			});
		}
		function remove_keyword(id) {
			alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
				$('#keywordbox' + id).fadeOut(600, function() {
					$('#keywordbox' + id).remove();
				});
			}, function() {
				alertify.error('Cancel')
			});
		}
	</script>
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
				<!-- END Page Header -->
				<!-- BEGIN Page Content -->
				<!-- the #js-page-content id is needed for some plugins to initialize -->
				<main id="js-page-content" role="main" class="page-content">
					<ol class="breadcrumb page-breadcrumb">
						<li class="breadcrumb-item"><a href="javascript:void(0);">Aryadibusiness</a></li>
						<li class="breadcrumb-item"><a href="view-random-page">View Random Pages</a></li>
						<li class="breadcrumb-item active">Add </li>

					</ol>

					<!-- Main Creation Form -->
					<div id="panel-5" class="panel">
						<div class="panel-hdr">
							<h2>
								Add <span class="fw-300"><i>Random Page</i></span>
							</h2>

						</div>
						<div class="panel-container show">
							<div class="panel-content">

								<form id="enterprise_create_form" method="post" action="action/add-random-page" enctype="multipart/form-data">

									<div class="row">
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="title">Title</label>
												<input type="text" id="title" name="title" class="form-control" placeholder="Title" required>
											</div>
										</div>
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="meta_title">Meta Title</label>
												<input type="text" id="meta_title" name="meta_title" class="form-control" placeholder="Meta Title" required>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="url">Url</label>
												<input type="text" id="url" name="url" class="form-control" placeholder="URL" required>
												<?php
												if (isset($_SESSION['randomExist'])) {
													echo '<span class="text-danger">' . $_SESSION["randomExist"] . '</span>';
												}
												unset($_SESSION['randomExist']);
												?>
												<span id="urlExist" class="text-danger"></span>
												<span id="urlYES" class="text-success"></span>
											</div>
										</div>
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="meta_keyword">Meta Keyword</label>
												<input type="text" id="meta_keyword" name="meta_keyword" class="form-control" placeholder="Meta Keyword" required>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="meta_description">Meta Description</label>
												<textarea id="meta_description" name="meta_description" class="form-control" rows="4" cols="5" required></textarea>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="color">Display Safety</label>
												<input type="radio" value="1" name="safety" style="margin-left: 20px;margin-right: 8px;" checked>Yes
												<input type="radio" value="0" name="safety" style="margin-left: 20px;margin-right: 8px;" id="">No

											</div>
										</div>
										<div class="col-lg-6">
											<div class="form-group">
												<label class="form-label mr-3" for="color">Safety Image (650*380) </label>
												<input type="file" name="safety_img" id="safety_img">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="Image">Box Main Heading</label>

												<input type="text" id="mainheading" name="mainheading" class="form-control">

											</div>
										</div>
									</div>

									<div class="row  bg-primary text-white pt-2">
										<div class="col-md-8">
											<h3>Descripton Box 1 </h3>
										</div>
										<div class="col-md-4"></div>
									</div>
									<div class="row mt-3">
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="Password">Box 1 Heading</label>
												<input type="text" name="desc_box_h1" class="form-control">
											</div>
										</div>
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="Password">Box 1 Description</label>
												<textarea id="desc_box_h1_desc" name="desc_box_h1_desc" class="form-control"></textarea>
											</div>
										</div>

									</div>

									<div class="row  bg-primary text-white pt-2">
										<div class="col-md-8">
											<h3>Descripton Box 2 </h3>
										</div>
										<div class="col-md-4"></div>
									</div>
									<div class="row mt-3">
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="Password">Box 2 Heading</label>
												<input type="text" name="desc_box_h2" class="form-control">
											</div>
										</div>
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="Password">Box 2 Description</label>
												<textarea id="desc_box_h2_desc" name="desc_box_h2_desc" class="form-control"></textarea>
											</div>
										</div>

									</div>

									<div class="row  bg-primary text-white pt-2">
										<div class="col-md-8">
											<h3>Descripton Box 3 </h3>
										</div>
										<div class="col-md-4"></div>
									</div>
									<div class="row mt-3">
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="Password">Box 3 Heading</label>
												<input type="text" name="desc_box_h3" class="form-control">
											</div>
										</div>
										<div class="col-lg-12">
											<div class="form-group">
												<label class="form-label" for="Password">Box 3 Description</label>
												<textarea id="desc_box_h3_desc" name="desc_box_h3_desc" class="form-control"></textarea>
											</div>
										</div>

									</div>




									<div class="row  bg-primary text-white pt-2">
										<div class="col-md-8">
											<h3>Sub-Service </h3>
										</div>
										<div class="col-md-4"></div>
									</div>
									<div class="form-group row mt-3">
										<label for="fname" class="col-sm-3 control-label col-form-label">Heading to Display</label>
										<div class="col-sm-9">
											<input type="text" class="form-control" name="subservice_heading" id="subservice_heading">
										</div>
									</div>
									<hr>
									<div id="subserviceBox" class="mb-5">
										<div class="form-group row">
											<label for="fname" class="col-sm-3 control-label col-form-label">Sub-Service</label>
											<div class="col-sm-7">
												<input type="text" class="form-control" name="subservice[]" id="subservice">
											</div>
											<div class="col-sm-2">
												<button type="button" id="Addsubservice" class="btn btn-warning btn-sm float-right"><i class="fa fa-plus"></i></button>
											</div>
										</div>

									</div>

									<div class="row  bg-primary text-white pt-2">
										<div class="col-md-8">
											<h3>Brand </h3>
										</div>
										<div class="col-md-4"></div>
									</div>
									<div class="form-group row mt-3">
										<label for="fname" class="col-sm-3 control-label col-form-label">Heading to Display</label>
										<div class="col-sm-9">
											<input type="text" class="form-control" name="brand_heading" id="brand_heading">
										</div>
									</div>
									<hr>
									<div id="brandBox" class="mb-5">
										<div class="form-group row">
											<label for="fname" class="col-sm-3 control-label col-form-label">Brand</label>
											<div class="col-sm-7">
												<input type="text" class="form-control" name="brand_title[]" id="brand_title">
											</div>
											<div class="col-sm-2">
												<button type="button" id="AddBrand" class="btn btn-warning btn-sm float-right"><i class="fa fa-plus"></i></button>
											</div>
										</div>
										<div class="form-group row">
											<label for="fname" class="col-sm-3 control-label col-form-label">Image (60*60 px)</label>
											<div class="col-sm-9">
												<input type="file" class="form-control brand_image" name="brand_image[]" id="brand_image" onchange="validateimg(this)" multiple>
											</div>
										</div>
										<div class="form-group row">
											<label for="fname" class="col-sm-3 control-label col-form-label">URL</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="brand_url[]" id="brand_url">
											</div>
										</div>
									</div>


									<div class="row bg-primary text-white pt-2">
										<div class="col-md-8">
											<h3>Frequently Asked Questions</h3>
										</div>
										<div class="col-md-4"></div>
									</div>
									<hr>
									<div id="faqBox">
										<div class="form-group row">
											<label for="fname" class="col-sm-3 control-label col-form-label">Question</label>
											<div class="col-sm-7">
												<input type="text" class="form-control" name="q[]" id="q">
											</div>
											<div class="col-sm-2">
												<button type="button" id="AddFaq" class="btn btn-warning btn-sm float-right"><i class="fa fa-plus"></i></button>
											</div>
										</div>
										<div class="form-group row">
											<label for="fname" class="col-sm-3 control-label col-form-label">Answer</label>
											<div class="col-sm-9">
												<textarea class="form-control" name="a[]" id="a"></textarea>
											</div>
										</div>
									</div>

									<div class="row bg-primary text-white pt-2">
										<div class="col-md-8">
											<h3>Links</h3>
										</div>
										<div class="col-md-4"></div>
									</div>
									<div class="form-group row mt-3">
										<label for="fname" class="col-sm-3 control-label col-form-label">Service Link Heading</label>
										<div class="col-sm-3">
											<input type="text" class="form-control" name="serviceLinkHeading" id="serviceLinkHeading">
										</div>
										<label for="fname" class="col-sm-3 control-label col-form-label">City Link Heading</label>
										<div class="col-sm-3">
											<input type="text" class="form-control" name="cityLinkHeading" id="cityLinkHeading">
										</div>
									</div>
									<hr>
									<div id="keywordbox">
										<div class="form-group row">
											<label for="Text" class="col-sm-3 control-label col-form-label">Text</label>
											<div class="col-sm-7">
												<input type="text" class="form-control" name="keywordtext[]" id="keywordtext">
											</div>
											<div class="col-sm-2">
												<button type="button" id="AddMoreKeyword" class="btn btn-warning btn-sm float-right"><i class="fa fa-plus"></i></button>
											</div>
										</div>

										<div class="form-group row">
											<label for="Link" class="col-sm-3 control-label col-form-label">Link</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="keywordlink[]" id="keywordlink">

											</div>
										</div>
										<div class="form-group row">
											<label for="Link" class="col-sm-3 control-label col-form-label">Link Type</label>
											<div class="col-sm-9">
												<select name="linkType[]" id="linkType" class="form-control">
													<option value="service">Service</option>
													<option value="place">Place</option>
												</select>
											</div>
										</div>
									</div>


									<div class="row mt-4">
										<div class="col-lg-12">
											<div class="form-group">
												<button type="submit" name="Submit" class="btn btn-primary">Create</button>
											</div>
										</div>
								</form>
								<!-- use in append div  -->
								<input type="hidden" id="numfaq" value="1">
								<input type="hidden" id="numbrand" value="1">
								<input type="hidden" id="numsubservice" value="1">
								<input type="hidden" id="AddKeyword" value="1">
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
			$("#nav_clf").addClass("active");
			$("#nav_clf").addClass("open");
			$("#nav_create_clf").addClass("active");
		});
	</script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
		$(document).ready(function() {
			$('.select2').select2();
		});
	</script>

	<script>
		$("#url").keyup(function(e) {
			var url = $("#url").val();
			$.ajax({
				type: "post",
				url: "action/url-exist.php",
				data: {
					url: url,
					check_url: 1
				},
				success: function(response) {
					if (response == "exist") {
						$("#urlExist").html("URL Already Exist !");
						$("#url").focus();
						$("#urlYES").html("");
					} else if (response == "yes") {
						$("#urlExist").html("");
						$("#urlYES").html("Available !");
					} else {
						$("#urlExist").html("");
						$("#urlYES").html("");

					}
					
				}
			});
		});
	</script>


	<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.2.0/tinymce.min.js" integrity="sha256-u2athPw1LMXR9Wx/7pt5l4LtyirEcmRCEPZdtLltAJo=" crossorigin="anonymous"></script>
	<script>
		tinymce.init({
			selector: 'textarea[name=desc_box_h1_desc],textarea[name=desc_box_h2_desc],textarea[name=desc_box_h3_desc]',
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
	<!-- faq script  -->
	<script>
		$('#AddFaq').click(function(e) {
			e.preventDefault();
			var numfaq = jQuery('#numfaq').val();
			numfaq++;

			jQuery('#numfaq').val(numfaq);

			var html = '<div id="box' + numfaq + '"><div class="form-group row"  style="padding-top: 50px;border-top: 1px solid #cccccc59;">\
										<label for="fname" class="col-sm-3 control-label col-form-label">Question</label>\
										<div class="col-sm-7">\
											<input type="text" class="form-control" name="q[]" id="q" required>\
										</div>\
										<div class="col-sm-2">\
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_more(' + numfaq + ')"><i class="fa fa-trash"></i></button>\
											</div>\
									</div>\
									<div class="form-group row">\
										<label for="fname" class="col-sm-3 control-label col-form-label">Answer</label>\
										<div class="col-sm-9">\
											<textarea class="form-control" name="a[]" id="a" required></textarea>\
										</div>\
									</div>\
									</div></div>';
			jQuery('#faqBox').append(html);



		});
	</script>

	<script>
		$('#AddBrand').click(function(e) {
			e.preventDefault();
			var numbrand = jQuery('#numbrand').val();
			numbrand++;
			jQuery('#numbrand').val(numbrand);
			var html2 = '<div id="brandbox' + numbrand + '"><div class="form-group row" style="padding-top: 50px;border-top: 1px solid #cccccc59;">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Brand</label>\
											<div class="col-sm-7">\
												<input type="text" class="form-control" name="brand_title[]" id="brand_title" required>\
											</div>\
											<div class="col-sm-2">\
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_brand(' + numbrand + ')"><i class="fa fa-trash"></i></button>\
											</div>\
										</div>\
										<div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Image (60*60 px)</label>\
											<div class="col-sm-9">\
												<input type="file" class="form-control brand_image" name="brand_image[]" id="brand_image" onchange="validateimg(this)" multiple required>\
											</div>\
										</div>\
										<div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">URL</label>\
											<div class="col-sm-9">\
												<input type="text" class="form-control" name="brand_url[]" id="brand_url">\
											</div>\
										</div></div>';
			jQuery('#brandBox').append(html2);
		});
	</script>

	<!-- Keyword script  -->
	<script>
		$('#AddMoreKeyword').click(function(e) {
			e.preventDefault();
			var AddKeyword = jQuery('#AddKeyword').val();
			AddKeyword++;

			jQuery('#AddKeyword').val(AddKeyword);

			var html = '<div id="keywordbox' + AddKeyword + '"><div class="form-group row"  style="padding-top: 50px;border-top: 1px solid #cccccc59;">\
										<label for="fname" class="col-sm-3 control-label col-form-label">Text</label>\
										<div class="col-sm-7">\
										<input type="text" class="form-control" name="keywordtext[]" id="keywordtext">\
										</div>\
										<div class="col-sm-2">\
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_keyword(' + AddKeyword + ')"><i class="fa fa-trash"></i></button>\
											</div>\
									</div>\
									<div class="form-group row">\
										<label for="fname" class="col-sm-3 control-label col-form-label">Link</label>\
										<div class="col-sm-9">\
										<input type="text" class="form-control" name="keywordlink[]" id="keywordlink">\
										</div>\
									</div>\
									<div class="form-group row">\
											<label for="Link" class="col-sm-3 control-label col-form-label">Link Type</label>\
											<div class="col-sm-9">\
												<select name="linkType[]" id="linkType" class="form-control">\
													<option value="service">Service</option>\
													<option value="place">Place</option>\
												</select>\
											</div>\
										</div>\
									</div></div>';
			jQuery('#keywordbox').append(html);



		});
	</script>
	<script>
		$('#Addsubservice').click(function(e) {
			e.preventDefault();
			var numsubservice = jQuery('#numsubservice').val();
			numsubservice++;

			jQuery('#numsubservice').val(numsubservice);

			var html3 = '<div id="subservicebox' + numsubservice + '"><div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Sub-Service</label>\
											<div class="col-sm-7">\
												<input type="text" class="form-control" name="subservice[]" id="subservice" required>\
											</div>\
											<div class="col-sm-2">\
											<button type="button" id="numsubservice" class="btn btn-danger btn-sm float-right" onclick="remove_subservice(' + numsubservice + ')"><i class="fa fa-trash"></i></button>\
											</div>\
										</div>';
			jQuery('#subserviceBox').append(html3);
		});
	</script>

</body>


</html>