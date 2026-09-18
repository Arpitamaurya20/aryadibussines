<?php
require_once('../admin/controllers/common_controllers.php');
$message = "Dear Prateek,\n\nTicket for your Service AC Repair has been created. Our team will get in touch with you asap.\n\nRegards,\nTechXpert Team";
sendWhatsAppMessage("+918826789578",$message);

?>