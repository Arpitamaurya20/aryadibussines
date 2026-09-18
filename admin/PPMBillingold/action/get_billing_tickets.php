<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
$UserType = SessionCheck();

header('Content-Type: application/json');

$response = array();
$response['error'] = false;

require_once('../controller/ppm_billing_controller.php');

// Get and validate dates
$StartDate = isset($_GET['StartDate']) ? trim($_GET['StartDate']) : date('Y-m-01');
$EndDate = isset($_GET['EndDate']) ? trim($_GET['EndDate']) : date('Y-m-t');

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $StartDate)) {
    $StartDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $EndDate)) {
    $EndDate = date('Y-m-t');
}

// Check if searching by specific TicketID
$TicketIDSearch = isset($_GET['TicketID']) ? trim($_GET['TicketID']) : '';

// Get and validate CorporateID and BranchID
$CorporateID = isset($_GET['CorporateID']) ? $_GET['CorporateID'] : -1;
$BranchID = isset($_GET['BranchID']) ? $_GET['BranchID'] : -1;

// Convert to integer, handling empty strings and '0'
if ($CorporateID === '' || $CorporateID === null || $CorporateID === '0') {
    $CorporateID = -1;
} else {
    $CorporateID = intval($CorporateID);
}

if ($BranchID === '' || $BranchID === null || $BranchID === '0') {
    $BranchID = -1;
} else {
    $BranchID = intval($BranchID);
}

$filters = array(
    'CorporateID' => $CorporateID,
    'BranchID' => $BranchID,
    'StartDate' => $StartDate,
    'EndDate' => $EndDate,
    'TicketID' => $TicketIDSearch
);

// Log filters for debugging
error_log("PPM Billing Filters: CorporateID=" . $CorporateID . ", BranchID=" . $BranchID . ", StartDate=" . $StartDate . ", EndDate=" . $EndDate);

$result = GetPPMTicketsForBilling($conn, $filters);

if ($result['error'] == false) {
    $response['data'] = isset($result['data']) ? $result['data'] : array();
    $response['message'] = "Billing tickets fetched successfully";
    $response['count'] = count($response['data']);
    
    // Always include debug info for date range verification
    // Count tickets directly from database with same filters
    $count_sql = "SELECT COUNT(*) as total FROM ppm_tickets pt 
        WHERE pt.IsActive = 1 
        AND pt.PPMDate >= '" . mysqli_real_escape_string($conn, $filters['StartDate']) . "' 
        AND pt.PPMDate <= '" . mysqli_real_escape_string($conn, $filters['EndDate']) . "'";
    
    if ($filters['CorporateID'] != -1) {
        $count_sql .= " AND pt.CorporateID = " . intval($filters['CorporateID']);
    }
    if ($filters['BranchID'] != -1) {
        $count_sql .= " AND pt.BranchID = " . intval($filters['BranchID']);
    }
    
    $count_result = mysqli_query($conn, $count_sql);
    $direct_count = 0;
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $direct_count = $count_row['total'];
    }
    
    // Get breakdown by status
    $status_sql = "SELECT pt.Status, COUNT(*) as count FROM ppm_tickets pt 
        WHERE pt.IsActive = 1 
        AND pt.PPMDate >= '" . mysqli_real_escape_string($conn, $filters['StartDate']) . "' 
        AND pt.PPMDate <= '" . mysqli_real_escape_string($conn, $filters['EndDate']) . "'";
    
    if ($filters['CorporateID'] != -1) {
        $status_sql .= " AND pt.CorporateID = " . intval($filters['CorporateID']);
    }
    if ($filters['BranchID'] != -1) {
        $status_sql .= " AND pt.BranchID = " . intval($filters['BranchID']);
    }
    $status_sql .= " GROUP BY pt.Status";
    
    $status_result = mysqli_query($conn, $status_sql);
    $status_breakdown = array();
    if ($status_result) {
        while ($status_row = mysqli_fetch_assoc($status_result)) {
            $status_breakdown[$status_row['Status']] = $status_row['count'];
        }
    }
    
    $response['debug_info'] = array(
        'filters_applied' => $filters,
        'result_count' => count($response['data']),
        'direct_db_count' => $direct_count,
        'date_range' => $filters['StartDate'] . ' to ' . $filters['EndDate'],
        'status_breakdown' => $status_breakdown,
        'sql_query' => isset($result['sql_debug']) ? $result['sql_debug'] : 'N/A'
    );
    
    // Debug info
    if (empty($response['data'])) {
        $response['message'] = "No tickets found for the selected filters";
        $response['debug'] = array(
            'CorporateID' => $filters['CorporateID'],
            'BranchID' => $filters['BranchID'],
            'StartDate' => $filters['StartDate'],
            'EndDate' => $filters['EndDate'],
            'StartDateType' => gettype($filters['StartDate']),
            'EndDateType' => gettype($filters['EndDate']),
            'StartDateLength' => strlen($filters['StartDate']),
            'EndDateLength' => strlen($filters['EndDate'])
        );
        
        // Test query to see if there are any tickets in the date range (exact match)
        $test_sql = "SELECT COUNT(*) as total FROM ppm_tickets WHERE IsActive = 1 AND PPMDate >= '" . mysqli_real_escape_string($conn, $filters['StartDate']) . "' AND PPMDate <= '" . mysqli_real_escape_string($conn, $filters['EndDate']) . "'";
        $test_result = mysqli_query($conn, $test_sql);
        if ($test_result) {
            $test_row = mysqli_fetch_assoc($test_result);
            $response['debug']['total_tickets_in_range'] = $test_row['total'];
        }
        
        // Test query with joins to see how many would be returned
        $test_sql_with_joins = "SELECT COUNT(*) as total FROM ppm_tickets pt 
            LEFT JOIN branch_assets ba ON pt.BranchAssetID = ba.ID 
            LEFT JOIN company c ON pt.CorporateID = c.ID 
            LEFT JOIN branch b ON pt.BranchID = b.ID 
            WHERE pt.IsActive = 1 AND pt.PPMDate >= '" . mysqli_real_escape_string($conn, $filters['StartDate']) . "' AND pt.PPMDate <= '" . mysqli_real_escape_string($conn, $filters['EndDate']) . "'";
        $test_result_joins = mysqli_query($conn, $test_sql_with_joins);
        if ($test_result_joins) {
            $test_row_joins = mysqli_fetch_assoc($test_result_joins);
            $response['debug']['total_tickets_with_joins'] = $test_row_joins['total'];
        }
        
        // Also check total tickets in 2025
        // $test_2025_sql = "SELECT COUNT(*) as total FROM ppm_tickets WHERE IsActive = 1 AND YEAR(PPMDate) = 2025";
        // $test_2025_result = mysqli_query($conn, $test_2025_sql);
        // if ($test_2025_result) {
        //     $test_2025_row = mysqli_fetch_assoc($test_2025_result);
        //     $response['debug']['total_tickets_2025'] = $test_2025_row['total'];
        // }
        
        // Check date range
        $date_range_sql = "SELECT MIN(PPMDate) as min_date, MAX(PPMDate) as max_date FROM ppm_tickets WHERE IsActive = 1";
        $date_range_result = mysqli_query($conn, $date_range_sql);
        if ($date_range_result) {
            $date_range_row = mysqli_fetch_assoc($date_range_result);
            $response['debug']['available_date_range'] = array(
                'min_date' => $date_range_row['min_date'],
                'max_date' => $date_range_row['max_date']
            );
        }
    }
} else {
    $response['error'] = true;
    $response['message'] = isset($result['message']) ? $result['message'] : "Error fetching tickets";
    $response['data'] = array();
    $response['count'] = 0;
    $response['debug'] = array(
        'error_details' => isset($result['sql_error']) ? $result['sql_error'] : 'N/A',
        'filters' => $filters
    );
}

echo json_encode($response);
?>
