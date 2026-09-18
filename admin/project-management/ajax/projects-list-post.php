<?php

require_once('../../includes/autoloader.inc.php');

@session_start();
$core = new Core();
$UserType = $core->SessionCheck();
$filter_employee = "";
if($UserType != "Admin")
{
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if(isset($_SESSION['Roles']['EmployeeID']))
            {
                $Employee_ID = $_SESSION['Roles']['EmployeeID'];
            }   
        }
    }
    $filter_employee = " AND a.ProjectManager = $Employee_ID";
}
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$projects = new Projects($conn);

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
   $searchQuery = " and (a.ProjectName like '%".$searchValue."%' or b.ProjectManager like '%".$searchValue."%' or TicketNumber like '%".$searchValue."%') ";
}

$filter = " where 1 ";
$filter = $filter.$filter_employee.$searchQuery;
/*$filter = $filter." AND (ConvenienceDate>='$StartDate' AND ConvenienceDate<='$EndDate') ";*/



$sql_count = " Select COUNT(*) as row_count from projects a INNER JOIN employees b on a.ProjectManager = b.ID $filter";
$result_Count = mysqli_query($conn, $sql_count);
if ($result_Count) 
{
    $row_count_result = $result_Count->fetch_assoc();
    $row_count = $row_count_result['row_count'];
    $totalRecordwithFilter = $row_count;
}
else
{
    //$tf_where = " where (CreatedDate>='$StartDate' AND CreatedDate<='$EndDate')";
    $tf_where = " where 1";
    $totalRecordwithFilter = $core->_getTotalRows($conn,'projects',$tf_where);
}

## Total number of record with filtering
$totalRecords = $totalRecordwithFilter;
//$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');

$filter = $filter. " ORDER BY ".$columnName." ".$columnSortOrder;
$filter = $filter." limit ".$row.",".$rowperpage;

$sql = "Select a.*,b.Name from projects a INNER JOIN employees b on a.ProjectManager = b.ID ".$filter;
//echo $sql;
$projects_data = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($projects_data, $row);
        }
    }
} else {
    //echo $sql;
}



foreach ($projects_data as $i_data) 
{
    extract($i_data);   
    $view_tasks = "<a onclick='ViewTasks(".$ID.")'><span class='badge badge-primary cursor-pointer'>View Tasks</span></a>";
    $add_task = "<a onclick='AddTask(".$ID.")'><span class='badge badge-primary cursor-pointer'>Add</span></a>";
    if($UserType == "Admin")
    {
        $ProjectName = "<a onclick='EditProject(".$ID.")'><i class='mr-2 fas fa-edit'></i>".$ProjectName."</a>";
    }
    $data[] = array(
        "ProjectName"=>$ProjectName,
        "ProjectManager"=>$Name,
        "StartDate"=>$StartDate,
        "EndDate"=>$EndDate,
        "TicketReference"=>$TicketNumber,
        "ViewTasks"=>$view_tasks,
        "AddTasks"=>$add_task,
        "Status"=>$Status
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