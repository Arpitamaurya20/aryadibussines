<?php
$allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 -,./';

// Create a function to filter out unwanted characters
function remove_special_chars($str, $allowed_chars) {
    $result = '';
    for ($i = 0; $i < strlen($str); $i++) {
        if (strpos($allowed_chars, $str[$i]) !== false) {
            $result .= $str[$i];
        }
    }
    return $result;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("../../controllers/common_controllers.php");
include('../controller/branch_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();
$response = array();
$response['message'] = "";
//echo $response;
// Check if a file was uploaded
 if (isset($_FILES["csvFile"]) && $_FILES["csvFile"]["size"] > 0) {
    $file = $_FILES["csvFile"]["tmp_name"];

    $CreatedDate = date("Y-m-d");
	$CreatedTime = date("H:i:s");
	$CreatedBy = $_SESSION['pb_username'];
    
    // Read the file
    $handle = fopen($file, "r");
    
    // Skip the header row
    $data = fgetcsv($handle, 1000);
    $i=0;
    // Process the remaining rows
    while (($data = fgetcsv($handle, 1000)) !== false) 
    {
        $i++;
        $CorporateAccountName = $data[0]; // Assuming the CSV has a column named 'column1'
        $where = " where CompanyName = '$CorporateAccountName'";
        $company_details = _getTableDetails($conn,'company',$where);
        if($company_details == null)
        {
            $response['error'] = true;
            $response['message'] = $response['message']."<br> Row:".$i." has invalid Corporate Account";
            continue;
        }
        $CompanyID = $company_details['ID'];
        $Branch_site = $data[1]; // Assuming the CSV has a column named 'column2'
        $Branch_site = remove_special_chars($Branch_site,$allowed_chars);
        $Branch_code = $data[2];
        $Branch_email = $data[3];
        $Branch_mobile = $data[4];
        $Branch_landline = $data[5];
        $Branch_address1 = cleantext($data[9]);
        $Branch_address1 = remove_special_chars($Branch_address1,$allowed_chars);
        $Branch_address2 = cleantext($data[10]);
        $Branch_address2 = remove_special_chars($Branch_address2,$allowed_chars);
        $Branch_city = $data[7];
        $Branch_state = $data[8];
        $Branch_postal_code = $data[6];
        $Site_incharge = $data[11];
        $User_Name = $data[12];
        $Password = $data[13];

        $not_duplicate = true;

        if($User_Name != "")
        {
            $filter = " where UserName = '$User_Name'";
            $not_duplicate = check_unique_identity_filter($conn,'users',$filter);
        }
        if($not_duplicate == false)
        {
            $response['message'] = $response['message']."<br> Row:".$i." has duplicate username";
            continue;
        }
        if($Branch_site != "")
        {
            $filter = " where  BranchSite = '$Branch_site' and CompanyID = $CompanyID";
            $not_duplicate = check_unique_identity_filter($conn,'branch',$filter);
        }
        if($not_duplicate == false)
        {
            $response['message'] = $response['message']."<br> Row:".$i." has duplicate branch (Branch Name)";
            continue;
        }
        if($not_duplicate == true)
        {
            // Insert the data into the database
            $branch_query = "INSERT INTO branch (CompanyID,BranchSite,BranchCode,BranchEmail,BranchMobile,BranchLandline,BranchAddress1,BranchAddress2,BranchCity,BranchState,BranchPostalCode,SiteIncharge,CreatedBy,CreatedDate,CreatedTime ) VALUES ('$CompanyID','$Branch_site', '$Branch_code', '$Branch_email', '$Branch_mobile','$Branch_landline', '$Branch_address1', '$Branch_address2', '$Branch_city', '$Branch_state', '$Branch_postal_code', '$Site_incharge', '$CreatedBy', '$CreatedDate', '$CreatedTime')";
 
                $response_insert = _InsertTableRecords($conn, $branch_query);
               //echo $response;  
                if($response_insert['error'] == false)
                {   
                    $response['message'] = $response['message']."<br> Row:".$i." Branch Added to the System";
                }
                else 
                {
                    $response['message'] = $response['message']."<br> Row:".$i." Branch Not Added to the System"; 
                }    
                if($User_Name != "") 
                {
                    $branch_password = md5($Password);
                    $user_type = "Corporate Branch User";
                    $emp_Id = "-1";
                    $BranchID = $response['last_insert_id'];

                    $add_branch_user = "INSERT INTO users (UserName,Password,UserType,EmployeeID,BranchID,CreatedDate,CreatedTime) VALUES('$User_Name','$branch_password','$user_type','$emp_Id',$BranchID,'$CreatedDate','$CreatedTime')";

                    $response_add_branch_user = _InsertTableRecords($conn, $add_branch_user);
                }
        }       
        else 
        {
            $response['message'] = $response['message']."<br> Row:".$i." Error inserting data into the database.";
        }
    }
    fclose($handle);         
} 
echo json_encode($response);
?>