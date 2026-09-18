<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$response = array();

if (isset($_FILES['banner'])) {
    $dbh = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();
    
    $bannerFile = $_FILES['banner'];
    $addedOn = date("Y-m-d");
    
    $targetDir = "../images/banners/";
    $targetFile = $targetDir . basename($bannerFile["name"]);
    
    // Detect if running on localhost or production
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $baseUrl = "http://localhost/Projects/techxpertindia/"; 
    } else {
        $baseUrl = "https://techxpertindia.in/";
    }
    $completeUrl = $baseUrl . "images/banners/" . basename($bannerFile["name"]);
    
    // Check if the file is an actual image
    $check = getimagesize($bannerFile["tmp_name"]);
    if ($check === false) {
        $response["error"] = true;
        $response["message"] = "File is not an image.";
    } else {
        // Move the uploaded file to the target directory
        if (move_uploaded_file($bannerFile["tmp_name"], $targetFile)) {
            $data = array(
                'banner' => $completeUrl,
                'added_on' => $addedOn,
            );
            
            $banner = new Banner($conn);
            $response = $banner->addBanners($data);
            if ($response['error'] == true) {
                $response["message"] = "Technical Problem, please try again later";
            } else if ($response['error'] == false) {
                $response["message"] = "Banner added Successfully";
            }
        } else {
            $response["error"] = true;
            $response["message"] = "Sorry, there was an error uploading your file.";
        }
    }
} else {
    $response["error"] = true;
    $response["message"] = "Missing User Fields";
}

echo json_encode($response);
?>
