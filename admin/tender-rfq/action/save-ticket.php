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

$corporateId = isset($_POST['corporate_id']) ? (int) $_POST['corporate_id'] : 0;
if ($UserType === 'Corporate Admin' || $UserType === 'Corporate Branch User') {
    $must = (int) ($_SESSION['Roles']['CorporateID'] ?? 0);
    if ($must && $corporateId !== $must) {
        header('Location: ../add-tender-rfq-ticket?err=corp');
        exit;
    }
}

$data = $_POST;
$data['corporate_id'] = $corporateId ?: null;
$data['branch_id'] = isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : null;

$trfq = new TenderRfq($conn);
$res = $trfq->createTicket($data, $_SESSION['pb_username'] ?? '');
if (!empty($res['error'])) {
    header('Location: ../add-tender-rfq-ticket?err=save');
    exit;
}
header('Location: ../view-tender-rfq-ticket-detail?id=' . (int) $res['id']);
exit;
