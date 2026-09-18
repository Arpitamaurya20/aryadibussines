<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/crm_controller.php');
$conn = _connectodb();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $LeadID = (int)$_POST['LeadID'];
    $Type = mysqli_real_escape_string($conn, $_POST['Type']);
    $FollowupDate = mysqli_real_escape_string($conn, $_POST['FollowupDate']);
    $FollowupTime = mysqli_real_escape_string($conn, $_POST['FollowupTime']);
    $Status = mysqli_real_escape_string($conn, $_POST['Status']);
    $Notes = mysqli_real_escape_string($conn, $_POST['Notes']);
    $CreatedBy = $_SESSION['EmployeeID'] ?? 0;
    
    $cDate = date('Y-m-d');
    $cTime = date('H:i:s');
    
    $sql = "INSERT INTO crm_followups (LeadID, Type, Status, FollowupDate, FollowupTime, Notes, CreatedBy, CreatedDate, CreatedTime, IsActive) 
            VALUES ($LeadID, '$Type', '$Status', '$FollowupDate', '$FollowupTime', '$Notes', $CreatedBy, '$cDate', '$cTime', 1)";
            
    if(mysqli_query($conn, $sql)) {
        insertAuditLog($conn, 'Insert', 'Followups', mysqli_insert_id($conn), null, $_POST, $CreatedBy);
        echo "<script>alert('Follow-up logged successfully'); window.location.href = '../view-lead-details.php?id=$LeadID';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
}
?>
