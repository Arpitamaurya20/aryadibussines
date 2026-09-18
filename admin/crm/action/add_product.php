<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/crm_controller.php');
$conn = _connectodb();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'ProductName' => $_POST['ProductName'] ?? '',
        'Brand' => $_POST['Brand'] ?? '',
        'SKU' => $_POST['SKU'] ?? '',
        'HSN_SAC' => $_POST['HSN_SAC'] ?? '',
        'UnitPrice' => $_POST['UnitPrice'] ?? 0,
        'GST_Percent' => $_POST['GST_Percent'] ?? 0,
        'Unit' => $_POST['Unit'] ?? 'Nos',
        'CreatedBy' => $_SESSION['EmployeeID'] ?? 0
    ];

    $response = insertProduct($conn, $data);
    
    if ($response['error']) {
        echo "<script>alert('Error: " . $response['message'] . "'); window.history.back();</script>";
    } else {
        echo "<script>alert('Product created successfully'); window.location.href = '../view-products.php';</script>";
    }
} else {
    header("Location: ../view-products.php");
    exit();
}
?>
