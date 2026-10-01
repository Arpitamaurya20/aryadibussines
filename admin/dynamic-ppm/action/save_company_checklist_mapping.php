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

$response = mapDynamicPPMCompanyChecklist($conn, $data);
$msg = isset($response['message']) ? $response['message'] : 'Mapping save completed.';
$status = (isset($response['error']) && $response['error']) ? 'error' : 'success';

header("Location: ../view-master-checklist.php?status=" . urlencode($status) . "&msg=" . urlencode($msg));
exit;
?>
