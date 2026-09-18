<?php
require_once('../../vendor/autoload.php');
require_once('../../include/autoloader.inc.php');
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$email = $_POST['email'];
$password = $_POST['password'];

$db = new Dbh();
$conn = $db->_connectodb();
$auth = new Authentication($conn);

// Get user record
$user = $auth->getUserByEmail($email);

// Verify MD5 passwords (your old system uses md5)
if (!$user || md5($password) !== $user['Password']) {
    echo json_encode(["error" => true, "message" => "Invalid email or password"]);
    exit;
}

$secret = "zeltologicabcdefghijklmnopqrstuvwxyz";

// -----------------------------
// Generate Access Token (1 Day)
// -----------------------------
$payload = [
    "user_id" => $user['ID'],
    "email"   => $email,
    "user_type" => $user['UserType'],
    "exp"     => time() + 86400
];

$accessToken = JWT::encode($payload, $secret, 'HS256');

// -----------------------------
// Generate Refresh Token (90 Days)
// -----------------------------
$refreshToken = bin2hex(random_bytes(40));
$refreshExpiry = date("Y-m-d H:i:s", time() + (86400 * 90));

$auth->saveRefreshToken($user['ID'], $refreshToken, $refreshExpiry);

setcookie("refresh_token", $refreshToken, [
    "expires" => time() + (86400 * 90),
    "path" => "/",  // IMPORTANT
    "httponly" => true,
    "secure" => false,
    "samesite" => "Lax"  // allows logout to receive cookie
]);


// -----------------------------
// Create PHP session for Dashboard
// -----------------------------
session_start();
$_SESSION['UserID'] = $user['ID'];
$_SESSION['pp_UserType'] = $user['UserType'];
$_SESSION['pp_email'] = $email;
$_SESSION['access_token'] = $accessToken;


echo json_encode([
    "error" => false,
    "message" => "Login successful",
    "access_token" => $accessToken,
    "UserType" => $user['UserType']
]);
?>
