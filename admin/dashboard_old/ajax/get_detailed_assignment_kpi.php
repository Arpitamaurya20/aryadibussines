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

// Normalize date
function normalizeDate($date, $default = null) {
    if (!empty($date)) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt && $dt->format('Y-m-d') === $date) return $date;

        $dt = date_create($date);
        if ($dt) return $dt->format('Y-m-d');
    }
    return $default;
}

$dateFrom = normalizeDate($dateFrom, date("Y-m-01"));
$dateTo   = normalizeDate($dateTo, date("Y-m-t"));

try {

    // Main ticket performance SQL
    $sql = "
        SELECT 
            t1.AssignedTo AS CityLeadID,

            COUNT(DISTINCT t1.TicketID) AS TotalTickets,

            GROUP_CONCAT(
                DISTINCT CASE 
                    WHEN TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                        CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                    ) <= 60 THEN t1.TicketID
                END
            ) AS Within1HourIDs,

            SUM(
                CASE 
                    WHEN TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                        CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                    ) <= 60 THEN 1 ELSE 0
                END
            ) AS Within1Hour,

            GROUP_CONCAT(
                DISTINCT CASE 
                    WHEN TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                        CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                    ) > 60 THEN t1.TicketID
                END
            ) AS After1HourIDs,

            SUM(
                CASE 
                    WHEN TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                        CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                    ) > 60 THEN 1 ELSE 0
                END
            ) AS After1Hour

        FROM corporate_ticket_status_history t1
        JOIN corporate_ticket_status_history t2 
            ON t1.TicketID = t2.TicketID
           AND t1.Status = 'Raised'
           AND t2.Status = 'Assigned'
           AND CONCAT(t2.CreatedDate,' ',t2.CreatedTime) > CONCAT(t1.CreatedDate,' ',t1.CreatedTime)

        WHERE t1.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
    ";

    if ($employeeId > 0) {
        $sql .= " AND t1.AssignedTo = $employeeId ";
    }

    $sql .= " GROUP BY t1.AssignedTo ";

    $rows = $core->_getSQLRecords($conn, $sql);

    // -----------------------------
    // REASSIGNMENT COUNT LOGIC
    // -----------------------------

    $reassignSQL = "
        SELECT TicketID, COUNT(*) AS totalAssign
        FROM corporate_ticket_status_history
        WHERE Status='Assigned'
          AND TicketID IN (
                SELECT TicketID FROM corporate_ticket_status_history
                WHERE Status='Raised'
                  AND CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
                  " . ($employeeId > 0 ? " AND AssignedTo=$employeeId " : "") . "
          )
        GROUP BY TicketID
    ";

    $reassignRows = $core->_getSQLRecords($conn, $reassignSQL);

    // Build reassignment map
    $ticketReassign = [];
    foreach ($reassignRows as $r) {
        $ticketId = $r['TicketID'];
        $assignCount = (int)$r['totalAssign'];

        // Real reassign count = totalAssign - 1
        $reassignCount = max(0, $assignCount - 1);

        $ticketReassign[$ticketId] = $reassignCount;
    }

    // Prepare final output
    $performance = [];

    foreach ($rows as $row) {

        

        // Build ReassignDetails format: "52825:2,52839:3.."
        $detailsArr = [];
        $totalReassign = 0;

        foreach ($ticketReassign as $tid => $rc) {
            if ($rc > 0) {
                $detailsArr[] = "$tid:$rc";
                $totalReassign += $rc;
            }
        }

        $within = (int)$row['Within1Hour'];
        $after  = (int)$row['After1Hour'];
        $total  = $within + $after;
        $percent = $total > 0 ? round((($within+$totalReassign) / $total) * 100, 2) : 0;

        $performance[] = [
            "CityLeadID"         => $row['CityLeadID'],
            "TotalTickets"       => $total,

            "Within1Hour"        => $within,
            "Within1HourIDs"     => $row['Within1HourIDs'],

            "After1Hour"         => ($after-$totalReassign),
            "After1HourIDs"      => $row['After1HourIDs'],

            "Performance%"       => $percent,

            "TotalReassignCount" => $totalReassign,
            "ReassignDetails"    => implode(",", $detailsArr),
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $performance,
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
