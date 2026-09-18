<?php 
class Session extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function SessionCheck_redirect()
{
    @session_start();

    // 1. Check JWT Access Token
    if (isset($_SESSION['access_token'])) {
        $secret = "zeltologicabcdefghijklmnopqrstuvwxyz";

        try {
            $decoded = \Firebase\JWT\JWT::decode($_SESSION['access_token'], new \Firebase\JWT\Key($secret, 'HS256'));
            return $decoded->user_id;  // valid session
        } catch (Exception $e) {
            // access token invalid or expired --> continue to refresh token
        }
    }

    // 2. Check Refresh Token Cookie
    if (!empty($_COOKIE['refresh_token'])) {

        $auth = new Authentication($this->conn);
        $user = $auth->verifyRefreshToken($_COOKIE['refresh_token']);

        if ($user) {
            // issue new access token
            $secret = "zeltologicabcdefghijklmnopqrstuvwxyz";

            $newAccessToken = \Firebase\JWT\JWT::encode([
                "user_id" => $user['ID'],
                "email"   => $user['Email'],
                "exp" => time() + 86400
            ], $secret, 'HS256');

            $_SESSION['access_token'] = $newAccessToken;

            return $user['UserType'];
        }
    }

    // 3. If no valid authentication → redirect
    header("Location: ../authentication/login.php");
    exit;
}

}