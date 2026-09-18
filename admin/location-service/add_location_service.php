<?php session_start(); ?>

<!DOCTYPE html>

<html lang="en">

<head>

    <?php

	include('../controllers/common_controllers.php');

	include('controller/location_service_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
	$conn = _connectodb();

	//$total_projects = getTotatProjects($conn,$EnterpriseID);

	?>

    <meta charset="utf-8">

    <title>

        Add Location Service

    </title>

    <meta name="description" content="Add location service ">

    <?php

	include('../includes/common_head_content.php');

	?>

    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">

</head>

<?php
$UserType = SessionCheck();
if (($UserType == "Admin") || ($UserType == "CFL")) {
} else {
	header('Location:../authentication/login.php');
}
$conn = _connectodb();
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
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

    function remove_keyword(id) {
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $('#keywordbox' + id).fadeOut(600, function() {
                $('#keywordbox' + id).remove();
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
    </script>
    <!-- DOC: script to save and load page settings -->

    <?php include('../js/theme_settings.js'); ?>

    <!-- BEGIN Page Wrapper -->

    <div class="page-wrapper">

        <div class="page-inner">

            <?php

			if ($UserType == "Admin")

				include('../navigation/admin_navigation.php');
			else
				include('../navigation/cfl_navigation.php');
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
                        <li class="breadcrumb-item"><a href="view_location_service">Location Services </a></li>
                        <li class="breadcrumb-item active">Add Location Service</li>

                    </ol>

                    <!-- Main Creation Form -->

                    <div id="panel-5" class="panel">

                        <div class="panel-hdr">

                            <h2>

                                Add <span class="fw-300"><i>Location Service</i></span>

                            </h2>
                        </div>

                        <div class="panel-container show">

                            <div class="panel-content">

                                <form id="create_location_service_form" action="create_location_service_action"
                                    method="post" enctype="multipart/form-data">

                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="ServiceBannerImage">Service Banner Image
                                                    (400 × 297 px) </label>
                                                <input type="file" name="service_banner_img" id="service_banner_img"
                                                    class="form-control" required>

                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">



                                        <div class="col-lg-6">

                                            <div class="form-group">

                                                <label class="form-label" for="block_name">Title</label>
                                                <input type="text" id="title" name="title" class="form-control">

                                            </div>

                                        </div>

                                        <div class="col-lg-6">

                                            <div class="form-group">

                                                <label class="form-label" for="block_name">City</label>
                                                <select name="CityId" class="form-control">

                                                    <?php
													$seclect = "select * from citydata";
													$sel = mysqli_query($conn, $seclect);
													while ($row = mysqli_fetch_array($sel)) {
														$CityId = $row['CityId'];
														$CityName = $row['CityName'];
													?>
                                                    <option value="<?php echo $CityId; ?>"><?php echo $CityName; ?>
                                                    </option>
                                                    <?php
													}
													?>

                                                </select>

                                            </div>

                                        </div>
                                        <div class="col-lg-6">

                                            <div class="form-group">
                                                <label class="form-label" for="service">Service</label>
                                                <select name="service" id="" class="form-control">
                                                    <?php
													$seclect = "select * from services";
													$sel = mysqli_query($conn, $seclect);
													while ($row = mysqli_fetch_array($sel)) {
													?>
                                                    <option value="<?php echo $row['ID'];; ?>">
                                                        <?php echo $row['Name']; ?></option>
                                                    <?php
													}
													?>


                                                </select>
                                            </div>

                                        </div>
                                        <div class="col-lg-6">

                                            <div class="form-group">

                                                <label class="form-label" for="block_name">URL</label>
                                                <input type="text" id="url" name="url" class="form-control">
                                            </div>

                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="phonenumber">Meta Title</label>
                                                <input type="text" id="txtMetaTitle" name="txtMetaTitle"
                                                    class="form-control" placeholder="Meta Title" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="phonenumber">Meta Keywords(seprated by
                                                    commas)</label>
                                                <input type="text" id="metakeyword" name="metakeyword"
                                                    class="form-control" placeholder="Meta Keyword" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="phonenumber">Service Position</label>
                                                <input type="number" id="service_position" name="service_position"
                                                    class="form-control" placeholder="Service Position" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label">Meta Description</label>
                                                <textarea id="txtMetaDesc" name="txtMetaDesc" class="form-control"
                                                    rows="4" cols="5" required></textarea>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">


                                            <div class="form-group">
                                                <label class="form-label" for="color">Display Safety</label>
                                                <input type="radio" value="1" name="safety" checked
                                                    style="margin-left: 4px;margin-right: 4px;"><label
                                                    for="">Yes</label>
                                                <input type="radio" value="0" name="safety" id=""
                                                    style="margin-left: 4px;margin-right: 4px;"><label for="">No</label>

                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="color">Is Featured</label>
                                                <input type="radio" value="1" name="featured" id=""
                                                    style="margin-left: 4px;margin-right: 4px;"><label
                                                    for="">Yes</label>
                                                <input type="radio" value="0" name="featured" checked id=""
                                                    style="margin-left: 4px;margin-right: 4px;"><label for="">No</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label mr-3" for="color">Safety Image (650*380)
                                                </label>
                                                <input type="file" name="safety_img" id="safety_img">
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
                                        <label for="fname" class="col-sm-3 control-label col-form-label">Heading to
                                            Display</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="subservice_heading"
                                                id="subservice_heading">
                                        </div>
                                    </div>
                                    <hr>
                                    <div id="subserviceBox" class="mb-5">
                                        <div class="form-group row">
                                            <label for="fname"
                                                class="col-sm-3 control-label col-form-label">Sub-Service</label>
                                            <div class="col-sm-7">
                                                <input type="text" class="form-control" name="subservice[]"
                                                    id="subservice">
                                            </div>
                                            <div class="col-sm-2">
                                                <button type="button" id="Addsubservice"
                                                    class="btn btn-warning btn-sm float-right"><i
                                                        class="fa fa-plus"></i></button>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="fname"
                                                class="col-sm-3 control-label col-form-label">Sub-Services Image (70*70
                                                px)</label>
                                            <div class="col-sm-9">
                                                <input type="file" class="form-control sub_services_image"
                                                    name="sub_services_image[]" id="sub_services_image" multiple>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Customer Issues </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Image">Box Main Heading</label>

                                                <input type="text" id="mainheading" name="mainheading"
                                                    class="form-control">

                                            </div>
                                        </div>
                                    </div>

                                    <div class="row  bg-primary m-2 text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Common Issues Box </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="row m-2 mt-3">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 1 Heading</label>
                                                <input type="text" name="desc_box_h1" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 1 Description</label>
                                                <textarea id="desc_box_h1_desc" name="desc_box_h1_desc"
                                                    class="form-control"></textarea>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row m-2 bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Services offered Box </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="row m-2 mt-3">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 2 Heading</label>
                                                <input type="text" name="desc_box_h2" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 2 Description</label>
                                                <textarea id="desc_box_h2_desc" name="desc_box_h2_desc"
                                                    class="form-control"></textarea>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- <div class="row  bg-primary text-white pt-2">
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

									</div> -->






                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Brand </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="form-group row mt-3">
                                        <label for="fname" class="col-sm-3 control-label col-form-label">Heading to
                                            Display</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="brand_heading"
                                                id="brand_heading">
                                        </div>
                                    </div>
                                    <hr>
                                    <div id="brandBox" class="mb-5">
                                        <div class="form-group row">
                                            <label for="fname"
                                                class="col-sm-3 control-label col-form-label">Brand</label>
                                            <div class="col-sm-7">
                                                <input type="text" class="form-control" name="brand_title[]"
                                                    id="brand_title">
                                            </div>
                                            <div class="col-sm-2">
                                                <button type="button" id="AddBrand"
                                                    class="btn btn-warning btn-sm float-right"><i
                                                        class="fa fa-plus"></i></button>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="fname" class="col-sm-3 control-label col-form-label">Image
                                                (60*60 px)</label>
                                            <div class="col-sm-9">
                                                <input type="file" class="form-control brand_image" name="brand_image[]"
                                                    id="brand_image" multiple>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="fname" class="col-sm-3 control-label col-form-label">URL</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" name="brand_url[]"
                                                    id="brand_url">
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
                                        <label for="fname" class="col-sm-3 control-label col-form-label">Service Link
                                            Heading</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" name="serviceLinkHeading"
                                                id="serviceLinkHeading">
                                        </div>
                                        <label for="fname" class="col-sm-3 control-label col-form-label">City Link
                                            Heading</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" name="cityLinkHeading"
                                                id="cityLinkHeading">
                                        </div>
                                    </div>
                                    <hr>
                                    <div id="keywordbox">
                                        <div class="form-group row">
                                            <label for="Text" class="col-sm-3 control-label col-form-label">Text</label>
                                            <div class="col-sm-7">
                                                <input type="text" class="form-control" name="keywordtext[]"
                                                    id="keywordtext">
                                            </div>
                                            <div class="col-sm-2">
                                                <button type="button" id="AddMoreKeyword"
                                                    class="btn btn-warning btn-sm float-right"><i
                                                        class="fa fa-plus"></i></button>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="Link" class="col-sm-3 control-label col-form-label">Link</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" name="keywordlink[]"
                                                    id="keywordlink">

                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="Link" class="col-sm-3 control-label col-form-label">Link
                                                Type</label>
                                            <div class="col-sm-9">
                                                <select name="linkType[]" id="linkType" class="form-control">
                                                    <option value="service">Service</option>
                                                    <option value="place">Place</option>
                                                </select>
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
                                            <label for="fname"
                                                class="col-sm-3 control-label col-form-label">Question</label>
                                            <div class="col-sm-7">
                                                <input type="text" class="form-control" name="q[]" id="q">
                                            </div>
                                            <div class="col-sm-2">
                                                <button type="button" id="AddFaq"
                                                    class="btn btn-warning btn-sm float-right"><i
                                                        class="fa fa-plus"></i></button>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="fname"
                                                class="col-sm-3 control-label col-form-label">Answer</label>
                                            <div class="col-sm-9">

                                                <textarea class="form-control" name="a[]" id="a"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-lg-12">
                                            <div class="form-group">


                                                <button type="submit" name="Submit"
                                                    class="btn btn-primary">Create</button>

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

                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->

                <!-- BEGIN Page Footer -->

                <?php

				include('../includes/common_footer.php')

				?>

                <!-- END Page Footer
                </div>

            </div>

        </div>

        <!-- END Page Wrapper ---->

                <?php

				include('../includes/common_modules.php');

				include('../includes/common_scripts.php');

				?>

                <script>
                $(document).ready(function() {
                    $("#js-nav-menu").addClass("active");
                    $("#js-nav-menu").addClass("open");
                    $("#nav_location_services").addClass("active");
                    $("#nav_services").addClass("active");
                });
                </script>

                <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.2.0/tinymce.min.js"
                    integrity="sha256-u2athPw1LMXR9Wx/7pt5l4LtyirEcmRCEPZdtLltAJo=" crossorigin="anonymous"></script>
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
                    content_style: "@import url('https://fonts.googleapis.com/css2?family=Poppins&display=swap'); body { font-family: Poppins; }",
                    height: 300
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
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_more(' +
                        numfaq + ')"><i class="fa fa-trash"></i></button>\
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
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_keyword(' +
                        AddKeyword + ')"><i class="fa fa-trash"></i></button>\
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
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_brand(' +
                        numbrand + ')"><i class="fa fa-trash"></i></button>\
											</div>\
										</div>\
										<div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Image (60*60 px)</label>\
											<div class="col-sm-9">\
												<input type="file" class="form-control brand_image" name="brand_image[]" id="brand_image"  multiple required>\
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


                <script>
                $('#Addsubservice').click(function(e) {
                    e.preventDefault();
                    var numsubservice = jQuery('#numsubservice').val();
                    numsubservice++;

                    jQuery('#numsubservice').val(numsubservice);

                    var html3 = '<div id="subservicebox' + numsubservice +
                        '"><div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Sub-Service</label>\
											<div class="col-sm-7">\
												<input type="text" class="form-control" name="subservice[]" id="subservice" required>\
											</div>\
											<div class="col-sm-2">\
											<button type="button" id="numsubservice" class="btn btn-danger btn-sm float-right" onclick="remove_subservice(' +
                        numsubservice + ')"><i class="fa fa-trash"></i></button>\
											</div>\
										</div>\
										<div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Sub-Services Image (70*70 px)</label>\
											<div class="col-sm-9">\
												<input type="file" class="form-control sub_services_image" name="sub_services_image[]" id="sub_services_image" multiple>\
											</div>\
										</div>\
										</div>';
                    jQuery('#subserviceBox').append(html3);
                });
                </script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js">
                </script>
                <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.js"></script>
                <script>
                $("#enterprise_create_form").validate({
                    rules: {
                        service_img: {
                            required: true,
                            extension: "jpg|jpeg|png|ico|bmp|webp"
                        }
                    },
                    messages: {

                        service_img: {
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