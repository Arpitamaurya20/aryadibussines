<?php
/**
 * Employee KPI monthly snapshot data layer.
 *
 * Provides list / get / update / delete / top / bottom queries over the
 * employee_kpi_monthly_snapshot table. UI lives in /hrms/hrms-kpi/.
 */
class Employeekpi extends Core
{
	private $conn;

	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	/* ----------------------------------------------------------------------
	 * Discovery helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Distinct (year, month) pairs that have at least one snapshot row.
	 * Sorted newest first. Used to populate the period dropdown.
	 */
	public function listAvailablePeriods()
	{
		$rows = [];
		// `Rows` is a reserved word in MySQL 8+, so use a safe alias here.
		$sql = "SELECT KpiYear, KpiMonth, COUNT(*) AS SnapshotCount
			FROM employee_kpi_monthly_snapshot
			GROUP BY KpiYear, KpiMonth
			ORDER BY KpiYear DESC, KpiMonth DESC";
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			while ($r = mysqli_fetch_assoc($res)) {
				$rows[] = [
					'year' => intval($r['KpiYear']),
					'month' => intval($r['KpiMonth']),
					'label' => date('F Y', mktime(0, 0, 0, intval($r['KpiMonth']), 1, intval($r['KpiYear']))),
					'rows' => intval($r['SnapshotCount']),
				];
			}
		}
		return $rows;
	}

	/* ----------------------------------------------------------------------
	 * Listing / filtering
	 * ------------------------------------------------------------------- */

	/**
	 * Snapshots for a given year/month with optional filters.
	 *
	 * Filters:
	 *   - search        substring matched against EmployeeName / Designation / ContactNumber
	 *   - role          'executive' | 'non_executive' | ''
	 *   - min_overall   only rows where OverallKpiPct >= value
	 *   - max_overall   only rows where OverallKpiPct <= value
	 *   - whatsapp      'sent' | 'pending' | ''
	 *
	 * @return array list of associative rows from the snapshot table.
	 */
	public function listSnapshots($year, $month, array $filters = [])
	{
		$year = intval($year);
		$month = intval($month);
		if ($month < 1 || $month > 12) {
			return [];
		}

		$where = ["KpiYear = $year", "KpiMonth = $month"];

		$search = trim(strval($filters['search'] ?? ''));
		if ($search !== '') {
			$esc = mysqli_real_escape_string($this->conn, $search);
			$where[] = "(EmployeeName LIKE '%$esc%' OR Designation LIKE '%$esc%' OR ContactNumber LIKE '%$esc%')";
		}

		$role = strtolower(trim(strval($filters['role'] ?? '')));
		if ($role === 'executive') {
			$where[] = 'IsExecutive = 1';
		} elseif ($role === 'non_executive') {
			$where[] = 'IsExecutive = 0';
		}

		if (isset($filters['min_overall']) && $filters['min_overall'] !== '') {
			$where[] = 'OverallKpiPct >= ' . floatval($filters['min_overall']);
		}
		if (isset($filters['max_overall']) && $filters['max_overall'] !== '') {
			$where[] = 'OverallKpiPct <= ' . floatval($filters['max_overall']);
		}

		$wa = strtolower(trim(strval($filters['whatsapp'] ?? '')));
		if ($wa === 'sent') {
			$where[] = 'WhatsappSent = 1';
		} elseif ($wa === 'pending') {
			$where[] = 'WhatsappSent = 0';
		}

		$sql = "SELECT * FROM employee_kpi_monthly_snapshot
			WHERE " . implode(' AND ', $where) . "
			ORDER BY OverallKpiPct DESC, EmployeeName ASC";

		$rows = [];
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			while ($r = mysqli_fetch_assoc($res)) {
				$rows[] = $r;
			}
		}
		return $rows;
	}

	public function getSnapshotById($id)
	{
		$id = intval($id);
		if ($id <= 0) {
			return null;
		}
		$res = mysqli_query($this->conn, "SELECT * FROM employee_kpi_monthly_snapshot WHERE ID = $id LIMIT 1");
		if ($res && mysqli_num_rows($res) > 0) {
			return mysqli_fetch_assoc($res);
		}
		return null;
	}

	/* ----------------------------------------------------------------------
	 * Top / bottom performers
	 * ------------------------------------------------------------------- */

	/**
	 * Highest OverallKpiPct rows in the given month. Limit defaults to 5.
	 */
	public function getTopPerformers($year, $month, $limit = 5)
	{
		return $this->getPerformersOrdered($year, $month, 'DESC', $limit);
	}

	/**
	 * Lowest OverallKpiPct rows in the given month. Limit defaults to 5.
	 * Rows with OverallKpiPct = 0 are excluded so we don't pollute the list
	 * with employees who never had any tickets assigned that month.
	 */
	public function getLowestPerformers($year, $month, $limit = 5)
	{
		return $this->getPerformersOrdered($year, $month, 'ASC', $limit, true);
	}

	private function getPerformersOrdered($year, $month, $direction, $limit, $excludeZero = false)
	{
		$year = intval($year);
		$month = intval($month);
		$limit = max(1, intval($limit));
		$direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

		$where = ["KpiYear = $year", "KpiMonth = $month"];
		if ($excludeZero) {
			$where[] = 'OverallKpiPct > 0';
		}

		$sql = "SELECT ID, EmployeeID, EmployeeName, Designation, IsExecutive,
				OverallKpiPct, AssignPerformancePct, QuotePerformancePct, ClosedPerformancePct,
				AttendancePerformancePct, SalaryPct, PayableSalary, FullSalary,
				PresentDays, AbsentDays, WorkingDays
			FROM employee_kpi_monthly_snapshot
			WHERE " . implode(' AND ', $where) . "
			ORDER BY OverallKpiPct $direction, EmployeeName ASC
			LIMIT $limit";

		$rows = [];
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			while ($r = mysqli_fetch_assoc($res)) {
				$rows[] = $r;
			}
		}
		return $rows;
	}

	/* ----------------------------------------------------------------------
	 * Monthly summary (KPIs at a glance)
	 * ------------------------------------------------------------------- */

	public function getMonthSummary($year, $month)
	{
		$year = intval($year);
		$month = intval($month);
		$sql = "SELECT
				COUNT(*)                                       AS TotalRows,
				SUM(CASE WHEN IsExecutive = 1 THEN 1 ELSE 0 END) AS Execs,
				SUM(CASE WHEN IsExecutive = 0 THEN 1 ELSE 0 END) AS NonExecs,
				ROUND(AVG(OverallKpiPct), 2)                   AS AvgOverall,
				ROUND(AVG(AttendancePerformancePct), 2)        AS AvgAttendance,
				ROUND(MAX(OverallKpiPct), 2)                   AS MaxOverall,
				ROUND(MIN(CASE WHEN OverallKpiPct > 0 THEN OverallKpiPct END), 2) AS MinOverall,
				SUM(CASE WHEN OverallKpiPct >= 90 THEN 1 ELSE 0 END) AS RowsExcellent,
				SUM(CASE WHEN OverallKpiPct >= 75 AND OverallKpiPct < 90 THEN 1 ELSE 0 END) AS RowsGood,
				SUM(CASE WHEN OverallKpiPct >= 60 AND OverallKpiPct < 75 THEN 1 ELSE 0 END) AS RowsAverage,
				SUM(CASE WHEN OverallKpiPct > 0 AND OverallKpiPct < 60 THEN 1 ELSE 0 END)   AS RowsNeedsImp,
				SUM(WhatsappSent)                              AS WaSent,
				ROUND(SUM(FullSalary), 2)                      AS SumFullSalary,
				ROUND(SUM(PayableSalary), 2)                   AS SumPayableSalary
			FROM employee_kpi_monthly_snapshot
			WHERE KpiYear = $year AND KpiMonth = $month";
		$res = mysqli_query($this->conn, $sql);
		$row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : [];

		$totals = [
			'total_rows'      => intval($row['TotalRows'] ?? 0),
			'executives'      => intval($row['Execs'] ?? 0),
			'non_executives'  => intval($row['NonExecs'] ?? 0),
			'avg_overall'     => floatval($row['AvgOverall'] ?? 0),
			'avg_attendance'  => floatval($row['AvgAttendance'] ?? 0),
			'max_overall'     => floatval($row['MaxOverall'] ?? 0),
			'min_overall'     => floatval($row['MinOverall'] ?? 0),
			'rows_excellent'  => intval($row['RowsExcellent'] ?? 0),
			'rows_good'       => intval($row['RowsGood'] ?? 0),
			'rows_average'    => intval($row['RowsAverage'] ?? 0),
			'rows_needs_imp'  => intval($row['RowsNeedsImp'] ?? 0),
			'whatsapp_sent'   => intval($row['WaSent'] ?? 0),
			'sum_full_salary' => floatval($row['SumFullSalary'] ?? 0),
			'sum_payable'     => floatval($row['SumPayableSalary'] ?? 0),
		];
		$totals['kpi_deduction'] = round($totals['sum_full_salary'] - $totals['sum_payable'], 2);
		return $totals;
	}

	/* ----------------------------------------------------------------------
	 * Mutations
	 * ------------------------------------------------------------------- */

	/**
	 * Columns the HRMS portal is allowed to inline-edit. Anything else in
	 * the payload is ignored to keep the audit / ticket-count fields safe.
	 */
	private function editableColumns()
	{
		return [
			'EmployeeName'             => 'string',
			'Designation'              => 'string',
			'ContactNumber'            => 'string',
			'IsExecutive'              => 'bool',
			'DateFrom'                 => 'date',
			'DateTo'                   => 'date',
			'AssignTotalTickets'       => 'int',
			'AssignWithin1Hour'        => 'int',
			'AssignAfter1Hour'         => 'int',
			'AssignReassignCount'      => 'int',
			'AssignPerformancePct'     => 'pct',
			'QuoteTotalTickets'        => 'int',
			'QuoteWithin48Hour'        => 'int',
			'QuoteAfter48Hour'         => 'int',
			'QuotePending'             => 'int',
			'QuoteNotApproved48'       => 'int',
			'QuoteAmcTickets'          => 'int',
			'QuotePerformancePct'      => 'pct',
			'ClosedTotal'              => 'int',
			'ClosedWithin24'           => 'int',
			'ClosedAfter24'            => 'int',
			'ClosingTarget'            => 'int',
			'ClosedPerformancePct'     => 'pct',
			'WorkingDays'              => 'int',
			'PresentDays'              => 'int',
			'AbsentDays'               => 'int',
			'AttendancePerformancePct' => 'pct',
			'OverallKpiPct'            => 'pct',
			'FullSalary'               => 'money',
			'PayableSalary'            => 'money',
			'SalaryPct'                => 'pct',
		];
	}

	/**
	 * Update an editable subset of fields on a single snapshot.
	 * Returns ['error' => bool, 'message' => string].
	 */
	public function updateSnapshot($id, array $data)
	{
		$id = intval($id);
		if ($id <= 0) {
			return ['error' => true, 'message' => 'Invalid snapshot ID'];
		}
		$existing = $this->getSnapshotById($id);
		if (!$existing) {
			return ['error' => true, 'message' => 'KPI snapshot not found'];
		}

		$editable = $this->editableColumns();
		$sets = [];
		foreach ($data as $col => $val) {
			if (!isset($editable[$col])) {
				continue;
			}
			switch ($editable[$col]) {
				case 'int':
					$sets[] = "`$col` = " . intval($val);
					break;
				case 'bool':
					$sets[] = "`$col` = " . (!empty($val) && $val !== '0' && $val !== 'false' ? 1 : 0);
					break;
				case 'pct':
					$f = floatval($val);
					if ($f < 0) { $f = 0; }
					if ($f > 100) { $f = 100; }
					$sets[] = "`$col` = " . $f;
					break;
				case 'money':
					$sets[] = "`$col` = " . max(0, floatval($val));
					break;
				case 'date':
					$d = trim((string) $val);
					if ($d === '') {
						$sets[] = "`$col` = NULL";
					} else {
						$ts = strtotime($d);
						$sets[] = $ts ? "`$col` = '" . date('Y-m-d', $ts) . "'" : "`$col` = '$d'";
					}
					break;
				case 'string':
				default:
					$esc = mysqli_real_escape_string($this->conn, (string) $val);
					$sets[] = "`$col` = '$esc'";
					break;
			}
		}

		if (empty($sets)) {
			return ['error' => true, 'message' => 'No editable fields supplied'];
		}

		$sql = "UPDATE employee_kpi_monthly_snapshot SET " . implode(', ', $sets) . " WHERE ID = $id";
		$ok = mysqli_query($this->conn, $sql);
		if (!$ok) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}
		return ['error' => false, 'message' => 'Snapshot updated', 'id' => $id];
	}

	public function deleteSnapshot($id)
	{
		$id = intval($id);
		if ($id <= 0) {
			return ['error' => true, 'message' => 'Invalid snapshot ID'];
		}
		$ok = mysqli_query($this->conn, "DELETE FROM employee_kpi_monthly_snapshot WHERE ID = $id");
		if (!$ok) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}
		return ['error' => false, 'message' => 'Snapshot deleted'];
	}

	/* ----------------------------------------------------------------------
	 * Presentation helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Status label + Bootstrap colour class for an OverallKpiPct value.
	 */
	public function statusForPct($pct)
	{
		$pct = floatval($pct);
		if ($pct >= 90) {
			return ['label' => 'Excellent', 'badge' => 'bg-success'];
		}
		if ($pct >= 75) {
			return ['label' => 'Good', 'badge' => 'bg-primary'];
		}
		if ($pct >= 60) {
			return ['label' => 'Average', 'badge' => 'bg-warning text-dark'];
		}
		if ($pct > 0) {
			return ['label' => 'Needs Improvement', 'badge' => 'bg-danger'];
		}
		return ['label' => 'No Data', 'badge' => 'bg-secondary'];
	}
}
