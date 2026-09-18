<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	include('../controllers/common_controllers.php');
	include('controller/city_controller.php');
    include('../state/controller/state_controller.php');
    include('../employees/controller/employee_controller.php');
    require_once('../includes/autoloader.inc.php');
    $UserType = SessionCheck();
	setNavigation($_SESSION['Roles']);
	$conn = _connectodb();
	$EnterpriseID = "";
	$UserType = SessionCheck();
	?>
    <meta charset="utf-8">
    <title>
        Update City
    </title>
    <meta name="description" content="Update City ">
    <?php
	include('../includes/common_head_content.php');
	?>
    <style>
    .error {
        color: #fd4f4f;
        margin-top: 10px;
    }
    </style>
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
</head>
<?php
$UserType = SessionCheck();
$conn = _connectodb();
$username = $_SESSION['pb_username'];
$CityId = $_GET['Id'];
$getCity = getSingleCity($conn, $CityId);
$getCity = json_decode($getCity, true);
foreach ($getCity as $key => $getCityvalue) {
	extract($getCityvalue);
}
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
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $('#bannerbox' + id).fadeOut(600, function() {
                $('#bannerbox' + id).remove();
            });
        }, function() {
            alertify.error('Cancel')
        });
    }

    function remove_oldbanner(id, img) {
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $.ajax({
                type: "post",
                url: "action/delete-banner.php",
                data: "bannerID=" + id + "&bannerImg=" + img,
                success: function(response) {
                    $('#remove_banner_' + id).fadeOut(700, function() {
                        $(this).remove();
                    });
                }
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
                        <li class="breadcrumb-item"><a href="view-city">View Cities </a></li>
                        <li class="breadcrumb-item active">Update City</li>
                    </ol>
                    <!-- Main Creation Form -->
                    <div id="panel-5" class="panel">
                        <div class="panel-hdr">
                            <h2>
                                Update <span class="fw-300"><i>City</i></span>
                            </h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <form id="enterprise_create_form" method="post" action="update_city_action"
                                    enctype="multipart/form-data">

                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Promotional Banner </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <!-- <div class="form-group row mt-3">
										<div class="col">
											<button type="button" id="Addbanner" class="btn btn-warning btn-sm float-right"><i class="fa fa-plus"></i></button>
										</div>
									</div> -->
                                    <hr>
                                    <div id="bannerBox" class="mb-5">
                                        <?php
										$i = 1;
										$CityId = $_GET['Id'];
										$bannerSql = mysqli_query($conn, "select * from citypromobanner where
                                         CityId='$CityId' ORDER BY ID ASC");
										while ($bannerRow = mysqli_fetch_assoc($bannerSql))
										{
										?>
                                        <div id="remove_banner_<?php echo $bannerRow['ID'] ?>" class="divider">
                                            <div class="form-group row">
                                                <input type="hidden" name="bannerID[]" id="bannerID"
                                                    value="<?php echo $bannerRow['ID'] ?>">
                                                <div class="col-sm-2">
                                                    <!-- <button type="button" class="float-right btn btn-sm btn-danger shadow-none" onclick="remove_oldbanner('<?php
														//  echo $bannerRow['ID']
														 ?>','<?php
														//   echo $bannerRow['image']
														  ?>')"><i class="fa fa-trash"></i></button> -->
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label for="fname" class="col-sm-3 control-label col-form-label">Banner
                                                    Image ( 1920 × 980 px )</label>
                                                <div class="col-sm-6">
                                                    <input type="file" class="form-control" name="banner_image[]"
                                                        id="banner_image" multiple>
                                                </div>
                                                <div class="col-sm-3">
                                                    <img src="../media/promobanner/<?php echo $bannerRow['image'] ?>"
                                                        alt="" width="50px">
                                                </div>
                                            </div>

                                        </div>
                                        <?php } ?>
                                    </div>


                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="block_name">City Name</label>
                                                <input type="hidden" id="CityId" name="CityId" class="form-control"
                                                    value="<?php echo $CityId; ?>">
                                                <input type="text" id="CityName" value="<?php echo $CityName ?>"
                                                    name="CityName" class="form-control">
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="village_name">State Name</label>
                                                <select class="select2 form-control w-100" name="state_name"
                                                    id="state_name">

                                                    <option value="-1">Search & Select</option>
                                                    <?php

                                                        foreach($statedata as $state_value)
                                                        {
                                                            $selected = "";
                                                            if($state_value['ID'] == $StateID)
                                                            {
                                                                $selected = "selected";
                                                            }
                                                        ?>
                                                    <option value="<?php echo $state_value['ID'];?>"
                                                        <?php echo $selected; ?>>
                                                        <?php echo $state_value['StateName'];?>
                                                    </option>

                                                    <?php
                                                        }
                                                        ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php
												if ($image != '') { ?>
                                                <img src="../media/city/<?php echo $image; ?>" style="width: 70px;">
                                                <?php
												} else {
													echo "please select image";
												}
												?>
                                                <br>
                                                <input type="file" name="cityimage" id="cityimage"
                                                    class="form-control-file mt-2">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="featured">Is Featured</label>
                                                <?php
												if ($featured == 1) {
												?>
                                                <input type="radio" value="1" name="featured"
                                                    style="margin-left: 4px;margin-right: 4px;" checked id="">Yes
                                                <input type="radio" value="0" name="featured"
                                                    style="margin-left: 4px;margin-right: 4px;" id="">No
                                                <?php 	} else {
												?>
                                                <input type="radio" value="1" name="featured"
                                                    style="margin-left: 4px;margin-right: 4px;" id="">Yes
                                                <input type="radio" value="0" name="featured"
                                                    style="margin-left: 4px;margin-right: 4px;" id="" checked>No
                                                <?php }
												?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="metaTitle">Meta Title</label>
                                                <input type="text" id="metaTitle" name="metaTitle" class="form-control"
                                                    placeholder="Meta Title" value="<?php echo $metaTitle; ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="url">URL</label>
                                                <input type="hidden" id="url" name="url" class="form-control"
                                                    value="<?php echo $url; ?>">
                                                <input type="text" id="url" value="<?php echo $url ?>" name="url"
                                                    class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Image">Meta Description</label>
                                                <textarea id="metaDescription" name="metaDescription"
                                                    class="form-control" rows="4" cols="5"
                                                    required><?php echo $metaDescription; ?></textarea>
                                            </div>
                                        </div>


                                        <!-- <div class="col-lg-6">
											<div class="form-group">
												<label class="form-label" for="topbanner"> Top Banner (370*180) </label>
												<br>
												<?php
												if ($getCityvalue['topbanner'] != '') { ?>
													<img src="../media/banners/<?php echo $getCityvalue['topbanner']; ?>" style="width: 70px;">
												<?php
												}
												?>
												<br>
												<input type="file" name="topbanner" id="topbanner" class="form-control-file mt-2">
											</div>
										</div> -->
                                    </div>

                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Management </h3>
                                        </div>
                                        <div class="col-md-4">

                                        </div>
                                    </div>

                                    <div class="row mt-3">


                                        <div class="col-md-6 col-12">
                                            <div class="form_div">
                                                <label class="form-label" for="single-default">Home Care Lead
                                                </label>

                                                <select class="select2 form-control w-100" id="home_care_lead"
                                                    name="city_lead">
                                                    <option value="-1">Search & Select</option>
                                                    <?php

                                                        foreach($employee_array as $employee)
                                                        {
                                                            $selected = "";
                                                            if($employee['ID'] == $CityLead)
                                                            {
                                                                $selected = "selected";
                                                            }
                                                        ?>
                                                    <option value="<?php echo $employee['ID'];?>"
                                                        <?php echo $selected; ?>>
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

                                                <select class="select2 form-control w-100" id="corporate_lead"
                                                    name="corporate_lead">

                                                    <option value="-1">Search & Select</option>
                                                    <?php

                                                       foreach($employee_array as $employee)
                                                       {
                                                        $selected = "";
                                                        if($employee['ID'] == $CorporateLead)
                                                        {
                                                            $selected = "selected";
                                                        }
                                                       ?>
                                                    <option value="<?php echo $employee['ID'];?>"
                                                        <?php echo $selected; ?>>
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
                                                            $selected = "";
                                                            if($tat_group['ID'] == $TATGroupID)
                                                            {
                                                                $selected = "selected";
                                                            }
                                                         ?>
                                                            <option value="<?php echo $tat_group['ID'];?>" <?php echo $selected; ?>>
                                                                <?php echo $tat_group['Name'];?>
                                                            </option>

                                                        <?php
                                                        }
                                                        ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="cityId" value="<?php echo $CityId ?>">
                                    <input type="hidden" name="UserType" id="UserType" value="">
                                    <input type="hidden" id="numbanner" value="1">
                                    <div class="row mt-4">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-success waves-effect waves-themed"
                                                    style="float:right;">Update </button>
                                            </div>
                                        </div>
                                    </div>
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
        END Page Wrapper ---->
                <?php
				include('../includes/common_modules.php');
				include('../includes/common_scripts.php');
				?>
                <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js">
                </script>
                <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.js"></script>
                <script>
                $(document).ready(function() {
                    $("#js-nav-menu").addClass("active");
                    $("#js-nav-menu").addClass("open");
                    $("#nav_city").addClass("active");
                    $("#nav_configuration").addClass("active");
                });
                </script>

                <script>
                $(document).ready(function() {
                    $("#home_care_lead").select2();
                    $("#corporate_lead").select2();
                    $("#state_name").select2();
                });
                </script>

                <script>
                $("#enterprise_create_form").validate({
                    rules: {
                        cityimage: {
                            extension: "jpg|jpeg|png|ico|bmp|webp"
                        }
                    },
                    messages: {
                        cityimage: {
                            required: 'Please choose image.',
                            extension: 'Please choose valid image.',
                        }
                    },
                    submitHandler: function(form) {
                        form.submit();
                    }
                });
                </script>
                <script>
                $('#Addbanner').click(function(e) {
                    e.preventDefault();
                    var numbanner = jQuery('#numbanner').val();
                    numbanner++;
                    jQuery('#numbanner').val(numbanner);
                    var html2 = '<div id="bannerbox' + numbanner + '"><div class="form-group row" style="padding-top: 50px;border-top: 1px solid #ccc;">\<div class="col">\
											<button type="button" id="AddFaq" class="btn btn-danger btn-sm float-right" onclick="remove_banner(' +
                        numbanner + ')"><i class="fa fa-trash"></i></button>\
											</div>\
										</div>\
										<div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Image (60*60 px)</label>\
											<div class="col-sm-9">\
												<input type="file" class="form-control" name="banner_image[]" id="banner_image" multiple required >\
											</div>\
										</div>\
										</div>';
                    jQuery('#bannerBox').append(html2);
                });
                </script>
</body>

</html>