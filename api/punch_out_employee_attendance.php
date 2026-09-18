<?php
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
   $OutTime = date("H:i:s");
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
   $where = " where EmployeeID = $EmployeeID and RecordDate = '$current_date' and OutTime != ''";
   if(!check_unique_identity_filter($conn,'employee_attendance', $where))
   {
        $response['error'] = true;
        $response['message'] = "Attendance already punched out";
   }
   else
   {
        $checkoutCheck = getEmployeeCheckoutEligibility($conn, $EmployeeID);
        if (!$checkoutCheck['canCheckout']) {
            $response['error'] = true;
            $response['message'] = $checkoutCheck['message'];
            echo json_encode($response);
            exit;
        }

        $geofence = validateEmployeeAttendanceGeofenceWithBranches($conn, $EmployeeID, $Latitude, $Longitude, 'checkout', $gpsAccuracy);
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
            $var_name = "checkout_{$EmployeeID}_{$current_date}";
            // Generate a unique filename for the image
            $filename = "ea_".$var_name.uniqid().'.jpg';
            // Define the storage directory where the image will be saved
            $storageDirectory = '../admin/media/employee_attendance/';
            file_put_contents($storageDirectory . $filename, $imageData); 
        }
       $query_parameter = "OutTime = '$OutTime',CheckoutLatitude='$Latitude',CheckoutLongitude='$Longitude',CheckoutImage='$filename' where EmployeeID = $EmployeeID and RecordDate = '$current_date'";
       $result = _UpdateTableRecords($conn,'employee_attendance',$query_parameter);
       if($result['error'] == false)
       {
          $response['error'] = false;
          $response['message'] = "Attendance punched out";
          if (!empty($geofence['matchedLocation'])) {
              $response['data'] = ['matchedLocation' => $geofence['matchedLocation']];
          }
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