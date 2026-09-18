<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once('../controllers/common_controllers.php');
$conn = _connectodb();

$batchSize = 2; // employees per run

$sql = "SELECT * 
        FROM kpi_queue 
        WHERE status='pending' 
          AND DATE(created_at) = CURDATE() 
        ORDER BY created_at ASC 
        LIMIT $batchSize";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) exit("No pending messages.\n");

while ($row = $result->fetch_assoc()) {
    $queueID = $row['id'];
    $empID = $row['employee_id'];

    // 🔹 Fetch employee details
    $emp = $conn->query("SELECT Name, Designation, ContactNumber FROM employees WHERE ID=$empID")->fetch_assoc();
    if (!$emp) continue;

    $role = $emp['Designation'] ?: "Employee";
    $empNo = $emp['ContactNumber'];

    // 🔹 Trigger existing KPI script via cURL
    $dateFrom = date('Y-m-01');
    $dateTo   = date('Y-m-d', strtotime('-1 day'));
    $apiUrl = "https://techxpertindia.in/admin/dashboard/employee_kpi_whatsapp.php"
        . "?Role=" . urlencode($role)
        . "&Employee=" . urlencode($empID)
        . "&EmployeeNumber=" . urlencode($empNo)
        . "&Datefrom=" . urlencode($dateFrom)
        . "&Dateto=" . urlencode($dateTo);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

   // 🔹 Update queue safely using prepared statements
    if ($error) {
        $status = 'failed';
        $resp   = json_encode(['error' => $error], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        $status = 'sent';
        $resp   = json_encode(['response' => $response], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    if ($status === 'sent') {
        $stmt = $conn->prepare("UPDATE kpi_queue SET status=?, sent_at=NOW(), response=? WHERE id=?");
    } else {
        $stmt = $conn->prepare("UPDATE kpi_queue SET status=?, response=? WHERE id=?");
    }

    $stmt->bind_param("ssi", $status, $resp, $queueID);
    $stmt->execute();
    $stmt->close();

    echo ($status === 'sent')
        ? "✅ EmployeeID $empID sent.\n"
        : "❌ EmployeeID $empID failed: $error\n";

    sleep(2); // optional delay
}
?>
