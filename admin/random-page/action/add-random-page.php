<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../../../function.inc.php');
$UserType = SessionCheck();
$conn = _connectodb();
setTimeZone();
$added_on = date("Y-m-d h:i:s");
$title = $_POST['title'];
$meta_title = $_POST['meta_title'];
$meta_description = $_POST['meta_description'];
$meta_keyword = $_POST['meta_keyword'];
$brand_heading = $_POST['brand_heading'];
$subservice_heading = $_POST['subservice_heading'];
$safety = $_POST['safety'];
$url = $_POST['url'];
$url = "" . FRONT_SITE_PATH . "repair-service/" . $url . "";



$desc_box_h1_desc = $_POST['desc_box_h1_desc'];
$desc_box_h1_desc = addslashes($desc_box_h1_desc);
$desc_box_h1_desc = trim($desc_box_h1_desc);



$desc_box_h2_desc = $_POST['desc_box_h2_desc'];
$desc_box_h2_desc = addslashes($desc_box_h2_desc);
$desc_box_h2_desc = trim($desc_box_h2_desc);


$desc_box_h3_desc = $_POST['desc_box_h3_desc'];
$desc_box_h3_desc = addslashes($desc_box_h3_desc);
$desc_box_h3_desc = trim($desc_box_h3_desc);

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
if (isset($_POST['desc_box_h3'])) {
    $desc_box_h3 = $_POST['desc_box_h3'];
} else {
    $desc_box_h3 = '';
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

$Randomsql = "Select * from  random_page where url ='$url'";

$Randomresult = mysqli_query($conn, $Randomsql);
if (mysqli_num_rows($Randomresult) > 0) {
    $_SESSION['randomExist'] = 'Url Already Exist !';
    redirect('../add-random-page.php');
} else {



    if (isset($_FILES['safety_img']['name'])  && $_FILES['safety_img']['name'] != '') {
        $extn1 = explode('.', $_FILES["safety_img"]["name"]);
        $rand1 = rand(1111, 9999);
        $str1 = str_replace(' ', '-', strtolower($Name));
        $safety_img   = $str1 . $rand1 . "." . $extn1[1];
        $path = "../../media/safety/" .  $safety_img;
        move_uploaded_file($_FILES["safety_img"]["tmp_name"], $path);
    } else {
        $safety_img = '';
    }
    $sql = "INSERT into random_page (
    title,
    meta_title,
    meta_description,
    meta_keyword,
    brand_heading,
	subservice_heading,
    added_on,
    status,
    safety,
    safety_img,
    url,
    desc_box_h1,
    desc_box_h2,
    desc_box_h3,
    desc_box_h1_desc,
    desc_box_h2_desc,
    desc_box_h3_desc,
    mainheading,
    serviceLinkHeading,
	cityLinkHeading
    )
    VALUES (
    '$title',
    '$meta_title',
    '$meta_description',
    '$meta_keyword',
    '$brand_heading',
	'$subservice_heading',
	'$added_on',
	'1',
	'$safety',
	'$safety_img',
    '$url',
    '$desc_box_h1',
    '$desc_box_h2',
    '$desc_box_h3',
    '$desc_box_h1_desc',
    '$desc_box_h2_desc',
    '$desc_box_h3_desc',
    '$mainheading',
    '$serviceLinkHeading',
	'$cityLinkHeading'
    )";
    // echo $sql;
    $result  = mysqli_query($conn, $sql);
    $last_id = $conn->insert_id;


    //insert faq (WORK FINE)
    $qArr = $_POST['q'];
    $aArr = $_POST['a'];
    foreach ($qArr as $key => $val) {
        $q = $val;
        $a = $aArr[$key];

        if ($q != '' && $a != '') {
            mysqli_query($conn, "insert into randomfaq(random_page,q,a) values('$last_id','$q' ,'$a')");
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
            mysqli_query($conn, "insert into random_keywords(random_page,keywordtext,keywordlink,linkType) values('$last_id','$keywordtext' ,'$keywordlink','$linkType')");
        }
    }

    // insert subservice (WORK FINE)
    $subserviceArr = $_POST['subservice'];
    foreach ($subserviceArr as $key => $val) {
        $subservice = $val;
        if ($subservice != '') {
            mysqli_query($conn, "insert into randomsubservice(random_page,title) values('$last_id','$subservice')");
        }
    }

    // insert brand (WORK FINE)

    $countImg = count($_FILES['brand_image']['name']);
    if ($countImg >= 1) {

        $imgArr = $_FILES['brand_image']['name'];
        $titleArr = $_POST['brand_title'];
        $urlArr = $_POST['brand_url'];
        foreach ($titleArr as $key => $val) {
            $brand_image = $imgArr[$key];
            $path = "../../media/brand/" .  $brand_image;
            move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
            $title = $val;
            $url = $urlArr[$key];
            mysqli_query($conn, "insert into  randomservicebrand(random_page,title,image,url) values('$last_id','$title' ,'$brand_image','$url')");
        }
    }
    redirect('../view-random-page.php');
}
