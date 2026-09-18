<?php
include("../../controllers/common_controllers.php");
include("../controller/audit_ticket_controller.php");

header('Content-Type: application/json');

$conn = _connectodb();
$masterAuditId = isset($_POST['master_audit_id']) ? (int) $_POST['master_audit_id'] : 0;

$options = '<option value="">Please Select Sub Audit</option>';
if ($masterAuditId > 0) {
    $subAudits = getAllCorporateMasterSubAudits($conn, $masterAuditId, true);
    foreach ($subAudits as $sub) {
        $options .= '<option value="' . (int) $sub['ID'] . '">' . htmlspecialchars($sub['SubAuditName']) . '</option>';
    }
}

echo json_encode(array('error' => false, 'html' => $options));
