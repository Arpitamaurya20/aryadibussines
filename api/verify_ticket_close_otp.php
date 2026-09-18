<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$filename = 'api.logs';

// Open the file in append mode
$file = fopen($filename, 'a');

// Write the content to the file
fwrite($file, $data_raw);

// Close the file
fclose($file);
$data = json_decode($data_raw,true);
setTimeZone();
$response = array();
if(isset($data['TicketID']) && isset($data['TicketCloseOTP']))
{
	$TicketID = $data['TicketID'];
	$otp = $data['TicketCloseOTP'];
	$conn = _connectodb();
	$response = checkTicketCloseOTP($conn,$TicketID,$otp);
	if($response['error'] == false)
	{
		$where = " where ID = $TicketID";
		$ticket_data = _getTableDetails($conn,'corporate_tickets', $where);
		$ticket_data['TicketStatus'] = "Closed";
		$ticket_data['TicketID'] = $TicketID;
		//SubmitTicketforClosure($conn,$TicketID,$data);
		ManageTicketAssignmentStatus($conn,$ticket_data);
		$service_report_details = _getTableDetails($conn,'corporate_ticket_general_service_report','where TicketID = '.$TicketID);
		if($service_report_details!=null)
		{
			$ServiceReportID = $service_report_details['ID'];
			$hvac_service_report_details = _getTableDetails($conn,'hvac_general_service_report','where ServiceReportID = '.$ServiceReportID);
			$url = 'https://techxpertindia.in/admin/corporate-tickets/action/generate_service_report_pdf.php';
			if($hvac_service_report_details != null)
			{
				$url = "https://techxpertindia.in/admin/corporate-tickets/action/generate_amc_service_report_pdf.php";
			}

			$core = new Core();
			// Define the POST data
			$postData = [
			    'ServiceReportID' => $ServiceReportID,
			    'Action' => 'Send'
			];
			$response_generate_pdf = $core->sendCurlRequest($postData,$url);
			//var_dump($response_generate_pdf);
		}
		$response['message'] = "Ticket closed!";
	}
}
else 
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>