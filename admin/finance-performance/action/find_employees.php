<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_offices.php');
setTimeZone();

fp_run_action('search_employees', function ($conn, $actor) {
    $results = array();
    foreach (fp_employee_search($conn, fp_input_string($_GET, 'q')) as $employee) {
        $details = array_filter(array($employee['EmployeeNumber'], $employee['Designation'], $employee['City'] ?: $employee['State']), 'strlen');
        $results[] = array(
            'id'   => (int) $employee['ID'],
            'text' => $employee['Name'] . ($details ? ' (' . implode(', ', $details) . ')' : ''),
        );
    }
    return fp_result(true, 200, 'OK', array('data' => array('results' => $results)));
});
