<?php
/**
 * Master habit API – anyone can create; only System Admin can delete.
 * Table: master_habbit (ID, HabbitName, CreateData, IsActive)
 * GET    = list all (any authenticated user)
 * POST   = create (any authenticated user)
 * DELETE = only System Admin
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/common-api-header.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/auth_jwt.php';

if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
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

$allowed_columns = ['HabbitName', 'CreateData', 'IsActive'];
$filter_to_allowed = function (array $raw, array $allowed) {
    return array_intersect_key($raw, array_flip($allowed));
};

$masterHabbit = new Masterhabbit($conn);
$response = ['error' => true, 'message' => ''];

// ---------- POST: Create (any authenticated user) ----------
if ($method === 'POST') {
    $insert_data = $filter_to_allowed($data, $allowed_columns);

    $name = isset($insert_data['HabbitName']) ? trim($insert_data['HabbitName']) : '';
    if ($name === '') {
        $response['message'] = 'HabbitName is required.';
        echo json_encode($response);
        exit;
    }

    if (!array_key_exists('CreateData', $insert_data) || $insert_data['CreateData'] === '') {
        $insert_data['CreateData'] = date('Y-m-d');
    }
    if (!array_key_exists('IsActive', $insert_data)) {
        $insert_data['IsActive'] = 1;
    }
    if (isset($insert_data['IsActive'])) {
        $insert_data['IsActive'] = (int) $insert_data['IsActive'];
    }

    $result = $masterHabbit->insert($insert_data);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Insert failed.';
        echo json_encode($response);
        exit;
    }

    echo json_encode([
        'error'   => false,
        'message' => 'Master habit created successfully.',
        'id'      => $result['last_insert_id'] ?? null,
    ]);
    exit;
}

// ---------- DELETE: Only System Admin ----------
if ($method === 'DELETE') {
    $role = isset($auth_user['role']) ? trim($auth_user['role']) : '';
    if (strtolower($role) !== 'system admin') {
        http_response_code(403);
        echo json_encode([
            'error'   => true,
            'message' => 'Only System Admin can delete master habit.',
        ]);
        exit;
    }

    $id = isset($data['ID']) ? (int) $data['ID'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    if ($id <= 0) {
        $response['message'] = 'Valid ID is required for delete.';
        echo json_encode($response);
        exit;
    }

    $result = $masterHabbit->delete($id);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Delete failed.';
        echo json_encode($response);
        exit;
    }

    echo json_encode([
        'error'   => false,
        'message' => 'Master habit deleted successfully.',
    ]);
    exit;
}

// ---------- GET: List all (any authenticated user) ----------
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';

    if ($id > 0) {
        $row = $masterHabbit->getById($id);
        if ($row) {
            echo json_encode(['error' => false, 'data' => $row]);
        } else {
            echo json_encode(['error' => true, 'message' => 'Record not found.']);
        }
    } else {
        $rows = $masterHabbit->getAll($activeOnly);
        echo json_encode(['error' => false, 'data' => $rows]);
    }
    exit;
}
