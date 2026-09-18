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

/* ================= INPUT ================= */
$date_from   = $_GET['Datefrom'] ?? date('Y-m-01');
$date_to     = $_GET['Dateto']   ?? date('Y-m-d');
$employee_id = isset($_GET['EmployeeID']) ? (int)$_GET['EmployeeID'] : 0;

// ----------------- HELPER: Normalize date ----------------- //
function normalizeDate($date, $default = null) {
    if (!empty($date)) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt && $dt->format('Y-m-d') === $date) return $dt->format('Y-m-d');
        $dt = date_create($date);
        if ($dt) return $dt->format('Y-m-d');
    }
    return $default;
}
$date_from = normalizeDate($date_from, date('Y-m-01'));
$date_to   = normalizeDate($date_to, date('Y-m-t'));

/* ================= HELPERS ================= */
function avg($arr){ return count($arr) ? array_sum($arr)/count($arr) : 0; }

function normalizeKPI($percentage, $hasData) {
    return ($hasData === false) ? 100 : floatval($percentage);
}

function safeRound($value, $precision = 2) {
    if ($value === '' || $value === null) return '';
    return round((float)$value, $precision);
}

function isExecutiveDesignation($designation) {
    if (!$designation) return false;
    $keywords = [
        'executive','sr. executive','senior helpdesk','purchase executive',
        'web developer','app developer','mis','hr','finance',
        'projectmanager','asst.manager','accounts','billing head'
    ];
    foreach ($keywords as $k) {
        if (stripos($designation, $k) !== false) return true;
    }
    return false;
}

/* ================= KPI FUNCTION (UNCHANGED) ================= */
/* ⚠️ This is SAME fetchKPI function you already use in WhatsApp */
function fetchKPI($employeeId, $dateFrom, $dateTo, $type, $conn, $core) {
    $result = ["success"=>false,"data"=>[]];

    switch($type){

    // ---------------- ASSIGNMENT KPI ---------------- //
case 'assignment':

    // -------------------------------------------
    // 1️⃣ MAIN KPI QUERY (Raised → Assigned)
    // -------------------------------------------
    $sql = "
        SELECT 
            t1.AssignedTo AS CityLeadID,
            COUNT(DISTINCT t1.TicketID) AS TotalTickets,
            SUM(
                CASE WHEN TIMESTAMPDIFF(MINUTE,
                    CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                    CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                ) <= 60 THEN 1 ELSE 0 END
            ) AS Within1Hour,
            SUM(
                CASE WHEN TIMESTAMPDIFF(MINUTE,
                    CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                    CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                ) > 60 THEN 1 ELSE 0 END
            ) AS After1Hour
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


    // -------------------------------------------
    // 2️⃣ REASSIGNMENT COUNT QUERY
    // -------------------------------------------
    $sqlReassign = "
        SELECT TicketID, COUNT(*) AS totalAssign
        FROM corporate_ticket_status_history
        WHERE Status='Assigned'
          AND TicketID IN (
                SELECT TicketID 
                FROM corporate_ticket_status_history
                WHERE Status='Raised'
                  AND CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
              " . ($employeeId > 0 ? " AND AssignedTo=$employeeId " : "") . "
          )
        GROUP BY TicketID
    ";

    $reassignRows = $core->_getSQLRecords($conn, $sqlReassign);

    // Map Ticket → TRUE reassignment count
    $ReassignMap = [];
    foreach ($reassignRows as $r) {
        $rc = max(0, ((int)$r['totalAssign'] - 1)); // if Assign called 3 times → 2 reassigned
        if ($rc > 0) {
            $ReassignMap[$r['TicketID']] = $rc;
        }
    }


    // -------------------------------------------
    // 3️⃣ BUILD RESULT WITH PERFORMANCE
    // -------------------------------------------
    $summary = [
        "TotalTickets"   => 0,
        "Within1Hour"    => 0,
        "After1Hour"     => 0,
        "ReassignCount"  => 0,
        "Performance%"   => 0,
        "ReassignDetails" => []
    ];

    // Build :2 and :3 strings
    $reDetails = [];
    $totalReassign = 0;

    foreach ($ReassignMap as $tid => $rc) {
        $reDetails[] = "$tid:$rc";
        $totalReassign += $rc;
    }

    $summary["ReassignCount"]   = $totalReassign;
    $summary["ReassignDetails"] = $reDetails;


    // -------------------------------------------
    // 4️⃣ MERGE NORMAL KPI + REASSIGN KPI
    // -------------------------------------------
    foreach ($rows as $row) {

        $within = (int)$row['Within1Hour'];
        $after  = (int)$row['After1Hour'];

        $normalTotal = $within + $after;

        // **Add reassignment tickets inside within performance**
        $newWithin = $within + $totalReassign;

        // New total is still original total (not inflated)
        $summary['TotalTickets'] = $normalTotal;
        $summary['Within1Hour']  = $newWithin;
        $summary['After1Hour']   = ($after - $totalReassign < 0 ? 0 : $after - $totalReassign);

        $summary['Performance%'] = $normalTotal > 0 
            ? round(($newWithin / $normalTotal) * 100, 2) 
            : 0;
    }


    // -------------------------------------------
    // 5️⃣ RETURN RESPONSE
    // -------------------------------------------
    $result['success'] = true;
    $result['data']    = [$summary];
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

            $summary = ["TotalTickets"=>0,"Within48Hour"=>0,"After48Hour"=>0,"Pending"=>0,"NotApprovedIn48Hour"=>0,"Performance%"=>0,"AmcTickets"=>0];

            foreach ($rows as $row) {
                        if($row['TicketType']==='AMC'){ $summary['AmcTickets']++; continue; }

                        $summary['TotalTickets']++;
                        $assignedAt = $row['AssignedAt'];
                        $approvedAt = $row['ApprovedAt'];

                        if(!empty($assignedAt) && !empty($approvedAt)){
                            $diffMin = (strtotime($approvedAt)-strtotime($assignedAt))/60;
                            if($diffMin <= 2880) $summary['Within48Hour']++;
                            else $summary['After48Hour']++;
                        } elseif(!empty($assignedAt) && empty($approvedAt)){
                            $summary['Pending']++;
                            $diffMin = (time()-strtotime($assignedAt))/60;
                            if($diffMin >= 2880) $summary['NotApprovedIn48Hour']++;
                        }
                    }


            $total = $summary['TotalTickets'];
            $summary['Performance%'] = $total>0 ? round(($summary['Within48Hour']/$total)*100,2) : 0;

            $result['success']=true;
            $result['data']=[$summary];
            break;

        // ---------------- CLOSED KPI (NEW SLA LOGIC) ---------------- //
        case 'closed':
            // --- Calculate standard target based on days ---
            $daysInRange = (new DateTime($dateFrom))->diff(new DateTime($dateTo))->days + 1;
            $standardTarget = $daysInRange * 5; // assuming 5 tickets/day
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

            // --- Fetch closed tickets ---
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
            $closedTickets = count($rows);
            $closedWithin24 = 0;
           foreach ($rows as $row) {
                $quoteStr = $row['QuoteApprovedAt'] ?? null;
                $closeStr = $row['ClosedAt'] ?? null;

                if (!empty($quoteStr) && !empty($closeStr)) {
                    $quote = strtotime($quoteStr);
                    $close = strtotime($closeStr);

                    if ($quote !== false && $close !== false && ($close - $quote) <= 86400) {
                        $closedWithin24++;
                    }
                }
            }

            $performance = ($closingTarget > 0) ? round(($closedTickets/$closingTarget)*100,2) : 0;
            $result['success'] = true;
            $result['data'] = [[
                "TotalClosed"      => $closedTickets,
                "ClosedWithin24"   => $closedWithin24,
                "ClosedAfter24"    => $closedTickets - $closedWithin24,
                "ClosingTarget"    => $closingTarget,
                "Performance%"     => $performance
            ]];
            break;

        // ---------------- ATTENDANCE KPI ---------------- //
       case 'attendance':

    /* ------------------ 1️⃣ Fetch Public Holidays ------------------ */
    $sqlHolidays = "
        SELECT HolidaysDate 
        FROM listofholidays 
        WHERE IsActive = 1
        AND HolidaysDate BETWEEN '$dateFrom' AND '$dateTo'
    ";

    $holidayRows = $core->_getSQLRecords($conn, $sqlHolidays);
    $holidays = [];

    if (!empty($holidayRows)) {
        foreach ($holidayRows as $h) {
            $holidays[] = $h['HolidaysDate'];
        }
    }

    /* ------------------ 2️⃣ Calculate Working Days ------------------ */
    $start  = new DateTime($dateFrom);
    $end    = new DateTime($dateTo);
    $end->modify('+1 day'); // include end date

    $workingDays = 0;
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    foreach ($period as $day) {
        $dayStr = $day->format('Y-m-d');

        // Exclude Sundays (7) and public holidays
        if ($day->format('N') != 7 && !in_array($dayStr, $holidays)) {
            $workingDays++;
        }
    }

    /* ------------------ 3️⃣ Count Present Days ------------------ */
    $sql = "
        SELECT COUNT(DISTINCT DATE(RecordDate)) AS PresentDays
        FROM employee_attendance
        WHERE EmployeeID = $employeeId
        AND DATE(RecordDate) BETWEEN '$dateFrom' AND '$dateTo'
    ";

    $row = $core->_getSQLDetails($conn, $sql);
    $presentDays = $row['PresentDays'] ?? 0;

    /* ------------------ 4️⃣ Calculate Performance ------------------ */
    $performance = ($workingDays > 0)
        ? round(($presentDays / $workingDays) * 100, 2)
        : 0;

    /* ------------------ 5️⃣ Response ------------------ */
    $result['success'] = true;
    $result['data'] = [[
        "WorkingDays"      => $workingDays,
        "PresentDays"      => $presentDays,
        "AbsentDays"       => max(0, $workingDays - $presentDays),
        "Performance%"     => $performance,
        "HolidaysExcluded" => $holidays
    ]];

    break;

    }

    return $result;
}

/* ================= SINGLE SOURCE OF TRUTH ================= */
/* ✅ SAME LOGIC AS WHATSAPP — ZERO CHANGE */
function calculateEmployeeKPI($employee, $date_from, $date_to, $conn, $core) {

    $emp = $core->_getSQLDetails($conn,"
        SELECT Designation, InHandSalary
        FROM employees
        WHERE ID = $employee
    ");

    $designation = $emp['Designation'] ?? '';
    $salary      = floatval($emp['InHandSalary'] ?? 0);

    $executive = isExecutiveDesignation($designation);

    if ($executive) {

        $attendance = fetchKPI($employee,$date_from,$date_to,'attendance',$conn,$core);
        $attendance_pct = floatval($attendance['data'][0]['Performance%'] ?? 0);

        $assignment_pct = 0;
        $closed_pct     = 0;
        $overall        = $attendance_pct;

    } else {

        $assign     = fetchKPI($employee,$date_from,$date_to,'assignment',$conn,$core);
        $closed     = fetchKPI($employee,$date_from,$date_to,'closed',$conn,$core);
        $attendance = fetchKPI($employee,$date_from,$date_to,'attendance',$conn,$core);

        $a = $assign['data'][0] ?? [];
        $c = $closed['data'][0] ?? [];
        $at = $attendance['data'][0] ?? [];

        $hasAssignment = ($a['TotalTickets'] ?? 0) > 0;
        $hasClosed     = ($c['ClosingTarget'] ?? 0) > 0;

        $assignment_pct = normalizeKPI($a['Performance%'] ?? 0, $hasAssignment);
        $closed_pct     = normalizeKPI($c['Performance%'] ?? 0, $hasClosed);
        $attendance_pct = floatval($at['Performance%'] ?? 0);

        $overall = avg([$assignment_pct,$closed_pct,$attendance_pct]);
    }

    $payable = ($overall < 90) ? ($overall / 100) * $salary : $salary;

    return [
        'assignment' => $assignment_pct ?? 0,
        'closed'     => $closed_pct ?? 0,
        'attendance' => $attendance_pct ?? 0,
        'overall'    => round($overall,2),
        'salary'     => $salary,
        'payable'    => round($payable,2)
    ];
}

/* ================= FETCH EMPLOYEES ================= */
$where = ($employee_id > 0) ? " AND e.ID=$employee_id " : "";

$employees = $core->_getSQLRecords($conn,"
    SELECT ID, Name, EmployeeNumber, Designation, City, State, InHandSalary
    FROM employees e
    WHERE e.IsActive=1
      AND e.ID NOT IN (SELECT EmployeeID FROM user_roles WHERE Role='Vendor')
      $where
    ORDER BY Name
");

/* ================= CSV OUTPUT ================= */
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=employee_kpi_'.date('Ymd').'.csv');

$output = fopen('php://output','w');

fputcsv($output, [
    'Employee Name','Employee Number','Designation','City','State',
    'Assignment %','Closed %','Attendance %',
    'Overall KPI %','In-Hand Salary','Payable Salary'
]);

foreach ($employees as $emp) {

    $kpi = calculateEmployeeKPI(
        $emp['ID'],
        $date_from,
        $date_to,
        $conn,
        $core
    );

    fputcsv($output, [
        $emp['Name'],
        $emp['EmployeeNumber'],
        $emp['Designation'],
        $emp['City'],
        $emp['State'],
        safeRound($kpi['assignment']),
        safeRound($kpi['closed']),
        safeRound($kpi['attendance']),
        safeRound($kpi['overall']),
        safeRound($kpi['salary']),
        safeRound($kpi['payable'])
    ]);
}

fclose($output);
exit;
