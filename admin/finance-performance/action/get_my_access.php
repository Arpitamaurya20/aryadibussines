<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
if (!$conn) {
    fp_json_response(array('error' => true, 'message' => 'Technical Problem. Please try again'), 500);
}

$actor = fp_require_action($conn, 'get_my_access');

fp_json_response(array(
    'error'   => false,
    'message' => 'OK',
    'data'    => fp_actor_public_view($actor),
));
