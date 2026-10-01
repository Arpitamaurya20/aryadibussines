<?php
include("../../controllers/common_controllers.php");
include("../../dynamic-ppm/controller/dynamic_ppm_controller.php");
header('Content-Type: application/json');

$conn = _connectodb();
$categoryID = isset($_POST['CategoryID']) ? intval($_POST['CategoryID']) : 0;
$rows = getDynamicPPMChecklistsByCategory($conn, $categoryID);
$results = array();
foreach ($rows as $row) {
    $results[] = array(
        'id' => (int) $row['ID'],
        'text' => $row['ChecklistCode'] . ' - ' . $row['ChecklistName']
    );
}

echo json_encode(array(
    'error' => false,
    'results' => $results
));
?>
