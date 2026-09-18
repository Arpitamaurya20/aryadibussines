<?php

include("../../controllers/common_controllers.php");
include('../../company/controller/company_controller.php');
include('../controller/branch_controller.php');

@session_start();
$conn = _connectodb();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "ID";
$columnSortOrder = "DESC";
$nav = $_GET['nav'];
$CorporateID = $_GET['CorporateID'];
$CompanyID = $_GET['CompanyID'];
$UserType = $_GET['UserType'];
$BranchID = $_GET['BranchID'];
$filter_state = "";
if(isset($_GET['stateName']))
{
    $stateName = $_GET['stateName'];
    if($stateName != "")
    {
        $filter_state = " AND BranchState = '$stateName'";
    }
}

$filter_city = "";
if(isset($_GET['city']))
{
    $city = $_GET['city'];
    if($city != "")
    {
        $filter_city = " AND BranchCity = '$city'";
    }
}

$filter_company_id = "";
if(isset($_GET['filter_company_id']))
{
    $f_company_id = $_GET['filter_company_id'];
    if($f_company_id != "")
    {
        $filter_company_id = " AND CompanyID = $f_company_id";
    }
}

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (BranchSite like '%".$searchValue."%' or BranchCode like '%".$searchValue."%' or BranchEmail LIKE '%".$searchValue."%') ";
}

$status = 1;
if(isset($_GET['status']))
{
    $status = intval($_GET['status']) === 0 ? 0 : 1;
}

$filter = " where IsActive = $status";
if($CompanyID != -1 && !$nav)
{
    $filter = $filter." AND CompanyID = $CompanyID";
}
$filter = $filter.$searchQuery.$filter_state.$filter_city.$filter_company_id." ORDER BY ".$columnName." ".$columnSortOrder;
$totalRecordwithFilter = _getTotalRows($conn,'branch', $filter);
## Total number of record with filtering
$totalRecords = _getTotalRows($conn,'branch', " where IsActive = $status");

$filter = $filter." limit ".$row.",".$rowperpage;
$branch_details = _getTableRecords($conn,'branch', $filter);

$company_array = getAllCompanies($conn);
$company_array_key = generateArraywithKey($company_array);

foreach ($branch_details as $branch_data) {
  $to_continue = false;
  extract($branch_data);
  if($BranchID == -1)
  {
    $to_continue = false;
  }
  else
  {
    $to_continue = true;
    if($BranchID == $ID)
    {
      $to_continue = false;
    }
  }
  if($to_continue)
  {
    continue;
  }

  $CompanyName = $company_array_key[$CompanyID]['CompanyName'];

  if($UserType == "Admin")
  {
  $data[] = array(
    "CompanyName"=>$CompanyName,
    "BranchName"=>$BranchSite,
    "Mobile_Alternate"=>$BranchMobile."<br>".$BranchLandline."<br>".$BranchEmail,
    "City_State"=>$BranchCity."<br>".$BranchState,
    "Branch_Assets"=>"<a onclick='ViewBranchAssets(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View Assets</span></a>",
    "ViewARC"=>"<a onclick='ViewARCItem(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View ARC</span></a>",
    "ViewSparePart"=>"<a onclick='ViewSparePartItem(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View Spare Part</span></a>",
    "Access"=>"<a onclick='OpenResetPasswordModal(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>Reset Password</span></a>",
    "Update"=>"<a onclick='UpdateBranch_modal(".$branch_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
    "Action"=>$status == 1 ? "<a onclick='DeactivateBranch(".$branch_data['ID'].")' title='Inactivate Branch'><i class='fal fa-ban text-danger' aria-hidden='true'></i></a>" : "<a onclick='ActivateBranch(".$branch_data['ID'].")' title='Activate Branch'><i class='fal fa-check-circle text-success' aria-hidden='true'></i></a>",
    "SiteIncharge"=>$SiteIncharge
   );
  }
  else if($UserType == "Corporate Admin")
  {
    $data[] = array(
    "CompanyName"=>$CompanyName,
    "BranchName"=>$BranchSite,
    "Mobile_Alternate"=>$BranchMobile."<br>".$BranchLandline."<br>".$BranchEmail,
    "City_State"=>$BranchCity."<br>".$BranchState,
    "Branch_Assets"=>"<a onclick='ViewBranchAssets(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View Assets</span></a>",
    "ViewARC"=>"<a onclick='ViewARCItem(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View ARC</span></a>",
    "ViewSparePart"=>"<a onclick='ViewSparePartItem(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View Spare Part</span></a>",
    "Access"=>"<a onclick='OpenResetPasswordModal(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>Reset Password</span></a>",
    "Update"=>"<a onclick='UpdateBranch_modal(".$branch_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
    "Action"=>'',
    "SiteIncharge"=>$SiteIncharge
   );
  }
  else
  {
    $data[] = array(
    "CompanyName"=>$CompanyName,
    "BranchName"=>$BranchSite,
    "Mobile_Alternate"=>$BranchMobile."<br>".$BranchLandline."<br>".$BranchEmail,
    "City_State"=>$BranchCity."<br>".$BranchState,
    "Branch_Assets"=>"<a onclick='ViewBranchAssets(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View Assets</span></a>",
    "ViewARC"=>"<a onclick='ViewARCItem(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View ARC</span></a>",
    "ViewSparePart"=>"<a onclick='ViewSparePartItem(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>View Spare Part</span></a>",
    "Access"=>"<a onclick='OpenResetPasswordModal(".$branch_data['ID'].")'><span class='badge badge-primary cursor-pointer'>Reset Password</span></a>",
    "Update"=>"<a onclick='UpdateBranch_modal(".$branch_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
    "Action"=>$status == 1 ? "<a onclick='DeactivateBranch(".$branch_data['ID'].")' title='Inactivate Branch'><i class='fal fa-ban text-danger' aria-hidden='true'></i></a>" : "<a onclick='ActivateBranch(".$branch_data['ID'].")' title='Activate Branch'><i class='fal fa-check-circle text-success' aria-hidden='true'></i></a>",
    "SiteIncharge"=>$SiteIncharge,
   );
  }
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