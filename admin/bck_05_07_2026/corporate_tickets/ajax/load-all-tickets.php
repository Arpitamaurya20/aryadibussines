<?php
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporate_tickets_obj = new Corporateticket($conn);
if (isset($_GET['q'])) {
    $searchTerm = $_GET['q'];
    $response = $corporate_tickets_obj->LoadAllTickets_Searchtext($searchTerm);
    echo $response;
}
else
{
	 echo json_encode(array('error' => 'No search term provided.'));
}
?>