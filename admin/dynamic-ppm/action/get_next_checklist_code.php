<?php
@session_start();
include("../../controllers/common_controllers.php");
include("../controller/dynamic_ppm_controller.php");

header('Content-Type: application/json; charset=utf-8');

$conn = _connectodb();
$categoryID = isset($_POST['CategoryID']) ? (int) $_POST['CategoryID'] : 0;
if ($categoryID <= 0) {
    echo json_encode(array('error' => true, 'message' => 'Category is required.', 'ChecklistCode' => ''));
    exit;
}

$code = getNextDynamicPPMChecklistCode($conn, $categoryID);
if ($code === '') {
    echo json_encode(array('error' => true, 'message' => 'Unable to generate checklist code.', 'ChecklistCode' => ''));
    exit;
}

echo json_encode(array('error' => false, 'message' => 'Checklist code generated.', 'ChecklistCode' => $code));
exit;
?>
