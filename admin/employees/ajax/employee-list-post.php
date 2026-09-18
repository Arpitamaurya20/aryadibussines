<?php

include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');

session_start();
$conn = _connectodb();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "ID";
$columnSortOrder = "DESC";
$UserType = $_GET['UserType'];

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (Name like '%".$searchValue."%' or Designation like '%".$searchValue."%' or EmployeeNumber like '%".$searchValue."%' or Email like '%".$searchValue."%' or ContactNumber like '%".$searchValue."%' or PAN like '%".$searchValue."%' or Aadhar like '%".$searchValue."%' or Vendor like '%".$searchValue."%' or IsActive like '%".$searchValue."%') ";
}

$filter = " where 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'employees', $filter);
## Total number of record with filtering
$totalRecords = getTotalEmployee($conn);

$filter = $filter. " ORDER BY ".$columnName." ".$columnSortOrder;
$filter = $filter." limit ".$row.",".$rowperpage;
$city_details = _getTableRecords($conn,'employees', $filter);




foreach ($city_details as $city_data) {
  extract($city_data);
 
  $Status = "";
  if($IsActive == 0)
  {
      $Status = "<a href='?action=deactive&ID=$ID' class='badge badge-light border border-secondary text-secondary' title='Click to Activate' data-toggle='tooltip'>Inactive</a>";
  }
  else
  {
      $Status = "<a href='?action=active&ID=$ID' class='badge badge-light border border-secondary text-secondary' title='Click to Deactivate' data-toggle='tooltip'>Active</a>";
  }

  $email = $contact_number =  $pan = $adhar = "";
  if($Email == "")
    $email = "Email - NA";
  else
     $email = "Email - ".$Email;
  if($ContactNumber == "")
    $contact_number = "Number - NA";
  else
    $contact_number = "Number - ".$ContactNumber;
  if($PAN == "")
    $pan = "PAN - NA";
  else
    $pan = "PAN - ".$PAN;
  if($Aadhar == "")
    $adhar = "Adhar - NA";
  else
    $adhar = "Aadhar - ".$Aadhar;
  
 
  $vendor = "";
  if($Vendor == 0){
    $vendor = "<span class='badge badge-light border border-secondary text-secondary'>No</span>";
  }else{
    $vendor = "<span class='badge badge-light border border-secondary text-secondary'>Yes</span>";
  }


  
  $data[] = array(
     "Name"=>"<span class='font-weight-bold'>$Name</span>",
     "ID"=>"<span class='badge badge-light border border-secondary text-secondary'>$EmployeeNumber</span>",
     "Department"=>"<span class='badge badge-light border border-secondary text-secondary'>$Department</span>",
     "ContactDetails"=>"<div style='font-size:12px;line-height:1.8;color:#475569;'>".$email."<br>".$contact_number."</div>",
     "Identidy"=>"<div style='font-size:11.5px;line-height:1.8;color:#64748b;'>".$pan."<br>".$adhar."</div>",
     "Vendor"=>$vendor,
     "View"=>"<a onclick='ViewEmployee($ID)' class='btn btn-sm btn-outline-secondary py-1 px-2 font-weight-bold'><i class='fal fa-eye'></i> View</a>",
     "CreatedBy"=>"<span class='text-muted'>$CreatedBy</span>",
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