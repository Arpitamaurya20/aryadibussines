<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(0);

include("../../controllers/common_controllers.php");
include('../controller/company_controller.php');

$conn = _connectodb();
setTimeZone();

$where = " WHERE IsActive = 1";
$company_details = _getTableRecords($conn, 'company', $where);

/* Clean EVERYTHING before output */
ob_end_clean();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=company_report.csv');
header('Pragma: no-cache');
header('Expires: 0');

/* UTF-8 BOM for Excel */
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

fputcsv($out, [
    'Corporate',
    'Company Name',
    'Company Email',
    'Phone Number',
    'Mobile Number',
    'Tendor',
    'R&M TAT',
    'PO/WO',
    'PO/WO Date'
]);

foreach ($company_details as $Companydata) {

    $CorporateID = (int)($Companydata["CorporateName"] ?? 0);
    $CorporateData = _getTableDetails($conn, 'corporate', " WHERE ID = $CorporateID");

    $corporateName = $CorporateData['CorporateName'] ?? '';

    fputcsv($out, [
        $corporateName,
        $Companydata['CompanyName'] ?? '',
        $Companydata['CompanyEmail'] ?? '',
        $Companydata['CompanyPhone'] ?? '',
        $Companydata['CompanyMobile'] ?? '',
        $Companydata['CompanyTendor'] ?? '',
        $Companydata['CompanyTAT'] ?? '',
        $Companydata['CompanyPOWO'] ?? '',
        $Companydata['CompanyPOWODate'] ?? ''
    ]);
}

fclose($out);
exit;
