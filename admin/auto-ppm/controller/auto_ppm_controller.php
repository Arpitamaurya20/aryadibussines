<?php

/**
 * Insert or Update Temp Branch Assets Info
 */
function InsertUpdateTempBranchAssetsInfo($conn, $data)
{
    // Check if this is bulk operation
    $is_bulk = isset($data['is_bulk']) && $data['is_bulk'] == true;
    
    if ($is_bulk && isset($data['BranchAssetIDs']) && is_array($data['BranchAssetIDs'])) {
        // Handle bulk insertion
        return InsertBulkTempBranchAssetsInfo($conn, $data);
    }
    
    // Handle single asset insertion/update (existing logic)
    $CorporateID = isset($data['CorporateID']) ? $data['CorporateID'] : -1;
    $BranchID = $data['BranchID'];
    $BranchAssetID = $data['BranchAssetID'];
    
    // If CorporateID is not provided, get it from Branch
    if ($CorporateID == -1 && $BranchID != -1) {
        $where = " where ID = $BranchID";
        $branch_details = _getTableDetails($conn, 'branch', $where);
        if ($branch_details && isset($branch_details['CompanyID'])) {
            $CorporateID = $branch_details['CompanyID'];
        }
    }
    
    $AMCStartDate = $data['AMCStartDate'];
    $AMCEndDate = $data['AMCEndDate'];
    $Interval = $data['Interval']; // monthly, quarterly, halfyearly, yearly
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    
    $response = array();
    
    // Check if record already exists for this asset
    $where = " where BranchAssetID = $BranchAssetID AND IsActive = 1";
    $existing_record = _getTableDetails($conn, 'temp_branch_assets_info', $where);
    
    if ($existing_record && isset($existing_record['ID'])) {
        // Update existing record
        $TempAssetInfoID = $existing_record['ID'];
        $update_param = " CorporateID = $CorporateID, BranchID = $BranchID, AMCStartDate = '$AMCStartDate', AMCEndDate = '$AMCEndDate', `Interval` = '$Interval' where ID = $TempAssetInfoID";
        $response = _UpdateTableRecords($conn, 'temp_branch_assets_info', $update_param);
        
        if ($response['error'] == false) {
            // Delete old PPM dates and regenerate
            $delete_old_dates = "DELETE FROM temp_ppm_dates WHERE TempAssetInfoID = $TempAssetInfoID";
            mysqli_query($conn, $delete_old_dates);
            
            // Generate new PPM dates
            $generate_response = GenerateTempPPMDates($conn, $TempAssetInfoID, $AMCStartDate, $AMCEndDate, $Interval);
            $response['message'] = "Temp Branch Assets Info Updated and PPM Dates Regenerated";
        }
    } else {
        // Insert new record
        $sql = "INSERT INTO temp_branch_assets_info (CorporateID, BranchID, BranchAssetID, AMCStartDate, AMCEndDate, `Interval`, CreatedBy, CreatedDate, CreatedTime) 
                VALUES ($CorporateID, $BranchID, $BranchAssetID, '$AMCStartDate', '$AMCEndDate', '$Interval', '$CreatedBy', '$CreatedDate', '$CreatedTime')";
        $response = _InsertTableRecords($conn, $sql);
        
        if ($response['error'] == false) {
            $TempAssetInfoID = $response['last_insert_id'];
            
            // Generate PPM dates
            $generate_response = GenerateTempPPMDates($conn, $TempAssetInfoID, $AMCStartDate, $AMCEndDate, $Interval);
            $response['message'] = "Temp Branch Assets Info Added and PPM Dates Generated";
        }
    }
    
    return $response;
}

/**
 * Insert Bulk Temp Branch Assets Info
 */
function InsertBulkTempBranchAssetsInfo($conn, $data)
{
    $CorporateID = isset($data['CorporateID']) ? $data['CorporateID'] : -1;
    $BranchID = $data['BranchID'];
    $BranchAssetIDs = $data['BranchAssetIDs']; // Array of asset IDs
    
    // If CorporateID is not provided, get it from Branch
    if ($CorporateID == -1 && $BranchID != -1) {
        $where = " where ID = $BranchID";
        $branch_details = _getTableDetails($conn, 'branch', $where);
        if ($branch_details && isset($branch_details['CompanyID'])) {
            $CorporateID = $branch_details['CompanyID'];
        }
    }
    
    $AMCStartDate = $data['AMCStartDate'];
    $AMCEndDate = $data['AMCEndDate'];
    $Interval = $data['Interval']; // monthly, quarterly, halfyearly, yearly
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    
    $response = array();
    $response['error'] = false;
    $response['success_count'] = 0;
    $response['update_count'] = 0;
    $response['insert_count'] = 0;
    $response['skipped_count'] = 0;
    $response['messages'] = array();
    
    if (!is_array($BranchAssetIDs) || empty($BranchAssetIDs)) {
        $response['error'] = true;
        $response['message'] = "No assets selected for bulk operation";
        return $response;
    }
    
    foreach ($BranchAssetIDs as $BranchAssetID) {
        // Skip if empty
        if (empty($BranchAssetID)) {
            $response['skipped_count']++;
            continue;
        }
        
        // Check if record already exists for this asset
        $where = " where BranchAssetID = $BranchAssetID AND IsActive = 1";
        $existing_record = _getTableDetails($conn, 'temp_branch_assets_info', $where);
        
        if ($existing_record && isset($existing_record['ID'])) {
            // Update existing record
            $TempAssetInfoID = $existing_record['ID'];
            $update_param = " CorporateID = $CorporateID, BranchID = $BranchID, AMCStartDate = '$AMCStartDate', AMCEndDate = '$AMCEndDate', `Interval` = '$Interval' where ID = $TempAssetInfoID";
            $update_response = _UpdateTableRecords($conn, 'temp_branch_assets_info', $update_param);
            
            if ($update_response['error'] == false) {
                // Delete old PPM dates and regenerate
                $delete_old_dates = "DELETE FROM temp_ppm_dates WHERE TempAssetInfoID = $TempAssetInfoID";
                mysqli_query($conn, $delete_old_dates);
                
                // Generate new PPM dates
                $generate_response = GenerateTempPPMDates($conn, $TempAssetInfoID, $AMCStartDate, $AMCEndDate, $Interval);
                $response['update_count']++;
                $response['success_count']++;
            }
        } else {
            // Insert new record
            $sql = "INSERT INTO temp_branch_assets_info (CorporateID, BranchID, BranchAssetID, AMCStartDate, AMCEndDate, `Interval`, CreatedBy, CreatedDate, CreatedTime) 
                    VALUES ($CorporateID, $BranchID, $BranchAssetID, '$AMCStartDate', '$AMCEndDate', '$Interval', '$CreatedBy', '$CreatedDate', '$CreatedTime')";
            $insert_response = _InsertTableRecords($conn, $sql);
            
            if ($insert_response['error'] == false) {
                $TempAssetInfoID = $insert_response['last_insert_id'];
                
                // Generate PPM dates
                $generate_response = GenerateTempPPMDates($conn, $TempAssetInfoID, $AMCStartDate, $AMCEndDate, $Interval);
                $response['insert_count']++;
                $response['success_count']++;
            }
        }
    }
    
    // Build success message
    $total_processed = $response['success_count'];
    $message = "Bulk operation completed: ";
    if ($response['insert_count'] > 0) {
        $message .= $response['insert_count'] . " asset(s) added, ";
    }
    if ($response['update_count'] > 0) {
        $message .= $response['update_count'] . " asset(s) updated, ";
    }
    $message .= "Total: " . $total_processed . " asset(s) processed successfully";
    
    if ($response['skipped_count'] > 0) {
        $message .= " (" . $response['skipped_count'] . " skipped)";
    }
    
    $response['message'] = $message;
    
    return $response;
}

/**
 * Generate Temp PPM Dates based on interval
 */
function GenerateTempPPMDates($conn, $TempAssetInfoID, $AMCStartDate, $AMCEndDate, $Interval)
{
    $response = array();
    $response['error'] = false;
    $response['message'] = "PPM Dates Generated";
    
    $start_date = new DateTime($AMCStartDate);
    $end_date = new DateTime($AMCEndDate);
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    
    $dates = array();
    
    switch (strtolower($Interval)) {
        case 'monthly':
            $current = clone $start_date;
            $counter = 1;
            while ($current <= $end_date) {
                $dates[] = array(
                    'date' => $current->format('Y-m-d'),
                    'identifier' => 'M' . $counter
                );
                $current->modify('+1 month');
                $counter++;
            }
            break;
            
        case 'quarterly':
            $current = clone $start_date;
            $counter = 1;
            while ($current <= $end_date) {
                $dates[] = array(
                    'date' => $current->format('Y-m-d'),
                    'identifier' => 'Q' . $counter
                );
                $current->modify('+3 months');
                $counter++;
                if ($counter > 4) $counter = 1; // Reset to Q1 after Q4
            }
            break;
            
        case 'halfyearly':
            $current = clone $start_date;
            $counter = 1;
            while ($current <= $end_date) {
                $dates[] = array(
                    'date' => $current->format('Y-m-d'),
                    'identifier' => 'H' . $counter
                );
                $current->modify('+6 months');
                $counter++;
                if ($counter > 2) $counter = 1; // Reset to H1 after H2
            }
            break;
            
        case 'yearly':
            $current = clone $start_date;
            $counter = 1;
            while ($current <= $end_date) {
                $dates[] = array(
                    'date' => $current->format('Y-m-d'),
                    'identifier' => 'Y' . $counter
                );
                $current->modify('+1 year');
                $counter++;
            }
            break;
            
        default:
            $response['error'] = true;
            $response['message'] = "Invalid Interval Type";
            return $response;
    }
    
    // Insert dates into temp_ppm_dates table
    foreach ($dates as $date_info) {
        $sql = "INSERT INTO temp_ppm_dates (TempAssetInfoID, PPMDate, IntervalIdentifier, IsTicketRaised, CreatedDate, CreatedTime) 
                VALUES ($TempAssetInfoID, '{$date_info['date']}', '{$date_info['identifier']}', 0, '$CreatedDate', '$CreatedTime')";
        _InsertTableRecords($conn, $sql);
    }
    
    return $response;
}

/**
 * Get Temp Branch Assets Info
 */
function GetTempBranchAssetsInfo($conn, $BranchAssetID = -1)
{
    if ($BranchAssetID == -1) {
        $where = " where IsActive = 1";
    } else {
        $where = " where BranchAssetID = $BranchAssetID AND IsActive = 1";
    }
    $response = _getTableRecords($conn, 'temp_branch_assets_info', $where);
    return $response;
}

/**
 * Get Temp PPM Dates
 */
function GetTempPPMDates($conn, $TempAssetInfoID = -1, $IsTicketRaised = -1)
{
    $where = " where 1";
    
    if ($TempAssetInfoID != -1) {
        $where .= " AND TempAssetInfoID = $TempAssetInfoID";
    }
    
    if ($IsTicketRaised != -1) {
        $where .= " AND IsTicketRaised = $IsTicketRaised";
    }
    
    $where .= " AND IsActive = 1 ORDER BY PPMDate ASC";
    
    $response = _getTableRecords($conn, 'temp_ppm_dates', $where);
    return $response;
}

/**
 * Process Daily Auto PPM Ticket Generation (Called by Cron Job)
 */
function ProcessAutoPPMTicketGeneration($conn)
{
    $response = array();
    $response['error'] = false;
    $response['tickets_raised'] = 0;
    $response['message'] = "Auto PPM Ticket Generation Processed";
    
    $current_date = date('Y-m-d');
    
    // Get all temp PPM dates that match current date and are not raised yet
    $where = " where PPMDate = '$current_date' AND IsTicketRaised = 0 AND IsActive = 1";
    $pending_dates = _getTableRecords($conn, 'temp_ppm_dates', $where);
    
    if (empty($pending_dates) || !is_array($pending_dates)) {
        $response['message'] = "No PPM tickets to raise for today";
        return $response;
    }
    
    require_once('../../branch/controller/branch_controller.php');
    require_once('../../ppm-ticket/controller/ppm_controller.php');
    
    foreach ($pending_dates as $ppm_date_record) {
        $TempAssetInfoID = $ppm_date_record['TempAssetInfoID'];
        $PPMDate = $ppm_date_record['PPMDate'];
        
        // Get temp asset info
        $where_asset = " where ID = $TempAssetInfoID";
        $temp_asset_info = _getTableDetails($conn, 'temp_branch_assets_info', $where_asset);
        
        if ($temp_asset_info && isset($temp_asset_info['ID'])) {
            $CorporateID = $temp_asset_info['CorporateID'];
            $BranchID = $temp_asset_info['BranchID'];
            $BranchAssetID = $temp_asset_info['BranchAssetID'];
            
            // Get branch details
            $branch_details = GetBranchDetailsbyID($conn, $BranchID);
            
            // Prepare data for ticket creation
            $ticket_data = array(
                'CorporateID' => $CorporateID,
                'BranchID' => $BranchID,
                'BranchAssetID' => $BranchAssetID,
                'ppmdate' => array($PPMDate),
                'CreatedBy' => 'System Auto PPM'
            );
            
            // Create PPM ticket
            $ticket_response = CreatePPMTicket($conn, $ticket_data, $branch_details);
            
            if ($ticket_response['error'] == false) {
                $TempPPMDateID = $ppm_date_record['ID'];
                
                // Get the created ticket ID from ppm_tickets table
                // Since CreatePPMTicket creates ticket with PPMDate, we can find it by matching
                $ticket_id_query = "SELECT ID, TicketID FROM ppm_tickets 
                                    WHERE CorporateID = $CorporateID 
                                    AND BranchID = $BranchID 
                                    AND BranchAssetID = $BranchAssetID 
                                    AND PPMDate = '$PPMDate' 
                                    AND CreatedBy = 'System Auto PPM' 
                                    ORDER BY ID DESC LIMIT 1";
                $ticket_id_result = mysqli_query($conn, $ticket_id_query);
                
                $ppm_ticket_id = null;
                if ($ticket_id_result && $ticket_id_row = mysqli_fetch_assoc($ticket_id_result)) {
                    $ppm_ticket_id = $ticket_id_row['ID'];
                }
                
                // Update temp_ppm_dates to mark as raised and store TicketID
                if ($ppm_ticket_id) {
                    $update_param = " IsTicketRaised = 1, TicketID = $ppm_ticket_id where ID = $TempPPMDateID";
                } else {
                    $update_param = " IsTicketRaised = 1 where ID = $TempPPMDateID";
                }
                _UpdateTableRecords($conn, 'temp_ppm_dates', $update_param);
                
                $response['tickets_raised']++;
            } else {
                // Log error for this specific ticket
                $error_msg = isset($ticket_response['message']) ? $ticket_response['message'] : 'Unknown error';
                error_log("Failed to create PPM ticket for TempAssetInfoID: $TempAssetInfoID, PPMDate: $PPMDate - $error_msg");
            }
        }
    }
    
    $response['message'] = "Successfully raised " . $response['tickets_raised'] . " PPM ticket(s)";
    return $response;
}

/**
 * Delete Temp Branch Assets Info
 */
function DeleteTempBranchAssetsInfo($conn, $data)
{
    $ID = $data['ID'];
    $where_update = " IsActive = 0 where ID = " . $data['ID'];
    $response = _UpdateTableRecords($conn, 'temp_branch_assets_info', $where_update);
    
    // Also deactivate related PPM dates
    $where_update_dates = " IsActive = 0 where TempAssetInfoID = $ID";
    _UpdateTableRecords($conn, 'temp_ppm_dates', $where_update_dates);
    
    return $response;
}

/**
 * Get Temp Branch Assets Info with Asset Details
 */
function GetTempBranchAssetsInfoWithDetails($conn, $CorporateID = -1, $BranchID = -1)
{
    $where_corporate = "";
    $where_branch = "";
    
    if ($CorporateID != -1) {
        $where_corporate = " AND t.CorporateID = $CorporateID";
    }
    
    if ($BranchID != -1) {
        $where_branch = " AND t.BranchID = $BranchID";
    }
    
    $sql = "SELECT t.*, ba.EquipmentName, ba.Make, ba.Model, ba.SNo, ba.Category, 
            c.CompanyName, b.BranchSite 
            FROM temp_branch_assets_info t
            INNER JOIN branch_assets ba ON t.BranchAssetID = ba.ID
            INNER JOIN branch b ON t.BranchID = b.ID
            INNER JOIN company c ON b.CompanyID = c.ID
            WHERE t.IsActive = 1 $where_corporate $where_branch
            ORDER BY t.ID DESC";
    
    $result = mysqli_query($conn, $sql);
    $response = array();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($response, $row);
        }
    }
    
    return $response;
}

?>

