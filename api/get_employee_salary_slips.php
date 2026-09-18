<?php
/**
 * Mobile / app API — list HRMS salary slips or get secure download URL.
 * Location: /api/ (main project api folder, not hrms/api)
 *
 * POST JSON:
 *   EmployeeID (required)
 *   slip_id (optional) — single slip download link
 *   company_id (optional) — letterhead company
 *   limit (optional, default 24, max 60)
 */
require_once __DIR__ . '/common_api_header.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/inc/payslip_bootstrap.php';

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (!is_array($data)) {
	$data = [];
}

$response = ['error' => true, 'message' => 'Missing required fields'];

$employeeId = isset($data['EmployeeID']) ? intval($data['EmployeeID']) : 0;
if ($employeeId <= 0) {
	$response['message'] = 'EmployeeID is required';
	echo json_encode($response);
	exit;
}

$conn = api_payslip_conn();
$emp = mysqli_query($conn, "SELECT ID, Name, EmployeeNumber FROM employees WHERE ID = $employeeId AND IFNULL(IsActive,1) = 1 LIMIT 1");
$empRow = $emp ? mysqli_fetch_assoc($emp) : null;
if (!$empRow) {
	$response['message'] = 'Employee not found';
	echo json_encode($response);
	exit;
}

$salary = api_payslip_service();
$slipId = isset($data['slip_id']) ? intval($data['slip_id']) : 0;
$companyId = isset($data['company_id']) ? intval($data['company_id']) : 0;
$limit = isset($data['limit']) ? intval($data['limit']) : 24;
if ($limit <= 0 || $limit > 60) {
	$limit = 24;
}
if ($companyId <= 0) {
	$companyId = $salary->getDefaultQuoteCompanyId();
}

if ($slipId > 0) {
	if (!$salary->slipBelongsToEmployee($slipId, $employeeId)) {
		$response['message'] = 'Salary slip not found for this employee';
		echo json_encode($response);
		exit;
	}
	$downloadUrl = $salary->buildSlipShareDownloadUrl($slipId, $companyId);
	if ($downloadUrl === null) {
		$response['message'] = 'No active company configured for payslip';
		echo json_encode($response);
		exit;
	}
	$slipData = $salary->getSlipWithLines($slipId);
	$slip = $slipData['slip'];
	echo json_encode([
		'error' => false,
		'message' => 'Download link ready',
		'employee_id' => $employeeId,
		'data' => [
			'slip_id' => $slipId,
			'company_id' => $companyId,
			'period_label' => date('M Y', mktime(0, 0, 0, intval($slip['PayrollMonth']), 1, intval($slip['PayrollYear']))),
			'net_salary' => floatval($slip['NetSalary'] ?? 0),
			'download_url' => $downloadUrl,
		],
	]);
	exit;
}

$slips = $salary->listEmployeeSlips($employeeId, $limit);
echo json_encode([
	'error' => false,
	'message' => count($slips) ? 'Salary slips loaded' : 'No salary slips generated yet',
	'employee_id' => $employeeId,
	'employee_name' => $empRow['Name'] ?? '',
	'default_company_id' => $companyId,
	'data' => $salary->formatSlipsForApi($slips, $companyId),
]);
exit;
