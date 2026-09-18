<?php
@session_start();
include('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
header('Content-Type: application/json');
if (isset($_GET['role']) && !empty($_GET['role'])) {
    $role = mysqli_real_escape_string($conn, $_GET['role']);
   $sql = "
    SELECT DISTINCT e.ID, e.Name, e.EmployeeNumber, ur.Role
    FROM user_roles ur
    INNER JOIN employees e ON ur.EmployeeID = e.ID
    WHERE ur.Role = '$role' AND ur.IsActive = 1 AND e.IsActive = 1
";

    $employees = $core->_getSQLRecords($conn, $sql);

    $resultData = [];
    if (!empty($employees)) {
        foreach ($employees as $row) {
            $resultData[] = [
                "id" => $row['ID'],
                "name" => $row['Name'],
                "employee_number" => $row['EmployeeNumber']
            ];
        }
    }

    echo json_encode(["success" => true, "employees" => $resultData]);
} else {
    echo json_encode(["success" => false, "employees" => []]);
}
?>
