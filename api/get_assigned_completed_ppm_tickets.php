<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getAssignedCompletedPPMTickets($conn,$data)
{
	$EmployeeID = (int)$data['EmployeeID'];
	$response = array();
	$response['data'] = array();
	$sql = "SELECT pt.*,
			b.BillingStatus AS BillingStatus,
			mc.CategoriesName AS CategoryName,
			br.BranchSite AS BranchName,
			ba.EquipmentName AS AssetName
		FROM ppm_tickets pt
		LEFT JOIN branch_assets ba ON pt.BranchAssetID = ba.ID
		LEFT JOIN manage_categories mc ON ba.Category = mc.ID
		LEFT JOIN branch br ON pt.BranchID = br.ID
		LEFT JOIN ppm_billing_tracking b ON pt.ID = b.TicketID
		WHERE pt.AssignedTo = $EmployeeID AND (pt.Status = 'Completed' OR pt.Status = 'Closed')
		ORDER BY pt.ID DESC";
	$result = mysqli_query($conn, $sql);
	if (!$result) {
		$response['error'] = true;
		$response['message'] = "Query failed: " . mysqli_error($conn);
		return $response;
	}
	while ($row = $result->fetch_assoc()) {
		$response['data'][] = $row;
	}
	$response['error'] = false;
	$response['message'] = "Tickets fetched";
	return $response;
}
if(isset($data['EmployeeID']))
{
	$conn = _connectodb();
	$response = getAssignedCompletedPPMTickets($conn,$data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>