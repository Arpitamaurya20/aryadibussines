<?php
/**
 * Include from future protected APIs (e.g. homecare-only endpoints).
 *
 * require_once __DIR__ . '/../appauthapis/inc/bootstrap.php';
 * require_once __DIR__ . '/../appauthapis/inc/auth_middleware.php';
 * require_once __DIR__ . '/../appauthapis/inc/auth_guard.php';
 *
 * $ctx = app_auth_require_user($app_auth_service);
 * app_auth_require_role($ctx, ['CUSTOMER', 'VENDOR']);
 */
function app_auth_require_role(array $context, array $allowedRoleCodes): void
{
    $roleCode = strtoupper((string) ($context['access']['RoleCode'] ?? ''));
    $allowed = array_map('strtoupper', $allowedRoleCodes);

    if (!in_array($roleCode, $allowed, true)) {
        app_auth_json_response([
            'error' => true,
            'message' => 'You do not have permission for this action.',
        ], 403);
    }
}
