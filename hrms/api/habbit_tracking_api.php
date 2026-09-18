<?php
/**
 * API: Habit tracking – submit date-wise checks (Yes/No) for checklist habits.
 * Table: habbit_tracking (ID, CheckListID, HabbitID, CheckingDate, CheckStatus, ImagePath, IsActive, CreatedDate)
 * POST = submit one or many checks (upsert per habit for that date)
 * GET  = fetch tracking by CheckListID + CheckingDate (or date range)
 * PUT  = update one record by ID
 * DELETE = soft delete one record by ID
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

if (!in_array($method, ['POST', 'GET', 'PUT', 'DELETE'], true)) {
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

$allowed_columns = ['CheckListID', 'HabbitID', 'CheckingDate', 'CheckStatus', 'ImagePath', 'IsActive', 'CreatedDate'];
$filter_to_allowed = function (array $raw, array $allowed) {
    return array_intersect_key($raw, array_flip($allowed));
};

$tracking = new Habbitracking($conn);
$checklistDetails = new Checklistdetails($conn);
$userId = isset($auth_user['user_id']) ? (int) $auth_user['user_id'] : 0;
$response = ['error' => true, 'message' => ''];

function ensureTrackingChecklistOwnership($checklistDetails, $checkListId, $userId) {
    if ($userId <= 0 || $checkListId <= 0) return null;
    return $checklistDetails->getByIdAndCreatedBy($checkListId, $userId);
}

// ---------- POST: Submit check(s) – single or batch (only own checklist) ----------
if ($method === 'POST') {
    $checkingDate = isset($data['CheckingDate']) && $data['CheckingDate'] !== '' ? $data['CheckingDate'] : date('Y-m-d');

    // Batch: { CheckListID, CheckingDate, entries: [ { HabbitID, CheckStatus, ImagePath? }, ... ] }
    if (isset($data['entries']) && is_array($data['entries'])) {
        $checkListId = (int) ($data['CheckListID'] ?? 0);
        if ($checkListId <= 0) {
            $response['message'] = 'CheckListID is required for batch submit.';
            echo json_encode($response);
            exit;
        }
        if (!ensureTrackingChecklistOwnership($checklistDetails, $checkListId, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'You can only submit tracking for your own checklist.']);
            exit;
        }

        $results = [];
        $hasError = false;
        foreach ($data['entries'] as $entry) {
            $habbitId = (int) ($entry['HabbitID'] ?? 0);
            if ($habbitId <= 0) {
                continue;
            }
            $row = [
                'CheckListID'  => $checkListId,
                'HabbitID'     => $habbitId,
                'CheckingDate' => $checkingDate,
                'CheckStatus'  => isset($entry['CheckStatus']) ? (strtolower($entry['CheckStatus']) === 'yes' ? 'Yes' : 'No') : 'No',
                'ImagePath'    => $entry['ImagePath'] ?? '',
            ];
            $res = $tracking->upsertOne($row);
            $results[] = ['HabbitID' => $habbitId, 'error' => $res['error'], 'id' => $res['id'] ?? null, 'inserted' => $res['inserted'] ?? false];
            if ($res['error']) {
                $hasError = true;
            }
        }

        $response = [
            'error'   => $hasError,
            'message' => $hasError ? 'Some entries failed.' : 'Checks submitted successfully.',
            'results' => $results,
        ];
        echo json_encode($response);
        exit;
    }

    // Single: { CheckListID, HabbitID, CheckingDate?, CheckStatus, ImagePath? }
    $insert_data = $filter_to_allowed($data, $allowed_columns);
    $checkListId = (int) ($insert_data['CheckListID'] ?? $data['CheckListID'] ?? 0);
    $habbitId = (int) ($insert_data['HabbitID'] ?? $data['HabbitID'] ?? 0);

    if ($checkListId <= 0 || $habbitId <= 0) {
        $response['message'] = 'CheckListID and HabbitID are required.';
        echo json_encode($response);
        exit;
    }
    if (!ensureTrackingChecklistOwnership($checklistDetails, $checkListId, $userId)) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'You can only submit tracking for your own checklist.']);
        exit;
    }

    $row = [
        'CheckListID'  => $checkListId,
        'HabbitID'     => $habbitId,
        'CheckingDate' => $checkingDate,
        'CheckStatus'  => isset($data['CheckStatus']) ? (strtolower($data['CheckStatus']) === 'yes' ? 'Yes' : 'No') : 'No',
        'ImagePath'    => $data['ImagePath'] ?? '',
    ];

    $result = $tracking->upsertOne($row);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Submit failed.';
        echo json_encode($response);
        exit;
    }

    $response = [
        'error'     => false,
        'message'   => 'Check submitted successfully.',
        'id'        => $result['id'] ?? null,
        'inserted'  => $result['inserted'] ?? true,
    ];
    echo json_encode($response);
    exit;
}

// ---------- GET: Fetch tracking by CheckListID + date (or date range) ----------
if ($method === 'GET') {
    $checkListId = isset($_GET['CheckListID']) ? (int) $_GET['CheckListID'] : 0;
    $checkingDate = isset($_GET['CheckingDate']) ? trim($_GET['CheckingDate']) : '';
    $fromDate = isset($_GET['FromDate']) ? trim($_GET['FromDate']) : '';
    $toDate = isset($_GET['ToDate']) ? trim($_GET['ToDate']) : '';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';

    if ($id > 0) {
        $row = $tracking->getById($id);
        if ($row) {
            if (!empty($row['CheckListID']) && !ensureTrackingChecklistOwnership($checklistDetails, (int) $row['CheckListID'], $userId)) {
                http_response_code(403);
                echo json_encode(['error' => true, 'message' => 'You can only view tracking for your own checklist.']);
                exit;
            }
            echo json_encode(['error' => false, 'data' => $row]);
        } else {
            echo json_encode(['error' => true, 'message' => 'Record not found.']);
        }
        exit;
    }

    if ($checkListId <= 0) {
        $response['message'] = 'CheckListID is required (e.g. ?CheckListID=1&CheckingDate=2025-02-15).';
        echo json_encode($response);
        exit;
    }
    if (!ensureTrackingChecklistOwnership($checklistDetails, $checkListId, $userId)) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'You can only view tracking for your own checklist.']);
        exit;
    }

    if ($fromDate !== '' && $toDate !== '') {
        $rows = $tracking->getByCheckListAndDateRange($checkListId, $fromDate, $toDate, $activeOnly);
    } else {
        $date = $checkingDate !== '' ? $checkingDate : date('Y-m-d');
        $rows = $tracking->getByCheckListAndDate($checkListId, $date, $activeOnly);
    }

    echo json_encode(['error' => false, 'data' => $rows]);
    exit;
}

// ---------- PUT: Update one record by ID (only own checklist) ----------
if ($method === 'PUT') {
    $id = isset($data['ID']) ? (int) $data['ID'] : 0;
    if ($id <= 0) {
        $response['message'] = 'Valid ID is required for update.';
        echo json_encode($response);
        exit;
    }
    $trackRow = $tracking->getById($id);
    if ($trackRow && !empty($trackRow['CheckListID'])) {
        if (!ensureTrackingChecklistOwnership($checklistDetails, (int) $trackRow['CheckListID'], $userId)) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'You can only update tracking for your own checklist.']);
            exit;
        }
    }

    $update_data = $filter_to_allowed($data, ['CheckStatus', 'ImagePath', 'CheckingDate', 'IsActive']);
    if (isset($update_data['CheckStatus'])) {
        $update_data['CheckStatus'] = strtolower($update_data['CheckStatus']) === 'yes' ? 'Yes' : 'No';
    }
    if (empty($update_data)) {
        $response['message'] = 'No fields to update (CheckStatus, ImagePath, CheckingDate, IsActive).';
        echo json_encode($response);
        exit;
    }

    $result = $tracking->update($id, $update_data);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Update failed.';
        echo json_encode($response);
        exit;
    }

    $response = ['error' => false, 'message' => 'Tracking updated successfully.'];
    echo json_encode($response);
    exit;
}

// ---------- DELETE: Soft delete one record by ID (only own checklist) ----------
if ($method === 'DELETE') {
    $id = isset($data['ID']) ? (int) $data['ID'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    if ($id <= 0) {
        $response['message'] = 'Valid ID is required for delete.';
        echo json_encode($response);
        exit;
    }
    $trackRow = $tracking->getById($id);
    if ($trackRow && !empty($trackRow['CheckListID'])) {
        if (!ensureTrackingChecklistOwnership($checklistDetails, (int) $trackRow['CheckListID'], $userId)) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'You can only delete tracking for your own checklist.']);
            exit;
        }
    }

    $result = $tracking->delete($id);
    if ($result['error']) {
        $response['message'] = $result['message'] ?? 'Delete failed.';
        echo json_encode($response);
        exit;
    }

    $response = ['error' => false, 'message' => 'Tracking record deleted successfully.'];
    echo json_encode($response);
    exit;
}
