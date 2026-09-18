<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // hide errors from browser
ini_set('log_errors', 1);      // but log them

require_once('../../controllers/common_controllers.php');
include("../../includes/autoloader.inc.php");

$conn = _connectodb();
$core = new Core();
$core->setTimeZone();

$response = ['success' => false];

if (!empty($_GET['employee'])) {
    $id = intval($_GET['employee']);

    $sql = "SELECT Basic, DA, HRA, Bonus, HealthInsurance, Others,ConvenienceAllowance
            FROM employees 
            WHERE ID = $id 
            LIMIT 1";

    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);

        // ✅ Safely calculate total salary (cast everything to float)
        $fields = ['Basic', 'DA', 'HRA', 'Bonus', 'HealthInsurance', 'Others' ,'ConvenienceAllowance'];
        $total  = 0;
        foreach ($fields as $f) {
            $total += floatval($row[$f] ?? 0);
        }

        $row['TotalSalary'] = $total;

        $response = [
            'success' => true,
            'data'    => $row
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
