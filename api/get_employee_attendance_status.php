<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/employees/controller/employee_controller.php');
setTimeZone();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (!is_array($data)) {
   $data = array();
}
if (empty($data['EmployeeID']) && !empty($_POST['EmployeeID'])) {
   $data['EmployeeID'] = $_POST['EmployeeID'];
}
if (empty($data['EmployeeID']) && !empty($_GET['EmployeeID'])) {
   $data['EmployeeID'] = $_GET['EmployeeID'];
}
$response = array();
if(isset($data['EmployeeID']) && $data['EmployeeID'] !== '')
{
   $InTimeStatus = 0;
   $OutTimeStatus = 0;
   $conn = _connectodb();
   $current_date = date("Y-m-d");
   $EmployeeID = $data['EmployeeID'];
   // Get Employee Name
   $where = " where ID = $EmployeeID";
   $response_employee_Details = _getTableDetails($conn,'employees', $where);
   $response['EmployeeName'] = $response_employee_Details['Name'];
   $response['EmployeeDesignation'] = $response_employee_Details['Designation'];
   $where = " where RecordDate = '$current_date' and EmployeeID = $EmployeeID";
   $response['data']['attendace_records'] = "";
   if(!check_unique_identity_filter($conn,'employee_attendance', $where))
   {
      $response_attendance = _getTableDetails($conn,'employee_attendance', $where);
      if($response_attendance['InTime'] != "")
      {
         $InTimeStatus = 1;
      }
      if($response_attendance['OutTime'] != "")
      {
         $OutTimeStatus = 1;
      }
      $response['data']['attendace_records'] = $response_attendance;
   }
   $response['InTimeStatus'] = $InTimeStatus;
   $response['OutTimeStatus'] = $OutTimeStatus;
   $locationPolicy = getEmployeeAttendanceLocationPolicyWithBranchesApiData($conn, $EmployeeID);
   $response['data']['checkout_eligibility'] = getEmployeeCheckoutEligibility($conn, $EmployeeID);
   $response['data']['location_policy'] = $locationPolicy !== null ? $locationPolicy : [
       'EmployeeID' => (int) $EmployeeID,
       'IsAllowLocationBoundary' => 0,
       'AttendanceLatitude' => null,
       'AttendanceLongitude' => null,
       'AttendanceRadiusMeters' => 100,
       'boundaryEnabled' => false,
       'allowBranchLocations' => true,
       'checkinLocationRule' => 'employee_and_branches',
       'checkoutLocationRule' => 'employee_and_branches',
       'branchLocations' => [],
       'locationSource' => 'employee_and_branches',
   ];
   $response['error'] = false;
   $response['message'] = "Records fetched";
}
else
{
    $response['error'] = true;
    $response['message'] = "Missing User Fields!";
}
echo json_encode($response);
?>