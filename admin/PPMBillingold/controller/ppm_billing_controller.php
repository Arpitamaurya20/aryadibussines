<?php
// Include common controllers for helper functions
if (!function_exists('_getTableDetails')) {
    require_once('../../controllers/common_controllers.php');
}

/**
 * Generate Billing Number
 * @param object $conn
 * @return string
 */
function GenerateBillingNumber($conn)
{
    $Initials = "PPM-BILL-";
    $where_query = " where 1";
    $max_seq = _getMaxIdentityValue_filter($conn, 'ppm_billing_tracking', 'ID', $where_query);
    $seq = $max_seq + 1;
    $formatted_seq = sprintf('%06d', $seq);
    $BillingNumber = $Initials . $formatted_seq;
    return $BillingNumber;
}

/**
 * Calculate Billing Amount based on AMC Date Interval (in months)
 * @param decimal $unitRate - Unit rate from branch_assets (total AMC amount for the period)
 * @param string $amcStartDate - AMC Start Date from branch_assets (YYYY-MM-DD)
 * @param string $amcEndDate - AMC End Date from branch_assets (YYYY-MM-DD)
 * @return decimal
 */
function CalculateBillingAmountOld($unitRate, $amcStartDate, $amcEndDate)
{
    $amount = 0;
    
    // Validate dates
    if (empty($amcStartDate) || empty($amcEndDate)) {
        // If AMC dates are not available, fallback to monthly calculation
        return round($unitRate / 12, 2);
    }
    
    // Parse dates
    $start = new DateTime($amcStartDate);
    $end = new DateTime($amcEndDate);
    
    // Calculate the difference in months
    // Count the number of months between start and end dates (inclusive)
    // Example: Jan 1, 2026 to Mar 31, 2026 = 3 months (Jan, Feb, Mar)
    $yearDiff = (int)$end->format('Y') - (int)$start->format('Y');
    $monthDiff = (int)$end->format('m') - (int)$start->format('m');
    $totalMonths = ($yearDiff * 12) + $monthDiff + 1; // +1 to include both start and end months
    
    // If months is 0 or negative, use default monthly calculation
    if ($totalMonths <= 0) {
        return round($unitRate / 12, 2);
    }
    
    // Calculate one ticket amount: UnitRate divided by the number of months in AMC period
    // Example: UnitRate = 1462, AMC period = 3 months (Jan-Mar), Amount = 1462/3 = 487.33
    $amount = $unitRate / $totalMonths;
    
    return round($amount, 2);
}

function CalculateBillingAmount($unitRate, $amcStartDate, $amcEndDate, $PPMInterval)
{
    // Default multiple
    $multiple = 1;

    switch ($PPMInterval) {
        case 'Monthly':
            $multiple = 12;
            break;

        case 'Quterly': // keep spelling consistent with DB/UI
            $multiple = 4;
            break;

        case 'Half Yearly':
            $multiple = 2;
            break;

        case 'Yearly':
            $multiple = 1;
            break;

        default:
            $multiple = 12; // safe fallback
    }

    // Prevent division by zero
    if ($multiple <= 0 || $unitRate <= 0) {
        return 0;
    }

    $amount = $unitRate / $multiple;

    return round($amount, 2);
}


/**
 * Determine Billing Period based on date range
 * @param string $startDate
 * @param string $endDate
 * @return string
 */
function DetermineBillingPeriod($startDate, $endDate)
{
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    $days = $diff->days;
    
    if ($days <= 31) {
        return 'Monthly';
    } elseif ($days <= 93) {
        return 'Quarterly';
    } elseif ($days <= 186) {
        return 'HalfYearly';
    } else {
        return 'Yearly';
    }
}

/**
 * Get PPM Tickets for Billing
 * @param object $conn - Database connection
 * @param array $filters - Filter array (CorporateID, BranchID, StartDate, EndDate)
 * @return array
 */
function GetPPMTicketsForBilling($conn, $filters)
{
    $response = array();
    $response['error'] = false;
    $response['data'] = array();
    
    $CorporateID = isset($filters['CorporateID']) ? intval($filters['CorporateID']) : -1;
    $BranchID = isset($filters['BranchID']) ? intval($filters['BranchID']) : -1;
    $TicketIDSearch = isset($filters['TicketID']) ? trim($filters['TicketID']) : '';
    $StartDate = isset($filters['StartDate']) ? trim($filters['StartDate']) : date('Y-m-01'); // First day of current month
    $EndDate = isset($filters['EndDate']) ? trim($filters['EndDate']) : date('Y-m-t'); // Last day of current month
    
    // If searching by specific TicketID, get its PPMDate and adjust date range
    if (!empty($TicketIDSearch)) {
        $ticket_search_sql = "SELECT PPMDate FROM ppm_tickets WHERE TicketID = '" . mysqli_real_escape_string($conn, $TicketIDSearch) . "' AND IsActive = 1 LIMIT 1";
        $ticket_search_result = mysqli_query($conn, $ticket_search_sql);
        if ($ticket_search_result && $ticket_row = mysqli_fetch_assoc($ticket_search_result)) {
            // Use the ticket's PPMDate to set the date range
            $ticketPPMDate = $ticket_row['PPMDate'];
            $StartDate = date('Y-m-01', strtotime($ticketPPMDate)); // First day of ticket's month
            $EndDate = date('Y-m-t', strtotime($ticketPPMDate)); // Last day of ticket's month
        }
    }
    
    // Validate and sanitize dates
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $StartDate)) {
        $StartDate = date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $EndDate)) {
        $EndDate = date('Y-m-t');
    }
    
    // Escape dates for SQL
    $StartDate = mysqli_real_escape_string($conn, $StartDate);
    $EndDate = mysqli_real_escape_string($conn, $EndDate);
    
    $where_clause = " WHERE pt.IsActive = 1 AND UPPER(pt.Status) != 'CANCEL'";
    
    // If searching by TicketID, prioritize that (but still apply date range if provided)
    if (!empty($TicketIDSearch)) {
        $TicketIDSearch = mysqli_real_escape_string($conn, $TicketIDSearch);
        $where_clause .= " AND pt.TicketID = '$TicketIDSearch'";
        // Also apply date range when searching by TicketID to ensure it's in range
        $where_clause .= " AND pt.PPMDate >= '$StartDate' AND pt.PPMDate <= '$EndDate'";
    } else {
        // Filter by date range based on PPMDate
        $where_clause .= " AND pt.PPMDate >= '$StartDate' AND pt.PPMDate <= '$EndDate'";
    }
    
    if ($CorporateID != -1) {
        $where_clause .= " AND pt.CorporateID = $CorporateID";
    }
    
    if ($BranchID != -1) {
        $where_clause .= " AND pt.BranchID = $BranchID";
    }
    
    $sql = "SELECT 
                pt.ID as TicketID,
                pt.TicketID as TicketNumber,
                pt.CorporateID,
                pt.BranchID,
                pt.BranchAssetID,
                pt.PPMDate,
                pt.Status as TicketStatus,
                pt.CreatedDate,
                pt.CloseDate,
                COALESCE(ba.ID,0) as EquipmentID,
                COALESCE(ba.EquipmentName, 'N/A') as EquipmentName,
                COALESCE(ba.UnitRate, 0) as AssetUnitRate,
                COALESCE(ba.Amount, 0) as AssetAmount,
                COALESCE(ba.AMCStartDate, '') as AMCStartDate,
                COALESCE(ba.AMCEndDate, '') as AMCEndDate,
                COALESCE(ba.PPMInterval, 'Monthly') as PPMInterval,
                COALESCE(c.CompanyName, 'N/A') as CompanyName,
                COALESCE(b.BranchSite, 'N/A') as BranchSite,
                COALESCE(b.BranchCode, '') as BranchCode,
                pbt.ID as BillingTrackingID,
                pbt.BillingStatus,
                pbt.PaymentStatus,
                pbt.BillingPeriod,
                pbt.CalculatedAmount,
                pbt.BilledAmount,
                pbt.BillingNumber,
                pbt.BilledDate
            FROM ppm_tickets pt
            LEFT JOIN branch_assets ba ON pt.BranchAssetID = ba.ID AND ba.IsActive = 1
            LEFT JOIN company c ON pt.CorporateID = c.ID AND c.IsActive = 1
            LEFT JOIN branch b ON pt.BranchID = b.ID AND b.IsActive = 1
            LEFT JOIN ppm_billing_tracking pbt ON pt.ID = pbt.TicketID AND pbt.IsActive = 1
            $where_clause
            ORDER BY pt.PPMDate DESC, pt.ID DESC";
    
    // Store SQL for potential debugging
    $response['sql_debug'] = $sql;
    
    // Log the query and filters for debugging
    error_log("PPM Billing Query: " . $sql);
    error_log("PPM Billing Filters: StartDate=$StartDate, EndDate=$EndDate, CorporateID=$CorporateID, BranchID=$BranchID, TicketID=$TicketIDSearch");
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        $error = mysqli_error($conn);
        error_log("PPM Billing SQL Error: " . $error);
        error_log("PPM Billing Query: " . $sql);
        error_log("PPM Billing Filters: StartDate=$StartDate, EndDate=$EndDate, CorporateID=$CorporateID, BranchID=$BranchID, TicketID=$TicketIDSearch");
        $response['error'] = true;
        $response['message'] = "SQL Error: " . $error;
        return $response;
    }
    
    // Count rows before processing
    $row_count = mysqli_num_rows($result);
    error_log("PPM Billing Query returned $row_count rows");
    
    if ($result) {
        $total_rows = mysqli_num_rows($result);
        error_log("PPM Billing: Processing $total_rows tickets");
        
        if ($total_rows > 0) {
            $processed_count = 0;
            $data = array(); // Initialize data array
            while ($row = mysqli_fetch_assoc($result)) {
                $processed_count++;
                // Determine billing period (for display purposes)
                $billingPeriod = DetermineBillingPeriod($StartDate, $EndDate);
                
                // Get unit rate (prefer UnitRate, fallback to Amount)
                $unitRate = !empty($row['AssetUnitRate']) ? floatval($row['AssetUnitRate']) : floatval($row['AssetAmount']);
                
                // Get AMC dates from asset
                $amcStartDate = !empty($row['AMCStartDate']) ? $row['AMCStartDate'] : '';
                $amcEndDate = !empty($row['AMCEndDate']) ? $row['AMCEndDate'] : '';
                $PPMInterval=!empty($row['PPMInterval']) ? $row['PPMInterval'] : 'Monthly';
                
                // Calculate billing amount based on AMC date interval
                $calculatedAmount = CalculateBillingAmount($unitRate, $amcStartDate, $amcEndDate,$PPMInterval);
                
                // Set default billing status if not exists
                if (empty($row['BillingTrackingID'])) {
                    $row['BillingStatus'] = 'Unbilled';
                    $row['PaymentStatus'] = 'Pending';
                    $row['BillingPeriod'] = $billingPeriod;
                    $row['CalculatedAmount'] = $calculatedAmount;
                    $row['BilledAmount'] = 0;
                    $row['BillingNumber'] = null;
                    $row['BilledDate'] = null;
                } else {
                    // Ensure BilledAmount is set even if NULL
                    if (empty($row['BilledAmount']) || $row['BilledAmount'] === null) {
                        $row['BilledAmount'] = 0;
                    }
                    // Use stored CalculatedAmount if available and > 0, otherwise use calculated
                    if (!empty($row['CalculatedAmount']) && floatval($row['CalculatedAmount']) > 0) {
                        // Use stored value to ensure consistency with what's stored in database
                        $row['CalculatedAmount'] = floatval($row['CalculatedAmount']);
                    } else {
                        // Use calculated amount if stored value is missing or 0
                        $row['CalculatedAmount'] = $calculatedAmount;
                    }
                }
                
                $data[] = $row;
            }
            error_log("PPM Billing: Processed $processed_count tickets successfully");
            $response['data'] = $data;
            $response['row_count'] = $total_rows;
        } else {
            // No rows found, but not an error
            error_log("PPM Billing: No tickets found with filters: StartDate=$StartDate, EndDate=$EndDate, CorporateID=$CorporateID, BranchID=$BranchID");
            $response['data'] = array();
            $response['message'] = "No tickets found for the selected date range and filters";
            $response['row_count'] = 0;
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Error fetching tickets: " . mysqli_error($conn);
        $response['sql_error'] = mysqli_error($conn);
    }
    
    return $response;
}

/**
 * Create or Update Billing Tracking Record
 * @param object $conn
 * @param array $data
 * @return array
 */
function CreateUpdateBillingTracking($conn, $data)
{
    $response = array();
    $response['error'] = false;
    
    $TicketID = intval($data['TicketID']);
    $BillingStatus = isset($data['BillingStatus']) ? $data['BillingStatus'] : 'Unbilled';
    $PaymentStatus = isset($data['PaymentStatus']) ? $data['PaymentStatus'] : 'Pending';
    $BillingNumber = isset($data['BillingNumber']) ? trim($data['BillingNumber']) : '';
    $BilledDate = isset($data['BilledDate']) ? $data['BilledDate'] : date('Y-m-d');
    $BilledBy = isset($data['BilledBy']) ? $data['BilledBy'] : '';
    $Remarks = isset($data['Remarks']) ? $data['Remarks'] : '';
    $CreatedBy = isset($data['CreatedBy']) ? $data['CreatedBy'] : '';
    
    // Get ticket details
    $where = " where ID = $TicketID";
    $ticket = _getTableDetails($conn, 'ppm_tickets', $where);
    
    if (!$ticket) {
        $response['error'] = true;
        $response['message'] = "Ticket not found";
        return $response;
    }
    
    // Get branch asset details
    $where_asset = " where ID = " . $ticket['BranchAssetID'];
    $asset = _getTableDetails($conn, 'branch_assets', $where_asset);
    
    if (!$asset) {
        $response['error'] = true;
        $response['message'] = "Asset not found";
        return $response;
    }
    
    // Get unit rate
    $unitRate = !empty($asset['UnitRate']) ? floatval($asset['UnitRate']) : floatval(0);
    
    // Get AMC dates from asset
    $amcStartDate = !empty($asset['AMCStartDate']) ? $asset['AMCStartDate'] : '';
    $amcEndDate = !empty($asset['AMCEndDate']) ? $asset['AMCEndDate'] : '';
    $PPMInterval=!empty($asset['PPMInterval']) ? $asset['PPMInterval'] : 'Monthly';
    
    // Determine billing period from data or calculate from date range (for display purposes)
    $BillingPeriod = isset($data['BillingPeriod']) ? $data['BillingPeriod'] : 'Monthly';
    if (isset($data['BillingStartDate']) && isset($data['BillingEndDate'])) {
        $BillingPeriod = DetermineBillingPeriod($data['BillingStartDate'], $data['BillingEndDate']);
    }
    
    // Calculate amount based on AMC date interval
    $CalculatedAmount = CalculateBillingAmount($unitRate, $amcStartDate, $amcEndDate,$PPMInterval);
    
    // Check if billing tracking exists
    $where_tracking = " where TicketID = $TicketID AND IsActive = 1";
    $existing = _getTableDetails($conn, 'ppm_billing_tracking', $where_tracking);
    
    // Get existing calculated amount if available
    if ($existing && !empty($existing['CalculatedAmount'])) {
        $CalculatedAmount = floatval($existing['CalculatedAmount']);
    }
    
    // Set BilledAmount: if status is Billed and amount is 0 or not provided, use CalculatedAmount
    $BilledAmount = isset($data['BilledAmount']) ? floatval($data['BilledAmount']) : 0;
    if ($BillingStatus == 'Billed' && ($BilledAmount == 0 || empty($data['BilledAmount']))) {
        $BilledAmount = $CalculatedAmount;
    }
    
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $UpdatedDate = date('Y-m-d');
    $UpdatedTime = date('H:i:s');
    
    if ($existing) {
        // Update existing record
        $ID = $existing['ID'];
        $update_param = " BillingStatus = '$BillingStatus', PaymentStatus = '$PaymentStatus', BilledAmount = $BilledAmount, CalculatedAmount = $CalculatedAmount";
        if (!empty($BillingNumber)) {
            $update_param .= ", BillingNumber = '$BillingNumber'";
        } else {
            $update_param .= ", BillingNumber = NULL";
        }
        $update_param .= ", BilledDate = '$BilledDate', BilledBy = '$BilledBy', Remarks = '$Remarks', UpdatedBy = '$CreatedBy', UpdatedDate = '$UpdatedDate', UpdatedTime = '$UpdatedTime' where ID = $ID";
        $response = _UpdateTableRecords($conn, 'ppm_billing_tracking', $update_param);
    } else {
        // Create new record
        $sql = "INSERT INTO ppm_billing_tracking (
                    TicketID, CorporateID, BranchID, BranchAssetID, PPMDate, 
                    TicketStatus, BillingStatus, PaymentStatus, BillingPeriod,
                    BillingStartDate, BillingEndDate, AssetUnitRate, CalculatedAmount,
                    BilledAmount, BillingNumber, BilledDate, BilledBy, Remarks,
                    CreatedBy, CreatedDate, CreatedTime, IsActive
                ) VALUES (
                    $TicketID, " . $ticket['CorporateID'] . ", " . $ticket['BranchID'] . ", " . $ticket['BranchAssetID'] . ", '" . $ticket['PPMDate'] . "',
                    '" . $ticket['Status'] . "', '$BillingStatus', '$PaymentStatus', '$BillingPeriod',
                    '" . $data['BillingStartDate'] . "', '" . $data['BillingEndDate'] . "', $unitRate, $CalculatedAmount,
                    $BilledAmount, " . (!empty($BillingNumber) ? "'$BillingNumber'" : "NULL") . ", '$BilledDate', '$BilledBy', '$Remarks',
                    '$CreatedBy', '$CreatedDate', '$CreatedTime', 1
                )";
        $response = _InsertTableRecords($conn, $sql);
    }
    
    if ($response['error'] == false) {
        $response['BilledAmount'] = $BilledAmount;
        $response['CalculatedAmount'] = $CalculatedAmount;
        if (!empty($BillingNumber)) {
            $response['BillingNumber'] = $BillingNumber;
        }
    }
    
    return $response;
}

/**
 * Get Billing Statistics
 * @param object $conn
 * @param array $filters
 * @return array
 */
function GetBillingStatistics($conn, $filters)
{
    $response = array();
    $response['error'] = false;
    
    $CorporateID = isset($filters['CorporateID']) ? intval($filters['CorporateID']) : -1;
    $BranchID = isset($filters['BranchID']) ? intval($filters['BranchID']) : -1;
    $StartDate = isset($filters['StartDate']) ? trim($filters['StartDate']) : date('Y-m-01');
    $EndDate = isset($filters['EndDate']) ? trim($filters['EndDate']) : date('Y-m-t');
    
    // Validate and sanitize dates
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $StartDate)) {
        $StartDate = date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $EndDate)) {
        $EndDate = date('Y-m-t');
    }
    
    // Escape dates for SQL
    $StartDate = mysqli_real_escape_string($conn, $StartDate);
    $EndDate = mysqli_real_escape_string($conn, $EndDate);
    
    // Filter by PPMDate - this is the key filter for billing
    // Exclude Cancel status tickets from all billing calculations
    $where_clause = " WHERE pt.IsActive = 1 AND pt.PPMDate >= '$StartDate' AND pt.PPMDate <= '$EndDate' AND UPPER(pt.Status) != 'CANCEL'";
    
    if ($CorporateID != -1) {
        $where_clause .= " AND pt.CorporateID = $CorporateID";
    }
    
    if ($BranchID != -1) {
        $where_clause .= " AND pt.BranchID = $BranchID";
    }
    
    // Get ticket status counts and amounts
    // Calculate total = sum of (each ticket's calculated amount) for each status
    // Use the same CalculatedAmount logic as GetPPMTicketsForBilling for consistency
    $sql_status = "SELECT 
                        pt.Status as TicketStatus,
                        pt.ID as TicketID,
                        COALESCE(pbt.CalculatedAmount, 0) as StoredCalculatedAmount,
                        COALESCE(ba.UnitRate, ba.Amount, 0) as UnitRate,
                        COALESCE(ba.AMCStartDate, '') as AMCStartDate,
                        COALESCE(ba.AMCEndDate, '') as AMCEndDate,
                        COALESCE(ba.PPMInterval, '') as PPMInterval
                    FROM ppm_tickets pt
                    INNER JOIN branch_assets ba ON pt.BranchAssetID = ba.ID
                    LEFT JOIN ppm_billing_tracking pbt ON pt.ID = pbt.TicketID AND pbt.IsActive = 1
                    $where_clause";
    
    $result_status = mysqli_query($conn, $sql_status);
    $status_counts = array('Raised' => 0, 'Assigned' => 0, 'Closed' => 0);
    $status_amounts = array('Raised' => 0, 'Assigned' => 0, 'Closed' => 0);
    
    if ($result_status) {
        while ($row = mysqli_fetch_assoc($result_status)) {
            $status = trim($row['TicketStatus']);
            // Map common status variations
            $status = ucfirst(strtolower($status));
            
            // Skip Cancel status tickets
            if (strtolower($status) == 'cancel') {
                continue;
            }
            
            if (in_array($status, ['Raised', 'Assigned', 'Closed'])) {
                // Count tickets for this status
                $status_counts[$status]++;
                
                // Get calculated amount for this ticket
                // IMPORTANT: Always calculate fresh to match what's shown in the table
                // Don't use stored CalculatedAmount as it might be outdated or incorrect
                // This ensures statistics match the table display exactly
                $unitRate = floatval($row['UnitRate']);
                $amcStartDate = !empty($row['AMCStartDate']) ? $row['AMCStartDate'] : '';
                $amcEndDate = !empty($row['AMCEndDate']) ? $row['AMCEndDate'] : '';
                $PPMInterval = !empty($row['PPMInterval']) ? $row['PPMInterval'] : '';
                $calculatedAmount = CalculateBillingAmount($unitRate, $amcStartDate,$amcEndDate,$PPMInterval);
                
                // Add this ticket's calculated amount to the total for this status
                // Total = sum of (each ticket's calculated amount)
                $status_amounts[$status] += $calculatedAmount;
            }
        }
    } else {
        error_log("Error in GetBillingStatistics - Status Query: " . mysqli_error($conn));
    }
    
    // Get billing status counts and amounts with AMC-based calculation
    // Use stored CalculatedAmount from billing tracking if available
    $sql_billing = "SELECT 
                        COALESCE(pbt.BillingStatus, 'Unbilled') as BillingStatus,
                        pt.ID as TicketID,
                        COALESCE(pbt.BilledAmount, 0) as BilledAmount,
                        COALESCE(pbt.CalculatedAmount, 0) as StoredCalculatedAmount,
                        COALESCE(ba.UnitRate, ba.Amount, 0) as UnitRate,
                        COALESCE(ba.AMCStartDate, '') as AMCStartDate,
                        COALESCE(ba.AMCEndDate, '') as AMCEndDate,
                        COALESCE(ba.PPMInterval, 'Monthly') as PPMInterval
                    FROM ppm_tickets pt
                    LEFT JOIN ppm_billing_tracking pbt ON pt.ID = pbt.TicketID AND pbt.IsActive = 1
                    INNER JOIN branch_assets ba ON pt.BranchAssetID = ba.ID
                    $where_clause";
    
    $result_billing = mysqli_query($conn, $sql_billing);
    $billing_counts = array('Billed' => 0, 'Unbilled' => 0);
    $billing_amounts = array('Billed' => 0, 'Unbilled' => 0);
    
    if ($result_billing) {
        while ($row = mysqli_fetch_assoc($result_billing)) {
            // Skip Cancel status tickets (additional safety check)
            // Note: SQL already filters these out, but this is a safety measure
            $status = $row['BillingStatus'];
            $billing_counts[$status]++;
            
            // For billed tickets, use BilledAmount if available
            // For billed tickets, use BilledAmount if available
            // For unbilled tickets, calculate fresh to match table display
            if ($status == 'Billed' && floatval($row['BilledAmount']) > 0) {
                $billing_amounts[$status] += floatval($row['BilledAmount']);
            } else {
                // Always calculate fresh to ensure consistency with table display
                $unitRate = floatval($row['UnitRate']);
                $amcStartDate = !empty($row['AMCStartDate']) ? $row['AMCStartDate'] : '';
                $amcEndDate = !empty($row['AMCEndDate']) ? $row['AMCEndDate'] : '';
                $PPMInterval =!empty($row['PPMInterval']) ? $row['PPMInterval'] : '';
                $calculatedAmount = CalculateBillingAmount($unitRate, $amcStartDate, $amcEndDate,$PPMInterval);
                $billing_amounts[$status] += $calculatedAmount;
            }
        }
    }
    
    // Get payment status counts and amounts with AMC-based calculation
    // Use stored CalculatedAmount from billing tracking if available
    $sql_payment = "SELECT 
                        COALESCE(pbt.PaymentStatus, 'Pending') as PaymentStatus,
                        pt.ID as TicketID,
                        COALESCE(pbt.BilledAmount, 0) as BilledAmount,
                        COALESCE(pbt.CalculatedAmount, 0) as StoredCalculatedAmount,
                        COALESCE(ba.UnitRate, ba.Amount, 0) as UnitRate,
                        COALESCE(ba.AMCStartDate, '') as AMCStartDate,
                        COALESCE(ba.AMCEndDate, '') as AMCEndDate,
                        COALESCE(ba.PPMInterval, 'Monthly') as PPMInterval
                    FROM ppm_tickets pt
                    LEFT JOIN ppm_billing_tracking pbt ON pt.ID = pbt.TicketID AND pbt.IsActive = 1
                    INNER JOIN branch_assets ba ON pt.BranchAssetID = ba.ID
                    $where_clause";
    
    $result_payment = mysqli_query($conn, $sql_payment);
    $payment_counts = array('Pending' => 0, 'Closed' => 0, 'Billed' => 0);
    $payment_amounts = array('Pending' => 0, 'Closed' => 0, 'Billed' => 0);
    
    if ($result_payment) {
        while ($row = mysqli_fetch_assoc($result_payment)) {
            $status = $row['PaymentStatus'];
            if (in_array($status, ['Pending', 'Closed', 'Billed'])) {
                $payment_counts[$status]++;
                
                // Use BilledAmount if available and > 0, otherwise calculate fresh
                if (floatval($row['BilledAmount']) > 0) {
                    $payment_amounts[$status] += floatval($row['BilledAmount']);
                } else {
                    // Always calculate fresh to ensure consistency with table display
                    $unitRate = floatval($row['UnitRate']);
                    $amcStartDate = !empty($row['AMCStartDate']) ? $row['AMCStartDate'] : '';
                    $amcEndDate = !empty($row['AMCEndDate']) ? $row['AMCEndDate'] : '';
                    $PPMInterval =!empty($row['PPMInterval']) ? $row['PPMInterval'] : '';
                    $calculatedAmount = CalculateBillingAmount($unitRate, $amcStartDate, $amcEndDate, $PPMInterval);
                    $payment_amounts[$status] += $calculatedAmount;
                }
            }
        }
    }
    
    // Calculate total amounts
    $sql_total = "SELECT 
                        COUNT(*) as TotalTickets,
                        SUM(CASE WHEN ba.UnitRate > 0 THEN ba.UnitRate ELSE ba.Amount END) as TotalAssetValue
                    FROM ppm_tickets pt
                    INNER JOIN branch_assets ba ON pt.BranchAssetID = ba.ID
                    $where_clause";
    
    $result_total = mysqli_query($conn, $sql_total);
    $total_tickets = 0;
    $total_asset_value = 0;
    
    if ($result_total && $row = mysqli_fetch_assoc($result_total)) {
        $total_tickets = intval($row['TotalTickets']);
        $total_asset_value = floatval($row['TotalAssetValue']);
    }
    
    $response['data'] = array(
        'ticket_status' => $status_counts,
        'ticket_amounts' => $status_amounts,
        'billing_status' => $billing_counts,
        'billing_amounts' => $billing_amounts,
        'payment_status' => $payment_counts,
        'payment_amounts' => $payment_amounts,
        'total_tickets' => $total_tickets,
        'total_asset_value' => $total_asset_value
    );
    
    return $response;
}

/**
 * Toggle Billing Status
 * @param object $conn
 * @param array $data
 * @return array
 */
function ToggleBillingStatus($conn, $data)
{
    $response = array();
    $response['error'] = false;
    
    $TicketID = intval($data['TicketID']);
    $CurrentBillingStatus = isset($data['CurrentBillingStatus']) ? $data['CurrentBillingStatus'] : 'Unbilled';
    $NewBillingStatus = ($CurrentBillingStatus == 'Billed') ? 'Unbilled' : 'Billed';
    $BillingStartDate = isset($data['BillingStartDate']) ? $data['BillingStartDate'] : date('Y-m-01');
    $BillingEndDate = isset($data['BillingEndDate']) ? $data['BillingEndDate'] : date('Y-m-t');
    $CreatedBy = isset($data['CreatedBy']) ? $data['CreatedBy'] : '';
    
    // Get ticket details
    $where = " where ID = $TicketID";
    $ticket = _getTableDetails($conn, 'ppm_tickets', $where);
    
    if (!$ticket) {
        $response['error'] = true;
        $response['message'] = "Ticket not found";
        return $response;
    }
    
    // Get existing billing tracking to preserve calculated amount
    $where_tracking = " where TicketID = $TicketID AND IsActive = 1";
    $existing = _getTableDetails($conn, 'ppm_billing_tracking', $where_tracking);
    
    // Prepare billing data
    $billing_data = array(
        'TicketID' => $TicketID,
        'BillingStatus' => $NewBillingStatus,
        'PaymentStatus' => ($NewBillingStatus == 'Billed') ? 'Billed' : 'Pending',
        'BilledAmount' => ($NewBillingStatus == 'Billed' && $existing && !empty($existing['CalculatedAmount'])) ? $existing['CalculatedAmount'] : 0,
        'BilledDate' => ($NewBillingStatus == 'Billed') ? date('Y-m-d') : null,
        'BilledBy' => ($NewBillingStatus == 'Billed') ? $CreatedBy : '',
        'BillingStartDate' => $BillingStartDate,
        'BillingEndDate' => $BillingEndDate,
        'CreatedBy' => $CreatedBy
    );
    
    $response = CreateUpdateBillingTracking($conn, $billing_data);
    
    if ($response['error'] == false) {
        $response['message'] = "Billing status updated to " . $NewBillingStatus;
        if ($NewBillingStatus == 'Billed' && isset($response['BilledAmount'])) {
            $response['message'] .= ". Billed amount set to ₹" . number_format($response['BilledAmount'], 2);
        }
        $response['new_status'] = $NewBillingStatus;
    }
    
    return $response;
}

?>
