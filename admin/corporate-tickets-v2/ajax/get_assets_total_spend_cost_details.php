<?php
require_once('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
$dbh  = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$BranchAssetsID = $_POST['BranchAssetsID'] ?? 0;
$where = "WHERE ID = $BranchAssetsID";
$data  = $core->_getTableDetails($conn, 'branch_assets', $where);
echo json_encode($data);
?>
