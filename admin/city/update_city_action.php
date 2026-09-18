<?php
session_start();
## Database configuration
include('../controllers/common_controllers.php');
include('controller/city_controller.php');
include('../employees/controller/employee_controller.php');
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

$current_date = date("Y-m-d");
$current_time = date("H:i:s");
$CityName = $_POST['CityName'];
$url = $_POST['url'];
$ID = $_POST['CityId'];
$featured = $_POST['featured'];
$CityName = $_POST['CityName'];
$metaTitle = $_POST['metaTitle'];
$metaDescription = $_POST['metaDescription'];
$CityLead = $_POST['city_lead'];
$State_name = $_POST["state_name"];
$corporate_lead = $_POST['corporate_lead'];
$tat_group = $_POST['tat_group'];
$old_city_details = getSingleCityByID($conn, $ID);
$update_city = "UPDATE citydata SET CityName='$CityName', url='$url',featured='$featured', metaTitle='$metaTitle', metaDescription='$metaDescription', CityLead='$CityLead', CorporateLead='$corporate_lead', StateName='$State_name', StateID='$State_name',TATGroupID = $tat_group WHERE CityId ='$ID' ";

$result_story 	= mysqli_query($conn, $update_city);

if($old_city_details['CityName'] != $CityName)
{
	$old_city_name = $old_city_details['CityName'];
	// Update City in employees
	$sql_update_city_name = " City = '$CityName' where City = '$old_city_name'";
	_UpdateTableRecords($conn,'employees',$sql_update_city_name);

	// Update BranchCity in branch
	$sql_update_city_name = " BranchCity = '$CityName' where BranchCity = '$old_city_name'";
	_UpdateTableRecords($conn,'branch',$sql_update_city_name);

	// Update City in confirm_booking
	$sql_update_city_name = " City_name = '$CityName' where City_name = '$old_city_name'";
	_UpdateTableRecords($conn,'confirm_booking',$sql_update_city_name);

	// Update City in confirm_booking
	$sql_update_city_name = " City = '$CityName' where City = '$old_city_name'";
	_UpdateTableRecords($conn,'customers',$sql_update_city_name);
}

if($CityLead != $old_city_details['CityLead'])
{
	// Delete old role
	$EmployeeID = $old_city_details['CityLead'];
	$query_parameter = " where EmployeeID = $EmployeeID and Role = 'City Lead'";
	delete_identity_filter($conn,"user_roles",$query_parameter);

	if($CityLead != -1)
	{
		$data['EmployeeID'] = $CityLead;
		$data['Role'] = "City Lead";
		$data['CreatedDate'] = $current_date;
		$data['CreatedTime'] = $current_time;
    	InsertUserRole($conn,$data);
	}
}
if($corporate_lead != $old_city_details['CorporateLead'])
{
	// Delete old role
	$EmployeeID = $old_city_details['CorporateLead'];
	$query_parameter = " where EmployeeID = $EmployeeID and Role = 'City Corporate Lead'";
	delete_identity_filter($conn,"user_roles",$query_parameter);

	if($corporate_lead != -1)
	{
		$data['EmployeeID'] = $corporate_lead;
		$data['Role'] = "City Corporate Lead";
		$data['CreatedDate'] = $current_date;
		$data['CreatedTime'] = $current_time;
    	InsertUserRole($conn,$data);
	}
}

if (isset($_FILES['cityimage']['name']) && $_FILES['cityimage']['name'] != '') {


	$rand1 = rand(1111, 9999);
	$extn = explode('.', $_FILES["cityimage"]["name"]);
	$str1 = str_replace(' ', '-', strtolower($CityName));
	$cityImage   = $str1 . $rand1 . "." . $extn[1];
	$upath = "../media/city/" . $cityImage;

	move_uploaded_file($_FILES["cityimage"]["tmp_name"], $upath);

	$update_city = "UPDATE citydata SET image='$cityImage' WHERE CityId ='$ID' ";
	$result_story 	= mysqli_query($conn, $update_city);
}

if (isset($_FILES['topbanner']['name']) && $_FILES['topbanner']['name'] != '') {
	$topbanner   = $_FILES['topbanner']['name'];
	$upath1 = "../media/banners/" . $_FILES['topbanner']['name'];
	move_uploaded_file($_FILES["topbanner"]["tmp_name"], $upath1);
	$update_service = "UPDATE citydata SET topbanner='$topbanner' WHERE CityId ='$ID' ";
	//echo $update_service;
	$result_service 	= mysqli_query($conn, $update_service);
}

// update banner



$imgArr = $_FILES['banner_image']['name'];

if (isset($_POST['bannerID'])) {
	$bannerIDArr = $_POST['bannerID'];
} else {
	$bannerIDArr = '';
}
$imgArr = $_FILES['banner_image']['name'];
foreach ($imgArr as $key => $val) {
	$banner_image = $imgArr[$key];
	if (isset($bannerIDArr[$key])) {
		$bannerID = $bannerIDArr[$key];
		if ($_FILES['banner_image']['name'][$key] != '') {
			if ($_POST['bannerID'][$key]) {
				$update_sql = "update citypromobanner set CityId='$ID',image='$banner_image' where ID='" . $_POST['bannerID'][$key] . "'";
				$path = "../media/promobanner/" .  $banner_image;
				move_uploaded_file($_FILES["banner_image"]["tmp_name"][$key], $path);
				mysqli_query($conn, $update_sql);
			}
		}
	}


	// new banner insert
	else {
		mysqli_query($conn, "insert into  citypromobanner(CityId,image) values('$ID','$banner_image')");

		$path = "../media/promobanner/" .  $banner_image;
		move_uploaded_file($_FILES["banner_image"]["tmp_name"][$key], $path);
	}
}


header("location:view-city.php");

?>