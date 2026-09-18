<?php
session_start();
## Database configuration
include('../controllers/common_controllers.php');

include('controller/location_service_controller.php');
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
$added_on = date("Y-m-d h:i:s");
$title = $_POST['title'];
$CityId = $_POST['CityId'];
$service = $_POST['service'];
$url = $_POST['url'];
$safety = $_POST['safety'];
$featured = $_POST['featured'];
$ID    = $_POST['locationId'];
$txtMetaDesc = $_POST['txtMetaDesc'];
$metakeyword = $_POST['metakeyword'];
$service_position = $_POST['service_position'];

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





$update_story = "UPDATE location_services SET title='$title',CityId='$CityId',service='$service',safety='$safety',featured='$featured',brand_heading='$brand_heading',subservice_heading='$subservice_heading',mainheading='$mainheading',url='$url', desc_box_h1_desc='$desc_box_h1_desc',desc_box_h2_desc='$desc_box_h2_desc',desc_box_h3_desc='$desc_box_h3_desc',desc_box_h1='$desc_box_h1',MetaDescription='$txtMetaDesc',metakeyword='$metakeyword',desc_box_h2='$desc_box_h2',desc_box_h3='$desc_box_h3',MetaTitle='$MetaTitle', serviceLinkHeading='$serviceLinkHeading', cityLinkHeading='$cityLinkHeading',service_position='$service_position' WHERE id ='$ID' ";

$result_story     = mysqli_query($conn, $update_story);

if (isset($_FILES['safety_img']['name']) && $_FILES['safety_img']['name'] != '') {
    $safety_img   = $_FILES['safety_img']['name'];
    $upath1 = "../media/safety/" . $_FILES['safety_img']['name'];
    move_uploaded_file($_FILES["safety_img"]["tmp_name"], $upath1);
    $update_service = "UPDATE location_services SET safety_img='$safety_img' WHERE id ='$ID' ";
    //echo $update_service;
    $result_service     = mysqli_query($conn, $update_service);
}


// update subservice (WORK FINE)
if (isset($_POST['subservice']) != '') {
    if (isset($_POST['subserviceID']) != '') {
        $subserviceIDarr = $_POST['subserviceID'];
    } else {
        $subserviceIDarr = '';
    }
    $subserviceArr = $_POST['subservice'];
    foreach ($subserviceArr as $key => $val) {
        $subservice = $val;
        if (isset($subserviceIDarr[$key])) {
            $SubID = $subserviceIDarr[$key];
            mysqli_query($conn, "update locationsubservice set title='$subservice' where ID='$SubID'");
        } else {
            mysqli_query($conn, "insert into locationsubservice(location_service_id ,title) values('$ID','$subservice')");
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
            mysqli_query($conn, "update locationfaq set q='$q', a='$a' where ID='$faqID'");
        } else {
            mysqli_query($conn, "insert into locationfaq(location_service_id,q,a) values('$ID','$q' ,'$a')");
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
            mysqli_query($conn, "update locationkeywords set keywordtext='$keywordtext', keywordlink='$keywordlink',linkType='$linkType' where ID='$keywordID'");
        } else {
            mysqli_query($conn, "insert into locationkeywords(location_service_id ,keywordtext,keywordlink,linkType) values('$ID','$keywordtext' ,'$keywordlink','$linkType')");
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
                    $update_sql = "update locationservice_brand set location_service_id ='$ID',title='$brand_title',image='$brand_image',url='$brand_url' where ID='" . $_POST['BrandID'][$key] . "'";
                    $path = "../media/brand/" .  $brand_image;
                    move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
                }
            } else {
                $update_sql = "update locationservice_brand set location_service_id ='$ID',title='$brand_title',url='$brand_url' where ID='$BrandID'";
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
            mysqli_query($conn, "insert into  locationservice_brand(location_service_id ,title,image,url) values('$ID','$brand_title' ,'$brand_image','$brand_url')");

            $path = "../media/brand/" .  $brand_image;
            move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
            // echo "new row insert";
        }
    }
}

header("location:view_location_service");
