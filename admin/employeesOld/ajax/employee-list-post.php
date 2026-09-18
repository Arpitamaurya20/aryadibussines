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
      $Status = "<a href='?action=deactive&ID=$ID' class='btn btn-dark btn-sm shadow-none waves-effect waves-dark' title='Click to active' data-toggle='tooltip'>Deactive</a>";
  }
  else
  {
      $Status = "<a href='?action=active&ID=$ID' class='btn btn-success btn-sm shadow-none waves-effect waves-dark' title='Click to Deactive' data-toggle='tooltip'>Active</a>";
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
    $vendor = "No";
  }else{
    $vendor = "yes";
  }


  
  $data[] = array(
     "Name"=>$Name,
     "ID"=>$EmployeeNumber,
     "Department"=>$Department,
     "ContactDetails"=>$email."<br>".$contact_number,
     "Identidy"=>$pan."<br>".$adhar,
     "Vendor"=>$vendor,
     "View"=>"<a onclick='ViewEmployee($ID)''><span class='badge badge-primary cursor-pointer'>View</span></a>",
     "CreatedBy"=>$CreatedBy,
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