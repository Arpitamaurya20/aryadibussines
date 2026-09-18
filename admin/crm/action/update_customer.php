<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/crm_controller.php');
$conn = _connectodb();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ID = (int)$_POST['ID'];
    $AccountName = mysqli_real_escape_string($conn, $_POST['AccountName']);
    $ContactPerson = mysqli_real_escape_string($conn, $_POST['ContactPerson']);
    $Email = mysqli_real_escape_string($conn, $_POST['Email']);
    $Mobile = mysqli_real_escape_string($conn, $_POST['Mobile']);
    $GST = mysqli_real_escape_string($conn, $_POST['GST']);
    $CustomerType = mysqli_real_escape_string($conn, $_POST['CustomerType']);
    $UpdatedBy = $_SESSION['EmployeeID'] ?? 0;
    
    $cDate = date('Y-m-d');
    $cTime = date('H:i:s');
    
    $sql = "UPDATE crm_accounts SET 
            AccountName = '$AccountName',
            ContactPerson = '$ContactPerson',
            Email = '$Email',
            Mobile = '$Mobile',
            GST = '$GST',
            CustomerType = '$CustomerType',
            UpdatedBy = $UpdatedBy,
            UpdatedDate = '$cDate',
            UpdatedTime = '$cTime'
            WHERE ID = $ID";
            
    if(mysqli_query($conn, $sql)) {
        insertAuditLog($conn, 'Update', 'Customers', $ID, null, $_POST, $UpdatedBy);
        echo "<script>alert('Customer updated successfully'); window.location.href = '../view-customers.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
}
?>
