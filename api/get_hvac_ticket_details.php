<?php

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

function getHVACTicketDetails($conn, $data)
{
    $where = " WHERE 1=1";

    // Add conditions based on the provided parameters
    if (!empty($data['ProblemReportedByClient'])) {
        $where .= " AND corp.ProblemReportedByClient = '" . mysqli_real_escape_string($conn, $data['ProblemReportedByClient']) . "'";
    }
    if (!empty($data['Observation'])) {
        $where .= " AND corp.Observation = '" . mysqli_real_escape_string($conn, $data['Observation']) . "'";
    }
    if (!empty($data['GrillTemperature'])) {
        $where .= " AND hvac.GrillTemperature = '" . mysqli_real_escape_string($conn, $data['GrillTemperature']) . "'";
    }
    if (!empty($data['AmbientTemperature'])) {
        $where .= " AND hvac.AmbientTemperature = '" . mysqli_real_escape_string($conn, $data['AmbientTemperature']) . "'";
    }
    if (!empty($data['RoomTemperature'])) {
        $where .= " AND hvac.RoomTemperature = '" . mysqli_real_escape_string($conn, $data['RoomTemperature']) . "'";
    }
    if (!empty($data['IndoorFan'])) {
        $where .= " AND hvac.IndoorFan = '" . mysqli_real_escape_string($conn, $data['IndoorFan']) . "'";
    }
    if (!empty($data['ReturnAirTemperature'])) {
        $where .= " AND hvac.ReturnAirTemperature = '" . mysqli_real_escape_string($conn, $data['ReturnAirTemperature']) . "'";
    }
    if (!empty($data['SupplyAirTemperature'])) {
        $where .= " AND hvac.SupplyAirTemperature = '" . mysqli_real_escape_string($conn, $data['SupplyAirTemperature']) . "'";
    }
    if (!empty($data['Compressor'])) {
        $where .= " AND hvac.Compressor = '" . mysqli_real_escape_string($conn, $data['Compressor']) . "'";
    }
    if (!empty($data['Voltage'])) {
        $where .= " AND hvac.Voltage = '" . mysqli_real_escape_string($conn, $data['Voltage']) . "'";
    }
    if (!empty($data['TotalCurrent'])) {
        $where .= " AND hvac.TotalCurrent = '" . mysqli_real_escape_string($conn, $data['TotalCurrent']) . "'";
    }
    if (!empty($data['OutdoorFan'])) {
        $where .= " AND hvac.OutdoorFan = '" . mysqli_real_escape_string($conn, $data['OutdoorFan']) . "'";
    }
    if (!empty($data['CompressorSuction'])) {
        $where .= " AND hvac.CompressorSuction = '" . mysqli_real_escape_string($conn, $data['CompressorSuction']) . "'";
    }
    if (!empty($data['CompressorDischarge'])) {
        $where .= " AND hvac.CompressorDischarge = '" . mysqli_real_escape_string($conn, $data['CompressorDischarge']) . "'";
    }
    if (!empty($data['CondenserAirInlet'])) {
        $where .= " AND hvac.CondenserAirInlet = '" . mysqli_real_escape_string($conn, $data['CondenserAirInlet']) . "'";
    }
    if (!empty($data['CondenserAirOutlet'])) {
        $where .= " AND hvac.CondenserAirOutlet = '" . mysqli_real_escape_string($conn, $data['CondenserAirOutlet']) . "'";
    }
    if (!empty($data['ServiceReportID'])) {
        $where .= " AND hvac.ServiceReportID = '" . mysqli_real_escape_string($conn, $data['ServiceReportID']) . "'";
    }
    if (!empty($data['ServiceReportTicketID'])) {
        $where .= " AND hvac.TicketID = '" . mysqli_real_escape_string($conn, $data['ServiceReportTicketID']) . "'";
    }
    if (!empty($data['CreatedBy'])) {
        $where .= " AND hvac.CreatedBy = '" . mysqli_real_escape_string($conn, $data['CreatedBy']) . "'";
    }
    if (!empty($data['Latitude'])) {
        $where .= " AND corp.Latitude = '" . mysqli_real_escape_string($conn, $data['Latitude']) . "'";
    }
    if (!empty($data['Longitude'])) {
        $where .= " AND corp.Longitude = '" . mysqli_real_escape_string($conn, $data['Longitude']) . "'";
    }

    // Join hvac_general_service_report with corporate_ticket_general_service_report
    $query = "
        SELECT hvac.*, corp.*
        FROM hvac_general_service_report AS hvac
        LEFT JOIN corporate_ticket_general_service_report AS corp
        ON hvac.ServiceReportID = corp.ID
        $where
    ";

    $result = mysqli_query($conn, $query);

    if ($result) {
        $response['data'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $response['error'] = false;
        $response['message'] = "Records fetched";
    } else {
        $response['error'] = true;
        $response['message'] = "Error fetching records: " . mysqli_error($conn);
    }

    return $response;
}

if (isset($data['ServiceReportID']) && isset($data['ServiceReportTicketID'])) {
    $conn = _connectodb();
    $response = getHVACTicketDetails($conn, $data);
} else {
    $response["error"] = true;
    $response["message"] = "Missing Required Fields";
}

echo json_encode($response);

?>
