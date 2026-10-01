<?php
@session_start();
include("../../controllers/common_controllers.php");
include("../controller/dynamic_ppm_controller.php");

$conn = _connectodb();
setTimeZone();

$data = $_POST;
if (!isset($data['CreatedBy']) || trim((string) $data['CreatedBy']) === '') {
    $data['CreatedBy'] = dynamicPPMGetSessionUser();
}

$response = bulkAddDynamicPPMChecklistItems($conn, $data);
$msg = isset($response['message']) ? $response['message'] : 'Checklist item save completed.';
$status = (isset($response['error']) && $response['error']) ? 'error' : 'success';

$redirect = '../view-master-checklist.php';
if (!empty($data['redirect_to'])) {
    $redirect = '../' . ltrim((string) $data['redirect_to'], '/');
}

header('Location: ' . $redirect . '?status=' . urlencode($status) . '&msg=' . urlencode($msg));
exit;
?>
