<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/crm_controller.php');
$conn = _connectodb();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'LeadName' => $_POST['LeadName'] ?? '',
        'CompanyName' => $_POST['CompanyName'] ?? '',
        'Email' => $_POST['Email'] ?? '',
        'Phone' => $_POST['Phone'] ?? '',
        'LeadSource' => $_POST['LeadSource'] ?? '',
        'LeadStatus' => $_POST['LeadStatus'] ?? 'New',
        'CreatedBy' => $_SESSION['EmployeeID'] ?? 0
    ];

    $response = insertLead($conn, $data);
    
    if ($response['error']) {
        echo "<script>alert('Error: " . $response['message'] . "'); window.history.back();</script>";
    } else {
        echo "<script>alert('Lead created successfully'); window.location.href = '../view-leads.php';</script>";
    }
} else {
    header("Location: ../view-leads.php");
    exit();
}
?>
