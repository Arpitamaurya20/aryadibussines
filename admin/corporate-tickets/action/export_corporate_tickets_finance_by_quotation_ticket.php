<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();

include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');

$conn = _connectodb();
setTimeZone();

/* ================= INPUTS ================= */
$corporate_id = $_POST['CorporateID'];
$branch_id    = $_POST['BranchID'];
$filter_date  = $_POST['filter_date_export'];
$epoch_time   = $_POST['epoch_time'];

$StartDate = explode(" - ", $filter_date)[0];
$EndDate   = explode(" - ", $filter_date)[1];

$city_export            = $_POST['city_export'] ?? "";
$branch_export          = $_POST['branch_export'] ?? "";
$state_export           = $_POST['state_export'] ?? "";
$status_export          = $_POST['status_export'] ?? "";
$company_account_export = $_POST['company_account_export'] ?? "";

/* ================= CONDITIONS ================= */
$corporate_check = "1";
$status_check    = "1";

if ($status_export != "") {
    $status_check = " ct.Status = '$status_export'";
}

if ($company_account_export != "") {
    $corporate_check = " ct.CorporateID = $company_account_export ";
}

if ($corporate_id != "" && $corporate_id != -1) {
    $corporate_check = " ct.CorporateID = $corporate_id ";
}

/* ================= MASTER DATA ================= */
$city_array = [];
$city_array_raw = _getTableRecords($conn, 'citydata', ' where 1');
foreach ($city_array_raw as $city) {
    $city_array[$city['CityName']]['CorporateLead'] = $city['CorporateLead'];
}

$employee_array = (new Employee($conn))->setEmployeeArray('All');
$company_array  = (new Company($conn))->setCompanyArray('All');
$branch_array   = (new Branch($conn))->setBranchArray('All');
$state_region_array = (new State($conn))->getStateswithRegion('All');

/* ================= SQL ================= */
$where = "
WHERE 
    $corporate_check 
    AND $status_check
    AND ct.CreatedDate BETWEEN '$StartDate' AND '$EndDate'
    AND ct.IsActive = 1
ORDER BY ct.ID DESC
";

$sql = "
SELECT 
    ct.ID AS TicketID,
    ct.ClientTicketID,
    ct.CorporateID,
    ct.BranchID,
    ct.Type,
    ct.Service,
    ct.Message,
    ct.CreatedDate,
    ct.CreatedTime,
    ct.CloseDate,
    ct.CloseTime,
    ct.DueDate,
    ct.CreatedBy,
    ct.AssignedTo,
    ct.Status AS TicketStatus,

    tf.T_TotalPrice,
    tf.C_TotalPrice,
    fs.StatusName AS FinanceStatus,

    sr.ID AS ServiceReportID,

    q.ID AS QuotationID,
    q.QuotationStatus,

    qi.Qty,
    qi.PerItemPrice,
    qi.TotalPrice AS ItemTotal,

    rc.LineItemName,
    rc.ARCItem,
    rc.ARCCode,
    rc.Type AS ItemType,
    rc.Category AS ItemCategory,
    rc.Make,
    rc.HSN,
    rc.UoM,
    rc.Tax

FROM corporate_ticket_quotation_items qi
INNER JOIN corporate_ticket_quotation q ON qi.QuotationID = q.ID
INNER JOIN corporate_tickets ct ON q.TicketID = ct.ID
LEFT JOIN corporate_tickets_finance tf ON ct.ID = tf.TicketID
LEFT JOIN corporate_ticket_finance_status fs ON tf.Status = fs.Status
LEFT JOIN corporate_ticket_general_service_report sr ON sr.TicketID = ct.ID
INNER JOIN corporate_rate_card rc ON qi.LineItemID = rc.ID
$where
";

$result = mysqli_query($conn, $sql);

/* ================= EXCEL HEADER ================= */
$output = '
<table border="1">
<tr>
<th>Client Ticket ID</th>
<th>Ticket ID</th>
<th>Corporate</th>
<th>Branch</th>
<th>City</th>
<th>City Lead</th>
<th>State</th>
<th>Region</th>
<th>Type</th>
<th>Service</th>
<th>Message</th>
<th>Created Date</th>
<th>Close Date</th>
<th>Assigned To</th>
<th>Ticket Status</th>
<th>Finance Status</th>
<th>T Total</th>
<th>C Total</th>
<th>Quotation Status</th>
<th>Line Item</th>
<th>ARC Item</th>
<th>ARC Code</th>
<th>Item Type</th>
<th>Category</th>
<th>Make</th>
<th>HSN</th>
<th>Qty</th>
<th>UoM</th>
<th>Per Item Price</th>
<th>Item Total</th>
<th>Tax %</th>
<th>Price Incl Tax</th>
<th>Service Report</th>
<th>Quotation</th>
</tr>
';

/* ================= DATA LOOP ================= */
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $BranchName = $City = $State = $Region = $CityLead = "N.A.";
        $AssignEmployeeName = "N.A.";

        if (isset($branch_array[$row['BranchID']])) {
            $BranchName = $branch_array[$row['BranchID']]['BranchName'];
            $City       = $branch_array[$row['BranchID']]['BranchCity'];
            $State      = $branch_array[$row['BranchID']]['BranchState'];

            if ($city_export && $City != $city_export) continue;
            if ($branch_export && $BranchName != $branch_export) continue;
            if ($state_export && $State != $state_export) continue;

            if (isset($state_region_array[$State])) {
                $Region = $state_region_array[$State]['RegionName'];
            }

            if (isset($city_array[$City]['CorporateLead'])) {
                $leadID = $city_array[$City]['CorporateLead'];
                if (isset($employee_array[$leadID])) {
                    $CityLead = $employee_array[$leadID]['Name'];
                }
            }
        } else {
            continue;
        }

        if (isset($employee_array[$row['AssignedTo']])) {
            $AssignEmployeeName = $employee_array[$row['AssignedTo']]['Name'];
        }

        $CompanyName = $company_array[$row['CorporateID']]['CompanyName'] ?? "N.A.";
        $ARCItem = ($row['ARCItem'] == 1) ? "Yes" : "No";

        $taxAmount = ($row['ItemTotal'] * $row['Tax']) / 100;
        $priceWithTax = round($row['ItemTotal'] + $taxAmount, 2);

        $ServiceReport_html = "Not Generated";
        if (!empty($row['ServiceReportID'])) {
            $ServiceReport_html = "<a href='https://techxpertindia.in/admin/corporate-tickets/action/generate_service_report_pdf.php?ServiceReportID={$row['ServiceReportID']}'>View</a>";
        }

        $Quotation_html = "Not Generated";
        if (!empty($row['QuotationID'])) {
            $Quotation_html = "<a href='https://techxpertindia.in/admin/corporate-tickets/action/generate_quotation_pdf.php?QuotationID={$row['QuotationID']}'>View</a>";
        }

        $output .= "
        <tr>
        <td>{$row['ClientTicketID']}</td>
        <td>{$row['TicketID']}</td>
        <td>{$CompanyName}</td>
        <td>{$BranchName}</td>
        <td>{$City}</td>
        <td>{$CityLead}</td>
        <td>{$State}</td>
        <td>{$Region}</td>
        <td>{$row['Type']}</td>
        <td>{$row['Service']}</td>
        <td>{$row['Message']}</td>
        <td>{$row['CreatedDate']}</td>
        <td>{$row['CloseDate']}</td>
        <td>{$AssignEmployeeName}</td>
        <td>{$row['TicketStatus']}</td>
        <td>{$row['FinanceStatus']}</td>
        <td>{$row['T_TotalPrice']}</td>
        <td>{$row['C_TotalPrice']}</td>
        <td>{$row['QuotationStatus']}</td>
        <td>{$row['LineItemName']}</td>
        <td>{$ARCItem}</td>
        <td>{$row['ARCCode']}</td>
        <td>{$row['ItemType']}</td>
        <td>{$row['ItemCategory']}</td>
        <td>{$row['Make']}</td>
        <td>{$row['HSN']}</td>
        <td>{$row['Qty']}</td>
        <td>{$row['UoM']}</td>
        <td>{$row['PerItemPrice']}</td>
        <td>{$row['ItemTotal']}</td>
        <td>{$row['Tax']}</td>
        <td>{$priceWithTax}</td>
        <td>{$ServiceReport_html}</td>
        <td>{$Quotation_html}</td>
        </tr>";
    }
} else {
    $output .= "<tr><td colspan='35'>No Data Available</td></tr>";
}

$output .= "</table>";

/* ================= FILE SAVE ================= */
$file_name = "../report_finance".$epoch_time.".xls";
$myfile = fopen($file_name, "w");
fwrite($myfile, $output);
fclose($myfile);
?>
