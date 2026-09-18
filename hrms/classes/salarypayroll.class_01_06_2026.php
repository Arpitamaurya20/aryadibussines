<?php

/**
 * Monthly payroll + India-oriented statutory helpers driven by hrms_salary_rule.
 * Uses employees.* salary columns already in your schema.
 */
class Salarypayroll extends Core
{
	private $conn;

	/** Cached Conf instance so we don't re-read config.json on every call. */
	private $_confCache = null;

	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	/**
	 * Lazy-load the HRMS Conf object (config.json reader).
	 */
	private function getConf()
	{
		if ($this->_confCache === null) {
			$this->_confCache = new Conf();
		}
		return $this->_confCache;
	}

	public function getRulesMap()
	{
		$rows = $this->_getTableRecords($this->conn, 'hrms_salary_rule', 'WHERE IsActive = 1 ORDER BY RuleKey ASC');
		$map = [];
		foreach ($rows as $r) {
			$map[$r['RuleKey']] = $r['RuleValue'];
		}
		return $map;
	}

	public function saveRuleKey($key, $value, $updatedBy = '')
	{
		$key = mysqli_real_escape_string($this->conn, $key);
		$value = mysqli_real_escape_string($this->conn, $value);
		$updatedBy = mysqli_real_escape_string($this->conn, $updatedBy);
		$d = date('Y-m-d');
		$t = date('H:i:s');
		$sql = "INSERT INTO hrms_salary_rule (RuleKey, RuleValue, RuleType, IsActive, CreatedDate, CreatedTime, UpdatedDate, UpdatedBy)
			VALUES ('$key', '$value', 'string', 1, '$d', '$t', '$d', '$updatedBy')
			ON DUPLICATE KEY UPDATE RuleValue = '$value', UpdatedDate = '$d', UpdatedBy = '$updatedBy'";
		return $this->_InsertTableRecords($this->conn, $sql);
	}

	/**
	 * Sum numeric columns from employee row using comma-separated column names.
	 */
	private function sumColumns($employeeRow, $csvKeys)
	{
		$keys = array_map('trim', explode(',', $csvKeys));
		$sum = 0.0;
		foreach ($keys as $col) {
			if ($col === '') {
				continue;
			}
			if (!array_key_exists($col, $employeeRow)) {
				continue;
			}
			$sum += floatval($employeeRow[$col]);
		}
		return round($sum, 2);
	}

	private function roundMoney($n, $decimals = 2)
	{
		return round(floatval($n), $decimals);
	}

	private function getAttendancePresentDays($employeeId, $year, $month)
	{
		$employeeId = intval($employeeId);
		$start = sprintf('%04d-%02d-01', intval($year), intval($month));
		$end = date('Y-m-t', strtotime($start));
		$sql = "SELECT COUNT(DISTINCT RecordDate) AS present_days
			FROM employee_attendance
			WHERE EmployeeID = $employeeId
			  AND RecordDate >= '$start'
			  AND RecordDate <= '$end'
			  AND IFNULL(InTime,'') <> ''";
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			$row = mysqli_fetch_assoc($res);
			return floatval($row['present_days']);
		}
		return 0.0;
	}

	private function getAttendanceStats($employeeId, $year, $month)
	{
		$employeeId = intval($employeeId);
		$start = sprintf('%04d-%02d-01', intval($year), intval($month));
		$end = date('Y-m-t', strtotime($start));
		$sql = "SELECT
				COUNT(DISTINCT RecordDate) AS present_days,
				MIN(RecordDate) AS first_present_date,
				MAX(RecordDate) AS last_present_date
			FROM employee_attendance
			WHERE EmployeeID = $employeeId
			  AND RecordDate >= '$start'
			  AND RecordDate <= '$end'
			  AND IFNULL(InTime,'') <> ''";
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			$row = mysqli_fetch_assoc($res);
			return [
				'present_days' => floatval($row['present_days']),
				'first_present_date' => !empty($row['first_present_date']) ? $row['first_present_date'] : null,
				'last_present_date' => !empty($row['last_present_date']) ? $row['last_present_date'] : null,
			];
		}
		return [
			'present_days' => 0.0,
			'first_present_date' => null,
			'last_present_date' => null,
		];
	}

	private function countSundaysBetween($startDate, $endDate)
	{
		if (empty($startDate) || empty($endDate)) {
			return 0;
		}
		$startTs = strtotime($startDate);
		$endTs = strtotime($endDate);
		if ($startTs === false || $endTs === false || $startTs > $endTs) {
			return 0;
		}
		$sundays = 0;
		for ($dayTs = $startTs; $dayTs <= $endTs; $dayTs += 86400) {
			if (date('w', $dayTs) == '0') {
				$sundays++;
			}
		}
		return $sundays;
	}

	private function parseWeeklyOffDays($weeklyOffRaw)
	{
		$map = [
			'sunday' => 0,
			'monday' => 1,
			'tuesday' => 2,
			'wednesday' => 3,
			'thursday' => 4,
			'friday' => 5,
			'saturday' => 6,
		];
		$weeklyOffRaw = trim(strtolower((string)$weeklyOffRaw));
		if ($weeklyOffRaw === '') {
			return [0];
		}
		$parts = preg_split('/[\s,\/|&-]+/', $weeklyOffRaw);
		$days = [];
		foreach ($parts as $part) {
			if ($part === '' || !isset($map[$part])) {
				continue;
			}
			$days[] = $map[$part];
		}
		$days = array_values(array_unique($days));
		return !empty($days) ? $days : [0];
	}

	private function getPayrollPaidDaysStats($employeeRow, $year, $month)
	{
		$employeeId = intval($employeeRow['ID']);
		$monthStart = sprintf('%04d-%02d-01', intval($year), intval($month));
		$monthEnd = date('Y-m-t', strtotime($monthStart));
		$totalDays = intval(date('t', strtotime($monthStart)));
		$today = date('Y-m-d');
		$weeklyOffDays = $this->parseWeeklyOffDays(isset($employeeRow['WeeklyOff']) ? $employeeRow['WeeklyOff'] : 'Sunday');

		$presentMap = [];
		$presentSql = "SELECT DISTINCT RecordDate FROM employee_attendance
			WHERE EmployeeID = $employeeId
			  AND RecordDate >= '$monthStart'
			  AND RecordDate <= '$monthEnd'
			  AND IFNULL(InTime,'') <> ''";
		$presentRes = mysqli_query($this->conn, $presentSql);
		if ($presentRes) {
			while ($row = mysqli_fetch_assoc($presentRes)) {
				$presentMap[$row['RecordDate']] = true;
			}
		}

		$leaveMap = [];
		$leaveSql = "SELECT FromDate, ToDate FROM employee_leave
			WHERE EmployeeID = $employeeId
			  AND IFNULL(IsActive,1)=1
			  AND Status IN ('Approved','approved')
			  AND FromDate <= '$monthEnd'
			  AND ToDate >= '$monthStart'";
		$leaveRes = mysqli_query($this->conn, $leaveSql);
		if ($leaveRes) {
			while ($lv = mysqli_fetch_assoc($leaveRes)) {
				$from = max($monthStart, $lv['FromDate']);
				$to = min($monthEnd, $lv['ToDate']);
				$fromTs = strtotime($from);
				$toTs = strtotime($to);
				if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
					continue;
				}
				for ($dTs = $fromTs; $dTs <= $toTs; $dTs += 86400) {
					$leaveMap[date('Y-m-d', $dTs)] = true;
				}
			}
		}

		$holidayMap = [];
		$holidaySql = "SELECT HolidaysDate FROM listofholidays
			WHERE IFNULL(IsActive,1)=1
			  AND HolidaysDate >= '$monthStart'
			  AND HolidaysDate <= '$monthEnd'";
		$holidayRes = mysqli_query($this->conn, $holidaySql);
		if ($holidayRes) {
			while ($row = mysqli_fetch_assoc($holidayRes)) {
				$holidayMap[$row['HolidaysDate']] = true;
			}
		}

		$p = 0;
		$l = 0;
		$h = 0;
		$wo = 0;
		for ($d = 1; $d <= $totalDays; $d++) {
			$date = sprintf('%04d-%02d-%02d', intval($year), intval($month), $d);
			if ($date > $today) {
				continue;
			}
			$weekday = intval(date('w', strtotime($date)));

			if (isset($presentMap[$date])) {
				$p++;
				continue;
			}
			if (isset($leaveMap[$date])) {
				$l++;
				continue;
			}
			if (isset($holidayMap[$date])) {
				$h++;
				continue;
			}
			if (in_array($weekday, $weeklyOffDays, true)) {
				$wo++;
			}
		}

		return [
			'present_days' => floatval($p),
			'leave_days' => floatval($l),
			'holiday_days' => floatval($h),
			'weekly_off_days' => floatval($wo),
			'paid_days' => floatval($p + $l + $h + $wo),
		];
	}

	private function getEmployeeAdjustments($employeeId, $year, $month)
	{
		$employeeId = intval($employeeId);
		$year = intval($year);
		$month = intval($month);
		return $this->_getTableRecords(
			$this->conn,
			'hrms_salary_employee_adjustment',
			"WHERE EmployeeID = $employeeId AND PayrollYear = $year AND PayrollMonth = $month ORDER BY ID ASC"
		);
	}

	/**
	 * Core calculation for one employee / one month (full month pay; proration hooks later).
	 */
	public function buildSlipComputation($employeeRow, array $rules)
	{
		$grossCsv = isset($rules['gross_components']) ? $rules['gross_components'] : 'Basic,HRA,ConvenienceAllowance,Bonus,HealthInsurance,Others';
		$pfBaseCsv = isset($rules['pf_base_components']) ? $rules['pf_base_components'] : 'Basic';

		$employeeApplyEPF = !isset($employeeRow['ApplyEPF']) || intval($employeeRow['ApplyEPF']) === 1;
		$employeeApplyESI = !isset($employeeRow['ApplyESI']) || intval($employeeRow['ApplyESI']) === 1;

		$epfEE = floatval(isset($rules['epf_employee_percent']) ? $rules['epf_employee_percent'] : 12);
		$epfER = floatval(isset($rules['epf_employer_percent']) ? $rules['epf_employer_percent'] : 12);
		$wageCeil = floatval(isset($rules['epf_wage_ceiling']) ? $rules['epf_wage_ceiling'] : 15000);

		$esiCeil = floatval(isset($rules['esi_eligible_gross_ceiling']) ? $rules['esi_eligible_gross_ceiling'] : 21000);
		$esiEE = floatval(isset($rules['esi_employee_percent']) ? $rules['esi_employee_percent'] : 0.75);
		$esiER = floatval(isset($rules['esi_employer_percent']) ? $rules['esi_employer_percent'] : 3.25);
		$esiEnabled = !isset($rules['esi_enabled']) || intval($rules['esi_enabled']) === 1;

		$lwf = floatval(isset($rules['lwf_monthly']) ? $rules['lwf_monthly'] : 0);
		$tdsPct = floatval(isset($rules['tds_percent']) ? $rules['tds_percent'] : 0);
		$incomeTaxFlat = floatval(isset($rules['income_tax_monthly']) ? $rules['income_tax_monthly'] : 0);

		$grossRaw = $this->sumColumns($employeeRow, $grossCsv);
		$lines = [];
		$sort = 0;

		$y = intval(date('Y'));
		$m = intval(date('m'));
		if (!empty($employeeRow['_period_year'])) {
			$y = intval($employeeRow['_period_year']);
		}
		if (!empty($employeeRow['_period_month'])) {
			$m = intval($employeeRow['_period_month']);
		}
		$totalDays = floatval(cal_days_in_month(CAL_GREGORIAN, $m, $y));
		$paidDaysMode = 'attendance_register_paid_days';
		$paidStats = $this->getPayrollPaidDaysStats($employeeRow, $y, $m);
		$presentDays = floatval($paidStats['present_days']);
		$minPaidDays = floatval(isset($rules['min_paid_days']) ? $rules['min_paid_days'] : 0);
		$paidDays = floatval($paidStats['paid_days']);
		$paidDays = max($paidDays, $minPaidDays);
		$paidDays = min($paidDays, $totalDays);
		$prorationFactor = ($totalDays > 0) ? ($paidDays / $totalDays) : 1.0;
		$prorationFactor = $this->roundMoney($prorationFactor, 6);

		$grossKeys = array_map('trim', explode(',', $grossCsv));
		$otherBonusCombined = 0.0;
		foreach ($grossKeys as $col) {
			if ($col === '' || !array_key_exists($col, $employeeRow)) {
				continue;
			}
			$amt = $this->roundMoney(floatval($employeeRow[$col]) * $prorationFactor);
			if ($col === 'Bonus' || $col === 'Others') {
				$otherBonusCombined += $amt;
				continue;
			}
			if ($amt != 0.0) {
				$lines[] = [
					'type' => 'earning',
					'code' => $col,
					'label' => $col,
					'amount' => $amt,
					'sort' => $sort++,
				];
			}
		}
		$otherBonusCombined = $this->roundMoney($otherBonusCombined);
		if ($otherBonusCombined != 0.0) {
			$lines[] = [
				'type' => 'earning',
				'code' => 'OTHER_BONUS',
				'label' => 'Other + Bonus',
				'amount' => $otherBonusCombined,
				'sort' => $sort++,
			];
		}

		$gross = $this->roundMoney($grossRaw * $prorationFactor);
		$pfWageFull = $this->roundMoney($this->sumColumns($employeeRow, $pfBaseCsv) * $prorationFactor);
		$pfWage = $pfWageFull;
		if ($wageCeil > 0) {
			$pfWage = min($pfWageFull, $wageCeil);
		}
		$pfWage = $this->roundMoney($pfWage);

		$isSalaryAboveESILimit = ($gross > 21000);
		$effectiveApplyESI = $employeeApplyESI && !$isSalaryAboveESILimit;

		$epfEmpAmount = 0.0;
		$epfEmprAmount = 0.0;
		if ($employeeApplyEPF) {
			if ($gross < 15000) {
				$epfEmpAmount = $this->roundMoney($gross * 12 / 100.0);
			} else {
				// 15,000 to 21,000 slab is fixed 1,800; above 21,000 kept at same statutory cap.
				$epfEmpAmount = 1800.00;
			}
			$epfEmprAmount = $this->roundMoney($epfEmpAmount);
		}

		if ($epfEmpAmount > 0) {
			$lines[] = [
				'type' => 'deduction',
				'code' => 'EPF_EE',
				'label' => 'EPF (Employee) on wage ' . $pfWage,
				'amount' => $epfEmpAmount,
				'sort' => $sort++,
			];
		}
		if ($epfEmprAmount > 0) {
			$lines[] = [
				'type' => 'employer',
				'code' => 'EPF_ER',
				'label' => 'EPF (Employer contribution)',
				'amount' => $epfEmprAmount,
				'sort' => $sort++,
			];
		}

		$esiEEAmt = 0.0;
		$esiERAmt = 0.0;
		if ($effectiveApplyESI && $esiEnabled && $esiCeil > 0 && $gross <= $esiCeil) {
			$esiEEAmt = $this->roundMoney($gross * $esiEE / 100.0);
			$esiERAmt = $this->roundMoney($gross * $esiER / 100.0);
			if ($esiEEAmt > 0) {
				$lines[] = [
					'type' => 'deduction',
					'code' => 'ESI_EE',
					'label' => 'ESI (Employee) @ ' . $esiEE . '%',
					'amount' => $esiEEAmt,
					'sort' => $sort++,
				];
			}
			if ($esiERAmt > 0) {
				$lines[] = [
					'type' => 'employer',
					'code' => 'ESI_ER',
					'label' => 'ESI (Employer) @ ' . $esiER . '%',
					'amount' => $esiERAmt,
					'sort' => $sort++,
				];
			}
		}

		// Professional Tax intentionally disabled as per payroll requirement.

		if ($lwf > 0) {
			$lines[] = [
				'type' => 'deduction',
				'code' => 'LWF',
				'label' => 'LWF',
				'amount' => $this->roundMoney($lwf),
				'sort' => $sort++,
			];
		}

		if ($tdsPct > 0) {
			$tdsAmt = $this->roundMoney($gross * $tdsPct / 100.0);
			if ($tdsAmt > 0) {
				$lines[] = [
					'type' => 'deduction',
					'code' => 'TDS',
					'label' => 'TDS @ ' . $tdsPct . '%',
					'amount' => $tdsAmt,
					'sort' => $sort++,
				];
			}
		}
		if ($incomeTaxFlat > 0) {
			$lines[] = [
				'type' => 'deduction',
				'code' => 'INCOME_TAX',
				'label' => 'Income Tax',
				'amount' => $this->roundMoney($incomeTaxFlat),
				'sort' => $sort++,
			];
		}

		$adjustments = $this->getEmployeeAdjustments($employeeRow['ID'], $y, $m);
		foreach ($adjustments as $adj) {
			$adjType = strtolower(trim($adj['LineType']));
			if ($adjType !== 'earning' && $adjType !== 'deduction') {
				continue;
			}
			$adjAmount = $this->roundMoney($adj['Amount']);
			if ($adjAmount <= 0) {
				continue;
			}
			$lines[] = [
				'type' => $adjType,
				'code' => !empty($adj['ComponentCode']) ? $adj['ComponentCode'] : 'ADJ_' . intval($adj['ID']),
				'label' => $adj['ComponentLabel'],
				'amount' => $adjAmount,
				'sort' => $sort++,
			];
		}

		$finalGross = 0.0;
		$ded = 0.0;
		foreach ($lines as $ln) {
			if ($ln['type'] === 'deduction') {
				$ded += $ln['amount'];
			}
			if ($ln['type'] === 'earning') {
				$finalGross += $ln['amount'];
			}
		}
		$finalGross = $this->roundMoney($finalGross);
		$ded = $this->roundMoney($ded);
		$net = $this->roundMoney($finalGross - $ded);

		$snapshot = [
			'gross_raw_full_month' => $grossRaw,
			'gross_after_proration' => $gross,
			'employee_apply_epf' => $employeeApplyEPF ? 1 : 0,
			'employee_apply_esi' => $effectiveApplyESI ? 1 : 0,
			'present_days' => $presentDays,
			'leave_days' => isset($paidStats['leave_days']) ? $paidStats['leave_days'] : 0,
			'holiday_days' => isset($paidStats['holiday_days']) ? $paidStats['holiday_days'] : 0,
			'weekly_off_days' => isset($paidStats['weekly_off_days']) ? $paidStats['weekly_off_days'] : 0,
			'attendance_paid_days' => isset($paidStats['paid_days']) ? $paidStats['paid_days'] : $paidDays,
			'paid_days_mode' => $paidDaysMode,
			'proration_factor' => $prorationFactor,
			'pf_wage_used' => $pfWage,
			'pf_wage_uncapped' => $this->roundMoney($pfWageFull),
			'epf_employee' => $epfEmpAmount,
			'epf_employer' => $epfEmprAmount,
			'esi_gross_ceiling' => $esiCeil,
			'esi_employee' => $esiEEAmt,
			'esi_employer' => $esiERAmt,
			'total_deductions' => $ded,
			'net_salary' => $net,
			'rules_snapshot' => $rules,
		];

		return [
			'lines' => $lines,
			'gross_salary' => $finalGross,
			'total_deductions' => $ded,
			'net_salary' => $net,
			'paid_days' => $paidDays,
			'total_days' => $totalDays,
			'calculation_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
		];
	}

	public function getOrCreateRun($year, $month, $createdBy = '')
	{
		$year = intval($year);
		$month = intval($month);
		$where = "WHERE PayrollYear = $year AND PayrollMonth = $month";
		$exists = $this->_getTableDetails($this->conn, 'hrms_salary_run', $where);
		if (!empty($exists['ID'])) {
			return intval($exists['ID']);
		}
		$d = date('Y-m-d');
		$t = date('H:i:s');
		$createdBy = mysqli_real_escape_string($this->conn, $createdBy);
		$sql = "INSERT INTO hrms_salary_run (PayrollYear, PayrollMonth, Status, CreatedBy, CreatedDate, CreatedTime)
			VALUES ($year, $month, 'draft', '$createdBy', '$d', '$t')";
		$res = $this->_InsertTableRecords($this->conn, $sql);
		if (!empty($res['last_insert_id'])) {
			return intval($res['last_insert_id']);
		}
		return 0;
	}

	/**
	 * Saved monthly KPI row (employee_kpi_monthly_snapshot) for payroll month.
	 */
	public function getKpiMonthlySnapshot($employeeId, $year, $month)
	{
		$employeeId = intval($employeeId);
		$year = intval($year);
		$month = intval($month);
		if ($employeeId <= 0 || $month < 1 || $month > 12) {
			return null;
		}
		$sql = "SELECT * FROM employee_kpi_monthly_snapshot
			WHERE EmployeeID = $employeeId AND KpiYear = $year AND KpiMonth = $month
			LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		if ($res && mysqli_num_rows($res) > 0) {
			return mysqli_fetch_assoc($res);
		}
		return null;
	}

	private function recomputeSlipTotalsFromLines(array $comp)
	{
		$finalGross = 0.0;
		$ded = 0.0;
		foreach ($comp['lines'] as $ln) {
			if (($ln['type'] ?? '') === 'deduction') {
				$ded += floatval($ln['amount']);
			}
			if (($ln['type'] ?? '') === 'earning') {
				$finalGross += floatval($ln['amount']);
			}
		}
		$comp['gross_salary'] = $this->roundMoney($finalGross);
		$comp['total_deductions'] = $this->roundMoney($ded);
		$comp['net_salary'] = $this->roundMoney($finalGross - $ded);

		$snap = json_decode($comp['calculation_snapshot'] ?? '', true);
		if (!is_array($snap)) {
			$snap = [];
		}
		unset($snap['kpi_applied'], $snap['kpi'], $snap['kpi_deduction'], $snap['net_salary_before_kpi'], $snap['kpi_net_payable']);
		$snap['total_deductions'] = $comp['total_deductions'];
		$snap['net_salary'] = $comp['net_salary'];
		$comp['calculation_snapshot'] = json_encode($snap, JSON_UNESCAPED_UNICODE);

		return $comp;
	}

	private function stripKpiFromComputation(array $comp)
	{
		$comp['lines'] = array_values(array_filter($comp['lines'], function ($ln) {
			return strtoupper(trim($ln['code'] ?? '')) !== 'KPI_PERF';
		}));
		return $this->recomputeSlipTotalsFromLines($comp);
	}

	private function buildKpiSnapshotPayload(array $kpiRow)
	{
		return [
			'snapshot_id' => intval($kpiRow['ID'] ?? 0),
			'date_from' => $kpiRow['DateFrom'] ?? '',
			'date_to' => $kpiRow['DateTo'] ?? '',
			'is_executive' => intval($kpiRow['IsExecutive'] ?? 0),
			'assign_pct' => floatval($kpiRow['AssignPerformancePct'] ?? 0),
			'quote_pct' => floatval($kpiRow['QuotePerformancePct'] ?? 0),
			'closed_pct' => floatval($kpiRow['ClosedPerformancePct'] ?? 0),
			'attendance_pct' => floatval($kpiRow['AttendancePerformancePct'] ?? 0),
			'overall_pct' => floatval($kpiRow['OverallKpiPct'] ?? 0),
			'full_salary' => floatval($kpiRow['FullSalary'] ?? 0),
			'payable_salary' => floatval($kpiRow['PayableSalary'] ?? 0),
			'salary_pct' => floatval($kpiRow['SalaryPct'] ?? 0),
			'assign_total' => intval($kpiRow['AssignTotalTickets'] ?? 0),
			'quote_total' => intval($kpiRow['QuoteTotalTickets'] ?? 0),
			'closed_total' => intval($kpiRow['ClosedTotal'] ?? 0),
			'working_days' => intval($kpiRow['WorkingDays'] ?? 0),
			'present_days' => intval($kpiRow['PresentDays'] ?? 0),
			'absent_days' => intval($kpiRow['AbsentDays'] ?? 0),
		];
	}

	private function applyKpiToComputation(array $comp, array $kpiRow)
	{
		$comp = $this->stripKpiFromComputation($comp);

		$payable = $this->roundMoney(floatval($kpiRow['PayableSalary'] ?? 0));
		$fullSal = $this->roundMoney(floatval($kpiRow['FullSalary'] ?? 0));
		$overallPct = floatval($kpiRow['OverallKpiPct'] ?? 0);
		$kpiDeduction = max(0, $this->roundMoney($fullSal - $payable));
		$netBefore = $this->roundMoney(floatval($comp['net_salary']));
		$payrollAdjust = max(0, $this->roundMoney($netBefore - $payable));

		if ($payrollAdjust > 0) {
			$maxSort = 0;
			foreach ($comp['lines'] as $ln) {
				$maxSort = max($maxSort, intval($ln['sort'] ?? 0));
			}
			$comp['lines'][] = [
				'type' => 'deduction',
				'code' => 'KPI_PERF',
				'label' => 'KPI Performance (' . number_format($overallPct, 1) . '% overall)',
				'amount' => $payrollAdjust,
				'sort' => $maxSort + 1,
			];
			$comp['total_deductions'] = $this->roundMoney(floatval($comp['total_deductions']) + $payrollAdjust);
		}

		$comp['net_salary'] = $payable;

		$snap = json_decode($comp['calculation_snapshot'] ?? '', true);
		if (!is_array($snap)) {
			$snap = [];
		}
		$snap['kpi_applied'] = 1;
		$snap['net_salary_before_kpi'] = $netBefore;
		$snap['kpi_deduction'] = $kpiDeduction;
		$snap['kpi_net_payable'] = $payable;
		$snap['kpi'] = $this->buildKpiSnapshotPayload($kpiRow);
		$snap['total_deductions'] = $comp['total_deductions'];
		$snap['net_salary'] = $payable;
		$comp['calculation_snapshot'] = json_encode($snap, JSON_UNESCAPED_UNICODE);

		return $comp;
	}

	public function replaceSlipLines($salarySlipId, array $lines)
	{
		$id = intval($salarySlipId);
		mysqli_query($this->conn, "DELETE FROM hrms_salary_slip_line WHERE SalarySlipID = $id");
		foreach ($lines as $ln) {
			$type = mysqli_real_escape_string($this->conn, $ln['type']);
			$code = mysqli_real_escape_string($this->conn, isset($ln['code']) ? $ln['code'] : '');
			$label = mysqli_real_escape_string($this->conn, $ln['label']);
			$amt = mysqli_real_escape_string($this->conn, $ln['amount']);
			$sort = intval($ln['sort']);
			$sql = "INSERT INTO hrms_salary_slip_line (SalarySlipID, LineType, ComponentCode, ComponentLabel, Amount, SortOrder)
				VALUES ($id, '$type', '$code', '$label', '$amt', $sort)";
			$this->_InsertTableRecords($this->conn, $sql);
		}
	}

	private function saveEmployeeSlip($runId, $year, $month, $employeeRow, $comp)
	{
		$eid = intval($employeeRow['ID']);
		$d = date('Y-m-d');
		$t = date('H:i:s');
		$whereSlip = "WHERE SalaryRunID = $runId AND EmployeeID = $eid";
		$old = $this->_getTableDetails($this->conn, 'hrms_salary_slip', $whereSlip);

		$g = mysqli_real_escape_string($this->conn, $comp['gross_salary']);
		$td = mysqli_real_escape_string($this->conn, $comp['total_deductions']);
		$n = mysqli_real_escape_string($this->conn, $comp['net_salary']);
		$pd = mysqli_real_escape_string($this->conn, $comp['paid_days']);
		$tdays = mysqli_real_escape_string($this->conn, $comp['total_days']);
		$json = mysqli_real_escape_string($this->conn, $comp['calculation_snapshot']);

		if (!empty($old['ID'])) {
			$sid = intval($old['ID']);
			$uq = " GrossSalary='$g', TotalDeductions='$td', NetSalary='$n', PaidDays='$pd', TotalDays='$tdays', CalculationJson='$json', CreatedDate='$d', CreatedTime='$t' WHERE ID=$sid";
			$this->_UpdateTableRecords($this->conn, 'hrms_salary_slip', $uq);
			$this->replaceSlipLines($sid, $comp['lines']);
			return $sid;
		}

		$sql = "INSERT INTO hrms_salary_slip (SalaryRunID, EmployeeID, PayrollYear, PayrollMonth, GrossSalary, TotalDeductions, NetSalary, PaidDays, TotalDays, CalculationJson, CreatedDate, CreatedTime)
			VALUES ($runId, $eid, $year, $month, '$g', '$td', '$n', '$pd', '$tdays', '$json', '$d', '$t')";
		$ins = $this->_InsertTableRecords($this->conn, $sql);
		$sid = isset($ins['last_insert_id']) ? intval($ins['last_insert_id']) : 0;
		if ($sid > 0) {
			$this->replaceSlipLines($sid, $comp['lines']);
		}
		return $sid;
	}

	public function generateMonthlyPayroll($year, $month, $overwrite = true, $createdBy = '')
	{
		$year = intval($year);
		$month = intval($month);
		$rules = $this->getRulesMap();

		$runId = $this->getOrCreateRun($year, $month, $createdBy);
		if ($runId <= 0) {
			return ['error' => true, 'message' => 'Could not create payroll run'];
		}

		$runRow = $this->_getTableDetails($this->conn, 'hrms_salary_run', "WHERE ID = $runId");
		if (!$overwrite && !empty($runRow['Status']) && strtolower($runRow['Status']) === 'finalized') {
			return ['error' => true, 'message' => 'Payroll finalized for this month. Enable overwrite or change status in DB.'];
		}

		$employees = $this->_getTableRecords($this->conn, 'employees', 'WHERE IFNULL(IsActive,1) = 1 ORDER BY ID ASC');
		$processed = 0;

		foreach ($employees as $emp) {
			$emp['_period_year'] = $year;
			$emp['_period_month'] = $month;
			$comp = $this->buildSlipComputation($emp, $rules);
			$this->saveEmployeeSlip($runId, $year, $month, $emp, $comp);
			$processed++;
		}

		return ['error' => false, 'message' => "Payroll generated for $processed employees", 'run_id' => $runId];
	}

	public function generateSingleEmployeePayroll($year, $month, $employeeId, $overwrite = true, $createdBy = '', $includeKpi = false)
	{
		$year = intval($year);
		$month = intval($month);
		$employeeId = intval($employeeId);
		$includeKpi = (bool) $includeKpi;
		if ($employeeId <= 0) {
			return ['error' => true, 'message' => 'Invalid employee selected'];
		}

		$rules = $this->getRulesMap();
		$runId = $this->getOrCreateRun($year, $month, $createdBy);
		if ($runId <= 0) {
			return ['error' => true, 'message' => 'Could not create payroll run'];
		}

		$runRow = $this->_getTableDetails($this->conn, 'hrms_salary_run', "WHERE ID = $runId");
		if (!$overwrite && !empty($runRow['Status']) && strtolower($runRow['Status']) === 'finalized') {
			return ['error' => true, 'message' => 'Payroll finalized for this month. Enable overwrite or change status in DB.'];
		}

		$emp = $this->_getTableDetails($this->conn, 'employees', "WHERE ID = $employeeId AND IFNULL(IsActive,1) = 1");
		if (empty($emp['ID'])) {
			return ['error' => true, 'message' => 'Employee not found or inactive'];
		}

		$emp['_period_year'] = $year;
		$emp['_period_month'] = $month;
		$comp = $this->buildSlipComputation($emp, $rules);

		if ($includeKpi) {
			$kpiRow = $this->getKpiMonthlySnapshot($employeeId, $year, $month);
			if ($kpiRow === null) {
				return [
					'error' => true,
					'message' => 'No KPI snapshot for this employee and month. Send monthly KPI WhatsApp first, then regenerate.',
				];
			}
			$comp = $this->applyKpiToComputation($comp, $kpiRow);
		} else {
			$comp = $this->stripKpiFromComputation($comp);
		}

		$sid = $this->saveEmployeeSlip($runId, $year, $month, $emp, $comp);
		if ($sid <= 0) {
			return ['error' => true, 'message' => 'Could not save employee salary slip'];
		}

		$msg = $includeKpi
			? 'Salary slip generated with KPI performance applied'
			: 'Salary generated for selected employee';

		return ['error' => false, 'message' => $msg, 'run_id' => $runId, 'salary_slip_id' => $sid];
	}

	public function updateEmployeeStatutoryFlags($employeeId, $applyEPF, $applyESI)
	{
		$employeeId = intval($employeeId);
		$applyEPF = intval($applyEPF) === 1 ? 1 : 0;
		$applyESI = intval($applyESI) === 1 ? 1 : 0;
		if ($employeeId <= 0) {
			return ['error' => true, 'message' => 'Invalid employee'];
		}
		$query = " ApplyEPF = $applyEPF, ApplyESI = $applyESI WHERE ID = $employeeId";
		return $this->_UpdateTableRecords($this->conn, 'employees', $query);
	}

	private function getPayslipShareSecret()
	{
		return hash('sha256', 'techxpert_hrms_payslip_share_v1');
	}

	public function createPayslipShareToken($slipId, $companyId, $ttlSeconds = 2592000)
	{
		$slipId = intval($slipId);
		$companyId = intval($companyId);
		$expiry = time() + max(3600, intval($ttlSeconds));
		$payload = $slipId . '.' . $companyId . '.' . $expiry;
		$sig = hash_hmac('sha256', $payload, $this->getPayslipShareSecret());
		return rtrim(strtr(base64_encode($payload . '.' . $sig), '+/', '-_'), '=');
	}

	public function verifyPayslipShareToken($token)
	{
		$token = trim((string) $token);
		if ($token === '') {
			return ['valid' => false, 'message' => 'Missing link token'];
		}
		$raw = base64_decode(strtr($token, '-_', '+/'), true);
		if ($raw === false) {
			return ['valid' => false, 'message' => 'Invalid link'];
		}
		$parts = explode('.', $raw);
		if (count($parts) !== 4) {
			return ['valid' => false, 'message' => 'Invalid link format'];
		}
		$slipId = intval($parts[0]);
		$companyId = intval($parts[1]);
		$expiry = intval($parts[2]);
		$sig = $parts[3];
		$payload = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
		$expected = hash_hmac('sha256', $payload, $this->getPayslipShareSecret());
		if (!hash_equals($expected, $sig)) {
			return ['valid' => false, 'message' => 'Invalid or tampered link'];
		}
		if (time() > $expiry) {
			return ['valid' => false, 'message' => 'This download link has expired'];
		}
		if ($slipId <= 0 || $companyId <= 0) {
			return ['valid' => false, 'message' => 'Invalid slip reference'];
		}
		return [
			'valid' => true,
			'slip_id' => $slipId,
			'company_id' => $companyId,
			'expires_at' => $expiry,
		];
	}

	public function getPayslipPublicWebPath()
	{
		$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
		$apiDir = str_replace('\\', '/', dirname(__DIR__, 2) . '/api');
		if ($docRoot !== '' && strpos($apiDir, $docRoot) === 0) {
			return str_replace('\\', '/', substr($apiDir, strlen($docRoot)));
		}
		if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
			return '/Projects/techxpert/api';
		}
		return '/api';
	}

	public function buildPayslipShareUrl($token, $scriptDir = null)
	{
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		if ($scriptDir === null) {
			$scriptDir = rtrim($this->getPayslipPublicWebPath(), '/');
		}
		return $protocol . '://' . $host . $scriptDir . '/download_employee_salary_slip.php?token=' . rawurlencode($token);
	}

	public function getDefaultQuoteCompanyId()
	{
		$list = $this->listQuoteCompanies();
		return !empty($list) ? intval($list[0]['ID']) : 0;
	}

	public function slipBelongsToEmployee($slipId, $employeeId)
	{
		$slip = $this->_getTableDetails($this->conn, 'hrms_salary_slip', 'WHERE ID = ' . intval($slipId));
		return !empty($slip['ID']) && intval($slip['EmployeeID']) === intval($employeeId);
	}

	public function buildSlipShareDownloadUrl($slipId, $companyId = 0)
	{
		$slipId = intval($slipId);
		if ($companyId <= 0) {
			$companyId = $this->getDefaultQuoteCompanyId();
		}
		if ($slipId <= 0 || $companyId <= 0) {
			return null;
		}
		$token = $this->createPayslipShareToken($slipId, $companyId);
		return $this->buildPayslipShareUrl($token);
	}

	public function formatSlipsForApi($slips, $companyId = 0)
	{
		if ($companyId <= 0) {
			$companyId = $this->getDefaultQuoteCompanyId();
		}
		$out = [];
		foreach ($slips as $s) {
			$slipId = intval($s['ID']);
			$y = intval($s['PayrollYear']);
			$m = intval($s['PayrollMonth']);
			$out[] = [
				'slip_id' => $slipId,
				'payroll_year' => $y,
				'payroll_month' => $m,
				'period_label' => date('M Y', mktime(0, 0, 0, $m, 1, $y)),
				'gross_salary' => floatval($s['GrossSalary'] ?? 0),
				'total_deductions' => floatval($s['TotalDeductions'] ?? 0),
				'net_salary' => floatval($s['NetSalary'] ?? 0),
				'paid_days' => floatval($s['PaidDays'] ?? 0),
				'total_days' => floatval($s['TotalDays'] ?? 0),
				'download_url' => $this->buildSlipShareDownloadUrl($slipId, $companyId),
				'company_id' => $companyId,
			];
		}
		return $out;
	}

	public function buildWhatsAppShareUrl($phone, $message)
	{
		$digits = preg_replace('/\D+/', '', (string) $phone);
		if ($digits === '') {
			return '';
		}
		if (strlen($digits) === 10) {
			$digits = '91' . $digits;
		}
		return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
	}

	/**
	 * Fallback WhatsApp message body for the wa.me manual-share link.
	 * Mirrors the approved chatmybot template (id 3dab037d-01ab-4152-86bd-c16fd137b190)
	 * so the employee sees the same wording whichever path is used.
	 */
	public function buildPayslipWhatsAppMessage($empName, $monthTitle, $companyName, $downloadUrl)
	{
		$empName = trim($empName) !== '' ? trim($empName) : 'Team Member';
		$monthTitle = trim($monthTitle);
		$downloadUrl = trim($downloadUrl);

		return "Dear " . $empName . ",\n\n"
			. "Greetings from Techxpert Group!\n\n"
			. "Your salary for " . $monthTitle . " has been processed and approved.\n\n"
			. "Download your salary slip for this month using the secure link below:\n"
			. $downloadUrl . "\n\n"
			. "(This link is personal and valid for 30 days.)\n\n"
			. "If you have any concern or discrepancy, please contact the HR department at the earliest.\n\n"
			. "Thank you,\n"
			. "HR Team\n"
			. "Techxpert Group";
	}

	/**
	 * Normalise an Indian mobile to a 10-digit format expected by the chatmybot gateway.
	 * Returns '' when the input cannot yield a valid 10-digit number.
	 */
	private function normalisePayslipPhone($phone)
	{
		$digits = preg_replace('/\D+/', '', (string) $phone);
		if ($digits === '') {
			return '';
		}
		if (strlen($digits) > 10) {
			$digits = substr($digits, -10);
		}
		return strlen($digits) === 10 ? $digits : '';
	}

	/**
	 * Clean a value before using it as a WhatsApp body parameter.
	 * WhatsApp rejects \n, \t and 4+ consecutive spaces inside template parameters.
	 */
	private function cleanWaTemplateParam($value)
	{
		$value = (string) $value;
		$value = preg_replace("/[\r\n\t]+/", ' ', $value);
		$value = preg_replace('/\s{4,}/', '   ', $value);
		return trim($value);
	}

	/**
	 * Generic chatmybot.in WhatsApp Business batch send.
	 * Reads endpoint, token, auth scheme and template IDs from config.json -> whatsapp.
	 *
	 * @param string $templateKey  Key under whatsapp._Templates in config.json (e.g. "salary_payslip").
	 * @param string $phone        Phone number in any format (10 / 11 / 12 digit, with or without +).
	 * @param array  $bodyParams   Values for {{1}}, {{2}}, ... in template body order.
	 * @return array               ['ok','http_code','response','message_ids','error']
	 */
	public function sendChatmybotTemplate($templateKey, $phone, array $bodyParams)
	{
		$conf = $this->getConf();
		$wa = is_array($conf->_WhatsApp) ? $conf->_WhatsApp : [];
		$endpoint = trim((string) ($wa['_BatchEndpoint'] ?? ''));
		$token = trim((string) ($wa['_ApiToken'] ?? ''));
		$authScheme = trim((string) ($wa['_AuthScheme'] ?? 'Bearer'));
		$templateId = $conf->getWhatsAppTemplateId($templateKey);

		if ($endpoint === '') {
			return ['ok' => false, 'http_code' => 0, 'response' => null, 'message_ids' => [], 'error' => 'WhatsApp gateway endpoint not configured (config.json -> whatsapp._BatchEndpoint)'];
		}
		if ($templateId === '') {
			return ['ok' => false, 'http_code' => 0, 'response' => null, 'message_ids' => [], 'error' => 'WhatsApp template "' . $templateKey . '" not configured (config.json -> whatsapp._Templates.' . $templateKey . ')'];
		}
		if ($token === '') {
			return ['ok' => false, 'http_code' => 0, 'response' => null, 'message_ids' => [], 'error' => 'WhatsApp API token not configured (config.json -> whatsapp._ApiToken)'];
		}

		$to = $this->normalisePayslipPhone($phone);
		if ($to === '') {
			return ['ok' => false, 'http_code' => 0, 'response' => null, 'message_ids' => [], 'error' => 'Invalid phone number'];
		}

		$parameters = [];
		foreach ($bodyParams as $p) {
			$parameters[] = ['type' => 'text', 'text' => $this->cleanWaTemplateParam($p)];
		}

		$payload = [[
			'template' => [
				'id' => $templateId,
				'components' => $parameters ? [[
					'type' => 'body',
					'parameters' => $parameters,
				]] : [],
			],
			'to' => $to,
			'type' => 'template',
		]];

		$headers = ['Content-Type: application/json', 'Accept: application/json'];
		$schemeLower = strtolower($authScheme);
		if ($schemeLower === 'bearer' || $schemeLower === 'basic') {
			$headers[] = 'Authorization: ' . ucfirst($schemeLower) . ' ' . $token;
		} elseif ($schemeLower === 'token') {
			$headers[] = 'Authorization: ' . $token;
		} elseif ($schemeLower === 'x-api-key' || $schemeLower === 'apikey' || $schemeLower === 'api-key') {
			$headers[] = 'x-api-key: ' . $token;
		} else {
			// Treat unknown schemes as a literal header name: "<scheme>: <token>".
			$headers[] = $authScheme . ': ' . $token;
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $endpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_TIMEOUT => 45,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			// Force IPv4 - some hosts try IPv6 first and stall on gateways that only listen on v4.
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
			CURLOPT_FORBID_REUSE => true,
			CURLOPT_FRESH_CONNECT => true,
		]);
		$raw = curl_exec($ch);
		$err = curl_error($ch);
		$errno = curl_errno($ch);
		$info = curl_getinfo($ch);
		$httpCode = intval($info['http_code'] ?? 0);
		curl_close($ch);

		if ($raw === false) {
			$stage = '';
			$dns = floatval($info['namelookup_time'] ?? 0);
			$conn = floatval($info['connect_time'] ?? 0);
			$ssl = floatval($info['appconnect_time'] ?? 0);
			if ($dns <= 0) {
				$stage = 'DNS lookup failed - server cannot resolve the gateway host';
			} elseif ($conn <= 0) {
				$stage = 'TCP connect blocked - outbound firewall on port 443 likely; or gateway needs to whitelist this server\'s IP';
			} elseif ($ssl <= 0) {
				$stage = 'TLS handshake stalled - server cipher / CA bundle issue';
			} else {
				$stage = 'Gateway accepted the connection but did not respond in time - whitelist this server\'s IP at chatmybot';
			}
			$diag = sprintf(' [errno=%d, dns=%.2fs, connect=%.2fs, tls=%.2fs, total=%.2fs]',
				$errno, $dns, $conn, $ssl, floatval($info['total_time'] ?? 0));
			return [
				'ok' => false,
				'http_code' => $httpCode,
				'response' => null,
				'message_ids' => [],
				'error' => ($err !== '' ? $err : 'cURL request failed') . ' | ' . $stage . $diag,
			];
		}

		$decoded = json_decode($raw, true);
		$ok = ($httpCode >= 200 && $httpCode < 300);
		$gatewayMsg = '';
		$messageIds = [];
		if (is_array($decoded)) {
			$status = strtolower(strval($decoded['status'] ?? $decoded['Status'] ?? ''));
			// chatmybot returns "SENT" on success; other gateways may use the values below.
			if ($ok && $status !== '' && !in_array($status, ['success', 'ok', 'accepted', 'queued', 'sent', 'submitted'], true)) {
				$ok = false;
			}
			$gatewayMsg = trim(strval(
				$decoded['message'] ?? $decoded['Message'] ?? $decoded['error'] ?? $decoded['Error'] ?? ''
			));
			if (!empty($decoded['ids']) && is_array($decoded['ids'])) {
				$messageIds = array_values(array_filter(array_map('strval', $decoded['ids'])));
			}
		}

		$errorText = '';
		if (!$ok) {
			$errorText = 'Gateway returned HTTP ' . $httpCode;
			if ($gatewayMsg !== '') {
				$errorText .= ' - ' . $gatewayMsg;
			} else if (is_string($raw) && $raw !== '') {
				$errorText .= ' - ' . substr(trim($raw), 0, 240);
			}
			if ($httpCode === 401 || $httpCode === 403) {
				$errorText .= ' (check whatsapp._ApiToken / _AuthScheme in config.json)';
			}
		}

		return [
			'ok' => $ok,
			'http_code' => $httpCode,
			'response' => $decoded !== null ? $decoded : $raw,
			'message_ids' => $messageIds,
			'error' => $errorText,
		];
	}

	/**
	 * Thin wrapper around sendChatmybotTemplate() for the salary-payslip template.
	 * Template body: "Dear {{1}}, ... Your salary for {{2}} ... download link {{3}}".
	 */
	public function sendPayslipWhatsAppViaTemplate($phone, $empName, $monthTitle, $downloadUrl)
	{
		return $this->sendChatmybotTemplate('salary_payslip', $phone, [
			$empName !== '' ? $empName : 'Team Member',
			$monthTitle,
			$downloadUrl,
		]);
	}

	/**
	 * Format an amount for email (₹ + grouped thousands, 2 decimals).
	 */
	private function formatKpiAmountForEmail($amount)
	{
		return '₹' . number_format(floatval($amount), 2);
	}

	/**
	 * Pick a status label + color for an overall KPI percentage.
	 */
	private function kpiStatusForPct($pct)
	{
		$pct = floatval($pct);
		if ($pct >= 90) {
			return ['label' => 'Excellent', 'color' => '#198754'];
		}
		if ($pct >= 75) {
			return ['label' => 'Good',      'color' => '#0d6efd'];
		}
		if ($pct >= 60) {
			return ['label' => 'Average',   'color' => '#fd7e14'];
		}
		return ['label' => 'Needs Improvement', 'color' => '#dc3545'];
	}

	/**
	 * Build the KPI snapshot block for the payslip HTML email.
	 * Uses table-based, inline-styled HTML for broad email-client support.
	 */
	private function buildKpiSnapshotEmailHtml(array $kpi, $monthTitle)
	{
		$esc = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

		$isExec = intval($kpi['IsExecutive'] ?? 0) === 1;
		$dateFrom = trim($kpi['DateFrom'] ?? '');
		$dateTo = trim($kpi['DateTo'] ?? '');
		$period = '';
		if ($dateFrom !== '' && $dateTo !== '') {
			$period = date('d M Y', strtotime($dateFrom)) . ' &rarr; ' . date('d M Y', strtotime($dateTo));
		}

		$overallPct = floatval($kpi['OverallKpiPct'] ?? 0);
		$status = $this->kpiStatusForPct($overallPct);

		$assignPct     = number_format(floatval($kpi['AssignPerformancePct'] ?? 0), 1);
		$quotePct      = number_format(floatval($kpi['QuotePerformancePct'] ?? 0), 1);
		$closedPct     = number_format(floatval($kpi['ClosedPerformancePct'] ?? 0), 1);
		$attendancePct = number_format(floatval($kpi['AttendancePerformancePct'] ?? 0), 1);
		$salaryPct     = number_format(floatval($kpi['SalaryPct'] ?? 0), 1);
		$overallPctFmt = number_format($overallPct, 1);

		$workingDays = intval($kpi['WorkingDays'] ?? 0);
		$presentDays = intval($kpi['PresentDays'] ?? 0);
		$absentDays  = intval($kpi['AbsentDays'] ?? 0);

		$assignTotal = intval($kpi['AssignTotalTickets'] ?? 0);
		$quoteTotal  = intval($kpi['QuoteTotalTickets'] ?? 0);
		$closedTotal = intval($kpi['ClosedTotal'] ?? 0);

		$fullSalary    = floatval($kpi['FullSalary'] ?? 0);
		$payableSalary = floatval($kpi['PayableSalary'] ?? 0);
		$kpiDeduction  = max(0.0, $fullSalary - $payableSalary);

		$cellTh = 'padding:8px 10px;background:#f1f3f5;border:1px solid #dee2e6;font-size:12px;color:#495057;text-align:left;';
		$cellTd = 'padding:8px 10px;border:1px solid #dee2e6;font-size:13px;color:#212529;';
		$cellTdRight = $cellTd . 'text-align:right;font-weight:600;';
		$wrap = 'width:100%;border-collapse:collapse;margin:6px 0 0 0;font-family:Arial,Helvetica,sans-serif;';

		$html = '';
		$html .= '<div style="margin-top:18px;padding:14px 16px;background:#f8f9fa;border:1px solid #e9ecef;border-radius:6px;font-family:Arial,Helvetica,sans-serif;">';
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-family:Arial,Helvetica,sans-serif;"><tr>';
		$html .= '<td style="font-size:14px;font-weight:bold;color:#212529;">KPI Performance Snapshot &mdash; ' . $esc($monthTitle) . '</td>';
		$html .= '<td style="text-align:right;font-size:12px;color:#6c757d;">' . $period . '</td>';
		$html .= '</tr></table>';

		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" style="' . $wrap . '">';
		$html .= '<tr>';
		$html .= '<td style="' . $cellTd . 'width:55%;">'
			. '<span style="font-size:12px;color:#6c757d;">Overall KPI</span><br>'
			. '<span style="font-size:22px;font-weight:bold;color:' . $status['color'] . ';">' . $overallPctFmt . '%</span> '
			. '<span style="display:inline-block;margin-left:6px;padding:2px 8px;background:' . $status['color'] . ';color:#fff;border-radius:10px;font-size:11px;vertical-align:middle;">' . $esc($status['label']) . '</span>'
			. '</td>';
		$html .= '<td style="' . $cellTd . 'width:45%;">'
			. '<span style="font-size:12px;color:#6c757d;">Salary Impact</span><br>'
			. '<span style="font-size:13px;color:#212529;">Full: <strong>' . $this->formatKpiAmountForEmail($fullSalary) . '</strong></span><br>'
			. '<span style="font-size:13px;color:#dc3545;">KPI Deduction: <strong>-' . $this->formatKpiAmountForEmail($kpiDeduction) . '</strong></span><br>'
			. '<span style="font-size:13px;color:#198754;">Payable: <strong>' . $this->formatKpiAmountForEmail($payableSalary) . '</strong></span>'
			. '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" style="' . $wrap . '">';
		if (!$isExec) {
			$html .= '<tr>'
				. '<th style="' . $cellTh . '">Assignment</th>'
				. '<td style="' . $cellTdRight . '">' . $assignPct . '%</td>'
				. '<th style="' . $cellTh . '">Quotation</th>'
				. '<td style="' . $cellTdRight . '">' . $quotePct . '%</td>'
				. '</tr>';
			$html .= '<tr>'
				. '<th style="' . $cellTh . '">Closed</th>'
				. '<td style="' . $cellTdRight . '">' . $closedPct . '%</td>'
				. '<th style="' . $cellTh . '">Attendance</th>'
				. '<td style="' . $cellTdRight . '">' . $attendancePct . '%</td>'
				. '</tr>';
			$html .= '<tr>'
				. '<th style="' . $cellTh . '">Tickets Assigned</th>'
				. '<td style="' . $cellTdRight . '">' . $assignTotal . '</td>'
				. '<th style="' . $cellTh . '">Quotes / Closed</th>'
				. '<td style="' . $cellTdRight . '">' . $quoteTotal . ' / ' . $closedTotal . '</td>'
				. '</tr>';
		} else {
			$html .= '<tr>'
				. '<th style="' . $cellTh . '">Attendance</th>'
				. '<td style="' . $cellTdRight . '">' . $attendancePct . '%</td>'
				. '<th style="' . $cellTh . '">Salary %</th>'
				. '<td style="' . $cellTdRight . '">' . $salaryPct . '%</td>'
				. '</tr>';
		}
		$html .= '<tr>'
			. '<th style="' . $cellTh . '">Working Days</th>'
			. '<td style="' . $cellTdRight . '">' . $workingDays . '</td>'
			. '<th style="' . $cellTh . '">Present / Absent</th>'
			. '<td style="' . $cellTdRight . '">' . $presentDays . ' / ' . $absentDays . '</td>'
			. '</tr>';
		$html .= '</table>';

		$html .= '<p style="margin:10px 0 0 0;font-size:11px;color:#6c757d;line-height:1.5;">'
			. 'This snapshot reflects your performance for the period above and is the basis for the KPI-linked component of this month\'s salary. '
			. 'For a detailed breakdown of tickets, attendance and calculations, please refer to your salary slip PDF or contact HR.'
			. '</p>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Plain-text variant of the KPI snapshot for the payslip email.
	 */
	private function buildKpiSnapshotEmailPlain(array $kpi, $monthTitle)
	{
		$isExec = intval($kpi['IsExecutive'] ?? 0) === 1;
		$dateFrom = trim($kpi['DateFrom'] ?? '');
		$dateTo = trim($kpi['DateTo'] ?? '');
		$period = '';
		if ($dateFrom !== '' && $dateTo !== '') {
			$period = ' (' . date('d M Y', strtotime($dateFrom)) . ' to ' . date('d M Y', strtotime($dateTo)) . ')';
		}

		$overallPct = floatval($kpi['OverallKpiPct'] ?? 0);
		$status = $this->kpiStatusForPct($overallPct);

		$fullSalary    = floatval($kpi['FullSalary'] ?? 0);
		$payableSalary = floatval($kpi['PayableSalary'] ?? 0);
		$kpiDeduction  = max(0.0, $fullSalary - $payableSalary);

		$lines = [];
		$lines[] = '------------------------------------------------------------';
		$lines[] = 'KPI Performance Snapshot - ' . $monthTitle . $period;
		$lines[] = '------------------------------------------------------------';
		$lines[] = 'Overall KPI       : ' . number_format($overallPct, 1) . '%  (' . $status['label'] . ')';

		if (!$isExec) {
			$lines[] = 'Assignment        : ' . number_format(floatval($kpi['AssignPerformancePct'] ?? 0), 1) . '%';
			$lines[] = 'Quotation         : ' . number_format(floatval($kpi['QuotePerformancePct'] ?? 0), 1) . '%';
			$lines[] = 'Closed            : ' . number_format(floatval($kpi['ClosedPerformancePct'] ?? 0), 1) . '%';
			$lines[] = 'Attendance        : ' . number_format(floatval($kpi['AttendancePerformancePct'] ?? 0), 1) . '%';
			$lines[] = 'Tickets Assigned  : ' . intval($kpi['AssignTotalTickets'] ?? 0);
			$lines[] = 'Quotes / Closed   : ' . intval($kpi['QuoteTotalTickets'] ?? 0) . ' / ' . intval($kpi['ClosedTotal'] ?? 0);
		} else {
			$lines[] = 'Attendance        : ' . number_format(floatval($kpi['AttendancePerformancePct'] ?? 0), 1) . '%';
			$lines[] = 'Salary %          : ' . number_format(floatval($kpi['SalaryPct'] ?? 0), 1) . '%';
		}

		$lines[] = 'Working Days      : ' . intval($kpi['WorkingDays'] ?? 0);
		$lines[] = 'Present / Absent  : ' . intval($kpi['PresentDays'] ?? 0) . ' / ' . intval($kpi['AbsentDays'] ?? 0);
		$lines[] = '';
		$lines[] = 'Salary Impact';
		$lines[] = '  Full Salary     : Rs. ' . number_format($fullSalary, 2);
		$lines[] = '  KPI Deduction   : Rs. -' . number_format($kpiDeduction, 2);
		$lines[] = '  Payable Salary  : Rs. ' . number_format($payableSalary, 2);
		$lines[] = '------------------------------------------------------------';

		return implode("\n", $lines);
	}

	public function sendPayslipToEmployee($slipId, $companyId, $sentBy = '')
	{
		$slipId = intval($slipId);
		$companyId = intval($companyId);
		$data = $this->getSlipWithLines($slipId);
		if ($data === null) {
			return ['error' => true, 'message' => 'Salary slip not found'];
		}
		$company = $this->getQuoteCompanyById($companyId);
		if ($company === null) {
			return ['error' => true, 'message' => 'Invalid company selected'];
		}

		$emp = $data['employee'];
		$slip = $data['slip'];
		$email = trim($emp['PersonalEmail'] ?? '');
		$phone = trim($emp['ContactNumber'] ?? '');
		if ($email === '' && $phone === '') {
			return ['error' => true, 'message' => 'Employee email and contact number are both empty. Update employee profile first.'];
		}

		$downloadUrl = $this->buildSlipShareDownloadUrl($slipId, $companyId);
		if ($downloadUrl === null) {
			return ['error' => true, 'message' => 'No active company configured for payslip share'];
		}

		$monthTitle = date('F Y', mktime(0, 0, 0, intval($slip['PayrollMonth']), 1, intval($slip['PayrollYear'])));
		$empName = trim($emp['Name'] ?? 'Employee');
		$companyName = trim($company['CompanyName'] ?? 'Company');

		$kpiRow = $this->getKpiMonthlySnapshot(
			intval($slip['EmployeeID']),
			intval($slip['PayrollYear']),
			intval($slip['PayrollMonth'])
		);
		$kpiHtml = $kpiRow ? $this->buildKpiSnapshotEmailHtml($kpiRow, $monthTitle) : '';
		$kpiPlain = $kpiRow ? $this->buildKpiSnapshotEmailPlain($kpiRow, $monthTitle) : '';

		$emailSent = false;
		$emailError = '';
		if ($email !== '') {
			require_once dirname(__DIR__) . '/include/send_mail_phpmailer.php';
			$conf = new Conf();
			$subject = 'Salary approved — ' . $monthTitle . ' — ' . $companyName;
			$plain = "Dear " . $empName . ",\n\n"
				. "Greetings from " . $companyName . ".\n\n"
				. "Your salary for " . $monthTitle . " has been processed and approved.\n\n"
				. "Download your salary slip for this month using the secure link below:\n"
				. $downloadUrl . "\n\n"
				. "This link is valid for 30 days and is for your personal use only.\n\n"
				. ($kpiPlain !== '' ? $kpiPlain . "\n" : '')
				. "If you have any concern, please contact the HR department.\n\n"
				. "Regards,\n" . ($conf->_MailSignature ?? 'HR Team');
			$html = '<p>Dear ' . htmlspecialchars($empName, ENT_QUOTES, 'UTF-8') . ',</p>'
				. '<p>Greetings from <strong>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
				. '<p>Your salary for <strong>' . htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8') . '</strong> has been processed and <strong>approved</strong>.</p>'
				. '<p>Please download your salary slip for this month using the button below:</p>'
				. '<p><a href="' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:4px;">Download salary slip (PDF)</a></p>'
				. '<p style="font-size:12px;color:#666;">This secure link expires in 30 days. If you have any concern, please contact the HR department.</p>'
				. $kpiHtml
				. '<p>Regards,<br>' . htmlspecialchars($conf->_MailSignature ?? 'HR Team', ENT_QUOTES, 'UTF-8') . '</p>';
			$emailSent = sendMailViaSMTP($email, $subject, $plain, $html, $empName, $emailError);
		}

		$whatsappSent = false;
		$whatsappError = '';
		$whatsappResponse = null;
		$whatsappMessageIds = [];
		if ($phone !== '') {
			$waResult = $this->sendPayslipWhatsAppViaTemplate($phone, $empName, $monthTitle, $downloadUrl);
			$whatsappSent = !empty($waResult['ok']);
			$whatsappError = $waResult['error'] ?? '';
			$whatsappResponse = $waResult['response'] ?? null;
			$whatsappMessageIds = $waResult['message_ids'] ?? [];
		}

		$parts = [];
		if ($emailSent) {
			$parts[] = 'email sent to ' . $email;
		} elseif ($email !== '') {
			$parts[] = 'email failed' . ($emailError !== '' ? ': ' . $emailError : '');
		}
		if ($whatsappSent) {
			$idHint = !empty($whatsappMessageIds) ? ' [ref ' . substr($whatsappMessageIds[0], 0, 8) . ']' : '';
			$parts[] = 'WhatsApp template sent to ' . $phone . $idHint;
		} elseif ($phone !== '') {
			$parts[] = 'WhatsApp template failed' . ($whatsappError !== '' ? ': ' . $whatsappError : '');
		}

		return [
			'error' => false,
			'message' => 'Salary slip shared. ' . implode('; ', $parts),
			'download_url' => $downloadUrl,
			'whatsapp_sent' => $whatsappSent,
			'whatsapp_error' => $whatsappError,
			'whatsapp_response' => $whatsappResponse,
			'whatsapp_message_ids' => $whatsappMessageIds,
			'email_sent' => $emailSent,
			'employee_email' => $email,
			'employee_phone' => $phone,
		];
	}

	public function listSlipsForPeriod($year, $month)
	{
		$year = intval($year);
		$month = intval($month);
		$sql = "SELECT s.ID, s.EmployeeID, s.GrossSalary, s.TotalDeductions, s.NetSalary, s.PaidDays, s.TotalDays,
				e.Name, e.EmployeeNumber, e.Designation, e.Department, e.Email, e.ContactNumber
			FROM hrms_salary_slip s
			INNER JOIN employees e ON e.ID = s.EmployeeID
			WHERE s.PayrollYear = $year AND s.PayrollMonth = $month
			ORDER BY e.Name ASC";
		$rows = [];
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	public function listEmployeesWithSlipSummary($year, $month)
	{
		$year = intval($year);
		$month = intval($month);
		$sql = "SELECT e.ID, e.Name, e.EmployeeNumber, e.Designation, e.Department, e.Email, e.ContactNumber, e.ProfileImage,
				e.ApplyEPF, e.ApplyESI,
				s.ID AS SalarySlipID, s.NetSalary, s.GrossSalary, s.TotalDeductions
			FROM employees e
			LEFT JOIN hrms_salary_slip s
				ON s.EmployeeID = e.ID AND s.PayrollYear = $year AND s.PayrollMonth = $month
			WHERE IFNULL(e.IsActive,1) = 1
			ORDER BY e.Name ASC";
		$rows = [];
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	public function listEmployeeSlips($employeeId, $limit = 12)
	{
		$employeeId = intval($employeeId);
		$limit = intval($limit);
		if ($limit <= 0) $limit = 12;
		$sql = "SELECT ID, PayrollYear, PayrollMonth, GrossSalary, TotalDeductions, NetSalary, PaidDays, TotalDays
			FROM hrms_salary_slip
			WHERE EmployeeID = $employeeId
			ORDER BY PayrollYear DESC, PayrollMonth DESC
			LIMIT $limit";
		$rows = [];
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	public function getSlipWithLines($slipId)
	{
		$slipId = intval($slipId);
		$slip = $this->_getTableDetails($this->conn, 'hrms_salary_slip', "WHERE ID = $slipId");
		if (empty($slip['ID'])) {
			return null;
		}
		$emp = $this->_getTableDetails($this->conn, 'employees', 'WHERE ID = ' . intval($slip['EmployeeID']));
		$lines = $this->_getTableRecords($this->conn, 'hrms_salary_slip_line', "WHERE SalarySlipID = $slipId ORDER BY SortOrder ASC, ID ASC");
		return ['slip' => $slip, 'employee' => $emp, 'lines' => $lines];
	}

	/** Active companies from quote_company_details (for payslip letterhead). */
	public function listQuoteCompanies()
	{
		return $this->_getTableRecords($this->conn, 'quote_company_details', 'WHERE IFNULL(IsActive,1)=1 ORDER BY CompanyName ASC');
	}

	public function getQuoteCompanyById($companyId)
	{
		$companyId = intval($companyId);
		if ($companyId <= 0) {
			return null;
		}
		$row = $this->_getTableDetails($this->conn, 'quote_company_details', "WHERE ID = $companyId AND IFNULL(IsActive,1)=1");
		return !empty($row['ID']) ? $row : null;
	}

	/** Month attendance breakdown for payslip display. */
	public function getPayslipAttendanceSummary($employeeId, $year, $month)
	{
		$employeeId = intval($employeeId);
		$year = intval($year);
		$month = intval($month);
		$emp = $this->_getTableDetails($this->conn, 'employees', "WHERE ID = $employeeId");
		if (empty($emp['ID'])) {
			return null;
		}
		$monthStart = sprintf('%04d-%02d-01', $year, $month);
		$totalDays = intval(date('t', strtotime($monthStart)));
		$stats = $this->getPayrollPaidDaysStats($emp, $year, $month);
		$otherPaid = floatval($stats['leave_days']) + floatval($stats['holiday_days']) + floatval($stats['weekly_off_days']);
		$absentDays = max(0, $totalDays - floatval($stats['present_days']) - $otherPaid);

		return [
			'total_days' => $totalDays,
			'present_days' => floatval($stats['present_days']),
			'leave_days' => floatval($stats['leave_days']),
			'holiday_days' => floatval($stats['holiday_days']),
			'weekly_off_days' => floatval($stats['weekly_off_days']),
			'paid_days' => floatval($stats['paid_days']),
			'absent_days' => $absentDays,
		];
	}
}
