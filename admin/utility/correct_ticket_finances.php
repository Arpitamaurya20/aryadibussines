<?php
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$corporateticket = new Corporateticket($conn);
$corporateticket->recalculateTicketFinances();

?>