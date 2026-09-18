<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	include('../controllers/common_controllers.php');
    setNavigation($_SESSION['Roles']);
	include('controller/city_controller.php');
    include('../state/controller/state_controller.php');
    include('../employees/controller/employee_controller.php');
    require_once('../includes/autoloader.inc.php');
	$conn = _connectodb();
	$UserType = SessionCheck();
	//$total_projects = getTotatProjects($conn,$EnterpriseID);
	?>
    <meta charset="utf-8">
    <title>
        Add City
    </title>
    <meta name="description" content="Add City">
    <?php
	include('../includes/common_head_content.php');
	?>
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

$employee_array = getFilteredEmployeeArray($conn,"Employee");

$statedata = getAllStates($conn);
$core = new Core();
$tat_groups = $core->_getTableRecords($conn,'tat_group','where IsActive = 1');

?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->
    <script>
    function remove_banner(id) {

        alertify.confirm('TechXpert ', 'Are you sure want to delete !', function() {
            $('#brandbox' + id).fadeOut(600, function() {
                $('#brandbox' + id).remove();
            });
        }, function() {
            alertify.error('Cancel')
        });
    }
    </script>
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
                        <li class="breadcrumb-item"><a href="view-city">View Cities </a></li>
                        <li class="breadcrumb-item active">Add City</li>

                    </ol>


                    <!-- Main Creation Form -->
                    <div id="panel-5" class="panel">
                        <div class="panel-hdr">
                            <h2>
                                Add <span class="fw-300"><i>City</i></span>
                            </h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <div class="panel-tag">
                                    Add the information below to create the City <br>
                                </div>
                                <form name="cityForm" method="post" action="action/create_city_action"
                                    enctype="multipart/form-data">
                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>City Banners </h3>
                                        </div>
                                        <div class="col-md-4">

                                        </div>
                                    </div>
                                    <!-- <div class="row mt-3" style="display: flex;justify-content: end;">
                                        <button type="button" id="AddBrand"
                                            class="btn btn-warning btn-sm float-right"><i
                                                class="fa fa-plus"></i></button>
                                    </div> -->

                                    <div id="brandBox" class="mb-5 mt-5">
                                        <div class="form-group row">

                                            <label for="banner" class="col-sm-3 control-label col-form-label">Banner 1 (
                                                1920 × 980 px )</label>
                                            <div class="col-sm-9">
                                                <input type="file" class="form-control citypromobanner"
                                                    name="citypromobanner[]" id="citypromobanner" multiple>
                                            </div>
                                        </div>

                                        <div class="form-group row">

                                            <label for="banner" class="col-sm-3 control-label col-form-label">Banner 2 (
                                                1920 × 980 px )</label>
                                            <div class="col-sm-9">
                                                <input type="file" class="form-control citypromobanner"
                                                    name="citypromobanner[]" id="citypromobanner" multiple>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>City Details </h3>
                                        </div>
                                        <div class="col-md-4">

                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 mt-3">
                                            <div class="form-group">
                                                <label class="form-label" for="village_name">City Name</label>
                                                <input type="text" id="city_name" name="city_name" class="form-control"
                                                    required>
                                            </div>
                                        </div>

                                        <div class="col-lg-6 mt-3">
                                            <div class="form-group">
                                                <label class="form-label" for="village_name">State Name</label>
                                                <select class="select2 form-control w-100" name="state_name"
                                                    id="state_name">
                                                    <option value="-1">Search & Select</option>
                                                    <?php

                                                        foreach($statedata as $state_value)
                                                        {
                                                            ?>
                                                                    <option value="<?php echo $state_value['ID'];?>">
                                                                        <?php echo $state_value['StateName'];?>
                                                                    </option>
                                                            <?php
                                                        }
                                                        ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="color">Is Featured</label>
                                                <input type="radio" value="1" name="featured" checked id=""
                                                    style="margin-left: 4px;margin-right: 4px;"><label
                                                    for="">Yes</label>
                                                <input type="radio" value="0" name="featured" id=""
                                                    style="margin-left: 4px;margin-right: 4px;"><label for="">No</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="image">City Image ( 100 × 54 px )</label>
                                                <input type="file" id="image" name="image" class="form-control"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="url">URL</label>
                                                <input type="text" id="url" name="url" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="metaTitle">Meta Title</label>
                                                <input type="text" id="metaTitle" name="metaTitle" class="form-control"
                                                    placeholder="Meta Title">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">

                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Image">Meta Description</label>
                                                <textarea id="metaDescription" name="metaDescription"
                                                    class="form-control" rows="4" cols="5" required></textarea>

                                            </div>
                                        </div>

                                    </div>

                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Management</h3>
                                        </div>
                                        <div class="col-md-4">

                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-6 col-12">
                                            <div class="form_div">
                                                <label class="form-label" for="single-default">Home Care Lead
                                                </label>

                                                <select class="select2 form-control w-100" name="city_lead"
                                                    id="home_care_lead">
                                                    <option value="-1">Search & Select</option>
                                                    <?php

                                                             foreach($employee_array as $employee)
                                                             {
                                                             ?>
                                                    <option value="<?php echo $employee['ID'];?>">
                                                        <?php echo $employee['Name'];?>
                                                    </option>
                                                    <?php
                                                        }
                                                        ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-12">
                                            <div class="form_div">
                                                <label class="form-label" for="single-default">Corporate Lead
                                                </label>

                                                <select class="select2 form-control w-100" name="corporate_lead"
                                                    id="corporate_lead">
                                                    <option value="-1">Search & Select</option>
                                                    <?php

                                                         foreach($employee_array as $employee)
                                                         {
                                                         ?>
                                                    <option value="<?php echo $employee['ID'];?>">
                                                        <?php echo $employee['Name'];?>
                                                    </option>

                                                    <?php
                                                        }
                                                        ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-12 mt-3">
                                            <div class="form_div">
                                                <label class="form-label" for="single-default">TAT Group
                                                </label>

                                                <select class="form-control w-100" name="tat_group"
                                                    id="tat_group">
                                                    <option value="-1">Select TAT Group</option>
                                                    <?php
                                                         foreach($tat_groups as $tat_group)
                                                         {
                                                         ?>
                                                            <option value="<?php echo $tat_group['ID'];?>">
                                                                <?php echo $tat_group['Name'];?>
                                                            </option>

                                                        <?php
                                                        }
                                                        ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row mt-4">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <button type="submit" id="create_btn" class="btn btn-blue"
                                                    style="float:right;">Create</button>
                                            </div>
                                        </div>
                                    </div>

                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="active_search" value="" />
                    <input type="hidden" id="numbrand" value="1">
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

    <?php
	include('../includes/common_modules.php');
	include('../includes/common_scripts.php');
	?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.js"></script>
    <script>
    $("#city_lead").select2();
    </script>
    <script>
    $(document).ready(function() {
        $("#home_care_lead").select2();
        $("#corporate_lead").select2();
        state_name
        $("#state_name").select2();
    });
    </script>
    <script>
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_city").addClass("active");
        $("#nav_configuration").addClass("active");
    });
    </script>
    <script>
    $('#AddBrand').click(function(e) {
        e.preventDefault();
        var numbrand = jQuery('#numbrand').val();
        numbrand++;
        jQuery('#numbrand').val(numbrand);
        var html2 = '<div id="brandbox' + numbrand + '"><div class="form-group row" style="padding-top: 50px;border-top: 1px solid #cccccc59;">\
										</div>\
										<div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Banner </label>\
											<div class="col-sm-7">\
												<input type="file" class="form-control citypromobanner" name="citypromobanner[]" id="citypromobanner"  multiple required>\
											</div>\
                                            <div class="col-sm-2" style="display: flex;justify-content: end;">\
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_banner(' +
            numbrand + ')"><i class="fa fa-trash"></i></button>\
											</div>\
										</div>\
										</div>';
        jQuery('#brandBox').append(html2);
    });
    </script>


    <script>
    $("#enterprise_create_form").validate({
        rules: {
            image: {
                required: true,
                extension: "jpg|jpeg|png|ico|bmp|webp"
            }
        },
        messages: {

            image: {
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