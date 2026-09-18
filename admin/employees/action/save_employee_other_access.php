<?php
include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');

header('Content-Type: application/json');
@session_start();
setTimeZone();
$conn = _connectodb();

$EmployeeID = intval($_POST['EmployeeID'] ?? 0);
$CreatedBy  = $_SESSION['pb_username'] ?? '';

if($EmployeeID == 0)
{
    echo json_encode([
        "status" => "error",
        "message" => "Invalid Employee"
    ]);
    exit;
}

/* -------------------------------------------------------
   1️⃣ DELETE OLD ACCESS USING COMMON FUNCTION
------------------------------------------------------- */

delete_identity_filter(
    $conn,
    'user_quation_access',
    "WHERE EmployeeID = '$EmployeeID'"
);


/* -------------------------------------------------------
   2️⃣ STATE APPROVAL INSERT
------------------------------------------------------- */

if(isset($_POST['StateApprovalMain']) && !empty($_POST['StateName']))
{
    foreach($_POST['StateName'] as $state)
    {
        $state = cleantext($state);

        $data = [
            "EmployeeID"        => $EmployeeID,
            "StateName"         => $state,
            "IsApprovedState"   => 1,
            "IsApprovedFinance" => 0,
            "CreatedDate"       => date("Y-m-d H:i:s"),
            "CreatedBy"         => $CreatedBy,
            "IsActive"          => 1
        ];

        _InsertTableRecords_prepare($conn, "user_quation_access", $data);
    }
}


/* -------------------------------------------------------
   3️⃣ FINANCE APPROVAL (GLOBAL)
------------------------------------------------------- */

if(isset($_POST['FinanceApproval']))
{
    $data = [
        "EmployeeID"        => $EmployeeID,
        "StateName"         => "ALL",
        "IsApprovedState"   => 0,
        "IsApprovedFinance" => 1,
        "CreatedDate"       => date("Y-m-d H:i:s"),
        "CreatedBy"         => $CreatedBy,
        "IsActive"          => 1
    ];

    _InsertTableRecords_prepare($conn, "user_quation_access", $data);
}


/* -------------------------------------------------------
   4️⃣ SUCCESS RESPONSE
------------------------------------------------------- */

echo json_encode([
    "status" => "success",
    "message" => "Access updated successfully"
]);

exit;
