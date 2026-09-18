<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
require_once("../admin/booking/controller/booking_controller.php");
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
$conn = _connectodb();
if(isset($data['bookingName']))
{
   $create_booking_response = CreateBooking($conn,$data,"Home Care Services");

   if($create_booking_response['error'] == false){
     $BookingID = $create_booking_response['BookingID'];
     $CreatedDate = date('Y-m-d');
     $CreatedTime = date('H:i:s');
     
     foreach ($data['laundry_booking'] as $item) {

        $itemtype = $item['Typeofclothes'];
        $itemquantity = $item['Quantity'];
        $itemprice = $item['Price'];
        $itemsubService = $item['SubServiceID'];


      if ($itemtype != '' && $itemquantity != '') {
        
        $ticket_finance_sql = "INSERT INTO laundry_booking(BookingID,SubServiceID,TypeofClothesID,Quantity,Price,CreatedDate,CreatedTime) VALUES ('$BookingID','$itemsubService','$itemtype','$itemquantity','$itemprice','$CreatedDate','$CreatedTime')";
        $response = _InsertTableRecords($conn,$ticket_finance_sql);
        $response['BookingID'] = $BookingID;
        $response['error'] = false;
        $response['message'] = "Booking is Confirmed. Thank you for contacting Us";
      }
    }
   }
}
else
{
    $response['error'] = true;
    $response['emessage'] = "Missing User Fields!";
}
echo json_encode($response);



?>