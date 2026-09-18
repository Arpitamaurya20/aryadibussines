<?php
@session_start();
include('../../includes/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();

header('Content-Type: application/json');

// Collect filter inputs
$dateFrom   = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo     = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$employeeId = isset($_GET['employee']) ? (int)$_GET['employee'] : 0;

// Function to normalize date to YYYY-MM-DD format
function normalizeDate($date, $default = null) {
    if (!empty($date)) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt && $dt->format('Y-m-d') === $date) {
            return $dt->format('Y-m-d'); // valid format already
        }
        $dt = date_create($date);
        if ($dt) {
            return $dt->format('Y-m-d');
        }
    }
    return $default;
}

$dateFrom = normalizeDate($dateFrom, date("Y-m-01"));
$dateTo   = normalizeDate($dateTo, date("Y-m-t"));

try {
    // Fetch all tickets raised by CityLead in date range
    $sql = "
        SELECT 
            t0.TicketID,
            t0.AssignedTo AS CityLeadID,
            MAX(t3.Type) AS TicketType,
            MIN(CASE WHEN t1.Status = 'Assigned' THEN CONCAT(t1.CreatedDate,' ',t1.CreatedTime) END) AS AssignedAt,
            MIN(CASE WHEN t2.Status = 'Quote Approved' THEN CONCAT(t2.CreatedDate,' ',t2.CreatedTime) END) AS ApprovedAt
        FROM corporate_ticket_status_history t0
        LEFT JOIN corporate_ticket_status_history t1 
            ON t0.TicketID = t1.TicketID AND t1.Status = 'Assigned'
        LEFT JOIN corporate_ticket_status_history t2 
            ON t0.TicketID = t2.TicketID AND t2.Status = 'Quote Approved'
        LEFT JOIN corporate_tickets t3
            ON t0.TicketID = t3.ID
        WHERE t0.Status = 'Raised'
          AND t0.AssignedTo = $employeeId
          AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
        GROUP BY t0.TicketID, t0.AssignedTo
    ";

    $rows = $core->_getSQLRecords($conn, $sql);



    $summary = [
        "CityLeadID" => $employeeId,
        "TotalTickets" => 0,
        "Within48Hour" => 0,
        "After48Hour" => 0,
        "Pending" => 0,
        "NotApprovedIn48Hour" => 0,
        "Performance%" => 0,
        "AmcTickets" => 0 // Separate count for AMC tickets
    ];

    foreach ($rows as $row) {
        // If AMC, count separately and skip performance calculation
        if (($row['TicketType']) === 'AMC') {
            $summary['AmcTickets']++;
            continue;
        }

        $summary["TotalTickets"]++;

        $assignedAt = $row['AssignedAt'];
        $approvedAt = $row['ApprovedAt'];

        if ($assignedAt && $approvedAt) {
            $diffMin = (strtotime($approvedAt) - strtotime($assignedAt)) / 60;
            if ($diffMin <= 2880) { // within 48 hours
                $summary["Within48Hour"]++;
            } else {
                $summary["After48Hour"]++;
            }
        } elseif ($assignedAt && !$approvedAt) {
            // Ticket still pending
            $summary["Pending"]++;

            // SLA check
            $diffMin = (time() - strtotime($assignedAt)) / 60;
            if ($diffMin >= 2880) {
                $summary["NotApprovedIn48Hour"]++;
            }
        }
    }

    // Performance % (excluding AMC tickets)
    $total = $summary["TotalTickets"];
    $summary["Performance%"] = $total > 0 ? round(($summary["Within48Hour"] / $total) * 100, 2) : 0;

    echo json_encode([
        "success" => true,
        "data" => [$summary],
        "filters_applied" => [
            "date_from" => $dateFrom,
            "date_to" => $dateTo,
            "employee_id" => $employeeId
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>
