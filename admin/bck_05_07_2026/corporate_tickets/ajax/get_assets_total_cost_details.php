<?php
header('Content-Type: application/json');
require_once('../../includes/autoloader.inc.php');

$AssetID = isset($_POST['BranchAssetsID']) ? intval($_POST['BranchAssetsID']) : 0;

$dbh = new Dbh();
$conn = $dbh->_connectodb();

// Total Asset Cost
$sql_asset = "SELECT Amount FROM branch_assets WHERE ID = $AssetID LIMIT 1";
$res_asset = $conn->query($sql_asset);
$total_asset = ($res_asset && $res_asset->num_rows > 0) ? $res_asset->fetch_assoc()['Amount'] : 0;

// Total Spending
$sql_spending = "SELECT SUM(FinalTotalAmount) AS TotalSpending
                 FROM sparepart_final_items fi
                 INNER JOIN sparepart_cart c ON fi.CartID = c.CartID
                 WHERE c.AssetsID = $AssetID AND fi.IsActive = 1";
$res_spending = $conn->query($sql_spending);
$total_spending = ($res_spending && $res_spending->num_rows > 0) ? $res_spending->fetch_assoc()['TotalSpending'] : 0;

echo json_encode([
    'total_asset' => floatval($total_asset),
    'total_spending' => floatval($total_spending)
]);
