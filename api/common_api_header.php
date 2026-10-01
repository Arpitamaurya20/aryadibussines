<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

date_default_timezone_set('Asia/Kolkata');

// --- JWT Authentication Implementation ---
require_once __DIR__.'/../admin/vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

define('JWT_SECRET_KEY', 'aryadibussines_super_secret_key_2026'); 

$whitelist_endpoints = [
    'login.php',
    'generate_otp.php',
    'verify_otp.php',
    'attendance_login.php',
    'corporate_login.php',
    'get_ppm_billing_status.php',
    'get_employee_info.php',
    'get_employee_attendance_status.php'
];

$current_script = basename($_SERVER['SCRIPT_NAME']);

if (!in_array($current_script, $whitelist_endpoints)) {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if(empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if ($authHeader) {
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
            try {
                $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
                $GLOBALS['jwt_user_data'] = $decoded->data;
            } catch (\Firebase\JWT\ExpiredException $e) {
                http_response_code(401);
                echo json_encode(["error" => true, "message" => "Token has expired."]);
                exit();
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(["error" => true, "message" => "Access denied. Invalid token."]);
                exit();
            }
        } else {
            http_response_code(401);
            echo json_encode(["error" => true, "message" => "Access denied. Invalid token format."]);
            exit();
        }
    } else {
        http_response_code(401);
        echo json_encode(["error" => true, "message" => "Access denied. Authorization header missing."]);
        exit();
    }
}
?>