<?php
require_once('../../controllers/common_controllers.php');
include("../../includes/autoloader.inc.php");

$conn = _connectodb();
$core = new Core();
$core->setTimeZone();

header("Content-Type: application/json");
$response = ["success" => false, "data" => []];

if (isset($_GET['employee'], $_GET['date_from'], $_GET['date_to'])) {
    $employeeID = intval($_GET['employee']);
    $dateFrom   = mysqli_real_escape_string($conn, $_GET['date_from']);
    $dateTo     = mysqli_real_escape_string($conn, $_GET['date_to']);

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

    // --- 1️⃣ Fetch Public Holidays within Date Range (of same year & month range) ---
    $sqlHolidays = "
        SELECT HolidaysDate 
        FROM listofholidays 
        WHERE IsActive = 1
        AND YEAR(HolidaysDate) = YEAR(CURDATE())
        AND MONTH(HolidaysDate) BETWEEN MONTH('$dateFrom') AND MONTH('$dateTo')
        AND HolidaysDate BETWEEN '$dateFrom' AND '$dateTo'
    ";
    $resultHolidays = mysqli_query($conn, $sqlHolidays);
    $holidays = [];
    if ($resultHolidays && mysqli_num_rows($resultHolidays) > 0) {
        while ($row = mysqli_fetch_assoc($resultHolidays)) {
            $holidays[] = $row['HolidaysDate'];
        }
    }

    // --- 2️⃣ Calculate Working Days (Exclude Sundays + Public Holidays) ---
    $start  = new DateTime($dateFrom);
    $end    = new DateTime($dateTo);
    $end->modify('+1 day'); // include end date
    $workingDays = 0;
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    foreach ($period as $day) {
        $dayStr = $day->format('Y-m-d');
        if ($day->format('N') != 7 && !in_array($dayStr, $holidays)) { // exclude Sundays & holidays
            $workingDays++;
        }
    }

    // --- 3️⃣ Count Present Days ---
    $sql = "
        SELECT COUNT(DISTINCT DATE(RecordDate)) as PresentDays
        FROM employee_attendance
        WHERE EmployeeID = '$employeeID'
        AND DATE(RecordDate) BETWEEN '$dateFrom' AND '$dateTo'
    ";
    $row = $core->_getSQLDetails($conn, $sql);
    $presentDays = $row['PresentDays'] ?? 0;

    // --- 4️⃣ Calculate Performance ---
    $performance = ($workingDays > 0) ? round(($presentDays / $workingDays) * 100, 2) : 0;

    $response = [
        "success" => true,
        "data" => [
            "EmployeeID"       => $employeeID,
            "DateRange"        => "$dateFrom to $dateTo",
            "WorkingDays"      => $workingDays,
            "PresentDays"      => $presentDays,
            "AbsentDays"       => max(0, $workingDays - $presentDays),
            "Performance%"     => $performance,
            "HolidaysExcluded" => $holidays
        ]
    ];
}

echo json_encode($response);
