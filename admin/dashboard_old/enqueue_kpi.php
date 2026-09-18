<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once('../controllers/common_controllers.php');
$conn = _connectodb();

// 🔹 Date range for current month
$dateFrom = date('Y-m-01');
$dateTo   = date('Y-m-d');

// 🔹 Fetch active employees (exclude Vendors)
$sql = "SELECT ID FROM employees
        WHERE IsActive=1
        AND ID NOT IN (
            SELECT EmployeeID FROM user_roles WHERE Role='Vendor' AND IsActive=1
        )";

$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    die("No active employees found.\n");
}

while ($emp = $result->fetch_assoc()) {
    $empID = $emp['ID'];

    // Avoid duplicate enqueue today
    $check = $conn->query("SELECT id FROM kpi_queue WHERE employee_id=$empID AND DATE(created_at)=CURDATE()");
    if ($check->num_rows === 0) {
        $conn->query("INSERT INTO kpi_queue (employee_id) VALUES ($empID)");
    }
}

echo "✅ Employees enqueued successfully.\n";
?>
