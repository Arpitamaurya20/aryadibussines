<?php
/**
 * Verify OTP and login for a specific app with role.
 * POST JSON: {
 *   "phonenumber": "9876543210",
 *   "otp": "123456",
 *   "app_code": "HOMECARE",
 *   "device_type": "android",
 *   "device_name": "Pixel 7",
 *   "device_id": "uuid",
 *   "firebase_token": "optional"
 * }
 */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth_middleware.php';

app_auth_require_post();
$data = app_auth_read_json_body();

$result = $app_auth_service->verifyOtpAndLogin($data);
app_auth_respond($result);
