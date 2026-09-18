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
$color_qsap = "#FFA500";
$color_qr = "#DC3545";
$color_qa = "#28A745";
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

$filter_quotation_status = "";
if(isset($_GET['quotation_status']))
{
    $quotation_status = $_GET['quotation_status'];
    if($quotation_status != "")
    {
        $filter_quotation_status = " AND a.QuotationStatus = '$quotation_status'";
    }
}

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (a.Category like '%".$searchValue."%' or a.SubCategory like '%".$searchValue."%') ";
}

$filter = " where a.IsActive = 1 AND a.QuotationStatus != 'Draft'";
if($CompanyID != -1)
{
    $filter = $filter." AND b.CorporateID = $CompanyID";
}
$filter = $filter.$filter_type.$filter_category.$filter_subcategory.$filter_quotation_status;

$sql_count = " Select COUNT(*) as row_count FROM `corporate_ticket_quotation` a LEFT JOIN corporate_tickets b ON a.TicketID = b.ID LEFT JOIN branch c ON b.BranchID = c.ID".$filter;
$result_Count = mysqli_query($conn, $sql_count);

$row_count_result = $result_Count->fetch_assoc();
$row_count = $row_count_result['row_count'];
$totalRecordwithFilter = $row_count;

## Total number of record with filtering
//$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');
$totalRecords = $totalRecordwithFilter;



$sql = "SELECT 
    a.ID,
    a.TicketID,
    a.QuotationStatus,
    a.QuotationDate,
    a.QuotationTC,
    a.QuotationExpiryDate,
    a.Remarks,
    a.CreatedBy,
    a.CreatedDate,
    a.CreatedTime,
    a.IsActive,
    b.ID as CorporateTicketID,
    b.TicketID,
    b.ClientTicketID,
    b.Service,
    b.Subservice,
    c.BranchSite,
    IFNULL(SUM(d.TotalPrice), 0) AS Cost
FROM 
    corporate_ticket_quotation a
LEFT JOIN 
    corporate_tickets b ON a.TicketID = b.ID
LEFT JOIN 
    branch c ON b.BranchID = c.ID
LEFT JOIN 
    corporate_ticket_quotation_items d ON a.ID = d.QuotationID
    $filter
GROUP BY 
    a.ID, 
    b.ID, 
    b.TicketID, 
    b.ClientTicketID, 
    b.Service, 
    b.Subservice, 
    c.BranchSite ";
    $sql = $sql." ORDER BY ".$columnName." ".$columnSortOrder;
$sql = $sql." limit ".$row.",".$rowperpage;
 
$quotations = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($quotations, $row);
        }
    }
}

foreach ($quotations as $quotation) 
{
  extract($quotation);
  $Cost = "₹".$core->formatIndianNumber($Cost);
  $bg_color = $color_qsap;
  if($QuotationStatus == "Quote Sent Approval Pending")
    $bg_color = $color_qsap;
  if($QuotationStatus == "Quote Approved")
    $bg_color = $color_qa;
  if($QuotationStatus == "Quote Rejected by Client")
    $bg_color = $color_qr;
  $data[] = array(
    "TicketNumber"=>$TicketID."<br>".$ClientTicketID,
    "BranchSite"=>$BranchSite,
    "Category_SubCategory"=>$Service."<br>".$Subservice,
    "CreatedDate_Time"=>$CreatedDate."<br>".$CreatedTime,
    "Status"=>"<span class='badge badge-danger cursor-pointer' style='background:".$bg_color."'>".$QuotationStatus."</span>",
    "Cost"=>$Cost,
    "View"=>"<a onclick='ViewBookingDetails($CorporateTicketID)'><span class='badge badge-primary cursor-pointer'>View Ticket</span></a>"
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