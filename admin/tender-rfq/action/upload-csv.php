<?php
session_start();
require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../controller/tender_rfq_controller.php');

$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
if (!isset($_Nav_Tender_RFQ) || !$_Nav_Tender_RFQ) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied.');
}
$conn = _connectodb();
$ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
$roles = tender_rfq_session_roles();

if ($ticketId < 1 || !tender_rfq_can_access($conn, $ticketId, $roles, $UserType)) {
    header('Location: ../view-tender-rfq-tickets?err=ticket');
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../view-tender-rfq-ticket-detail?id=' . $ticketId . '&err=upload');
    exit;
}

$tmp = $_FILES['csv_file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    header('Location: ../view-tender-rfq-ticket-detail?id=' . $ticketId . '&err=ext');
    exit;
}

$uniqueKeyHeader = isset($_POST['unique_key_header']) ? trim((string) $_POST['unique_key_header']) : '';
$parsed = TenderRfq::parseCsvFile($tmp, $uniqueKeyHeader);
if (!empty($parsed['error'])) {
    header('Location: ../view-tender-rfq-ticket-detail?id=' . $ticketId . '&err=' . rawurlencode($parsed['message']));
    exit;
}

$trfq = new TenderRfq($conn);
$res = $trfq->replaceCsvImport($ticketId, $parsed);
if (!empty($res['error'])) {
    header('Location: ../view-tender-rfq-ticket-detail?id=' . $ticketId . '&err=' . rawurlencode($res['message']));
    exit;
}

header('Location: ../view-tender-rfq-ticket-detail?id=' . $ticketId . '&ok=1');
exit;
