<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once('../includes/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();

$core = new Core();
$core->setTimeZone();
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");

function getCompanyID($conn, $core, $companyName) {
    $where = "WHERE CompanyName = '$companyName'";
    $companyDetails = $core->_getTableDetails($conn, 'company', $where);
    return $companyDetails ? $companyDetails['ID'] : null;
}

$dir = fopen("ColliersIDFCNew.csv", "r");
$k = 0;

while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) {
    $companyName = $data[0];
    $branchSite = $data[1];
    $branchEmail = $data[3];
    $branchMobile = $data[4];

    $companyID = getCompanyID($conn, $core, $companyName);

    if ($companyID !== null) {
        // Prepare update parameters for branch table
        $update_param = "CompanyID = $companyID, BranchEmail = '$branchEmail', BranchMobile = '$branchMobile' WHERE BranchSite = '$branchSite'";
        
        // Update the branch table
        $response = $core->_UpdateTableRecords($conn, 'branch', $update_param);

        if ($response['error'] == false) {
            echo "Branch updated successfully for CompanyID: $companyID<br>";
        } else {
            echo "Error updating branch for CompanyID: $companyID<br>";
        }
    } else {
        echo "Company not found for CompanyName: $companyName<br>";
    }
}

fclose($dir);
?>
