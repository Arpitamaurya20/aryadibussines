<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/auto_ppm_controller.php');
require_once('../../includes/autoloader.inc.php');

$conn = _connectodb();
setTimeZone();

$response = array();
$response['error'] = false;
$response['message'] = '';
$response['insert_count'] = 0;
$response['update_count'] = 0;
$response['skipped_count'] = 0;
$response['errors'] = array();

// Check if a file was uploaded
if (!isset($_FILES["csvFile"]) || $_FILES["csvFile"]["size"] == 0) {
    $response['error'] = true;
    $response['message'] = "No file uploaded or file is empty";
    echo json_encode($response);
    exit;
}

$file = $_FILES["csvFile"]["tmp_name"];
$CreatedBy = $_SESSION['pb_username'];
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');

// Validate file extension
$file_name = $_FILES["csvFile"]["name"];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if ($file_ext !== 'csv') {
    $response['error'] = true;
    $response['message'] = "Invalid file type. Only CSV files are allowed.";
    echo json_encode($response);
    exit;
}

// Read the file
$handle = fopen($file, "r");
if ($handle === false) {
    $response['error'] = true;
    $response['message'] = "Error reading CSV file";
    echo json_encode($response);
    exit;
}

// Read header row
$header = fgetcsv($handle, 1000);
if ($header === false) {
    $response['error'] = true;
    $response['message'] = "CSV file is empty or invalid";
    fclose($handle);
    echo json_encode($response);
    exit;
}

// Validate header columns - Support both name-based and ID-based columns
$expected_headers = array(
    'Corporate', 'CorporateID', 
    'Branch', 'BranchID', 
    'Equipment Name', 'EquipmentName', 'BranchAssetID',
    'Make', 
    'Model', 
    'AMC Start Date', 'AMCStartDate',
    'AMC End Date', 'AMCEndDate',
    'Interval'
);
$header_lower = array_map('strtolower', array_map('trim', $header));

// Check for required columns (at least one way to identify each entity)
$has_corporate = in_array('corporate', $header_lower) || in_array('corporateid', $header_lower);
$has_branch = in_array('branch', $header_lower) || in_array('branchid', $header_lower);
$has_asset = in_array('equipment name', $header_lower) || in_array('equipmentname', $header_lower) || in_array('branchassetid', $header_lower);
$has_amc_start = in_array('amc start date', $header_lower) || in_array('amcstartdate', $header_lower);
$has_amc_end = in_array('amc end date', $header_lower) || in_array('amcenddate', $header_lower);
$has_interval = in_array('interval', $header_lower);

$missing_required = array();
if (!$has_corporate) $missing_required[] = 'Corporate or CorporateID';
if (!$has_branch) $missing_required[] = 'Branch or BranchID';
if (!$has_asset) $missing_required[] = 'Equipment Name or BranchAssetID';
if (!$has_amc_start) $missing_required[] = 'AMC Start Date';
if (!$has_amc_end) $missing_required[] = 'AMC End Date';
if (!$has_interval) $missing_required[] = 'Interval';

if (!empty($missing_required)) {
    $response['error'] = true;
    $response['message'] = "Missing required columns: " . implode(', ', $missing_required);
    fclose($handle);
    echo json_encode($response);
    exit;
}

// Get column indices - support multiple column name variations
$col_indices = array();
$col_indices['Corporate'] = array_search('corporate', $header_lower);
$col_indices['CorporateID'] = array_search('corporateid', $header_lower);
$col_indices['Branch'] = array_search('branch', $header_lower);
$col_indices['BranchID'] = array_search('branchid', $header_lower);
$col_indices['Equipment Name'] = array_search('equipment name', $header_lower);
$col_indices['EquipmentName'] = array_search('equipmentname', $header_lower);
$col_indices['BranchAssetID'] = array_search('branchassetid', $header_lower);
$col_indices['Make'] = array_search('make', $header_lower);
$col_indices['Model'] = array_search('model', $header_lower);
$col_indices['AMC Start Date'] = array_search('amc start date', $header_lower);
$col_indices['AMCStartDate'] = array_search('amcstartdate', $header_lower);
$col_indices['AMC End Date'] = array_search('amc end date', $header_lower);
$col_indices['AMCEndDate'] = array_search('amcenddate', $header_lower);
$col_indices['Interval'] = array_search('interval', $header_lower);

// Valid intervals
$valid_intervals = array('monthly', 'quarterly', 'halfyearly', 'yearly');

// Process rows
$row_number = 1;
$success_count = 0;

while (($data = fgetcsv($handle, 1000)) !== false) {
    $row_number++;
    
    // Skip empty rows
    if (empty(array_filter($data))) {
        continue;
    }
    
    // Get values from CSV columns - support both names and IDs
    $corporate_name = '';
    $corporate_id = '';
    if ($col_indices['Corporate'] !== false) {
        $corporate_name = isset($data[$col_indices['Corporate']]) ? trim($data[$col_indices['Corporate']]) : '';
    }
    if ($col_indices['CorporateID'] !== false) {
        $corporate_id = isset($data[$col_indices['CorporateID']]) ? trim($data[$col_indices['CorporateID']]) : '';
    }
    
    $branch_name = '';
    $branch_id = '';
    if ($col_indices['Branch'] !== false) {
        $branch_name = isset($data[$col_indices['Branch']]) ? trim($data[$col_indices['Branch']]) : '';
    }
    if ($col_indices['BranchID'] !== false) {
        $branch_id = isset($data[$col_indices['BranchID']]) ? trim($data[$col_indices['BranchID']]) : '';
    }
    
    $equipment_name = '';
    $branch_asset_id = '';
    if ($col_indices['Equipment Name'] !== false) {
        $equipment_name = isset($data[$col_indices['Equipment Name']]) ? trim($data[$col_indices['Equipment Name']]) : '';
    }
    if ($col_indices['EquipmentName'] !== false) {
        $equipment_name = isset($data[$col_indices['EquipmentName']]) ? trim($data[$col_indices['EquipmentName']]) : '';
    }
    if ($col_indices['BranchAssetID'] !== false) {
        $branch_asset_id = isset($data[$col_indices['BranchAssetID']]) ? trim($data[$col_indices['BranchAssetID']]) : '';
    }
    
    $make = '';
    if ($col_indices['Make'] !== false) {
        $make = isset($data[$col_indices['Make']]) ? trim($data[$col_indices['Make']]) : '';
    }
    
    $model = '';
    if ($col_indices['Model'] !== false) {
        $model = isset($data[$col_indices['Model']]) ? trim($data[$col_indices['Model']]) : '';
    }
    
    $amc_start_date = '';
    if ($col_indices['AMC Start Date'] !== false) {
        $amc_start_date = isset($data[$col_indices['AMC Start Date']]) ? trim($data[$col_indices['AMC Start Date']]) : '';
    }
    if (empty($amc_start_date) && $col_indices['AMCStartDate'] !== false) {
        $amc_start_date = isset($data[$col_indices['AMCStartDate']]) ? trim($data[$col_indices['AMCStartDate']]) : '';
    }
    
    $amc_end_date = '';
    if ($col_indices['AMC End Date'] !== false) {
        $amc_end_date = isset($data[$col_indices['AMC End Date']]) ? trim($data[$col_indices['AMC End Date']]) : '';
    }
    if (empty($amc_end_date) && $col_indices['AMCEndDate'] !== false) {
        $amc_end_date = isset($data[$col_indices['AMCEndDate']]) ? trim($data[$col_indices['AMCEndDate']]) : '';
    }
    
    $interval = '';
    if ($col_indices['Interval'] !== false) {
        $interval = isset($data[$col_indices['Interval']]) ? strtolower(trim($data[$col_indices['Interval']])) : '';
    }
    
    // Validate required fields
    $row_errors = array();
    
    if (empty($corporate_name) && empty($corporate_id)) {
        $row_errors[] = "Corporate or CorporateID is required";
    }
    if (empty($branch_name) && empty($branch_id)) {
        $row_errors[] = "Branch or BranchID is required";
    }
    if (empty($branch_asset_id) && (empty($equipment_name) || empty($make))) {
        $row_errors[] = "BranchAssetID or (Equipment Name and Make) is required";
    }
    if (empty($amc_start_date)) {
        $row_errors[] = "AMC Start Date is required";
    }
    if (empty($amc_end_date)) {
        $row_errors[] = "AMC End Date is required";
    }
    if (empty($interval)) {
        $row_errors[] = "Interval is required";
    }
    
    if (!empty($row_errors)) {
        $response['errors'][] = "Row $row_number: " . implode(', ', $row_errors);
        $response['skipped_count']++;
        continue;
    }
    
    // Validate interval
    if (!in_array($interval, $valid_intervals)) {
        $response['errors'][] = "Row $row_number: Invalid interval '$interval'. Must be one of: " . implode(', ', $valid_intervals);
        $response['skipped_count']++;
        continue;
    }
    
    // Parse dates - support multiple formats (YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY)
    $start_date_obj = false;
    $end_date_obj = false;
    $date_formats = array('Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d', 'm-d-Y', 'd-m-Y');
    
    foreach ($date_formats as $format) {
        $start_date_obj = DateTime::createFromFormat($format, $amc_start_date);
        if ($start_date_obj && $start_date_obj->format($format) === $amc_start_date) {
            break;
        }
    }
    
    foreach ($date_formats as $format) {
        $end_date_obj = DateTime::createFromFormat($format, $amc_end_date);
        if ($end_date_obj && $end_date_obj->format($format) === $amc_end_date) {
            break;
        }
    }
    
    if (!$start_date_obj) {
        $response['errors'][] = "Row $row_number: Invalid AMC Start Date format '$amc_start_date'. Use YYYY-MM-DD or MM/DD/YYYY format";
        $response['skipped_count']++;
        continue;
    }
    
    if (!$end_date_obj) {
        $response['errors'][] = "Row $row_number: Invalid AMC End Date format '$amc_end_date'. Use YYYY-MM-DD or MM/DD/YYYY format";
        $response['skipped_count']++;
        continue;
    }
    
    // Convert to standard format
    $amc_start_date = $start_date_obj->format('Y-m-d');
    $amc_end_date = $end_date_obj->format('Y-m-d');
    
    if ($start_date_obj > $end_date_obj) {
        $response['errors'][] = "Row $row_number: AMC Start Date cannot be after AMC End Date";
        $response['skipped_count']++;
        continue;
    }
    
    // Get Corporate ID
    $CorporateID = -1;
    if (!empty($corporate_id) && is_numeric($corporate_id)) {
        // Use provided Corporate ID
        $CorporateID = intval($corporate_id);
        $where_corporate = " where ID = $CorporateID AND IsActive = 1";
        $corporate_details = _getTableDetails($conn, 'company', $where_corporate);
        if (!$corporate_details || !isset($corporate_details['ID'])) {
            $response['errors'][] = "Row $row_number: Corporate ID '$corporate_id' not found";
            $response['skipped_count']++;
            continue;
        }
    } else if (!empty($corporate_name)) {
        // Find Corporate ID by name
        $where_corporate = " where CompanyName = '" . mysqli_real_escape_string($conn, $corporate_name) . "' AND IsActive = 1";
        $corporate_details = _getTableDetails($conn, 'company', $where_corporate);
        if (!$corporate_details || !isset($corporate_details['ID'])) {
            $response['errors'][] = "Row $row_number: Corporate '$corporate_name' not found";
            $response['skipped_count']++;
            continue;
        }
        $CorporateID = $corporate_details['ID'];
    }
    
    // Get Branch ID
    $BranchID = -1;
    if (!empty($branch_id) && is_numeric($branch_id)) {
        // Use provided Branch ID
        $BranchID = intval($branch_id);
        $where_branch = " where ID = $BranchID AND CompanyID = $CorporateID AND IsActive = 1";
        $branch_details = _getTableDetails($conn, 'branch', $where_branch);
        if (!$branch_details || !isset($branch_details['ID'])) {
            $response['errors'][] = "Row $row_number: Branch ID '$branch_id' not found for Corporate ID '$CorporateID'";
            $response['skipped_count']++;
            continue;
        }
    } else if (!empty($branch_name)) {
        // Find Branch ID by name
        $where_branch = " where BranchSite = '" . mysqli_real_escape_string($conn, $branch_name) . "' AND CompanyID = $CorporateID AND IsActive = 1";
        $branch_details = _getTableDetails($conn, 'branch', $where_branch);
        if (!$branch_details || !isset($branch_details['ID'])) {
            $response['errors'][] = "Row $row_number: Branch '$branch_name' not found for Corporate";
            $response['skipped_count']++;
            continue;
        }
        $BranchID = $branch_details['ID'];
    }
    
    // Get Branch Asset ID
    $BranchAssetID = -1;
    if (!empty($branch_asset_id) && is_numeric($branch_asset_id)) {
        // Use provided Branch Asset ID
        $BranchAssetID = intval($branch_asset_id);
        $where_asset = " where ID = $BranchAssetID AND BranchID = $BranchID AND IsActive = 1";
        $asset_details = _getTableDetails($conn, 'branch_assets', $where_asset);
        if (!$asset_details || !isset($asset_details['ID'])) {
            $response['errors'][] = "Row $row_number: Branch Asset ID '$branch_asset_id' not found in Branch";
            $response['skipped_count']++;
            continue;
        }
    } else if (!empty($equipment_name) && !empty($make)) {
        // Find Branch Asset ID by Equipment Name, Make, and optionally Model
        $where_asset = " where EquipmentName = '" . mysqli_real_escape_string($conn, $equipment_name) . "' 
                          AND Make = '" . mysqli_real_escape_string($conn, $make) . "' 
                          AND BranchID = $BranchID AND IsActive = 1";
        
        // Model is optional - include it in search if provided
        if (!empty($model)) {
            $where_asset .= " AND Model = '" . mysqli_real_escape_string($conn, $model) . "'";
        } else {
            // If model is empty, match assets with empty or NULL model
            $where_asset .= " AND (Model = '' OR Model IS NULL)";
        }
        
        $asset_details = _getTableDetails($conn, 'branch_assets', $where_asset);
        if (!$asset_details || !isset($asset_details['ID'])) {
            $asset_desc = $equipment_name . ' - ' . $make;
            if (!empty($model)) {
                $asset_desc .= ' ' . $model;
            }
            $response['errors'][] = "Row $row_number: Asset '$asset_desc' not found in Branch";
            $response['skipped_count']++;
            continue;
        }
        $BranchAssetID = $asset_details['ID'];
    }
    
    // Prepare data for insertion/update
    $asset_data = array(
        'CorporateID' => $CorporateID,
        'BranchID' => $BranchID,
        'BranchAssetID' => $BranchAssetID,
        'AMCStartDate' => $amc_start_date,
        'AMCEndDate' => $amc_end_date,
        'Interval' => $interval,
        'CreatedBy' => $CreatedBy,
        'form_action' => 'add'
    );
    
    // Check if record already exists
    $where_existing = " where BranchAssetID = $BranchAssetID AND IsActive = 1";
    $existing_record = _getTableDetails($conn, 'temp_branch_assets_info', $where_existing);
    
    if ($existing_record && isset($existing_record['ID'])) {
        // Update existing record
        $TempAssetInfoID = $existing_record['ID'];
        $update_param = " CorporateID = $CorporateID, BranchID = $BranchID, AMCStartDate = '$amc_start_date', AMCEndDate = '$amc_end_date', `Interval` = '$interval' where ID = $TempAssetInfoID";
        $update_response = _UpdateTableRecords($conn, 'temp_branch_assets_info', $update_param);
        
        if ($update_response['error'] == false) {
            // Delete old PPM dates and regenerate
            $delete_old_dates = "DELETE FROM temp_ppm_dates WHERE TempAssetInfoID = $TempAssetInfoID";
            mysqli_query($conn, $delete_old_dates);
            
            // Generate new PPM dates
            $generate_response = GenerateTempPPMDates($conn, $TempAssetInfoID, $amc_start_date, $amc_end_date, $interval);
            $response['update_count']++;
            $success_count++;
        } else {
            $response['errors'][] = "Row $row_number: Failed to update existing record";
            $response['skipped_count']++;
        }
    } else {
        // Insert new record
        $insert_response = InsertUpdateTempBranchAssetsInfo($conn, $asset_data);
        
        if ($insert_response['error'] == false) {
            $response['insert_count']++;
            $success_count++;
        } else {
            $response['errors'][] = "Row $row_number: " . (isset($insert_response['message']) ? $insert_response['message'] : 'Failed to insert record');
            $response['skipped_count']++;
        }
    }
}

fclose($handle);

// Build response message
if ($success_count > 0) {
    $response['message'] = "CSV upload completed successfully. ";
    if ($response['insert_count'] > 0) {
        $response['message'] .= $response['insert_count'] . " asset(s) added. ";
    }
    if ($response['update_count'] > 0) {
        $response['message'] .= $response['update_count'] . " asset(s) updated. ";
    }
    $response['message'] .= "Total: $success_count asset(s) processed.";
    
    if ($response['skipped_count'] > 0) {
        $response['message'] .= " " . $response['skipped_count'] . " row(s) skipped.";
    }
    
    if (count($response['errors']) > 0) {
        $response['error'] = true; // Mark as error if there were any issues
    }
} else {
    $response['error'] = true;
    $response['message'] = "No assets were processed. Please check the CSV file format and data.";
}

echo json_encode($response);
?>

