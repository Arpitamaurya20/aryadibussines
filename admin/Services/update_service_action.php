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
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM subservice LIKE 'UOM'");
if ($colCheck && mysqli_num_rows($colCheck) == 0) {
	mysqli_query($conn, "ALTER TABLE subservice ADD COLUMN UOM varchar(100) NOT NULL DEFAULT '' AFTER sub_service_price");
}
$descColCheck = mysqli_query($conn, "SHOW COLUMNS FROM subservice LIKE 'description'");
if ($descColCheck && mysqli_num_rows($descColCheck) == 0) {
	$uomExists = mysqli_query($conn, "SHOW COLUMNS FROM subservice LIKE 'UOM'");
	$afterClause = ($uomExists && mysqli_num_rows($uomExists) > 0) ? ' AFTER UOM' : ' AFTER sub_service_price';
	mysqli_query($conn, "ALTER TABLE subservice ADD COLUMN description TEXT NULL" . $afterClause);
}
setTimeZone();
$current_date = date("Y-m-d");
$Name = $_POST['txtname'];
$image = '';
$brand_image = '';
$DisplayPriority = $_POST['txtDisplayPriority'];
$featured = $_POST['featured'];
$metakeyword = $_POST['metakeyword'];
$ServiceUrl = $_POST['txtUrl'];
$ID    = $_POST['txtId'];

$txtMetaDesc=$_POST['txtMetaDesc'];
$desc_box_h1_desc = $_POST['desc_box_h1_desc'];
$desc_box_h1_desc = addslashes($desc_box_h1_desc);
$desc_box_h1_desc = trim($desc_box_h1_desc);



$desc_box_h2_desc = $_POST['desc_box_h2_desc'];
$desc_box_h2_desc = addslashes($desc_box_h2_desc);
$desc_box_h2_desc = trim($desc_box_h2_desc);


// $desc_box_h3_desc = $_POST['desc_box_h3_desc'];
// $desc_box_h3_desc = addslashes($desc_box_h3_desc);
// $desc_box_h3_desc = trim($desc_box_h3_desc);

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

$MetaTitle = $_POST['txtMetaTitle'];
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
if (isset($_POST['mainheading'])) {
	$mainheading = $_POST['mainheading'];
} else {
	$mainheading = '';
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





$update_story = "UPDATE services SET Name='$Name',metakeyword='$metakeyword',brand_heading='$brand_heading',subservice_heading='$subservice_heading',mainheading='$mainheading',DisplayPriority='$DisplayPriority',featured='$featured', desc_box_h1_desc='$desc_box_h1_desc',desc_box_h2_desc='$desc_box_h2_desc',desc_box_h1='$desc_box_h1',MetaDescription='$txtMetaDesc',desc_box_h2='$desc_box_h2',MetaTitle='$MetaTitle', ServiceUrl='$ServiceUrl', serviceLinkHeading='$serviceLinkHeading', cityLinkHeading='$cityLinkHeading' WHERE ID ='$ID' ";

$result_story 	= mysqli_query($conn, $update_story);
if (isset($_FILES['service_img']['name']) && $_FILES['service_img']['name'] != '') {
	$service_Image   = $_FILES['service_img']['name'];
	$upath = "../media/Services/" . $_FILES['service_img']['name'];
	move_uploaded_file($_FILES["service_img"]["tmp_name"], $upath);
	$update_service = "UPDATE services SET service_img='$service_Image' WHERE ID ='$ID' ";
	//echo $update_service;
	$result_service = mysqli_query($conn, $update_service);
}

if (isset($_FILES['service_icon']['name']) && $_FILES['service_icon']['name'] != '') {
	$service_icon   = $_FILES['service_icon']['name'];
	$upath = "../media/Services/" . $_FILES['service_icon']['name'];
	move_uploaded_file($_FILES["service_icon"]["tmp_name"], $upath);
	$update_service = "UPDATE services SET service_icon='$service_icon' WHERE ID ='$ID' ";
	//echo $update_service;
	$result_service 	= mysqli_query($conn, $update_service);
}

if (isset($_FILES['service_banner_img']['name']) && $_FILES['service_banner_img']['name'] != '') {
	$service_banner_image   = $_FILES['service_banner_img']['name'];
	$upath = "../media/Services/" . $_FILES['service_banner_img']['name'];
	move_uploaded_file($_FILES["service_banner_img"]["tmp_name"], $upath);
	$update_service = "UPDATE services SET service_banner_image ='$service_banner_image' WHERE ID ='$ID' ";
	//echo $update_service;
	$result_service 	= mysqli_query($conn, $update_service);
}


// update subservice (WORK FINE)
// var_dump($_FILES['sub_services_image']['name']);
if (isset($_POST['subservice']) != '') {
	$imgArr = $_FILES['sub_services_image']['name'];
	$pdfArr = $_FILES['sub_services_pdf']['name'];
	$ss_Price = $_POST['subserviceprice'];
	$ss_UOM = isset($_POST['subserviceuom']) ? $_POST['subserviceuom'] : array();
	$ss_Description = isset($_POST['subservicedescription']) ? $_POST['subservicedescription'] : array();
	$exclusive_service = $_POST['exclusiveservice'];
	$inclusive_service = $_POST['inclusiveservice'];
	$hourlyservice = $_POST['hourlyservice'];

	if (isset($_POST['subserviceID'])) {
		$subserviceIDArr = $_POST['subserviceID'];
	} else {
		$subserviceIDArr = '';
	}
	

	$subserviceArr = $_POST['subservice'];
	foreach ($subserviceArr as $key => $val) {
		$sub_services_image = $imgArr[$key];
		$sub_services_pdf = $pdfArr[$key];
		$sub_services_price = $ss_Price[$key];
		$sub_services_uom = isset($ss_UOM[$key]) ? mysqli_real_escape_string($conn, $ss_UOM[$key]) : '';
		$sub_description = isset($ss_Description[$key]) ? mysqli_real_escape_string($conn, trim($ss_Description[$key])) : '';
		$sub_exclusive_service = $exclusive_service[$key];
		$sub_inclusive_service = $inclusive_service[$key];
		$sub_hourlyservice = isset($hourlyservice[$key]) ? $hourlyservice[$key] : 'hourlyserviceno';

		$hourlyserviceStatus = ($sub_hourlyservice == 'hourlyserviceyes') ? 'Yes' : 'No';

		$subservice = $val;
		if (isset($subserviceIDArr[$key])) {
			$subserviceID = $subserviceIDArr[$key];
			// if (isset($_FILES['sub_services_image']['name']) == '') {
			if ($_FILES['sub_services_image']['name'][$key] != '' || $_FILES['sub_services_pdf']['name'][$key] != '') {
				if ($_POST['subserviceID'][$key]) {
					$update_sql = "update subservice set service_id='$ID',title='$subservice', sub_service_price='$sub_services_price', UOM='$sub_services_uom', description='$sub_description', exclusive_service='$sub_exclusive_service', inclusive_service='$sub_inclusive_service',hourlyservice='$hourlyserviceStatus' where ID='" . $_POST['subserviceID'][$key] . "'";

				}

				if ($_FILES['sub_services_image']['name'][$key] != '') {
					$update_sql = "update subservice set sub_service_image='$sub_services_image' where ID='" . $_POST['subserviceID'][$key] . "'";
					$path = "../media/Services/" .  $sub_services_image;
					move_uploaded_file($_FILES["sub_services_image"]["tmp_name"][$key], $path);
					
				}

				if ($_FILES['sub_services_pdf']['name'][$key] != '') {
					$update_sql = "update subservice set sub_services_pdf='$sub_services_pdf' where ID='" . $_POST['subserviceID'][$key] . "'";
					$pdfpath = "../media/Services/Rate-PDF/" .  $sub_services_pdf;
		             move_uploaded_file($_FILES["sub_services_pdf"]["tmp_name"][$key], $pdfpath);
				}
			} else {
				$update_sql = "update subservice set service_id='$ID',title='$subservice', sub_service_price='$sub_services_price', UOM='$sub_services_uom', description='$sub_description', exclusive_service='$sub_exclusive_service',inclusive_service='$sub_inclusive_service',hourlyservice='$hourlyserviceStatus' where ID='$subserviceID'";
				
				// echo $update_sql;
			}
			mysqli_query($conn, $update_sql);
		}

		else {
					$update_sql = "update subservice set service_id='$ID',title='$subservice',sub_service_image='$sub_services_image', sub_service_price='$sub_services_price', UOM='$sub_services_uom', description='$sub_description', exclusive_service='$sub_exclusive_service',inclusive_service='$sub_inclusive_service',sub_services_pdf='$sub_services_pdf',hourlyservice='$hourlyserviceStatus' where ID='" . $_POST['subserviceID'][$key] . "'";
					$sql = "insert into  subservice(service_id,title,sub_service_image,sub_service_price,UOM,description,exclusive_service,inclusive_service,sub_services_pdf,hourlyservice) values('$ID','$subservice' ,'$sub_services_image','$sub_services_price','$sub_services_uom','$sub_description','$sub_exclusive_service','$sub_inclusive_service','$sub_services_pdf','$hourlyserviceStatus')";

			mysqli_query($conn,$sql);

			$path = "../media/Services/" .  $sub_services_image;
			move_uploaded_file($_FILES["sub_services_image"]["tmp_name"][$key], $path);

			$pdfpath = "../media/Services/Rate-PDF/" .  $sub_services_pdf;
				move_uploaded_file($_FILES["sub_services_pdf"]["tmp_name"][$key], $pdfpath);

		}
	}
}

//update  faq (WORK FINE)
if (isset($_POST['q']) != '' && isset($_POST['a']) != '') {
	if (isset($_POST['faqID'])) {
		$faqIDarr = $_POST['faqID'];
	} else {
		$faqIDarr = "";
	}
	$qArr = $_POST['q'];
	$aArr = $_POST['a'];
	foreach ($qArr as $key => $val) {
		$q = $val;
		$a = $aArr[$key];
		if (isset($faqIDarr[$key])) {
			$faqID = $faqIDarr[$key];
			mysqli_query($conn, "update faq set q='$q', a='$a' where ID='$faqID'");
		} else {
			mysqli_query($conn, "insert into faq(service_id,q,a) values('$ID','$q' ,'$a')");
		}
	}
}

//update  keyword (WORK FINE)
if (isset($_POST['keywordtext']) != '' && isset($_POST['keywordlink']) != '') {
	if (isset($_POST['keywordID'])) {
		$keywordIDarr = $_POST['keywordID'];
	} else {
		$keywordIDarr = "";
	}

	$keywordtextArr = $_POST['keywordtext'];
	$keywordlinkArr = $_POST['keywordlink'];
	$linkTypeArr = $_POST['linkType'];
	foreach ($keywordtextArr as $key => $val) {
		$keywordtext = $val;
		$keywordlink = $keywordlinkArr[$key];
		$linkType = $linkTypeArr[$key];
		if (isset($keywordIDarr[$key])) {
			$keywordID = $keywordIDarr[$key];
			mysqli_query($conn, "update keywords set keywordtext='$keywordtext', keywordlink='$keywordlink',linkType='$linkType' where keyword_id='$keywordID'");
		} else {
			mysqli_query($conn, "insert into keywords(service_id,keywordtext,keywordlink,linkType) values('$ID','$keywordtext' ,'$keywordlink','$linkType')");
		}
	}
}

// update brand


if (isset($_POST['brand_title']) != '' && isset($_POST['brand_url']) != '') {
	$imgArr = $_FILES['brand_image']['name'];

	if (isset($_POST['BrandID'])) {
		$BrandIDArr = $_POST['BrandID'];
	} else {
		$BrandIDArr = '';
	}

	$brand_titleArr = $_POST['brand_title'];
	$brand_urlArr = $_POST['brand_url'];
	foreach ($brand_titleArr as $key => $val) {
		$brand_image = $imgArr[$key];
		$brand_title = $val;
		$brand_url = $brand_urlArr[$key];
		if (isset($BrandIDArr[$key])) {
			$BrandID = $BrandIDArr[$key];
			// if (isset($_FILES['brand_image']['name']) == '') {
			if ($_FILES['brand_image']['name'][$key] != '') {
				if ($_POST['BrandID'][$key]) {
					$update_sql = "update service_brand set service_id='$ID',title='$brand_title',image='$brand_image',url='$brand_url' where ID='" . $_POST['BrandID'][$key] . "'";
					$path = "../media/brand/" .  $brand_image;
					move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
				}
			} else {
				$update_sql = "update service_brand set service_id='$ID',title='$brand_title',url='$brand_url' where ID='$BrandID'";
			}


			// die();

			// if (in_array("", $_FILES['brand_image']['name'])) {
			// 	$update_sql = "update service_brand set service_id='$ID',title='$brand_title',url='$brand_url' where ID='$BrandID'";
			// } else {
			// 	$update_sql = "update service_brand set service_id='$ID',title='$brand_title',image='$brand_image',url='$brand_url' where ID='$BrandID'";
			// 	$path = "../media/brand/" .  $brand_image;
			// 	move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
			// }
			mysqli_query($conn, $update_sql);
		}


		// new brand insert
		else {
			mysqli_query($conn, "insert into  service_brand(service_id,title,image,url) values('$ID','$brand_title' ,'$brand_image','$brand_url')");

			$path = "../media/brand/" .  $brand_image;
			move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
			// echo "new row insert";
		}
	}
}

header("location:view-services");


?>