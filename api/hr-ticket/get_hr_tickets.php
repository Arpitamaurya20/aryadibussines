<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('hr_ticket_helpers.php');

header('Content-Type: application/json; charset=utf-8');
setTimeZone();

$data = hr_ticket_api_parse_input();
$conn = _connectodb();

$employeeId = isset($data['EmployeeID']) ? (int) $data['EmployeeID'] : 0;
if ($employeeId < 1) {
    hr_ticket_api_response(true, 'EmployeeID is required.');
}

$viewRole = 'employee';
if (isset($data['view_role'])) {
    $viewRole = strtolower(trim((string) $data['view_role']));
}
if (isset($data['ViewRole'])) {
    $viewRole = strtolower(trim((string) $data['ViewRole']));
}

$hr = new Hrticket($conn);
$filters = array();
if (!empty($data['Status'])) {
    $filters['status'] = strtolower(trim((string) $data['Status']));
}
if (!empty($data['status'])) {
    $filters['status'] = strtolower(trim((string) $data['status']));
}
if (!empty($data['Category'])) {
    $filters['category'] = strtolower(trim((string) $data['Category']));
}
if (!empty($data['category'])) {
    $filters['category'] = strtolower(trim((string) $data['category']));
}

$rows = array();
if ($viewRole === 'hr' || $viewRole === 'manager') {
    if (!hr_ticket_api_employee_has_hr_access($conn, $employeeId)) {
        hr_ticket_api_response(true, 'Access denied. HR role required.');
    }
    if (!empty($data['AssignedTo']) || !empty($data['assigned_to'])) {
        $filters['assigned_to'] = (int) ($data['AssignedTo'] ?? $data['assigned_to']);
    }
    $rows = $hr->listAllTickets($filters);
} else {
    $rows = $hr->listTicketsForEmployee($employeeId);
    if (!empty($filters['status'])) {
        $rows = array_values(array_filter($rows, function ($r) use ($filters) {
            return strtolower((string) ($r['status'] ?? '')) === $filters['status'];
        }));
    }
    if (!empty($filters['category'])) {
        $rows = array_values(array_filter($rows, function ($r) use ($filters) {
            return strtolower((string) ($r['category'] ?? '')) === $filters['category'];
        }));
    }
}

$baseUrl = hr_ticket_api_base_url();
$list = array();
foreach ($rows as $row) {
    $list[] = hr_ticket_api_format_ticket($conn, $row, false, $baseUrl, $employeeId);
}

echo json_encode(array(
    'error' => false,
    'message' => 'HR tickets fetched.',
    'total_records' => count($list),
    'view_role' => $viewRole,
    'data' => $list,
));
