<?php
session_start();

// Include only the necessary files for database connection
include('../includes/autoloader.inc.php');
include('../controllers/common_controllers.php');

// Check if user is logged in
$UserType = SessionCheck();
if (!$UserType) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Not authenticated']);
    exit;
}

$conn = _connectodb();
$core = new Core();
$core->setTimeZone();

// Only process AJAX requests
if (!isset($_GET['ajax']) || $_GET['ajax'] != '1') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid request']);
    exit;
}

header('Content-Type: application/json');

$employeeId = intval($_GET['employee'] ?? 0);
$period     = $_GET['period'] ?? 'month';
$start      = $_GET['start']  ?? null;
$end        = $_GET['end']    ?? null;

// Guard: if no employee chosen, return empty
if ($employeeId <= 0) {
    echo json_encode(['ok'=>false,'msg'=>'Select employee','data'=>[]]);
    exit;
}

function period_range($period, $customStart=null, $customEnd=null) {
    $today = new DateTime('today');
    switch (strtolower($period)) {
        case 'day':
        case 'daily':
            $start = clone $today;
            $end   = clone $today;
            break;
        case 'week':
        case 'weekly':
            // Monday–Sunday
            $start = (clone $today)->modify('monday this week');
            $end   = (clone $start)->modify('sunday this week');
            break;
        case 'month':
        case 'monthly':
            $start = new DateTime(date('Y-m-01'));
            $end   = new DateTime(date('Y-m-t'));
            break;
        case 'year':
        case 'yearly':
            $start = new DateTime(date('Y-01-01'));
            $end   = new DateTime(date('Y-12-31'));
            break;
        case 'custom':
            $start = new DateTime($customStart ?: date('Y-m-d'));
            $end   = new DateTime($customEnd   ?: date('Y-m-d'));
            break;
        default:
            $start = new DateTime(date('Y-m-01'));
            $end   = new DateTime(date('Y-m-t'));
    }
    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}

function days_between($start, $end) {
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    return (int)$d1->diff($d2)->format("%a") + 1;
}

[$startDate, $endDate] = period_range($period, $start, $end);

// --- 3.1 BASE SALARY ---
$sqlSalary = "
    SELECT 
        COALESCE(Basic,0)+COALESCE(DA,0)+COALESCE(HRA,0)+COALESCE(Bonus,0)+
        COALESCE(HealthInsurance,0)+COALESCE(Others,0) AS base_salary,
        Name
    FROM employees
    WHERE IsActive=1 AND ID=?";
$stmt = $conn->prepare($sqlSalary);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$baseRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$baseSalary = floatval($baseRow['base_salary'] ?? 0);
$employeeName = $baseRow['Name'] ?? 'Employee';

// --- 3.2 CORPORATE TICKETS KPI CALC ---
$sqlCorp = "
    SELECT
        COUNT(*) AS total_corp,
        SUM(
            CASE 
                WHEN ComplianceAssignedTime IS NOT NULL 
                AND TIMESTAMPDIFF(
                    HOUR, 
                    STR_TO_DATE(CONCAT(CreatedDate,' ',COALESCE(CreatedTime,'00:00:00')),'%Y-%m-%d %H:%i:%s'),
                    ComplianceAssignedTime
                ) <= 2 THEN 1 
                ELSE 0 
            END
        ) AS assign_ok,
        SUM(
            CASE 
                WHEN QuotationUpload IS NOT NULL 
                AND TIMESTAMPDIFF(DAY, CreatedDate, QuotationUpload) <= 2 THEN 1 
                ELSE 0 
            END
        ) AS quote_ok,
        SUM(
            CASE 
                WHEN CloseDate IS NOT NULL 
                AND QuotationUpload IS NOT NULL
                AND TIMESTAMPDIFF(DAY, QuotationUpload, CloseDate) <= 2 THEN 1 
                ELSE 0 
            END
        ) AS resolve_ok,
        SUM(
            CASE 
                WHEN Status='Closed' AND CloseDate BETWEEN ? AND ? THEN 1 
                ELSE 0 
            END
        ) AS closed_corp
    FROM corporate_tickets
    WHERE IsActive=1
      AND (AssignedTo=? OR Technician=?)
      AND CreatedDate BETWEEN ? AND ?";

$stmt = $conn->prepare($sqlCorp);
$stmt->bind_param("ssiiss", $endDate, $endDate, $employeeId, $employeeId, $startDate, $endDate);
$stmt->execute();
$corp = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- 3.3 PPM TICKETS KPI CALC ---
$sqlPPM = "
    SELECT
        COUNT(*) AS total_ppm,
        SUM(
            CASE 
                WHEN Status='Closed' AND CloseDate BETWEEN ? AND ? THEN 1 
                ELSE 0 
            END
        ) AS closed_ppm,
        SUM(
            CASE 
                WHEN CloseDate IS NOT NULL 
                AND TIMESTAMPDIFF(DAY, CreatedDate, CloseDate) <= 2 THEN 1 
                ELSE 0 
            END
        ) AS resolve_ok
    FROM ppm_tickets
    WHERE IsActive=1
    AND AssignedTo=?
    AND CreatedDate BETWEEN ? AND ?";

$stmt = $conn->prepare($sqlPPM);
$stmt->bind_param("ssiss", $startDate, $endDate, $employeeId, $startDate, $endDate);
$stmt->execute();
$ppm = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Totals across both ticket types
$totalTickets = intval($corp['total_corp']) + intval($ppm['total_ppm']);
$ticketsClosed = intval($corp['closed_corp']) + intval($ppm['closed_ppm']);

// Rates (guard divide-by-zero)
$assignmentCompliance = intval($corp['total_corp']) > 0 ? round((intval($corp['assign_ok']) / max(1,intval($corp['total_corp']))) * 100, 2) : 0;
$quotationRate        = intval($corp['total_corp']) > 0 ? round((intval($corp['quote_ok']) / max(1,intval($corp['total_corp']))) * 100, 2) : 0;
$resolutionSLA_numer  = intval($corp['resolve_ok']) + intval($ppm['resolve_ok']);
$resolutionRate       = $totalTickets > 0 ? round(($resolutionSLA_numer / $totalTickets) * 100, 2) : 0;

// --- 3.4 PRODUCTIVITY ---
$periodDays   = days_between($startDate, $endDate);
$daysInMonth  = (int)date('t', strtotime($startDate));
$monthlyTargetScaled = max(1, round(90 * ($periodDays / max(1,$daysInMonth))));

$sqlAssignedPerDay = "
    SELECT d.workday,
           SUM(d.assigned) AS assigned_total,
           SUM(d.closed) AS closed_total
    FROM (
         SELECT CreatedDate AS workday, COUNT(*) AS assigned, 0 AS closed
         FROM corporate_tickets
         WHERE (AssignedTo=? OR Technician=?) AND CreatedDate BETWEEN ? AND ? AND IsActive=1
         GROUP BY CreatedDate
         UNION ALL
         SELECT CloseDate AS workday, 0 AS assigned, COUNT(*) AS closed
         FROM corporate_tickets
         WHERE Technician=? AND Status='Closed' AND CloseDate BETWEEN ? AND ? AND IsActive=1
         GROUP BY CloseDate
         UNION ALL
         SELECT CreatedDate AS workday, COUNT(*) AS assigned, 0 AS closed
         FROM ppm_tickets
         WHERE AssignedTo=? AND CreatedDate BETWEEN ? AND ? AND IsActive=1
         GROUP BY CreatedDate
         UNION ALL
         SELECT CloseDate AS workday, 0 AS assigned, COUNT(*) AS closed
         FROM ppm_tickets
         WHERE AssignedTo=? AND Status='Closed' AND CloseDate BETWEEN ? AND ? AND IsActive=1
         GROUP BY CloseDate
    ) d
    GROUP BY d.workday";

$stmt = $conn->prepare($sqlAssignedPerDay);
$stmt->bind_param(
    "iississississ",
    $employeeId, $employeeId, $startDate, $endDate,
    $employeeId, $startDate, $endDate,
    $employeeId, $startDate, $endDate,
    $employeeId, $startDate, $endDate
);

$stmt->execute();
$res = $stmt->get_result();

$daysRuleOk = 0; $daysRuleTotal=0;
while($r=$res->fetch_assoc()){
    if (!$r['workday']) continue;
    $assigned = (int)$r['assigned_total'];
    $closed   = (int)$r['closed_total'];
    if ($assigned > 5) {
        $daysRuleTotal++;
        if ($closed >= 5) $daysRuleOk++;
    }
}
$stmt->close();
$dailyRuleRate = $daysRuleTotal>0 ? round(($daysRuleOk/$daysRuleTotal)*100,2) : 100;

$productivityScore = min(100, round(($ticketsClosed / max(1,$monthlyTargetScaled))*100, 2));
$productivityFinal = max($productivityScore, $dailyRuleRate);

// --- 3.5 ATTENDANCE ---
$sqlAttendance = "
    SELECT 
        COUNT(DISTINCT RecordDate) AS present_days
    FROM employee_attendance
    WHERE EmployeeID=? AND RecordDate BETWEEN ? AND ?";
$stmt = $conn->prepare($sqlAttendance);
$stmt->bind_param("iss", $employeeId, $startDate, $endDate);
$stmt->execute();
$att = $stmt->get_result()->fetch_assoc();
$stmt->close();

$presentDays = (int)($att['present_days'] ?? 0);
$totalDays   = $periodDays;
$attendanceRate = $totalDays>0 ? round(($presentDays/$totalDays)*100,2) : 0;

// --- 3.6 FINAL KPI CALCULATION ---
$wAssign = 0.25;   // 25%
$wQuote  = 0.15;   // 15%
$wRes    = 0.30;   // 30%
$wProd   = 0.20;   // 20%
$wAtt    = 0.10;   // 10%

$finalKPI = round(
    $assignmentCompliance*$wAssign +
    $quotationRate*$wQuote +
    $resolutionRate*$wRes +
    $productivityFinal*$wProd +
    $attendanceRate*$wAtt
,2);

// Salary rule: linear factor by KPI (cap 120%) + note when below 80%
$salaryFactor = max(0.0, min(1.2, $finalKPI/100));
$finalSalary  = round($baseSalary * $salaryFactor, 2);
$salaryNote   = ($finalKPI < 80) ? "Performance below 80% may negatively impact salary." : "";

echo json_encode([
    'ok'=>true,
    'employee'=>['id'=>$employeeId,'name'=>$employeeName],
    'period'=>['start'=>$startDate,'end'=>$endDate,'days'=>$periodDays],
    'kpi'=>[
        'assignment_compliance'=>$assignmentCompliance,
        'quotation_approval'=>$quotationRate,
        'resolution_sla'=>$resolutionRate,
        'productivity'=>$productivityFinal,
        'productivity_detail'=>[
            'closed'=>$ticketsClosed,
            'target_scaled'=>$monthlyTargetScaled,
            'monthly_score'=>$productivityScore,
            'daily_rule_rate'=>$dailyRuleRate
        ],
        'attendance'=>$attendanceRate,
        'final_kpi'=>$finalKPI
    ],
    'salary'=>[
        'base'=>$baseSalary,
        'factor'=>$salaryFactor,
        'final'=>$finalSalary,
        'note'=>$salaryNote
    ],
    'raw'=>[
        'total_tickets'=>$totalTickets,
        'closed_total'=>$ticketsClosed,
        'present_days'=>$presentDays,
        'days_rule_total'=>$daysRuleTotal,
        'days_rule_ok'=>$daysRuleOk
    ]
]);
exit;
?>