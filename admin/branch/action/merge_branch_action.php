<?php
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$CurrentBranchID = $_POST['CurrentBranchID'];
$NewBranchID = $_POST['NewBranchID'];
$branch = new Branch($conn);
$response = $branch->MergeBranches($CurrentBranchID,$NewBranchID);
echo json_encode($response);
?>