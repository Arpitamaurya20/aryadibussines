<?php
require_once('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');

$dbh  = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();

$BranchAssetsID = $_GET['AssetID'] ?? 0;

if (!$BranchAssetsID) {
    echo json_encode([
        "error" => true,
        "message" => "AssetID missing"
    ]);
    exit;
}

/*----------------------------------------------------------
  1️⃣ FIND BRANCH ID USING BranchAssetsID
----------------------------------------------------------*/
$sql_branch = "SELECT BranchID FROM branch_assets WHERE ID = ? AND IsActive = 1";
$stmt_branch = $conn->prepare($sql_branch);
$stmt_branch->bind_param("i", $BranchAssetsID);
$stmt_branch->execute();
$stmt_branch->bind_result($BranchID);
$stmt_branch->fetch();
$stmt_branch->close();

if (!$BranchID) {
    echo json_encode([
        "total_asset" => 0,
        "total_spending" => 0
    ]);
    exit;
}

/*----------------------------------------------------------
  2️⃣ GET TOTAL ASSET COST OF THIS BRANCH
----------------------------------------------------------*/
$sql1 = "SELECT SUM(Amount) AS total_asset 
         FROM branch_assets 
         WHERE BranchID = ? AND IsActive = 1";

$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $BranchID);
$stmt1->execute();
$stmt1->bind_result($total_asset);
$stmt1->fetch();
$stmt1->close();

$total_asset = $total_asset ? $total_asset : 0;

/*----------------------------------------------------------
  3️⃣ GET TOTAL SPENDING OF THIS BRANCH
----------------------------------------------------------*/
$sql2 = "SELECT SUM(f.FinalTotalAmount) AS total_spending
         FROM sparepart_final_items f
         JOIN sparepart_cart c ON f.CartID = c.CartID
         JOIN branch_assets a ON c.AssetsID = a.ID
         WHERE a.BranchID = ? AND f.IsActive = 1";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $BranchID);
$stmt2->execute();
$stmt2->bind_result($total_spending);
$stmt2->fetch();
$stmt2->close();

$total_spending = $total_spending ? $total_spending : 0;

/*----------------------------------------------------------
  4️⃣ FINAL RESPONSE
----------------------------------------------------------*/
echo json_encode([
    "total_asset"    => $total_asset,
    "total_spending" => $total_spending
]);

?>
