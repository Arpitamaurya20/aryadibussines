<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
require_once('../include/autoloader.inc.php');
$db = new Dbh();
$conn = $db->_connectodb();

// --------------------------------------
// 1. Delete refresh token from database
// --------------------------------------
if (isset($_COOKIE['refresh_token'])) {
    $refresh = $_COOKIE['refresh_token'];
    $stmt = $conn->prepare("UPDATE users_hrms SET refresh_token=NULL, refresh_expiry=NULL WHERE refresh_token=?");
    $stmt->bind_param("s", $refresh);
    $stmt->execute();
}

// --------------------------------------
// 2. Delete refresh_token cookie
// --------------------------------------
setcookie("refresh_token", "", [
    "expires" => time() - 3600,
    "httponly" => true,
    "secure" => false,
    "samesite" => "Strict"
]);

// --------------------------------------
// 3. Destroy php session completely
// --------------------------------------
session_unset();
session_destroy();

// --------------------------------------
// 4. Response
// --------------------------------------
echo json_encode([
    "error" => false,
    "message" => "Logout successful"
]);
?>
