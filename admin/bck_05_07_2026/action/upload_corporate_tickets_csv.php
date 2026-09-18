<?php
@session_start();
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
include('../controller/corporate_tickets_controller.php');
include('../../branch/controller/branch_controller.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
setTimeZone();
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
    $to_continue = false;
    $priority_array = array("Low","Medium","High");
    $categories_obj = new Categories($conn);
    $categories_array = $categories_obj->getAllCategoriesNameArray();
    $sub_categories_array = $categories_obj->getAllSubCategoriesNameArray();
    // Process the remaining rows
    while (($data = fgetcsv($handle, 1000)) !== false) 
    {
        $i++;
        $Sno = $data[0];
        $to_continue = false;

        $ClientTicketID = $data[1];
        if($ClientTicketID != "")
        {
            $corporateticket = new Corporateticket($conn);
            if($corporateticket->CheckforDuplicateClientTicketID($ClientTicketID))
            {
                $response['message'] = $response['message']."<br> Row:".$i." has duplicate ClientTicketID";
                $to_continue = true;
            }
        }
        if($to_continue)
        {
            continue;
        }

        $CorporateAccountName = $data[2]; // Assuming the CSV has a column named 'column1'
        $where = " where CompanyName = '$CorporateAccountName'";
        $company_details = _getTableDetails($conn,'company',$where);
        if($company_details == null)
        {
            $response['error'] = true;
            $response['message'] = $response['message']."<br> Row:".$i." has invalid Corporate Account";
            $to_continue = true;
        }
        if($to_continue)
        {
            continue;
        }
        $CompanyID = $company_details['ID'];

        $Branch_site = $data[3]; // Assuming the CSV has a column named 'column2'
        $Branch_site = remove_special_chars($Branch_site,$allowed_chars);
        $where = " where BranchSite = '$Branch_site'";
        $branch_details = _getTableDetails($conn,'branch',$where);
        if($branch_details == null)
        {
            $response['error'] = true;
            $response['message'] = $response['message']."<br> Row:".$i." has invalid Branch Name";
            $to_continue = true;
        }
        if($to_continue)
        {
            continue;
        }
        $BranchID = $branch_details['ID'];
        $BranchCity = $branch_details['BranchCity']; 
        $AssignedTo = -1;
        $AccountBranchManager = $branch_details['AccountBranchManager'];
        $AssignedTo = $AccountBranchManager;
        if($AssignedTo == -1 || $AssignedTo == "")
        {
            $where = " where CityName = '$BranchCity'";
            $result_city_lead = _getTableDetails($conn,'citydata',$where);
            $CorporateLead = $result_city_lead['CorporateLead'];
            $AssignedTo = $CorporateLead;
        }

        $Type = $data[4];
        if($Type !== "R&M")
        {
            $response['message'] = $response['message']."<br> Row:".$i." - Only R&M Type can be uploaded";
            $to_continue = true;
        }
        if($to_continue)
        {
            continue;
        }

        
        $Service = $data[5];
        $where = " where CategoriesName = '$Service'";
        $category_details = _getTableDetails($conn,'manage_categories',$where);
        if($category_details == null)
        {
            $response['message'] = $response['message']."<br> Row:".$i." - Service is not in master list";
            $to_continue = true;
        }
        if($to_continue)
        {
            continue;
        }

        $Subservice = $data[6];
        $CategoryID = $category_details['ID'];
        $where = " where SubCategoriesName = '$Subservice' and Categories = $CategoryID";
        $sub_category_details = _getTableDetails($conn,'manage_subcategories',$where);
        $Subservice_Others = "";
        if($sub_category_details == null)
        {
            $Subservice_Others = $Subservice;
            $Subservice = "Others";
        }

        $Priority = $data[7];
        if(!in_array($Priority,$priority_array))
        {
            $response['message'] = $response['message']."<br> Row:".$i." - Invalid Priority";
            $to_continue = true;
        }
        if($to_continue)
        {
            continue;
        }

        $Message = $data[8];
        $param1 = "";
        $param2 = "";
        $param3 = "";
        $param4 = "";
        $param5 = "";
        $BranchAssetID ="-1";
        $Status = "Raised"; 

        $Raise_ticket = "INSERT INTO corporate_tickets (CorporateID,BranchID,Type,param1,param2,param3,param4,param5,BranchAssetID,Service,Subservice,SubService_Others,ClientTicketID,Message,Description,CreatedDate,CreatedTime,CreatedBy,AssignedTo,Priority,Status,Remarks) VALUES ('$CompanyID','$BranchID','$Type','$param1','$param2','$param3','$param4','$param5',$BranchAssetID,'$Service','$Subservice','$Subservice_Others','$ClientTicketID','$Message','','$CreatedDate','$CreatedTime','$CreatedBy','$AssignedTo','$Priority','$Status','')";
     
        $raise_result = _InsertTableRecords($conn, $Raise_ticket);
        $LastID = $raise_result['last_insert_id'];
        $formatted_id = sprintf('%06d', $LastID);
        $TicketID = "CS-RM-".$formatted_id;
    
        $query_parameter = " TicketID = '$TicketID' where ID = $LastID";
        _UpdateTableRecords($conn,'corporate_tickets',$query_parameter);
        $response['message'] = $response['message']."<br> Row:".$i." - Uploaded";

        
    }
    fclose($handle);         
} 
echo json_encode($response);
?>