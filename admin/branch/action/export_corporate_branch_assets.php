<?php
@session_start();

include("../../controllers/common_controllers.php");
include('../controller/branch_controller.php');
require_once('../../includes/autoloader.inc.php');

$conn = _connectodb();
setTimeZone();

/* ================= SESSION CHECK ================= */
$UserType = SessionCheck();

/* ================= WHERE CONDITION ================= */
$where = " WHERE b.IsActive = 1 ";

if (!empty($_SESSION['CompanyID'])) {
    $CorporateID = $_SESSION['CompanyID'];
    $where .= " AND b.CompanyID = '$CorporateID'";
}

/* ================= SQL QUERY (WITH COMPANY TABLE) ================= */
$sql = "
SELECT 
    c.CompanyName,
    b.CompanyID,
    b.BranchSite,
    b.BranchCode,
    b.BranchEmail,
    b.BranchMobile,
    b.BranchCity,
    b.BranchState,
    b.BranchPostalCode,
    b.SiteIncharge,

    a.EquipmentName,
    a.Make,
    a.Model,
    a.SNo,
    a.Capacity,
    a.Qty,
    a.UoM,
    a.UnitRate,
    a.Amount,
    a.ManufacturingYear,
    a.ServiceType,
    a.Category,
    a.SubCategory,
    a.EquipmentLocation

FROM branch b
LEFT JOIN company c ON c.ID = b.CompanyID
LEFT JOIN branch_assets a ON a.BranchID = b.ID
$where
ORDER BY b.ID
";

$result = mysqli_query($conn, $sql);

/* ================= CSV HEADERS ================= */
$filename = "branch_assets_with_company_" . date("Ymd_His") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

/* ================= OUTPUT CSV ================= */
$output = fopen("php://output", "w");

/* UTF-8 BOM (Excel Fix) */
fputs($output, "\xEF\xBB\xBF");

/* CSV COLUMN HEADERS */
fputcsv($output, [
    'Company Name',
    'Company ID',
    'Branch Site',
    'Branch Code',
    'Email',
    'Mobile',
    'City',
    'State',
    'Postal Code',
    'Site Incharge',
    'Equipment Name',
    'Make',
    'Model',
    'Serial No',
    'Capacity',
    'Qty',
    'UoM',
    'Unit Rate',
    'Amount',
    'Manufacturing Year',
    'Service Type',
    'Category',
    'Sub Category',
    'Location'
]);

/* CSV DATA ROWS */
while ($row = mysqli_fetch_assoc($result)) {

    fputcsv($output, [
        $row['CompanyName'],
        $row['CompanyID'],
        $row['BranchSite'],
        $row['BranchCode'],
        $row['BranchEmail'],
        $row['BranchMobile'],
        $row['BranchCity'],
        $row['BranchState'],
        $row['BranchPostalCode'],
        $row['SiteIncharge'],
        $row['EquipmentName'],
        $row['Make'],
        $row['Model'],
        $row['SNo'],
        $row['Capacity'],
        $row['Qty'],
        $row['UoM'],
        $row['UnitRate'],
        $row['Amount'],
        $row['ManufacturingYear'],
        $row['ServiceType'],
        $row['Category'],
        $row['SubCategory'],
        $row['EquipmentLocation']
    ]);
}

fclose($output);
exit;
