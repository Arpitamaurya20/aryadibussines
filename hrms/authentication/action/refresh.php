<?php
use Firebase\JWT\JWT;
require_once('../../vendor/autoload.php');
require_once('../../include/autoloader.inc.php');

if (!isset($_COOKIE['refresh_token'])) {
    echo json_encode(["error" => true, "message" => "Expired. Login again."]);
    exit;
}

$refreshToken = $_COOKIE['refresh_token'];

$db = new Dbh();
$conn = $db->_connectodb();
$auth = new Authentication($conn);

$user = $auth->verifyRefreshToken($refreshToken);
if (!$user) {
    echo json_encode(["error" => true, "message" => "Session expired"]);
    exit;
}

$secret = "YOUR_SECRET_KEY";

$newAccessToken = JWT::encode([
    "user_id" => $user['ID'],
    "email"   => $user['Email'],
    "exp"     => time() + 86400
], $secret, 'HS256');

echo json_encode(["error" => false, "access_token" => $newAccessToken]);
?>
