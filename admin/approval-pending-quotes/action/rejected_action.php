<?php
include("../../controllers/common_controllers.php");
include('../controller/approval_pending_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();

$TicketID = $_POST['TicketID'];
$change_status = ManageTicketReject($conn,$TicketID);

echo json_encode($change_status);

?>