<?php
@session_start();
@ini_set('display_errors', '0');
while (ob_get_level() > 0) { ob_end_clean(); }
ob_start();

require_once('../../includes/autoloader.inc.php');

header('Content-Type: application/json; charset=utf-8');

$response = array('error' => true, 'message' => 'Unable to process request.');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (empty($_SESSION['pb_username'])) {
        throw new Exception('Session expired. Please log in again.');
    }

    // Detect oversize POST (file bigger than post_max_size returns empty $_POST/$_FILES)
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        throw new Exception('File is too large to upload. Please use an image under 5 MB.');
    }

    $data = $_POST;
    $file_data = $_FILES;
    $data['CreatedBy']   = $_SESSION['pb_username'];
    $data['CreatedDate'] = date('Y-m-d');
    $data['CreatedTime'] = date('H:i:s');

    $dbh  = new Dbh();
    $conn = $dbh->_connectodb();
    $corporateticket_obj = new Corporateticket($conn);

    $response = $corporateticket_obj->UploadTicketMedia($data, $file_data);

    if (is_array($response) && isset($response['error']) && $response['error'] === false) {
        $response['message'] = "Media Uploaded";
    }
} catch (Throwable $e) {
    $response = array('error' => true, 'message' => $e->getMessage());
}

ob_end_clean();
echo json_encode($response);
exit;