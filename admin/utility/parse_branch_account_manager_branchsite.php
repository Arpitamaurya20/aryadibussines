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
$employee_obj = new Employee($conn);
$employees_array = $employee_obj->setEmployeeArrayByName("All");

$dir = fopen("branch_RAJASTHAN.csv", "r");

while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) {
    $BranchSite = $data[2];
    $AccountBranchManagerName = $data[5];
    
    // Fetch employee details by name
    $where = " WHERE Name = '$AccountBranchManagerName'";
    $employeedetails = $core->_getTableDetails($conn, 'employees', $where);

    if ($employeedetails) {
        $employeeID = $employeedetails['ID'];
        
        // Fetch branch details by BranchSite
        $where = "WHERE BranchSite = '$BranchSite'";
        $Branchdetails = $core->_getTableDetails($conn, 'branch', $where);

        if ($Branchdetails) {
            $BranchID = $Branchdetails['ID'];

            // Update the branch with the new AccountBranchManagerID
            $update_param = "AccountBranchManager = '$employeeID' WHERE ID = $BranchID";
            $response = $core->_UpdateTableRecords($conn, 'branch', $update_param);

            if (!$response['error']) {
                echo "Updated Branch ID: $BranchID with Account Branch Manager ID: $employeeID\n";
            } else {
                echo "Error updating Branch ID: $BranchID\n";
            }
        } else {
            echo "Branch not found for BranchSite: $BranchSite\n";
        }
    } else {
        echo "Employee not found for Name: $AccountBranchManagerName\n";
    }
}

fclose($dir);
?>
