<?php

/**
 * Employee leave management — policy engine, balances, sandwich rule, comp-off, advance leave.
 */
class Employeeleavemgmt extends Core
{
    private $conn;
    private $policyCache = null;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    private function leaveColumnExists($columnName)
    {
        static $columns = null;
        if ($columns === null) {
            $columns = [];
            $res = mysqli_query($this->conn, 'SHOW COLUMNS FROM employee_leave');
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $columns[$row['Field']] = true;
                }
            }
        }
        return !empty($columns[$columnName]);
    }

    private function hasExtendedLeaveColumns()
    {
        return $this->leaveColumnExists('LeaveDays');
    }

    private function balanceTableExists()
    {
        static $exists = null;
        if ($exists === null) {
            $res = mysqli_query($this->conn, "SHOW TABLES LIKE 'employee_leave_monthly_balance'");
            $exists = ($res && $res->num_rows > 0);
        }
        return $exists;
    }

    public function resolveLeaveDays(array $leave)
    {
        if (isset($leave['LeaveDays']) && (float) $leave['LeaveDays'] > 0) {
            return (float) $leave['LeaveDays'];
        }
        $employeeId = (int) ($leave['EmployeeID'] ?? 0);
        $from = (string) ($leave['FromDate'] ?? '');
        $to = (string) ($leave['ToDate'] ?? '');
        $duration = (string) ($leave['Duration'] ?? 'Full Day');
        if ($employeeId > 0 && $from !== '' && $to !== '') {
            $calc = $this->calculateLeaveDays(
                $employeeId,
                $from,
                $to,
                $duration,
                (string) ($leave['HalfDaySession'] ?? '')
            );
            if (empty($calc['error'])) {
                return (float) $calc['total_days'];
            }
        }
        return 1.0;
    }

    private function userFacingDbError($mysqliMessage)
    {
        $msg = trim((string) $mysqliMessage);
        if ($msg === '') {
            return 'Could not save leave. Please try again.';
        }
        if (stripos($msg, 'Unknown column') !== false) {
            return 'Leave module database is not fully installed. Run admin/employee-leave-mgmt/sql/install_employee_leave_mgmt.sql';
        }
        return $msg;
    }

    public static function leaveTypes()
    {
        return ['CL', 'SL', 'COMPOFF', 'UNPAID'];
    }

    /** Leave types that do not deduct CL/SL/comp-off balance. */
    private function skipsBalanceTracking($type)
    {
        return in_array(strtoupper(trim((string) $type)), ['COMPOFF', 'UNPAID'], true);
    }

    public function getPolicy($key = null, $default = '')
    {
        if ($this->policyCache === null) {
            $this->policyCache = [];
            $res = mysqli_query($this->conn, 'SELECT setting_key, setting_value FROM leave_policy_settings');
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $this->policyCache[$row['setting_key']] = $row['setting_value'];
                }
            }
        }
        if ($key === null) {
            return $this->policyCache;
        }
        return isset($this->policyCache[$key]) ? $this->policyCache[$key] : $default;
    }

    public function policyBool($key, $default = false)
    {
        $v = strtolower(trim((string) $this->getPolicy($key, $default ? '1' : '0')));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    public function policyFloat($key, $default = 0.0)
    {
        return (float) $this->getPolicy($key, (string) $default);
    }

    public function policyInt($key, $default = 0)
    {
        return (int) $this->getPolicy($key, (string) $default);
    }

    public function leaveYearForDate($dateStr)
    {
        $ts = strtotime($dateStr);
        if ($ts === false) {
            $ts = time();
        }
        $year = (int) date('Y', $ts);
        $month = (int) date('n', $ts);
        $startMonth = max(1, min(12, $this->policyInt('leave_year_start_month', 4)));
        if ($month < $startMonth) {
            $year--;
        }
        return $year;
    }

    /** Calendar year/month for monthly balance ledger rows. */
    public function calendarYearMonthForDate($dateStr)
    {
        $ts = strtotime($dateStr);
        if ($ts === false) {
            $ts = time();
        }
        return [
            'year' => (int) date('Y', $ts),
            'month' => (int) date('n', $ts),
        ];
    }

    public function financialYearStartMonth()
    {
        return max(1, min(12, $this->policyInt('leave_year_start_month', 4)));
    }

    public function financialYearStartDate($leaveYear)
    {
        $leaveYear = (int) $leaveYear;
        $m = $this->financialYearStartMonth();
        return sprintf('%04d-%02d-01', $leaveYear, $m);
    }

    public function financialYearEndDate($leaveYear)
    {
        $leaveYear = (int) $leaveYear;
        $m = $this->financialYearStartMonth();
        $endTs = strtotime(sprintf('%04d-%02d-01', $leaveYear + 1, $m)) - 86400;
        return date('Y-m-d', $endTs);
    }

    public function financialYearLabel($leaveYear)
    {
        $leaveYear = (int) $leaveYear;
        return sprintf('FY %d-%02d', $leaveYear, ($leaveYear + 1) % 100);
    }

    public function financialYearMeta($asOfDate = null)
    {
        if ($asOfDate === null) {
            $asOfDate = date('Y-m-d');
        }
        $leaveYear = $this->leaveYearForDate($asOfDate);
        $startMonth = $this->financialYearStartMonth();
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        return [
            'financial_year' => $leaveYear,
            'financial_year_label' => $this->financialYearLabel($leaveYear),
            'financial_year_start' => $this->financialYearStartDate($leaveYear),
            'financial_year_end' => $this->financialYearEndDate($leaveYear),
            'financial_year_start_month' => $startMonth,
            'financial_year_start_month_name' => $monthNames[$startMonth] ?? 'April',
            'year_type' => 'financial',
        ];
    }

    public function parseWeeklyOffDays($weeklyOffRaw)
    {
        $map = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6,
        ];
        $weeklyOffRaw = trim(strtolower((string) $weeklyOffRaw));
        if ($weeklyOffRaw === '') {
            return [0];
        }
        $parts = preg_split('/[\s,\/|&-]+/', $weeklyOffRaw);
        $days = [];
        foreach ($parts as $part) {
            if ($part !== '' && isset($map[$part])) {
                $days[] = $map[$part];
            }
        }
        $days = array_values(array_unique($days));
        return !empty($days) ? $days : [0];
    }

    public function getHolidayMap($fromDate, $toDate)
    {
        $map = [];
        $from = mysqli_real_escape_string($this->conn, $fromDate);
        $to = mysqli_real_escape_string($this->conn, $toDate);
        $sql = "SELECT HolidaysDate, HolidaysName FROM listofholidays
            WHERE IFNULL(IsActive,1)=1 AND HolidaysDate >= '$from' AND HolidaysDate <= '$to'";
        $res = mysqli_query($this->conn, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $map[$row['HolidaysDate']] = $row['HolidaysName'] ?? 'Holiday';
            }
        }
        return $map;
    }

    public function getEmployeeWeeklyOff($employeeId)
    {
        $employeeId = (int) $employeeId;
        $stmt = $this->conn->prepare('SELECT WeeklyOff FROM employees WHERE ID = ? LIMIT 1');
        if (!$stmt) {
            return [0];
        }
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $this->parseWeeklyOffDays($row['WeeklyOff'] ?? 'Sunday');
    }

    public function isWeeklyOffDate($dateStr, array $weeklyOffDays)
    {
        $w = (int) date('w', strtotime($dateStr));
        return in_array($w, $weeklyOffDays, true);
    }

    public function dayType($dateStr, array $weeklyOffDays, array $holidayMap)
    {
        if (isset($holidayMap[$dateStr])) {
            return 'holiday';
        }
        if ($this->isWeeklyOffDate($dateStr, $weeklyOffDays)) {
            return 'weekly_off';
        }
        return 'working';
    }

    /**
     * @return array{dates: string[], working_days: float, sandwich_extra: float, breakdown: array}
     */
    public function calculateLeaveDays($employeeId, $fromDate, $toDate, $duration, $halfDaySession = '')
    {
        $employeeId = (int) $employeeId;
        $fromTs = strtotime($fromDate);
        $toTs = strtotime($toDate);
        if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
            return ['error' => true, 'message' => 'Invalid date range.'];
        }

        $duration = strtolower(trim((string) $duration));
        $isHalf = ($duration !== '' && strpos($duration, 'half') !== false);
        if ($isHalf && $fromDate !== $toDate) {
            return ['error' => true, 'message' => 'Half-day leave must be for a single date.'];
        }
        if ($isHalf && !$this->policyBool('half_day_enabled', true)) {
            return ['error' => true, 'message' => 'Half-day leave is not enabled by company policy.'];
        }

        $weeklyOff = $this->getEmployeeWeeklyOff($employeeId);
        $padFrom = date('Y-m-d', strtotime('-7 days', $fromTs));
        $padTo = date('Y-m-d', strtotime('+7 days', $toTs));
        $holidayMap = $this->getHolidayMap($padFrom, $padTo);

        $dates = [];
        for ($ts = $fromTs; $ts <= $toTs; $ts += 86400) {
            $dates[] = date('Y-m-d', $ts);
        }

        $workingDays = 0.0;
        $breakdown = [];
        foreach ($dates as $d) {
            $type = $this->dayType($d, $weeklyOff, $holidayMap);
            if ($type === 'working') {
                $workingDays += $isHalf ? 0.5 : 1.0;
                $breakdown[] = ['date' => $d, 'type' => 'leave', 'days' => $isHalf ? 0.5 : 1.0];
            } else {
                $breakdown[] = ['date' => $d, 'type' => $type, 'days' => 0];
            }
        }

        $sandwichExtra = 0.0;
        if ($this->policyBool('sandwich_rule_enabled', true) && !$isHalf && count($dates) >= 1) {
            $sandwichExtra = $this->computeSandwichExtraDays(
                $fromDate,
                $toDate,
                $weeklyOff,
                $holidayMap
            );
        }

        $totalDays = $workingDays + $sandwichExtra;
        if ($totalDays <= 0) {
            return ['error' => true, 'message' => 'No working days in selected range. Choose working days only or enable sandwich rule.'];
        }

        return [
            'error' => false,
            'dates' => $dates,
            'working_days' => $workingDays,
            'sandwich_extra' => $sandwichExtra,
            'total_days' => $totalDays,
            'is_half_day' => $isHalf,
            'breakdown' => $breakdown,
        ];
    }

    private function computeSandwichExtraDays($fromDate, $toDate, array $weeklyOff, array $holidayMap)
    {
        $extra = 0.0;
        $includeWo = $this->policyBool('sandwich_include_weekly_off', true);
        $includeHol = $this->policyBool('sandwich_include_holidays', true);

        $fromTs = strtotime($fromDate);
        $toTs = strtotime($toDate);

        // Days immediately before fromDate until previous working day
        $before = [];
        for ($ts = $fromTs - 86400; $ts >= $fromTs - (14 * 86400); $ts -= 86400) {
            $d = date('Y-m-d', $ts);
            $type = $this->dayType($d, $weeklyOff, $holidayMap);
            if ($type === 'working') {
                break;
            }
            if (($type === 'weekly_off' && $includeWo) || ($type === 'holiday' && $includeHol)) {
                $before[] = $d;
            } else {
                break;
            }
        }

        // Days immediately after toDate until next working day
        $after = [];
        for ($ts = $toTs + 86400; $ts <= $toTs + (14 * 86400); $ts += 86400) {
            $d = date('Y-m-d', $ts);
            $type = $this->dayType($d, $weeklyOff, $holidayMap);
            if ($type === 'working') {
                break;
            }
            if (($type === 'weekly_off' && $includeWo) || ($type === 'holiday' && $includeHol)) {
                $after[] = $d;
            } else {
                break;
            }
        }

        // Sandwich between leave block: non-working days inside [from, to]
        $between = 0.0;
        for ($ts = $fromTs; $ts <= $toTs; $ts += 86400) {
            $d = date('Y-m-d', $ts);
            $type = $this->dayType($d, $weeklyOff, $holidayMap);
            if ($type === 'weekly_off' && $includeWo) {
                $between += 1.0;
            } elseif ($type === 'holiday' && $includeHol) {
                $between += 1.0;
            }
        }

        $extra = (float) count($before) + (float) count($after) + $between;
        return max(0.0, $extra);
    }

    public function ensureMonthlyBalance($employeeId, $calendarYear, $calendarMonth)
    {
        if (!$this->balanceTableExists()) {
            return;
        }
        $employeeId = (int) $employeeId;
        $calendarYear = (int) $calendarYear;
        $calendarMonth = (int) $calendarMonth;
        if ($employeeId < 1 || $calendarMonth < 1 || $calendarMonth > 12) {
            return;
        }
        $this->syncAccruedBalancesThrough(
            $employeeId,
            sprintf('%04d-%02d-01', $calendarYear, $calendarMonth)
        );
    }

    public function getEmployeeJoiningDate($employeeId)
    {
        $employeeId = (int) $employeeId;
        if ($employeeId < 1) {
            return null;
        }
        $stmt = $this->conn->prepare('SELECT DateofJoining FROM employees WHERE ID = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $join = trim((string) ($row['DateofJoining'] ?? ''));
        if ($join === '' || $join === '0000-00-00') {
            return null;
        }
        $ts = strtotime($join);
        return ($ts !== false) ? date('Y-m-d', $ts) : null;
    }

    private function accrualPeriodStartDate($employeeId, $asOfDate)
    {
        $leaveYear = $this->leaveYearForDate($asOfDate);
        $yearStartDate = $this->financialYearStartDate($leaveYear);

        $join = $this->getEmployeeJoiningDate($employeeId);
        if ($join !== null) {
            $joinMonthStart = date('Y-m-01', strtotime($join));
            if (strtotime($joinMonthStart) > strtotime($yearStartDate)) {
                return $joinMonthStart;
            }
        }
        return $yearStartDate;
    }

    private function isEmployedInMonth($employeeId, $year, $month)
    {
        $join = $this->getEmployeeJoiningDate($employeeId);
        if ($join === null) {
            return true;
        }
        $monthEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
        return strtotime($join) <= strtotime($monthEnd);
    }

    /**
     * Build month-by-month balance from joining date (or leave-year start) through as-of date.
     * Example: FY starts April — joined previous year, no leave → by June shows accrued months from Apr.
     */
    public function syncAccruedBalancesThrough($employeeId, $asOfDate)
    {
        if (!$this->balanceTableExists()) {
            return;
        }
        $employeeId = (int) $employeeId;
        if ($employeeId < 1) {
            return;
        }

        $startDate = $this->accrualPeriodStartDate($employeeId, $asOfDate);
        $endDate = date('Y-m-01', strtotime($asOfDate));
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        if ($startTs === false || $endTs === false || $startTs > $endTs) {
            return;
        }

        for ($ts = $startTs; $ts <= $endTs; $ts = strtotime('+1 month', $ts)) {
            $year = (int) date('Y', $ts);
            $month = (int) date('n', $ts);
            if (!$this->isEmployedInMonth($employeeId, $year, $month)) {
                continue;
            }
            foreach (['CL', 'SL'] as $type) {
                $this->upsertMonthBalanceRow($employeeId, $year, $month, $type);
            }
            $this->ensureAnnualSummary($employeeId, $this->leaveYearForDate(date('Y-m-d', $ts)));
        }
    }

    private function upsertMonthBalanceRow($employeeId, $year, $month, $type)
    {
        $quotaKey = strtolower($type) . '_monthly_quota';
        $entitled = $this->policyFloat($quotaKey, 1.0);
        $carriedIn = 0.0;
        if ($this->policyBool('carry_forward_enabled', true)) {
            $carriedIn = $this->computeCarryIn($employeeId, $year, $month, $type);
        }
        $closing = max(0, $entitled + $carriedIn);
        $closing = $this->capClosingByAnnualQuota($employeeId, $year, $month, $type, $closing);

        $stmt = $this->conn->prepare(
            'SELECT id, monthly_entitled, carried_in, used, pending_reserved FROM employee_leave_monthly_balance
             WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ? LIMIT 1'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('iiis', $employeeId, $year, $month, $type);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($existing) {
            $used = (float) $existing['used'];
            $pending = (float) $existing['pending_reserved'];
            $newClosing = max(0, $entitled + $carriedIn - $used - $pending);
            $newClosing = $this->capClosingByAnnualQuota($employeeId, $year, $month, $type, $newClosing);
            $upd = $this->conn->prepare(
                'UPDATE employee_leave_monthly_balance
                 SET monthly_entitled = ?, carried_in = ?, closing_available = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            if ($upd) {
                $id = (int) $existing['id'];
                $upd->bind_param('dddi', $entitled, $carriedIn, $newClosing, $id);
                $upd->execute();
                $upd->close();
            }
            return;
        }

        $ins = $this->conn->prepare(
            'INSERT INTO employee_leave_monthly_balance
            (employee_id, balance_year, balance_month, leave_type, monthly_entitled, carried_in, used, carried_out, pending_reserved, closing_available)
            VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, ?)'
        );
        if ($ins) {
            $ins->bind_param('iiisddd', $employeeId, $year, $month, $type, $entitled, $carriedIn, $closing);
            $ins->execute();
            $ins->close();
        }
    }

    private function computeCarryIn($employeeId, $calendarYear, $calendarMonth, $leaveType)
    {
        $fyStartMonth = $this->financialYearStartMonth();
        if ((int) $calendarMonth === $fyStartMonth) {
            return 0.0;
        }

        $prevMonth = $calendarMonth - 1;
        $prevYear = $calendarYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        // Read previous month only — never recurse backward (avoids infinite loop).
        $stmt = $this->conn->prepare(
            'SELECT closing_available FROM employee_leave_monthly_balance
             WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ? LIMIT 1'
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('iiis', $employeeId, $prevYear, $prevMonth, $leaveType);
        $stmt->execute();
        $res = $stmt->get_result();
        $prev = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$prev) {
            return 0.0;
        }

        $unused = (float) $prev['closing_available'];
        return $this->cappedCarryForwardAmount($unused);
    }

    /**
     * How much unused balance may carry from previous month.
     * Policy carry_forward_max_per_type: 0 = carry ALL unused (full accrual bank).
     * Values > 0 limit carry per month (legacy / strict mode).
     */
    private function cappedCarryForwardAmount($unused)
    {
        $unused = max(0, (float) $unused);
        $maxPerMonth = $this->policyFloat('carry_forward_max_per_type', 0);
        if ($maxPerMonth > 0) {
            return min($unused, $maxPerMonth);
        }
        return $unused;
    }

    private function capClosingByAnnualQuota($employeeId, $year, $month, $type, $closing)
    {
        $leaveYear = $this->leaveYearForDate(sprintf('%04d-%02d-01', $year, $month));
        $annual = $this->getAnnualSummaryRow($employeeId, $leaveYear);
        $annualCap = $type === 'CL'
            ? $this->policyFloat('cl_annual_quota', 12)
            : $this->policyFloat('sl_annual_quota', 12);
        $usedKey = $type === 'CL' ? 'cl_used_ytd' : 'sl_used_ytd';
        $usedYtd = (float) ($annual[$usedKey] ?? 0);
        $maxPool = max(0, $annualCap - $usedYtd);
        return min(max(0, $closing), $maxPool);
    }

    public function ensureAnnualSummary($employeeId, $year)
    {
        $employeeId = (int) $employeeId;
        $year = (int) $year;
        $stmt = $this->conn->prepare(
            'SELECT id FROM employee_leave_annual_summary WHERE employee_id = ? AND balance_year = ? LIMIT 1'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ii', $employeeId, $year);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = $res && $res->num_rows > 0;
        $stmt->close();
        if ($exists) {
            return;
        }
        $ins = $this->conn->prepare(
            'INSERT INTO employee_leave_annual_summary (employee_id, balance_year) VALUES (?, ?)'
        );
        if ($ins) {
            $ins->bind_param('ii', $employeeId, $year);
            $ins->execute();
            $ins->close();
        }
    }

    public function getBalanceSummary($employeeId, $asOfDate = null)
    {
        $employeeId = (int) $employeeId;
        if ($asOfDate === null) {
            $asOfDate = date('Y-m-d');
        }
        $year = $this->leaveYearForDate($asOfDate);
        $cal = $this->calendarYearMonthForDate($asOfDate);
        $calendarYear = $cal['year'];
        $calendarMonth = $cal['month'];
        $fyMeta = $this->financialYearMeta($asOfDate);

        $this->syncApprovedLeaveDeductions($employeeId);
        $this->ensureMonthlyBalance($employeeId, $calendarYear, $calendarMonth);
        $this->expireCompOffCredits($employeeId);

        $monthly = [];
        foreach (['CL', 'SL'] as $type) {
            $stmt = $this->conn->prepare(
                'SELECT * FROM employee_leave_monthly_balance
                 WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ? LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('iiis', $employeeId, $calendarYear, $calendarMonth, $type);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                $monthly[$type] = [
                    'entitled' => (float) ($row['monthly_entitled'] ?? 0),
                    'carried_in' => (float) ($row['carried_in'] ?? 0),
                    'used' => (float) ($row['used'] ?? 0),
                    'pending' => (float) ($row['pending_reserved'] ?? 0),
                    'available' => max(0, (float) ($row['closing_available'] ?? 0) - (float) ($row['pending_reserved'] ?? 0)),
                ];
            }
        }

        $annual = $this->getAnnualSummaryRow($employeeId, $year);
        $annualClCap = $this->policyFloat('cl_annual_quota', 12);
        $annualSlCap = $this->policyFloat('sl_annual_quota', 12);
        $annualTotalCap = $this->policyFloat('annual_total_quota', 24);

        $clUsedYtd = (float) ($annual['cl_used_ytd'] ?? 0);
        $slUsedYtd = (float) ($annual['sl_used_ytd'] ?? 0);

        $compOffBal = $this->getCompOffAvailableBalance($employeeId);
        $joinDate = $this->getEmployeeJoiningDate($employeeId);
        $accrualStart = $this->accrualPeriodStartDate($employeeId, $asOfDate);
        $accrualMonths = 0;
        $startTs = strtotime($accrualStart);
        $endTs = strtotime(date('Y-m-01', strtotime($asOfDate)));
        if ($startTs !== false && $endTs !== false && $startTs <= $endTs) {
            $accrualMonths = 0;
            for ($ts = $startTs; $ts <= $endTs; $ts = strtotime('+1 month', $ts)) {
                $accrualMonths++;
            }
        }

        $clAvailable = (float) ($monthly['CL']['available'] ?? 0);
        $slAvailable = (float) ($monthly['SL']['available'] ?? 0);

        return [
            'employee_id' => $employeeId,
            'leave_year' => $year,
            'financial_year' => $fyMeta,
            'month' => $calendarMonth,
            'calendar_year' => $calendarYear,
            'calendar_month' => $calendarMonth,
            'joining_date' => $joinDate,
            'accrual_from' => $accrualStart,
            'accrual_months' => $accrualMonths,
            'total_available' => $clAvailable + $slAvailable,
            'monthly' => $monthly,
            'annual' => [
                'cl_quota' => $annualClCap,
                'sl_quota' => $annualSlCap,
                'total_quota' => $annualTotalCap,
                'cl_used' => $clUsedYtd,
                'sl_used' => $slUsedYtd,
                'total_used' => $clUsedYtd + $slUsedYtd,
                'cl_remaining' => max(0, $annualClCap - $clUsedYtd),
                'sl_remaining' => max(0, $annualSlCap - $slUsedYtd),
                'cl_advance_used' => (float) ($annual['cl_advance_used'] ?? 0),
                'sl_advance_used' => (float) ($annual['sl_advance_used'] ?? 0),
                'advance_max' => $this->policyFloat('advance_leave_max_days', 3),
            ],
            'comp_off' => [
                'available' => $compOffBal,
                'enabled' => $this->policyBool('comp_off_enabled', true),
            ],
            'policy' => $this->getPolicyForApi(),
        ];
    }

    public function getPolicyForApi()
    {
        return [
            'cl_monthly_quota' => $this->policyFloat('cl_monthly_quota', 1),
            'sl_monthly_quota' => $this->policyFloat('sl_monthly_quota', 1),
            'annual_total_quota' => $this->policyFloat('annual_total_quota', 24),
            'half_day_enabled' => $this->policyBool('half_day_enabled', true),
            'sandwich_rule_enabled' => $this->policyBool('sandwich_rule_enabled', true),
            'advance_leave_enabled' => $this->policyBool('advance_leave_enabled', true),
            'advance_leave_max_days' => $this->policyFloat('advance_leave_max_days', 3),
            'carry_forward_enabled' => $this->policyBool('carry_forward_enabled', true),
            'comp_off_enabled' => $this->policyBool('comp_off_enabled', true),
            'unpaid_leave_enabled' => $this->policyBool('unpaid_leave_enabled', true),
            'min_notice_days_cl' => $this->policyInt('min_notice_days_cl', 1),
            'min_notice_days_sl' => $this->policyInt('min_notice_days_sl', 0),
            'max_consecutive_days' => $this->policyInt('max_consecutive_days', 10),
            'leave_year_start_month' => $this->financialYearStartMonth(),
            'financial_year_start_month' => $this->financialYearStartMonth(),
            'year_type' => 'financial',
            'leave_types' => ['CL', 'SL', 'COMPOFF', 'UNPAID'],
        ];
    }

    private function getAnnualSummaryRow($employeeId, $year)
    {
        $this->ensureAnnualSummary($employeeId, $year);
        $stmt = $this->conn->prepare(
            'SELECT * FROM employee_leave_annual_summary WHERE employee_id = ? AND balance_year = ? LIMIT 1'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $employeeId, $year);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : [];
        $stmt->close();
        return $row ?: [];
    }

    public function getPendingReservedDays($employeeId, $leaveType, $excludeLeaveId = 0)
    {
        if (!$this->balanceTableExists()) {
            return 0.0;
        }
        $employeeId = (int) $employeeId;
        $excludeLeaveId = (int) $excludeLeaveId;
        $type = mysqli_real_escape_string($this->conn, strtoupper(trim($leaveType)));
        $cancelFilter = $this->leaveColumnExists('CancelledAt')
            ? " AND IFNULL(CancelledAt, '') = ''"
            : '';
        if ($this->leaveColumnExists('LeaveDays')) {
            $sql = "SELECT COALESCE(SUM(LeaveDays - BalanceDeducted), 0) AS pending_days
                FROM employee_leave
                WHERE EmployeeID = $employeeId AND IsActive = 1
                  AND UPPER(TypeOfLeave) = '$type'
                  AND Status IN ('Pending', 'SupervisorApproved')
                  $cancelFilter
                  AND ID != $excludeLeaveId";
        } else {
            return 0.0;
        }
        $res = mysqli_query($this->conn, $sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            return max(0, (float) $row['pending_days']);
        }
        return 0.0;
    }

    /**
     * Validate leave application without saving.
     */
    public function validateLeaveApplication(array $data)
    {
        $employeeId = (int) ($data['EmployeeID'] ?? 0);
        $type = strtoupper(trim((string) ($data['TypeOfLeave'] ?? '')));
        $fromDate = trim((string) ($data['FromDate'] ?? $data['from_date'] ?? ''));
        $toDate = trim((string) ($data['ToDate'] ?? $data['to_date'] ?? ''));
        $reason = trim((string) ($data['ReasonOfLeave'] ?? $data['leave_reason'] ?? ''));
        $duration = trim((string) ($data['Duration'] ?? 'Full Day'));
        $halfSession = strtolower(trim((string) ($data['HalfDaySession'] ?? $data['half_day_session'] ?? '')));
        $compOffId = (int) ($data['CompOffId'] ?? $data['comp_off_id'] ?? 0);

        if ($employeeId < 1) {
            return ['error' => true, 'message' => 'EmployeeID is required.'];
        }
        if (!in_array($type, ['CL', 'SL', 'COMPOFF', 'UNPAID'], true)) {
            return ['error' => true, 'message' => 'Invalid leave type. Use CL, SL, Comp-off, or Unpaid Leave.'];
        }
        if ($type === 'UNPAID' && !$this->policyBool('unpaid_leave_enabled', true)) {
            return ['error' => true, 'message' => 'Unpaid leave is not enabled. Contact HR.'];
        }
        if ($reason === '') {
            return ['error' => true, 'message' => 'Reason is required.'];
        }
        if ($fromDate === '' || $toDate === '') {
            return ['error' => true, 'message' => 'From and To dates are required.'];
        }

        $calc = $this->calculateLeaveDays($employeeId, $fromDate, $toDate, $duration, $halfSession);
        if (!empty($calc['error'])) {
            return $calc;
        }

        $totalDays = (float) $calc['total_days'];
        $workingDays = (float) ($calc['working_days'] ?? $totalDays);
        $maxConsec = $this->policyInt('max_consecutive_days', 10);
        if ($totalDays > $maxConsec) {
            $calendarDays = (int) floor((strtotime($toDate) - strtotime($fromDate)) / 86400) + 1;
            return [
                'error' => true,
                'message' => "This range is $totalDays leave day(s) ($workingDays working day(s), $calendarDays calendar day(s)). "
                    . "Maximum $maxConsec leave day(s) allowed per single application. "
                    . "To use 5 CL, pick about 5 working days in one month (e.g. Mon–Fri), or split into multiple applications.",
                'days_calculated' => $totalDays,
                'max_consecutive_days' => $maxConsec,
            ];
        }

        $balancePreview = $this->getBalanceSummary($employeeId, $fromDate);
        $typeKey = in_array($type, ['CL', 'SL'], true) ? $type : null;
        $available = 0.0;
        if ($typeKey && isset($balancePreview['monthly'][$typeKey])) {
            $available = (float) $balancePreview['monthly'][$typeKey]['available'];
        } elseif ($type === 'COMPOFF') {
            $available = (float) ($balancePreview['comp_off']['available'] ?? 0);
        }

        if (!in_array($type, ['COMPOFF', 'UNPAID'], true) && $totalDays > $available) {
            $advanceMax = $this->policyFloat('advance_leave_max_days', 3);
            $advanceEnabled = $this->policyBool('advance_leave_enabled', true);
            $shortfall = $totalDays - $available;
            if (!$advanceEnabled || $shortfall > $advanceMax) {
                return [
                    'error' => true,
                    'message' => "Insufficient $type balance. You need $totalDays day(s) but only $available available. "
                        . "Reduce the date range or use fewer working days.",
                    'days_to_deduct' => $totalDays,
                    'balance_available' => $available,
                ];
            }
        }

        if ($this->hasOverlappingLeave($employeeId, $fromDate, $toDate)) {
            return ['error' => true, 'message' => 'Leave dates overlap with an existing application.'];
        }

        $noticeKey = $type === 'SL' ? 'min_notice_days_sl' : 'min_notice_days_cl';
        $minNotice = $this->policyInt($noticeKey, $type === 'SL' ? 0 : 1);
        if ($type === 'UNPAID') {
            $minNotice = $this->policyInt('min_notice_days_cl', 1);
        }
        if ($minNotice > 0) {
            $today = strtotime(date('Y-m-d'));
            $fromTs = strtotime($fromDate);
            $diffDays = (int) floor(($fromTs - $today) / 86400);
            if ($diffDays < $minNotice) {
                return ['error' => true, 'message' => "Minimum $minNotice day(s) notice required for $type."];
            }
        }

        $balanceCheck = $this->checkBalanceForLeave($employeeId, $type, $totalDays, $fromDate, $compOffId);
        if (!empty($balanceCheck['error'])) {
            return $balanceCheck;
        }

        $isUnpaid = ($type === 'UNPAID');
        return [
            'error' => false,
            'message' => $isUnpaid
                ? 'Unpaid leave can be applied (no CL/SL balance required). These days may reduce salary on approval.'
                : 'Leave can be applied.',
            'calculation' => $calc,
            'balance_check' => $balanceCheck,
            'is_advance' => !empty($balanceCheck['is_advance']),
            'is_unpaid' => $isUnpaid,
            'days_to_deduct' => $totalDays,
            'balance_available' => $isUnpaid ? null : $available,
            'balance_after' => $isUnpaid ? null : max(0, $available - $totalDays),
        ];
    }

    private function hasOverlappingLeave($employeeId, $fromDate, $toDate)
    {
        $employeeId = (int) $employeeId;
        $from = mysqli_real_escape_string($this->conn, $fromDate);
        $to = mysqli_real_escape_string($this->conn, $toDate);
        $cancelFilter = $this->leaveColumnExists('CancelledAt')
            ? " AND IFNULL(CancelledAt, '') = ''"
            : '';
        $sql = "SELECT ID FROM employee_leave
            WHERE EmployeeID = $employeeId AND IsActive = 1
              AND Status NOT IN ('Rejected')
              $cancelFilter
              AND FromDate <= '$to' AND ToDate >= '$from'
            LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        return ($res && $res->num_rows > 0);
    }

    private function checkBalanceForLeave($employeeId, $type, $totalDays, $fromDate, $compOffId = 0)
    {
        if ($type === 'UNPAID') {
            if (!$this->policyBool('unpaid_leave_enabled', true)) {
                return ['error' => true, 'message' => 'Unpaid leave is not enabled.'];
            }
            return ['error' => false, 'is_unpaid' => true, 'is_advance' => false];
        }

        if ($type === 'COMPOFF') {
            if (!$this->policyBool('comp_off_enabled', true)) {
                return ['error' => true, 'message' => 'Comp-off leave is not enabled.'];
            }
            $avail = $this->getCompOffAvailableBalance($employeeId);
            $pending = $this->getPendingReservedDays($employeeId, 'COMPOFF');
            $net = $avail - $pending;
            if ($totalDays > $net) {
                return ['error' => true, 'message' => 'Insufficient comp-off balance. Available: ' . round($net, 2)];
            }
            return ['error' => false, 'available' => $net, 'is_advance' => false];
        }

        $summary = $this->getBalanceSummary($employeeId, $fromDate);
        $monthly = $summary['monthly'][$type] ?? ['available' => 0];
        $annual = $summary['annual'];
        $available = (float) $monthly['available'];
        $pending = $this->getPendingReservedDays($employeeId, $type);

        $annualKey = $type === 'CL' ? 'cl_remaining' : 'sl_remaining';
        $annualRemaining = (float) ($annual[$annualKey] ?? 0);
        $totalRemaining = min($available, $annualRemaining);

        $netAvailable = $totalRemaining - $pending;
        $isAdvance = false;
        $advanceNeeded = 0.0;

        if ($totalDays > $netAvailable) {
            if (!$this->policyBool('advance_leave_enabled', true)) {
                return [
                    'error' => true,
                    'message' => 'Insufficient leave balance. Available: ' . round(max(0, $netAvailable), 2) . ' day(s).',
                ];
            }
            $advanceNeeded = $totalDays - max(0, $netAvailable);
            $advanceKey = $type === 'CL' ? 'cl_advance_used' : 'sl_advance_used';
            $advanceUsed = (float) ($annual[$advanceKey] ?? 0);
            $advanceMax = $this->policyFloat('advance_leave_max_days', 3);
            if (($advanceUsed + $advanceNeeded) > $advanceMax) {
                return [
                    'error' => true,
                    'message' => 'Advance leave limit exceeded. Max advance: ' . $advanceMax . ' day(s) per year for ' . $type . '.',
                ];
            }
            $isAdvance = true;
        }

        $totalUsed = (float) $annual['total_used'];
        $totalCap = $this->policyFloat('annual_total_quota', 24);
        if (($totalUsed + $totalDays) > $totalCap && !$isAdvance) {
            return ['error' => true, 'message' => 'Annual leave quota of ' . $totalCap . ' days exceeded.'];
        }

        return [
            'error' => false,
            'monthly_available' => $available,
            'annual_remaining' => $annualRemaining,
            'net_available' => max(0, $netAvailable),
            'is_advance' => $isAdvance,
            'advance_needed' => $advanceNeeded,
        ];
    }

    public function applyLeave(array $data, $createdBy = '')
    {
        $validation = $this->validateLeaveApplication($data);
        if (!empty($validation['error'])) {
            return $validation;
        }

        $employeeId = (int) $data['EmployeeID'];
        $type = strtoupper(trim((string) $data['TypeOfLeave']));
        $fromDate = trim((string) ($data['FromDate'] ?? $data['from_date']));
        $toDate = trim((string) ($data['ToDate'] ?? $data['to_date']));
        $reason = mysqli_real_escape_string($this->conn, trim((string) ($data['ReasonOfLeave'] ?? $data['leave_reason'])));
        $duration = trim((string) ($data['Duration'] ?? 'Full Day'));
        $halfSession = strtolower(trim((string) ($data['HalfDaySession'] ?? $data['half_day_session'] ?? '')));
        $isHalfDay = (stripos($duration, 'half') !== false);
        if (!$isHalfDay) {
            $halfSession = '';
        }
        $compOffId = (int) ($data['CompOffId'] ?? 0);
        $calc = $validation['calculation'];
        $totalDays = (float) $calc['total_days'];
        $isAdvance = !empty($validation['is_advance']) ? 1 : 0;
        $sandwichExtra = (float) ($calc['sandwich_extra'] ?? 0);
        $isSandwich = $sandwichExtra > 0 ? 1 : 0;
        $createdBy = mysqli_real_escape_string($this->conn, substr((string) $createdBy, 0, 100));
        $createdDate = date('Y-m-d');
        $createdTime = date('H:i:s');
        $durationEsc = mysqli_real_escape_string($this->conn, $duration);
        $halfSql = ($isHalfDay && $halfSession !== '')
            ? "'" . mysqli_real_escape_string($this->conn, $halfSession) . "'"
            : 'NULL';
        $compOffSql = $compOffId > 0 ? $compOffId : 'NULL';

        if ($this->hasExtendedLeaveColumns()) {
            $policyJson = mysqli_real_escape_string($this->conn, json_encode($this->getPolicy()));
            $sql = "INSERT INTO employee_leave (
                EmployeeID, TypeOfLeave, ReasonOfLeave, FromDate, ToDate, Duration,
                LeaveDays, HalfDaySession, IsAdvanceLeave, IsSandwichApplied, SandwichExtraDays,
                CompOffId, BalanceDeducted, PolicySnapshot, Status, Approved,
                CreatedBy, CreatedDate, CreatedTime, IsActive
            ) VALUES (
                $employeeId, '$type', '$reason', '$fromDate', '$toDate', '$durationEsc',
                $totalDays, $halfSql, $isAdvance, $isSandwich, $sandwichExtra,
                $compOffSql, 0, '$policyJson', 'Pending', '0',
                '$createdBy', '$createdDate', '$createdTime', 1
            )";
        } else {
            $sql = "INSERT INTO employee_leave (
                EmployeeID, TypeOfLeave, ReasonOfLeave, FromDate, ToDate, Duration,
                Status, Approved, CreatedBy, CreatedDate, CreatedTime, IsActive
            ) VALUES (
                $employeeId, '$type', '$reason', '$fromDate', '$toDate', '$durationEsc',
                'Pending', '0', '$createdBy', '$createdDate', '$createdTime', 1
            )";
        }

        $result = $this->_InsertTableRecords($this->conn, $sql);
        if (!empty($result['error'])) {
            return ['error' => true, 'message' => $this->userFacingDbError($result['message'] ?? '')];
        }

        $leaveId = (int) ($result['last_insert_id'] ?? 0);
        $this->reservePendingBalance($employeeId, $type, $totalDays, $fromDate);

        $this->notifySupervisorOnApply($employeeId, $leaveId, $fromDate, $toDate, $type, $duration);

        return [
            'error' => false,
            'message' => 'Leave applied successfully. Pending supervisor approval.',
            'leave_id' => $leaveId,
            'days' => $totalDays,
            'is_advance' => (bool) $isAdvance,
        ];
    }

    private function reservePendingBalance($employeeId, $leaveType, $days, $fromDate)
    {
        if ($this->skipsBalanceTracking($leaveType) || !$this->balanceTableExists()) {
            return;
        }
        $cal = $this->calendarYearMonthForDate($fromDate);
        $calendarYear = $cal['year'];
        $calendarMonth = $cal['month'];
        $this->ensureMonthlyBalance($employeeId, $calendarYear, $calendarMonth);

        $stmt = $this->conn->prepare(
            'UPDATE employee_leave_monthly_balance
             SET pending_reserved = pending_reserved + ?,
                 closing_available = GREATEST(0, monthly_entitled + carried_in - used - (pending_reserved + ?)),
                 updated_at = NOW()
             WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ?'
        );
        if ($stmt) {
            $stmt->bind_param('ddiiis', $days, $days, $employeeId, $calendarYear, $calendarMonth, $leaveType);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function cancelLeave($leaveId, $employeeId, $cancelledBy = '')
    {
        $leaveId = (int) $leaveId;
        $employeeId = (int) $employeeId;
        $leave = $this->getLeaveById($leaveId);
        if (!$leave || (int) $leave['EmployeeID'] !== $employeeId) {
            return ['error' => true, 'message' => 'Leave not found.'];
        }
        if (!empty($leave['CancelledAt'])) {
            return ['error' => true, 'message' => 'Leave already cancelled.'];
        }
        if (strcasecmp((string) $leave['Status'], 'Approved') === 0) {
            return ['error' => true, 'message' => 'Approved leave cannot be cancelled. Contact HR.'];
        }
        $st = (string) ($leave['Status'] ?? '');
        if (!in_array($st, ['Pending', 'SupervisorApproved'], true)) {
            return ['error' => true, 'message' => 'This leave cannot be cancelled.'];
        }

        $cancelledByEsc = mysqli_real_escape_string($this->conn, substr((string) $cancelledBy, 0, 128));
        $now = date('Y-m-d H:i:s');
        if ($this->leaveColumnExists('CancelledAt')) {
            $sql = "UPDATE employee_leave SET CancelledAt = '$now', CancelledBy = '$cancelledByEsc', IsActive = 0
                WHERE ID = $leaveId AND EmployeeID = $employeeId";
        } else {
            $sql = "UPDATE employee_leave SET IsActive = 0 WHERE ID = $leaveId AND EmployeeID = $employeeId";
        }
        mysqli_query($this->conn, $sql);

        $days = $this->resolveLeaveDays($leave);
        $type = strtoupper((string) $leave['TypeOfLeave']);
        if ($days > 0 && !$this->skipsBalanceTracking($type)) {
            $this->releasePendingReservation($employeeId, $type, $days, $leave['FromDate']);
        }

        return ['error' => false, 'message' => 'Leave cancelled successfully.'];
    }

    private function releasePendingReservation($employeeId, $leaveType, $days, $fromDate)
    {
        if (!$this->balanceTableExists()) {
            return;
        }
        $cal = $this->calendarYearMonthForDate($fromDate);
        $calendarYear = $cal['year'];
        $calendarMonth = $cal['month'];
        $stmt = $this->conn->prepare(
            'UPDATE employee_leave_monthly_balance
             SET pending_reserved = GREATEST(0, pending_reserved - ?),
                 closing_available = GREATEST(0, monthly_entitled + carried_in - used - GREATEST(0, pending_reserved - ?)),
                 updated_at = NOW()
             WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ?'
        );
        if ($stmt) {
            $stmt->bind_param('ddiiis', $days, $days, $employeeId, $calendarYear, $calendarMonth, $leaveType);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Sync balance deduction for leaves approved via existing HR approval flow.
     */
    public function syncApprovedLeaveDeductions($employeeId = 0)
    {
        if (!$this->balanceTableExists() || !$this->hasExtendedLeaveColumns()) {
            return;
        }
        $employeeFilter = $employeeId > 0 ? ' AND EmployeeID = ' . (int) $employeeId : '';
        $cancelFilter = $this->leaveColumnExists('CancelledAt')
            ? " AND IFNULL(CancelledAt, '') = ''"
            : '';
        $sql = "SELECT * FROM employee_leave
            WHERE IsActive = 1 AND Status = 'Approved'
              $cancelFilter
              AND LeaveDays > 0
              AND (BalanceDeducted IS NULL OR BalanceDeducted < LeaveDays)
              $employeeFilter
            ORDER BY ID ASC LIMIT 50";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) {
            return;
        }
        while ($leave = mysqli_fetch_assoc($res)) {
            $this->deductBalanceForApprovedLeave($leave);
        }
    }

    private function deductBalanceForApprovedLeave(array $leave)
    {
        $leaveId = (int) $leave['ID'];
        $employeeId = (int) $leave['EmployeeID'];
        $type = strtoupper(trim((string) $leave['TypeOfLeave']));
        $days = $this->resolveLeaveDays($leave);
        $already = (float) ($leave['BalanceDeducted'] ?? 0);
        $toDeduct = $days - $already;
        if ($toDeduct <= 0) {
            return;
        }

        if ($type === 'UNPAID') {
            mysqli_query($this->conn, "UPDATE employee_leave SET BalanceDeducted = $days WHERE ID = $leaveId");
            return;
        }

        if ($type === 'COMPOFF') {
            $this->consumeCompOffForLeave($employeeId, $leaveId, $toDeduct, (int) ($leave['CompOffId'] ?? 0));
        } else {
            $this->deductMonthlyAndAnnual($employeeId, $type, $toDeduct, $leave['FromDate'], !empty($leave['IsAdvanceLeave']), $leaveId);
        }

        $newDeducted = $already + $toDeduct;
        mysqli_query($this->conn, "UPDATE employee_leave SET BalanceDeducted = $newDeducted WHERE ID = $leaveId");

        $this->releasePendingReservation($employeeId, $type, $toDeduct, $leave['FromDate']);
    }

    private function deductMonthlyAndAnnual($employeeId, $leaveType, $days, $fromDate, $isAdvance, $leaveId)
    {
        $cal = $this->calendarYearMonthForDate($fromDate);
        $calendarYear = $cal['year'];
        $calendarMonth = $cal['month'];
        $leaveYear = $this->leaveYearForDate($fromDate);
        $this->ensureMonthlyBalance($employeeId, $calendarYear, $calendarMonth);

        $stmt = $this->conn->prepare(
            'UPDATE employee_leave_monthly_balance
             SET used = used + ?,
                 closing_available = GREATEST(0, monthly_entitled + carried_in - used - pending_reserved),
                 updated_at = NOW()
             WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ?'
        );
        if ($stmt) {
            $stmt->bind_param('diiis', $days, $employeeId, $calendarYear, $calendarMonth, $leaveType);
            $stmt->execute();
            $stmt->close();
        }

        $annualField = $leaveType === 'CL' ? 'cl_used_ytd' : 'sl_used_ytd';
        $advanceField = $leaveType === 'CL' ? 'cl_advance_used' : 'sl_advance_used';
        $this->ensureAnnualSummary($employeeId, $leaveYear);

        if ($isAdvance) {
            mysqli_query($this->conn, "UPDATE employee_leave_annual_summary
                SET $annualField = $annualField + $days, $advanceField = $advanceField + $days, updated_at = NOW()
                WHERE employee_id = $employeeId AND balance_year = $leaveYear");
        } else {
            mysqli_query($this->conn, "UPDATE employee_leave_annual_summary
                SET $annualField = $annualField + $days, updated_at = NOW()
                WHERE employee_id = $employeeId AND balance_year = $leaveYear");
        }

        $this->logBalanceChange($employeeId, $leaveId, null, 'deduct_approved', $leaveType, -$days, null, null, 'HR approved leave');
    }

    public function getLeaveById($leaveId)
    {
        $leaveId = (int) $leaveId;
        $where = " WHERE ID = $leaveId";
        return $this->_getTableDetails($this->conn, 'employee_leave', $where);
    }

    public function listEmployeeLeaves($employeeId, $limit = 100)
    {
        $employeeId = (int) $employeeId;
        $limit = max(1, min(500, (int) $limit));
        $sql = "SELECT * FROM employee_leave WHERE EmployeeID = $employeeId AND IsActive = 1
            ORDER BY ID DESC LIMIT $limit";
        return $this->_getSQLRecords($this->conn, $sql);
    }

    // ---------- Comp-off ----------

    public function getCompOffAvailableBalance($employeeId)
    {
        $employeeId = (int) $employeeId;
        $this->expireCompOffCredits($employeeId);
        $sql = "SELECT COALESCE(SUM(credit_days), 0) AS bal FROM employee_comp_off
            WHERE employee_id = $employeeId AND status = 'Approved'
              AND (used_leave_id IS NULL OR used_leave_id = 0)
              AND (expires_at IS NULL OR expires_at >= CURDATE())";
        $res = mysqli_query($this->conn, $sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            return (float) $row['bal'];
        }
        return 0.0;
    }

    public function expireCompOffCredits($employeeId = 0)
    {
        $filter = $employeeId > 0 ? ' AND employee_id = ' . (int) $employeeId : '';
        mysqli_query($this->conn, "UPDATE employee_comp_off SET status = 'Expired'
            WHERE status = 'Approved' AND expires_at IS NOT NULL AND expires_at < CURDATE() $filter");
    }

    public function requestCompOff($employeeId, $workDate, $creditDays, $reason, $createdBy = '')
    {
        $employeeId = (int) $employeeId;
        if (!$this->policyBool('comp_off_enabled', true)) {
            return ['error' => true, 'message' => 'Comp-off is not enabled.'];
        }
        $workDate = trim((string) $workDate);
        $creditDays = max(0.5, min(2.0, (float) $creditDays));
        if ($workDate === '') {
            return ['error' => true, 'message' => 'Work date is required.'];
        }

        $weeklyOff = $this->getEmployeeWeeklyOff($employeeId);
        $holidayMap = $this->getHolidayMap($workDate, $workDate);
        $dayType = $this->dayType($workDate, $weeklyOff, $holidayMap);
        if ($dayType === 'working') {
            return ['error' => true, 'message' => 'Comp-off can only be claimed for working on a weekly off or public holiday.'];
        }

        if (!$this->employeeWorkedOnDate($employeeId, $workDate)) {
            return ['error' => true, 'message' => 'No attendance punch found for the selected date.'];
        }

        $dup = mysqli_query($this->conn, "SELECT id FROM employee_comp_off
            WHERE employee_id = $employeeId AND work_date = '" . mysqli_real_escape_string($this->conn, $workDate) . "'
              AND status NOT IN ('Rejected','Expired') LIMIT 1");
        if ($dup && $dup->num_rows > 0) {
            return ['error' => true, 'message' => 'Comp-off already requested for this date.'];
        }

        $validity = $this->policyInt('comp_off_validity_days', 90);
        $expiresAt = date('Y-m-d', strtotime($workDate . " + $validity days"));
        $status = $this->policyBool('comp_off_requires_approval', true) ? 'Pending' : 'Approved';
        $reasonEsc = mysqli_real_escape_string($this->conn, substr(trim((string) $reason), 0, 500));
        $createdByEsc = mysqli_real_escape_string($this->conn, substr((string) $createdBy, 0, 128));
        $approvedAt = $status === 'Approved' ? "'" . date('Y-m-d H:i:s') . "'" : 'NULL';

        $sql = "INSERT INTO employee_comp_off
            (employee_id, work_date, credit_days, reason, status, expires_at, created_by, approved_at)
            VALUES ($employeeId, '$workDate', $creditDays, '$reasonEsc', '$status', '$expiresAt', '$createdByEsc', $approvedAt)";
        $result = $this->_InsertTableRecords($this->conn, $sql);
        if (!empty($result['error'])) {
            return ['error' => true, 'message' => 'Could not save comp-off request.'];
        }

        return [
            'error' => false,
            'message' => $status === 'Approved' ? 'Comp-off credited.' : 'Comp-off request submitted for approval.',
            'comp_off_id' => (int) ($result['last_insert_id'] ?? 0),
        ];
    }

    private function employeeWorkedOnDate($employeeId, $date)
    {
        $employeeId = (int) $employeeId;
        $date = mysqli_real_escape_string($this->conn, $date);
        $sql = "SELECT ID FROM employee_attendance
            WHERE EmployeeID = $employeeId AND RecordDate = '$date' AND IFNULL(InTime,'') <> '' LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        return ($res && $res->num_rows > 0);
    }

    public function listCompOff($employeeId)
    {
        $employeeId = (int) $employeeId;
        $sql = "SELECT * FROM employee_comp_off WHERE employee_id = $employeeId ORDER BY id DESC LIMIT 100";
        return $this->_getSQLRecords($this->conn, $sql);
    }

    public function approveCompOff($compOffId, $approverId, $approve = true, $reason = '')
    {
        $compOffId = (int) $compOffId;
        $approverId = (int) $approverId;
        $row = $this->_getTableDetails($this->conn, 'employee_comp_off', " WHERE id = $compOffId");
        if (!$row || strcasecmp((string) $row['status'], 'Pending') !== 0) {
            return ['error' => true, 'message' => 'Comp-off request not found or already processed.'];
        }
        if ($approve) {
            $now = date('Y-m-d H:i:s');
            mysqli_query($this->conn, "UPDATE employee_comp_off SET status = 'Approved', approved_by = $approverId, approved_at = '$now' WHERE id = $compOffId");
            return ['error' => false, 'message' => 'Comp-off approved.'];
        }
        $reasonEsc = mysqli_real_escape_string($this->conn, substr(trim($reason), 0, 500));
        mysqli_query($this->conn, "UPDATE employee_comp_off SET status = 'Rejected', approved_by = $approverId, rejection_reason = '$reasonEsc' WHERE id = $compOffId");
        return ['error' => false, 'message' => 'Comp-off rejected.'];
    }

    private function consumeCompOffForLeave($employeeId, $leaveId, $days, $preferredCompOffId = 0)
    {
        $remaining = $days;
        if ($preferredCompOffId > 0) {
            $row = $this->_getTableDetails($this->conn, 'employee_comp_off', " WHERE id = $preferredCompOffId AND employee_id = $employeeId AND status = 'Approved'");
            if ($row) {
                $credit = (float) $row['credit_days'];
                if ($credit >= $remaining) {
                    mysqli_query($this->conn, "UPDATE employee_comp_off SET status = 'Used', used_leave_id = $leaveId WHERE id = $preferredCompOffId");
                    return;
                }
            }
        }
        $res = mysqli_query($this->conn, "SELECT id, credit_days FROM employee_comp_off
            WHERE employee_id = $employeeId AND status = 'Approved'
              AND (used_leave_id IS NULL OR used_leave_id = 0)
              AND (expires_at IS NULL OR expires_at >= CURDATE())
            ORDER BY expires_at ASC, id ASC");
        if (!$res) {
            return;
        }
        while ($remaining > 0 && ($row = mysqli_fetch_assoc($res))) {
            $credit = (float) $row['credit_days'];
            if ($credit <= $remaining) {
                mysqli_query($this->conn, "UPDATE employee_comp_off SET status = 'Used', used_leave_id = $leaveId WHERE id = " . (int) $row['id']);
                $remaining -= $credit;
            }
        }
    }

    private function logBalanceChange($employeeId, $leaveId, $compOffId, $action, $leaveType, $delta, $before, $after, $remarks)
    {
        $employeeId = (int) $employeeId;
        $leaveId = $leaveId ? (int) $leaveId : 'NULL';
        $compOffId = $compOffId ? (int) $compOffId : 'NULL';
        $action = mysqli_real_escape_string($this->conn, substr($action, 0, 32));
        $leaveType = mysqli_real_escape_string($this->conn, substr((string) $leaveType, 0, 16));
        $remarks = mysqli_real_escape_string($this->conn, substr((string) $remarks, 0, 500));
        $beforeSql = $before !== null ? (float) $before : 'NULL';
        $afterSql = $after !== null ? (float) $after : 'NULL';
        mysqli_query($this->conn, "INSERT INTO employee_leave_balance_log
            (employee_id, leave_id, comp_off_id, action_type, leave_type, days_delta, balance_before, balance_after, remarks)
            VALUES ($employeeId, $leaveId, $compOffId, '$action', '$leaveType', $delta, $beforeSql, $afterSql, '$remarks')");
    }

    private function notifySupervisorOnApply($employeeId, $leaveId, $from, $to, $type, $duration)
    {
        $where = " WHERE ID = " . (int) $employeeId;
        $emp = $this->_getTableDetails($this->conn, 'employees', $where);
        if (!$emp || empty($emp['Supervisor']) || (int) $emp['Supervisor'] <= 0) {
            return;
        }
        $supervisorId = (int) $emp['Supervisor'];
        if (file_exists(dirname(__DIR__) . '/controllers/push_notification_controller.php')) {
            require_once dirname(__DIR__) . '/controllers/push_notification_controller.php';
            if (function_exists('pnc_sendPushToEmployee')) {
                $name = $emp['Name'] ?? 'Employee';
                pnc_sendPushToEmployee($this->conn, $supervisorId, 'Leave request', "$name applied $type leave from $from to $to ($duration).", [
                    'module' => 'leave',
                    'leave_id' => $leaveId,
                ]);
            }
        }
    }

    public function savePolicySettings(array $settings, $updatedBy = '')
    {
        $updatedBy = mysqli_real_escape_string($this->conn, substr((string) $updatedBy, 0, 128));
        foreach ($settings as $key => $value) {
            $key = mysqli_real_escape_string($this->conn, preg_replace('/[^a-z0-9_]/', '', strtolower($key)));
            if ($key === '') {
                continue;
            }
            $value = mysqli_real_escape_string($this->conn, (string) $value);
            mysqli_query($this->conn, "INSERT INTO leave_policy_settings (setting_key, setting_value, updated_by, updated_at)
                VALUES ('$key', '$value', '$updatedBy', NOW())
                ON DUPLICATE KEY UPDATE setting_value = '$value', updated_by = '$updatedBy', updated_at = NOW()");
        }
        $this->policyCache = null;
        return ['error' => false, 'message' => 'Policy settings saved.'];
    }

    public function runMonthEndCarryForward($employeeId, $year, $month)
    {
        $employeeId = (int) $employeeId;
        $year = (int) $year;
        $month = (int) $month;
        if (!$this->policyBool('carry_forward_enabled', true)) {
            return ['error' => false, 'message' => 'Carry forward disabled.'];
        }
        $this->ensureMonthlyBalance($employeeId, $year, $month);

        foreach (['CL', 'SL'] as $type) {
            $stmt = $this->conn->prepare(
                'SELECT id, closing_available, pending_reserved FROM employee_leave_monthly_balance
                 WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ? LIMIT 1'
            );
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('iiis', $employeeId, $year, $month, $type);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if (!$row) {
                continue;
            }
            $carryOut = $this->cappedCarryForwardAmount((float) $row['closing_available']);
            mysqli_query($this->conn, "UPDATE employee_leave_monthly_balance SET carried_out = $carryOut WHERE id = " . (int) $row['id']);

            $nextMonth = $month + 1;
            $nextYear = $year;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
            $this->ensureMonthlyBalance($employeeId, $nextYear, $nextMonth);
            mysqli_query($this->conn, "UPDATE employee_leave_monthly_balance
                SET carried_in = $carryOut,
                    closing_available = GREATEST(0, monthly_entitled + carried_in - used - pending_reserved)
                WHERE employee_id = $employeeId AND balance_year = $nextYear AND balance_month = $nextMonth AND leave_type = '$type'");
        }
        return ['error' => false, 'message' => 'Carry forward processed.'];
    }

    /**
     * Leave balance breakdown for payroll preview (financial year + carry-forward rules applied).
     */
    public function formatBalanceForPayrollApi($employeeId, $asOfDate = null)
    {
        $employeeId = (int) $employeeId;
        if ($asOfDate === null) {
            $asOfDate = date('Y-m-d');
        }
        $summary = $this->getBalanceSummary($employeeId, $asOfDate);
        $policy = $summary['policy'] ?? $this->getPolicyForApi();
        $fy = $summary['financial_year'] ?? $this->financialYearMeta($asOfDate);
        $carryMax = $this->policyFloat('carry_forward_max_per_type', 0);

        $formatType = static function ($type, array $summary, array $policy) {
            $m = $summary['monthly'][$type] ?? [];
            $annual = $summary['annual'] ?? [];
            $quotaKey = $type === 'CL' ? 'cl_monthly_quota' : 'sl_monthly_quota';
            $remainKey = $type === 'CL' ? 'cl_remaining' : 'sl_remaining';
            $usedKey = $type === 'CL' ? 'cl_used' : 'sl_used';
            $capKey = $type === 'CL' ? 'cl_quota' : 'sl_quota';
            $advanceKey = $type === 'CL' ? 'cl_advance_used' : 'sl_advance_used';
            $monthlyQuota = (float) ($m['entitled'] ?? $policy[$quotaKey] ?? 0);
            $carriedIn = (float) ($m['carried_in'] ?? 0);
            $usedMonth = (float) ($m['used'] ?? 0);
            $pending = (float) ($m['pending'] ?? 0);
            $available = (float) ($m['available'] ?? 0);

            return [
                'available' => round($available, 2),
                'monthly_quota' => round($monthlyQuota, 2),
                'carried_in' => round($carriedIn, 2),
                'used_this_month' => round($usedMonth, 2),
                'pending' => round($pending, 2),
                'pool' => round($monthlyQuota + $carriedIn, 2),
                'fy_used' => round((float) ($annual[$usedKey] ?? 0), 2),
                'fy_quota' => round((float) ($annual[$capKey] ?? 0), 2),
                'fy_remaining' => round((float) ($annual[$remainKey] ?? 0), 2),
                'advance_used' => round((float) ($annual[$advanceKey] ?? 0), 2),
            ];
        };

        $carryEnabled = !empty($policy['carry_forward_enabled']);
        $carryNote = 'Off';
        if ($carryEnabled) {
            $carryNote = $carryMax > 0
                ? 'On — max ' . round($carryMax, 2) . ' day(s) per type/month from previous month'
                : 'On — all unused from previous month carries forward';
        }

        return [
            'as_of' => $asOfDate,
            'calendar_month' => (int) ($summary['calendar_month'] ?? (int) date('n', strtotime($asOfDate))),
            'calendar_year' => (int) ($summary['calendar_year'] ?? (int) date('Y', strtotime($asOfDate))),
            'financial_year' => [
                'label' => $fy['financial_year_label'] ?? '',
                'start' => $fy['financial_year_start'] ?? '',
                'end' => $fy['financial_year_end'] ?? '',
                'start_month_name' => $fy['financial_year_start_month_name'] ?? 'April',
            ],
            'policy' => [
                'carry_forward_enabled' => $carryEnabled,
                'carry_forward_note' => $carryNote,
                'cl_monthly_quota' => round((float) ($policy['cl_monthly_quota'] ?? 1), 2),
                'sl_monthly_quota' => round((float) ($policy['sl_monthly_quota'] ?? 1), 2),
                'cl_annual_quota' => round($this->policyFloat('cl_annual_quota', 12), 2),
                'sl_annual_quota' => round($this->policyFloat('sl_annual_quota', 12), 2),
                'annual_total_quota' => round((float) ($policy['annual_total_quota'] ?? 24), 2),
                'advance_leave_enabled' => !empty($policy['advance_leave_enabled']),
                'advance_leave_max_days' => round((float) ($policy['advance_leave_max_days'] ?? 3), 2),
            ],
            'cl' => $formatType('CL', $summary, $policy),
            'sl' => $formatType('SL', $summary, $policy),
            'total_available' => round((float) ($summary['total_available'] ?? 0), 2),
            'comp_off' => [
                'enabled' => !empty($summary['comp_off']['enabled']),
                'available' => round((float) ($summary['comp_off']['available'] ?? 0), 2),
                'validity_days' => $this->policyInt('comp_off_validity_days', 90),
                'requires_approval' => $this->policyBool('comp_off_requires_approval', true),
            ],
        ];
    }

    public function formatCompOffForPayrollApi($employeeId, $monthStart, $monthEnd)
    {
        $employeeId = (int) $employeeId;
        $monthStart = trim((string) $monthStart);
        $monthEnd = trim((string) $monthEnd);
        $this->expireCompOffCredits($employeeId);

        $credits = [];
        foreach ($this->listCompOff($employeeId) as $row) {
            $workDate = (string) ($row['work_date'] ?? '');
            $credits[] = [
                'id' => (int) ($row['id'] ?? 0),
                'work_date' => $workDate,
                'credit_days' => round((float) ($row['credit_days'] ?? 0), 2),
                'status' => (string) ($row['status'] ?? ''),
                'expires_at' => (string) ($row['expires_at'] ?? ''),
                'reason' => trim((string) ($row['reason'] ?? '')),
                'used_leave_id' => (int) ($row['used_leave_id'] ?? 0),
                'in_payroll_month' => ($workDate >= $monthStart && $workDate <= $monthEnd),
            ];
        }

        return [
            'enabled' => $this->policyBool('comp_off_enabled', true),
            'available' => round($this->getCompOffAvailableBalance($employeeId), 2),
            'validity_days' => $this->policyInt('comp_off_validity_days', 90),
            'requires_approval' => $this->policyBool('comp_off_requires_approval', true),
            'credits' => $credits,
        ];
    }

    public function hasCompOffCreditForWorkDate($employeeId, $workDate)
    {
        $employeeId = (int) $employeeId;
        $workDate = mysqli_real_escape_string($this->conn, trim((string) $workDate));
        if ($employeeId < 1 || $workDate === '') {
            return false;
        }
        $sql = "SELECT id FROM employee_comp_off
            WHERE employee_id = $employeeId AND work_date = '$workDate'
              AND status NOT IN ('Rejected','Expired') LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        return ($res && $res->num_rows > 0);
    }

    /**
     * HR grants comp-off for an employee who worked on weekly off / holiday.
     */
    public function hrGrantCompOff($employeeId, $workDate, $creditDays, $reason, $createdBy = '', $autoApprove = true)
    {
        $result = $this->requestCompOff($employeeId, $workDate, $creditDays, $reason, $createdBy);
        if (!empty($result['error'])) {
            return $result;
        }
        $compOffId = (int) ($result['comp_off_id'] ?? 0);
        if ($autoApprove && $compOffId > 0) {
            $approve = $this->approveCompOff($compOffId, 0, true);
            if (!empty($approve['error'])) {
                return $approve;
            }
            return [
                'error' => false,
                'message' => 'Comp-off credited for ' . $workDate . ' (' . round((float) $creditDays, 2) . ' day(s)).',
                'comp_off_id' => $compOffId,
            ];
        }
        return $result;
    }

    /**
     * HR reclassifies an approved leave to CL, SL, or comp-off (balance updated).
     */
    public function changeApprovedLeaveType($leaveId, $employeeId, $newType, $changedBy = '')
    {
        $leaveId = (int) $leaveId;
        $employeeId = (int) $employeeId;
        $newType = strtoupper(trim((string) $newType));

        if (!in_array($newType, ['CL', 'SL', 'COMPOFF', 'UNPAID'], true)) {
            return ['error' => true, 'message' => 'Choose CL, SL, Comp-off, or Unpaid Leave only.'];
        }
        if ($newType === 'UNPAID' && !$this->policyBool('unpaid_leave_enabled', true)) {
            return ['error' => true, 'message' => 'Unpaid leave is not enabled.'];
        }

        $leave = $this->getLeaveById($leaveId);
        if (!$leave || (int) ($leave['EmployeeID'] ?? 0) !== $employeeId) {
            return ['error' => true, 'message' => 'Leave record not found for this employee.'];
        }
        if (strcasecmp((string) ($leave['Status'] ?? ''), 'Approved') !== 0) {
            return ['error' => true, 'message' => 'Only approved leave can be updated by HR.'];
        }
        if ($this->leaveColumnExists('CancelledAt') && !empty($leave['CancelledAt'])) {
            return ['error' => true, 'message' => 'This leave is cancelled.'];
        }

        $oldType = strtoupper(trim((string) ($leave['TypeOfLeave'] ?? '')));
        if ($oldType === 'COMPOFF' && $newType !== 'COMPOFF') {
            return ['error' => true, 'message' => 'Comp-off leave cannot be changed to CL/SL here. Cancel and re-apply, or contact system admin.'];
        }

        if ($oldType === $newType) {
            return [
                'error' => false,
                'message' => 'Leave is already ' . $newType . '.',
                'leave_id' => $leaveId,
                'new_type' => $newType,
            ];
        }

        $this->syncApprovedLeaveDeductions($employeeId);
        $leave = $this->getLeaveById($leaveId);
        $days = $this->resolveLeaveDays($leave);
        if ($days <= 0) {
            return ['error' => true, 'message' => 'Could not calculate leave days for this record.'];
        }

        $alreadyDeducted = (float) ($leave['BalanceDeducted'] ?? 0);
        if ($alreadyDeducted > 0 && in_array($oldType, ['CL', 'SL'], true)) {
            $this->creditMonthlyAndAnnual(
                $employeeId,
                $oldType,
                $alreadyDeducted,
                $leave['FromDate'],
                !empty($leave['IsAdvanceLeave']),
                $leaveId
            );
        }

        if ($newType !== 'UNPAID') {
            $balanceCheck = $this->checkBalanceForLeave($employeeId, $newType, $days, $leave['FromDate'], 0);
        } else {
            $balanceCheck = ['error' => false, 'is_unpaid' => true, 'is_advance' => false];
        }
        if (!empty($balanceCheck['error'])) {
            if ($alreadyDeducted > 0 && in_array($oldType, ['CL', 'SL'], true)) {
                $this->deductMonthlyAndAnnual(
                    $employeeId,
                    $oldType,
                    $alreadyDeducted,
                    $leave['FromDate'],
                    !empty($leave['IsAdvanceLeave']),
                    $leaveId
                );
            }
            $balanceCheck['balance'] = $this->formatBalanceForPayrollApi($employeeId, $leave['FromDate']);
            if ($newType === 'COMPOFF') {
                $avail = (float) ($balanceCheck['balance']['comp_off']['available'] ?? 0);
                if (!empty($balanceCheck['message']) && stripos($balanceCheck['message'], 'insufficient') !== false) {
                    $balanceCheck['message'] .= ' (Comp-off available: ' . round($avail, 2) . ' day(s). Grant comp-off for work on weekly off/holiday first.)';
                }
            } else {
                $avail = (float) ($balanceCheck['net_available'] ?? $balanceCheck['balance'][$newType === 'CL' ? 'cl' : 'sl']['available'] ?? 0);
                if (!empty($balanceCheck['message']) && stripos($balanceCheck['message'], 'insufficient') !== false) {
                    $balanceCheck['message'] .= ' (' . $newType . ' available after FY/carry rules: ' . round($avail, 2) . ' day(s).)';
                }
            }
            return $balanceCheck;
        }

        $isAdvance = !in_array($newType, ['COMPOFF', 'UNPAID'], true) && !empty($balanceCheck['is_advance']);
        $newTypeEsc = mysqli_real_escape_string($this->conn, $newType);
        $isAdvanceInt = $isAdvance ? 1 : 0;
        $advanceSql = $this->leaveColumnExists('IsAdvanceLeave')
            ? ", IsAdvanceLeave = $isAdvanceInt"
            : '';
        mysqli_query($this->conn, "UPDATE employee_leave
            SET TypeOfLeave = '$newTypeEsc', BalanceDeducted = 0 $advanceSql
            WHERE ID = $leaveId AND EmployeeID = $employeeId");

        $updatedLeave = $this->getLeaveById($leaveId);
        if (!$updatedLeave || strtoupper(trim((string) ($updatedLeave['TypeOfLeave'] ?? ''))) !== $newType) {
            return ['error' => true, 'message' => 'Could not update leave type. Please try again.'];
        }

        $this->deductBalanceForApprovedLeave($updatedLeave);

        $changedByEsc = mysqli_real_escape_string($this->conn, substr((string) $changedBy, 0, 128));
        if ($this->leaveColumnExists('ReasonOfLeave')) {
            $note = ' [HR reclassified from ' . ($oldType !== '' ? $oldType : 'unset') . ' to ' . $newType;
            if ($changedByEsc !== '') {
                $note .= ' by ' . $changedByEsc;
            }
            $note .= ']';
            mysqli_query($this->conn, "UPDATE employee_leave
                SET ReasonOfLeave = CONCAT(IFNULL(ReasonOfLeave, ''), '" . mysqli_real_escape_string($this->conn, $note) . "')
                WHERE ID = $leaveId");
        }

        $summary = $this->getBalanceSummary($employeeId, $leave['FromDate']);
        $advanceNote = $isAdvance ? ' Advance leave applied.' : '';

        return [
            'error' => false,
            'message' => 'Leave updated to ' . $newType . ' (' . round($days, 2) . ' day(s)). Leave balance updated.' . $advanceNote,
            'leave_id' => $leaveId,
            'old_type' => $oldType,
            'new_type' => $newType,
            'days' => $days,
            'is_advance' => $isAdvance,
            'balance' => $this->formatBalanceForPayrollApi($employeeId, $leave['FromDate']),
        ];
    }

    private function creditMonthlyAndAnnual($employeeId, $leaveType, $days, $fromDate, $wasAdvance, $leaveId)
    {
        if ($days <= 0 || !in_array($leaveType, ['CL', 'SL'], true)) {
            return;
        }
        if (!$this->balanceTableExists()) {
            return;
        }

        $cal = $this->calendarYearMonthForDate($fromDate);
        $calendarYear = $cal['year'];
        $calendarMonth = $cal['month'];
        $leaveYear = $this->leaveYearForDate($fromDate);
        $this->ensureMonthlyBalance($employeeId, $calendarYear, $calendarMonth);

        $before = 0.0;
        $stmtSel = $this->conn->prepare(
            'SELECT closing_available FROM employee_leave_monthly_balance
             WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ? LIMIT 1'
        );
        if ($stmtSel) {
            $stmtSel->bind_param('iiis', $employeeId, $calendarYear, $calendarMonth, $leaveType);
            $stmtSel->execute();
            $resSel = $stmtSel->get_result();
            if ($resSel && ($rowSel = $resSel->fetch_assoc())) {
                $before = (float) $rowSel['closing_available'];
            }
            $stmtSel->close();
        }

        $stmt = $this->conn->prepare(
            'UPDATE employee_leave_monthly_balance
             SET used = GREATEST(0, used - ?),
                 closing_available = GREATEST(0, monthly_entitled + carried_in - GREATEST(0, used - ?) - pending_reserved),
                 updated_at = NOW()
             WHERE employee_id = ? AND balance_year = ? AND balance_month = ? AND leave_type = ?'
        );
        if ($stmt) {
            $stmt->bind_param('ddiiis', $days, $days, $employeeId, $calendarYear, $calendarMonth, $leaveType);
            $stmt->execute();
            $stmt->close();
        }

        $annualField = $leaveType === 'CL' ? 'cl_used_ytd' : 'sl_used_ytd';
        $advanceField = $leaveType === 'CL' ? 'cl_advance_used' : 'sl_advance_used';
        $this->ensureAnnualSummary($employeeId, $leaveYear);
        mysqli_query($this->conn, "UPDATE employee_leave_annual_summary
            SET $annualField = GREATEST(0, $annualField - $days), updated_at = NOW()
            WHERE employee_id = $employeeId AND balance_year = $leaveYear");
        if ($wasAdvance) {
            mysqli_query($this->conn, "UPDATE employee_leave_annual_summary
                SET $advanceField = GREATEST(0, $advanceField - $days), updated_at = NOW()
                WHERE employee_id = $employeeId AND balance_year = $leaveYear");
        }

        $after = $before + $days;
        $this->logBalanceChange($employeeId, $leaveId, null, 'credit_reclassify', $leaveType, $days, $before, $after, 'HR changed leave type');
    }
}
