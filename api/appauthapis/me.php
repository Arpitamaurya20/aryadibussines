<?php
/**
 * Current logged-in user for the app embedded in JWT.
 * GET or POST with Authorization: Bearer <token>
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/inc/bootstrap.php';
    require_once __DIR__ . '/inc/auth_middleware.php';

    $context = app_auth_require_user($app_auth_service);
    $user = $context['user'];
    $app = $context['app'];
    $access = $context['access'];

    app_auth_respond([
        'error' => false,
        'user' => [
            'user_id' => (int) $user['ID'],
            'full_name' => $user['FullName'],
            'mobile' => $user['MobileNumber'],
            'email' => $user['Email'],
            'profile_image' => $user['ProfileImage'],
            'is_mobile_verified' => $user['IsMobileVerified'],
            'last_login' => $user['LastLogin'] ?? null,
        ],
        'app' => [
            'app_id' => (int) $app['ID'],
            'app_code' => $app['AppCode'],
            'app_name' => $app['AppName'],
            'app_logo' => $app['AppLogo'] ?? null,
        ],
        'role' => [
            'role_id' => (int) $access['RoleID'],
            'role_code' => $access['RoleCode'],
            'role_name' => $access['RoleName'],
        ],
        'profile' => $context['profile'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ],
    ]);
}
