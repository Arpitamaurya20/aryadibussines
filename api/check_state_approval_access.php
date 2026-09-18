<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

if(isset($data['EmployeeID']))
{
    $conn = _connectodb();
    $EmployeeID = $data['EmployeeID'];

    // Get ALL state access rows
    $where = " WHERE EmployeeID = '$EmployeeID' AND IsActive = 1";
    $access_rows = _getTableRecords($conn, 'user_quation_access', $where);

    $response['IsStateApprove'] = "No";
    $response['IsFinanceApprove'] = "No";

    if(!empty($access_rows))
    {
        foreach($access_rows as $row)
        {
            if($row['IsApprovedState'] == "Yes")
            {
                $response['IsStateApprove'] = "Yes";
            }

            if($row['IsApprovedFinance'] == "Yes")
            {
                $response['IsFinanceApprove'] = "Yes";
            }

            // Optimization: break if both found
            if($response['IsStateApprove'] == "Yes" && 
               $response['IsFinanceApprove'] == "Yes")
            {
                break;
            }
        }
    }

    $response['error'] = false;
    $response['message'] = "Overall approval status checked";
}
else
{
    $response['error'] = true;
    $response['message'] = "EmployeeID is required";
}

echo json_encode($response);

?>
