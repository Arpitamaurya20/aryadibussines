<?php
session_start();
include('../../controllers/common_controllers.php');

$conn = _connectodb();
$action = isset($_POST['action']) ? $_POST['action'] : '';

$response = ['status' => 'error', 'message' => 'Invalid action.'];

if ($action === 'add') {

    $name     = mysqli_real_escape_string($conn, trim($_POST['industry_type_name']));
    $is_active = intval($_POST['is_active']);

    if (empty($name)) {
        $response = ['status' => 'error', 'message' => 'Industry Type Name is required.'];
    } else {
        // Check duplicate
        $check = mysqli_query($conn, "SELECT ID FROM master_industry_type WHERE IndustryTypeName = '$name'");
        if (mysqli_num_rows($check) > 0) {
            $response = ['status' => 'error', 'message' => 'Industry Type already exists.'];
        } else {
            $sql = "INSERT INTO master_industry_type (IndustryTypeName, IsActive, CreatedDate) 
                    VALUES ('$name', $is_active, NOW())";
            if (mysqli_query($conn, $sql)) {
                $response = ['status' => 'success', 'message' => 'Industry Type added successfully.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to add. Please try again.'];
            }
        }
    }

} elseif ($action === 'edit') {

    $id       = intval($_POST['industry_type_id']);
    $name     = mysqli_real_escape_string($conn, trim($_POST['industry_type_name']));
    $is_active = intval($_POST['is_active']);

    if (empty($name)) {
        $response = ['status' => 'error', 'message' => 'Industry Type Name is required.'];
    } else {
        // Check duplicate excluding current record
        $check = mysqli_query($conn, "SELECT ID FROM master_industry_type WHERE IndustryTypeName = '$name' AND ID != $id");
        if (mysqli_num_rows($check) > 0) {
            $response = ['status' => 'error', 'message' => 'Industry Type name already exists.'];
        } else {
            $sql = "UPDATE master_industry_type 
                    SET IndustryTypeName = '$name', IsActive = $is_active, UpdatedAt = NOW() 
                    WHERE ID = $id";
            if (mysqli_query($conn, $sql)) {
                $response = ['status' => 'success', 'message' => 'Industry Type updated successfully.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to update. Please try again.'];
            }
        }
    }

} elseif ($action === 'delete') {

    $id = intval($_POST['industry_type_id']);

    $sql = "DELETE FROM master_industry_type WHERE ID = $id";
    if (mysqli_query($conn, $sql)) {
        $response = ['status' => 'success', 'message' => 'Industry Type deleted successfully.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to delete. Please try again.'];
    }
}

echo json_encode($response);
?>