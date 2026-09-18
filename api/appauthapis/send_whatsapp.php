<?php
/**
 * Send WhatsApp template message via chatmybot (new) or Interakt OTP (legacy).
 *
 * POST JSON:
 * {
 *   "phonenumber": "8948975967",
 *   "provider": "chatmybot",
 *   "template_id": "f3539660-8863-4829-bdf5-0de52e2b6f2c",
 *   "body_params": ["235456"],
 *   "button_params": ["Test"],
 *   "otp": "235456"
 * }
 *
 * - provider "chatmybot" (default): uses template_id + body_params (+ optional button_params)
 * - provider "interakt": sends login_otp using "otp" field (legacy)
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/inc/bootstrap.php';
    require_once __DIR__ . '/inc/auth_middleware.php';

    app_auth_require_post();
    $data = app_auth_read_json_body();

    $phone = app_auth_normalize_phone($data['phonenumber'] ?? '');
    if (!preg_match('/^\d{10}$/', $phone)) {
        app_auth_respond(['error' => true, 'message' => 'Invalid mobile number. Enter 10-digit Indian mobile.']);
    }

    $waConfig = $app_auth_config['whatsapp'] ?? [];
    $provider = strtolower(trim((string) ($data['provider'] ?? ($waConfig['provider'] ?? 'chatmybot'))));
    $whatsapp = new Whatsapp();

    if ($provider === 'interakt') {
        $otp = trim((string) ($data['otp'] ?? ($data['body_params'][0] ?? '')));
        if ($otp === '') {
            app_auth_respond(['error' => true, 'message' => 'otp is required for interakt provider.']);
        }
        $raw = $whatsapp->sendOTPWhatsAppMessage(['phonenumber' => $phone, 'otp' => $otp]);
        app_auth_respond([
            'error' => false,
            'message' => 'WhatsApp message sent via Interakt.',
            'provider' => 'interakt',
            'response' => json_decode((string) $raw, true) ?? $raw,
        ]);
    }

    $cbConfig = is_array($waConfig['chatmybot'] ?? null) ? $waConfig['chatmybot'] : [];
    $templateId = trim((string) ($data['template_id'] ?? ($cbConfig['otp_template_id'] ?? '')));
    $bodyParams = $data['body_params'] ?? null;
    if (!is_array($bodyParams)) {
        $otp = trim((string) ($data['otp'] ?? ''));
        $bodyParams = $otp !== '' ? [$otp] : [];
    }
    if (!$bodyParams) {
        app_auth_respond(['error' => true, 'message' => 'body_params or otp is required.']);
    }

    $buttonParams = null;
    if (array_key_exists('button_params', $data) && is_array($data['button_params'])) {
        $buttonParams = $data['button_params'];
    } elseif (!empty($cbConfig['otp_include_button'])) {
        $buttonText = isset($cbConfig['otp_button_param']) && $cbConfig['otp_button_param'] !== ''
            ? (string) $cbConfig['otp_button_param']
            : (string) ($bodyParams[0] ?? '');
        $buttonParams = $buttonText !== '' ? [$buttonText] : null;
    }

    $result = $whatsapp->sendChatmybotTemplate([
        'endpoint' => $cbConfig['endpoint'] ?? '',
        'access_token' => $cbConfig['access_token'] ?? '',
        'auth_scheme' => $cbConfig['auth_scheme'] ?? 'accessToken',
        'template_id' => $templateId,
        'phone' => $phone,
        'body_params' => $bodyParams,
        'button_params' => $buttonParams,
        'button_index' => (string) ($data['button_index'] ?? ($cbConfig['button_index'] ?? '0')),
    ]);

    if (empty($result['ok'])) {
        $payload = [
            'error' => true,
            'message' => $result['error'] ?: 'Failed to send WhatsApp message.',
            'provider' => 'chatmybot',
            'http_code' => (int) ($result['http_code'] ?? 0),
        ];
        if (!empty($app_auth_config['debug'])) {
            $payload['response'] = $result['response'] ?? null;
        }
        app_auth_respond($payload);
    }

    app_auth_respond([
        'error' => false,
        'message' => 'WhatsApp message sent successfully.',
        'provider' => 'chatmybot',
        'message_ids' => $result['message_ids'] ?? [],
        'http_code' => (int) ($result['http_code'] ?? 0),
        'response' => !empty($app_auth_config['debug']) ? ($result['response'] ?? null) : null,
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
