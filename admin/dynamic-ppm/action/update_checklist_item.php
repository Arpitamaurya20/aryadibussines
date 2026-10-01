<?php
@session_start();
include("../../controllers/common_controllers.php");
include("../controller/dynamic_ppm_controller.php");

$conn = _connectodb();
setTimeZone();

$data = $_POST;
$data['UpdatedBy'] = dynamicPPMGetSessionUser();

$response = updateDynamicPPMChecklistItem($conn, $data);
$msg = isset($response['message']) ? $response['message'] : 'Checklist item update completed.';
$status = (isset($response['error']) && $response['error']) ? 'error' : 'success';

$checklistID = isset($data['ChecklistID']) ? (int) $data['ChecklistID'] : 0;
$redirect = '../view-checklist-details.php?id=' . $checklistID;
if (!empty($data['redirect_to'])) {
    $redirect = '../' . ltrim((string) $data['redirect_to'], '/');
}

header('Location: ' . $redirect . '?status=' . urlencode($status) . '&msg=' . urlencode($msg));
exit;
?>
