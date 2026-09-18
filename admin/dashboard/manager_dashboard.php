<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/autoloader.inc.php';
require_once __DIR__ . '/../controllers/common_controllers.php';
require_once __DIR__ . '/inc/state_dashboard_scope.php';

SessionCheck();

$conn = _connectodb();
if (!$conn) {
    header('Location: ../login/login.php');
    exit;
}

manager_dashboard_redirect_to_default($conn, $_SESSION);
