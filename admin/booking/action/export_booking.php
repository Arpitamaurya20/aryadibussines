<?php
// require('../PHPExcel/Classes/PHPExcel.php');

// require('../library/php-excel-reader/excel_reader2.php');
include("../../controllers/common_controllers.php");
include('../controller/booking_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$booking_details = _getTableRecords($conn,'confirm_booking', $where);
if ($booking_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Booking ID</th>  
                         <th>Division</th>  
                         <th>Division Sequence</th>  
                         <th>Name</th>  
                         <th>Phone</th>  
                         <th>Email</th>  
                         <th>Service Name</th>  
                         <th>SubService</th>  
                         <th>Customer Address</th>  
                         <th>City</th>  
                         <th>State</th>  
                         <th>Location Landmark</th>  
                         <th>Postal Code</th>  
                         <th>Subject</th>  
                         <th>Booking Date</th>  
                         <th>Booking Time</th>  
                         <th>Status</th>  
                         <th>AssignedTo</th>  
                         <th>CreatedBy</th>  
                         <th>UpdatedBy</th>  
                         <th>UpdatedDate</th> 
                         <th>UpdatedTime</th> 
                    </tr>
  ';
    foreach ($booking_details as $Bookingdata) {
        $AssignID = $Bookingdata["AssignedTo"];
        $where = " where ID = '$AssignID'";
        $employee_get_assign_data = _getTableDetails($conn,'employees', $where);
        $AssignEmployeeName = $employee_get_assign_data['Name'];        
        $output .= '<tr>  
       <td>' . $Bookingdata["BookingID"] . '</td>  
       <td>' . $Bookingdata["Division"] . '</td>  
       <td>' . $Bookingdata["DivisionSequence"] . '</td>  
       <td>' . $Bookingdata["Name"] . '</td>  
       <td>' . $Bookingdata["Phone"] . '</td>
       <td>' . $Bookingdata["Email"] . '</td>
       <td>' . $Bookingdata["Service_name"] . '</td>
       <td>' . $Bookingdata["SubService"] . '</td>
       <td>' . $Bookingdata["Customer_address"] . '</td>
       <td>' . $Bookingdata["City_name"] . '</td>
       <td>' . $Branchdata["State_name"] . '</td>
       <td>' . $Bookingdata["Location_landmark"] . '</td>
       <td>' . $Bookingdata["PostalCode"] . '</td>
       <td>' . $Bookingdata["Subject"] . '</td>
       <td>' . $Bookingdata["BookingDate"] . '</td>
       <td>' . $Bookingdata["BookingTime"] . '</td>
       <td>' . $Bookingdata["Status"] . '</td>
       <td>' . $AssignEmployeeName . '</td>
       <td>' . $Bookingdata["CreatedBy"] . '</td>
       <td>' . $Bookingdata["UpdatedBy"] . '</td>
       <td>' . $Bookingdata["UpdatedDate"] . '</td>
       <td>' . $Bookingdata["UpdatedTime"] . '</td>
                    </tr>
   ';
    }
} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}

echo $output;
$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>