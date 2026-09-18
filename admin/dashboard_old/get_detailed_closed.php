<?php
@session_start();
include('../../includes/autoloader.inc.php');

$dbh  = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();

header('Content-Type: application/json');

$dateFrom   = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo     = isset($_GET['date_to'])   ? $_GET['date_to']   : '';
$employeeId = isset($_GET['employee'])  ? (int)$_GET['employee'] : 0;

function normalizeDate($date, $default = null) {
    if (!empty($date)) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt && $dt->format('Y-m-d') === $date) return $dt->format('Y-m-d');
        $dt = date_create($date);
        if ($dt) return $dt->format('Y-m-d');
    }
    return $default;
}

$dateFrom = normalizeDate($dateFrom, date("Y-m-d"));
$dateTo   = normalizeDate($dateTo,   date("Y-m-d"));

try {
    // ---- Days in Range
    $daysInRange   = (new DateTime($dateFrom))->diff(new DateTime($dateTo))->days + 1;
    $standardTarget = $daysInRange * 5;

    // ---- Total tickets assigned in range
    $sqlAvailable = "
        SELECT COUNT(DISTINCT TicketID) AS total
        FROM corporate_ticket_status_history
        WHERE AssignedTo = $employeeId
          AND CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
    ";
    // ✅ Use your helper that returns one associative row
    $availableRow = $core->_getSQLDetails($conn, $sqlAvailable);
    $availableTickets = isset($availableRow['total']) ? (int)$availableRow['total'] : 0;

    // ---- Closing Target
    $closingTarget = ($availableTickets >= $standardTarget)
        ? $standardTarget
        : $availableTickets;

    // ---- Closed Tickets + SLA (24 hrs)
    $sqlClosed = "
        SELECT 
            t0.TicketID,
            MAX(CASE WHEN t1.Status='Quote Approved'
                     THEN CONCAT(t1.CreatedDate,' ',t1.CreatedTime) END) AS QuoteApprovedAt,
            MAX(CASE WHEN t2.Status='Closed'
                     THEN CONCAT(t2.CreatedDate,' ',t2.CreatedTime) END) AS ClosedAt
        FROM corporate_ticket_status_history t0
        LEFT JOIN corporate_ticket_status_history t1 
            ON t0.TicketID = t1.TicketID AND t1.Status = 'Quote Approved'
        LEFT JOIN corporate_ticket_status_history t2 
            ON t0.TicketID = t2.TicketID AND t2.Status = 'Closed'
        WHERE t0.AssignedTo = $employeeId
          AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
        GROUP BY t0.TicketID
        HAVING ClosedAt IS NOT NULL
    ";
    $rows = $core->_getSQLRecords($conn, $sqlClosed);

    $closedTickets  = count($rows);
    $closedWithin24 = 0;
    foreach ($rows as $row) {
        $quote = strtotime($row['QuoteApprovedAt']);
        $close = strtotime($row['ClosedAt']);
        if ($quote && ($close - $quote) <= 86400) { // 24 hrs = 86400 sec
            $closedWithin24++;
        }
    }

    // ---- Performance %
    $performance = ($closingTarget > 0)
        ? round(($closedTickets / $closingTarget) * 100, 2)
        : 0;

    echo json_encode([
        "success" => true,
        "data" => [
            "EmployeeID"        => $employeeId,
            "DateRange"         => "$dateFrom to $dateTo",
            "DaysInRange"       => $daysInRange,
            "AvailableTickets"  => $availableTickets,
            "StandardTarget"    => $standardTarget,
            "ClosingTarget"     => $closingTarget,
            "ClosedTickets"     => $closedTickets,
            "ClosedWithin24Hour"=> $closedWithin24,
            "ClosedAfter24Hour" => $closedTickets - $closedWithin24,
            "Performance%"      => $performance
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error"   => "Database error: " . $e->getMessage()
    ]);
}
