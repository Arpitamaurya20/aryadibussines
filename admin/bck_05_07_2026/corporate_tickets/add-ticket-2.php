<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	include('../controllers/common_controllers.php');
    include('../company/controller/company_controller.php');
    include('../branch/controller/branch_controller.php');
    include('../Services/controller/service_controller.php');
	$UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
	$conn = _connectodb();
	?>
    <meta charset="utf-8">
    <title>
    Raise Ticket
    </title>
    <meta name="description" content="Create CFL  ">
    <?php
	include('../includes/common_head_content.php');
	?>
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php
//print_r($_SESSION);
$AllCompany = getAllCompanies($conn);
$AllBranch = getAllBranchesWithName($conn);
$AllServices_array = getAllServices($conn);
$AllServices = json_decode($AllServices_array, true);
$CorporateID = -1;
$BranchID = -1;
if($UserType == "Corporate Admin")
{
    $corporate_user = true;
    $CorporateID = $_SESSION['Roles']['CorporateID'];
}
if($UserType == "Corporate Branch User")
{
    $corporate_user = true;
    $CorporateID = $_SESSION['Roles']['CorporateID'];
    $BranchID = $_SESSION['Roles']['BranchID'];
}

?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">Raise Ticket</li>

                    </ol>

                    <!-- Main Creation Form -->
                    <div id="panel-5" class="panel">
                        <div class="panel-hdr">
                            <h2>
                                Add <span class="fw-300"><i>Ticket</i></span>
                            </h2>

                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <form method="post" id="raise_ticket_form" >
                                    <div class="row">

                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Corporate <span
                                                        class="text-danger">*</span></label>
                                                <select name="corporate_name" class="select2 form-control"
                                                    id="corporate_name">
                                                    <option value="">Please Select Corporate</option>
                                                    <?php
                                                          foreach($AllCompany as $CompanyValue)
                                                          {
                                                            if($CompanyValue['ID'] !== $CorporateID)
                                                            {
                                                                continue;
                                                            }
                                                    ?>

                                                    <option value="<?php echo $CompanyValue['ID']; ?>">
                                                        <?php echo $CompanyValue['CompanyName']; ?></option>
                                                    <?php
                                                         }
                                                     ?>
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Branch <span
                                                        class="text-danger">*</span></label>
                                                <select name="branch_name" class="select2 form-control"
                                                    id="branch_name">
                                                    <option value="">Please Select Branch</option>
                                                    <?php
                                                          foreach($AllBranch as $BranchValue)
                                                          {
                                                            if($BranchID == -1)
                                                            {
                                                                if($BranchValue['CompanyID'] !== $CorporateID)
                                                                {
                                                                    continue;
                                                                }
                                                            }
                                                            else
                                                            {
                                                                if($BranchID !== $BranchValue['ID'])
                                                                {
                                                                    continue;
                                                                }
                                                            }
                                                    ?>

                                                    <option value="<?php echo $BranchValue['ID']; ?>">
                                                        <?php echo $BranchValue['BranchSite']; ?></option>
                                                    <?php
                                                         }
                                                     ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Service Type <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control w-100" name="service_type" id="service_type">
                                                    <option value="">Please Select Service</option>
                                                    <option value="AMC">AMC</option>
                                                    <option value="R&M">R&M</option>
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-lg-6">
                                                <label for="number">Service <span
                                                        class="text-danger">*</span></label>
                                                        <select class="form-control w-100" name="service_name" class="select2"
                                                    id="service_name">
                                                    <option value="">Please Select Service</option>
                                                    <?php
                                                          foreach($AllServices as $Service)
                                                          {
                                                    ?>

                                                    <option value="<?php echo $Service['ID']; ?>">
                                                        <?php echo $Service['Name']; ?></option>
                                                    <?php
                                                         }
                                                     ?>
                                                </select>
                                        </div>



                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="name"> Message </label>
                                                <textarea class="form-control w-100" name="message" id="message" cols="30" rows="3" placeholder="Type Your Message"></textarea>

                                            </div>
                                        </div>



                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <a onclick="return RaiseTicket()"  class="btn btn-info text-white" id="RaiseTicketButton"
                                                   >Raise</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
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

</body>

<script src="../js/modules/raise-ticket.js"></script>
<script>


</script>

</html>