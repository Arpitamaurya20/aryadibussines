<?php
/**
 * Logout – invalidates access/refresh tokens.
 * POST JSON: { "refresh_token": "optional" }
 * Header: Authorization: Bearer <access_token> (optional)
 */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth_middleware.php';

app_auth_require_post();
$data = app_auth_read_json_body();

$accessToken = $app_auth_service->extractBearerToken();
$refreshToken = isset($data['refresh_token']) ? (string) $data['refresh_token'] : null;
$deviceId = isset($data['device_id']) ? (string) $data['device_id'] : null;

$result = $app_auth_service->logout($accessToken ?: null, $refreshToken, $deviceId);
app_auth_respond($result);
