<?php
require_once('../../includes/autoloader.inc.php');
require_once('../controller/corporate_tickets_controller.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();

// Core class for SQL functions
$core = new Core();

$ticketID = $_POST['TicketID'] ?? '';

$sql = "
    SELECT 
        p.ID AS project_id,
        p.TicketID,
        p.ProjectName,
        p.ProjectManager,
        p.StartDate,
        p.EndDate,
        p.TicketNumber,
        p.Status,

        t.ID AS team_id,
        t.CustomerManagerName,
        t.CustomerManagerEmail,
        t.CustomerManagerPhone,
        t.CustomerSupervisorEmail,
        t.CustomerSupervisorPhone,
        t.TechXpertManagerEmail,
        t.TechXpertManagerPhone,

        t.OtherEmail,
        t.OtherPhonenumber,
        t.TechxpertOtherEmail,
        t.TechxpertOtherPhonenumber

    FROM projects p
    LEFT JOIN project_team_details t 
        ON p.TicketID = t.TicketID
    WHERE p.TicketID = '$ticketID'
    LIMIT 1
";

// Fetch record
$data = $core->_getSQLDetails($conn, $sql);

if (empty($data)) {
    echo json_encode(["status" => "not_found"]);
    exit;
}

$response = [
    "status" => "success",

    "project" => [
        "ID" => $data["project_id"],
        "TicketID" => $data["TicketID"],
        "ProjectName" => $data["ProjectName"],
        "ProjectManager" => $data["ProjectManager"],
        "StartDate" => $data["StartDate"],
        "EndDate" => $data["EndDate"],
        "TicketNumber" => $data["TicketNumber"],
        "Status" => $data["Status"]
    ],

    "team" => [
        "ID" => $data["team_id"],
        "CustomerManagerName" => $data["CustomerManagerName"],
        "CustomerManagerEmail" => $data["CustomerManagerEmail"],
        "CustomerManagerPhone" => $data["CustomerManagerPhone"],
        "CustomerSupervisorEmail" => $data["CustomerSupervisorEmail"],
        "CustomerSupervisorPhone" => $data["CustomerSupervisorPhone"],
        "TechXpertManagerEmail" => $data["TechXpertManagerEmail"],
        "TechXpertManagerPhone" => $data["TechXpertManagerPhone"],

        "OtherEmail" => $data["OtherEmail"],
        "OtherPhonenumber" => $data["OtherPhonenumber"],
        "TechxpertOtherEmail" => $data["TechxpertOtherEmail"],
        "TechxpertOtherPhonenumber" => $data["TechxpertOtherPhonenumber"]
    ]
];

echo json_encode($response);
