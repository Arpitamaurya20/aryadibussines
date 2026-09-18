<?php
require_once('../../includes/autoloader.inc.php');

@session_start();

$core = new Core();
$core->SessionCheck();

$dbh = new Dbh();
$conn = $dbh->_connectodb();

## Read values
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$searchValue = $_POST['search']['value'];

$columnName = "Id";
$columnSortOrder = "DESC";

## SEARCH
$searchQuery = "";
if($searchValue != ''){
    $searchQuery = " AND (
        Name LIKE '%".$searchValue."%' OR 
        Mobile LIKE '%".$searchValue."%' OR 
        Issue LIKE '%".$searchValue."%' OR 
        Status LIKE '%".$searchValue."%'
    ) ";
}

## TOTAL RECORDS
$totalRecordsQuery = "SELECT COUNT(*) as count FROM employee_concerns";
$totalRecordsResult = mysqli_query($conn, $totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['count'];

## TOTAL FILTERED RECORDS
$totalFilterQuery = "SELECT COUNT(*) as count FROM employee_concerns WHERE 1 ".$searchQuery;
$totalFilterResult = mysqli_query($conn, $totalFilterQuery);
$totalRecordwithFilter = $totalFilterResult->fetch_assoc()['count'];

## FETCH DATA
$sql = "SELECT * FROM employee_concerns 
        WHERE 1 ".$searchQuery."
        ORDER BY ".$columnName." ".$columnSortOrder."
        LIMIT ".$row.",".$rowperpage;

$result = mysqli_query($conn, $sql);

$data = [];

while ($rowData = mysqli_fetch_assoc($result)) {

    $Id = $rowData['Id'];

    $attachment = "No File";
    if(!empty($rowData['Attachment'])) {
        $attachment = "<a href='../uploads/".$rowData['Attachment']."' target='_blank'>View</a>";
    }

    $isAnonymous = ($rowData['IsAnonymous'] == 1) ? "Yes" : "No";

    $status = $rowData['Status'];
    if($status == "New"){
        $statusBadge = "<span class='badge badge-primary'>New</span>";
    } elseif($status == "Contacted"){
        $statusBadge = "<span class='badge badge-warning'>Contacted</span>";
    } else {
        $statusBadge = "<span class='badge badge-success'>Closed</span>";
    }

    $action = "<button class='btn btn-sm btn-primary' onclick='openStatusModal($Id)'>Update</button>";

    $data[] = [
        "Name" => $rowData['Name'],
        "Mobile" => $rowData['Mobile'],
        "Issue" => $rowData['Issue'],
        "Attachment" => $attachment,
        "IsAnonymous" => $isAnonymous,
        "Status" => $statusBadge,
        "CreatedAt" => $rowData['CreatedAt'],
        "Action" => $action
    ];
}

## RESPONSE
$response = [
    "draw" => intval($draw),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordwithFilter,
    "data" => $data
];

echo json_encode($response);
?>