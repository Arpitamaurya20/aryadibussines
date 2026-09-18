<?php
include("../../controllers/common_controllers.php");
include('../controller/categories_controller.php');

$conn = _connectodb();
$response = [];

// Read inputs
$category_id     = $_POST['category_id'] ?? '';
$categories_name = trim($_POST['categories_name'] ?? '');
$type            = trim($_POST['category_type'] ?? '');

// Validation
if ($categories_name == '') {
    echo json_encode([
        'error' => true,
        'message' => 'Category Name is required'
    ]);
    exit;
}

if ($type == '') {
    echo json_encode([
        'error' => true,
        'message' => 'Category Type is required'
    ]);
    exit;
}

if ($category_id == '') {

    /* ======================
       ADD CATEGORY
    ======================= */

    $data = [
        'CategoriesName' => $categories_name,
        'Type'           => $type,
        'IsActive'       => '1'
    ];

    $result = _InsertTableRecords_prepare(
        $conn,
        'manage_categories',
        $data
    );

    echo json_encode([
        'error' => $result['error'],
        'message' => $result['error']
            ? $result['message']
            : 'Category Added Successfully'
    ]);

} else {

    /* ======================
       UPDATE CATEGORY
    ======================= */

    $data = [
        'CategoriesName' => $categories_name,
        'Type'           => $type
    ];

    $where = [
        'ID' => $category_id
    ];

    $result = _UpdateTableRecords_prepare(
        $conn,
        'manage_categories',
        $data,
        $where
    );

    echo json_encode([
        'error' => $result['error'],
        'message' => $result['error']
            ? $result['message']
            : 'Category Updated Successfully'
    ]);
}
