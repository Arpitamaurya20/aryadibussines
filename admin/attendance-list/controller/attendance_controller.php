<?php
function getAllAttendanceDetails($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'attendance_login', $where);
	return $response;
}

function InsertAttendance($conn,$data)
{
	// exit;
	date_default_timezone_set('Asia/Kolkata');
	$RecordDate = $data["RecordDate"];
    $EmployeeID = $data["EmployeeID"];
	$InTime = date('H:i');
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $holidays_query = "INSERT INTO attendance_login (EmployeeID,RecordDate,InTime,CreatedBy,CreatedDate,CreatedTime ) VALUES('$EmployeeID','$RecordDate','$InTime','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $holidays_query);
    $response['message'] = "Attendance Added to the System";
    return $response;
}


function DeleteAttendance($conn,$data)
{
	$ID = $data['ID'];

	$query_parameter = " where ID = '$ID'";
	$response = delete_identity_filter($conn,"attendance_login",$query_parameter);
	return $response;
}

function UpdateAttendance($conn,$data)
{	
	date_default_timezone_set('Asia/Kolkata');
    $ID = $data['ID'];
    $Hours = $data['Hours'];
    $OutTime = date('H:i');
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    	$update_param = " OutTime = '$OutTime', Hours = '$Hours' where ID ='$ID'";
    	$response = _UpdateTableRecords($conn,'attendance_login', $update_param);
        $response['message'] = "Attendance Updated to the System";
    return $response;
}

function getEmployeeDataWithUserName($conn,$UserName)
{
    $where = " where UserName = '$UserName'";
    $response = _getTableDetails($conn,'users', $where);
    return $response;
}

function getAttendanceDataWithEmployeeID($conn,$EmployeeID)
{
    $where = " where EmployeeID = '$EmployeeID'";
    $response = _getTableRecords($conn,'attendance_login', $where);
    return $response;
}

function getAttendanceDataWithDateFilter($conn,$FromDate,$ToDate)
{
    $where = " where RecordDate BETWEEN  '$FromDate' AND '$ToDate'";
    $response = _getTableRecords($conn,'attendance_login', $where);
    return $response;
}

function getAttendanceDataInEmployeeWithDateFilter($conn,$FromDate,$ToDate,$EmployeeID)
{
    $where = " where EmployeeID = '$EmployeeID' AND RecordDate BETWEEN  '$FromDate' AND '$ToDate'";
    $response = _getTableRecords($conn,'attendance_login', $where);
    return $response;
}


function checkmanualAttendance($conn, $employee_id, $record_date)
{
    $where = "WHERE EmployeeID='$employee_id' AND RecordDate='$record_date'";
    return _getTableDetails($conn, 'employee_attendance', $where);
}

function updatemanualAttendance($conn, $id, $in_time, $out_time)
{
    $set_parts = array(
        "InTime='$in_time'",
        "OutTime='$out_time'",
        "ApprovalStatus='Pending'",
        "ApprovedBy=NULL",
        "ApprovedAt=NULL",
        "RejectionReason=NULL"
    );
    if (attendanceApprovalColumnExists($conn, 'SupervisorApprovedBy')) {
        $set_parts[] = 'SupervisorApprovedBy=NULL';
        $set_parts[] = 'SupervisorApprovedAt=NULL';
    }
    $query_parameter = implode(', ', $set_parts) . " WHERE ID=$id";
    return _UpdateTableRecords($conn, 'employee_attendance', $query_parameter);
}

function insertmanualAttendance($conn, $employee_id, $record_date, $in_time, $out_time)
{
    $sql = "INSERT INTO employee_attendance (EmployeeID, RecordDate, InTime, OutTime, ApprovalStatus) 
            VALUES ('$employee_id','$record_date','$in_time','$out_time','Pending')";
    return _InsertTableRecords($conn, $sql);
}

function attendanceApprovalColumnExists($conn, $column_name)
{
    $column_name = mysqli_real_escape_string($conn, $column_name);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `employee_attendance` LIKE '$column_name'");
    return ($result && mysqli_num_rows($result) > 0);
}

function getAttendanceApprovalSqlParts($conn)
{
    $parts = array(
        'supervisor_select' => 'NULL AS SupervisorApproverName, NULL AS SupervisorApprovedAt,',
        'supervisor_join' => '',
        'status_column' => 'Pending AS ApprovalStatus',
        'hr_select' => 'NULL AS HrApproverName, NULL AS HrApprovedAt,',
        'hr_join' => ''
    );

    if (attendanceApprovalColumnExists($conn, 'ApprovalStatus')) {
        $parts['status_column'] = 'a.ApprovalStatus';
    }
    if (attendanceApprovalColumnExists($conn, 'SupervisorApprovedBy')) {
        $parts['supervisor_select'] = 'supervisor_approver.Name AS SupervisorApproverName, a.SupervisorApprovedAt,';
        $parts['supervisor_join'] = 'LEFT JOIN employees supervisor_approver ON a.SupervisorApprovedBy = supervisor_approver.ID';
    }
    if (attendanceApprovalColumnExists($conn, 'ApprovedBy')) {
        $parts['hr_select'] = 'hr_approver.Name AS HrApproverName, a.ApprovedAt AS HrApprovedAt,';
        $parts['hr_join'] = 'LEFT JOIN employees hr_approver ON a.ApprovedBy = hr_approver.ID';
    }

    return $parts;
}

function getAttendanceApprovalOrderSql()
{
    return ' ORDER BY a.RecordDate DESC, a.InTime DESC, a.ID DESC ';
}

function sendAttendanceDatatableJson($payload)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $json = json_encode($payload);
    if ($json === false) {
        $json = json_encode(array(
            'draw' => 0,
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'aaData' => array()
        ));
    }
    echo $json;
    exit;
}

function hasHrAttendanceApprovalAccess($roles)
{
    if (!isset($roles['EmployeeRoles']) || !is_array($roles['EmployeeRoles'])) {
        return false;
    }
    $hr_roles = array('HR', 'Super Admin', 'Admin');
    foreach ($roles['EmployeeRoles'] as $role) {
        if (in_array($role, $hr_roles)) {
            return true;
        }
    }
    return false;
}

function buildAttendanceStatusBadge($approval_status)
{
    $status = $approval_status ? $approval_status : 'Pending';
    if ($status === 'Approved') {
        return '<span class="badge badge-success">Approved (HR Final)</span>';
    }
    if ($status === 'SupervisorApproved') {
        return '<span class="badge badge-info">Supervisor Approved - Pending HR</span>';
    }
    if ($status === 'Rejected') {
        return '<span class="badge badge-danger">Rejected</span>';
    }
    if ($status === 'Pending') {
        return '<span class="badge badge-warning">Pending Supervisor</span>';
    }
    return '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
}

function buildAttendanceApprovalInfoHtml($record)
{
    $lines = array();
    if (!empty($record['SupervisorApproverName']) && !empty($record['SupervisorApprovedAt'])) {
        $lines[] = '<strong>Supervisor:</strong> ' . htmlspecialchars($record['SupervisorApproverName']) . '<br><small>' . htmlspecialchars($record['SupervisorApprovedAt']) . '</small>';
    }
    if (!empty($record['HrApproverName']) && !empty($record['HrApprovedAt'])) {
        $lines[] = '<strong>HR Final:</strong> ' . htmlspecialchars($record['HrApproverName']) . '<br><small>' . htmlspecialchars($record['HrApprovedAt']) . '</small>';
    }
    if (($record['ApprovalStatus'] ?? '') === 'Rejected' && !empty($record['RejectionReason'])) {
        $lines[] = '<small class="text-danger">Reason: ' . htmlspecialchars($record['RejectionReason']) . '</small>';
    }
    if (empty($lines)) {
        return '-';
    }
    return implode('<br>', $lines);
}

function buildAttendanceCheckInOutHtml($record)
{
    $InTime = $record['InTime'] ?? '';
    $OutTime = $record['OutTime'] ?? '';
    $CheckinImage = $record['CheckinImage'] ?? '';
    $CheckoutImage = $record['CheckoutImage'] ?? '';
    $Latitude = $record['Latitude'] ?? '';
    $Longitude = $record['Longitude'] ?? '';
    $CheckoutLatitude = $record['CheckoutLatitude'] ?? '';
    $CheckoutLongitude = $record['CheckoutLongitude'] ?? '';

    $InTime_html = $InTime ? $InTime : '-';
    if (!empty($CheckinImage)) {
        $InTime_html .= " <a href='javascript:void(0);' onclick='ViewAttendanceImage(\"" . $CheckinImage . "\")' title='View check-in photo'><i class='fal fa-camera'></i> Photo</a>";
    }
    if (!empty($Latitude) && !empty($Longitude)) {
        $InTime_html .= " <br><a href='javascript:void(0);' onclick='openLocationModal(" . $Latitude . "," . $Longitude . ")' style='font-size:12px;color:#184384;'><i class='fal fa-map-marker-alt'></i> Check-in Location</a>";
    }

    $OutTime_html = $OutTime ? $OutTime : '-';
    if (!empty($CheckoutImage)) {
        $OutTime_html .= " <a href='javascript:void(0);' onclick='ViewAttendanceImage(\"" . $CheckoutImage . "\")' title='View check-out photo'><i class='fal fa-camera'></i> Photo</a>";
    }
    if (!empty($CheckoutLatitude) && !empty($CheckoutLongitude)) {
        $OutTime_html .= " <br><a href='javascript:void(0);' onclick='openLocationModal(" . $CheckoutLatitude . "," . $CheckoutLongitude . ")' style='font-size:12px;color:#184384;'><i class='fal fa-map-marker-alt'></i> Check-out Location</a>";
    }

    return array('CheckInTime' => $InTime_html, 'CheckOutTime' => $OutTime_html);
}

function getSupervisedEmployeeIds($conn, $supervisor_employee_id, $active_only = true)
{
    $ids = array();
    if ($supervisor_employee_id == -1 || $supervisor_employee_id === '' || $supervisor_employee_id === null) {
        return $ids;
    }
    $supervisor_employee_id = (int) $supervisor_employee_id;
    $sql = "SELECT ID FROM employees WHERE Supervisor = $supervisor_employee_id";
    if ($active_only) {
        $sql .= " AND IsActive = 1";
    }
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int) $row['ID'];
        }
    }
    return $ids;
}

function getSupervisedEmployeesList($conn, $supervisor_employee_id, $active_only = true)
{
    $list = array();
    if ($supervisor_employee_id == -1 || $supervisor_employee_id === '' || $supervisor_employee_id === null) {
        return $list;
    }
    $supervisor_employee_id = (int) $supervisor_employee_id;
    $sql = "SELECT * FROM employees WHERE Supervisor = $supervisor_employee_id";
    if ($active_only) {
        $sql .= " AND IsActive = 1";
    }
    $sql .= " ORDER BY Name ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list;
}

function employeeHasSupervisedTeam($conn, $supervisor_employee_id)
{
    return count(getSupervisedEmployeeIds($conn, $supervisor_employee_id, true)) > 0;
}

function getAttendanceRecordById($conn, $attendance_id)
{
    $attendance_id = (int) $attendance_id;
    $where = " WHERE ID = $attendance_id";
    return _getTableDetails($conn, 'employee_attendance', $where);
}

function canSupervisorApproveAttendance($conn, $attendance_id, $approver_employee_id)
{
    $attendance = getAttendanceRecordById($conn, $attendance_id);
    if (!$attendance || !isset($attendance['EmployeeID'])) {
        return false;
    }
    if (($attendance['ApprovalStatus'] ?? '') !== 'Pending') {
        return false;
    }
    $employee_id = (int) $attendance['EmployeeID'];
    $approver_employee_id = (int) $approver_employee_id;
    if ($approver_employee_id <= 0) {
        return false;
    }
    $where = " WHERE ID = $employee_id AND Supervisor = $approver_employee_id AND IsActive = 1";
    $employee = _getTableDetails($conn, 'employees', $where);
    return !empty($employee);
}

function canHrApproveAttendance($conn, $attendance_id, $roles)
{
    if (!hasHrAttendanceApprovalAccess($roles)) {
        return false;
    }
    $attendance = getAttendanceRecordById($conn, $attendance_id);
    if (!$attendance) {
        return false;
    }
    return ($attendance['ApprovalStatus'] ?? '') === 'SupervisorApproved';
}

function attendanceUpdateSucceeded($conn, $update_result)
{
    if (!is_array($update_result) || !isset($update_result['error']) || $update_result['error'] === true) {
        return false;
    }
    return mysqli_affected_rows($conn) > 0;
}

function attendanceUpdateErrorMessage($update_result)
{
    if (is_array($update_result) && !empty($update_result['message'])) {
        return $update_result['message'];
    }
    return 'Unable to update attendance record.';
}

function supervisorApproveEmployeeAttendance($conn, $attendance_id, $approver_employee_id)
{
    $attendance_id = (int) $attendance_id;
    $approver_employee_id = (int) $approver_employee_id;
    $approved_at = date('Y-m-d H:i:s');

    $set_parts = array(
        "ApprovalStatus = 'SupervisorApproved'",
        "SupervisorApprovedBy = $approver_employee_id",
        "SupervisorApprovedAt = '$approved_at'",
        "ApprovedBy = NULL",
        "ApprovedAt = NULL",
        "RejectionReason = NULL"
    );
    if (!attendanceApprovalColumnExists($conn, 'SupervisorApprovedBy')) {
        $set_parts = array(
            "ApprovalStatus = 'SupervisorApproved'",
            "ApprovedBy = NULL",
            "ApprovedAt = NULL",
            "RejectionReason = NULL"
        );
    }

    $update_param = implode(', ', $set_parts) . " WHERE ID = $attendance_id AND ApprovalStatus = 'Pending'";
    $result = _UpdateTableRecords($conn, 'employee_attendance', $update_param);
    $success = attendanceUpdateSucceeded($conn, $result);
    if ($success) {
        require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
        pnc_notifyAttendanceDecision($conn, $attendance_id, 'approved', 'supervisor');
        attendanceNotifyHrOnSupervisorApproved($conn, $attendance_id);
    }
    return array('success' => $success, 'result' => $result);
}

function supervisorRejectEmployeeAttendance($conn, $attendance_id, $approver_employee_id, $reason = '')
{
    $attendance_id = (int) $attendance_id;
    $approver_employee_id = (int) $approver_employee_id;
    $approved_at = date('Y-m-d H:i:s');
    $reason = mysqli_real_escape_string($conn, trim($reason));
    $update_param = "ApprovalStatus = 'Rejected', ApprovedBy = $approver_employee_id, ApprovedAt = '$approved_at', RejectionReason = '$reason' WHERE ID = $attendance_id AND ApprovalStatus = 'Pending'";
    $result = _UpdateTableRecords($conn, 'employee_attendance', $update_param);
    $success = attendanceUpdateSucceeded($conn, $result);
    if ($success) {
        require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
        pnc_notifyAttendanceDecision($conn, $attendance_id, 'rejected', 'supervisor');
    }
    return array('success' => $success, 'result' => $result);
}

function hrFinalApproveEmployeeAttendance($conn, $attendance_id, $approver_employee_id)
{
    $attendance_id = (int) $attendance_id;
    $approver_sql = 'NULL';
    if ($approver_employee_id > 0) {
        $approver_sql = (int) $approver_employee_id;
    }
    $approved_at = date('Y-m-d H:i:s');
    $update_param = "ApprovalStatus = 'Approved', ApprovedBy = $approver_sql, ApprovedAt = '$approved_at', RejectionReason = NULL WHERE ID = $attendance_id AND ApprovalStatus = 'SupervisorApproved'";
    $result = _UpdateTableRecords($conn, 'employee_attendance', $update_param);
    $success = attendanceUpdateSucceeded($conn, $result);
    if ($success) {
        require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
        pnc_notifyAttendanceDecision($conn, $attendance_id, 'approved', 'hr');
    }
    return array('success' => $success, 'result' => $result);
}

function hrRejectEmployeeAttendance($conn, $attendance_id, $approver_employee_id, $reason = '')
{
    $attendance_id = (int) $attendance_id;
    $approver_sql = 'NULL';
    if ($approver_employee_id > 0) {
        $approver_sql = (int) $approver_employee_id;
    }
    $approved_at = date('Y-m-d H:i:s');
    $reason = mysqli_real_escape_string($conn, trim($reason));
    $update_param = "ApprovalStatus = 'Rejected', ApprovedBy = $approver_sql, ApprovedAt = '$approved_at', RejectionReason = '$reason' WHERE ID = $attendance_id AND ApprovalStatus = 'SupervisorApproved'";
    $result = _UpdateTableRecords($conn, 'employee_attendance', $update_param);
    $success = attendanceUpdateSucceeded($conn, $result);
    if ($success) {
        require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
        pnc_notifyAttendanceDecision($conn, $attendance_id, 'rejected', 'hr');
    }
    return array('success' => $success, 'result' => $result);
}

function attendanceParseMultiFilterValues($value)
{
    if (is_array($value)) {
        $items = $value;
    } else {
        $value = trim((string) $value);
        if ($value === '' || $value === '-1') {
            return array();
        }
        $items = preg_split('/\s*,\s*/', $value);
    }

    $result = array();
    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item !== '' && $item !== '-1') {
            $result[] = $item;
        }
    }

    return array_values(array_unique($result));
}

function attendanceAppendSqlInClause($conn, $column, $values, $numeric = false)
{
    if (empty($values)) {
        return '';
    }

    if ($numeric) {
        $ids = array();
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return '';
        }
        return ' AND ' . $column . ' IN (' . implode(',', $ids) . ')';
    }

    $escaped = array();
    foreach ($values as $value) {
        $escaped[] = "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
    }
    if (empty($escaped)) {
        return '';
    }

    return ' AND ' . $column . ' IN (' . implode(',', $escaped) . ')';
}

function attendanceHrActionableStatusSql()
{
    return "a.ApprovalStatus = 'SupervisorApproved'";
}

function attendanceBuildApprovalStatusFilterSql($conn, $filters)
{
    if (!attendanceApprovalColumnExists($conn, 'ApprovalStatus')) {
        return '';
    }

    $status_values = attendanceParseMultiFilterValues(isset($filters['approval_status']) ? $filters['approval_status'] : '');
    if (empty($status_values)) {
        return '';
    }

    $or_parts = array();
    if (in_array('hr_actionable', $status_values, true)) {
        $or_parts[] = attendanceHrActionableStatusSql();
    }
    if (in_array('pending_supervisor', $status_values, true)) {
        $or_parts[] = "a.ApprovalStatus = 'Pending'";
    }
    $status_values = array_values(array_diff($status_values, array('hr_actionable', 'pending_supervisor')));

    if (!empty($status_values)) {
        $escaped = array();
        foreach ($status_values as $value) {
            $escaped[] = "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
        }
        $or_parts[] = 'a.ApprovalStatus IN (' . implode(',', $escaped) . ')';
    }

    if (empty($or_parts)) {
        return '';
    }

    return ' AND (' . implode(' OR ', $or_parts) . ')';
}

function buildAttendanceListFilterSql($conn, $filters = array(), $options = array())
{
    $scope = isset($options['scope']) ? (string) $options['scope'] : 'admin';
    $supervisor_employee_id = isset($options['supervisor_employee_id']) ? (int) $options['supervisor_employee_id'] : -1;

    $where = ' WHERE 1=1 ';

    if ($scope === 'supervisor') {
        $supervised_ids = getSupervisedEmployeeIds($conn, $supervisor_employee_id, false);
        if (empty($supervised_ids)) {
            return ' WHERE 1=0 ';
        }
        $where .= ' AND a.EmployeeID IN (' . implode(',', $supervised_ids) . ')';
    }

    $filter_date = isset($filters['filter_date']) ? trim((string) $filters['filter_date']) : '';
    if ($filter_date !== '' && strtolower($filter_date) !== 'all' && strpos($filter_date, ' - ') !== false) {
        $date_parts = explode(' - ', $filter_date, 2);
        if (count($date_parts) === 2) {
            $start_date = mysqli_real_escape_string($conn, trim($date_parts[0]));
            $end_date = mysqli_real_escape_string($conn, trim($date_parts[1]));
            $where .= " AND (a.RecordDate >= '$start_date' AND a.RecordDate <= '$end_date')";
        }
    }

    $employee_ids = attendanceParseMultiFilterValues(isset($filters['employee_id']) ? $filters['employee_id'] : '');
    $where .= attendanceAppendSqlInClause($conn, 'a.EmployeeID', $employee_ids, true);

    if (!empty($filters['employee_number'])) {
        $employee_number = mysqli_real_escape_string($conn, trim((string) $filters['employee_number']));
        $where .= " AND (b.EmployeeNumber LIKE '%$employee_number%' OR CAST(b.ID AS CHAR) LIKE '%$employee_number%')";
    }

    $where .= attendanceBuildApprovalStatusFilterSql($conn, $filters);

    $state_values = attendanceParseMultiFilterValues(isset($filters['state']) ? $filters['state'] : '');
    $where .= attendanceAppendSqlInClause($conn, 'b.State', $state_values, false);

    $department_values = attendanceParseMultiFilterValues(isset($filters['department']) ? $filters['department'] : '');
    $where .= attendanceAppendSqlInClause($conn, 'b.Department', $department_values, false);

    $designation_values = attendanceParseMultiFilterValues(isset($filters['designation']) ? $filters['designation'] : '');
    $where .= attendanceAppendSqlInClause($conn, 'b.Designation', $designation_values, false);

    if (!empty($filters['search'])) {
        $search = mysqli_real_escape_string($conn, trim((string) $filters['search']));
        $where .= " AND (b.Name LIKE '%$search%' OR b.EmployeeNumber LIKE '%$search%' OR CAST(b.ID AS CHAR) LIKE '%$search%')";
    }

    return $where;
}

function getAttendanceFilterOptions($conn)
{
    $options = array(
        'states' => array(),
        'departments' => array(),
        'designations' => array(),
    );
    $state_rows = _getSQLRecords($conn, "SELECT DISTINCT State FROM employees WHERE IsActive = 1 AND Vendor = 0 AND State <> '' ORDER BY State ASC");
    foreach ($state_rows as $row) {
        $options['states'][] = $row['State'];
    }
    $department_rows = _getSQLRecords($conn, "SELECT DISTINCT Department FROM employees WHERE IsActive = 1 AND Vendor = 0 AND Department <> '' ORDER BY Department ASC");
    foreach ($department_rows as $row) {
        $options['departments'][] = $row['Department'];
    }
    $designation_rows = _getSQLRecords($conn, "SELECT DISTINCT Designation FROM employees WHERE IsActive = 1 AND Vendor = 0 AND Designation <> '' ORDER BY Designation ASC");
    foreach ($designation_rows as $row) {
        $options['designations'][] = $row['Designation'];
    }
    return $options;
}

function attendanceBulkProcessIds($conn, $attendance_ids, $processor)
{
    $summary = array(
        'success' => 0,
        'failed' => 0,
    );

    foreach ($attendance_ids as $attendance_id) {
        $attendance_id = (int) $attendance_id;
        if ($attendance_id <= 0) {
            continue;
        }

        $result = call_user_func($processor, $conn, $attendance_id);
        if (!empty($result['success'])) {
            $summary['success']++;
        } else {
            $summary['failed']++;
        }
    }

    return $summary;
}

function buildAttendanceBulkActionMessage($summary, $action_label)
{
    $success = (int) ($summary['success'] ?? 0);
    $failed = (int) ($summary['failed'] ?? 0);
    if ($success <= 0 && $failed <= 0) {
        return 'No attendance records were updated.';
    }
    if ($success <= 0) {
        return 'Unable to ' . $action_label . ' selected record(s).';
    }
    return $success . ' record(s) ' . $action_label . '. ' . $failed . ' record(s) could not be updated.';
}

function supervisorBulkApproveEmployeeAttendance($conn, $attendance_ids, $approver_employee_id)
{
    $approver_employee_id = (int) $approver_employee_id;
    return attendanceBulkProcessIds($conn, $attendance_ids, function ($conn, $attendance_id) use ($approver_employee_id) {
        if (!canSupervisorApproveAttendance($conn, $attendance_id, $approver_employee_id)) {
            return array('success' => false);
        }
        return supervisorApproveEmployeeAttendance($conn, $attendance_id, $approver_employee_id);
    });
}

function supervisorBulkRejectEmployeeAttendance($conn, $attendance_ids, $approver_employee_id, $reason = '')
{
    $approver_employee_id = (int) $approver_employee_id;
    return attendanceBulkProcessIds($conn, $attendance_ids, function ($conn, $attendance_id) use ($approver_employee_id, $reason) {
        if (!canSupervisorApproveAttendance($conn, $attendance_id, $approver_employee_id)) {
            return array('success' => false);
        }
        return supervisorRejectEmployeeAttendance($conn, $attendance_id, $approver_employee_id, $reason);
    });
}

function hrBulkApproveEmployeeAttendance($conn, $attendance_ids, $approver_employee_id, $roles)
{
    $approver_employee_id = (int) $approver_employee_id;
    return attendanceBulkProcessIds($conn, $attendance_ids, function ($conn, $attendance_id) use ($approver_employee_id, $roles) {
        if (!canHrApproveAttendance($conn, $attendance_id, $roles)) {
            return array('success' => false);
        }
        return hrFinalApproveEmployeeAttendance($conn, $attendance_id, $approver_employee_id);
    });
}

function hrBulkRejectEmployeeAttendance($conn, $attendance_ids, $approver_employee_id, $roles, $reason = '')
{
    $approver_employee_id = (int) $approver_employee_id;
    return attendanceBulkProcessIds($conn, $attendance_ids, function ($conn, $attendance_id) use ($approver_employee_id, $roles, $reason) {
        if (!canHrApproveAttendance($conn, $attendance_id, $roles)) {
            return array('success' => false);
        }
        return hrRejectEmployeeAttendance($conn, $attendance_id, $approver_employee_id, $reason);
    });
}

function formatAttendanceApprovalDatatableRow($conn, $record, $employee_obj, $options = array())
{
    $scope = isset($options['scope']) ? (string) $options['scope'] : 'supervisor';
    $roles = isset($options['roles']) ? $options['roles'] : array();
    $supervisor_employee_id = isset($options['supervisor_employee_id']) ? (int) $options['supervisor_employee_id'] : -1;
    $attendance_id = (int) ($record['ID'] ?? 0);

    $in_time = isset($record['InTime']) ? $record['InTime'] : '';
    $out_time = isset($record['OutTime']) ? $record['OutTime'] : '';
    $duration = $employee_obj->calculatetimeDifference($in_time, $out_time);
    $time_html = buildAttendanceCheckInOutHtml($record);
    $approval_status = isset($record['ApprovalStatus']) ? $record['ApprovalStatus'] : 'Pending';
    $approval_row = array(
        'ApprovalStatus' => $approval_status,
        'SupervisorApproverName' => isset($record['SupervisorApproverName']) ? $record['SupervisorApproverName'] : '',
        'SupervisorApprovedAt' => isset($record['SupervisorApprovedAt']) ? $record['SupervisorApprovedAt'] : '',
        'HrApproverName' => isset($record['HrApproverName']) ? $record['HrApproverName'] : '',
        'HrApprovedAt' => isset($record['HrApprovedAt']) ? $record['HrApprovedAt'] : (isset($record['ApprovedAt']) ? $record['ApprovedAt'] : ''),
        'RejectionReason' => isset($record['RejectionReason']) ? $record['RejectionReason'] : ''
    );

    if ($scope === 'list') {
        return array(
            'EmployeeName' => isset($record['Name']) ? $record['Name'] : '',
            'RecordDate' => isset($record['RecordDate']) ? $record['RecordDate'] : '',
            'CheckInTime' => $time_html['CheckInTime'],
            'CheckOutTime' => $time_html['CheckOutTime'],
            'Duration' => $duration,
            'ApprovalStatus' => buildAttendanceStatusBadge($approval_status),
            'ApprovalInfo' => buildAttendanceApprovalInfoHtml($approval_row),
            'State' => isset($record['State']) ? $record['State'] : '',
        );
    }

    $select = '';
    $actions = '-';
    if ($scope === 'supervisor' && $approval_status === 'Pending' && canSupervisorApproveAttendance($conn, $attendance_id, $supervisor_employee_id)) {
        $select = "<input type='checkbox' class='attendance-supervisor-select' value='" . $attendance_id . "'>";
        $actions = "<button type='button' class='btn btn-xs text-white mr-1 shadow-sm attendance-action-approve' onclick=\"ApproveAttendance('" . $attendance_id . "')\" style='background-color: #0284c7; border: none; border-radius: 4px;'>Supervisor Approve</button>";
        $actions .= "<button type='button' class='btn btn-xs text-white shadow-sm attendance-action-reject' onclick=\"openAttendanceRejectModal('" . $attendance_id . "')\" style='background-color: #003f88; border: none; border-radius: 4px;'>Reject</button>";
    } elseif ($scope === 'supervisor' && $approval_status === 'SupervisorApproved') {
        $actions = '<span class="text-info small">Waiting for HR final approval</span>';
    } elseif ($scope === 'hr' && $approval_status === 'SupervisorApproved' && canHrApproveAttendance($conn, $attendance_id, $roles)) {
        $select = "<input type='checkbox' class='attendance-hr-select' value='" . $attendance_id . "'>";
        $actions = "<button type='button' class='btn btn-xs text-white mr-1 shadow-sm attendance-action-approve' onclick=\"HrApproveAttendance('" . $attendance_id . "')\" style='background-color: #0284c7; border: none; border-radius: 4px;'>HR Final Approve</button>";
        $actions .= "<button type='button' class='btn btn-xs text-white shadow-sm attendance-action-reject' onclick=\"openHrAttendanceRejectModal('" . $attendance_id . "')\" style='background-color: #003f88; border: none; border-radius: 4px;'>Reject</button>";
    }

    $department = trim((string) ($record['Department'] ?? ''));

    return array(
        'DT_RowAttr' => array('data-attendance-id' => $attendance_id),
        'Select' => $select,
        'EmployeeName' => isset($record['Name']) ? $record['Name'] : '',
        'EmployeeNumber' => isset($record['EmployeeNumber']) ? $record['EmployeeNumber'] : '',
        'RecordDate' => isset($record['RecordDate']) ? $record['RecordDate'] : '',
        'CheckInTime' => $time_html['CheckInTime'],
        'CheckOutTime' => $time_html['CheckOutTime'],
        'Duration' => $duration,
        'ApprovalStatus' => buildAttendanceStatusBadge($approval_status),
        'ApprovalInfo' => buildAttendanceApprovalInfoHtml($approval_row),
        'Actions' => $actions,
        'State' => isset($record['State']) ? $record['State'] : '',
        'Department' => $department !== '' ? $department : '-',
        'Designation' => isset($record['Designation']) ? $record['Designation'] : '-',
    );
}

function getApproverNameById($conn, $approver_employee_id)
{
    $approver_employee_id = (int) $approver_employee_id;
    if ($approver_employee_id <= 0) {
        return '';
    }
    $where = " WHERE ID = $approver_employee_id";
    $employee = _getTableDetails($conn, 'employees', $where);
    return isset($employee['Name']) ? $employee['Name'] : '';
}

function calculateEmployeeAttendanceDayMetrics($in_time, $out_time, $is_weekend = '0')
{
    $metrics = array(
        'twh' => '-',
        'ot' => '-',
        'st' => '-',
        'work_status' => 'A',
        'row_class' => 'att-row-absent',
    );

    if (empty($in_time) || empty($out_time)) {
        if ((string) $is_weekend === '1') {
            $metrics['work_status'] = 'H';
            $metrics['row_class'] = 'att-row-weekend';
        }
        return $metrics;
    }

    $diff = strtotime($out_time) - strtotime($in_time);
    if ($diff < 0) {
        return $metrics;
    }

    $finaltime = gmdate('H:i', $diff);
    $othource = '00:00';
    $shorthource = '00:00';

    if ($finaltime < '02:00') {
        $metrics['work_status'] = 'A';
        $metrics['row_class'] = 'att-row-absent';
        $othource = $finaltime;
    } elseif ($finaltime < '04:00') {
        $metrics['work_status'] = 'HD';
        $metrics['row_class'] = 'att-row-half';
        $shorthource = gmdate('H:i', strtotime('04:00') - strtotime($finaltime));
    } elseif ($finaltime < '07:30') {
        $metrics['work_status'] = 'HDO';
        $metrics['row_class'] = 'att-row-half';
        $othource = gmdate('H:i', strtotime($finaltime) - strtotime('04:00'));
    } else {
        $metrics['work_status'] = 'P';
        $metrics['row_class'] = 'att-row-present';
        if ($finaltime < '09:00') {
            $shorthource = gmdate('H:i', strtotime('09:00') - strtotime($finaltime));
        } else {
            $othource = gmdate('H:i', strtotime($finaltime) - strtotime('09:00'));
        }
    }

    if ((string) $is_weekend === '1' && $metrics['work_status'] === 'A') {
        $metrics['work_status'] = 'H';
        $metrics['row_class'] = 'att-row-weekend';
    }

    $metrics['twh'] = $finaltime;
    $metrics['ot'] = $othource;
    $metrics['st'] = $shorthource;
    return $metrics;
}

function buildEmployeeAttendanceSupervisorStatusHtml($record)
{
    $status = isset($record['ApprovalStatus']) ? $record['ApprovalStatus'] : 'Pending';
    $supervisor_approved = !empty($record['SupervisorApprovedBy'])
        || $status === 'SupervisorApproved'
        || $status === 'Approved';

    if ($status === 'Rejected' && !$supervisor_approved) {
        return '<span class="att-status-pill att-status-rejected">Rejected</span>';
    }
    if ($supervisor_approved) {
        return '<span class="att-status-pill att-status-approved">Approved</span>';
    }
    return '<span class="att-status-pill att-status-pending">Pending</span>';
}

function buildEmployeeAttendanceHrStatusHtml($record)
{
    $status = isset($record['ApprovalStatus']) ? $record['ApprovalStatus'] : 'Pending';

    if ($status === 'Approved') {
        return '<span class="att-status-pill att-status-approved">Approved</span>';
    }
    if ($status === 'Rejected' && !empty($record['SupervisorApprovedBy'])) {
        return '<span class="att-status-pill att-status-rejected">Rejected</span>';
    }
    if ($status === 'SupervisorApproved') {
        return '<span class="att-status-pill att-status-pending">Pending</span>';
    }
    if ($status === 'Rejected') {
        return '<span class="att-status-pill att-status-muted">N/A</span>';
    }
    return '<span class="att-status-pill att-status-pending">Pending</span>';
}

function buildEmployeeAttendanceDualApprovalStatusHtml($record)
{
    $html = '<div class="att-approval-status">';
    $html .= '<div class="att-approval-row"><span class="att-approval-label">Supervisor</span>' . buildEmployeeAttendanceSupervisorStatusHtml($record) . '</div>';
    $html .= '<div class="att-approval-row"><span class="att-approval-label">HR</span>' . buildEmployeeAttendanceHrStatusHtml($record) . '</div>';
    $html .= '</div>';
    return $html;
}

function buildEmployeeAttendanceCheckInOutCellHtml($record, $type = 'in')
{
    if (!$record) {
        return '-';
    }

    if ($type === 'in') {
        $time_html = !empty($record['InTime']) ? htmlspecialchars($record['InTime']) : '-';
        $image = $record['CheckinImage'] ?? '';
        $latitude = $record['Latitude'] ?? '';
        $longitude = $record['Longitude'] ?? '';
    } else {
        $time_html = !empty($record['OutTime']) ? htmlspecialchars($record['OutTime']) : '-';
        $image = $record['CheckoutImage'] ?? '';
        $latitude = $record['CheckoutLatitude'] ?? '';
        $longitude = $record['CheckoutLongitude'] ?? '';
    }

    if ($time_html === '-') {
        return '-';
    }

    if (!empty($image)) {
        $time_html .= " <a href='javascript:void(0);' onclick='ViewAttendanceImage(\"" . htmlspecialchars($image, ENT_QUOTES) . "\")' title='View photo'><i class='fal fa-eye'></i></a>";
    }
    if (!empty($latitude) && !empty($longitude)) {
        $time_html .= "<br><a href='javascript:void(0);' onclick='openLocationModal(" . (float) $latitude . "," . (float) $longitude . ")' class='att-location-link'>View Location</a>";
    }

    return $time_html;
}

function fetchEmployeeAttendanceMonthData($conn, $employee_id, $year, $month)
{
    $employee_id = (int) $employee_id;
    $year = mysqli_real_escape_string($conn, (string) $year);
    $month = mysqli_real_escape_string($conn, (string) $month);

    $calendar_rows = array();
    $sql_calender = "SELECT `date`, `is_weekend`
                     FROM `calendar`
                     WHERE YEAR(`date`) = '$year' AND MONTH(`date`) = '$month'
                     ORDER BY `date` ASC";
    $result_calender = mysqli_query($conn, $sql_calender);
    if ($result_calender) {
        while ($row = mysqli_fetch_assoc($result_calender)) {
            $calendar_rows[] = $row;
        }
    }

    $attendance_data = array();
    $sql_attendance = "SELECT * FROM `employee_attendance`
                       WHERE EmployeeID = $employee_id
                       AND YEAR(RecordDate) = '$year'
                       AND MONTH(RecordDate) = '$month'";
    $result_attendance = mysqli_query($conn, $sql_attendance);
    if ($result_attendance) {
        while ($row = mysqli_fetch_assoc($result_attendance)) {
            $attendance_data[$row['RecordDate']] = $row;
        }
    }

    return array(
        'calendar_rows' => $calendar_rows,
        'attendance_data' => $attendance_data,
    );
}

function renderEmployeeAttendanceReportRows($calendar_rows, $attendance_data)
{
    $summary = array(
        'present' => 0,
        'absent' => 0,
        'half_day' => 0,
        'total_twh_minutes' => 0,
        'total_ot_minutes' => 0,
        'total_st_minutes' => 0,
    );
    $rows_html = '';

    foreach ($calendar_rows as $calendar_row) {
        $record_date = $calendar_row['date'];
        $record = isset($attendance_data[$record_date]) ? $attendance_data[$record_date] : null;
        $in_time = $record['InTime'] ?? '';
        $out_time = $record['OutTime'] ?? '';
        $metrics = calculateEmployeeAttendanceDayMetrics($in_time, $out_time, $calendar_row['is_weekend'] ?? '0');

        if ($metrics['work_status'] === 'P') {
            $summary['present']++;
        } elseif ($metrics['work_status'] === 'A') {
            $summary['absent']++;
        } elseif (in_array($metrics['work_status'], array('HD', 'HDO'), true)) {
            $summary['half_day']++;
        }

        if ($metrics['twh'] !== '-') {
            list($h, $m) = explode(':', $metrics['twh']);
            $summary['total_twh_minutes'] += ((int) $h * 60) + (int) $m;
        }
        if ($metrics['ot'] !== '-' && $metrics['ot'] !== '00:00') {
            list($h, $m) = explode(':', $metrics['ot']);
            $summary['total_ot_minutes'] += ((int) $h * 60) + (int) $m;
        }
        if ($metrics['st'] !== '-' && $metrics['st'] !== '00:00') {
            list($h, $m) = explode(':', $metrics['st']);
            $summary['total_st_minutes'] += ((int) $h * 60) + (int) $m;
        }

        $day_label = date('D', strtotime($record_date));
        $display_date = date('d M Y', strtotime($record_date));
        $approval_record = $record ? $record : array('ApprovalStatus' => 'Pending');
        $row_class = $metrics['row_class'];
        if ((string) ($calendar_row['is_weekend'] ?? '0') === '1') {
            $row_class .= ' att-row-weekend';
        }

        $rows_html .= '<tr class="' . htmlspecialchars($row_class) . '">';
        $rows_html .= '<td class="att-col-date"><span class="att-date-main">' . htmlspecialchars($display_date) . '</span><span class="att-date-day">' . htmlspecialchars($day_label) . '</span></td>';
        $rows_html .= '<td class="att-col-time">' . buildEmployeeAttendanceCheckInOutCellHtml($record, 'in') . '</td>';
        $rows_html .= '<td class="att-col-time">' . buildEmployeeAttendanceCheckInOutCellHtml($record, 'out') . '</td>';
        $rows_html .= '<td class="att-col-metric">' . htmlspecialchars($metrics['twh']) . '</td>';
        $rows_html .= '<td class="att-col-metric">' . htmlspecialchars($metrics['ot']) . '</td>';
        $rows_html .= '<td class="att-col-metric">' . htmlspecialchars($metrics['st']) . '</td>';
        $rows_html .= '<td class="att-col-status">' . buildEmployeeAttendanceDualApprovalStatusHtml($approval_record) . '</td>';
        $rows_html .= '</tr>';
    }

    $summary['total_twh'] = sprintf('%02d:%02d', floor($summary['total_twh_minutes'] / 60), $summary['total_twh_minutes'] % 60);
    $summary['total_ot'] = sprintf('%02d:%02d', floor($summary['total_ot_minutes'] / 60), $summary['total_ot_minutes'] % 60);
    $summary['total_st'] = sprintf('%02d:%02d', floor($summary['total_st_minutes'] / 60), $summary['total_st_minutes'] % 60);

    return array(
        'rows_html' => $rows_html,
        'summary' => $summary,
    );
}

function normalizeAttendanceRegularizationTime($time)
{
    $time = trim((string) $time);
    if ($time === '') {
        return '';
    }
    if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
        $parts = explode(':', $time);
        return sprintf('%02d:%02d:00', (int) $parts[0], (int) $parts[1]);
    }
    if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
        $parts = explode(':', $time);
        return sprintf('%02d:%02d:%02d', (int) $parts[0], (int) $parts[1], (int) $parts[2]);
    }
    return '';
}

function validateAttendanceRegularizationDate($record_date)
{
    $record_date = trim((string) $record_date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $record_date)) {
        return array('valid' => false, 'message' => 'Invalid record date. Use YYYY-MM-DD.');
    }
    $today = date('Y-m-d');
    if ($record_date > $today) {
        return array('valid' => false, 'message' => 'Future dates are not allowed for attendance regularization.');
    }
    return array('valid' => true, 'date' => $record_date);
}

function attendanceEnsurePushNotificationController()
{
    $file = dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
    if (is_readable($file)) {
        require_once $file;
    }
}

function getAttendanceSupervisorIdsForEmployee($conn, $employee_id)
{
    $employee_id = (int) $employee_id;
    if ($employee_id <= 0) {
        return array();
    }
    $employee = _getTableDetails($conn, 'employees', " WHERE ID = $employee_id AND IsActive = 1");
    if (!is_array($employee) || empty($employee['Supervisor']) || (int) $employee['Supervisor'] <= 0) {
        return array();
    }
    return array((int) $employee['Supervisor']);
}

function getAttendanceHrEmployeeIdsForNotification($conn)
{
    $ids = array();
    $sql = "SELECT DISTINCT ur.EmployeeID
            FROM user_roles ur
            INNER JOIN employees e ON e.ID = ur.EmployeeID
            WHERE e.IsActive = 1 AND ur.IsActive = 1 AND ur.Role IN ('HR', 'Admin', 'Super Admin')";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int) $row['EmployeeID'];
        }
    }
    if (empty($ids)) {
        $fallback = _getTableRecords($conn, 'employees', " WHERE IsActive = 1 AND Designation LIKE '%HR%' ORDER BY ID ASC LIMIT 20");
        foreach ($fallback as $row) {
            $ids[] = (int) $row['ID'];
        }
    }
    return array_values(array_unique($ids));
}

function attendancePublishNotificationToEmployees($conn, $employee_ids, $title, $body, $payload = array())
{
    $employee_ids = array_values(array_unique(array_filter(array_map('intval', (array) $employee_ids), function ($id) {
        return $id > 0;
    })));
    if (empty($employee_ids)) {
        return;
    }

    attendanceEnsurePushNotificationController();

    $portalFile = dirname(__DIR__, 2) . '/controllers/portal_notification_controller.php';
    if (is_readable($portalFile)) {
        require_once $portalFile;
    }

    foreach ($employee_ids as $employee_id) {
        $pushSent = false;
        if (function_exists('pnc_publishNotification')) {
            $publish = pnc_publishNotification($conn, array(
                'target' => 'employee',
                'employee_id' => $employee_id,
                'title' => (string) $title,
                'body' => (string) $body,
                'payload' => is_array($payload) ? $payload : array(),
                'send_push' => true,
                'save_portal' => true,
            ));
            $pushSent = is_array($publish) && empty($publish['error']) && empty($publish['push_failed']);
        }

        if (!$pushSent && function_exists('pnc_sendPushToEmployee')) {
            pnc_sendPushToEmployee($conn, $employee_id, $title, $body, is_array($payload) ? $payload : array());
        }
    }
}

function formatAttendanceRegularizationRecord(array $record)
{
    return array(
        'ID' => (int) ($record['ID'] ?? 0),
        'attendance_id' => (int) ($record['ID'] ?? 0),
        'EmployeeID' => (int) ($record['EmployeeID'] ?? 0),
        'RecordDate' => $record['RecordDate'] ?? '',
        'record_date' => $record['RecordDate'] ?? '',
        'InTime' => $record['InTime'] ?? '',
        'in_time' => $record['InTime'] ?? '',
        'OutTime' => $record['OutTime'] ?? '',
        'out_time' => $record['OutTime'] ?? '',
        'ApprovalStatus' => $record['ApprovalStatus'] ?? 'Pending',
        'approval_status' => $record['ApprovalStatus'] ?? 'Pending',
        'RejectionReason' => $record['RejectionReason'] ?? null,
        'rejection_reason' => $record['RejectionReason'] ?? null,
        'SupervisorApprovedAt' => $record['SupervisorApprovedAt'] ?? null,
        'ApprovedAt' => $record['ApprovedAt'] ?? null,
    );
}

function validateAttendanceRegularizationEntry($conn, $employee_id, array $entry)
{
    $employee_id = (int) $employee_id;
    $attendance_id = (int) ($entry['attendance_id'] ?? $entry['ID'] ?? $entry['id'] ?? 0);
    $record_date = trim((string) ($entry['record_date'] ?? $entry['RecordDate'] ?? ''));
    $in_time = normalizeAttendanceRegularizationTime($entry['in_time'] ?? $entry['InTime'] ?? '');
    $out_time = normalizeAttendanceRegularizationTime($entry['out_time'] ?? $entry['OutTime'] ?? '');

    if ($attendance_id > 0) {
        $existing = getAttendanceRecordById($conn, $attendance_id);
        if (!$existing || (int) ($existing['EmployeeID'] ?? 0) !== $employee_id) {
            return array('valid' => false, 'message' => 'Attendance record not found for this employee.');
        }
        $status = (string) ($existing['ApprovalStatus'] ?? 'Pending');
        if (!in_array($status, array('Pending', 'Rejected'), true)) {
            return array('valid' => false, 'message' => 'Only pending or rejected records can be updated.');
        }
        if ($record_date === '') {
            $record_date = (string) ($existing['RecordDate'] ?? '');
        }
    }

    $date_check = validateAttendanceRegularizationDate($record_date);
    if (empty($date_check['valid'])) {
        return array('valid' => false, 'message' => $date_check['message'] ?? 'Invalid date.');
    }
    $record_date = $date_check['date'];

    if ($in_time === '') {
        return array('valid' => false, 'message' => 'Check-in time is required.');
    }
    if ($out_time !== '' && strtotime($record_date . ' ' . $out_time) <= strtotime($record_date . ' ' . $in_time)) {
        return array('valid' => false, 'message' => 'Check-out time must be after check-in time.');
    }

    return array(
        'valid' => true,
        'attendance_id' => $attendance_id,
        'record_date' => $record_date,
        'in_time' => $in_time,
        'out_time' => $out_time,
    );
}

function submitAttendanceRegularizationEntry($conn, $employee_id, array $entry)
{
    $employee_id = (int) $employee_id;
    $employee = _getTableDetails($conn, 'employees', " WHERE ID = $employee_id AND IsActive = 1");
    if (!$employee) {
        return array('error' => true, 'message' => 'Employee not found or inactive.');
    }

    $validated = validateAttendanceRegularizationEntry($conn, $employee_id, $entry);
    if (empty($validated['valid'])) {
        return array('error' => true, 'message' => $validated['message'] ?? 'Invalid entry.');
    }

    $attendance_id = (int) ($validated['attendance_id'] ?? 0);
    $record_date = mysqli_real_escape_string($conn, $validated['record_date']);
    $in_time = mysqli_real_escape_string($conn, $validated['in_time']);
    $out_time = mysqli_real_escape_string($conn, $validated['out_time']);

    if ($attendance_id > 0) {
        $result = updatemanualAttendance($conn, $attendance_id, $in_time, $out_time);
        if (!empty($result['error'])) {
            return array('error' => true, 'message' => $result['message'] ?? 'Could not update attendance.');
        }
        return array(
            'error' => false,
            'message' => 'Attendance regularization updated and sent for approval.',
            'attendance_id' => $attendance_id,
        );
    }

    $out_sql = $out_time !== '' ? "'$out_time'" : 'NULL';
    $sql = "INSERT INTO employee_attendance (EmployeeID, RecordDate, InTime, OutTime, ApprovalStatus)
            VALUES ($employee_id, '$record_date', '$in_time', $out_sql, 'Pending')";
    $result = _InsertTableRecords($conn, $sql);
    if (!empty($result['error'])) {
        return array('error' => true, 'message' => $result['message'] ?? 'Could not save attendance regularization.');
    }

    return array(
        'error' => false,
        'message' => 'Attendance regularization submitted for approval.',
        'attendance_id' => (int) ($result['last_insert_id'] ?? 0),
    );
}

function submitAttendanceRegularizationBatch($conn, $employee_id, array $entries, $reason = '')
{
    $employee_id = (int) $employee_id;
    if ($employee_id <= 0) {
        return array('error' => true, 'message' => 'EmployeeID is required.');
    }
    if (empty($entries) || !is_array($entries)) {
        return array('error' => true, 'message' => 'At least one attendance entry is required.');
    }

    $saved = array();
    $errors = array();

    foreach ($entries as $index => $entry) {
        if (!is_array($entry)) {
            $errors[] = 'Entry #' . ($index + 1) . ' is invalid.';
            continue;
        }
        $result = submitAttendanceRegularizationEntry($conn, $employee_id, $entry);
        if (!empty($result['error'])) {
            $errors[] = 'Entry #' . ($index + 1) . ': ' . ($result['message'] ?? 'Failed.');
            continue;
        }
        $attendance_id = (int) ($result['attendance_id'] ?? 0);
        if ($attendance_id > 0) {
            $saved[] = $attendance_id;
        }
    }

    if (empty($saved)) {
        return array(
            'error' => true,
            'message' => !empty($errors) ? implode(' ', $errors) : 'No attendance entries were saved.',
            'errors' => $errors,
        );
    }

    try {
        attendanceNotifySupervisorOnRegularization($conn, $saved, $employee_id, $reason);
        attendanceNotifyHrOnRegularizationRequest($conn, $saved, $employee_id, $reason);
    } catch (Throwable $e) {
        // Save succeeded; do not fail the API if push/portal notification fails.
    }

    $records = array();
    foreach ($saved as $attendance_id) {
        $row = getAttendanceRecordById($conn, $attendance_id);
        if ($row) {
            $records[] = formatAttendanceRegularizationRecord($row);
        }
    }

    return array(
        'error' => false,
        'message' => count($saved) . ' attendance regularization request(s) submitted for approval.',
        'attendance_ids' => $saved,
        'saved_count' => count($saved),
        'failed_count' => count($errors),
        'errors' => $errors,
        'records' => $records,
    );
}

function buildAttendanceRegularizationNotificationSummary($conn, array $attendance_ids)
{
    $lines = array();
    foreach ($attendance_ids as $attendance_id) {
        $record = getAttendanceRecordById($conn, (int) $attendance_id);
        if (!$record) {
            continue;
        }
        $date_label = function_exists('pnc_formatDisplayDate')
            ? pnc_formatDisplayDate($record['RecordDate'] ?? '')
            : trim((string) ($record['RecordDate'] ?? ''));
        $in_time = trim((string) ($record['InTime'] ?? ''));
        $out_time = trim((string) ($record['OutTime'] ?? ''));
        $time_label = $in_time;
        if ($out_time !== '') {
            $time_label .= ' to ' . $out_time;
        }
        $lines[] = "$date_label ($time_label)";
    }
    return implode('; ', $lines);
}

function attendanceNotifySupervisorOnRegularization($conn, array $attendance_ids, $employee_id, $reason = '')
{
    $attendance_ids = array_values(array_filter(array_map('intval', $attendance_ids), function ($id) {
        return $id > 0;
    }));
    if (empty($attendance_ids)) {
        return;
    }

    $supervisor_ids = getAttendanceSupervisorIdsForEmployee($conn, (int) $employee_id);
    if (empty($supervisor_ids)) {
        return;
    }

    $employee = _getTableDetails($conn, 'employees', ' WHERE ID = ' . (int) $employee_id);
    $employee_name = trim((string) ($employee['Name'] ?? 'Employee'));
    $summary = buildAttendanceRegularizationNotificationSummary($conn, $attendance_ids);
    $reason = trim((string) $reason);
    $body = "$employee_name submitted attendance regularization for $summary. Please review.";
    if ($reason !== '') {
        $body .= ' Reason: ' . $reason;
    }

    attendancePublishNotificationToEmployees($conn, $supervisor_ids, 'Attendance Regularization', $body, array(
        'module' => 'attendance',
        'layer' => 'supervisor_pending',
        'employee_id' => (int) $employee_id,
        'attendance_ids' => $attendance_ids,
        'screen' => 'dashboard',
    ));
}

function attendanceNotifyHrOnRegularizationRequest($conn, array $attendance_ids, $employee_id, $reason = '')
{
    $attendance_ids = array_values(array_filter(array_map('intval', $attendance_ids), function ($id) {
        return $id > 0;
    }));
    if (empty($attendance_ids)) {
        return;
    }

    $hr_ids = getAttendanceHrEmployeeIdsForNotification($conn);
    if (empty($hr_ids)) {
        return;
    }

    $employee = _getTableDetails($conn, 'employees', ' WHERE ID = ' . (int) $employee_id);
    $employee_name = trim((string) ($employee['Name'] ?? 'Employee'));
    $summary = buildAttendanceRegularizationNotificationSummary($conn, $attendance_ids);
    $reason = trim((string) $reason);
    $body = "$employee_name submitted attendance regularization for $summary. Pending supervisor approval.";
    if ($reason !== '') {
        $body .= ' Reason: ' . $reason;
    }

    attendancePublishNotificationToEmployees($conn, $hr_ids, 'Attendance Regularization', $body, array(
        'module' => 'attendance',
        'layer' => 'hr_info',
        'employee_id' => (int) $employee_id,
        'attendance_ids' => $attendance_ids,
        'screen' => 'dashboard',
    ));
}

function attendanceNotifyHrOnSupervisorApproved($conn, $attendance_id)
{
    $attendance_id = (int) $attendance_id;
    $record = getAttendanceRecordById($conn, $attendance_id);
    if (!$record || empty($record['EmployeeID']) || ($record['ApprovalStatus'] ?? '') !== 'SupervisorApproved') {
        return;
    }

    $hr_ids = getAttendanceHrEmployeeIdsForNotification($conn);
    if (empty($hr_ids)) {
        return;
    }

    $employee = _getTableDetails($conn, 'employees', ' WHERE ID = ' . (int) $record['EmployeeID']);
    $employee_name = trim((string) ($employee['Name'] ?? 'Employee'));
    $date_label = function_exists('pnc_formatDisplayDate')
        ? pnc_formatDisplayDate($record['RecordDate'] ?? '')
        : trim((string) ($record['RecordDate'] ?? ''));
    $in_time = trim((string) ($record['InTime'] ?? ''));
    $out_time = trim((string) ($record['OutTime'] ?? ''));
    $time_label = $in_time;
    if ($out_time !== '') {
        $time_label .= ' to ' . $out_time;
    }

    attendancePublishNotificationToEmployees($conn, $hr_ids, 'Attendance HR Approval', "Attendance regularization for $employee_name on $date_label ($time_label) is approved by supervisor and pending HR final approval.", array(
        'module' => 'attendance',
        'attendance_id' => $attendance_id,
        'layer' => 'hr_pending',
        'employee_id' => (int) $record['EmployeeID'],
        'record_date' => $record['RecordDate'] ?? '',
        'screen' => 'dashboard',
    ));
}

function getEmployeeAttendanceRegularizationHistory($conn, $employee_id, $from_date = '', $to_date = '')
{
    $employee_id = (int) $employee_id;
    if ($employee_id <= 0) {
        return array();
    }

    $where = " WHERE EmployeeID = $employee_id";
    if ($from_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
        $from_date = mysqli_real_escape_string($conn, $from_date);
        $where .= " AND RecordDate >= '$from_date'";
    }
    if ($to_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
        $to_date = mysqli_real_escape_string($conn, $to_date);
        $where .= " AND RecordDate <= '$to_date'";
    }
    $where .= ' ORDER BY RecordDate DESC, InTime DESC, ID DESC';

    $rows = _getTableRecords($conn, 'employee_attendance', $where);
    $records = array();
    foreach ($rows as $row) {
        $records[] = formatAttendanceRegularizationRecord($row);
    }
    return $records;
}

function renderEmployeeAttendanceSummaryFooterHtml($summary)
{
    return '<tr class="att-summary-row">'
        . '<td colspan="3"><strong>Month Summary</strong></td>'
        . '<td class="att-col-metric"><strong>' . htmlspecialchars($summary['total_twh']) . '</strong><div class="att-summary-sub">Total TWH</div></td>'
        . '<td class="att-col-metric"><strong>' . htmlspecialchars($summary['total_ot']) . '</strong><div class="att-summary-sub">Total OT</div></td>'
        . '<td class="att-col-metric"><strong>' . htmlspecialchars($summary['total_st']) . '</strong><div class="att-summary-sub">Total ST</div></td>'
        . '<td class="att-col-status"><div class="att-summary-stats">'
        . '<span>Present: <strong>' . (int) $summary['present'] . '</strong></span>'
        . '<span>Absent: <strong>' . (int) $summary['absent'] . '</strong></span>'
        . '<span>Half Day: <strong>' . (int) $summary['half_day'] . '</strong></span>'
        . '</div></td>'
        . '</tr>';
}

?>