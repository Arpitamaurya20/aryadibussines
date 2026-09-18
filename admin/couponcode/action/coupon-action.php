<?php
@session_start();
require_once('../../includes/autoloader.inc.php');

$core = new Core();
$core->setTimeZone();

$response = array(
    "error" => true,
    "message" => "Invalid request"
);

if (!isset($_SESSION['pb_username'])) {
    echo json_encode([
        "error" => true,
        "message" => "Session expired"
    ]);
    exit;
}

$dbh  = new Dbh();
$conn = $dbh->_connectodb();

$action = $_POST['action'] ?? '';

$CreatedBy   = $_SESSION['pb_username'];
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');

$table = "coupons_code";

/**
 * =========================
 * ADD / UPDATE COUPON
 * =========================
 */
if ($action === "save") {

    $id          = $_POST['id'] ?? '';
    $CouponName  = trim($_POST['CouponName']);
    $Discount    = trim($_POST['Discount']);
    $Type        = trim($_POST['Type']);

    if ($CouponName == "" || $Discount == "" || $Type == "") {
        $response['message'] = "All fields are required";
        echo json_encode($response);
        exit;
    }

    if ($id == "") {
        // ---------- INSERT ----------
        $data = [
            "CouponName"  => $CouponName,
            "Discount"    => $Discount,
            "Type"        => $Type,
            "IsActive"    => 1,
            "CreatedBy"   => $CreatedBy,
            "CreatedDate"=> $CreatedDate,
            "CreatedTime"=> $CreatedTime
        ];

        $response = $core->_InsertTableRecords_prepare($conn, $table, $data);
    } else {
        // ---------- UPDATE ----------
        $data = [
            "CouponName" => $CouponName,
            "Discount"   => $Discount,
            "Type"       => $Type
        ];

        $where = [
            "ID" => $id
        ];

        $response = $core->_UpdateTableRecords_prepare($conn, $table, $data, $where);
    }
}

/**
 * =========================
 * GET SINGLE COUPON
 * =========================
 */
if ($action === "get") {

    $id = intval($_POST['id']);

    $row = $core->_getTableDetails($conn, $table, " WHERE ID = $id");

    if (!empty($row)) {
        $response['error'] = false;
        $response['data']  = $row;
        $response['message'] = "Success";
    } else {
        $response['message'] = "Coupon not found";
    }
}

/**
 * =========================
 * DELETE COUPON (SOFT)
 * =========================
 */
if ($action === "delete") {

    $id = intval($_POST['id']);

    $success = $core->delete_identity_filter_disable(
        $conn,
        $table,
        " WHERE ID = $id"
    );

    if ($success) {
        $response['error'] = false;
        $response['message'] = "Coupon deleted";
    } else {
        $response['message'] = "Delete failed";
    }
}

/**
 * =========================
 * TOGGLE ACTIVE / INACTIVE
 * =========================
 */
if ($action === "status") {

    $id     = intval($_POST['id']);
    $status = intval($_POST['status']); // 1 or 0

    $data = [
        "IsActive" => $status
    ];

    $where = [
        "ID" => $id
    ];

    $response = $core->_UpdateTableRecords_prepare($conn, $table, $data, $where);
}

echo json_encode($response);
