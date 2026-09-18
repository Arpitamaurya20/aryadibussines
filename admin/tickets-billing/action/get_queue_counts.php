<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_billing_controller.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$filters = array(
    'CorporateID' => isset($_GET['CorporateID']) ? intval($_GET['CorporateID']) : -1,
    'BranchID' => isset($_GET['BranchID']) ? intval($_GET['BranchID']) : -1,
    'RegionID' => isset($_GET['RegionID']) ? intval($_GET['RegionID']) : -1,
    'StateID' => isset($_GET['StateID']) ? intval($_GET['StateID']) : -1,
    'StartDate' => isset($_GET['StartDate']) ? $_GET['StartDate'] : date('Y-m-01'),
    'EndDate' => isset($_GET['EndDate']) ? $_GET['EndDate'] : date('Y-m-t'),
    'TicketStatus' => isset($_GET['TicketStatus']) ? trim($_GET['TicketStatus']) : '',
    'TicketType' => isset($_GET['TicketType']) ? trim($_GET['TicketType']) : ''
);

echo json_encode(GetBillingQueueCounts($conn, $filters));
