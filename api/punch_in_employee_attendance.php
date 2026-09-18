<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/employees/controller/employee_controller.php');
setTimeZone();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['EmployeeID']))
{
   
   $conn = _connectodb();
   $current_date = date("Y-m-d");
   $InTime = date("H:i:s");
   $EmployeeID = $data['EmployeeID'];
   $Latitude = "";
   $Longitude = "";
   if(isset($data['Latitude']))
   {
       $Latitude = $data['Latitude'];
   }
   if(isset($data['Longitude']))
   {
       $Longitude = $data['Longitude'];
   }
   $gpsAccuracy = parseGpsAccuracyMeters($data['GpsAccuracy'] ?? $data['gpsAccuracy'] ?? $data['Accuracy'] ?? $data['accuracy'] ?? 0);
   $where = " where EmployeeID = $EmployeeID and RecordDate = '$current_date'";
   if(!check_unique_identity_filter($conn,'employee_attendance', $where))
   {
        $response['error'] = true;
        $response['message'] = "Attendance already punched in";
   }
   else
   {
        $geofence = validateEmployeeAttendanceGeofenceWithBranches($conn, $EmployeeID, $Latitude, $Longitude, 'checkin', $gpsAccuracy);
        if (!$geofence['allowed']) {
            $response['error'] = true;
            $response['message'] = $geofence['message'];
            echo json_encode($response);
            exit;
        }

        $filename = "";
        if(isset($data['imageData']))
        {
            $imageData = $data['imageData'];
            $imageData = base64_decode($imageData);
            $var_name = "checkin_{$EmployeeID}_{$current_date}";
            // Generate a unique filename for the image
            $filename = "ea_".$var_name.uniqid().'.jpg';
            // Define the storage directory where the image will be saved
            $storageDirectory = '../admin/media/employee_attendance/';
            file_put_contents($storageDirectory . $filename, $imageData); 
        }

       $insert_attendance = "INSERT INTO employee_attendance(EmployeeID,RecordDate,InTime,Latitude,Longitude,CheckinImage,ApprovalStatus) VALUES ($EmployeeID,'$current_date','$InTime','$Latitude','$Longitude','$filename','Pending')";
       $result = _InsertTableRecords($conn,$insert_attendance);
       if($result['error'] == false)
       {
          $response['error'] = false;
          $response['message'] = "Attendance punched in";
       }
   }
}
else
{
    $response['error'] = true;
    $response['message'] = "Missing User Fields!";
}
echo json_encode($response);
?>