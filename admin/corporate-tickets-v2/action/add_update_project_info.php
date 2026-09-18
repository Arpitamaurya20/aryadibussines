<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
include('../../branch/controller/branch_controller.php');
require_once('../../includes/autoloader.inc.php');

setTimeZone();
$conn = _connectodb();
$core = new Core();

session_start();

$response = ['error' => true, 'message' => 'Something went wrong'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // BASIC PROJECT DATA
    $TicketID       = cleantext($_POST['TicketID']);
    $ProjectName    = cleantext($_POST['ProjectName']);
    $StartDate      = cleantext($_POST['StartDate']);
    $EndDate        = cleantext($_POST['EndDate']);
    $TicketNumber   = cleantext($_POST['TicketNumber']);

    // CUSTOMER SIDE
    $CustomerManagerName       = cleantext($_POST['CustomerManagerName']);
    $CustomerManagerEmail      = cleantext($_POST['CustomerManagerEmail']);
    $CustomerManagerPhone      = cleantext($_POST['CustomerManagerPhone']);
    $CustomerSupervisorEmail   = cleantext($_POST['CustomerSupervisorEmail']);
    $CustomerSupervisorPhone   = cleantext($_POST['CustomerSupervisorPhone']);

    // TECHXPERT SIDE
    $TechXpertManagerEmail       = cleantext($_POST['TechXpertManagerEmail']);
    $TechXpertManagerPhone       = cleantext($_POST['TechXpertManagerPhone']);
    $TechxpertOtherEmail         = cleantext($_POST['TechxpertOtherEmail'] ?? '');
    $TechxpertOtherPhonenumber   = cleantext($_POST['TechxpertOtherPhonenumber'] ?? '');

    // OTHER CONTACTS
    $OtherEmail        = cleantext($_POST['OtherEmail'] ?? '');
    $OtherPhonenumber  = cleantext($_POST['OtherPhonenumber'] ?? '');

    $CreatedBy  = $_SESSION['pb_username'] ?? 'system';
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');

    // =============================
    // CHECK IF PROJECT EXISTS
    // =============================
    $checkProject = $core->_getSQLDetails($conn, "SELECT * FROM projects WHERE TicketID='$TicketID' LIMIT 1");

    // =============================
    // UPSERT → PROJECT TABLE
    // =============================
    if (!empty($checkProject)) {

        // UPDATE
        $updateProject = [
            "ProjectName" => $ProjectName,
            "StartDate" => $StartDate,
            "EndDate" => $EndDate,
            "TicketNumber" => $TicketNumber
        ];

        $whereProject = ["TicketID" => $TicketID];

        $projectResult = $core->_UpdateTableRecords_prepare(
            $conn, 
            "projects", 
            $updateProject, 
            $whereProject
        );

        $ProjectID = $checkProject["ID"];

    } else {

        // INSERT
        $insertProject = [
            "TicketID" => $TicketID,
            "ProjectName" => $ProjectName,
            "StartDate" => $StartDate,
            "EndDate" => $EndDate,
            "TicketNumber" => $TicketNumber,
            "CreatedBy" => $CreatedBy,
            "CreatedDate" => $CreatedDate,
            "CreatedTime" => $CreatedTime,
            "Status" => "Active",
            "IsActive" => 1
        ];

        $projectResult = $core->_InsertTableRecords_prepare(
            $conn, 
            "projects", 
            $insertProject
        );

        $ProjectID = $projectResult["last_insert_id"];
    }

    if ($projectResult["error"]) {
        echo json_encode([
            "error" => true,
            "message" => "Project save/update failed: " . $projectResult["message"]
        ]);
        exit;
    }

    // =============================
    // CHECK IF TEAM EXISTS
    // =============================
    $checkTeam = $core->_getSQLDetails($conn, "SELECT * FROM project_team_details WHERE TicketID='$TicketID' LIMIT 1");

    // =============================
    // UPSERT → TEAM TABLE
    // =============================
    if (!empty($checkTeam)) {

        // UPDATE TEAM
        $updateTeam = [
            "CustomerManagerName" => $CustomerManagerName,
            "CustomerManagerEmail" => $CustomerManagerEmail,
            "CustomerManagerPhone" => $CustomerManagerPhone,
            "CustomerSupervisorEmail" => $CustomerSupervisorEmail,
            "CustomerSupervisorPhone" => $CustomerSupervisorPhone,
            "OtherEmail" => $OtherEmail,
            "OtherPhonenumber" => $OtherPhonenumber,
            "TechXpertManagerEmail" => $TechXpertManagerEmail,
            "TechXpertManagerPhone" => $TechXpertManagerPhone,
            "TechxpertOtherEmail" => $TechxpertOtherEmail,
            "TechxpertOtherPhonenumber" => $TechxpertOtherPhonenumber
        ];

        $whereTeam = ["TicketID" => $TicketID];

        $teamResult = $core->_UpdateTableRecords_prepare(
            $conn, 
            "project_team_details", 
            $updateTeam, 
            $whereTeam
        );

    } else {

        // INSERT TEAM
        $insertTeam = [
            "ProjectID" => $ProjectID,
            "TicketID" => $TicketID,
            "CustomerManagerName" => $CustomerManagerName,
            "CustomerManagerEmail" => $CustomerManagerEmail,
            "CustomerManagerPhone" => $CustomerManagerPhone,
            "CustomerSupervisorEmail" => $CustomerSupervisorEmail,
            "CustomerSupervisorPhone" => $CustomerSupervisorPhone,
            "OtherEmail" => $OtherEmail,
            "OtherPhonenumber" => $OtherPhonenumber,
            "TechXpertManagerEmail" => $TechXpertManagerEmail,
            "TechXpertManagerPhone" => $TechXpertManagerPhone,
            "TechxpertOtherEmail" => $TechxpertOtherEmail,
            "TechxpertOtherPhonenumber" => $TechxpertOtherPhonenumber,
            "CreatedBy" => $CreatedBy,
            "CreatedDate" => $CreatedDate,
            "CreatedTime" => $CreatedTime,
            "IsActive" => 1
        ];

        $teamResult = $core->_InsertTableRecords_prepare(
            $conn, 
            "project_team_details", 
            $insertTeam
        );
    }

    if ($teamResult["error"]) {
        echo json_encode([
            "error" => true,
            "message" => "Team save/update failed: " . $teamResult["message"]
        ]);
        exit;
    }

    // =============================
    // SUCCESS RESPONSE
    // =============================
    echo json_encode([
        "error" => false,
        "message" => "Project & Team saved/updated successfully",
        "ProjectID" => $ProjectID
    ]);
}
?>
