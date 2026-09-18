<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$response = array();
if(isset($_POST['StateGSTID']))
{
    $StateGSTID = $_POST['StateGSTID'];
    $CreatedBy = $_SESSION['pb_username'];
   
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();

    $core = new Core();
    $response = $core->_getTableDetails($conn,'company_state_gst','where ID = '.$StateGSTID);
    $conn->close();
}
else
{
    $response['message'] = "Technical Error ! Please login and try again.";
    $response['error'] = true;
}
echo json_encode($response);
?>
