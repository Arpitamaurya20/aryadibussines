<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/attendance_controller.php');
$response = array();
$employee_id = $_POST['employee_id'] ?? '';
$record_date = $_POST['record_date'] ?? '';
$in_time = $_POST['in_time'] ?? '';
$out_time = $_POST['out_time'] ?? '';
if(!$employee_id || !$record_date || !$in_time){
    echo json_encode(['status'=>'error','message'=>'Required fields missing']);
    exit;
}

$conn = _connectodb();

// Check if attendance exists
$attendance = checkmanualAttendance($conn, $employee_id, $record_date);

if($attendance){
    // Update existing record
    $update_response = updatemanualAttendance($conn, $attendance['ID'], $in_time, $out_time);
    if($update_response['error']){
        echo json_encode(['status'=>'error','message'=>$update_response['message']]);
        exit;
    }
} else {
    // Insert new record
    $insert_response = insertmanualAttendance($conn, $employee_id, $record_date, $in_time, $out_time);
    if($insert_response['error']){
        echo json_encode(['status'=>'error','message'=>$insert_response['message']]);
        exit;
    }
}

echo json_encode(['status'=>'success','message'=>'Attendance saved successfully']);
?>
