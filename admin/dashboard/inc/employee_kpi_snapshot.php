<?php
/**
 * Save / update monthly employee KPI snapshot (one row per employee per month).
 */

/**
 * Default KPI window: through yesterday, from the 1st of that month.
 * On 2026-06-01 → 2026-05-01 … 2026-05-31 (full May).
 * On 2026-06-15 → 2026-06-01 … 2026-06-14 (MTD).
 *
 * @return array{0:string,1:string} [dateFrom, dateTo] Y-m-d
 */
function employee_kpi_default_date_range()
{
	$dateTo = date('Y-m-d', strtotime('-1 day'));
	$dateFrom = date('Y-m-01', strtotime($dateTo));

	return [$dateFrom, $dateTo];
}

/**
 * Ensure DateFrom is never after DateTo (fixes month-boundary bug).
 */
function employee_kpi_fix_date_range($dateFrom, $dateTo)
{
	if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
		$dateFrom = date('Y-m-01', strtotime($dateTo));
	}

	return [$dateFrom, $dateTo];
}

/**
 * KPI date range for a payroll month (year + month selected on salary slip).
 * Past month → full month. Current month → 1st through yesterday.
 *
 * @return array{0:string,1:string}|null [dateFrom, dateTo] or null if month not started
 */
function employee_kpi_date_range_for_payroll_month($year, $month)
{
	$year = (int) $year;
	$month = (int) $month;
	if ($month < 1 || $month > 12) {
		return null;
	}

	$dateFrom = sprintf('%04d-%02d-01', $year, $month);
	$monthEnd = date('Y-m-t', strtotime($dateFrom));
	$yesterday = date('Y-m-d', strtotime('-1 day'));

	if ($yesterday < $dateFrom) {
		return null;
	}

	$dateTo = ($yesterday > $monthEnd) ? $monthEnd : $yesterday;

	return employee_kpi_fix_date_range($dateFrom, $dateTo);
}

function employee_kpi_snapshot_ensure_table($conn)
{
	$sql = "CREATE TABLE IF NOT EXISTS `employee_kpi_monthly_snapshot` (
		`ID` int NOT NULL AUTO_INCREMENT,
		`EmployeeID` int NOT NULL,
		`KpiYear` smallint NOT NULL,
		`KpiMonth` tinyint NOT NULL,
		`DateFrom` date NOT NULL,
		`DateTo` date NOT NULL,
		`EmployeeName` varchar(255) DEFAULT NULL,
		`Designation` varchar(255) DEFAULT NULL,
		`ContactNumber` varchar(50) DEFAULT NULL,
		`IsExecutive` tinyint(1) NOT NULL DEFAULT 0,
		`AssignTotalTickets` int NOT NULL DEFAULT 0,
		`AssignWithin1Hour` int NOT NULL DEFAULT 0,
		`AssignAfter1Hour` int NOT NULL DEFAULT 0,
		`AssignReassignCount` int NOT NULL DEFAULT 0,
		`AssignPerformancePct` decimal(6,2) NOT NULL DEFAULT 0.00,
		`QuoteTotalTickets` int NOT NULL DEFAULT 0,
		`QuoteWithin48Hour` int NOT NULL DEFAULT 0,
		`QuoteAfter48Hour` int NOT NULL DEFAULT 0,
		`QuotePending` int NOT NULL DEFAULT 0,
		`QuoteNotApproved48` int NOT NULL DEFAULT 0,
		`QuoteAmcTickets` int NOT NULL DEFAULT 0,
		`QuotePerformancePct` decimal(6,2) NOT NULL DEFAULT 0.00,
		`ClosedTotal` int NOT NULL DEFAULT 0,
		`ClosedWithin24` int NOT NULL DEFAULT 0,
		`ClosedAfter24` int NOT NULL DEFAULT 0,
		`ClosingTarget` int NOT NULL DEFAULT 0,
		`ClosedPerformancePct` decimal(6,2) NOT NULL DEFAULT 0.00,
		`WorkingDays` int NOT NULL DEFAULT 0,
		`PresentDays` int NOT NULL DEFAULT 0,
		`AbsentDays` int NOT NULL DEFAULT 0,
		`AttendancePerformancePct` decimal(6,2) NOT NULL DEFAULT 0.00,
		`OverallKpiPct` decimal(6,2) NOT NULL DEFAULT 0.00,
		`FullSalary` decimal(12,2) NOT NULL DEFAULT 0.00,
		`PayableSalary` decimal(12,2) NOT NULL DEFAULT 0.00,
		`SalaryPct` decimal(6,2) NOT NULL DEFAULT 0.00,
		`ChartUrl` text,
		`WhatsappSent` tinyint(1) NOT NULL DEFAULT 0,
		`WhatsappResponse` text,
		`SentAt` datetime DEFAULT NULL,
		`CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`UpdatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`ID`),
		UNIQUE KEY `uk_employee_kpi_month` (`EmployeeID`,`KpiYear`,`KpiMonth`),
		KEY `idx_kpi_period` (`KpiYear`,`KpiMonth`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
	mysqli_query($conn, $sql);
}

function saveEmployeeKpiMonthlySnapshot($conn, array $d)
{
	employee_kpi_snapshot_ensure_table($conn);

	$employeeId = intval($d['employee_id'] ?? 0);
	$kpiYear = intval($d['kpi_year'] ?? date('Y'));
	$kpiMonth = intval($d['kpi_month'] ?? date('n'));
	if ($employeeId <= 0) {
		return ['error' => true, 'message' => 'Invalid employee'];
	}

	$assignData = $d['assignment_data'] ?? [];
	$quoteData = $d['quotation_data'] ?? [];
	$closedData = $d['closed_data'] ?? [];
	$attData = $d['attendance_data'] ?? [];

	$esc = function ($v) use ($conn) {
		return mysqli_real_escape_string($conn, (string) $v);
	};

	$row = [
		'EmployeeID' => $employeeId,
		'KpiYear' => $kpiYear,
		'KpiMonth' => $kpiMonth,
		'DateFrom' => $esc($d['date_from'] ?? date('Y-m-01')),
		'DateTo' => $esc($d['date_to'] ?? date('Y-m-d')),
		'EmployeeName' => $esc($d['employee_name'] ?? ''),
		'Designation' => $esc($d['designation'] ?? ''),
		'ContactNumber' => $esc($d['contact_number'] ?? ''),
		'IsExecutive' => !empty($d['is_executive']) ? 1 : 0,
		'AssignTotalTickets' => intval($assignData['TotalTickets'] ?? 0),
		'AssignWithin1Hour' => intval($assignData['Within1Hour'] ?? 0),
		'AssignAfter1Hour' => intval($assignData['After1Hour'] ?? 0),
		'AssignReassignCount' => intval($assignData['ReassignCount'] ?? 0),
		'AssignPerformancePct' => floatval($d['assign_pct'] ?? 0),
		'QuoteTotalTickets' => intval($quoteData['TotalTickets'] ?? 0),
		'QuoteWithin48Hour' => intval($quoteData['Within48Hour'] ?? 0),
		'QuoteAfter48Hour' => intval($quoteData['After48Hour'] ?? 0),
		'QuotePending' => intval($quoteData['Pending'] ?? 0),
		'QuoteNotApproved48' => intval($quoteData['NotApprovedIn48Hour'] ?? 0),
		'QuoteAmcTickets' => intval($quoteData['AmcTickets'] ?? 0),
		'QuotePerformancePct' => floatval($d['quote_pct'] ?? 0),
		'ClosedTotal' => intval($closedData['TotalClosed'] ?? 0),
		'ClosedWithin24' => intval($closedData['ClosedWithin24'] ?? 0),
		'ClosedAfter24' => intval($closedData['ClosedAfter24'] ?? 0),
		'ClosingTarget' => intval($closedData['ClosingTarget'] ?? 0),
		'ClosedPerformancePct' => floatval($d['closed_pct'] ?? 0),
		'WorkingDays' => intval($attData['WorkingDays'] ?? 0),
		'PresentDays' => intval($attData['PresentDays'] ?? 0),
		'AbsentDays' => intval($attData['AbsentDays'] ?? 0),
		'AttendancePerformancePct' => floatval($d['attendance_pct'] ?? 0),
		'OverallKpiPct' => floatval($d['overall_pct'] ?? 0),
		'FullSalary' => floatval($d['full_salary'] ?? 0),
		'PayableSalary' => floatval($d['payable_salary'] ?? 0),
		'SalaryPct' => floatval($d['salary_pct'] ?? 0),
		'ChartUrl' => $esc($d['chart_url'] ?? ''),
	];

	$exists = mysqli_query($conn, "SELECT ID FROM employee_kpi_monthly_snapshot WHERE EmployeeID=$employeeId AND KpiYear=$kpiYear AND KpiMonth=$kpiMonth LIMIT 1");
	$isUpdate = $exists && mysqli_num_rows($exists) > 0;

	if ($isUpdate) {
		$sets = [];
		foreach ($row as $col => $val) {
			if (in_array($col, ['EmployeeID', 'KpiYear', 'KpiMonth'], true)) {
				continue;
			}
			if (is_float($val) || in_array($col, ['AssignPerformancePct', 'QuotePerformancePct', 'ClosedPerformancePct', 'AttendancePerformancePct', 'OverallKpiPct', 'FullSalary', 'PayableSalary', 'SalaryPct'], true)) {
				$sets[] = "`$col`=" . floatval($val);
			} elseif (is_int($val) || is_numeric($val)) {
				$sets[] = "`$col`=" . intval($val);
			} else {
				$sets[] = "`$col`='$val'";
			}
		}
		$sql = "UPDATE employee_kpi_monthly_snapshot SET " . implode(', ', $sets) . " WHERE EmployeeID=$employeeId AND KpiYear=$kpiYear AND KpiMonth=$kpiMonth";
	} else {
		$cols = array_keys($row);
		$vals = [];
		foreach ($row as $col => $val) {
			if (in_array($col, ['AssignPerformancePct', 'QuotePerformancePct', 'ClosedPerformancePct', 'AttendancePerformancePct', 'OverallKpiPct', 'FullSalary', 'PayableSalary', 'SalaryPct'], true)) {
				$vals[] = floatval($val);
			} elseif (is_int($val) || (is_numeric($val) && $col !== 'DateFrom' && $col !== 'DateTo' && $col !== 'EmployeeName' && $col !== 'Designation' && $col !== 'ContactNumber' && $col !== 'ChartUrl')) {
				$vals[] = intval($val);
			} else {
				$vals[] = "'$val'";
			}
		}
		$sql = "INSERT INTO employee_kpi_monthly_snapshot (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ")";
	}

	$ok = mysqli_query($conn, $sql);
	if (!$ok) {
		return ['error' => true, 'message' => mysqli_error($conn)];
	}

	return [
		'error' => false,
		'message' => $isUpdate ? 'updated' : 'inserted',
		'employee_id' => $employeeId,
		'kpi_year' => $kpiYear,
		'kpi_month' => $kpiMonth,
	];
}

function updateEmployeeKpiWhatsappStatus($conn, $employeeId, $kpiYear, $kpiMonth, $response, $sent = true)
{
	employee_kpi_snapshot_ensure_table($conn);
	$employeeId = intval($employeeId);
	$kpiYear = intval($kpiYear);
	$kpiMonth = intval($kpiMonth);
	$resp = mysqli_real_escape_string($conn, is_string($response) ? $response : json_encode($response));
	$sentFlag = $sent ? 1 : 0;
	$sentSql = $sent ? ', SentAt=NOW()' : '';
	$sql = "UPDATE employee_kpi_monthly_snapshot SET WhatsappSent=$sentFlag, WhatsappResponse='$resp' $sentSql WHERE EmployeeID=$employeeId AND KpiYear=$kpiYear AND KpiMonth=$kpiMonth";
	mysqli_query($conn, $sql);
	return true;
}
