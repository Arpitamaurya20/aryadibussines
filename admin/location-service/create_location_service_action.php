<?php





session_start();





## Database configuration





include('../controllers/common_controllers.php');





include('controller/location_service_controller.php');


$conn = _connectodb();


setTimeZone();





// echo '<pre>';


// print_r($_POST);





$current_date = date("Y-m-d h:i:s");





$title = $_POST['title'];


$CityId = $_POST['CityId'];


$service = $_POST['service'];


$url = $_POST['url'];


$txtMetaTitle = $_POST['txtMetaTitle'];


$metakeyword = $_POST['metakeyword'];


$txtMetaDesc = $_POST['txtMetaDesc'];


$safety = $_POST['safety'];


$featured = $_POST['featured'];


$service_position = $_POST['service_position'];


$brand_image = '';


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








if (isset($_FILES['safety_img']['name'])  && $_FILES['safety_img']['name'] != '') {


    $extn1 = explode('.', $_FILES["safety_img"]["name"]);


    $rand1 = rand(1111, 9999);


    $str1 = str_replace(' ', '-', strtolower($Name));


    $safety_img   = $str1 . $rand1 . "." . $extn1[1];


    $path = "../media/safety/" .  $safety_img;


    move_uploaded_file($_FILES["safety_img"]["tmp_name"], $path);


} else {


    $safety_img = '';


}





 $sql = "INSERT into location_services (title,


		CityId,


		service,


		url,


		MetaTitle,


		metakeyword,


        MetaDescription,


		brand_heading,


		subservice_heading,


		safety,


		safety_img,


		desc_box_h1,


		desc_box_h2,


		desc_box_h3,


		desc_box_h1_desc,


		desc_box_h2_desc,


		desc_box_h3_desc,


		mainheading,


		serviceLinkHeading,


		cityLinkHeading,


        status,


        added_on,


        featured,


        service_position


		)


		VALUES ('$title',


		'$CityId',


		'$service',


		'$url',


		'$txtMetaTitle',


		'$metakeyword',


		'$txtMetaDesc',


		'$brand_heading',


		'$subservice_heading',


		'$safety',


		'$safety_img',


		'$desc_box_h1',


		'$desc_box_h2',


		'$desc_box_h3',


		'$desc_box_h1_desc',


		'$desc_box_h2_desc',


		'$desc_box_h3_desc',


		'$mainheading',


		'$serviceLinkHeading',


		'$cityLinkHeading',


        '1',


        '$current_date',


        '$featured',


        '$service_position'


		)";


$result    = mysqli_query($conn, $sql);


$last_id = $conn->insert_id;





//insert faq (WORK FINE)


$qArr = $_POST['q'];


$aArr = $_POST['a'];


foreach ($qArr as $key => $val) {


    $q = $val;


    $a = $aArr[$key];





    if ($q != '' && $a != '') {


        mysqli_query($conn, "insert into locationfaq(location_service_id,q,a) values('$last_id','$q' ,'$a')");


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


        mysqli_query($conn, "insert into locationkeywords(location_service_id,keywordtext,keywordlink,linkType) values('$last_id','$keywordtext' ,'$keywordlink','$linkType')");


    }


}





// insert subservice (WORK FINE)


$subserviceArr = $_POST['subservice'];


foreach ($subserviceArr as $key => $val) {


    $subservice = $val;


    if ($subservice != '') {


        mysqli_query($conn, "insert into locationsubservice(location_service_id,title) values('$last_id','$subservice')");


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


        $path = "../media/brand/" .  $brand_image;


        move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);


        $title = $val;


        $url = $urlArr[$key];


        mysqli_query($conn, "insert into  locationservice_brand(location_service_id,title,image,url) values('$last_id','$title' ,'$brand_image','$url')");


       


    }


}


header("location:view_location_service");