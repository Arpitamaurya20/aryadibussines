<?php
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');

@session_start();
$conn = _connectodb();

$draw = intval($_POST['draw'] ?? 1);

$sql = "SELECT 
            p.ID,
            s.SparePart,
            s.Price,
            p.CreatedDate,
            c.CategoriesName,
            u.UOMName
        FROM post_spare_part p
        LEFT JOIN sparepartlist s ON p.SparePart = s.ID
        LEFT JOIN manage_categories c ON s.Categories = c.ID
        LEFT JOIN manage_uom u ON s.UOM = u.ID
        ORDER BY p.ID DESC";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$response = [
    "draw" => $draw,
    "recordsTotal" => count($data),
    "recordsFiltered" => count($data),
    "data" => $data
];

echo json_encode($response);
?>
