<?php
/**
 * Send FCM (Firebase Cloud Messaging) HTTP v1 notifications.
 * Requires Firebase service account JSON. Uses Firebase JWT for OAuth2 access token.
 */
require_once __DIR__ . '/fcm_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

/**
 * Get Google OAuth2 access token using service account (for FCM).
 * Token valid ~1 hour; consider caching in production.
 *
 * @return string|null Access token or null on failure
 */
function fcm_get_access_token() {
    $path = FCM_SERVICE_ACCOUNT_PATH;
    if (!is_file($path)) {
        return null;
    }
    $json = @file_get_contents($path);
    if ($json === false) {
        return null;
    }
    $key = json_decode($json, true);
    if (!$key || empty($key['client_email']) || empty($key['private_key']) || empty($key['project_id'])) {
        return null;
    }
    $now = time();
    $payload = [
        'iss'   => $key['client_email'],
        'sub'   => $key['client_email'],
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
    ];
    $privateKey = str_replace(['\n', '\r'], ["\n", ''], $key['private_key']);
    try {
        $jwt = JWT::encode($payload, $privateKey, 'RS256');
    } catch (\Exception $e) {
        return null;
    }
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || $response === false) {
        return null;
    }
    $data = json_decode($response, true);
    return isset($data['access_token']) ? $data['access_token'] : null;
}

/**
 * Send FCM notification to a single device token (HTTP v1).
 *
 * @param string $accessToken OAuth2 access token from fcm_get_access_token()
 * @param string $projectId   Firebase project ID (from service account JSON)
 * @param string $token       FCM device token
 * @param string $title       Notification title
 * @param string $body        Notification body
 * @return bool True if sent successfully
 */
function fcm_send_to_token($accessToken, $projectId, $token, $title, $body) {
    $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
    $payload = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'android' => [
                'priority' => 'high',
            ],
            'apns' => [
                'headers' => ['apns-priority' => '10'],
                'payload' => ['aps' => ['sound' => 'default']],
            ],
        ],
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code >= 200 && $code < 300;
}

/**
 * Get Firebase project ID from service account JSON.
 *
 * @return string|null
 */
function fcm_get_project_id() {
    $path = FCM_SERVICE_ACCOUNT_PATH;
    if (!is_file($path)) {
        return null;
    }
    $json = @file_get_contents($path);
    if ($json === false) {
        return null;
    }
    $key = json_decode($json, true);
    return isset($key['project_id']) ? $key['project_id'] : null;
}
