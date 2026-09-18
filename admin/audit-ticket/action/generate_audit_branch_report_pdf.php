<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php';
include '../../controllers/common_controllers.php';
include '../controller/audit_ticket_controller.php';

header('Content-Type: application/json');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Invalid request.');

$auditTicketId = 0;
if (isset($_POST['AuditTicketID'])) {
    $auditTicketId = (int) $_POST['AuditTicketID'];
}
if (isset($_GET['AuditTicketID'])) {
    $auditTicketId = (int) $_GET['AuditTicketID'];
}

if ($auditTicketId <= 0) {
    echo json_encode($response);
    exit;
}

if (!empty($_POST['ReportFloor']) || !empty($_POST['ClientRepresentative'])) {
    saveAuditTicketReportMeta($conn, $_POST);
}

$response = generateCorporateAuditBranchReportPdf($conn, $auditTicketId, array(
    'inline' => isset($_GET['AuditTicketID']),
));

if (!empty($response['error'])) {
    echo json_encode($response);
    exit;
}

if (isset($_GET['AuditTicketID'])) {
    $pdfPath = AUDIT_TICKET_REPORTS_DIR . $response['pdfname'];
    if (is_file($pdfPath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $response['pdfname'] . '"');
        readfile($pdfPath);
        exit;
    }
}

echo json_encode($response);
