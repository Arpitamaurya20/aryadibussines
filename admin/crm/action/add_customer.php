<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/crm_controller.php');
$conn = _connectodb();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'AccountName' => $_POST['AccountName'] ?? '',
        'ContactPerson' => $_POST['ContactPerson'] ?? '',
        'Email' => $_POST['Email'] ?? '',
        'Mobile' => $_POST['Mobile'] ?? '',
        'Phone' => $_POST['Phone'] ?? '',
        'GST' => $_POST['GST'] ?? '',
        'PAN' => $_POST['PAN'] ?? '',
        'CustomerType' => $_POST['CustomerType'] ?? '',
        'Source' => $_POST['Source'] ?? '',
        'CreatedBy' => $_SESSION['EmployeeID'] ?? 0
    ];

    $response = insertCustomer($conn, $data);
    
    if ($response['error']) {
        echo "<script>alert('Error: " . $response['message'] . "'); window.history.back();</script>";
    } else {
        echo "<script>alert('Customer created successfully'); window.location.href = '../view-customers.php';</script>";
    }
} else {
    header("Location: ../view-customers.php");
    exit();
}
?>
