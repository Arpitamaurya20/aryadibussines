<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/crm_controller.php');
$conn = _connectodb();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_POST['CreatedBy'] = $_SESSION['EmployeeID'] ?? 0;
    
    $response = insertQuotation($conn, $_POST);
    
    if ($response['error']) {
        echo "<script>alert('Error: " . $response['message'] . "'); window.history.back();</script>";
    } else {
        echo "<script>alert('Quotation created successfully'); window.location.href = '../view-quotations.php';</script>";
    }
} else {
    header("Location: ../view-quotations.php");
    exit();
}
?>
