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


// ----------------- Fetch KPI ----------------- //
function fetchKPI($employeeId, $dateFrom, $dateTo, $type, $conn, $core) {
    $result = ["success"=>false,"data"=>[]];

    switch($type){

        // ---------------- ASSIGNMENT KPI ---------------- //
        case 'assignment':
            $sql = "
                SELECT 
                    t1.AssignedTo AS CityLeadID,
                    COUNT(DISTINCT t1.TicketID) AS TotalTickets,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE,
                        CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                        CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                    ) <= 60 THEN 1 ELSE 0 END) AS Within1Hour,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE,
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
                  AND t1.AssignedTo = $employeeId
                GROUP BY t1.AssignedTo
            ";
            $rows = $core->_getSQLRecords($conn, $sql);

            $summary = ["Performance%"=>0];
            foreach ($rows as $row) {
                $within = (int)$row['Within1Hour'];
                $after  = (int)$row['After1Hour'];
                $total  = $within + $after;
                $summary['Performance%'] = $total > 0 ? round(($within / $total) * 100, 2) : 0;
            }
            $result['success'] = true;
            $result['data'] = [$summary];
            break;

        // ---------------- QUOTATION KPI ---------------- //
        case 'quotation':
            $sql = "
                SELECT 
                    t0.TicketID,
                    MAX(t3.Type) AS TicketType,
                    MIN(CASE WHEN t1.Status='Assigned' THEN CONCAT(t1.CreatedDate,' ',t1.CreatedTime) END) AS AssignedAt,
                    MIN(CASE WHEN t2.Status='Quote Approved' THEN CONCAT(t2.CreatedDate,' ',t2.CreatedTime) END) AS ApprovedAt
                FROM corporate_ticket_status_history t0
                LEFT JOIN corporate_ticket_status_history t1 ON t0.TicketID=t1.TicketID AND t1.Status='Assigned'
                LEFT JOIN corporate_ticket_status_history t2 ON t0.TicketID=t2.TicketID AND t2.Status='Quote Approved'
                LEFT JOIN corporate_tickets t3 ON t0.TicketID=t3.ID
                WHERE t0.Status='Raised'
                AND t0.AssignedTo=$employeeId
                AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
                GROUP BY t0.TicketID
            ";

            $rows = $core->_getSQLRecords($conn,$sql);
            $within48 = 0; $total = 0;

            foreach ($rows as $r) {
                if ($r['TicketType']==='AMC') continue;
                if (!empty($r['AssignedAt']) && !empty($r['ApprovedAt'])) {
                    $diffMin = (strtotime($r['ApprovedAt']) - strtotime($r['AssignedAt'])) / 60;
                    if ($diffMin <= 2880) $within48++;
                    $total++;
                }
            }

            $result['success'] = true;
            $result['data'] = [[
                "Performance%" => $total > 0 ? round(($within48/$total)*100, 2) : 0
            ]];
            break;

        // ---------------- CLOSED KPI ---------------- //
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
            $availableTickets = (int)($available['total'] ?? 0);

            $sqlClosed = "
                SELECT COUNT(DISTINCT t0.TicketID) AS ClosedCount
                FROM corporate_ticket_status_history t0
                LEFT JOIN corporate_ticket_status_history t2 
                    ON t0.TicketID = t2.TicketID AND t2.Status='Closed'
                WHERE t0.AssignedTo = $employeeId
                  AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
                  AND t2.Status IS NOT NULL
            ";
            $closed = $core->_getSQLDetails($conn, $sqlClosed);
            $closedTickets = (int)($closed['ClosedCount'] ?? 0);

            $closingTarget = min($standardTarget, $availableTickets);
            $performance = ($closingTarget > 0)
                ? round(($closedTickets / $closingTarget) * 100, 2)
                : 0;

            $result['success'] = true;
            $result['data'] = [["Performance%" => $performance]];
            break;

        // ---------------- ATTENDANCE KPI ---------------- //
        case 'attendance':
            $start  = new DateTime($dateFrom);
            $end    = new DateTime($dateTo);
            $end->modify('+1 day');
            $workingDays = 0;
            foreach (new DatePeriod($start,new DateInterval('P1D'),$end) as $d) {
                if ($d->format('N') != 7) $workingDays++;
            }

            $sql = "
                SELECT COUNT(DISTINCT DATE(RecordDate)) AS PresentDays
                FROM employee_attendance
                WHERE EmployeeID=$employeeId 
                AND DATE(RecordDate) BETWEEN '$dateFrom' AND '$dateTo'
            ";
            $att = $core->_getSQLDetails($conn, $sql);
            $present = $att['PresentDays'] ?? 0;

            $result['success'] = true;
            $result['data'] = [[
                "Performance%" => $workingDays > 0 
                    ? round(($present / $workingDays) * 100, 2)
                    : 0
            ]];
            break;
    }

    return $result;
}


// ----------------- Helpers ----------------- //
function isExecutiveDesignation($designation) {
    $keywords = [ 'executive',
        'sr. executive',
        'senior helpdesk',
        'purchase executive',
        'web developer',
        'app developer',
        'mis',
        'hr',
        'finance',
        'projectmanager',
        'asst.manager'];
    $designation = strtolower($designation);
    foreach ($keywords as $word) {
        if (strpos($designation, $word) !== false) return true;
    }
    return false;
}
function avg($arr){ return count($arr)? array_sum($arr)/count($arr) : 0; }


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
    $id = $emp['ID'];
    $designation = strtolower($emp['Designation']);

    // Fetch KPIs
    $assign = fetchKPI($id,$date_from,$date_to,'assignment',$conn,$core);
    $closed = fetchKPI($id,$date_from,$date_to,'closed',$conn,$core);
    $attend = fetchKPI($id,$date_from,$date_to,'attendance',$conn,$core);

    $assignment_pct = floatval($assign['data'][0]['Performance%'] ?? 0);
    $closed_pct     = floatval($closed['data'][0]['Performance%'] ?? 0);
    $attendance_pct = floatval($attend['data'][0]['Performance%'] ?? 0);

    // ================= TECHNICIAN LOGIC ================= //
    if ((stripos($designation, 'TECHNICIAN') !== false) || stripos($designation, 'technician') !== false) {
        // Technician → ignore quotation
        $quotation_pct = 0;
        $overall = avg([$assignment_pct, $closed_pct, $attendance_pct]);
    } 
    else {
        // Other roles → include quotation
        $quote = fetchKPI($id,$date_from,$date_to,'quotation',$conn,$core);
        $quotation_pct = floatval($quote['data'][0]['Performance%'] ?? 0);
        $overall = avg([$assignment_pct, $quotation_pct, $closed_pct, $attendance_pct]);
    }

    $fullSalary = floatval($emp['InHandSalary']);
    $payableSalary = ($overall < 90) ? ($overall / 100) * $fullSalary : $fullSalary;
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
        round($overall,2),
        number_format($payableSalary,2),
        $salaryPct
    ]);
}

fclose($output);
exit;
?>
