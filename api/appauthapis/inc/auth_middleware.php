<?php
/**
 * Require valid Bearer access token. Sets $app_auth_user_context on success.
 */
function app_auth_require_user(AppAuthService $service): array
{
    $token = $service->extractBearerToken();
    if ($token === '') {
        app_auth_json_response([
            'error' => true,
            'message' => 'Authorization required. Send Bearer token in Authorization header.',
        ], 401);
    }

    $context = $service->getAuthenticatedContext($token);
    if (!empty($context['error'])) {
        $status = (int) ($context['_http'] ?? 401);
        unset($context['_http']);
        app_auth_json_response($context, $status);
    }

    return $context;
}

function app_auth_respond(array $result): void
{
    $status = (int) ($result['_http'] ?? 200);
    unset($result['_http']);
    app_auth_json_response($result, $status);
}
