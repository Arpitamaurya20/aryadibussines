<?php
/**
 * Refresh JWT access token.
 * POST JSON: { "refresh_token": "...", "app_code": "HOMECARE", "device_id": "optional" }
 */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth_middleware.php';

app_auth_require_post();
$data = app_auth_read_json_body();

$result = $app_auth_service->refreshAccessToken(
    (string) ($data['refresh_token'] ?? ''),
    (string) ($data['app_code'] ?? ''),
    isset($data['device_id']) ? (string) $data['device_id'] : null
);
app_auth_respond($result);
