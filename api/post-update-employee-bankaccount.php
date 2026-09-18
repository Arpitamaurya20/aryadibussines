<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

if (isset($data['EmployeeID']) && !empty($data['EmployeeID']) && isset($data['BankAccountName']) && !empty($data['BankAccountName'])&& isset($data['BankAccountNumber']) && !empty($data['BankAccountNumber']) ) {

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    // Employee ID
    $employeeID = (int)$data['EmployeeID'];
    $E_BankAccountName = $data['BankAccountName'];
    $E_BankAccountNumber = $data['BankAccountNumber'];




    // Data to Update
    $updateData = array(
        "BankAccountName" => $E_BankAccountName,
        "BankAccountNumber" => $E_BankAccountNumber
    );

    // Update Condition
    $where = array(
        "ID" => $employeeID
    );

    // Update Employee Number
    $response = $core->_UpdateTableRecords_prepare(
        $conn,
        "employees",
        $updateData,
        $where
    );

    if ($response['error'] == false) {
        $response['EmployeeID'] = $employeeID;
        $response['BankAccountName'] = $E_BankAccountName;
        $response['BankAccountNumber'] = $E_BankAccountNumber;

        $response['message'] = "Employee Bankaccount updated successfully.";
    }

} else {

    $response['error'] = true;
    $response['message'] = "EmployeeID is required.";

}

echo json_encode($response);
exit;

?>