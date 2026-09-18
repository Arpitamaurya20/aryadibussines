<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$response = array();
if(isset($_SESSION['CompanyID']))
{
    $CompanyID = $_SESSION['CompanyID'];
    $CreatedBy = $_SESSION['pb_username'];
    if(isset($_POST)) 
    {
        $data = $_POST;
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();

        $core = new Core();
        $core->setTimeZone();

        $CreatedDate = date("Y-m-d");
        $CreatedTime = date("H:i:s");

        if($data['form_action'] == "add")
        {
        
            $rowData = [
                'CompanyID' => $CompanyID,
                'CompanyState' => $data['state'],
                'GST' => $data['state_gst'],
                'Address' => $data['state_gst_address'],
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy
            ];
            $response = $core->_InsertTableRecords_prepare($conn, 'company_state_gst', $rowData);
        }
        else
        {
            $rowData = [
                'CompanyState' => $data['state'],
                'GST' => $data['state_gst'],
                'Address' => $data['state_gst_address']
            ];
            $whereCondition = [
                'ID' => $data['form_id']
            ];
            $response = $core->_UpdateTableRecords_prepare($conn, 'company_state_gst', $rowData, $whereCondition);

        }
    }
    $conn->close();
}
else
{
    $response['message'] = "Technical Error ! Please login and try again.";
    $response['error'] = true;
}
echo json_encode($response);


?>
