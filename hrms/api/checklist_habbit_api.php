<?php
/**
 * API: Add habit to checklist (insert into checklistsetting)
 * Tables: checklistsetting (ID, CheckListID, HabbitID, CreatedDate, IsActive)
 *         master_habbit (ID, HabbitName, CreateData, IsActive)
 * POST = add habit to checklist (raw body: CheckListID, HabbitID, ...)
 * GET  = list habits in a checklist, or list master habits
 * DELETE = remove habit from checklist
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

if (!in_array($method, ['POST', 'GET', 'DELETE'], true)) {
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

$allowed_columns = ['CheckListID', 'HabbitID', 'CreatedDate', 'IsActive'];
$filter_to_allowed = function (array $raw, array $allowed) {
    return array_intersect_key($raw, array_flip($allowed));
};

$checklistSetting = new Checklistsetting($conn);
$masterHabbit = new Masterhabbit($conn);
$checklistDetails = new Checklistdetails($conn);
$userId = isset($auth_user['user_id']) ? (int) $auth_user['user_id'] : 0;
$response = ['error' => true, 'message' => ''];

function ensureChecklistOwnership($checklistDetails, $checkListId, $userId) {
    if ($userId <= 0 || $checkListId <= 0) return null;
    return $checklistDetails->getByIdAndCreatedBy($checkListId, $userId);
}

// ---------- POST: Add habit to checklist (only own checklist) ----------
if ($method === 'POST') {
    $insert_data = $filter_to_allowed($data, $allowed_columns);

    $checkListId = isset($insert_data['CheckListID']) ? (int) $insert_data['CheckListID'] : (int) ($data['CheckListID'] ?? 0);
    $habbitId = isset($insert_data['HabbitID']) ? (int) $insert_data['HabbitID'] : (int) ($data['HabbitID'] ?? 0);

    if ($checkListId <= 0 || $habbitId <= 0) {
        $response['message'] = 'CheckListID and HabbitID are required and must be greater than 0.';
        echo json_encode($response);
        exit;
    }
    if (!ensureChecklistOwnership($checklistDetails, $checkListId, $userId)) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'You can only add habits to your own checklist.']);
        exit;
    }

    if (!$masterHabbit->exists($habbitId)) {
        $response['message'] = 'HabbitID does not exist in master_habbit.';
        echo json_encode($response);
        exit;
    }

    if ($checklistSetting->isHabitInChecklist($checkListId, $habbitId)) {
        $response['message'] = 'This habit is already added to this checklist.';
        echo json_encode($response);
        exit;
    }

    $insert_data['CheckListID'] = $checkListId;
    $insert_data['HabbitID'] = $habbitId;
    if (!array_key_exists('CreatedDate', $insert_data) || $insert_data['CreatedDate'] === '') {
        $insert_data['CreatedDate'] = date('Y-m-d');
    }
    if (!array_key_exists('IsActive', $insert_data)) {
        $insert_data['IsActive'] = 1;
    }
    foreach (['CheckListID', 'HabbitID', 'IsActive'] as $k) {
        if (isset($insert_data[$k])) {
            $insert_data[$k] = (int) $insert_data[$k];
        }
    }

    $result = $checklistSetting->insert($insert_data);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Insert failed.';
        echo json_encode($response);
        exit;
    }

    $response = [
        'error'   => false,
        'message' => 'Habit added to checklist successfully.',
        'id'      => $result['last_insert_id'] ?? null,
    ];
    echo json_encode($response);
    exit;
}

// ---------- GET: List habits in a checklist, or list master habits ----------
if ($method === 'GET') {
    $checkListId = isset($_GET['CheckListID']) ? (int) $_GET['CheckListID'] : 0;
    $master = isset($_GET['master']) && $_GET['master'] === '1';

    if ($master) {
        $activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';
        $rows = $masterHabbit->getAll($activeOnly);
        echo json_encode(['error' => false, 'data' => $rows]);
        exit;
    }

    if ($checkListId <= 0) {
        $response['message'] = 'CheckListID is required (e.g. ?CheckListID=1) or use ?master=1 for master habit list.';
        echo json_encode($response);
        exit;
    }
    if (!ensureChecklistOwnership($checklistDetails, $checkListId, $userId)) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'You can only view habits for your own checklist.']);
        exit;
    }

    $activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';
    $rows = $checklistSetting->getHabitsByCheckListID($checkListId, $activeOnly);
    echo json_encode(['error' => false, 'data' => $rows]);
    exit;
}

// ---------- DELETE: Remove habit from checklist ----------
if ($method === 'DELETE') {
    $id = isset($data['ID']) ? (int) $data['ID'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    $checkListId = isset($data['CheckListID']) ? (int) $data['CheckListID'] : (isset($_GET['CheckListID']) ? (int) $_GET['CheckListID'] : 0);
    $habbitId = isset($data['HabbitID']) ? (int) $data['HabbitID'] : (isset($_GET['HabbitID']) ? (int) $_GET['HabbitID'] : 0);

    if ($id > 0) {
        $row = $checklistSetting->getById($id);
        if ($row && !empty($row['CheckListID'])) {
            if (!ensureChecklistOwnership($checklistDetails, (int) $row['CheckListID'], $userId)) {
                http_response_code(403);
                echo json_encode(['error' => true, 'message' => 'You can only remove habits from your own checklist.']);
                exit;
            }
        }
        $result = $checklistSetting->delete($id);
    } elseif ($checkListId > 0 && $habbitId > 0) {
        if (!ensureChecklistOwnership($checklistDetails, $checkListId, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'You can only remove habits from your own checklist.']);
            exit;
        }
        $result = $checklistSetting->removeHabitFromChecklist($checkListId, $habbitId);
    } else {
        $response['message'] = 'Provide ID or both CheckListID and HabbitID.';
        echo json_encode($response);
        exit;
    }

    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Delete failed.';
        echo json_encode($response);
        exit;
    }

    $response = ['error' => false, 'message' => 'Habit removed from checklist successfully.'];
    echo json_encode($response);
    exit;
}
