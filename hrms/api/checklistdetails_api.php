<?php
/**
 * Checklist details API
 * Table: checklistdetails (ID, CheckListName, StartDate, TargetDays, CreatedDate, IsActive)
 * POST = create, PUT = update, DELETE = soft delete (IsActive=0)
 * Uses Core _InsertTableRecords_prepare, _UpdateTableRecords_prepare.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/common-api-header.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/auth_jwt.php';

if (!in_array($method, ['POST', 'PUT', 'DELETE', 'GET'], true)) {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$data_raw = file_get_contents('php://input');
$data = [];
if ($data_raw !== '') {
    $data = json_decode($data_raw, true) ?: [];
}
if (!is_array($data)) {
    $data = [];
}

// GET can use query string id for single record
if ($method === 'GET' && isset($_GET['id'])) {
    $data['ID'] = $_GET['id'];
}

// Allowed columns (CreatedBy set from auth, not from body)
$allowed_columns = ['CheckListName', 'StartDate', 'TargetDays', 'CreatedDate', 'CreatedBy', 'IsActive'];

$filter_to_allowed = function (array $raw, array $allowed) {
    return array_intersect_key($raw, array_flip($allowed));
};

$checklist = new Checklistdetails($conn);
$response = ['error' => true, 'message' => ''];

// ---------- POST: Create (pass raw body {} – CreatedBy = current user) ----------
if ($method === 'POST') {
    $insert_data = $filter_to_allowed($data, $allowed_columns);
    if (!array_key_exists('CreatedDate', $insert_data) || $insert_data['CreatedDate'] === '') {
        $insert_data['CreatedDate'] = date('Y-m-d');
    }
    if (!array_key_exists('IsActive', $insert_data)) {
        $insert_data['IsActive'] = 1;
    }
    $userId = isset($auth_user['user_id']) ? (int) $auth_user['user_id'] : 0;
    $insert_data['CreatedBy'] = $userId;
    foreach ($insert_data as $k => $v) {
        if ($v === null) {
            $insert_data[$k] = '';
        }
        if ($k === 'TargetDays' || $k === 'IsActive' || $k === 'CreatedBy') {
            $insert_data[$k] = (int) $insert_data[$k];
        }
    }

    $result = $checklist->insert($insert_data);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Insert failed.';
        echo json_encode($response);
        exit;
    }

    $response = [
        'error'   => false,
        'message' => 'Checklist details created successfully.',
        'id'      => $result['last_insert_id'] ?? null,
    ];
    echo json_encode($response);
    exit;
}

// ---------- PUT: Update (only own checklist) ----------
if ($method === 'PUT') {
    $id = isset($data['ID']) ? (int) $data['ID'] : 0;
    $userId = isset($auth_user['user_id']) ? (int) $auth_user['user_id'] : 0;
    if ($id <= 0) {
        $response['message'] = 'Valid ID is required for update.';
        echo json_encode($response);
        exit;
    }
    $row = $checklist->getByIdAndCreatedBy($id, $userId);
    if (!$row) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'You can only update your own checklist.']);
        exit;
    }

    $update_data = $filter_to_allowed($data, $allowed_columns);
    unset($update_data['CreatedBy'], $update_data['CreatedDate']);
    if (array_key_exists('TargetDays', $update_data)) {
        $update_data['TargetDays'] = (int) $update_data['TargetDays'];
    }
    if (array_key_exists('IsActive', $update_data)) {
        $update_data['IsActive'] = (int) $update_data['IsActive'];
    }
    foreach ($update_data as $k => $v) {
        if ($v === null) {
            $update_data[$k] = '';
        }
    }

    if (empty($update_data)) {
        $response['message'] = 'No fields to update.';
        echo json_encode($response);
        exit;
    }

    $result = $checklist->update($id, $update_data);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Update failed.';
        echo json_encode($response);
        exit;
    }

    $response = ['error' => false, 'message' => 'Checklist details updated successfully.'];
    echo json_encode($response);
    exit;
}

// ---------- DELETE: Soft delete (only own checklist) ----------
if ($method === 'DELETE') {
    $id = isset($data['ID']) ? (int) $data['ID'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    $userId = isset($auth_user['user_id']) ? (int) $auth_user['user_id'] : 0;
    if ($id <= 0) {
        $response['message'] = 'Valid ID is required for delete.';
        echo json_encode($response);
        exit;
    }
    $row = $checklist->getByIdAndCreatedBy($id, $userId);
    if (!$row) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'You can only delete your own checklist.']);
        exit;
    }

    $result = $checklist->delete($id);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Delete failed.';
        echo json_encode($response);
        exit;
    }

    $response = ['error' => false, 'message' => 'Checklist details deleted successfully.'];
    echo json_encode($response);
    exit;
}

// ---------- GET: List own checklists or get one by ID (only own) ----------
if ($method === 'GET') {
    $id = isset($data['ID']) ? (int) $data['ID'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    $userId = isset($auth_user['user_id']) ? (int) $auth_user['user_id'] : 0;
    $activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';

    if ($id > 0) {
        $row = $checklist->getByIdAndCreatedBy($id, $userId);
        if ($row) {
            echo json_encode(['error' => false, 'data' => $row]);
        } else {
            echo json_encode(['error' => true, 'message' => 'Record not found or access denied.']);
        }
    } else {
        $rows = $checklist->getByCreatedBy($userId, $activeOnly);
        echo json_encode(['error' => false, 'data' => $rows]);
    }
    exit;
}
