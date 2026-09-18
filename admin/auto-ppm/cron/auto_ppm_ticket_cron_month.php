<?php
/**
 * Auto PPM Ticket Generation Cron Job (Monthly Processing)
 *
 * Logic:
 * - Checks temp_ppm_dates table for any PPMDate where MONTH(PPMDate) = current month
 *   AND YEAR(PPMDate) = current year
 *   AND IsTicketRaised = 0
 *   AND IsActive = 1
 * - Generates tickets one-by-one
 * - Logs all activity
 * - Handles large volumes (100,000+ records)
 */

// ---------------------- CONFIG ----------------------
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
set_time_limit(0); // Unlimited for large batch

// Log directory
$log_dir = dirname(__FILE__) . '/../../logs/';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

// Logging function
function writeLog($msg, $log_file) {
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($log_file, "[$ts] $msg\n", FILE_APPEND);
}

// ---------------------- SETUP ----------------------
$current_date = date('Y-m-d');
$log_file = $log_dir . 'auto_ppm_monthly_' . $current_date . '.txt';

writeLog("===========================================", $log_file);
writeLog("Auto Monthly PPM Ticket Cron Started", $log_file);
writeLog("Current Date: $current_date", $log_file);
writeLog("===========================================", $log_file);

try {
    // Base path
    $base = dirname(__FILE__) . '/../../';

    // Include controllers
    require_once($base . 'controllers/common_controllers.php');
    require_once($base . 'branch/controller/branch_controller.php');
    require_once($base . 'ppm-ticket/controller/ppm_controller.php');
    require_once(dirname(__FILE__) . '/../controller/auto_ppm_controller.php');

    writeLog("Required files loaded", $log_file);

    // Set timezone
    setTimeZone();
    writeLog("Timezone set: " . date_default_timezone_get(), $log_file);

    // DB Connect
    $conn = _connectodb();
    if (!$conn) throw new Exception("Database connection failed");
    writeLog("Database connected", $log_file);

    // Current month & year
    $current_month = date('m');
    $current_year  = date('Y');
    writeLog("Processing Month: $current_month-$current_year", $log_file);

    // ---------------------- FETCH PENDING ----------------------
    $sql = "
        SELECT *
        FROM temp_ppm_dates
        WHERE MONTH(PPMDate) = '$current_month'
          AND YEAR(PPMDate) = '$current_year'
          AND IsTicketRaised = 0
          AND IsActive = 1
        ORDER BY PPMDate ASC
    ";
    $result = mysqli_query($conn, $sql);
    if (!$result) throw new Exception("Query failed: " . mysqli_error($conn));

    $total_pending = mysqli_num_rows($result);
    writeLog("Total Pending PPM Records for Month: $total_pending", $log_file);

    if ($total_pending == 0) {
        writeLog("No tickets to process for this month", $log_file);
        exit;
    }

    $tickets_created = 0;

    // ---------------------- PROCESS ONE-BY-ONE ----------------------
    while ($row = mysqli_fetch_assoc($result)) {
        $ID               = $row['ID']; // Primary key
        $TempAssetInfoID  = $row['TempAssetInfoID'];
        $PPMDate          = $row['PPMDate'];

        writeLog("Processing TempAssetInfoID: $TempAssetInfoID | Date: $PPMDate", $log_file);

        // Generate ticket
        $response = GeneratePPMTicket($conn, $row);

        if ($response['error'] === false) {
            // Mark as raised
            $update_sql = "
                UPDATE temp_ppm_dates 
                SET IsTicketRaised = 1, TicketID = '" . ($response['ticket_id'] ?? 0) . "'
                WHERE ID = '$ID'
            ";
            mysqli_query($conn, $update_sql);

            $tickets_created++;
            writeLog("SUCCESS → Ticket Raised | TempAssetInfoID: $TempAssetInfoID | TicketID: " . ($response['ticket_id'] ?? 'N/A'), $log_file);
        } else {
            writeLog("FAILED → TempAssetInfoID: $TempAssetInfoID | Error: " . $response['message'], $log_file);
        }
    }

    writeLog("===========================================", $log_file);
    writeLog("TOTAL TICKETS GENERATED: $tickets_created", $log_file);
    writeLog("===========================================", $log_file);

    mysqli_close($conn);
    writeLog("Database connection closed", $log_file);

    $response_final = [
        'error' => false,
        'message' => 'Monthly PPM Ticket Generation Completed',
        'tickets_raised' => $tickets_created
    ];

} catch (Exception $e) {
    $err = "FATAL ERROR: " . $e->getMessage();
    writeLog($err, $log_file);

    if (isset($conn)) mysqli_close($conn);

    $response_final = [
        'error' => true,
        'message' => $err,
        'tickets_raised' => 0
    ];
}

// ---------------------- OUTPUT ----------------------
writeLog("Cron Completed", $log_file);
writeLog("===========================================", $log_file);

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode($response_final);
} else {
    print_r($response_final);
}

// ======================= GENERATE PPM TICKET FUNCTION =======================
function GeneratePPMTicket($conn, $ppm_row) {
    $response = ['error' => false, 'message' => '', 'ticket_id' => null];

    $TempAssetInfoID = $ppm_row['TempAssetInfoID'];
    $PPMDate         = $ppm_row['PPMDate'];

    // Get temp asset info
    $asset_info = _getTableDetails($conn, 'temp_branch_assets_info', " WHERE ID = $TempAssetInfoID");

    if (!$asset_info) {
        $response['error'] = true;
        $response['message'] = "Asset info not found for TempAssetInfoID: $TempAssetInfoID";
        return $response;
    }

    $CorporateID   = $asset_info['CorporateID'];
    $BranchID      = $asset_info['BranchID'];
    $BranchAssetID = $asset_info['BranchAssetID'];

    // Branch details
    $branch_details = GetBranchDetailsbyID($conn, $BranchID);
    if (!$branch_details) {
        $response['error'] = true;
        $response['message'] = "Branch details not found for BranchID: $BranchID";
        return $response;
    }

    // Prepare ticket data
    $ticket_data = [
        'CorporateID'   => $CorporateID,
        'BranchID'      => $BranchID,
        'BranchAssetID' => $BranchAssetID,
        'ppmdate'       => [$PPMDate],
        'CreatedBy'     => 'System Auto PPM'
    ];

    // Create PPM ticket
    try {
        $ticket_resp = CreatePPMTicket($conn, $ticket_data, $branch_details);
    } catch (Exception $e) {
        $response['error'] = true;
        $response['message'] = "Exception: " . $e->getMessage();
        return $response;
    }

    if ($ticket_resp['error'] === false) {
        // Get created ticket ID safely
        $PPMDateEscaped = mysqli_real_escape_string($conn, $PPMDate);
        $ticket_id_result = mysqli_query($conn, "
            SELECT ID FROM ppm_tickets
            WHERE CorporateID = $CorporateID AND BranchID = $BranchID
              AND BranchAssetID = $BranchAssetID AND PPMDate = '$PPMDateEscaped'
              AND CreatedBy = 'System Auto PPM'
            ORDER BY ID DESC LIMIT 1
        ");
        if ($ticket_id_result && $row = mysqli_fetch_assoc($ticket_id_result)) {
            $response['ticket_id'] = $row['ID'];
        }
        $response['message'] = "Ticket raised successfully";
    } else {
        $response['error'] = true;
        $response['message'] = $ticket_resp['message'] ?? "Unknown error";
    }

    return $response;
}
?>
