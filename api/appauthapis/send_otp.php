<?php
/**
 * Send OTP to mobile (shared across HOMECARE, MYGATE, PARKING).
 * POST JSON: { "phonenumber": "9876543210", "full_name": "optional" }
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/inc/bootstrap.php';
    require_once __DIR__ . '/inc/auth_middleware.php';
    require_once __DIR__ . '/../../admin/customer/controller/customer_controller.php';

    app_auth_require_post();
    $data = app_auth_read_json_body();

    $phone = app_auth_normalize_phone($data['phonenumber'] ?? '');
    $fullName = isset($data['full_name']) ? trim((string) $data['full_name']) : null;

    $result = $app_auth_service->sendOtp($phone, $fullName);
    if (empty($result['error'])) {
        CreateCustomer($app_auth_conn, $phone);
    }
    app_auth_respond($result);
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
