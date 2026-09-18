<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/corporate_audit_controller.php');

SessionCheck();
$conn = _connectodb();
$id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$data = getCorporateAuditChecklistById($conn, $id);

if (!empty($data)) {
    if (!empty($data['OptionsJSON'])) {
        $decoded = json_decode($data['OptionsJSON'], true);
        $data['options_list'] = is_array($decoded) ? implode(', ', $decoded) : '';
    } else {
        $data['options_list'] = '';
    }
    echo json_encode(array('error' => false, 'data' => $data));
} else {
    echo json_encode(array('error' => true, 'message' => 'Record not found.'));
}
