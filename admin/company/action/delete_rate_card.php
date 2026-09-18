<?php
require_once('../../includes/autoloader.inc.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $core = new Core();
    $RateCardID = $_POST['ID'];
    $query = " where ID = $RateCardID";
    $result = $core->delete_identity_filter($conn,'corporate_rate_card', $query);
    if($result == true)
    {
        $response['message'] = "Rate Card Deleted";
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