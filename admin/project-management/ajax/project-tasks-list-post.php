<?php

require_once('../../includes/autoloader.inc.php');

@session_start();
$core = new Core();
$UserType = $core->SessionCheck();
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$projects = new Projects($conn);
$ProjectID = "-1";
if(!isset($_SESSION['ProjectID']))
{
    // Redirect to Project Screen
}
else
{
    $ProjectID = $_SESSION['ProjectID'];
}
$project_details = $projects->GetProjectDetails($ProjectID);
$ProjectName = $project_details['ProjectName'];
## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

/*$filter_date = $_GET['filter_date'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];*/

$columnName = "ID";
$columnSortOrder = "DESC";

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (Description like '%".$searchValue."%' or StartDate like '%".$searchValue."%' or EndDate like '%".$searchValue."%' or ActualEndDate like '%".$searchValue."%') ";
}

$filter = " where ProjectID = $ProjectID ";
$filter = $filter.$searchQuery;
/*$filter = $filter." AND (ConvenienceDate>='$StartDate' AND ConvenienceDate<='$EndDate') ";*/



$sql_count = " Select COUNT(*) as row_count from project_tasks $filter";
$result_Count = mysqli_query($conn, $sql_count);
if ($result_Count) 
{
    $row_count_result = $result_Count->fetch_assoc();
    $row_count = $row_count_result['row_count'];
    $totalRecordwithFilter = $row_count;
}
else
{
    $error = mysqli_error($conn);
    echo $sql_count;
    echo $error;
}

## Total number of record with filtering
$totalRecords = $totalRecordwithFilter;

$filter = $filter. " ORDER BY ".$columnName." ".$columnSortOrder;
$filter = $filter." limit ".$row.",".$rowperpage;

$sql = "Select * from project_tasks ".$filter;
$project_tasks_data = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($project_tasks_data, $row);
        }
    }
} else {
    //echo $sql;
}



foreach ($project_tasks_data as $task) 
{
    extract($task);   
    if($ActualEndDate == "")
    {
        $ActualEndDate = "N.A.";
    }
    $task_progress_raw = $projects->GetTaskProgress($ID,$TaskStatus);
    $task_progress_raw = number_format($task_progress_raw,2);
    $task_progress = (string)$task_progress_raw."%";
    $edit_task = "<a onclick='EditTaskDates(".$ID.",\"".$TaskStatus."\")'><span class='badge badge-primary cursor-pointer'>Edit</span></a>";
    $progress = "
    <div class='d-flex'>
        Progress
        <span class='d-inline-block ml-auto'>".$task_progress."</span>
    </div>
    <div class='progress progress-sm mb-3'>
                    <div class='progress-bar bg-fusion-400' role='progressbar' style='width:".$task_progress.";'' aria-valuenow='".$task_progress_raw."' aria-valuemin='0' aria-valuemax='100'></div>
                </div>";
    $data[] = array(
        "ProjectName"=>$ProjectName,
        "TaskTitle"=>$Description,
        "StartDate"=>$StartDate,
        "ExpectedEndDate"=>$EndDate,
        "ActualEndDate"=>$ActualEndDate,
        "Progress"=>$progress,
        "Cost"=>$InternalCost."<br>".$ExternalCost,
        "Status"=>$TaskStatus,
        "Edit"=>$edit_task
   );
}
## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);
// echo $response;
echo json_encode($response);
?>