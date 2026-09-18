<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../../includes/autoloader.inc.php');

$response = array();

if (isset($_SESSION['CompanyID'])) {
    $CompanyID = $_SESSION['CompanyID'];
    $UpdatedBy = $_SESSION['pb_username'];

    $data = json_decode(file_get_contents('php://input'), true);


    if ($data && isset($data['ID'])) {
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $core = new Core();
        $core->setTimeZone();

        $UpdatedDate = date("Y-m-d");
        $UpdatedTime = date("H:i:s");

        // Build update array (only editable fields)
        $rowData = [
            'Type'         => $data['type'],
            'Category'     => $data['category'],
            'SubCategory'  => $data['subcategory'],
            'LineItemName' => $data['lineItemName'],
            'Make'         => $data['make'],
            'HSN'          => $data['hsn'],
            'ARCCode'      => $data['arccode'],
            'UoM'          => $data['uom'],
            'Price'        => $data['price'],
            'Tax'          => $data['tax'],
            'UpdatedDate'  => $UpdatedDate
        ];

        // WHERE condition for update
        $where = [
            'ID'        => $data['ID'],
            'CompanyID' => $CompanyID // ensure user can only update his company’s data
        ];

        $response = $core->_UpdateTableRecords_prepare($conn, 'corporate_rate_card', $rowData, $where);

        $conn->close();
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid data received.";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Technical Error! Please login and try again.";
}

echo json_encode($response);
