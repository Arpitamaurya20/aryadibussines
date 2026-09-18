<?php
/**
 * Aryadi Business - Dedicated Mobile REST API
 * Endpoint: POST /aryadibussines/app_api/login.php
 * Application: aryadi_app (React Native Expo)
 */

// 1. Load CORS, security headers, and JWT infrastructure
require_once __DIR__ . '/../api/common_api_header.php';

// 2. Load existing business controllers and database functions
require_once __DIR__ . '/../admin/controllers/common_controllers.php';
require_once __DIR__ . '/../admin/employees/controller/employee_controller.php';
require_once __DIR__ . '/../admin/authentication/auth_controller/authentication_controller.php';

// Only allow POST method for login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ]);
    exit();
}

// 3. Connect to database using existing system connection FIRST
// (Note: common_controllers uses global $password for DB password, so connect before parsing input)
$conn = _connectodb();

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection unavailable. Please try again later.'
    ]);
    exit();
}

// 4. Read JSON payload
$raw_input = file_get_contents('php://input');
$input_data = json_decode($raw_input, true);

// 5. Validate input
if (
    !is_array($input_data) ||
    !isset($input_data['username']) ||
    !isset($input_data['password']) ||
    trim((string)$input_data['username']) === '' ||
    trim((string)$input_data['password']) === ''
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required.'
    ]);
    exit();
}

$user_credential = [
    'username' => trim((string)$input_data['username']),
    'password' => trim((string)$input_data['password'])
];

// 6. Authenticate using existing backend logic
$auth_result = _api_login_user($conn, $user_credential);

// 7. Handle Authentication Response
if (isset($auth_result['error']) && $auth_result['error'] === false) {
    // Authentication successful
    $user_raw = isset($auth_result['data']) && is_array($auth_result['data']) ? $auth_result['data'] : [];
    $token = isset($auth_result['token']) ? $auth_result['token'] : ($user_raw['AuthToken'] ?? '');

    // Extract roles safely and deduplicate
    $roles = [];
    if (isset($user_raw['role']) && is_array($user_raw['role'])) {
        $raw_roles = isset($user_raw['role']['EmployeeRoles']) ? $user_raw['role']['EmployeeRoles'] : $user_raw['role'];
        if (is_array($raw_roles)) {
            $roles = array_values(array_unique($raw_roles));
        }
    }

    // Sanitize user output: Strictly omit passwords, password hashes, and internal fields
    $sanitized_user = [
        'id'           => (int)($user_raw['UserID'] ?? 0),
        'username'     => (string)($user_raw['UserName'] ?? ''),
        'user_type'    => (string)($user_raw['UserType'] ?? ''),
        'employee_id'  => isset($user_raw['EmployeeID']) && (int)$user_raw['EmployeeID'] !== -1 ? (int)$user_raw['EmployeeID'] : null,
        'corporate_id' => isset($user_raw['CorporateID']) && (int)$user_raw['CorporateID'] !== -1 ? (int)$user_raw['CorporateID'] : null,
        'branch_id'    => isset($user_raw['BranchID']) && (int)$user_raw['BranchID'] !== -1 ? (int)$user_raw['BranchID'] : null,
        'roles'        => $roles,
        'is_active'    => (int)($user_raw['IsActive'] ?? 1)
    ];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token'   => $token,
        'user'    => $sanitized_user
    ]);
    exit();
} else {
    $err_msg = isset($auth_result['message']) ? strtolower($auth_result['message']) : '';

    // Check if failure is due to blocked/inactive account
    if (strpos($err_msg, 'inactive') !== false || (strpos($err_msg, 'not found') !== false && strpos($err_msg, 'employee') !== false)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Account is inactive or blocked. Please contact support.'
        ]);
        exit();
    }

    // Default credential failure: return 401 and generic message without revealing user vs password
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
    ]);
    exit();
}
