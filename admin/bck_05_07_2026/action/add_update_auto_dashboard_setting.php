<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
require_once('../../includes/autoloader.inc.php');
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
setTimeZone();

$core = new Core();
$dbh  = new Dbh();
$conn = $dbh->_connectodb();

/* ======================================================
   VALIDATE AJAX REQUEST
====================================================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ajax_action'])) {
    echo json_encode(['error' => true, 'success' => false, 'message' => 'Invalid request']);
    exit;
}

header('Content-Type: application/json');
$action = trim($_POST['ajax_action']);

/* ======================================================
   SAFE INPUTS (NO trim(null))
====================================================== */

$ID          = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
$CorporateID = isset($_POST['CorporateID']) ? intval($_POST['CorporateID']) : null;
$State       = isset($_POST['State']) ? trim($_POST['State']) : '';
$City        = isset($_POST['City']) ? trim($_POST['City']) : '';
$BranchID    = isset($_POST['BranchID']) ? intval($_POST['BranchID']) : null;

$StatusFilter = isset($_POST['StatusFilter'])
    ? (is_array($_POST['StatusFilter']) ? implode(',', $_POST['StatusFilter']) : trim($_POST['StatusFilter']))
    : '';

$TypeFilter = isset($_POST['TypeFilter'])
    ? (is_array($_POST['TypeFilter']) ? implode(',', $_POST['TypeFilter']) : trim($_POST['TypeFilter']))
    : '';

$DateRange = isset($_POST['DateRange']) ? trim($_POST['DateRange']) : '';

$EmailTo   = isset($_POST['EmailTo']) ? trim($_POST['EmailTo']) : '';
$EmailCC   = isset($_POST['EmailCC']) ? trim($_POST['EmailCC']) : '';
$IsActive  = isset($_POST['IsActive']) ? intval($_POST['IsActive']) : 1;

/* ======================================================
   DATE RANGE PARSE
====================================================== */
$DateFrom = null;
$DateTo   = null;

if ($DateRange !== '' && strpos($DateRange, ' - ') !== false) {
    list($DateFrom, $DateTo) = explode(' - ', $DateRange);
} elseif ($DateRange !== '') {
    $DateFrom = $DateTo = $DateRange;
}

/* ======================================================
   DATA TO INSERT/UPDATE
====================================================== */

$rowData = [
    'CorporateID'  => $CorporateID,
    'State'        => $State,
    'City'         => $City,
    'BranchID'     => $BranchID,
    'StatusFilter' => $StatusFilter,
    'TypeFilter'   => $TypeFilter,
    'DateFrom'     => $DateFrom,
    'DateTo'       => $DateTo,
    'EmailTo'      => $EmailTo,
    'EmailCC'      => $EmailCC,
    'IsActive'     => $IsActive
];

/* ======================================================
   INSERT (NEW RECORD)
====================================================== */
if ($action === 'save' && $ID == 0) {

    $response = $core->_InsertTableRecords_prepare(
        $conn,
        'ticket_status_setting',
        $rowData
    );

    $response['success'] = ($response['error'] === false);
    echo json_encode($response);
    exit;
}

/* ======================================================
   UPDATE (EXISTING RECORD)
====================================================== */
if ($action === 'save' && $ID > 0) {

    $response = $core->_UpdateTableRecords_prepare(
        $conn,
        'ticket_status_setting',
        $rowData,
        ['ID' => $ID]
    );

    $response['success'] = ($response['error'] === false);
    echo json_encode($response);
    exit;
}

/* ======================================================
   DELETE
====================================================== */
if ($action === 'delete') {

    if ($ID <= 0) {
        echo json_encode(['error' => true, 'success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $ok = $core->delete_identity_filter(
        $conn,
        'ticket_status_setting',
        "WHERE ID = $ID"
    );

    echo json_encode([
        'error'   => !$ok,
        'success' => $ok,
        'message' => $ok ? "Deleted" : "Delete failed"
    ]);
    exit;
}

/* ======================================================
   UNKNOWN ACTION
====================================================== */
echo json_encode(['error' => true, 'success' => false, 'message' => 'Unknown action']);
exit;

?>
