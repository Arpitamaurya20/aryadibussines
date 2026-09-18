<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// ----------------- HELPER: Fetch KPI ----------------- //
function fetchKPI($employeeId, $dateFrom, $dateTo, $type, $conn, $core) {
    $result = ["success"=>false,"data"=>[]];

    switch($type){
        // ---------------- ASSIGNMENT KPI ---------------- //
    case 'assignment':

    // -------------------------------------------------------
    // 1️⃣ MAIN KPI QUERY (Raised → Assigned)
    // -------------------------------------------------------
    $sql = "
        SELECT 
            t1.AssignedTo AS CityLeadID,

            COUNT(DISTINCT t1.TicketID) AS TotalTickets,

            SUM(
                CASE 
                    WHEN TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(t1.CreatedDate,' ',t1.CreatedTime),
                        CONCAT(t2.CreatedDate,' ',t2.CreatedTime)
                    ) <= 60 THEN 1 ELSE 0
                END
            ) AS Within1Hour,

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


    // -------------------------------------------------------
    // 2️⃣ REASSIGNMENT COUNT (Assigned entries per Ticket)
    // -------------------------------------------------------
    $reassignSQL = "
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

    $reassignRows = $core->_getSQLRecords($conn, $reassignSQL);

    // Build ticket ⇒ reassignment count
    $ticketReassign = [];
    foreach ($reassignRows as $r) {
        $ticketId  = $r['TicketID'];
        $assignCnt = (int)$r['totalAssign'];

        // True reassignments = Assigned entries - 1
        $ticketReassign[$ticketId] = max(0, $assignCnt - 1);
    }

    // Total reassignment tickets
    $totalReassign = array_sum($ticketReassign);


    // -------------------------------------------------------
    // 3️⃣ PERFORMANCE CALCULATION
    // -------------------------------------------------------
    $summary = [
        "Performance%" => 0,
        "ReassignCount" => $totalReassign
    ];

    foreach ($rows as $row) {

        $within = (int)$row['Within1Hour'];
        $after  = (int)$row['After1Hour'];

        $total = $within + $after;

        // ⭐ Add reassigned tickets to within performance
        $newWithin = $within + $totalReassign;

        // Avoid negative After1Hour
        $newAfter = max(0, $after - $totalReassign);

        // Correct performance formula
        $summary['Performance%'] = $total > 0
            ? round(($newWithin / $total) * 100, 2)
            : 0;
    }


    // -------------------------------------------------------
    // 4️⃣ RETURN RESPONSE
    // -------------------------------------------------------
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
            $summary = ["Performance%"=>0];
            $within48 = 0; $total = 0;
            foreach ($rows as $r) {
                if ($r['TicketType']==='AMC') continue;
                $assignedAt = $r['AssignedAt'];
                $approvedAt = $r['ApprovedAt'];
                if(!empty($assignedAt) && !empty($approvedAt)){
                    $diffMin = (strtotime($approvedAt)-strtotime($assignedAt))/60;
                    if($diffMin <= 2880) $within48++;
                    $total++;
                }
            }
            $summary['Performance%'] = $total>0 ? round(($within48/$total)*100,2) : 0;
            $result['success']=true;
            $result['data']=[$summary];
            break;

        // ---------------- CLOSED KPI ---------------- //
        case 'closed':
            $sqlClosed = "
                SELECT 
                    MAX(CASE WHEN t1.Status='Quote Approved' THEN CONCAT(t1.CreatedDate,' ',t1.CreatedTime) END) AS QuoteApprovedAt,
                    MAX(CASE WHEN t2.Status='Closed' THEN CONCAT(t2.CreatedDate,' ',t2.CreatedTime) END) AS ClosedAt
                FROM corporate_ticket_status_history t0
                LEFT JOIN corporate_ticket_status_history t1 ON t0.TicketID = t1.TicketID AND t1.Status='Quote Approved'
                LEFT JOIN corporate_ticket_status_history t2 ON t0.TicketID = t2.TicketID AND t2.Status='Closed'
                WHERE t0.AssignedTo = $employeeId
                  AND t0.CreatedDate BETWEEN '$dateFrom' AND '$dateTo'
                GROUP BY t0.TicketID
            ";
            $rows = $core->_getSQLRecords($conn, $sqlClosed);
            $closed = count($rows);
            $result['success'] = true;
            $result['data'] = [["Performance%" => $closed>0 ? 100 : 0]];
            break;

        // ---------------- ATTENDANCE KPI ---------------- //
        case 'attendance':
            $start  = new DateTime($dateFrom);
            $end    = new DateTime($dateTo);
            $end->modify('+1 day');
            $workingDays=0;
            $period = new DatePeriod($start,new DateInterval('P1D'),$end);
            foreach($period as $day){ if($day->format('N')!=7) $workingDays++; }

            $sql="SELECT COUNT(DISTINCT DATE(RecordDate)) as PresentDays
                  FROM employee_attendance
                  WHERE EmployeeID=$employeeId AND DATE(RecordDate) BETWEEN '$dateFrom' AND '$dateTo'";
            $row = $core->_getSQLDetails($conn,$sql);
            $presentDays = $row['PresentDays'] ?? 0;
            $performance = $workingDays>0 ? round(($presentDays/$workingDays)*100,2):0;
            $result['success']=true;
            $result['data']=[["Performance%"=>$performance]];
            break;
    }

    return $result;
}

// ----------------- HELPER FUNCTIONS ----------------- //
function isExecutiveDesignation($designation) {
    $keywords = ['executive','developer','hr','finance','mis','projectmanager'];
    $designation = strtolower($designation);
    foreach ($keywords as $word) {
        if (strpos($designation, $word) !== false) return true;
    }
    return false;
}
function avg($arr){ return count($arr)? array_sum($arr)/count($arr) : 0; }

// ==================== FETCH ALL EMPLOYEES ==================== //
$employees = $core->_getSQLRecords($conn, "SELECT ID, Name, Designation, Basic, DA, HRA, Bonus, HealthInsurance, Others, ConvenienceAllowance, InHandSalary FROM employees WHERE IsActive=1");

// ==================== PREPARE CSV ==================== //
$filename = "All_Employees_KPI_Report_{$date_from}_to_{$date_to}.csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
$output = fopen('php://output', 'w');

fputcsv($output, ['Employee KPI Report']);
fputcsv($output, []);
fputcsv($output, ['Date Range', "$date_from to $date_to"]);
fputcsv($output, []);
fputcsv($output, ['Employee Name','Designation','Assignment %','Quotation %','Closed %','Attendance %','Overall KPI %','Payable Salary (₹)','Salary %']);

foreach ($employees as $emp) {
    $id = $emp['ID'];
    $designation = $emp['Designation'];
    $executive = isExecutiveDesignation($designation);

    if ($executive) {
        $attendance = fetchKPI($id,$date_from,$date_to,'attendance',$conn,$core);
        $attendance_pct = floatval($attendance['data'][0]['Performance%'] ?? 0);
        $assignment_pct = $quotation_pct = $closed_pct = 0;
        $overall = $attendance_pct;
    } else {
        $assign = fetchKPI($id,$date_from,$date_to,'assignment',$conn,$core);
        $quote  = fetchKPI($id,$date_from,$date_to,'quotation',$conn,$core);
        $closed = fetchKPI($id,$date_from,$date_to,'closed',$conn,$core);
        $attend = fetchKPI($id,$date_from,$date_to,'attendance',$conn,$core);
        $assignment_pct = floatval($assign['data'][0]['Performance%'] ?? 0);
        $quotation_pct  = floatval($quote['data'][0]['Performance%'] ?? 0);
        $closed_pct     = floatval($closed['data'][0]['Performance%'] ?? 0);
        $attendance_pct = floatval($attend['data'][0]['Performance%'] ?? 0);
        $overall = avg([$assignment_pct,$closed_pct,$attendance_pct]);
    }

    // $fullSalary = floatval($emp['InHandSalary']) + floatval($emp['DA']) + floatval($emp['HRA']) +
    //               floatval($emp['Bonus']) + floatval($emp['HealthInsurance']) +
    //               floatval($emp['Others']) + floatval($emp['ConvenienceAllowance']);

    $fullSalary = floatval($emp['InHandSalary']);

    $payableSalary = ($overall < 90) ? ($overall / 100) * $fullSalary : $fullSalary;
    $salaryPct = ($fullSalary > 0) ? round(($payableSalary / $fullSalary) * 100, 2) : 0;

    fputcsv($output, [
        $emp['Name'],
        $designation,
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
