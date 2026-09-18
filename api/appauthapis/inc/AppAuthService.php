<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AppAuthService
{
    /** @var mysqli */
    private $conn;

    /** @var array */
    private $config;

    public function __construct(mysqli $conn, array $config)
    {
        $this->conn = $conn;
        $this->config = $config;
    }

    public function sendOtp(string $mobile, ?string $fullName = null): array
    {
        if (!preg_match('/^\d{10}$/', $mobile)) {
            return $this->fail('Invalid mobile number. Enter 10-digit Indian mobile.');
        }

        $otp = $this->generateOtp();
        $expiry = date('Y-m-d H:i:s', time() + ((int) $this->config['otp_ttl_minutes'] * 60));

        $user = $this->findUserByMobile($mobile);
        if ($user) {
            $stmt = $this->conn->prepare(
                'UPDATE app_users SET Otp = ?, OtpExpiry = ?, UpdatedDate = NOW() WHERE ID = ? AND IsActive = 1'
            );
            if (!$stmt) {
                return $this->fail('Unable to process request. ' . $this->conn->error);
            }
            $userId = (int) $user['ID'];
            $stmt->bind_param('ssi', $otp, $expiry, $userId);
            if (!$stmt->execute()) {
                $dbError = $stmt->error;
                $stmt->close();
                return $this->fail('Unable to save OTP. ' . $dbError);
            }
            $stmt->close();
        } else {
            $name = ($fullName !== null && $fullName !== '') ? $fullName : '';
            $stmt = $this->conn->prepare(
                'INSERT INTO app_users (MobileNumber, FullName, Otp, OtpExpiry, IsMobileVerified, IsActive)
                 VALUES (?, ?, ?, ?, \'No\', 1)'
            );
            if (!$stmt) {
                return $this->fail('Unable to process request. ' . $this->conn->error);
            }
            $stmt->bind_param('ssss', $mobile, $name, $otp, $expiry);
            if (!$stmt->execute()) {
                $dbError = $stmt->error;
                $stmt->close();
                return $this->fail('Unable to register mobile number. ' . $dbError);
            }
            $stmt->close();
        }

        $whatsappResult = $this->dispatchOtp($mobile, $otp);

        $response = [
            'error' => false,
            'message' => 'OTP sent successfully on WhatsApp.',
            'otp_expires_in_minutes' => (int) $this->config['otp_ttl_minutes'],
        ];

        if (!empty($this->config['debug'])) {
            $response['debug_whatsapp'] = $whatsappResult;
        }

        return $response;
    }

    public function verifyOtpAndLogin(array $params): array
    {
        $mobile = app_auth_normalize_phone($params['phonenumber'] ?? '');
        $otp = trim((string) ($params['otp'] ?? ''));
        $appCode = strtoupper(trim((string) ($params['app_code'] ?? '')));

        if (!preg_match('/^\d{10}$/', $mobile)) {
            return $this->fail('Invalid mobile number.');
        }
        if ($otp === '') {
            return $this->fail('OTP is required.');
        }
        if (!in_array($appCode, $this->config['valid_app_codes'], true)) {
            return $this->fail('Invalid app_code. Use HOMECARE, MYGATE, or PARKING.');
        }

        $user = $this->findUserByMobile($mobile);
        if (!$user) {
            return $this->fail('User not found. Request OTP first.');
        }
        if ((int) ($user['IsActive'] ?? 0) !== 1) {
            return $this->fail('Account is inactive. Contact support.');
        }

        if (!$this->isOtpValid($user, $otp)) {
            return $this->fail('Invalid or expired OTP.');
        }

        $userId = (int) $user['ID'];
        $this->clearOtp($userId);
        $this->markMobileVerified($userId);

        $app = $this->getAppByCode($appCode);
        if (!$app) {
            return $this->fail('Application not configured.');
        }

        $access = $this->resolveUserAppAccess($userId, (int) $app['ID'], $appCode);
        if (!$access) {
            return $this->fail('You do not have access to this application.');
        }

        $deviceType = isset($params['device_type']) ? substr(trim((string) $params['device_type']), 0, 50) : null;
        $deviceName = isset($params['device_name']) ? substr(trim((string) $params['device_name']), 0, 255) : null;
        $deviceId = isset($params['device_id']) ? substr(trim((string) $params['device_id']), 0, 255) : null;
        $firebaseToken = isset($params['firebase_token']) ? trim((string) $params['firebase_token']) : null;
        $ip = app_auth_client_ip();

        $tokens = $this->issueTokens(
            $userId,
            $mobile,
            $app,
            $access,
            $deviceType,
            $deviceName,
            $deviceId,
            $firebaseToken,
            $ip
        );

        $profile = $this->getAppProfile($userId, $appCode);

        return [
            'error' => false,
            'message' => 'Login successful.',
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => (int) $this->config['access_token_ttl_seconds'],
            'user' => [
                'user_id' => $userId,
                'full_name' => $user['FullName'],
                'mobile' => $mobile,
                'email' => $user['Email'],
                'profile_image' => $user['ProfileImage'],
                'is_mobile_verified' => 'Yes',
            ],
            'app' => [
                'app_id' => (int) $app['ID'],
                'app_code' => $app['AppCode'],
                'app_name' => $app['AppName'],
                'app_logo' => $app['AppLogo'],
            ],
            'role' => [
                'role_id' => (int) $access['RoleID'],
                'role_code' => $access['RoleCode'],
                'role_name' => $access['RoleName'],
            ],
            'profile' => $profile,
        ];
    }

    public function refreshAccessToken(string $refreshToken, string $appCode, ?string $deviceId = null): array
    {
        $refreshToken = trim($refreshToken);
        $appCode = strtoupper(trim($appCode));

        if ($refreshToken === '' || strlen($refreshToken) > 512) {
            return $this->fail('Invalid refresh token.');
        }
        if (!in_array($appCode, $this->config['valid_app_codes'], true)) {
            return $this->fail('Invalid app_code.');
        }

        $stmt = $this->conn->prepare(
            "SELECT ut.ID AS TokenRowID, ut.UserID, ut.DeviceId, ut.ExpiryDate,
                    u.MobileNumber, u.IsActive AS UserActive
             FROM user_tokens ut
             INNER JOIN app_users u ON u.ID = ut.UserID
             WHERE ut.AuthToken = ? AND ut.DeviceType = 'refresh' AND ut.IsActive = 1
             LIMIT 1"
        );
        if (!$stmt) {
            return $this->fail('Service unavailable.');
        }
        $stmt->bind_param('s', $refreshToken);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row || (int) $row['UserActive'] !== 1) {
            return $this->fail('Invalid or expired refresh token. Please login again.');
        }
        if ($row['ExpiryDate'] !== null && strtotime($row['ExpiryDate']) < time()) {
            $this->deactivateTokenRow((int) $row['TokenRowID']);
            return $this->fail('Refresh token expired. Please login again.');
        }
        if ($deviceId !== null && $deviceId !== '' && !empty($row['DeviceId']) && $row['DeviceId'] !== $deviceId) {
            return $this->fail('Device mismatch.');
        }

        $userId = (int) $row['UserID'];
        $mobile = $row['MobileNumber'];
        $app = $this->getAppByCode($appCode);
        if (!$app) {
            return $this->fail('Application not found.');
        }

        $access = $this->getUserAppAccessRow($userId, (int) $app['ID']);
        if (!$access) {
            return $this->fail('You do not have access to this application.');
        }

        $this->deactivateUserDeviceTokens($userId, $row['DeviceId'] ?? null);

        $tokens = $this->issueTokens(
            $userId,
            $mobile,
            $app,
            $access,
            null,
            null,
            $row['DeviceId'] ?? null,
            null,
            app_auth_client_ip()
        );

        return [
            'error' => false,
            'message' => 'Token refreshed.',
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => (int) $this->config['access_token_ttl_seconds'],
            'app' => [
                'app_id' => (int) $app['ID'],
                'app_code' => $app['AppCode'],
                'app_name' => $app['AppName'],
            ],
            'role' => [
                'role_id' => (int) $access['RoleID'],
                'role_code' => $access['RoleCode'],
                'role_name' => $access['RoleName'],
            ],
        ];
    }

    public function logout(?string $accessToken, ?string $refreshToken, ?string $deviceId = null): array
    {
        if ($accessToken !== null && $accessToken !== '') {
            $this->deactivateTokenByValue($accessToken);
        }
        if ($refreshToken !== null && $refreshToken !== '') {
            $this->deactivateTokenByValue($refreshToken);
        }
        if ($deviceId !== null && $deviceId !== '') {
            $stmt = $this->conn->prepare(
                'UPDATE user_tokens SET IsActive = 0 WHERE DeviceId = ? AND IsActive = 1'
            );
            if ($stmt) {
                $stmt->bind_param('s', $deviceId);
                $stmt->execute();
                $stmt->close();
            }
        }

        return ['error' => false, 'message' => 'Logged out successfully.'];
    }

    public function getAuthenticatedContext(string $accessToken): array
    {
        $payload = $this->decodeAccessToken($accessToken);
        if (!$payload) {
            return $this->fail('Invalid or expired token.', 401);
        }

        $userId = (int) ($payload['data']['user_id'] ?? 0);
        $appCode = strtoupper((string) ($payload['data']['app_code'] ?? ''));

        if ($userId < 1 || $appCode === '') {
            return $this->fail('Invalid token payload.', 401);
        }

        if (!$this->isStoredAccessTokenActive($accessToken, $userId)) {
            return $this->fail('Session expired. Please login again.', 401);
        }

        $user = $this->findUserById($userId);
        if (!$user || (int) $user['IsActive'] !== 1) {
            return $this->fail('Account inactive.', 401);
        }

        $app = $this->getAppByCode($appCode);
        $access = $app ? $this->getUserAppAccessRow($userId, (int) $app['ID']) : null;
        if (!$app || !$access) {
            return $this->fail('App access not found.', 403);
        }

        return [
            'error' => false,
            'user' => $user,
            'app' => $app,
            'access' => $access,
            'payload' => $payload,
            'profile' => $this->getAppProfile($userId, $appCode),
        ];
    }

    public function switchApp(int $userId, string $mobile, string $appCode, array $params = []): array
    {
        $appCode = strtoupper(trim($appCode));
        if (!in_array($appCode, $this->config['valid_app_codes'], true)) {
            return $this->fail('Invalid app_code.');
        }

        $app = $this->getAppByCode($appCode);
        if (!$app) {
            return $this->fail('Application not found.');
        }

        $access = $this->resolveUserAppAccess($userId, (int) $app['ID'], $appCode);
        if (!$access) {
            return $this->fail('You do not have access to this application.');
        }

        $tokens = $this->issueTokens(
            $userId,
            $mobile,
            $app,
            $access,
            isset($params['device_type']) ? (string) $params['device_type'] : null,
            isset($params['device_name']) ? (string) $params['device_name'] : null,
            isset($params['device_id']) ? (string) $params['device_id'] : null,
            isset($params['firebase_token']) ? (string) $params['firebase_token'] : null,
            app_auth_client_ip()
        );

        return [
            'error' => false,
            'message' => 'Switched application.',
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => (int) $this->config['access_token_ttl_seconds'],
            'app' => [
                'app_id' => (int) $app['ID'],
                'app_code' => $app['AppCode'],
                'app_name' => $app['AppName'],
            ],
            'role' => [
                'role_id' => (int) $access['RoleID'],
                'role_code' => $access['RoleCode'],
                'role_name' => $access['RoleName'],
            ],
            'profile' => $this->getAppProfile($userId, $appCode),
        ];
    }

    public function listUserApps(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.ID AS app_id, a.AppCode AS app_code, a.AppName AS app_name, a.AppLogo AS app_logo,
                    ar.ID AS role_id, ar.RoleCode AS role_code, ar.RoleName AS role_name,
                    ua.IsDefaultApp AS is_default_app, ua.IsEnabled AS is_enabled
             FROM user_apps ua
             INNER JOIN apps a ON a.ID = ua.AppID AND a.IsActive = 1
             INNER JOIN app_roles ar ON ar.ID = ua.RoleID AND ar.IsActive = 1
             WHERE ua.UserID = ? AND ua.IsEnabled = 1
             ORDER BY ua.IsDefaultApp DESC, a.AppName ASC"
        );
        if (!$stmt) {
            return $this->fail('Unable to load applications.');
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $apps = [];
        while ($row = $res->fetch_assoc()) {
            $apps[] = [
                'app_id' => (int) $row['app_id'],
                'app_code' => $row['app_code'],
                'app_name' => $row['app_name'],
                'app_logo' => $row['app_logo'],
                'role_id' => (int) $row['role_id'],
                'role_code' => $row['role_code'],
                'role_name' => $row['role_name'],
                'is_default_app' => (int) $row['is_default_app'] === 1,
            ];
        }
        $stmt->close();

        return ['error' => false, 'apps' => $apps];
    }

    public function extractBearerToken(): string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($authHeader === '' && function_exists('getallheaders')) {
            foreach (getallheaders() ?: [] as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    $authHeader = $value;
                    break;
                }
            }
        }
        if ($authHeader === '' && !empty($_SERVER['HTTP_X_ACCESS_TOKEN'])) {
            $authHeader = 'Bearer ' . trim($_SERVER['HTTP_X_ACCESS_TOKEN']);
        }
        if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $authHeader, $m)) {
            return $m[1];
        }
        return '';
    }

    private function generateOtp(): string
    {
        $len = (int) $this->config['otp_length'];
        $max = (int) str_repeat('9', $len);
        $min = (int) str_repeat('1', $len - 1);
        return str_pad((string) random_int($min, $max), $len, '0', STR_PAD_LEFT);
    }

    private function dispatchOtp(string $mobile, string $otp): array
    {
        $waConfig = $this->config['whatsapp'] ?? [];
        $provider = strtolower((string) ($waConfig['provider'] ?? 'chatmybot'));

        try {
            $whatsapp = new Whatsapp();

            if ($provider === 'interakt') {
                $whatsapp->sendOTPWhatsAppMessage(['phonenumber' => $mobile, 'otp' => $otp]);
                return ['sent' => true, 'channel' => 'whatsapp', 'provider' => 'interakt'];
            }

            $cbConfig = is_array($waConfig['chatmybot'] ?? null) ? $waConfig['chatmybot'] : [];
            $result = $whatsapp->sendOtpViaChatmybot(
                ['phonenumber' => $mobile, 'otp' => $otp],
                $cbConfig
            );

            return [
                'sent' => !empty($result['ok']),
                'channel' => 'whatsapp',
                'provider' => 'chatmybot',
                'http_code' => (int) ($result['http_code'] ?? 0),
                'message_ids' => $result['message_ids'] ?? [],
                'error' => !empty($result['ok']) ? null : ($result['error'] ?? 'WhatsApp send failed'),
                'response' => $result['response'] ?? null,
            ];
        } catch (Throwable $e) {
            return [
                'sent' => false,
                'channel' => 'whatsapp',
                'provider' => $provider,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function isOtpValid(array $user, string $otp): bool
    {
        $stored = (string) ($user['Otp'] ?? '');
        $expiry = $user['OtpExpiry'] ?? null;
        if ($stored === '' || $expiry === null) {
            return false;
        }
        if (!hash_equals($stored, $otp)) {
            return false;
        }
        return strtotime($expiry) >= time();
    }

    private function clearOtp(int $userId): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE app_users SET Otp = NULL, OtpExpiry = NULL, LastLogin = NOW(), UpdatedDate = NOW() WHERE ID = ?'
        );
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function markMobileVerified(int $userId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE app_users SET IsMobileVerified = 'Yes', UpdatedDate = NOW() WHERE ID = ?"
        );
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function resolveUserAppAccess(int $userId, int $appId, string $appCode): ?array
    {
        $access = $this->getUserAppAccessRow($userId, $appId);
        if ($access) {
            return $access;
        }

        $defaultRoleCode = $this->config['default_role_by_app'][$appCode] ?? 'CUSTOMER';
        $role = $this->getRoleByAppAndCode($appId, $defaultRoleCode);
        if (!$role) {
            return null;
        }

        $roleId = (int) $role['ID'];
        $isDefault = $this->userHasAnyApp($userId) ? 0 : 1;

        $stmt = $this->conn->prepare(
            'INSERT INTO user_apps (UserID, AppID, RoleID, IsDefaultApp, IsEnabled) VALUES (?, ?, ?, ?, 1)'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('iiii', $userId, $appId, $roleId, $isDefault);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        $stmt->close();

        return $this->getUserAppAccessRow($userId, $appId);
    }

    private function getUserAppAccessRow(int $userId, int $appId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT ua.ID, ua.UserID, ua.AppID, ua.RoleID, ua.IsDefaultApp, ua.IsEnabled,
                    ar.RoleCode, ar.RoleName, ar.Description AS RoleDescription
             FROM user_apps ua
             INNER JOIN app_roles ar ON ar.ID = ua.RoleID AND ar.IsActive = 1
             WHERE ua.UserID = ? AND ua.AppID = ? AND ua.IsEnabled = 1
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ii', $userId, $appId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    private function issueTokens(
        int $userId,
        string $mobile,
        array $app,
        array $access,
        ?string $deviceType,
        ?string $deviceName,
        ?string $deviceId,
        ?string $firebaseToken,
        string $ip
    ): array {
        $this->deactivateUserDeviceTokens($userId, $deviceId);

        $accessExpiry = date('Y-m-d H:i:s', time() + (int) $this->config['access_token_ttl_seconds']);
        $refreshExpiry = date('Y-m-d H:i:s', time() + (int) $this->config['refresh_token_ttl_seconds']);

        $jwt = $this->buildAccessJwt($userId, $mobile, $app, $access);
        $refreshToken = bin2hex(random_bytes(32));

        $this->insertTokenRow($userId, $jwt, 'access', $deviceType, $deviceName, $deviceId, $firebaseToken, $ip, $accessExpiry);
        $this->insertTokenRow($userId, $refreshToken, 'refresh', $deviceType, $deviceName, $deviceId, null, $ip, $refreshExpiry);

        return [
            'access_token' => $jwt,
            'refresh_token' => $refreshToken,
        ];
    }

    private function buildAccessJwt(int $userId, string $mobile, array $app, array $access): string
    {
        $exp = time() + (int) $this->config['access_token_ttl_seconds'];
        $payload = [
            'iss' => 'techxpert.appauth',
            'aud' => $app['AppCode'],
            'iat' => time(),
            'exp' => $exp,
            'data' => [
                'user_id' => $userId,
                'mobile' => $mobile,
                'app_id' => (int) $app['ID'],
                'app_code' => $app['AppCode'],
                'role_id' => (int) $access['RoleID'],
                'role_code' => $access['RoleCode'],
            ],
        ];

        return JWT::encode($payload, $this->config['jwt_secret'], $this->config['jwt_algorithm']);
    }

    private function insertTokenRow(
        int $userId,
        string $token,
        string $deviceType,
        ?string $deviceTypeLabel,
        ?string $deviceName,
        ?string $deviceId,
        ?string $firebaseToken,
        string $ip,
        string $expiry
    ): void {
        $type = $deviceType === 'refresh' ? 'refresh' : 'access';
        $label = $deviceTypeLabel ?? $type;

        $stmt = $this->conn->prepare(
            'INSERT INTO user_tokens (UserID, AuthToken, DeviceType, DeviceName, DeviceId, FirebaseToken, IPAddress, ExpiryDate, LastUsedDate, IsActive)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param(
            'isssssss',
            $userId,
            $token,
            $type,
            $label,
            $deviceId,
            $firebaseToken,
            $ip,
            $expiry
        );
        $stmt->execute();
        $stmt->close();
    }

    private function deactivateUserDeviceTokens(int $userId, ?string $deviceId): void
    {
        if ($deviceId !== null && $deviceId !== '') {
            $stmt = $this->conn->prepare(
                'UPDATE user_tokens SET IsActive = 0 WHERE UserID = ? AND DeviceId = ? AND IsActive = 1'
            );
            if ($stmt) {
                $stmt->bind_param('is', $userId, $deviceId);
                $stmt->execute();
                $stmt->close();
            }
            return;
        }

        $stmt = $this->conn->prepare(
            'UPDATE user_tokens SET IsActive = 0 WHERE UserID = ? AND IsActive = 1'
        );
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function deactivateTokenByValue(string $token): void
    {
        $stmt = $this->conn->prepare('UPDATE user_tokens SET IsActive = 0 WHERE AuthToken = ?');
        if ($stmt) {
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function deactivateTokenRow(int $tokenRowId): void
    {
        $stmt = $this->conn->prepare('UPDATE user_tokens SET IsActive = 0 WHERE ID = ?');
        if ($stmt) {
            $stmt->bind_param('i', $tokenRowId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function isStoredAccessTokenActive(string $jwt, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT ID, ExpiryDate FROM user_tokens
             WHERE UserID = ? AND AuthToken = ? AND DeviceType = 'access' AND IsActive = 1
             LIMIT 1"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('is', $userId, $jwt);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return false;
        }
        if (!empty($row['ExpiryDate']) && strtotime($row['ExpiryDate']) < time()) {
            $this->deactivateTokenRow((int) $row['ID']);
            return false;
        }

        $touch = $this->conn->prepare('UPDATE user_tokens SET LastUsedDate = NOW() WHERE ID = ?');
        if ($touch) {
            $id = (int) $row['ID'];
            $touch->bind_param('i', $id);
            $touch->execute();
            $touch->close();
        }

        return true;
    }

    public function decodeAccessToken(string $jwt): ?array
    {
        try {
            $decoded = JWT::decode($jwt, new Key($this->config['jwt_secret'], $this->config['jwt_algorithm']));
            // JWT nested claims are stdClass; convert fully to array for PHP 8+
            return json_decode(json_encode($decoded), true);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function getAppProfile(int $userId, string $appCode): ?array
    {
        $tableMap = [
            'HOMECARE' => 'homecare_profiles',
            'PARKING' => 'parking_profiles',
            'MYGATE' => 'mygate_profiles',
        ];
        $table = $tableMap[$appCode] ?? null;
        if (!$table) {
            return null;
        }

        $sql = "SELECT * FROM {$table} WHERE UserID = ? AND IsActive = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    private function findUserByMobile(string $mobile): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT ID, FullName, MobileNumber, Email, Otp, OtpExpiry, ProfileImage, IsMobileVerified, IsActive
             FROM app_users WHERE MobileNumber = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $mobile);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    private function findUserById(int $userId): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT ID, FullName, MobileNumber, Email, ProfileImage, IsMobileVerified, IsActive, LastLogin
             FROM app_users WHERE ID = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    private function getAppByCode(string $appCode): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT ID, AppName, AppCode, AppLogo, Description FROM apps WHERE AppCode = ? AND IsActive = 1 LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $appCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    private function getAppById(int $appId): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT ID, AppName, AppCode, AppLogo FROM apps WHERE ID = ? AND IsActive = 1 LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $appId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    private function getRoleByAppAndCode(int $appId, string $roleCode): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT ID, RoleName, RoleCode FROM app_roles WHERE AppID = ? AND RoleCode = ? AND IsActive = 1 LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('is', $appId, $roleCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    private function userHasAnyApp(int $userId): bool
    {
        $stmt = $this->conn->prepare('SELECT ID FROM user_apps WHERE UserID = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $has = $res && $res->num_rows > 0;
        $stmt->close();
        return $has;
    }

    private function fail(string $message, int $httpCode = 200): array
    {
        return ['error' => true, 'message' => $message, '_http' => $httpCode];
    }
}
