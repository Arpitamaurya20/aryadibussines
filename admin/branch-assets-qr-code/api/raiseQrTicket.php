<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../../controllers/common_controllers.php');
include("../../corporate-tickets/controller/corporate_tickets_controller.php");
require_once("../../branch/controller/branch_controller.php");
require_once('../../includes/autoloader.inc.php');

setTimeZone();
$conn = _connectodb();
$response = [];

$BranchID      = $_POST['BranchID']      ?? '';
$BranchAssetID = $_POST['BranchAssetID'] ?? '';
$Message       = $_POST['Message']       ?? '';
$CreatedBy     = $_POST['CreatedBy']     ?? 'system';
$Type          = $_POST['Type']          ?? 'AMC';
$Name          = $_POST['Name']          ?? '';
$PhoneNumber   = $_POST['PhoneNumber']   ?? '';

if ($BranchID && $BranchAssetID && $Message && $Name && $PhoneNumber) {

    // 1. Prepare data for corporate ticket
    $data = [
        "BranchID"      => $BranchID,
        "BranchAssetID" => $BranchAssetID,
        "Message"       => $Message,
        "CreatedBy"     => $CreatedBy,
        "Type"          => $Type
    ];

    // 2. Create corporate ticket
    $branch_details = GetBranchDetailsbyID($conn, $BranchID);
    $Raise_ticket = CreateCorporateTicket($conn, $data, $branch_details);

       // Get City Branches
   $where = " where ID = $BranchID";
   $result_branch_details = _getTableDetails($conn,'branch',$where);
   // $CityName = $result_branch_details['BranchCity'];
   
   $Corporate_name = $_POST["CompanyID"];
    // Get Company Details
    $company_object = new Company($conn);
    $company_details = $company_object->GetCompanyDetailsbyID($Corporate_name);

   $BranchSite = $result_branch_details['BranchSite'];  
   $BranchCity = $result_branch_details['BranchCity']; 
   $AccountBranchManager = $result_branch_details['AccountBranchManager'];
   $AccountBranchEmail=$result_branch_details['BranchEmail'];

   //Get City Corporate Lead
    $where = " where CityName = '$BranchCity'";
    $result_city_lead = _getTableDetails($conn,'citydata',$where);
    $CorporateLead = $result_city_lead['CorporateLead'];
    $AssignedTo = -1;
    $Employee_PhoneNumber = "";
    if($CorporateLead != -1 && $CorporateLead != "")
    {
        $AssignedTo = $CorporateLead;
        $where_emp = " where ID = $AssignedTo";
        $result_emp = _getTableDetails($conn,'employees',$where_emp);
        $Employee_PhoneNumber = $result_emp['ContactNumber'];
        $EmployeeName = $result_emp['Name'];
        $CityLeadEmail=$result_emp['Email'];
    }

    $AccountBranchManagerContact = "";
    if($AccountBranchManager != -1 && $AccountBranchManager != "")
    {
        $AssignedTo = $AccountBranchManager;
        $where_emp = " where ID = $AccountBranchManager";
        $result_emp = _getTableDetails($conn,'employees',$where_emp);
        $AccountBranchManagerContact = $result_emp['ContactNumber'];
        $AccountBranchManagerName = $result_emp['Name'];
    }

    if (!empty($Raise_ticket['last_insert_id'])) {

        $TicketID = $Raise_ticket['last_insert_id'];

         if($Type == "AMC")
        {
         $TicketID = "CS-AMC-".$formatted_id;
        }

        $emailPayload = [
            "action"        => "Corporate Ticket Raised",
            "TicketID"      => $TicketID,
            "CorporateName" => $company_details['CompanyName'],
            "BranchSite"    => $BranchSite,
            "BranchCity"    => $BranchCity,
            "ServiceType"   => $Service_type,
            "ServiceName"   => $Service_name,
            "SubService"    => ($Sub_Service_name == "Others") ? $Sub_Service_others : $Sub_Service_name,
            "Priority"      => $Priority,
            "Message"       => $Message,
            "Status"        => $Status,
            "RaisedBy"      => $CreatedBy,
            "CreatedDate"   => $CreatedDate,
            "CreatedTime"   => $CreatedTime,
            "ToEmail"       => $AccountBranchEmail,
            "CCEmail"       => 'rohittechxpert@gmail.com',
            "QuationID"     =>$quationID
        ];

        // 3. Insert into qr_corporate_tickets_client_info
        $TicketDate = date('Y-m-d');
        $sql = "INSERT INTO qr_corporate_tickets_client_info (TicketID, Name, PhoneNumber, CreatedDate) 
                VALUES ($TicketID, '".mysqli_real_escape_string($conn,$Name)."', '".mysqli_real_escape_string($conn,$PhoneNumber)."', '$TicketDate')";
        $insert_client = _InsertTableRecords($conn, $sql);

        $response['error'] = false;
        $response['message'] = "Ticket with number $TicketID has been raised successfully!";
        $response['last_insert_id'] = $Raise_ticket['last_insert_id']; // corporate ticket id
        $response['client_insert_id'] = $insert_client['last_insert_id']; // new table id
    } else {
        $response['error'] = true;
        $response['message'] = "Failed to raise corporate ticket!";
    }

    echo json_encode($response);

        // ===== BACKGROUND MODE =====
    ignore_user_abort(true);
    set_time_limit(0);

    // ===== BACKGROUND TASKS =====
    sendMailRequestRaisedTicket($emailPayload);
    exit;
} else {
    $response['status']  = "error";
    $response['message'] = "Missing required fields!";
    echo json_encode($response);
    exit;
}
