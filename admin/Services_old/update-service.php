<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	include('../controllers/common_controllers.php');
	include('controller/service_controller.php');
	include('../city/controller/city_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
	$conn = _connectodb();
	?>
    <meta charset="utf-8">
    <title>
        Update Service
    </title>
    <meta name="description" content="Update Schema">
    <?php
	include('../includes/common_head_content.php');
	?>
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
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

    .error {
        color: #fd4f4f;
        margin-top: 10px;
    }

    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
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
$ID = $_GET['ID'];
$getCitydata = getAllCity($conn);
$getCitydata = json_decode($getCitydata, true);
$getService = getSingleService($conn, $ID);
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
            $('#subservicebox' + id).fadeOut(700, function() {
                $(this).remove();
            });
        }, function() {
            alertify.error('Cancel')
        });
    }

    function remove_keyword(id) {
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $('#keywordbox' + id).fadeOut(700, function() {
                $(this).remove();
            });
        }, function() {
            alertify.error('Cancel')
        });
    }

    function remove_oldFaq(id) {
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $.ajax({
                type: "post",
                url: "action/delete-faq.php",
                data: "FAQID=" + id,
                success: function(response) {
                    $('#remove_faq_' + id).fadeOut(700, function() {
                        $(this).remove();
                    });
                }
            });
        }, function() {
            alertify.error('Cancel')
        });
    }

    function remove_oldKeyword(id) {
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $.ajax({
                type: "post",
                url: "action/delete-keyword.php",
                data: "KEYWORDID=" + id,
                success: function(response) {
                    $('#remove_keyword_' + id).fadeOut(700, function() {
                        $(this).remove();
                    });
                }
            });
        }, function() {
            alertify.error('Cancel')
        });
    }

    function remove_oldSubservice(id) {
        // alert(id);
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $.ajax({
                type: "post",
                url: "action/delete-subservice.php",
                data: "subservceId=" + id,
                success: function(response) {
                    $('#remove_subservice_' + id).fadeOut(700, function() {
                        $(this).remove();
                    });
                }
            });
        }, function() {
            alertify.error('Cancel')
        });
    }

    function remove_oldbrand(id, img) {
        alertify.confirm('Aryadibusiness ', 'Are you sure want to delete !', function() {
            $.ajax({
                type: "post",
                url: "action/delete-brand.php",
                data: "brandID=" + id + "&brandImg=" + img,
                success: function(response) {
                    $('#remove_brand_' + id).fadeOut(700, function() {
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="view-services">View Services</a></li>
                        <li class="breadcrumb-item active">Update Service</li>
                    </ol>
                    <!-- Main Creation Form -->
                    <div id="panel-5" class="panel">
                        <div class="panel-hdr">
                            <h2>
                                Update <span class="fw-300"><i>Service</i></span>
                            </h2>
                            <?php if($ID == 30){?>
                            <a href='view-laundry-service-conf?ID=<?php echo $ID ?>' class="text-white btn btn-primary"
                                style="margin-right:20px;">View Laundry Service Configuration</a>
                            
                            <a data-toggle="modal" data-target="#luandryservice" class="text-white btn btn-info"
                                style="margin-right:20px;">Laundry Service Configuration</a>
                            <?php } ?>

                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <div class="panel-tag">
                                    Update the information below <br>
                                </div>
                                <form id="enterprise_create_form" method="post" action="update_service_action"
                                    enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="ServiceBannerImage">Service Banner Image
                                                    (400 × 297 px) </label>

                                                <input type="file" name="service_banner_img" id="service_banner_img"
                                                    class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <br>
                                                <?php
                                                    if ($getServicevalue['service_banner_image'] != '') { ?>
                                                <img src="../media/Services/<?php echo $getServicevalue['service_banner_image']; ?>"
                                                    style="width: 70px;">
                                                <?php
                                                        }
                                                       ?>
                                                <br>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Name</label>
                                                <input type="hidden" id="txtId" name="txtId" class="form-control"
                                                    value="<?php echo $ID;?>">
                                                <input type="text" id="txtname" name="txtname" class="form-control"
                                                    placeholder="Name" value="<?php echo $getServicevalue['Name']; ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="URL">URL</label>
                                                <input type="text" id="txtUrl" name="txtUrl" class="form-control"
                                                    oninput="noSpace()" placeholder="URL"
                                                    value="<?php echo $getServicevalue['ServiceUrl']; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="service_img"> Service Image (100 x
                                                    70px )</label>
                                                <br>
                                                <?php
                                                 if ($getServicevalue['service_img'] != '') { ?>
                                                <img src="../media/Services/<?php echo $getServicevalue['service_img']; ?>"
                                                    style="width: 70px;">
                                                <?php
                                                     }
                                                ?>
                                                <br>
                                                <input type="file" name="service_img" id="service_img"
                                                    class="form-control-file mt-2">
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="service_icon"> Service icon (112 x
                                                    112px) </label>
                                                <br>
                                                <?php
												if ($getServicevalue['service_icon'] != '') { ?>
                                                <img src="../media/Services/<?php echo $getServicevalue['service_icon']; ?>"
                                                    style="width: 70px;">
                                                <?php
												}
												?>
                                                <br>
                                                <input type="file" name="service_icon" id="service_icon"
                                                    class="form-control-file mt-2">
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="phonenumber">Display Priority</label>
                                                <input type="number" id="txtDisplayPriority" name="txtDisplayPriority"
                                                    min="1" class="form-control" placeholder="Display priority"
                                                    value="<?php echo $getServicevalue['DisplayPriority']; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="featured">Is Featured</label>
                                                <?php
												if ($getServicevalue['featured'] == 1) {
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
                                        <!-- <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="safety">Display Safety</label>
                                                <?php
												// if ($getServicevalue['safety'] == 1) {
												?>
                                                <input type="radio" value="1" name="safety"
                                                    style="margin-left: 4px;margin-right: 4px;" checked id="">Yes
                                                <input type="radio" value="0" name="safety"
                                                    style="margin-left: 4px;margin-right: 4px;" id="">No
                                                <?php
                                                	// } else {
												?>
                                                <input type="radio" value="1" name="safety"
                                                    style="margin-left: 4px;margin-right: 4px;" id="">Yes
                                                <input type="radio" value="0" name="safety"
                                                    style="margin-left: 4px;margin-right: 4px;" id="" checked>No
                                                <?php
                                                // }
												?>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="safety">Safety Image (650*380) </label>
                                                <input type="file" name="safety_img" id="safety_img">
                                            </div>
                                        </div> -->
                                        <div class="col-lg-3">
                                            <?php
											if ($safety_img != '') {
											?>
                                            <img src="../media/safety/<?php echo $safety_img ?>" alt="" width="50px">
                                            <?php
											}
											?>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="phonenumber">Meta Title</label>
                                                <input type="text" id="txtMetaTitle" name="txtMetaTitle"
                                                    class="form-control" placeholder="Meta Title"
                                                    value="<?php echo $MetaTitle ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="phonenumber">Meta Keyword (seprated by
                                                    commas)</label>
                                                <input type="text" id="metakeyword" name="metakeyword"
                                                    class="form-control" placeholder="Meta Keyword"
                                                    value="<?php echo $MetaKeyword ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Image">Meta Description</label>
                                                <textarea id="txtMetaDesc" name="txtMetaDesc" class="form-control"
                                                    rows="4" cols="5"><?php echo $MetaDescription; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Sub-Service </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div id="sub_services">
                                        <div class="form-group row mt-3">
                                            <label for="fname" class="col-sm-3 control-label col-form-label">Heading to
                                                Display</label>
                                            <div class="col-sm-7">
                                                <input type="text" class="form-control" name="subservice_heading"
                                                    id="subservice_heading"
                                                    value="<?php echo $getServicevalue['subservice_heading']; ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" id="Addsubservice"
                                                    class="btn btn-warning btn-sm float-right"> <i
                                                        class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                    <hr>
                                    <div id="subserviceBox" class="mb-5">
                                        <?php
										$i = 1;
										$serviceID = $_GET['ID'];
										$subserviceSql = mysqli_query($conn, "select * from subservice where
                                        service_id='$serviceID' ORDER BY ID ASC");
                                         
										if (mysqli_num_rows($subserviceSql) > 0)
                                        {
										while ($subServiceRow = mysqli_fetch_assoc($subserviceSql))
                                        {
                                            $sub_service_image = $subServiceRow['sub_service_image'];
                                            $sub_services_pdf = $subServiceRow['sub_services_pdf'];
                                            $ss_image_path = "../media/Services/".$sub_service_image;
                                            $ss_pdf_path = "../media/Services/Rate-PDF/".$sub_services_pdf;

                                            $hourlyserviceyes = $subServiceRow['hourlyservice'];
                                            $hourlychecked = '';
                                            if($hourlyserviceyes == "Yes"){
                                                $hourlychecked = 'checked';
                                            }
										?>
                                        <div class="form-group row mt-4"
                                            id="remove_subservice_<?php echo $subServiceRow['ID'] ?>">
                                            <label for="fname"
                                                class="col-sm-3 control-label col-form-label">Sub-Service</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="subservice[]"
                                                    id="subservice" value="<?php echo $subServiceRow['title'] ?>">
                                            </div>
                                            <div class="col-sm-2 d-flex align-items-center">
                                                <input type="checkbox" name='hourlyservice[]' value='hourlyserviceyes' <?php echo $hourlychecked; ?> class="form-control mr-2" style='width:18px;'>
                                                <label for="hourlyservice">Hourly Price Available </label>
                                            </div>
                                            <input type="hidden" name="subserviceID[]"
                                                value="<?php echo $subServiceRow['ID'] ?>">
                                            <div class="col-sm-2">
                                                <button type="button"
                                                    class="float-right btn btn-sm btn-danger shadow-none"
                                                    onclick="remove_oldSubservice('<?php echo $subServiceRow['ID'] ?>')"><i
                                                        class="fa fa-trash"></i></button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="fname col-sm-3"
                                                class="col-sm-3 control-label col-form-label">Sub-Services Image
                                                (70*70px)</label>
                                            <div class="col-sm-6">
                                                <input type="file" class="form-control sub_services_image"
                                                    name="sub_services_image[]">
                                            </div>
                                            <div class="col-sm-3">
                                                <img src="<?php echo $ss_image_path; ?>" width="70px" />
                                            </div>
                                        </div>

                                        <div class="form-group row mt-4">
                                            <label for="fname"
                                                class="col-sm-3 control-label col-form-label">Price</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" name="subserviceprice[]"
                                                    id="subserviceprice"
                                                    value="<?php echo $subServiceRow['sub_service_price'] ?>">
                                            </div>
                                        </div>

                                        <div class="form-group row mt-4">
                                            <label for="fname" class="col-sm-3 control-label col-form-label">Exclusive
                                                service</label>
                                            <div class="col-sm-9">
                                                <textarea id="exclusiveservice" class="form-control" rows="4" cols="5"
                                                    name="exclusiveservice[]"><?php echo $subServiceRow['exclusive_service'] ?></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group row mt-4">
                                            <label for="fname" class="col-sm-3 control-label col-form-label">Inclusive
                                                Service</label>
                                            <div class="col-sm-9">
                                                <textarea id="inclusiveservice" class="form-control" rows="4" cols="5"
                                                    name="inclusiveservice[]"><?php echo $subServiceRow['inclusive_service'] ?></textarea>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <label for="fname col-sm-3"
                                                class="col-sm-3 control-label col-form-label">Spares Rate Card</label>
                                            <div class="col-sm-6">
                                                <input type="file" class="form-control sub_services_pdf"
                                                    name="sub_services_pdf[]">
                                            </div>
                                            <div class="col-sm-3">
                                                <?php if( $subServiceRow['sub_services_pdf'] != '' ){ ?>

                                                <iframe src="<?php echo $ss_pdf_path; ?>"
                                                    style="width:150px; height:100px;" frameborder="0"></iframe>
                                                <button type="button"
                                                    class="float-right btn btn-sm btn-danger shadow-none"
                                                    onclick="DeleteRateCardPDF('<?php echo $subServiceRow['ID'] ?>')">Delete  PDF</button>
                                                <?php } ?>    
                                            </div>
                                        </div>

                                        <hr style='border-bottom: 1px dashed #eb1212;'>

                                        <?php
                                        } // while
										}
                                        ?>
                                    </div>
                                    <div class="row bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Customer Issues </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Image">Box Main Heading ( DB col-6
                                                    )</label>
                                                <input type="text" id="mainheading" name="mainheading"
                                                    class="form-control"
                                                    value="<?php echo $getServicevalue['mainheading']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row  bg-primary text-white mt-3 m-2 pt-2">
                                        <div class="col-md-8">
                                            <h3>Common Issues Box </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="row m-2 mt-3">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 1 Heading</label>
                                                <input type="text" name="desc_box_h1"
                                                    value="<?php echo $getServicevalue['desc_box_h1']; ?>"
                                                    class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 1 Description</label>
                                                <textarea id="desc_box_h1_desc" name="desc_box_h1_desc"
                                                    class="form-control"><?php echo $getServicevalue['desc_box_h1_desc']; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row m-2  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Services offered Box </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="row m-2 mt-3">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 2 Heading</label>
                                                <input type="text" name="desc_box_h2" class="form-control"
                                                    value="<?php echo $getServicevalue['desc_box_h2']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 2 Description</label>
                                                <textarea id="desc_box_h2_desc" name="desc_box_h2_desc"
                                                    class="form-control"><?php echo $getServicevalue['desc_box_h2_desc']; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="row  bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Descripton Box 3 </h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div> -->
                                    <!-- <div class="row mt-3">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 3 Heading</label>
                                                <input type="text" name="desc_box_h3" class="form-control"
                                                    value="<?php
													//  echo $getServicevalue['desc_box_h3'];
													 ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="Password">Box 3 Description</label>
                                                <textarea id="desc_box_h3_desc" name="desc_box_h3_desc"
                                                    class="form-control"><?php
													// echo $getServicevalue['desc_box_h3_desc'];
													?></textarea>
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
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" name="brand_heading"
                                                id="brand_heading"
                                                value="<?php echo $getServicevalue['brand_heading']; ?>">
                                        </div>
                                        <div class="col-sm-3">
                                            <button type="button" id="AddBrand"
                                                class="btn btn-warning btn-sm float-right"><i
                                                    class="fa fa-plus"></i></button>
                                        </div>
                                    </div>
                                    <hr>
                                    <div id="brandBox" class="mb-5">
                                        <?php
										$i = 1;
										$serviceID = $_GET['ID'];
										$brandSql = mysqli_query($conn, "select * from service_brand where
                                        service_id='$serviceID' ORDER BY ID ASC");
										while ($brandRow = mysqli_fetch_assoc($brandSql)) {
										?>
                                        <div id="remove_brand_<?php echo $brandRow['ID'] ?>" class="divider">
                                            <div class="form-group row">
                                                <hr>
                                                <input type="hidden" name="BrandID[]" id="BrandID"
                                                    value="<?php echo $brandRow['ID'] ?>">
                                                <label for="fname"
                                                    class="col-sm-3 control-label col-form-label">Brand</label>
                                                <div class="col-sm-7">
                                                    <input type="text" class="form-control" name="brand_title[]"
                                                        id="brand_title" value="<?php echo $brandRow['title'] ?>">
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button"
                                                        class="float-right btn btn-sm btn-danger shadow-none"
                                                        onclick="remove_oldbrand('<?php echo $brandRow['ID'] ?>','<?php echo $brandRow['image'] ?>')"><i
                                                            class="fa fa-trash"></i></button>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label for="fname" class="col-sm-3 control-label col-form-label">Image
                                                    (60*60 px)</label>
                                                <div class="col-sm-6">
                                                    <input type="file" class="form-control" name="brand_image[]"
                                                        id="brand_image" multiple>
                                                </div>
                                                <div class="col-sm-3">
                                                    <img src="../media/brand/<?php echo $brandRow['image'] ?>" alt=""
                                                        width="50px">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label for="fname"
                                                    class="col-sm-3 control-label col-form-label">URL</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" name="brand_url[]"
                                                        id="brand_url" value="<?php echo $brandRow['url'] ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <div class="row bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Links</h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-9 "></div>
                                        <div class="col-md-3 pt-3"><button type="button" id="AddMoreKeyword"
                                                class="btn btn-warning btn-sm float-right"><i
                                                    class="fa fa-plus"></i></button></div>
                                    </div>
                                    <div class="form-group row mt-3">
                                        <label for="fname" class="col-sm-3 control-label col-form-label">Service Link
                                            Heading</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" name="serviceLinkHeading"
                                                id="serviceLinkHeading"
                                                value="<?php echo $getServicevalue['serviceLinkHeading']; ?>">
                                        </div>
                                        <label for="fname" class="col-sm-3 control-label col-form-label">City Link
                                            Heading</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" name="cityLinkHeading"
                                                id="cityLinkHeading"
                                                value="<?php echo $getServicevalue['cityLinkHeading']; ?>">
                                        </div>
                                    </div>
                                    <hr>
                                    <div id="keywordbox">
                                        <?php
										$i = 1;
										$serviceID = $_GET['ID'];
										$keywordSql = mysqli_query($conn, "select * from keywords where
                                        service_id='$serviceID' ");
										while ($keywordrow = mysqli_fetch_assoc($keywordSql)) {
										?>
                                        <div id="remove_keyword_<?php echo $keywordrow['keyword_id'] ?>"
                                            class="divider">
                                            <div class="form-group row">
                                                <label for="fname"
                                                    class="col-sm-3 control-label col-form-label">Text</label>
                                                <div class="col-sm-7">
                                                    <input type="text" class="form-control" name="keywordtext[]"
                                                        id="keywordtext"
                                                        value="<?php echo $keywordrow['keywordtext'] ?>">
                                                </div>
                                                <div class="col-sm-2 mb-2 remove_<?= $keywordrow['keyword_id'] ?>">
                                                    <button type="button"
                                                        class="float-right btn btn-sm btn-danger shadow-none"
                                                        onclick="remove_oldKeyword('<?php echo $keywordrow['keyword_id'] ?>')"><i
                                                            class="fa fa-trash"></i></button>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label for="fname"
                                                    class="col-sm-3 control-label col-form-label">Link</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" name="keywordlink[]"
                                                        id="keywordlink"
                                                        value="<?php echo $keywordrow['keywordlink'] ?>">
                                                </div>
                                                <input type="hidden" name="keywordID[]" id="keywordID"
                                                    value="<?php echo $keywordrow['keyword_id'] ?>">
                                            </div>
                                            <div class="form-group row">
                                                <label for="fname" class="col-sm-3 control-label col-form-label">Link
                                                    Type</label>
                                                <div class="col-sm-9">
                                                    <select name="linkType[]" id="linkType" class="form-control">
                                                        <?php
															if ($keywordrow['linkType'] == 'service') {
															?>
                                                        <option value="service" selected>Service</option>
                                                        <option value="place">Place</option>
                                                        <?php } else {
															?>
                                                        <option value="service">Service</option>
                                                        <option value="place" selected>Place</option>
                                                        <?php }
															?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $i++;
										} ?>
                                    </div>
                                    <div class="row bg-primary text-white pt-2">
                                        <div class="col-md-8">
                                            <h3>Frequently Asked Questions</h3>
                                        </div>
                                        <div class="col-md-4"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-9 "></div>
                                        <div class="col-md-3 pt-3"><button type="button" id="AddFaq"
                                                class="btn btn-warning btn-sm float-right"><i
                                                    class="fa fa-plus"></i></button></div>
                                    </div>
                                    <hr>
                                    <div id="faqBox">
                                        <?php
$i = 1;
$serviceID = $_GET['ID'];
$faqSql = mysqli_query($conn, "select * from faq where
 service_id='$serviceID' ");
while ($faqrow = mysqli_fetch_assoc($faqSql)) {
?>
                                        <div id="remove_faq_<?php echo $faqrow['ID'] ?>" class="divider">
                                            <div class="form-group row">
                                                <label for="fname"
                                                    class="col-sm-3 control-label col-form-label">Question</label>
                                                <div class="col-sm-7">
                                                    <input type="text" class="form-control" name="q[]" id="q"
                                                        value="<?php echo $faqrow['q'] ?>">
                                                </div>
                                                <div class="col-sm-2 mb-2 remove_<?= $faqrow['ID'] ?>"><button
                                                        type="button"
                                                        class="float-right btn btn-sm btn-danger shadow-none"
                                                        onclick="remove_oldFaq('<?php echo $faqrow['ID'] ?>')"><i
                                                            class="fa fa-trash"></i></button></div>
                                            </div>
                                            <div class="form-group row">
                                                <label for="fname"
                                                    class="col-sm-3 control-label col-form-label">Answer</label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control" name="a[]"
                                                        id="a"><?php echo $faqrow['a'] ?></textarea>
                                                </div>
                                                <input type="hidden" name="faqID[]" id="faqID"
                                                    value="<?php echo $faqrow['ID'] ?>">
                                            </div>
                                        </div>
                                        <?php $i++;
} ?>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <button type="submit" name="Submit"
                                                    class="btn btn-primary">Update</button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- use in append div  -->
                                    <input type="hidden" id="numfaq" value="1">
                                    <input type="hidden" id="numbrand" value="1">
                                    <input type="hidden" id="numsubservice" value="1">
                                    <input type="hidden" id="AddKeyword" value="1">
                                </form>
                </main>
                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
				include('../includes/common_footer.php')
				?>
                <!-- END Page Footer -->

                <?php
				include('modal-include/luandry-modal.php')
				?>






            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->
    <?php
	include('../includes/common_modules.php');
	include('../includes/common_scripts.php');
	?>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>

    <script src="../js/modules/service-configuration.js"></script>
    <script>
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_services").addClass("active");
        $("#main_services").addClass("active");
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
        font_formats: "Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Oswald=oswald; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats;Poppins",
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Poppins&display=swap'); body { font-family: Poppins; }",
        height: 300
    });
    </script>



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




    <script>
    $('#Addsubservice').click(function(e) {
        e.preventDefault();
        var numsubservice = jQuery('#numsubservice').val();
        numsubservice++;

        jQuery('#numsubservice').val(numsubservice);

        var html3 = '<div id="subservicebox' + numsubservice +
            '"><div class="form-group row mt-4">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Sub-Service</label>\
											<div class="col-sm-5">\
												<input type="text" class="form-control" name="subservice[]" id="subservice" required>\
											</div>\
                                            <div class="col-sm-2 d-flex align-items-center">\
                                                <input type="checkbox" name="hourlyservice[]" value="hourlyserviceyes" class="form-control mr-2" style="width:18px;">\
                                                <label for="hourlyservice">Hourly Price Available </label>\
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
										</div>\<div class="form-group row mt-4">\
                                            <label for="fname" class="col-sm-3 control-label col-form-label">Price</label>\
                                            <div class="col-sm-9">\
                                                <input type="text" class="form-control" name="subserviceprice[]" id="subserviceprice">\
                                            </div>\
                                        </div>\<div class="form-group row mt-4">\
                                            <label for="fname" class="col-sm-3 control-label col-form-label">Exclusive Service</label>\
                                            <div class="col-sm-9">\
                                                <textarea id="exclusiveservice" name="exclusiveservice[]" class="form-control"rows="4" cols="5"></textarea>\
                                            </div>\
                                        </div>\<div class="form-group row mt-4">\
                                            <label for="fname" class="col-sm-3 control-label col-form-label">Inclusive Service</label>\
                                            <div class="col-sm-9">\
                                               <textarea id="inclusiveservice" name="inclusiveservice[]" class="form-control"rows="4" cols="5"></textarea>\
                                            </div>\
                                        </div>\
                                        <div class="form-group row">\
											<label for="fname" class="col-sm-3 control-label col-form-label">Spares Rate Card </label>\
											<div class="col-sm-9">\
												<input type="file" class="form-control sub_services_pdf" name="sub_services_pdf[]" id="sub_services_pdf" multiple>\
											</div>\
										</div>\
										</div>';
        jQuery('#subserviceBox').append(html3);
    });

    // $('#Addsubservice').click(function(e) {

    //     console.log("hello");

    // 	e.preventDefault();

    // 	var numsubservice = jQuery('#numsubservice').val();

    // 	numsubservice++;



    // 	jQuery('#numsubservice').val(numsubservice);



    // 	var html3 = '<div id="subservicebox' + numsubservice + '" class="divider"><div class="form-group row">\

    // 									<label for="fname" class="col-sm-3 control-label col-form-label">Sub-Service</label>\

    // 									<div class="col-sm-7">\

    // 										<input type="text" class="form-control" name="subservice[]" id="subservice" required>\

    // 									</div>\

    // 									<div class="col-sm-2">\

    // 									<button type="button" id="numsubservice" class="btn btn-danger btn-sm float-right" onclick="remove_subservice(' + numsubservice + ')"><i class="fa fa-trash"></i></button>\

    // 									</div>\

    // 								</div>';

    // 	jQuery('#subserviceBox').append(html3);

    // });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.js"></script>
    <script>
    $("#enterprise_create_form").validate({
        rules: {
            service_img: {
                extension: "jpg|jpeg|png|ico|bmp|webp"
            }
        },
        messages: {
            service_img: {
                extension: 'Please choose valid image.',
            }
        },
        submitHandler: function(form) {
            form.submit();
        }
    });
    </script>
    <script>
    function noSpace() {
        var urlTextbox = document.getElementById("txtUrl");
        urlTextbox.addEventListener("input", () => {
            let url = urlTextbox.value;
            url = url.replace(/\s/g, "");
            urlTextbox.value = url;
        });
    }
    </script>
</body>

</html>