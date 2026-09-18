<?php
require_once('../../includes/autoloader.inc.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $core = new Core();
    $StateGSTID = $_POST['ID'];
    $query = " where ID = $StateGSTID";
    $result = $core->delete_identity_filter($conn,'company_state_gst', $query);
    if($result == true)
    {
        $response['message'] = "Deleted";
        $response['error'] = false;
    }
    else
    	$response['message'] = "Technical Problem. Please try again";
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>