<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

require_once('../controllers/common_controllers.php');
$conn = _connectodb(); // <-- MySQLi connection

$baseUrl = "https://techxpertindia.in/admin/dashboard/employee_kpi_whatsapp.php";

// 🔹 Date range for current month
$dateFrom = date('Y-m-01'); // First day of current month
$dateTo = date('Y-m-d', strtotime('-1 day'));

// 🔹 Fetch active employees
$sql = "SELECT 
    ID AS EmployeeID, 
    Name, 
    Designation, 
    ContactNumber, 
    City,
    EmployeeNumber
    FROM employees
    WHERE IsActive = 1
    AND ID NOT IN (
        SELECT EmployeeID 
        FROM user_roles 
        WHERE Role = 'Vendor' 
            AND IsActive = 1
    )";

$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    die("No active employees found.\n");
}



// 🔹 Loop through employees
while ($emp = $result->fetch_assoc()) {
    $empID = $emp['EmployeeID'];
    $name = $emp['Name'];
    $city = $emp['City'];
    $role = !empty($emp['Designation']) ? $emp['Designation'] : "Employee";
    $empNo = $emp['ContactNumber'];

    // ✅ Build API URL
    $apiUrl = $baseUrl . "?Role=" . urlencode($role) .
              "&Employee=" . urlencode($empID) .
              "&EmployeeNumber=" . urlencode($empNo) .
              "&Datefrom=" . urlencode($dateFrom) .
              "&Dateto=" . urlencode($dateTo);

    // ✅ cURL Request
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'Accept: */*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
    ]
]);
    $response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debugging output
// echo "\n--- API Call ---\n";
// echo "URL: $apiUrl\n";
// echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo  $response;
}

    sleep(2); // small delay between requests
}



// echo "✅ KPI WhatsApp automation completed successfully.\n" .$apiUrl;
?>
