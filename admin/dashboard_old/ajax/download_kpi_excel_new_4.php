<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

@session_start();
include('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');

$conn = _connectodb();
$core = new Core();
$core->setTimeZone();

// ==================== CONFIG ==================== //
$date_from = $_GET['Datefrom'] ?? date('Y-m-01');
$date_to   = $_GET['Dateto'] ?? date('Y-m-d');
$employee_id = isset($_GET['EmployeeID']) ? intval($_GET['EmployeeID']) : 0;

// ----------------- Date Normalizer ----------------- //
function normalizeDate($date, $default = null) {
    if (!empty($date)) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt && $dt->format('Y-m-d') === $date) return $date;

        $dt2 = date_create($date);
        if ($dt2) return $dt2->format('Y-m-d');
    }
    return $default;
}

$date_from = normalizeDate($date_from, date('Y-m-01'));
$date_to   = normalizeDate($date_to, date('Y-m-t'));

// ----------------- Helpers ----------------- //
function isExecutiveDesignation($designation) {
    $keywords = [
        'executive','sr. executive','senior helpdesk','purchase executive',
        'web developer','app developer','mis','hr','finance',
        'projectmanager','asst.manager'
    ];
    $designation = strtolower(trim($designation));
    foreach ($keywords as $word) {
        if (strpos($designation, $word) !== false) return true;
    }
    return false;
}

function avg($arr){
    $arr = array_values(array_filter($arr, fn($v) => $v !== null));
    return count($arr) ? array_sum($arr) / count($arr) : 0;
}

// ----------------- Fetch KPI ----------------- //
function fetchKPI($employeeId, $dateFrom, $dateTo, $type, $conn, $core) {
    $result = ["success"=>false,"data"=>[]];

    switch($type){

        // =========================================================
        //                    ASSIGNMENT KPI
        // =========================================================
        case 'assignment':
            $sql = "
                SELECT 
                    t1.AssignedTo AS CityLeadID,
                    COUNT(DISTINCT t1.TicketID) AS TotalTickets,
                    SUM(CASE 
                        WHEN TIMESTAMPDIFF(MINUTE,
                            CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                            CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                        ) <= 60 THEN 1 ELSE 0 END) AS Within1Hour,
                    SUM(CASE 
                        WHEN TIMESTAMPDIFF(MINUTE,
                            CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                            CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                        ) > 60 THEN 1 ELSE 0 END) AS After1Hour
                FROM corporate_ticket_status_history t1
                JOIN corporate_ticket_status_history t2 
                    ON t1.TicketID = t2.TicketID
                   AND t1.Status = 'Raised'
                   AND t2.Status = 'Assigned'
                   AND CONCAT(t2.CreatedDate,' ',t2.CreatedTime) > CONCAT(t1.CreatedDate,' ',t1.CreatedTime)
                WHERE t1.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
            ";
            if ($employeeId > 0) $sql .= " AND t1.AssignedTo = $employeeId ";
            $sql .= " GROUP BY t1.AssignedTo ";

            $rows = $core->_getSQLRecords($conn, $sql);

            // Reassignment Fix
            $reassignSQL = "
                SELECT TicketID, COUNT(*) AS totalAssign
                FROM corporate_ticket_status_history
                WHERE Status='Assigned'
                  AND TicketID IN (
                        SELECT TicketID 
                        FROM corporate_ticket_status_history
                        WHERE Status='Raised'
                          AND CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
                )
                GROUP BY TicketID
            ";

            $reassignRows = $core->_getSQLRecords($conn, $reassignSQL);

            $ticketReassign = [];
            foreach ($reassignRows as $r) {
                $ticketReassign[$r['TicketID']] = max(0, $r['totalAssign'] - 1);
            }

            $totalReassign = array_sum($ticketReassign);

            $summary = ["Performance%" => 0, "ReassignCount" => $totalReassign];

            foreach ($rows as $row) {
                $within = (int)$row['Within1Hour'];
                $after  = (int)$row['After1Hour'];
                $total  = $within + $after;

                $newWithin = $within + $totalReassign;
                $newAfter  = max(0, $after - $totalReassign);

                $summary['Performance%'] = ($total > 0)
                    ? round(($newWithin / $total) * 100, 2)
                    : 0;
            }

            $result['success'] = true;
            $result['data'] = [$summary];
        break;


        // =========================================================
        //                        CLOSED KPI
        // =========================================================
        case 'closed':

            $daysInRange = (new DateTime($dateFrom))->diff(new DateTime($dateTo))->days + 1;
            $standardTarget = $daysInRange * 5;

            $sqlAvailable = "
                SELECT COUNT(DISTINCT TicketID) AS total
                FROM corporate_ticket_status_history
                WHERE AssignedTo = $employeeId
                  AND CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
            ";
            $available = $core->_getSQLDetails($conn, $sqlAvailable);
            $availableTickets = intval($available['total'] ?? 0);

            $sqlClosed = "
                SELECT 
                    t0.TicketID,
                    MAX(CASE WHEN t2.Status='Closed' THEN CONCAT(t2.CreatedDate,' ',t2.CreatedTime) END) AS ClosedAt
                FROM corporate_ticket_status_history t0
                LEFT JOIN corporate_ticket_status_history t2 
                    ON t0.TicketID = t2.TicketID AND t2.Status='Closed'
                WHERE t0.AssignedTo = $employeeId
                  AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
                GROUP BY t0.TicketID
                HAVING ClosedAt IS NOT NULL
            ";

            $rows = $core->_getSQLRecords($conn, $sqlClosed);
            $closedTickets = count($rows);

            // --- FIXED SAFE strtotime() ---
            $closedWithin24 = 0;
            foreach ($rows as $r) {
                $ticketId = $r['TicketID'];

                $sqlTimes = "
                    SELECT 
                        MAX(CASE WHEN Status='Quote Approved' THEN CONCAT(CreatedDate,' ',CreatedTime) END) AS QuoteApprovedAt,
                        MAX(CASE WHEN Status='Closed' THEN CONCAT(CreatedDate,' ',CreatedTime) END) AS ClosedAt
                    FROM corporate_ticket_status_history
                    WHERE TicketID = {$ticketId}
                ";

                $times = $core->_getSQLDetails($conn, $sqlTimes);

                $quote = !empty($times['QuoteApprovedAt']) ? strtotime($times['QuoteApprovedAt']) : null;
                $close = !empty($times['ClosedAt']) ? strtotime($times['ClosedAt']) : null;

                if ($quote !== null && $close !== null && ($close - $quote) <= 259200) {
                    $closedWithin24++;
                }
            }

            $closingTarget = min($standardTarget, $availableTickets);
            $performance = ($closingTarget > 0)
                ? round(($closedTickets / $closingTarget) * 100, 2)
                : 0;

            $result['success'] = true;
            $result['data'] = [[
                "Performance%" => $performance,
                "ClosedTickets" => $closedTickets,
                "ClosedWithin24" => $closedWithin24,
                "ClosingTarget"  => $closingTarget,
                "AvailableTickets" => $availableTickets
            ]];
        break;


        // =========================================================
        //                     ATTENDANCE KPI
        // =========================================================
        case 'attendance':

            $sqlHolidays = "
                SELECT HolidaysDate 
                FROM listofholidays 
                WHERE IsActive = 1
                AND HolidaysDate BETWEEN '$dateFrom' AND '$dateTo'
            ";
            $holRows = $core->_getSQLRecords($conn, $sqlHolidays);
            $holidays = array_column($holRows, 'HolidaysDate');

            $start  = new DateTime($dateFrom);
            $end    = new DateTime($dateTo); $end->modify('+1 day');

            $workingDays = 0;
            foreach (new DatePeriod($start,new DateInterval('P1D'),$end) as $d) {
                $ds = $d->format('Y-m-d');
                if ($d->format('N') != 7 && !in_array($ds, $holidays)) $workingDays++;
            }

            $sql = "
                SELECT COUNT(DISTINCT DATE(RecordDate)) AS PresentDays
                FROM employee_attendance
                WHERE EmployeeID=$employeeId 
                AND DATE(RecordDate) BETWEEN '$dateFrom' AND '$dateTo'
            ";
            $att = $core->_getSQLDetails($conn, $sql);
            $present = intval($att['PresentDays'] ?? 0);

            $result['success'] = true;
            $result['data'] = [[
                "Performance%" => $workingDays > 0 
                    ? round(($present / $workingDays) * 100, 2)
                    : 0,
                "WorkingDays" => $workingDays,
                "PresentDays" => $present,
                "Holidays" => $holidays
            ]];
        break;

                // =========================================================
        //                        QUOTATION KPI
        // =========================================================
        case 'quotation':

            // Total tickets assigned to employee
            $sqlTotal = "
                SELECT DISTINCT TicketID
                FROM corporate_ticket_status_history
                WHERE AssignedTo = $employeeId
                  AND CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
            ";
            $rows = $core->_getSQLRecords($conn, $sqlTotal);
            $totalTickets = count($rows);

            // Count tickets where quotation approved
            $approvedWithin48 = 0;

            foreach ($rows as $r) {
                $ticketId = $r['TicketID'];

                $sqlTimes = "
                    SELECT 
                        MAX(CASE WHEN Status='Raised' THEN CONCAT(CreatedDate,' ',CreatedTime) END) AS RaisedAt,
                        MAX(CASE WHEN Status='Quote Approved' THEN CONCAT(CreatedDate,' ',CreatedTime) END) AS QuoteApprovedAt
                    FROM corporate_ticket_status_history
                    WHERE TicketID = $ticketId
                ";

                $times = $core->_getSQLDetails($conn, $sqlTimes);

                $raisedAt = !empty($times['RaisedAt']) ? strtotime($times['RaisedAt']) : null;
                $approvedAt = !empty($times['QuoteApprovedAt']) ? strtotime($times['QuoteApprovedAt']) : null;

                // Approve within 48 hours (172800 seconds)
                if ($raisedAt !== null && $approvedAt !== null && ($approvedAt - $raisedAt) <= 172800) {
                    $approvedWithin48++;
                }
            }

            $performance = ($totalTickets > 0)
                ? round(($approvedWithin48 / $totalTickets) * 100, 2)
                : 0;

            $result['success'] = true;
            $result['data'] = [[
                "Performance%" => $performance,
                "TotalTickets" => $totalTickets,
                "ApprovedWithin48" => $approvedWithin48
            ]];
        break;

    }

    return $result;
}

// ==================== FETCH EMPLOYEES ==================== //
$where = ($employee_id > 0) ? "AND e.ID=$employee_id" : "";

$employees = $core->_getSQLRecords($conn, "
    SELECT 
        e.ID,
        e.EmployeeNumber,
        e.Name, 
        e.Designation, 
        e.State,
        e.InHandSalary
    FROM employees e
    WHERE e.IsActive = 1 
      AND e.ID NOT IN (SELECT EmployeeID FROM user_roles WHERE Role='Vendor')
      $where
");

// ==================== PREPARE CSV ==================== //
$filename = "All_Employees_KPI_Report_{$date_from}_to_{$date_to}.csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen('php://output', 'w');

fputcsv($output, ['=============================']);
fputcsv($output, ['Employee KPI Performance Report']);
fputcsv($output, ['=============================']);
fputcsv($output, []);
fputcsv($output, ['Date Range', "$date_from to $date_to"]);
fputcsv($output, []);
fputcsv($output, [
    'Employee Name','Employee Number','Designation','State',
    'Assignment %','Quotation %','Closed %','Attendance %',
    'Overall KPI %','Payable Salary (₹)','Salary %'
]);

// ==================== MAIN LOOP ==================== //
foreach ($employees as $emp) {

    $id = intval($emp['ID']);
    $designation = $emp['Designation'] ?? '';
    $designationLower = strtolower($designation);

    $assign = fetchKPI($id,$date_from,$date_to,'assignment',$conn,$core);
    $quote  = fetchKPI($id,$date_from,$date_to,'quotation',$conn,$core);
    $closed = fetchKPI($id,$date_from,$date_to,'closed',$conn,$core);
    $attend = fetchKPI($id,$date_from,$date_to,'attendance',$conn,$core);

    $assignment_pct = floatval($assign['data'][0]['Performance%'] ?? 0);
    $quotation_pct  = floatval($quote['data'][0]['Performance%'] ?? 0);
    $closed_pct     = floatval($closed['data'][0]['Performance%'] ?? 0);
    $attendance_pct = floatval($attend['data'][0]['Performance%'] ?? 0);

    // -------- Overall KPI Logic --------
    if (isExecutiveDesignation($designation)) {
        $overall = $attendance_pct;
    }
    elseif (strpos($designationLower, 'technician') !== false) {
        $overall = round(avg([$attendance_pct, $closed_pct]), 2);
    }
    else {
        $overall = round(avg([$assignment_pct, $closed_pct, $attendance_pct]), 2);
    }

    $fullSalary = floatval($emp['InHandSalary']);
    $payableSalary = ($overall < 90) ? (($overall / 100) * $fullSalary) : $fullSalary;
    $salaryPct = ($fullSalary > 0) ? round(($payableSalary / $fullSalary) * 100, 2) : 0;

    fputcsv($output, [
        strtoupper($emp['Name']),
        strtoupper($emp['EmployeeNumber']),
        strtoupper($emp['Designation']),
        strtoupper($emp['State']),
        $assignment_pct,
        $quotation_pct,
        $closed_pct,
        $attendance_pct,
        $overall,
        number_format($payableSalary,2),
        $salaryPct
    ]);
}

fclose($output);
exit;

?>
