<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$CompanyID = isset($_GET['CompanyID']) ? intval($_GET['CompanyID']) : -1;
$RegionID = isset($_GET['RegionID']) ? intval($_GET['RegionID']) : -1;
$StateID = isset($_GET['StateID']) ? intval($_GET['StateID']) : -1;

$where = ' WHERE b.IsActive = 1';
if ($CompanyID > 0) {
    $where .= " AND b.CompanyID = $CompanyID";
}
if ($StateID > 0) {
    $where .= " AND b.BranchState IN (SELECT StateName FROM state WHERE ID = $StateID AND IsActive = 1)";
}
if ($RegionID > 0) {
    $where .= " AND b.BranchState IN (SELECT StateName FROM state WHERE RegionID = $RegionID AND IsActive = 1)";
}

$sql = "SELECT b.ID, b.BranchSite, b.BranchCode, b.BranchState
        FROM branch b
        $where
        ORDER BY b.BranchSite ASC";
$result = mysqli_query($conn, $sql);
$branches = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $branches[] = $row;
    }
}

echo json_encode(array('error' => false, 'data' => $branches));
?>
