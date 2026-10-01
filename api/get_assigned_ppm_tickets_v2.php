<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw, true);

$response = array();

function getAssignedPPMTickets($conn, $data)
{
    $EmployeeID = (int)$data['EmployeeID']; // always cast to int for safety

    $response = array();
    $response['data'] = array();

    // Join ppm_tickets -> branch_assets -> manage_categories
    $sql = "
        SELECT 
            pt.ID,
            pt.TicketID,
            pt.CorporateID,
            pt.BranchID,
            pt.BranchAssetID,
            pt.PPMDate,
            pt.CreatedDate,
            pt.CreatedTime,
            pt.CloseDate,
            pt.CloseTime,
            pt.CreatedBy,
            pt.DueDate,
            pt.AssignedTo,
            pt.Status,
            pt.IsActive,
            b.BillingStatus AS BillingStatus,
            mc.CategoriesName AS CategoryName,
            br.BranchSite AS BranchName,
            ba.EquipmentName AS AssetName
        FROM ppm_tickets pt
        LEFT JOIN branch_assets ba ON pt.BranchAssetID = ba.ID
        LEFT JOIN manage_categories mc ON ba.Category = mc.ID
        LEFT JOIN branch br ON pt.BranchID = br.ID
        LEFT JOIN ppm_billing_tracking b ON pt.ID = b.TicketID
        WHERE pt.AssignedTo = $EmployeeID
          AND pt.Status != 'Closed'
          AND pt.IsActive = 1
        ORDER BY pt.ID DESC
    ";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
        $response['error'] = false;
        $response['message'] = "Tickets fetched";
    } else {
        $response['error'] = true;
        $response['message'] = "Query failed: " . mysqli_error($conn);
    }

    return $response;
}


if (isset($data['EmployeeID'])) {

    $conn = _connectodb();

    $response = getAssignedPPMTickets($conn, $data);
} else {

    $response["error"] = true;

    $response["message"] = "Missing User Fields";
}

echo json_encode($response);
