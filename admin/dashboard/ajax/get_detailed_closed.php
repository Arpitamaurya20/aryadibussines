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

function normalizeDateStrict($date) {
    if (!$date) return null;

    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if ($dt && $dt->format('Y-m-d') === $date) {
        return $date;
    }

    return null;
}

$dateFrom = normalizeDateStrict($_GET['date_from'] ?? null);
$dateTo   = normalizeDateStrict($_GET['date_to'] ?? null);

if (!$dateFrom || !$dateTo) {
    throw new Exception("Invalid date format. Expected YYYY-MM-DD");
}

if (new DateTime($dateFrom) > new DateTime($dateTo)) {
    throw new Exception("date_from cannot be greater than date_to");
}


try {
    // ---- Days in Range
    $daysInRange   = (new DateTime($dateFrom))->diff(new DateTime($dateTo))->days + 1;
    $standardTarget = $daysInRange * 5;

    // ---- Total tickets assigned in range
    $sqlAvailable = "
    SELECT COUNT(DISTINCT t0.TicketID) AS total
    FROM corporate_ticket_status_history t0
    INNER JOIN corporate_ticket_status_history t1
        ON t0.TicketID = t1.TicketID
       AND t1.Status = 'Quote Approved'
    WHERE t0.AssignedTo = $employeeId
      AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
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
        MAX(CASE WHEN t1.Status = 'Quote Approved'
                 THEN CONCAT(t1.CreatedDate,' ',t1.CreatedTime) END) AS QuoteApprovedAt,
        MAX(CASE WHEN t2.Status = 'Closed'
                 THEN CONCAT(t2.CreatedDate,' ',t2.CreatedTime) END) AS ClosedAt
    FROM corporate_ticket_status_history t0
    LEFT JOIN corporate_ticket_status_history t1 
        ON t0.TicketID = t1.TicketID 
       AND t1.Status = 'Quote Approved'
    LEFT JOIN corporate_ticket_status_history t2 
        ON t0.TicketID = t2.TicketID 
       AND t2.Status = 'Closed'
    WHERE t0.AssignedTo = $employeeId
      AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
    GROUP BY t0.TicketID
    HAVING QuoteApprovedAt IS NOT NULL
       AND ClosedAt IS NOT NULL
";

    $rows = $core->_getSQLRecords($conn, $sqlClosed);

    $closedTickets       = count($rows);
$closedWithin24      = 0;

$sqlAvailableIDs = "
    SELECT DISTINCT TicketID
    FROM corporate_ticket_status_history
    WHERE AssignedTo = $employeeId
      AND CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
";
$availableRows = $core->_getSQLRecords($conn, $sqlAvailableIDs);

$availableTicketIDs = [];
foreach ($availableRows as $r) {
    $availableTicketIDs[] = (int)$r['TicketID'];
}


$allClosedTicketIDs      = [];
$closedWithin24TicketIDs = [];
$closedAfter24TicketIDs  = [];

foreach ($rows as $row) {

    $ticketId = (int)$row['TicketID'];
    $allClosedTicketIDs[] = $ticketId;

    if (empty($row['QuoteApprovedAt']) || empty($row['ClosedAt'])) {
        $closedAfter24TicketIDs[] = $ticketId;
        continue;
    }

    $quote = strtotime($row['QuoteApprovedAt']);
    $close = strtotime($row['ClosedAt']);

    if ($quote !== false && $close !== false && ($close - $quote) <= 86400) {
        $closedWithin24++;
        $closedWithin24TicketIDs[] = $ticketId;
    } else {
        $closedAfter24TicketIDs[] = $ticketId;
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
            "Performance%"      => $performance,
              
        "AllClosedTicketIDs"        => $allClosedTicketIDs,
        "ClosedWithin24TicketIDs"  => $closedWithin24TicketIDs,
        "ClosedAfter24TicketIDs"   => $closedAfter24TicketIDs,

        // AVAILABLE
       
        "AvailableTicketIDs"  => $availableTicketIDs,
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error"   => "Database error: " . $e->getMessage()
    ]);
}
