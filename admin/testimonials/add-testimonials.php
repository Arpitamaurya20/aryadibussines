<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

    <?php
	include('../controllers/common_controllers.php');
	include('controller/testimonials_controller.php');

    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
	$conn = _connectodb();
	$EnterpriseID = "";

	?>
    <meta charset="utf-8">
    <title>
        Add Testimonials
    </title>
    <meta name="description" content="Create CFL  ">
    <?php
	include('../includes/common_head_content.php');
	?>
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/dropzone/dropzone.css">
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
                        <li class="breadcrumb-item"><a href="view-testimonials">View Testimonials</a></li>
                        <li class="breadcrumb-item active">Add </li>

                    </ol>

                    <!-- Main Creation Form -->
                    <div id="panel-5" class="panel">
                        <div class="panel-hdr">
                            <h2>
                                Add <span class="fw-300"><i>Testimonials</i></span>
                            </h2>

                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">

                                <form id="enterprise_create_form" method="post" action="create_testimonials_action.php"
                                    enctype="multipart/form-data">

                                    <div class="row">


                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Name</label>
                                                <input type="text" id="name" name="name" class="form-control"
                                                    placeholder="Name" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Image ( 85 × 92 px )</label>
                                                <input type="file" id="image" name="image" class="form-control"
                                                    required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">


                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="Description">Testimonal Description
                                                </label>
                                                <input type="text" id="decs" name="decs" class="form-control"
                                                    placeholder="Enter Testimonal" required>
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
                                    </div>
                                </form>
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
            $("#nav_testimonal").addClass("active");
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
</body>


</html>