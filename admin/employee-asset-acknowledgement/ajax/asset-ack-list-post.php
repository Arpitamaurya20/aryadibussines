<?php
ini_set('display_errors', '0');
error_reporting(0);
ob_start();

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$empty_response = array(
    'draw' => $draw,
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array(),
);

try {
    require_once('../../includes/autoloader.inc.php');
    require_once('../../controllers/common_controllers.php');
    require_once('../controller/employee_asset_acknowledgement_controller.php');

    @session_start();

    if (!isset($_SESSION['pb_username'])) {
        sendEmployeeAssetAckJson($empty_response);
    }

    $conn = _connectodb();
    $roles = $_SESSION['Roles'] ?? array();
    if (!$conn || !hasEmployeeAssetAckAdminAccess($roles)) {
        sendEmployeeAssetAckJson($empty_response);
    }

    $row = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $rowperpage = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    if ($rowperpage < 0) {
        $rowperpage = 10;
    }

    $searchValue = '';
    if (isset($_POST['search']) && is_array($_POST['search']) && isset($_POST['search']['value'])) {
        $searchValue = $_POST['search']['value'];
    }

    $filters = array(
        'employee_id' => isset($_GET['EmployeeID']) ? $_GET['EmployeeID'] : -1,
        'status' => isset($_GET['status']) ? $_GET['status'] : -1,
        'search' => $searchValue,
    );

    $where = buildEmployeeAssetAckListFilterSql($conn, $filters);

    $sql_count = "SELECT COUNT(*) AS row_count FROM employees e $where";
    $result_count = mysqli_query($conn, $sql_count);
    if (!$result_count) {
        sendEmployeeAssetAckJson($empty_response);
    }
    $total = (int) ($result_count->fetch_assoc()['row_count'] ?? 0);

    $sql = "SELECT e.ID, e.Name, e.EmployeeNumber, e.Department, e.Designation
            FROM employees e
            $where
            ORDER BY e.Name ASC
            LIMIT $row, $rowperpage";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        sendEmployeeAssetAckJson($empty_response);
    }

    $data = array();
    while ($emp = mysqli_fetch_assoc($result)) {
        $summary = getEmployeeAssetAckSummaryCounts($conn, (int) $emp['ID']);
        $employeeId = (int) $emp['ID'];
        $statusBadge = formatEmployeeAssetAckStatusBadge($summary['pending_assets'], $summary['total_assets']);
        $manageUrl = 'view-manage-employee-assets.php?EmployeeID=' . $employeeId;
        $actions = '<a class="btn btn-xs btn-primary mr-1" href="' . $manageUrl . '">Manage</a>'
            . '<button type="button" class="btn btn-xs btn-outline-info btn-generate-link" data-employee-id="' . $employeeId . '">Public Link</button>';
        $data[] = array(
            htmlspecialchars($emp['Name']),
            htmlspecialchars($emp['EmployeeNumber']),
            htmlspecialchars($emp['Department']),
            (int) $summary['total_assets'],
            (int) $summary['pending_assets'],
            $statusBadge,
            $actions,
        );
    }

    sendEmployeeAssetAckJson(array(
        'draw' => $draw,
        'iTotalRecords' => $total,
        'iTotalDisplayRecords' => $total,
        'aaData' => $data,
    ));
} catch (Throwable $e) {
    sendEmployeeAssetAckJson($empty_response);
}
