<?php
/** @deprecated Use main API: /api/download_employee_salary_slip.php */
$qs = $_SERVER['QUERY_STRING'] ?? '';
$target = '../../api/download_employee_salary_slip.php' . ($qs !== '' ? '?' . $qs : '');
header('Location: ' . $target, true, 302);
exit;
