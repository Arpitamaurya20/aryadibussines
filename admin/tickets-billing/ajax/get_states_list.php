<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

// Always return all active states for searchable dropdown.
// Region filter is applied on ticket query, not on this list.
$where = ' WHERE IsActive = 1 ORDER BY StateName ASC';

$states = _getTableRecords($conn, 'state', $where);
if (!is_array($states)) {
    $states = array();
}

echo json_encode(array('error' => false, 'data' => $states));
?>
