<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	include('../controllers/common_controllers.php');
    setNavigation($_SESSION['Roles']);
	include('controller/employee_controller.php');
    include('../manage-site/controller/site_controller.php');
	$conn = _connectodb();
	$UserType = SessionCheck();
	?>
    <meta charset="utf-8">
    <title>
        Add Employee
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
$username = $_SESSION['pb_username'];
$division_array = getDivisionArray($conn);

$sitedata = getAllsite($conn);
$sitedata = json_decode($sitedata,true);


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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">TechXpert</a></li>
                        <li class="breadcrumb-item"><a href="view-employees.php">View Employees</a></li>
                        <li class="breadcrumb-item active">Add </li>

                    </ol>

                    <!-- Main Creation Form -->
                    <div id="panel-5" class="panel">
                        <div class="panel-hdr">
                            <h2>
                                Add <span class="fw-300"><i>Employee</i></span>
                            </h2>

                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <form id="add_employee_form">
                                    <div class="row">


                                        <div class="col-lg-6">

                                            <div class="form-group">
                                                <label class="form-label mb-3"> Work Type : <span
                                                        class="text-danger">*</span></label> <br>
                                                <input type="radio" value="Employee" class="work_type" name="work_type"
                                                    onchange="EnableDisableDivision(this.value)"> <label
                                                    for="employee">Employee</label>
                                                &nbsp;
                                                <input type="radio" value="Vendor" class="work_type"  name="work_type"
                                                    onchange="EnableDisableDivision(this.value)"> <label
                                                    for="vendor">Vendor</label>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">

                                            <div class="form-group">
                                                <label class="form-label mb-3"> Gender : <span
                                                        class="text-danger">*</span></label> <br>
                                                <input type="radio" value="His" name="gender_type"> <label
                                                    for="His">His</label>
                                                &nbsp;
                                                <input type="radio" value="Her" name="gender_type"> <label
                                                    for="Her">Her</label>
                                            </div>
                                        </div>

                                        <!-- <div class="col-lg-6">
                                            <div class="form-group mb-0">
                                                <label class="form-label">Employee ID</label>
                                                <input type="text" name="employee_id" id="employee_id"
                                                    class="form-control" value="TECHX" placeholder="Employee ID">

                                            </div>
                                        </div> -->

                                        <!-- <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="add division">Division <span
                                                        class="text-danger">*</span></label>
                                                <div class="col-12 p-0">
                                                    <select name="add_division" id="division" class="form-control">
                                                        <option value="">Please Select</option>
                                                        <?php
                                                        // foreach($division_array as $division)
                                                        // {
                                                        //     if($division['Division'] == "Vendor")
                                                        //         continue;
                                                        ?>
                                                        <option value="<?php
                                                        //  echo $division['Division'];
                                                         ?>">
                                                            <?php
                                                            // echo $division['Division'];
                                                            ?></option>
                                                        <?php
                                                        // }
                                                        ?>


                                                    </select>
                                                    <input type="text" name="add_division" value="Vendor"
                                                        id="division_textbox" class="form-control" readonly
                                                        style="display:none;" />
                                                </div>
                                            </div>

                                        </div> -->




                                        <div class="col-lg-6">
                                            <div class="form-group mb-0">
                                                <label class="form-label">Profile Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="employee_profile_photo"
                                                        name="employee_profile_photo" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Employee Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="employee_name" id="employee_name"
                                                    class="form-control" placeholder="Employee Name">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Designation <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="designation" id="designation"
                                                    class="form-control" placeholder="Designation">
                                            </div>
                                        </div>


                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Department <span
                                                        class="text-danger">*</span></label>
                                                <div class="col-12 p-0">
                                                    <select name="employee_department" id="employee_department"
                                                        class="form-control">
                                                        <option value="">Please Select</option>
                                                        <option value="Operation">Operation</option>
                                                        <option value="HR">HR</option>
                                                        <option value="Accountant">Accountant</option>
                                                        <option value="Finance">Finance</option>
                                                        <option value="Marketing">Marketing</option>
                                                        <option value="Precurment">Precurment</option>
                                                    </select>
                                                    <input type="text" value="NA" id="department_textbox"
                                                        class="form-control" readonly style="display:none;" />
                                                </div>
                                            </div>

                                        </div>


                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="basic">Basic </label>
                                                <input type="text" name="basic" id="Basic"
                                                    class="form-control" placeholder="Basic">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="da">DA </label>
                                                <input type="text" name="da" id="da"
                                                    class="form-control" placeholder="DA">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="hra">HRA </label>
                                                <input type="text" name="hra" id="hra"
                                                    class="form-control" placeholder="HRA">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="bonus">Bonus </label>
                                                <input type="text" name="bonus" id="bonus"
                                                    class="form-control" placeholder="Bonus">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="health_ensurance">Health Ensurance </label>
                                                <input type="text" name="health_insurance" id="health_insurance"
                                                    class="form-control" placeholder="Health Ensurance">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="others">Others </label>
                                                <input type="text" name="others" id="others"
                                                    class="form-control" placeholder="Others">
                                            </div>
                                        </div>


                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="number">UAN Number </label>
                                                <input type="text" name="uan_number" id="uan_number"
                                                    class="form-control" placeholder="UAN Number ">
                                            </div>
                                        </div>


                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Date Of Joining </label>

                                                <input type="text" class="form-control col-xl-12 col-sm-12"
                                                    name="date_of_joining" id="date_of_joining" value="" />
                                            </div>
                                        </div>


                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="email">Contact Email <span
                                                        class="text-danger">*</span></label>
                                                <input type="email" name="employee_email" id="employee_email"
                                                    class="form-control" placeholder="Contact Email">
                                            </div>
                                        </div>



                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Contact Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="employee_contact" id="employee_contact"
                                                    class="form-control" placeholder="Contact Number">
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Bank Account Name </label>
                                                <input type="text" name="bank_account_name" id="bank_account_name"
                                                    class="form-control" placeholder="Bank Account Name ">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Bank Account Number </label>
                                                <input type="text" name="bank_account_number" id="bank_account_number"
                                                    class="form-control" placeholder="Bank Account Number">
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="number">EPF Number </label>
                                                <input type="text" name="epf_number" id="epf_number"
                                                    class="form-control" placeholder="EPF Number">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="number">ESIC Number </label>
                                                <input type="text" name="esic_number" id="esic_number"
                                                    class="form-control" placeholder="ESIC Number">
                                            </div>
                                        </div>



                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="Pan"> PAN Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="employee_pan_number" id="employee_pan_number"
                                                    class="form-control" placeholder="PAN Number ">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group mb-0">
                                                <label class="form-label">PAN Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="employee_pan_img" name="employee_pan_img"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="name"> Aadhar Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="employee_aadhar" id="employee_aadhar"
                                                    class="form-control" placeholder="Aadhar Number ">
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group mb-0">
                                                <label class="form-label">Aadhar Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="employee_addhar_image"
                                                        name="employee_addhar_img" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group mb-0">
                                                <label class="form-label">Police Verification Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="employee_police_verification"
                                                        name="employee_police_verification" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="number">City </label>
                                                <select name="employee_city" class="select2 form-control w-100"
                                                    id="citydata">
                                                    <option value="">Please Select</option>
                                                    <?php
                                                          foreach($sitedata as $sitevalue)
                                                          {
                                                    ?>

                                                    <option value="<?php echo $sitevalue['site_name']; ?>">
                                                        <?php echo $sitevalue['site_name']; ?></option>
                                                    <?php
                                                         }
                                                     ?>
                                                </select>
                                            </div>
                                        </div>


                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <a class="btn btn-info text-white" id="AddEmployeeButton"
                                                    onclick="AddEmployee();">Add</a>
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
<script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
<script src="../js/modules/employee.js"></script>

<script>
$(document).ready(function() {
    $("#js-nav-menu").addClass("active");
    $("#js-nav-menu").addClass("open");
    $("#nav_employees").addClass("active");
    $('#date_of_joining').datepicker({
        format: "yyyy-mm-dd",
        todayBtn: "linked",
        clearBtn: true,
        todayHighlight: true,
        autoclose: true
    });
});

$("#citydata").select2();
</script>

</html>