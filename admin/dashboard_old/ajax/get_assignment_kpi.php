<?php
@session_start();
include('../../includes/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();

header('Content-Type: application/json');

// Collect filter inputs
$role       = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$employeeId = isset($_GET['employee']) ? (int)$_GET['employee'] : 0;
$dateFrom   = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$dateTo     = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

// Define the roles that need to be tracked for assignment compliance
$trackedRoles = ['City Lead', 'Region Lead', 'Account Manager', 'Branch Account Manager'];

// Base SQL to get assignment compliance data
$sql = "
    SELECT 
        ur.Role,
        e.ID as EmployeeID,
        e.Name as EmployeeName,
        COUNT(DISTINCT t.ID) AS TotalTickets,
        SUM(
            CASE 
                WHEN t.AssignedTo = e.ID 
                AND EXISTS (
                    SELECT 1 FROM corporate_ticket_status_history h2 
                    WHERE h2.TicketID = t.ID 
                    AND h2.AssignedTo != t.AssignedTo 
                    AND h2.AssignedTo != -1
                    AND TIMESTAMPDIFF(
                        HOUR, 
                        STR_TO_DATE(CONCAT(t.CreatedDate, ' ', t.CreatedTime), '%Y-%m-%d %H:%i:%s'),
                        STR_TO_DATE(CONCAT(h2.CreatedDate, ' ', h2.CreatedTime), '%Y-%m-%d %H:%i:%s')
                    ) <= 2
                ) THEN 1 
                ELSE 0 
            END
        ) AS CompliantTickets,
        SUM(
            CASE 
                WHEN t.AssignedTo = e.ID 
                AND NOT EXISTS (
                    SELECT 1 FROM corporate_ticket_status_history h3 
                    WHERE h3.TicketID = t.ID 
                    AND h3.AssignedTo != t.AssignedTo 
                    AND h3.AssignedTo != -1
                    AND TIMESTAMPDIFF(
                        HOUR, 
                        STR_TO_DATE(CONCAT(t.CreatedDate, ' ', t.CreatedTime), '%Y-%m-%d %H:%i:%s'),
                        STR_TO_DATE(CONCAT(h3.CreatedDate, ' ', h3.CreatedTime), '%Y-%m-%d %H:%i:%s')
                    ) <= 2
                ) THEN 1 
                ELSE 0 
            END
        ) AS NonCompliantTickets
    FROM corporate_tickets t
    INNER JOIN employees e ON t.AssignedTo = e.ID
    INNER JOIN user_roles ur ON e.ID = ur.EmployeeID
    WHERE t.IsActive = 1
    AND ur.Role IN ('" . implode("', '", $trackedRoles) . "')
    AND t.AssignedTo != -1
";

// Apply filters
if (!empty($role)) {
    $sql .= " AND ur.Role = '$role' ";
}

if ($employeeId > 0) {
    $sql .= " AND e.ID = $employeeId ";
}

if (!empty($dateFrom) && !empty($dateTo)) {
    $sql .= " AND STR_TO_DATE(CONCAT(t.CreatedDate, ' ', t.CreatedTime), '%Y-%m-%d %H:%i:%s') 
              BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59' ";
}

$sql .= " GROUP BY ur.Role, e.ID, e.Name ORDER BY ur.Role, e.Name ";

// Run query
$data = $core->_getSQLRecords($conn, $sql);

$resultData = [];
$roleSummary = [];

foreach ($data as $row) {
    $compliancePercent = $row['TotalTickets'] > 0 
        ? round(($row['CompliantTickets'] / $row['TotalTickets']) * 100, 2) 
        : 0;

    $resultData[] = [
        "role" => ucfirst(str_replace("_", " ", $row['Role'])),
        "employee_id" => (int)$row['EmployeeID'],
        "employee_name" => $row['EmployeeName'],
        "total" => (int)$row['TotalTickets'],
        "compliant" => (int)$row['CompliantTickets'],
        "non_compliant" => (int)$row['NonCompliantTickets'],
        "percent" => $compliancePercent
    ];

    // Aggregate by role for summary
    if (!isset($roleSummary[$row['Role']])) {
        $roleSummary[$row['Role']] = [
            'total' => 0,
            'compliant' => 0,
            'non_compliant' => 0
        ];
    }
    
    $roleSummary[$row['Role']]['total'] += (int)$row['TotalTickets'];
    $roleSummary[$row['Role']]['compliant'] += (int)$row['CompliantTickets'];
    $roleSummary[$row['Role']]['non_compliant'] += (int)$row['NonCompliantTickets'];
}

// Create role-level summary
$roleSummaryData = [];
foreach ($roleSummary as $role => $summary) {
    $compliancePercent = $summary['total'] > 0 
        ? round(($summary['compliant'] / $summary['total']) * 100, 2) 
        : 0;

    $roleSummaryData[] = [
        "role" => ucfirst(str_replace("_", " ", $role)),
        "total" => $summary['total'],
        "compliant" => $summary['compliant'],
        "non_compliant" => $summary['non_compliant'],
        "percent" => $compliancePercent
    ];
}

echo json_encode([
    "success" => true,
    "kpi" => $roleSummaryData, // For chart display
    "detailed_kpi" => $resultData, // For detailed breakdown
    "tracked_roles" => $trackedRoles,
    "description" => "Assignment compliance: Tickets reassigned within 2 hours of initial assignment"
]);
?>
