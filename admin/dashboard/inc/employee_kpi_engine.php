<?php
/**
 * Recompute one employee KPI snapshot for payroll (no WhatsApp, no HTTP).
 */

require_once __DIR__ . '/employee_kpi_snapshot.php';

/**
 * Run employee_kpi_whatsapp.php compute + DB save for a single employee.
 *
 * @return array{error:bool,message?:string,kpi_snapshot?:array}
 */
function refreshEmployeeKpiSnapshotForPayroll($conn, $core, $employeeId, $year, $month)
{
	$employeeId = (int) $employeeId;
	$year = (int) $year;
	$month = (int) $month;

	if ($employeeId <= 0) {
		return ['error' => true, 'message' => 'Invalid employee for KPI refresh'];
	}

	$range = employee_kpi_date_range_for_payroll_month($year, $month);
	if ($range === null) {
		return [
			'error' => true,
			'message' => 'Cannot compute KPI: selected payroll month has not started yet.',
		];
	}

	[$dateFrom, $dateTo] = $range;

	$emp = $core->_getSQLDetails(
		$conn,
		'SELECT Name, Designation, ContactNumber FROM employees WHERE ID = ' . $employeeId . ' AND IFNULL(IsActive,1) = 1'
	);
	if (empty($emp)) {
		return ['error' => true, 'message' => 'Employee not found or inactive'];
	}

	$whatsappScript = dirname(__DIR__) . '/employee_kpi_whatsapp.php';
	if (!is_readable($whatsappScript)) {
		return ['error' => true, 'message' => 'KPI compute script not found'];
	}

	if (!defined('EMPLOYEE_KPI_SKIP_WHATSAPP')) {
		define('EMPLOYEE_KPI_SKIP_WHATSAPP', true);
	}
	if (!defined('EMPLOYEE_KPI_PAYROLL_RUN')) {
		define('EMPLOYEE_KPI_PAYROLL_RUN', true);
	}

	require_once dirname(__DIR__, 2) . '/controllers/common_controllers.php';

	// Release session lock so other portal tabs stay responsive during KPI SQL
	if (session_status() === PHP_SESSION_ACTIVE) {
		session_write_close();
	}

	$GLOBALS['_KPI_PAYROLL_CONN'] = $conn;
	$GLOBALS['_KPI_PAYROLL_CORE'] = $core;
	$GLOBALS['_KPI_COMPUTE_RESULT'] = null;
	$_GET['Employee'] = (string) $employeeId;
	$_GET['Datefrom'] = $dateFrom;
	$_GET['Dateto'] = $dateTo;
	$_GET['Role'] = !empty($emp['Designation']) ? $emp['Designation'] : 'Employee';
	$_GET['EmployeeNumber'] = $emp['ContactNumber'] ?? '';

	$prevLimit = ini_get('max_execution_time');
	@set_time_limit(90);

	ob_start();
	try {
		include $whatsappScript;
	} catch (Throwable $e) {
		ob_end_clean();
		@set_time_limit((int) $prevLimit);
		return ['error' => true, 'message' => 'KPI compute failed: ' . $e->getMessage()];
	}
	ob_end_clean();
	@set_time_limit((int) $prevLimit);

	$result = $GLOBALS['_KPI_COMPUTE_RESULT'] ?? null;
	if (!is_array($result)) {
		return ['error' => true, 'message' => 'KPI snapshot was not saved. Check server logs.'];
	}

	if (!empty($result['error'])) {
		return [
			'error' => true,
			'message' => $result['message'] ?? 'Failed to save KPI snapshot',
		];
	}

	return [
		'error' => false,
		'message' => 'KPI snapshot updated (' . $dateFrom . ' → ' . $dateTo . ')',
		'kpi_snapshot' => $result['kpi_snapshot'] ?? [],
		'date_from' => $dateFrom,
		'date_to' => $dateTo,
	];
}
