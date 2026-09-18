<?php
/**
 * Switch to another app using existing session (no OTP) if user has access.
 * POST JSON: { "app_code": "PARKING", "device_id": "optional" }
 * Authorization: Bearer <token>
 */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth_middleware.php';

app_auth_require_post();
$context = app_auth_require_user($app_auth_service);
$data = app_auth_read_json_body();

$result = $app_auth_service->switchApp(
    (int) $context['user']['ID'],
    (string) $context['user']['MobileNumber'],
    (string) ($data['app_code'] ?? ''),
    $data
);
app_auth_respond($result);
