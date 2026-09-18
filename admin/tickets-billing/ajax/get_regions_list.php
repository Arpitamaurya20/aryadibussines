<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$regions = _getTableRecords($conn, 'region', ' WHERE IsActive = 1 ORDER BY RegionName ASC');
if (!is_array($regions)) {
    $regions = array();
}

echo json_encode(array('error' => false, 'data' => $regions));
?>
