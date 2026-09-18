<?php
require_once('../../includes/autoloader.inc.php');

@session_start();
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "a.ID";
$columnSortOrder = "DESC";
$CorporateID = $CompanyID = $_GET['CompanyID'];
$filter_company_id = "";
if(isset($_GET['filter_company_id']))
{
    $f_company_id = $_GET['filter_company_id'];
    if($f_company_id != "")
    {
        $filter_company_id = " AND a.CompanyID = $f_company_id";
    }
}

$filter_type = "";
if(isset($_GET['type']))
{
    $type = $_GET['type'];
    if($type != "")
    {
        $filter_type = " AND a.Type = '$type'";
    }
}

$filter_category = "";
if(isset($_GET['category']))
{
    $category = $_GET['category'];
    if($category != "")
    {
        $filter_category = " AND a.Category = '$category'";
    }
}

$filter_subcategory = "";
if(isset($_GET['category']))
{
    $subcategory = $_GET['subcategory'];
    if($subcategory != "")
    {
        $filter_subcategory = " AND a.SubCategory = '$subcategory'";
    }
}

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (a.Category like '%".$searchValue."%' or a.Type like '%".$searchValue."%' or a.Make like '%".$searchValue."%' or a.SubCategory like '%".$searchValue."%' or a.LineItemName LIKE '%".$searchValue."%' or a.HSN LIKE '%".$searchValue."%' or a.ARCCode LIKE '%".$searchValue."%') ";
}

$filter = " where a.IsActive = 1 and a.ARCItem = 1";
if($CompanyID != -1)
{
    $filter = $filter." AND a.CompanyID = $CompanyID";
}
$filter = $filter.$filter_type.$filter_category.$filter_subcategory;
$totalRecordwithFilter = $core->_getTotalRows($conn,'corporate_rate_card a JOIN company b ON a.CompanyID = b.ID',$filter);
$filter = $filter.$searchQuery.$filter_company_id." ORDER BY ".$columnName." ".$columnSortOrder;
## Total number of record with filtering
$totalRecords = $totalRecordwithFilter;

$filter = $filter." limit ".$row.",".$rowperpage;
$sql = "Select a.*,b.CompanyName FROM corporate_rate_card a JOIN company b ON a.CompanyID = b.ID ".$filter;
//echo $sql;
$result = mysqli_query($conn, $sql);
$rate_cards_array = array();
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($rate_cards_array, $row);
        }
    }
} else {
    //echo $sql;
}
foreach ($rate_cards_array as $rate_card) 
{
  extract($rate_card);
  
  $data[] = array(
    "CompanyName"=>$CompanyName,
    "Type"=>$Type,
    "Category_SubCategory"=>$Category."<br>".$SubCategory,
    "LineItemName"=>$LineItemName,
    "Make"=>$Make,
    "HSN"=>$HSN,
    "ARCCode"=>$ARCCode,
    "UoM"=>$UoM,
    "Price"=>$Price,
    "Tax"=>$Tax,
    "Add"=>"<a onclick='AddToQuotation($ID)' class='cursor-pointer btn btn-primary btn-sm btn-icon rounded-circle'><i class='fal fa-add text-white' aria-hidden='true'></i></a>"
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