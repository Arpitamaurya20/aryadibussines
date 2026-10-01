<?php
include("../../controllers/common_controllers.php");
header('Content-Type: application/json');

$conn = _connectodb();
$results = array();
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q = mysqli_real_escape_string($conn, $q);

$where = " WHERE IsActive = 1";
if ($q !== '') {
    $where .= " AND (BranchSite LIKE '%$q%' OR BranchCode LIKE '%$q%')";
}

$sql = "SELECT ID, BranchSite, BranchCode FROM branch $where ORDER BY BranchSite ASC LIMIT 50";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $label = $row['BranchSite'];
        if (!empty($row['BranchCode'])) {
            $label .= " (" . $row['BranchCode'] . ")";
        }
        $results[] = array(
            "id" => $row['ID'],
            "text" => $label
        );
    }
}

echo json_encode(array("results" => $results));
?>
