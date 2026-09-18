<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['EmployeeID'])) {
    $dbh = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();

    if (isset($data['EmployeeID'])) {
        $Status= "";
        if(isset($data['Status'])){
            $Status=trim($data['Status']);
        }	
    
        $EmployeeID = $data['EmployeeID'];
        if ($EmployeeID != -1) {

            $filter_limit = "";
            if (isset($data['start_counter'])) {
                $start_counter = $data['start_counter'];
                $no_of_records = $data['no_of_records'];
                $filter_limit = " LIMIT $start_counter, $no_of_records ";
            }

            // Get cities mapped to Employees
            $company = new Branch($conn);
            $company_array_mapped_raw = $company->getMappedAccountsofBranchAccountManager($EmployeeID);
            $Branch_mapped = array();
            foreach ($company_array_mapped_raw as $Branch_mapped_raw) {
                $Branch_mapped[] = $Branch_mapped_raw['ID'];
            }



            if (!empty($Branch_mapped)) {
                $sql_in_branch = "'" . implode("', '", $Branch_mapped) . "'";
                if (!empty($Status)) {
                    $where = " WHERE BranchID IN ($sql_in_branch) AND Status = '$Status' AND Status != 'Closed' ORDER BY ID DESC $filter_limit";
                } else {
                    $where = " WHERE BranchID IN ($sql_in_branch) AND Status != 'Closed' ORDER BY ID DESC $filter_limit";
                }
                $tickets_array = $core->_getTableRecords($conn, 'corporate_tickets', $where);

                $response['data'] = $tickets_array;
                $response['error'] = false;
                $response['message'] = "Tickets fetched successfully.";
            } else {
                $response['error'] = true;
                $response['message'] = "No Branch IDs found for the given EmployeeID.";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Invalid EmployeeID.";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "EmployeeID is required.";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Missing EmployeeID.";
}

echo json_encode($response);
?>
