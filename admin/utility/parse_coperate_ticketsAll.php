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

$dir = fopen("ITFilenew.csv", "r");
$k = 0;

while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
    $ClientTicketReference = $data[0];
    $Status = $data[1];

    $where = "WHERE ClientTicketID = '$ClientTicketReference'";
    $ticketDetails = $core->_getTableDetails($conn, 'corporate_tickets', $where);

    echo "<br>".$ClientTicketReference."-";

    if ($ticketDetails != null) 
    {
        $TicketId = $ticketDetails['ID'];
        $AssignedTo = $ticketDetails['AssignedTo'];

        if ($TicketId != "") 
        {
            // Update the corporate_tickets table
            $update_param = "Status = '$Status' WHERE ID = $TicketId";
            $response = $core->_UpdateTableRecords($conn, 'corporate_tickets', $update_param);

            if ($response['error'] == false) 
            {
                echo "Updated";

                // Insert into corporate_ticket_status_history table
                $history_sql = "INSERT INTO corporate_ticket_status_history (TicketID, AssignedTo, Status, CreatedDate, CreatedTime) 
                                VALUES ('$TicketId', '$AssignedTo', '$Status', '$CreatedDate', '$CreatedTime')";
                $history_response = $core->_InsertTableRecords($conn, $history_sql);

                if ($history_response['error'] == false) 
                {
                    echo " and history recorded.";
                } 
                else 
                {
                    echo " but error recording history.";
                }
            } 
            else 
            {
                echo "Error updating";
            }
        }
    } 
    else 
    {
        echo "Ticket Not Found";
    }
}

fclose($dir);
?>
