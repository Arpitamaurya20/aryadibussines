<?php
session_start();
## Database configuration
include('../controllers/common_controllers.php');
$UserType = SessionCheck();
if (!($UserType == "Admin")) {
?>
<script type="text/javascript">
window.location.href = "../authentication/login.php";
</script>
<?php
}
$conn = _connectodb();
setTimeZone();

$response = array();
	$employee_name = $_POST['employee_name'];
	$employee_department = "Vendor";
    if(isset($_POST['employee_department']))
    {
    	$employee_department = $_POST['employee_department'];
    }
    $Division = $_POST['add_division'];
    $employee_email = $_POST['employee_email'];
    $employee_contact = $_POST['employee_contact'];
    $employee_pan_number = $_POST['employee_pan_number'];
	$employee_pan_image_path = '';
	$employee_aadhar_image_path = '';
	$employee_police_verify_image_path = '';
	$employee_profile_image_path = '';
	$EmpNo = GenerateEmployeeNumber($conn,$_POST['add_division'])['EmployeeNumber'];
	$DivisionSequence = GenerateEmployeeNumber($conn,$_POST['add_division'])['DivisionSequence'];
	$employee_id = $EmpNo;
	if (isset($_FILES['employee_pan_img']['name'])  && $_FILES['employee_pan_img']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_pan_img"]["name"]);
        $employee_pan_image_path   = $EmpNo."_PAN.".$extn_pan[1];
        $path = "../media/".$employee_pan_image_path;
        move_uploaded_file($_FILES["employee_pan_img"]["tmp_name"], $path);
    }

	if (isset($_FILES['employee_addhar_img']['name'])  && $_FILES['employee_addhar_img']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_addhar_img"]["name"]);
        $employee_aadhar_image_path   = $EmpNo."_ADH.".$extn_pan[1];
        $path = "../media/".$employee_aadhar_image_path;
        move_uploaded_file($_FILES["employee_addhar_img"]["tmp_name"], $path);
    }

	if (isset($_FILES['employee_police_verification']['name'])  && $_FILES['employee_police_verification']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_police_verification"]["name"]);
        $employee_police_verify_image_path   = $EmpNo."_PV.".$extn_pan[1];
        $path = "../media/".$employee_police_verify_image_path;
        move_uploaded_file($_FILES["employee_police_verification"]["tmp_name"], $path);
    }

	if (isset($_FILES['employee_profile_photo']['name'])  && $_FILES['employee_profile_photo']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_profile_photo"]["name"]);
        $employee_profile_image_path   = $EmpNo."_PP.".$extn_pan[1];
        $path = "../media/".$employee_profile_image_path;
        move_uploaded_file($_FILES["employee_profile_photo"]["tmp_name"], $path);

        $update_img = "UPDATE employees SET ProfileImage='$extn_pan' WHERE id ='$employee_id ";
	    $result 	= mysqli_query($conn, $update_img);
    }

    $employee_aadhar = $_POST['employee_aadhar'];
    $work_type = $_POST['work_type'];
    // $username = $_POST['username'];
 	// $CreatedDate = $_POST['CreatedDate'];
 	// $CreatedTime = $_POST['CreatedTime'];
    $vendor = 0;


    if($work_type == "Vendor")
    {
    	$vendor = 1;
    }
	$sql = "UPDATE  employees SET DivisionSequence='$DivisionSequence',Name='$employee_name',EmployeeNumber='$employee_id',Division='$Division',Department='$employee_department',Email='$employee_email',ContactNumber='$employee_contact',PAN='$employee_pan_number',PANImage='$employee_pan_image_path',Aadhar='$employee_aadhar',AadharImage='$employee_aadhar_image_path',ProfileImage='$employee_profile_image_path',PoliceVerificationImage='$employee_police_verify_image_path',Vendor='$vendor' WHERE id = '$employee_id'";
	$response = mysqli_query($conn, $sql);
	return $response;


// $name = $_POST['name'];
// $decs = $_POST['decs'];
// $id    = $_POST['txtId'];
// $update_story = "UPDATE testimonials SET name='$name', decs='$decs' WHERE id ='$id' ";
// $result_story 	= mysqli_query($conn, $update_story);

if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {

	$image = $_FILES['image']['name'];
	$location = "../media/testimonials/" . $image;
	move_uploaded_file($_FILES["image"]["tmp_name"], $location);


	$update_story = "UPDATE testimonials SET image='$image' WHERE id ='$id' ";
	$result_story 	= mysqli_query($conn, $update_story);
}


//$response_block_code = CreateStory($conn,$Name,$Title,$Location,$Story);
header("location:view-testimonials.php");
//echo json_encode($response_block_code);

?>