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
$current_date = date("Y-m-d");
$Name = $_POST['txtname'];
$image = '';
$brand_image = '';
$DisplayPriority = $_POST['txtDisplayPriority'];
//$safety = $_POST['safety'];
$safety = "-1";
$metakeyword = $_POST['metakeyword'];
$MetaDescription = $_POST['txtMetaDesc'];
$str_metadescription = addslashes($MetaDescription);
$MetaDescription = trim($str_metadescription);
$MetaTitle = $_POST['txtMetaTitle'];


$desc_box_h1_desc = $_POST['desc_box_h1_desc'];
$desc_box_h1_desc = addslashes($desc_box_h1_desc);
$desc_box_h1_desc = trim($desc_box_h1_desc);

$desc_box_h2_desc = $_POST['desc_box_h2_desc'];
$desc_box_h2_desc = addslashes($desc_box_h2_desc);
$desc_box_h2_desc = trim($desc_box_h2_desc);


if (isset($_POST['desc_box_h1'])) {
	$desc_box_h1 = $_POST['desc_box_h1'];
} else {
	$desc_box_h1 = '';
}
if (isset($_POST['desc_box_h2'])) {
	$desc_box_h2 = $_POST['desc_box_h2'];
} else {
	$desc_box_h2 = '';
}


if (isset($_POST['mainheading'])) {
	$mainheading = $_POST['mainheading'];
} else {
	$mainheading = '';
}

if (isset($_POST['brand_heading'])) {
	$brand_heading = $_POST['brand_heading'];
} else {
	$brand_heading = '';
}

if (isset($_POST['subservice_heading'])) {
	$subservice_heading = $_POST['subservice_heading'];
} else {
	$subservice_heading = '';
}
if (isset($_POST['serviceLinkHeading'])) {
	$serviceLinkHeading = $_POST['serviceLinkHeading'];
} else {
	$serviceLinkHeading = '';
}
if (isset($_POST['cityLinkHeading'])) {
	$cityLinkHeading = $_POST['cityLinkHeading'];
} else {
	$cityLinkHeading = '';
}

$featured = $_POST['featured'];
$ServiceUrl = $_POST['txtUrl'];
$service_img = "";
if (isset($_FILES['service_img']['name'])  && $_FILES['service_img']['name'] != '') {
	$extn = explode('.', $_FILES["service_img"]["name"]);
	$rand = rand(1111, 9999);
	$str = str_replace(' ', '-', strtolower($Name));
	$image   = $str . $rand . "." . $extn[1];
	$path = "../media/Services/" .  $image;
	$service_img = $image;
	move_uploaded_file($_FILES["service_img"]["tmp_name"], $path);
}

$service_icon = "";
if (isset($_FILES['service_icon']['name'])  && $_FILES['service_icon']['name'] != '') {
	$extn = explode('.', $_FILES["service_icon"]["name"]);
	$rand = rand(1111, 9999);
	$str = str_replace(' ', '-', strtolower($Name));
	$image   = $str . $rand . "." . $extn[1];
	$path = "../media/Services/" .  $image;
	$service_icon = $image;
	move_uploaded_file($_FILES["service_icon"]["tmp_name"], $path);
}
$service_banner_image = "";
if (isset($_FILES['service_banner_img']['name'])  && $_FILES['service_banner_img']['name'] != '') {
	$extn = explode('.', $_FILES["service_banner_img"]["name"]);
	$rand = rand(1111, 9999);
	$str = str_replace(' ', '-', strtolower($Name));
	$image   = $str . $rand . "." . $extn[1];
	$path = "../media/Services/" .  $image;
	$service_banner_image = $image;
	move_uploaded_file($_FILES["service_banner_img"]["tmp_name"], $path);
}
$safety_img = '';

$sql = "INSERT into services (Name,
		DisplayPriority,
		MetaDescription,
		MetaTitle,
		featured,
		MetaKeyword,
		brand_heading,
		subservice_heading,
		safety,
		safety_img,
		ServiceUrl,
		desc_box_h1,
		desc_box_h2,
		desc_box_h1_desc,
		desc_box_h2_desc,
		mainheading,
		service_banner_image,
		service_img,
		service_icon,
		serviceLinkHeading,
		cityLinkHeading
		)
		VALUES ('$Name',
		'$DisplayPriority',
		'$MetaDescription',
		'$MetaTitle',
		'$featured',
		'$metakeyword',
		'$brand_heading',
		'$subservice_heading',
		'$safety',
		'$safety_img',
		'$ServiceUrl',
		'$desc_box_h1',
		'$desc_box_h2',
		'$desc_box_h1_desc',
		'$desc_box_h2_desc',
		'$mainheading',
		'$service_banner_image',
		'$service_img',
		'$service_icon',
		'$serviceLinkHeading',
		'$cityLinkHeading'

		)";
//echo $sql."<br>";
$result	= mysqli_query($conn, $sql);
if(!$result)
{
	$error = mysqli_error($conn);
	echo $error;
}
$last_id = $conn->insert_id;

//insert faq (WORK FINE)
$qArr = $_POST['q'];
$aArr = $_POST['a'];
foreach ($qArr as $key => $val) {
	$q = $val;
	$a = $aArr[$key];

	if ($q != '' && $a != '') {
		mysqli_query($conn, "insert into faq(service_id,q,a) values('$last_id','$q' ,'$a')");
	}
}

//insert keyword (WORK FINE)
$keywordtextArr = $_POST['keywordtext'];
$keywordlinkArr = $_POST['keywordlink'];
$linkTypeArr = $_POST['linkType'];

foreach ($keywordtextArr as $key => $val) {

	$keywordtext = $val;
	$keywordlink = $keywordlinkArr[$key];
	$linkType = $linkTypeArr[$key];

	if ($keywordtext != '' && $keywordlink != '') {
		mysqli_query($conn, "insert into keywords(service_id,keywordtext,keywordlink,linkType) values('$last_id','$keywordtext' ,'$keywordlink','$linkType')");

	}
}


// insert subservice (WORK FINE)
/*$subserviceArr = $_POST['subservice'];
$subservicesimage = '';
foreach ($subserviceArr as $key => $val) {
	$subservice = $val;
	if (isset($_FILES['sub_services_image']['name'])  && $_FILES['sub_services_image']['name'] != '') {
		$extn = explode('.', $_FILES["sub_services_image"]["name"]);
		$rand = rand(1111, 9999);
		$str = str_replace(' ', '-', strtolower($Name));
		$image   = $str.$rand.".".$extn[1];
		$path = "../media/Services/".$image;
		move_uploaded_file($_FILES["sub_services_image"]["tmp_name"], $path);
	}
	if ($subservice != '') {
		mysqli_query($conn, "INSERT into subservice (service_id,title,sub_services_image) values('$last_id','$subservice', '$subservicesimage')");
	}
}
*/
// insert subservice
$countImg = count($_FILES['sub_services_image']['name']);
if ($countImg >= 1) {
	$ss_imgArr = $_FILES['sub_services_image']['name'];
	$pdfArr = $_FILES['sub_services_pdf']['name'];
	$ss_Price = $_POST['subserviceprice'];
	$exclusive_service = $_POST['exclusiveservice'];
	$inclusive_service = $_POST['inclusiveservice'];
	$ss_titleArr = $_POST['subservice'];
	$hourlyservice = $_POST['hourlyservice'];

	foreach ($ss_titleArr as $key => $val) {
		$sub_service_image = $ss_imgArr[$key];
		$sub_services_pdf = $pdfArr[$key];
		$sub_services_price = $ss_Price[$key];
		$sub_exclusive_service = $exclusive_service[$key];
		$sub_inclusive_service = $inclusive_service[$key];
		$sub_hourlyservice = isset($hourlyservice[$key]) ? $hourlyservice[$key] : 'hourlyserviceno';

		$hourlyserviceStatus = ($sub_hourlyservice == 'hourlyserviceyes') ? 'Yes' : 'No';

		$path = "../media/Services/".$sub_service_image;
		move_uploaded_file($_FILES["sub_services_image"]["tmp_name"][$key], $path);

		$pdfpath = "../media/Services/Rate-PDF/" .  $sub_services_pdf;
		move_uploaded_file($_FILES["sub_services_pdf"]["tmp_name"][$key], $pdfpath);

		$title = $val;
		if ($title != '') 
		{
			$ss_query = "INSERT into subservice (service_id,title,sub_service_image,sub_service_price,exclusive_service,inclusive_service,sub_services_pdf,hourlyservice) values('$last_id','$title', '$sub_service_image', '$sub_services_price','$sub_exclusive_service','$sub_inclusive_service','$sub_services_pdf','$hourlyserviceStatus')";
			$result	= mysqli_query($conn, $ss_query);
			if(!$result)
			{
				$error = mysqli_error($conn);
				echo $error;
			}
		}
	}
}
// insert brand (WORK FINE)

$countImg = count($_FILES['brand_image']['name']);
if ($countImg >= 1) {
	$imgArr = $_FILES['brand_image']['name'];
	$titleArr = $_POST['brand_title'];
	$urlArr = $_POST['brand_url'];
	foreach ($titleArr as $key => $val) {
		if($val != "")
		{
			$brand_image = $imgArr[$key];
			$path = "../media/brand/" .  $brand_image;
			move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
			$title = $val;
			$url = $urlArr[$key];
			mysqli_query($conn, "insert into  service_brand(service_id,title,image,url) values('$last_id','$title' ,'$brand_image','$url')");
		}
	}
}

header("location:view-services");

?>