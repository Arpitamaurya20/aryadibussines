<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/city_controller.php');
include("../../employees/controller/employee_controller.php");
$conn = _connectodb();
setTimeZone();
$current_date = date("Y-m-d");
$current_time = date("H:i:s");
$corporate_lead = $_POST['corporate_lead'];
$metaTitle = $_POST['metaTitle'];
$city_name = $_POST['city_name'];
$featured = $_POST['featured'];
$metaDescription = $_POST['metaDescription'];
$CityLead = $_POST['city_lead'];
$tat_group = $_POST['tat_group'];
$State_name = $_POST["state_name"];
$url = $_POST['url'];
$rand1 = rand(1111, 9999);
$extn = explode('.', $_FILES["image"]["name"]);
$str1 = str_replace(' ', '-', strtolower($city_name));
$image   = $str1.$rand1.".".$extn[1];
$upath = "../../media/city/".$image;
move_uploaded_file($_FILES["image"]["tmp_name"], $upath);

if (isset($_FILES['topbanner']['name'])  && $_FILES['topbanner']['name'] != '') {
    $extn1 = explode('.', $_FILES["topbanner"]["name"]);
    $rand1 = rand(1111, 9999);
    $str1 = str_replace(' ', '-', strtolower($city_name));
    $topbanner   = $str1 . $rand1 . "." . $extn1[1];
    $path = "../../media/banners/" .  $topbanner;
    move_uploaded_file($_FILES["topbanner"]["tmp_name"], $path);
} else {
    $topbanner = '';
}
$sql = "INSERT INTO  citydata (CityName,StateName,StateID,CityLead,CorporateLead,image,url,topbanner,metaTitle,metaDescription,featured,TATGroupID,CreatedDate) VALUES ('$city_name','$State_name','$State_name','$CityLead','$corporate_lead','$image','$url','$topbanner','$metaTitle','$metaDescription','$featured',$tat_group,'$current_date')";
$result_insert = mysqli_query($conn, $sql);if($result_insert){}else{	echo $sql;	$error = mysqli_error($conn);	echo $error;	die();}
$last_id = $conn->insert_id;
// insert banner
$countImg = count($_FILES['citypromobanner']['name']);

if ($countImg >= 1) {
    $imgArr = $_FILES['citypromobanner']['name'];
    foreach ($imgArr as $key => $val) {
        $citypromobanner = $imgArr[$key];
        if($citypromobanner != "")
        {
            $path = "../../media/promobanner/" .  $citypromobanner;
            move_uploaded_file($_FILES["citypromobanner"]["tmp_name"][$key], $path);
            mysqli_query($conn, "insert into  citypromobanner(cityId,image) values('$last_id','$citypromobanner')");
        }
    }
}

if($CityLead != -1)
{
    $data['EmployeeID'] = $CityLead;
    $data['Role'] = "City Lead";
    $data['CreatedDate'] = $current_date;
    $data['CreatedTime'] = $CreatedTime;

    $filter = " where EmployeeID = $CityLead and Role = 'City Lead'";
    $num_rows = _getTotalRows($conn,'user_roles',$filter);
    if($num_rows == 0)
    {
        InsertUserRole($conn,$data);
    }
}
if($corporate_lead != -1)
{
    $data['EmployeeID'] = $corporate_lead;
    $data['Role'] = "City Corporate Lead";
    $data['CreatedDate'] = $current_date;
    $data['CreatedTime'] = $CreatedTime;
    $filter = " where EmployeeID = $corporate_lead and Role = 'City Corporate Lead'";
    $num_rows = _getTotalRows($conn,'user_roles',$filter);
    if($num_rows == 0)
    {
        InsertUserRole($conn,$data);
    }
}

header("location:../view-city.php");
?>