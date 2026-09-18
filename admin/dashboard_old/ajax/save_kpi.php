<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

require_once('../../controllers/common_controllers.php');
require_once('../../customer/controller/customer_controller.php');
require_once('../../authentication/auth_controller/authentication_controller.php');

setTimeZone();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['image'], $data['employeeID'], $data['employeeNumber'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$image = $data['image'];
$employeeID = $data['employeeID'];
$employeeNumber = $data['employeeNumber'];

// Remove base64 prefix
$image = str_replace('data:image/png;base64,', '', $image);
$image = str_replace(' ', '+', $image);

// Ensure directory exists
$dateStr = date('Ymd');
$dir = __DIR__ . "/kpi_images";
if (!is_dir($dir)) mkdir($dir, 0777, true);

// Save the image
$file = $dir . "/KPI_{$employeeID}_{$dateStr}.png";
file_put_contents($file, base64_decode($image));

// Now, send this image via Interakt
function _interakt_sendWhatsAppMessageNew($phonenumber, $imageUrl, $templateName = "emp_kpi") {
    $curl = curl_init();

    // Body values, you can include dynamic text if needed
    $body_values = json_encode([]);

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "countryCode": "+91",
            "phoneNumber": "'.$phonenumber.'",
            "type": "Template",
            "template": {
                "name": "'.$templateName.'", 
                "languageCode": "en", 
                "bodyValues": '.$body_values.',
                "headerValues": ["'.$imageUrl.'"]
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic TVZPai1Sb3VRbE9fN3ltYWxNOTlRTWxFd0kwckt2NmFBak4zSUNEQnRaczo=',
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error = 'Curl error: ' . curl_error($curl);
        curl_close($curl);
        return ['status' => 'error', 'message' => $error];
    }

    curl_close($curl);

    // Log response
    file_put_contents('interakt_response.log', date('Y-m-d H:i:s').' - '.$response.PHP_EOL, FILE_APPEND);

    return json_decode($response, true);
}

// Build public URL for the saved image
$imageUrl = 'https://techxpertindia.in/admin/dashboard/ajax/kpi_images/' . basename($file);

// Send WhatsApp message
$whatsappResponse = _interakt_sendWhatsAppMessageNew($employeeNumber, $imageUrl);

echo json_encode([
    'success' => true,
    'file' => $file,
    'whatsapp' => $whatsappResponse
]);
?>
