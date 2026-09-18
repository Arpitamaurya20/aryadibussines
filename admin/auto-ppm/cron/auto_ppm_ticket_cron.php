<?php
/**
 * Auto PPM Ticket Generation Cron Job
 * 
 * This cron job runs daily and automatically raises PPM tickets for assets
 * where the PPM date in temp_ppm_dates table matches the current date.
 * 
 * CRON SETUP:
 * ===========
 * 
 * Linux/Unix Cron (Run daily at midnight):
 * 0 0 * * * /usr/bin/php /path/to/admin/auto-ppm/cron/auto_ppm_ticket_cron.php >> /path/to/logs/cron_output.log 2>&1
 * 
 * Or run daily at 6:00 AM:
 * 0 6 * * * /usr/bin/php /path/to/admin/auto-ppm/cron/auto_ppm_ticket_cron.php >> /path/to/logs/cron_output.log 2>&1
 * 
 * Windows Task Scheduler:
 * 1. Open Task Scheduler
 * 2. Create Basic Task
 * 3. Set trigger to "Daily" at your preferred time
 * 4. Set action to "Start a program"
 * 5. Program: C:\wamp64\bin\php\php7.x.x\php.exe (or your PHP path)
 * 6. Arguments: C:\wamp64\www\projects\techxpert\admin\auto-ppm\cron\auto_ppm_ticket_cron.php
 * 7. Start in: C:\wamp64\www\projects\techxpert\admin\auto-ppm\cron
 * 
 * Third-Party Cron Services:
 * - Use services like EasyCron, Cron-Job.org, or setcronjob.com
 * - Set URL: https://yourdomain.com/admin/auto-ppm/cron/auto_ppm_ticket_cron.php
 * - Schedule: Daily at your preferred time
 */

// Error reporting (disable in production for cron)
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set execution time limit
set_time_limit(300); // 5 minutes

// Define log directory
$log_dir = dirname(__FILE__) . '/../../logs/';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

// Logging function
function writeLog($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Get current date for logging
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$log_file = $log_dir . 'auto_ppm_cron_' . $current_date . '.txt';

// Start logging
writeLog("===========================================", $log_file);
writeLog("Auto PPM Ticket Generation Cron Started", $log_file);
writeLog("Current Date: $current_date", $log_file);
writeLog("Current Time: $current_time", $log_file);
writeLog("===========================================", $log_file);

try {
    // Include required files
    $base_path = dirname(__FILE__) . '/../../';
    require_once($base_path . 'controllers/common_controllers.php');
    require_once(dirname(__FILE__) . '/../controller/auto_ppm_controller.php');
    require_once($base_path . 'branch/controller/branch_controller.php');
    require_once($base_path . 'ppm-ticket/controller/ppm_controller.php');
    
    writeLog("Required files loaded successfully", $log_file);
    
    // Set timezone
    setTimeZone();
    writeLog("Timezone set: " . date_default_timezone_get(), $log_file);
    
    // Connect to database
    $conn = _connectodb();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    writeLog("Database connection established", $log_file);
    
    // Verify current date matches
    $db_date = date('Y-m-d');
    writeLog("Processing tickets for date: $db_date", $log_file);
    
    // Check if there are any pending PPM dates for today
    $check_sql = "SELECT COUNT(*) as count FROM temp_ppm_dates 
                  WHERE PPMDate = '$db_date' 
                  AND IsTicketRaised = 0 
                  AND IsActive = 1";
    $check_result = mysqli_query($conn, $check_sql);
    
    if ($check_result) {
        $check_row = mysqli_fetch_assoc($check_result);
        $pending_count = $check_row['count'];
        writeLog("Found $pending_count pending PPM date(s) for today", $log_file);
    } else {
        writeLog("Warning: Could not check pending dates - " . mysqli_error($conn), $log_file);
    }
    
    // Process auto PPM ticket generation
    writeLog("Starting ticket generation process...", $log_file);
    $response = ProcessAutoPPMTicketGeneration($conn);
    
    // Log detailed result
    if (isset($response['error']) && $response['error'] == false) {
        writeLog("SUCCESS: " . $response['message'], $log_file);
        writeLog("Tickets Raised: " . $response['tickets_raised'], $log_file);
    } else {
        $error_msg = isset($response['message']) ? $response['message'] : 'Unknown error';
        writeLog("ERROR: $error_msg", $log_file);
    }
    
    // Close database connection
    mysqli_close($conn);
    writeLog("Database connection closed", $log_file);
    
} catch (Exception $e) {
    $error_message = "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
    writeLog($error_message, $log_file);
    
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
    
    // Set error response
    $response = array(
        'error' => true,
        'message' => $error_message,
        'tickets_raised' => 0
    );
}

// Log completion
writeLog("===========================================", $log_file);
writeLog("Auto PPM Ticket Generation Cron Completed", $log_file);
writeLog("===========================================", $log_file);
writeLog("", $log_file);

// Output for manual execution (web browser or CLI)
if (php_sapi_name() !== 'cli') {
    // Web execution - return JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // CLI execution - return readable text
    if (isset($response['error']) && $response['error'] == false) {
        echo "SUCCESS: " . $response['message'] . "\n";
        echo "Tickets Raised: " . $response['tickets_raised'] . "\n";
        exit(0);
    } else {
        $error_msg = isset($response['message']) ? $response['message'] : 'Unknown error';
        echo "ERROR: $error_msg\n";
        exit(1);
    }
}
?>

