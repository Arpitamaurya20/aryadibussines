<?php
/**
 * List all apps the user can access with roles.
 * Authorization: Bearer <token>
 */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth_middleware.php';

$context = app_auth_require_user($app_auth_service);
$userId = (int) $context['user']['ID'];

$result = $app_auth_service->listUserApps($userId);
app_auth_respond($result);
