<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
@session_start();
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$filename = 'ppm_api.logs';
// Open the file in append mode
$file = fopen($filename, 'a');
// Write the content to the file
fwrite($file, $data_raw);
setTimeZone();
$data = json_decode($data_raw,true);
$response = array();

if(isset($data['TicketID']))
{
	$TicketID = (int)$data['TicketID'];
	$conn = _connectodb();
	$host = $_SERVER['HTTP_HOST'] ?? '';
	$isLocal = stripos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || preg_match('/^(192\.168|10\.)\./', $host);
	$pdf_base_url = $isLocal ? 'http://localhost/Projects/aryadibussines/admin/corporate-tickets/action/' : 'https://techxpertindia.in/admin/corporate-tickets/action/';
	$ppm_ticket_obj = new Ppmtickets($conn);
	$where = " where ID = $TicketID";
	$ticket_data = _getTableDetails($conn,'ppm_tickets', $where);
	$ticket_data['TicketStatus'] = "Closed";
	$ticket_data['TicketID'] = $TicketID;
	//SubmitTicketforClosure($conn,$TicketID,$data);
	$ppm_ticket_obj->closePPMTicket($ticket_data);
	$service_report_details = _getTableDetails($conn,'ppm_ticket_general_service_report','where TicketID = '.$TicketID);
	if($service_report_details!=null)
	{
		$ServiceReportID = $service_report_details['ID'];
		$ppm_hvac_service_report_details = _getTableDetails($conn,'ppm_hvac_service_report','where ServiceReportID = '.$ServiceReportID);
		$url = $pdf_base_url . 'generate_ppm_service_report_pdf.php';
		if($ppm_hvac_service_report_details != null)
		{
			$url = $pdf_base_url . 'generate_ppm_hvac_service_report_pdf.php';
		}
		$core = new Core();
		// Define the POST data
		$postData = [
		    'ServiceReportID' => $ServiceReportID,
		    'Action' => $isLocal ? 'Download' : 'Send'
		];
		$response_generate_pdf = $core->sendCurlRequest($postData,$url);
		//var_dump($response_generate_pdf);
	}
	$response['message'] = "Ticket closed!";
	$response['error'] = false;
}
else
{
	$response['message'] = "Missing User Fields";
	$response['error'] = true;
}
echo json_encode($response);
?>