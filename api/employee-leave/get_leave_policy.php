<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$elm = new Employeeleavemgmt($conn);

elm_api_response(false, 'Leave policy.', [
    'data' => $elm->getPolicyForApi(),
    'policy' => $elm->getPolicyForApi(),
]);
