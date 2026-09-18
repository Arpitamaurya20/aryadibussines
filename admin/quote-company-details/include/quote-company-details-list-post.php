<?php

include("../../controllers/common_controllers.php");
include('../controller/quote_company_details_controller.php');

@session_start();
if (!isset($_SESSION['pb_username'])) {
    http_response_code(401);
    echo json_encode(array(
        'draw' => 0,
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
        'aaData' => array()
    ));
    exit;
}

$conn = _connectodb();

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$row = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$rowperpage = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

$data = array();

$searchQuery = '';
if ($searchValue !== '') {
    $sv = cleantext($searchValue);
    $searchQuery = " AND (CompanyName LIKE '%$sv%' OR Email LIKE '%$sv%' OR Phone LIKE '%$sv%' OR GstNumber LIKE '%$sv%') ";
}

$filter = " WHERE 1=1" . $searchQuery;
$totalRecordwithFilter = _getTotalRows($conn, 'quote_company_details', $filter);
$totalRecords = getTotalQuoteCompanyDetails($conn);

$filterPaged = $filter . " ORDER BY ID DESC LIMIT " . (int) $row . "," . (int) $rowperpage;
$rows = _getTableRecords($conn, 'quote_company_details', $filterPaged);

foreach ($rows as $r) {
    $active = (int) $r['IsActive'] === 1;
    $statusBadge = $active
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-secondary">Inactive</span>';
    $toggleLabel = $active ? 'Deactivate' : 'Activate';
    $nextActive = $active ? 0 : 1;
    $toggleBtn = "<a href='#' class='text-primary' onclick='toggleQuoteCompanyDetails(" . (int) $r['ID'] . ", " . $nextActive . "); return false;'>" . $toggleLabel . "</a>";
    $viewBtn = "<a href='#' class='text-secondary' onclick='viewQuoteCompanyDetails(" . (int) $r['ID'] . "); return false;'><i class='fal fa-eye'></i> View</a>";
    $editBtn = "<a href='#' class='text-info' onclick='editQuoteCompanyDetails(" . (int) $r['ID'] . "); return false;'><i class='fal fa-edit'></i> Edit</a>";

    $data[] = array(
        'CompanyName' => htmlspecialchars($r['CompanyName']),
        'Email' => htmlspecialchars($r['Email']),
        'Phone' => htmlspecialchars($r['Phone']),
        'GstNumber' => htmlspecialchars($r['GstNumber']),
        'Status' => $statusBadge,
        'Actions' => $viewBtn . ' &nbsp;|&nbsp; ' . $editBtn . ' &nbsp;|&nbsp; ' . $toggleBtn
    );
}

$response = array(
    'draw' => $draw,
    'iTotalRecords' => $totalRecords,
    'iTotalDisplayRecords' => $totalRecordwithFilter,
    'aaData' => $data
);

echo json_encode($response);
