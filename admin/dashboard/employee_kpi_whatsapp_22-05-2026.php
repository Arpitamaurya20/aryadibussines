<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
include('../includes/autoloader.inc.php');
include('../controllers/common_controllers.php');

$conn = _connectodb();
$core = new Core();
$core->setTimeZone();


function normalizeKPI($percentage, $hasData) {
    return ($hasData === false) ? 100 : floatval($percentage);
}


// ==================== CONFIG ==================== //
$role           = $_GET['Role'] ?? 'Employee';
$employee       = $_GET['Employee'] ?? -1;
$date_from      = $_GET['Datefrom'] ?? date('Y-m-01');
$date_to        = $_GET['Dateto'] ?? date('Y-m-d');
$number =      $_GET['EmployeeNumber'] ??'8948975967';

// $number = preg_replace('/\D/', '', $number);
// if (substr($number, 0, 2) !== '91') {
//     $whatsappNumber = '91' . $number;
// } else {
//     $whatsappNumber = $number;
// }

$whatsappNumber = $number;
// ============================================== //

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


// ----------------- GET EMPLOYEE DESIGNATION ----------------- //
$employeeData = $core->_getSQLDetails($conn, "SELECT Designation FROM employees WHERE ID=$employee");
$designation = $employeeData['Designation'] ?? '';

// ----------------- HELPER: Check if Executive ----------------- //
function isExecutiveDesignation($designation) {
    if (empty($designation)) return false;
    $keywords = [
        'executive',
        'sr. executive',
        'senior helpdesk',
        'purchase executive',
        'web developer',
        'app developer',
        'mis',
        'hr',
        'finance',
        'projectmanager',
        'asst.manager',
        'accounts',
        'billing head'
    ];
    $designation = strtolower($designation);
    foreach ($keywords as $word) {
        if (strpos($designation, $word) !== false) return true;
    }
    return false;
}

$executive = isExecutiveDesignation($designation);



// ----------------- FETCH ALL KPI ----------------- //
// ----------------- FETCH KPI BASED ON ROLE ----------------- //
function avg($arr){ return count($arr)? array_sum($arr)/count($arr) : 0; }

if ($executive) {
    $attendance = fetchKPI($employee, $date_from, $date_to, 'attendance', $conn, $core);
    $assignment_pct = 0;
    $quotation_pct  = 0;
    $closed_pct     = 0;
    $attendance_pct = (!empty($attendance['data'])) ? floatval($attendance['data'][0]['Performance%']) : 0;
    $percentages = [$attendance_pct];
    $overall = avg($percentages);
} else {
    $assign     = fetchKPI($employee,$date_from,$date_to,'assignment',$conn,$core);
    $quote      = fetchKPI($employee,$date_from,$date_to,'quotation',$conn,$core);
    $closed     = fetchKPI($employee,$date_from,$date_to,'closed',$conn,$core);
    $attendance = fetchKPI($employee,$date_from,$date_to,'attendance',$conn,$core);

    // $assignment_pct = (!empty($assign['data'])) ? floatval($assign['data'][0]['Performance%']) : 0;
    // $quotation_pct  = (!empty($quote['data'])) ? floatval($quote['data'][0]['Performance%']) : 0;
    // $closed_pct     = (!empty($closed['data'])) ? floatval($closed['data'][0]['Performance%']) : 0;
    // $attendance_pct = (!empty($attendance['data'])) ? floatval($attendance['data'][0]['Performance%']) : 0;
    // $percentages = [$assignment_pct,$closed_pct,$attendance_pct];
    // $overall = avg($percentages);


    $assignmentData = $assign['data'][0] ?? [];
$quotationData  = $quote['data'][0] ?? [];
$closedData     = $closed['data'][0] ?? [];
$attendanceData = $attendance['data'][0] ?? [];

/* ---- Detect data availability ---- */
$hasAssignment = ($assignmentData['TotalTickets'] ?? 0) > 0;
$hasQuotation  = ($quotationData['TotalTickets'] ?? 0) > 0;
$hasClosed     = ($closedData['ClosingTarget'] ?? 0) > 0;

/* ---- Normalize KPI ---- */
$assignment_pct = normalizeKPI($assignmentData['Performance%'] ?? 0, $hasAssignment);
$quotation_pct  = normalizeKPI($quotationData['Performance%'] ?? 0, $hasQuotation);
$closed_pct     = normalizeKPI($closedData['Performance%'] ?? 0, $hasClosed);

/* Attendance is ALWAYS real */
$attendance_pct = (!empty($attendance['data'])) ? floatval($attendance['data'][0]['Performance%']) : 0;

/* ---- Final Overall KPI ---- */
$percentages = [$assignment_pct, $closed_pct, $attendance_pct];
$overall = avg($percentages);

}

// ----------------- SALARY CALCULATION ----------------- //
$salaryData = $core->_getSQLDetails($conn, "
    SELECT Basic, DA, HRA, Bonus, HealthInsurance, Others,ConvenienceAllowance,InHandSalary
    FROM employees
    WHERE ID = $employee
");
$fullSalary = 0;
//  $fullSalary = floatval($salaryData['Basic'])
//                 + floatval($salaryData['DA'])
//                 + floatval($salaryData['HRA'])
//                 + floatval($salaryData['Bonus'])
//                 + floatval($salaryData['HealthInsurance'])
//                 + floatval($salaryData['Others'])
//                 + floatval($salaryData['ConvenienceAllowance']);
if ($salaryData) {
    $fullSalary = floatval($salaryData['InHandSalary']);
}
$payableSalary = ($overall < 90) ? ($overall / 100) * $fullSalary : $fullSalary;
$salaryPct = ($fullSalary > 0) ? min(($payableSalary / $fullSalary) * 100, 100) : 0;

$overall = round($overall,2);
$salaryPct = round($salaryPct,2);

// ----------------- GENERATE CHART ----------------- //
if ($executive) {
    $chartData = [
        "type" => "doughnut",
        "data" => [
            "labels" => ["Attendance", "Salary %"],
            "datasets" => [[
                "label" => "KPI %",
                "data" => [$attendance_pct, $salaryPct],
                "backgroundColor" => ["#00c853","#f44336"],
                "borderWidth" => 2
            ]]
        ]
    ];
} else {
    $chartData = [
        "type" => "doughnut",
        "data" => [
            "labels" => ["Assignment", "Quotation", "Closed", "Attendance", "Overall KPI", "Salary %"],
            "datasets" => [[
                "label" => "KPI %",
                "data" => [$assignment_pct, $quotation_pct, $closed_pct, $attendance_pct, $overall, $salaryPct],
                "backgroundColor" => ["#003f88","#027dc1","#2196f3","#00c853","#ff9800","#f44336"],
                "borderWidth" => 2
            ]]
        ]
    ];
}
$chartUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($chartData));





// ----------------- PREPARE MESSAGE ----------------- //
    $messageText  = "---------------------------------------\n";
    $messageText .= "Your Performance Of this Month Till Now\n";
    $messageText .= "Date Range: $date_from → $date_to\n";
    $messageText .= "---------------------------------------\n";
    $messageText .= "Overall KPI    : ".number_format($overall,1)."%\n";
    $messageText .= "Payable Salary : ₹".number_format($payableSalary,2)."\n";
    $messageText .= "---------------------------------------\n";

// ----------------- SEND WHATSAPP MESSAGE WITH CHART ----------------- //
// function sendWhatsAppChartNew($phonenumber, $chartUrl, $messageText)
// {
//     $params = [
//         'token' => '48y5d4l930we57ya',  // Your UltraMsg token
//         'to'    => $phonenumber,
//         'body'  => $messageText,
//         'image' => $chartUrl,
//         'caption' => $messageText
//     ];

//     $curl = curl_init();
//     curl_setopt_array($curl, [
//         CURLOPT_URL => "https://api.ultramsg.com/instance32275/messages/image",
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_TIMEOUT => 30,
//         CURLOPT_SSL_VERIFYHOST => 0,
//         CURLOPT_SSL_VERIFYPEER => 0,
//         CURLOPT_POST => true,
//         CURLOPT_POSTFIELDS => http_build_query($params),
//         CURLOPT_HTTPHEADER => ["content-type: application/x-www-form-urlencoded"],
//     ]);

//     $response = curl_exec($curl);
//     $err = curl_error($curl);
//     curl_close($curl);

//     if ($err) {
//         echo "cURL Error #: $err";
//     } else {
//         echo "Chart sent successfully: $response";
//     }
// }


function sendInteraktKPI($whatsappNumber, $chartUrl, $date_from, $date_to, $overall, $payableSalary, $templateName = "kpi_v1") {
    // Body values for template placeholders {{1}}, {{2}}, etc.
    $bodyValues = [
        $date_from,      
        $date_to,       
        number_format($overall,2),       
        number_format($payableSalary,2)
    ];

    $payload = [
        "countryCode" => "+91",
        "phoneNumber" => $whatsappNumber,
        "type" => "Template",
        "template" => [
            "name" => $templateName,
            "languageCode" => "en",
            "bodyValues" => $bodyValues,
            "headerValues" => [$chartUrl] // chart image in header
        ]
    ];

    $curl = curl_init('https://api.interakt.ai/v1/public/message/');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic TVZPai1Sb3VRbE9fN3ltYWxNOTlRTWxFd0kwckt2NmFBak4zSUNEQnRaczo=',
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $err = 'Curl error: ' . curl_error($curl);
        curl_close($curl);
        return ['status' => 'error', 'message' => $err];
    }
    curl_close($curl);

    // Log for debugging
    file_put_contents('interakt_response.log', date('Y-m-d H:i:s').' - '.$response.PHP_EOL, FILE_APPEND);

    return json_decode($response, true);
}

// ------------------ SEND KPI MESSAGE ------------------ //
$response = sendInteraktKPI($whatsappNumber, $chartUrl, $date_from, $date_to, $overall, $payableSalary);
print_r($response);
?>