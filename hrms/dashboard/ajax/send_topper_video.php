<?php
@session_start();
require_once('../../include/autoloader.inc.php');

header('Content-Type: application/json');
error_reporting(0); // suppress warnings from breaking JSON

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$row = isset($_POST['start']) ? intval($_POST['start']) : 0;
$rowperpage = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

$searchQuery = "";
if (!empty($searchValue)) {
    $searchValue = mysqli_real_escape_string($conn, $searchValue);
    $searchQuery = " AND (title LIKE '%$searchValue%' OR video_url LIKE '%$searchValue%')";
}

// Total records
$totalRecordsQuery = "SELECT COUNT(*) as count FROM topper_videos WHERE 1";
$totalRecordsResult = mysqli_query($conn, $totalRecordsQuery);
$totalRecords = mysqli_fetch_assoc($totalRecordsResult)['count'];

// Total after filtering
$totalFilteredQuery = "SELECT COUNT(*) as count FROM topper_videos WHERE 1 $searchQuery";
$totalFilteredResult = mysqli_query($conn, $totalFilteredQuery);
$totalRecordwithFilter = mysqli_fetch_assoc($totalFilteredResult)['count'];

// Fetch data
$query = "SELECT id, title, video_url, status
          FROM topper_videos
          WHERE 1 $searchQuery
          ORDER BY id DESC
          LIMIT $row, $rowperpage";
$result = mysqli_query($conn, $query);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['id'];
    $status = strtoupper($row['status']);
    $status_span = $status === "ACTIVE" ? "<h5><span class='badge text-bg-success'>{$status}</span></h5>" : "<h5><span class='badge text-bg-danger'>{$status}</span></h5>";
    $actions = "";
    $yt_video_id = $row['video_url'];
    $youtube_video = "<a href='https://www.youtube.com/watch?v=$yt_video_id' target='_blank'>YouTube Video</a>";

    $data[] = [
        "id" => $id,
        "title" => $row['title'],
        "video_url" =>  $yt_video_id,
        "status" =>  $status_span,
        "edit" => "<button type='button' class='btn btn-link' onclick='open_modal_to_edit({$id})'>Edit</button>",
        "delete" => "<button type='button' class='btn btn-danger' onclick='confirm_to_delete({$id})'>Delete</button>",
        "yt_link" => $youtube_video
    ];
}

$response = [
    "draw" => $draw,
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
];

echo json_encode($response);
exit;
