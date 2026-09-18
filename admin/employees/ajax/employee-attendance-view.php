<?php
if (isset($_POST)) {
    require_once('../../includes/autoloader.inc.php');
    require_once('../../attendance-list/controller/attendance_controller.php');

    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $data = $_POST;
    $currentYear = $data['s_year'];
    $currentMonth = $data['s_month'];
    $ID = (int) $data['EmployeeID'];

    $month_data = fetchEmployeeAttendanceMonthData($conn, $ID, $currentYear, $currentMonth);
    $rendered = renderEmployeeAttendanceReportRows($month_data['calendar_rows'], $month_data['attendance_data']);

    echo $rendered['rows_html'];
    echo renderEmployeeAttendanceSummaryFooterHtml($rendered['summary']);
}

?>
