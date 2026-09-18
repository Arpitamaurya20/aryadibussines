<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include('../controllers/common_controllers.php');
        include('controller/attendance_controller.php');
        include('../employees/controller/employee_controller.php');

        $UserType = SessionCheck();
        $conn = _connectodb();
        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>Employee Attendance</title>
    <meta name="description" content="Employee Attendance Check-in/Check-out">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">

    <style>
    /* Professional Clean Design */
    .attendance-wrapper {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 40px 20px;
    }

    .attendance-container {
        max-width: 600px;
        margin: 0 auto;
    }

    .attendance-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 40px;
        margin-bottom: 30px;
        border: 1px solid #e8ecf0;
        position: relative;
        overflow: hidden;
    }

    .attendance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .attendance-header {
        text-align: center;
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 1px solid #f0f2f5;
    }

    .attendance-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }

    .current-date {
        font-size: 1rem;
        color: #7f8c8d;
        margin-bottom: 15px;
        font-weight: 500;
    }

    .current-time {
        font-size: 1.4rem;
        font-weight: 600;
        color: #34495e;
        background: #f8f9fa;
        padding: 12px 24px;
        border-radius: 8px;
        display: inline-block;
        border: 1px solid #e9ecef;
    }

    /* Status Cards */
    .status-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
    }

    .status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .status-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .status-row:first-child {
        padding-top: 0;
    }

    .status-label {
        font-size: 0.95rem;
        color: #6c757d;
        font-weight: 500;
    }

    .status-value {
        font-size: 1rem;
        font-weight: 600;
    }

    .status-pending {
        color: #fd7e14;
        background: #fff3cd;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .status-completed {
        color: #198754;
        background: #d1e7dd;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    /* Action Button */
    .action-button {
        width: 100%;
        padding: 16px 32px;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: none;
        letter-spacing: 0.5px;
        margin-top: 20px;
        position: relative;
        overflow: hidden;
    }

    .btn-checkin {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-checkin:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    .btn-checkout {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .btn-checkout:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }

    .action-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Modal Styles */
    .attendance-modal .modal-content {
        border-radius: 16px;
        border: none;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 24px 32px;
    }

    .modal-title {
        font-size: 1.4rem;
        font-weight: 600;
        margin: 0;
    }

    .modal-body {
        padding: 32px;
        background: #ffffff;
    }

    /* Step Progress */
    .step-progress {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }

    .step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .step-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 1.2rem;
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        color: #6c757d;
    }

    .step-icon.active {
        background: #667eea;
        border-color: #667eea;
        color: white;
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .step-icon.completed {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }

    .step-icon.error {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .step-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 4px;
        color: #495057;
    }

    .step-description {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .step-connector {
        position: absolute;
        top: 25px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: #e9ecef;
        z-index: 1;
    }

    .step-connector.completed {
        background: #28a745;
    }

    /* Status Messages */
    .status-message {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 16px;
        margin: 20px 0;
        border-left: 4px solid #667eea;
        font-size: 0.95rem;
    }

    .status-message.success {
        background: #d1e7dd;
        border-left-color: #28a745;
        color: #0f5132;
    }

    .status-message.error {
        background: #f8d7da;
        border-left-color: #dc3545;
        color: #721c24;
    }

    .status-message.info {
        background: #cff4fc;
        border-left-color: #0dcaf0;
        color: #055160;
    }

    /* Camera Styles */
    .camera-container {
        position: relative;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        margin: 24px 0;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    #camera-video {
        width: 100%;
        height: 320px;
        object-fit: cover;
    }

    .camera-controls {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 16px;
    }

    .btn-camera {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .btn-capture {
        background: #28a745;
        color: white;
    }

    .btn-capture:hover {
        background: #218838;
        transform: scale(1.1);
    }

    .btn-cancel {
        background: #dc3545;
        color: white;
    }

    .btn-cancel:hover {
        background: #c82333;
        transform: scale(1.1);
    }

    .btn-retake {
        background: #ffc107;
        color: #212529;
    }

    .btn-retake:hover {
        background: #e0a800;
        transform: scale(1.1);
    }

    .captured-image {
        width: 100%;
        height: 320px;
        object-fit: cover;
        border-radius: 12px;
    }

    /* Loading Animation */
    .loading-spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
        margin-right: 8px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        color: #28a745;
    }

    .empty-state h4 {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: #495057;
    }

    .empty-state p {
        font-size: 0.95rem;
        margin: 0;
    }

    /* Debug Info */
    .debug-info {
        background: #f8f9fa;
        padding: 12px 16px;
        border-radius: 6px;
        font-size: 0.8rem;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
        color: #6c757d;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .attendance-wrapper {
            padding: 20px 15px;
        }
        
        .attendance-card {
            padding: 24px;
        }
        
        .attendance-title {
            font-size: 1.6rem;
        }
        
        .step-progress {
            flex-direction: column;
            gap: 24px;
        }
        
        .step-connector {
            display: none;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-header {
            padding: 20px 24px;
        }
    }

    /* Professional Icons */
    .status-icon {
        width: 20px;
        height: 20px;
        margin-right: 8px;
        vertical-align: middle;
    }

    /* Smooth Transitions */
    * {
        transition: all 0.3s ease;
    }

    /* Focus States */
    .action-button:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
    }
    </style>
</head>
<?php

    $UserType = SessionCheck();
    date_default_timezone_set('Asia/Kolkata');

    $EmployeesID = "N.A.";
    if(!isset($_SESSION['Roles']))
    {
    }
    else
    {
        $EmployeesID = $_SESSION['Roles']['EmployeeID'];
        
    }

    $EmployeesData = GetAllEmployeeInArray($conn);
    $employee_array_key = generateArraywithKey($EmployeesData);
    
    // Get today's attendance status
    $today = date("Y-m-d");
    $attendance_status = null;
    
    // Debug information
    $debug_info = "EmployeeID: " . $EmployeesID . " | Today: " . $today;
    
    $attendance_location_policy = [
        'boundaryEnabled' => false,
        'latitude' => null,
        'longitude' => null,
        'radiusMeters' => 100,
    ];

    $branch_attendance_locations = [];

    $checkout_eligibility = [
        'canCheckout' => false,
        'hoursWorked' => 0,
        'message' => '',
    ];

    if($EmployeesID != "N.A.") {
        $attendance_location_policy = getEmployeeAttendanceLocationPolicy($conn, $EmployeesID);
        $branch_attendance_locations = getBranchAttendanceLocationsApiData($conn, $EmployeesID);
        $where = " where EmployeeID = '$EmployeesID' AND RecordDate = '$today'";
        $attendance_status = _getTableDetails($conn,'employee_attendance', $where);
        if ($attendance_status && !empty($attendance_status['InTime']) && empty($attendance_status['OutTime'])) {
            $checkout_eligibility = getEmployeeCheckoutEligibility($conn, $EmployeesID);
        }
        
        // Additional debug info
        $debug_info .= " | Query: " . $where;
        if($attendance_status) {
            $debug_info .= " | Found: Yes | InTime: " . ($attendance_status['InTime'] ?? 'null') . " | OutTime: " . ($attendance_status['OutTime'] ?? 'null');
        } else {
            $debug_info .= " | Found: No";
        }
    }

    ?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->

    <div class="page-wrapper">
        <div class="page-inner">
            <?php
                include('../navigation/admin_navigation.php');
                ?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php
                        include('../includes/common_header.php');
                    ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">Employee Attendance</li>
                    </ol>

                    <!-- Debug Info (remove this in production) -->
                    <!-- <div class="debug-info">
                        <?php echo $debug_info; ?>
                    </div> -->

                    <!-- Professional Attendance Wrapper -->
                    <div class="attendance-wrapper">
                        <div class="attendance-container">
                            <div class="attendance-card">
                                <div class="attendance-header">
                                    <h1 class="attendance-title">My Attendance</h1>
                                    <div class="current-date"><?php echo date('l, F j, Y'); ?></div>
                                    <div class="current-time" id="current-time">
                                        <!-- Time will be updated by JavaScript -->
                                    </div>
                                </div>

                                <?php if (!empty($attendance_location_policy['boundaryEnabled'])): ?>
                                <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.9rem;">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <strong>Check-in &amp; check-out:</strong> you must be within
                                    <strong><?= (int) $attendance_location_policy['radiusMeters']; ?> m</strong>
                                    of your <strong>assigned work location</strong><?php if (!empty($branch_attendance_locations)): ?>
                                    or any active branch<?php elseif (empty($attendance_location_policy['latitude']) || empty($attendance_location_policy['longitude'])): ?>
                                    (branch locations will be used when work location is not set)<?php endif; ?>.
                                    <?php if (!empty($branch_attendance_locations)): ?>
                                    <br><span class="text-muted"><?= count($branch_attendance_locations); ?> branch location(s) available.</span>
                                    <?php endif; ?>
                                    Half-day or full-day check-out is allowed after check-in.
                                </div>
                                <?php endif; ?>

                                <?php if($attendance_status): ?>
                                    <!-- Show attendance details -->
                                    <div class="status-card">
                                        <div class="status-row">
                                            <span class="status-label">
                                                <i class="fas fa-sign-in-alt status-icon"></i>Check-in Time:
                                            </span>
                                            <span class="status-value <?php echo $attendance_status['InTime'] ? 'status-completed' : 'status-pending'; ?>">
                                                <?php echo $attendance_status['InTime'] ? $attendance_status['InTime'] : 'Not checked in'; ?>
                                            </span>
                                        </div>
                                        <div class="status-row">
                                            <span class="status-label">
                                                <i class="fas fa-sign-out-alt status-icon"></i>Check-out Time:
                                            </span>
                                            <span class="status-value <?php echo $attendance_status['OutTime'] ? 'status-completed' : 'status-pending'; ?>">
                                                <?php echo $attendance_status['OutTime'] ? $attendance_status['OutTime'] : 'Not checked out'; ?>
                                            </span>
                                        </div>
                                        <?php if($attendance_status['InTime'] && $attendance_status['OutTime']): ?>
                                            <div class="status-row">
                                                <span class="status-label">
                                                    <i class="fas fa-clock status-icon"></i>Total Hours:
                                                </span>
                                                <span class="status-value status-completed">
                                                    <?php 
                                                        $inTime = new DateTime($attendance_status['InTime']);
                                                        $outTime = new DateTime($attendance_status['OutTime']);
                                                        $diff = $outTime->diff($inTime);
                                                        echo $diff->format('%h hours %i minutes');
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if($attendance_status['InTime'] && !$attendance_status['OutTime']): ?>
                                        <?php if (!empty($checkout_eligibility['hoursWorked'])): ?>
                                        <p class="text-muted small text-center mb-2">
                                            <i class="fas fa-clock"></i>
                                            Time since check-in: <strong><?= $checkout_eligibility['hoursWorked']; ?> hrs</strong>
                                        </p>
                                        <?php endif; ?>
                                        <button class="action-button btn-checkout" onclick="openAttendanceModal('checkout')">
                                            <i class="fas fa-sign-out-alt"></i> Check Out
                                        </button>
                                    <?php elseif(!$attendance_status['InTime']): ?>
                                        <!-- Not checked in yet, show checkin button -->
                                        <button class="action-button btn-checkin" onclick="openAttendanceModal('checkin')">
                                            <i class="fas fa-sign-in-alt"></i> Check In
                                        </button>
                                    <?php else: ?>
                                        <!-- Attendance completed for today -->
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <h4>Attendance Complete</h4>
                                            <p>You have completed your attendance for today.</p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- No attendance record for today -->
                                    <div class="status-card">
                                        <div class="status-row">
                                            <span class="status-label">
                                                <i class="fas fa-info-circle status-icon"></i>Status:
                                            </span>
                                            <span class="status-value status-pending">No attendance record</span>
                                        </div>
                                    </div>
                                    <button class="action-button btn-checkin" onclick="openAttendanceModal('checkin')">
                                        <i class="fas fa-sign-in-alt"></i> Check In
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </main>

                <?php
                        include('../includes/common_footer.php')
                    ?>
                <!-- END Page Footer -->

            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->

    <!-- Professional Attendance Modal -->
    <div class="modal fade attendance-modal" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceModalLabel">Employee Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step Progress -->
                    <div class="step-progress">
                        <div class="step">
                            <div class="step-icon" id="location-step">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="step-title">Location</div>
                            <div class="step-description">Get Current Location</div>
                        </div>
                        
                        <div class="step">
                            <div class="step-icon" id="camera-step">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="step-title">Selfie</div>
                            <div class="step-description">Take Your Photo</div>
                        </div>
                        
                        <div class="step">
                            <div class="step-icon" id="action-step">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="step-title">Complete</div>
                            <div class="step-description">Submit Attendance</div>
                        </div>
                    </div>

                    <!-- Status Messages -->
                    <div id="status-messages"></div>

                    <!-- Camera Section -->
                    <div id="camera-section" style="display: none;">
                        <div class="camera-container">
                            <video id="camera-video" autoplay></video>
                            <canvas id="camera-canvas" style="display: none;"></canvas>
                            <img id="captured-image" class="captured-image" style="display: none;">
                            <div class="camera-controls">
                                <button class="btn-camera btn-capture" id="capture-btn" onclick="capturePhoto()">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <button class="btn-camera btn-cancel" onclick="closeCamera()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="text-center mt-4">
                        <button class="action-button" id="modal-action-btn" onclick="submitAttendance()" disabled>
                            <span class="loading-spinner"></span> Processing...
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/modules/attendance-list.js"></script>

    <script>
    // Global variables
    let currentLocation = null;
    let capturedImage = null;
    let currentProcess = null; // 'checkin' or 'checkout'
    let stream = null;
    let currentStep = 0;
    const employeeId = <?= $EmployeesID != 'N.A.' ? (int) $EmployeesID : 0; ?>;
    const attendanceLocationPolicy = <?= json_encode($attendance_location_policy ?? ['boundaryEnabled' => false]); ?>;
    const branchAttendanceLocations = <?= json_encode($branch_attendance_locations ?? []); ?>;
    const checkoutEligibility = <?= json_encode($checkout_eligibility ?? ['canCheckout' => false]); ?>;

    function haversineMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const p1 = lat1 * Math.PI / 180;
        const p2 = lat2 * Math.PI / 180;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(p1) * Math.cos(p2) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function getEffectiveAttendanceRadius(baseRadius, gpsAccuracy) {
        const buffer = 25;
        const accuracy = Math.min(Math.max(0, parseFloat(gpsAccuracy) || 0), 50);
        return (parseInt(baseRadius, 10) || 100) + Math.max(buffer, accuracy);
    }

    function employeeHasConfiguredAttendanceLocation() {
        const p = attendanceLocationPolicy;
        const lat = p.latitude;
        const lng = p.longitude;
        if (lat === null || lng === null || lat === '' || lng === '') {
            return false;
        }
        const latNum = parseFloat(lat);
        const lngNum = parseFloat(lng);
        if (isNaN(latNum) || isNaN(lngNum) || latNum === 0 || lngNum === 0) {
            return false;
        }
        return true;
    }

    function findNearestAllowedAttendanceLocation(lat, lng, includeBranches, gpsAccuracy) {
        const p = attendanceLocationPolicy;
        const radius = parseInt(p.radiusMeters, 10) || 100;
        const effectiveRadius = getEffectiveAttendanceRadius(radius, gpsAccuracy);
        let nearestMatch = null;
        let nearestDistance = null;

        function considerLocation(distance, matchData) {
            if (distance <= effectiveRadius) {
                if (!nearestMatch || distance < nearestMatch.distanceMeters) {
                    nearestMatch = matchData;
                }
            } else if (nearestDistance === null || distance < nearestDistance) {
                nearestDistance = distance;
            }
        }

        if (employeeHasConfiguredAttendanceLocation()) {
            const dist = haversineMeters(lat, lng, parseFloat(p.latitude), parseFloat(p.longitude));
            considerLocation(dist, {
                allowed: true,
                locationType: 'employee',
                distanceMeters: Math.round(dist),
                message: 'Within assigned work location (' + Math.round(dist) + ' m away).',
            });
        } else if (!includeBranches) {
            return {
                allowed: false,
                message: 'Your attendance location is not configured. Contact HR.',
            };
        }

        if (includeBranches) {
            branchAttendanceLocations.forEach(function (branch) {
                if (!branch.Latitude || !branch.Longitude) {
                    return;
                }
                const dist = haversineMeters(lat, lng, parseFloat(branch.Latitude), parseFloat(branch.Longitude));
                considerLocation(dist, {
                    allowed: true,
                    locationType: 'branch',
                    BranchID: branch.BranchID,
                    BranchSite: branch.BranchSite,
                    distanceMeters: Math.round(dist),
                    message: 'Within branch ' + branch.BranchSite + ' (' + Math.round(dist) + ' m away).',
                });
            });
        }

        if (nearestMatch) {
            return nearestMatch;
        }

        if (!includeBranches) {
            return {
                allowed: false,
                message: 'You are outside all allowed attendance locations ('
                    + Math.round(nearestDistance || 0) + ' m from nearest location, maximum ' + radius + ' m).',
            };
        }

        const hasEmployeeLocation = employeeHasConfiguredAttendanceLocation();
        const hasBranchLocations = branchAttendanceLocations.length > 0;
        if (!hasEmployeeLocation && !hasBranchLocations) {
            return {
                allowed: false,
                message: 'No attendance locations are configured. Contact HR.',
            };
        }

        return {
            allowed: false,
            message: 'You are outside all allowed attendance locations ('
                + Math.round(nearestDistance || 0) + ' m from nearest location, maximum ' + radius + ' m).',
        };
    }

    function checkLocationPolicyClient(lat, lng, gpsAccuracy) {
        const p = attendanceLocationPolicy;
        if (!p.boundaryEnabled) {
            return Promise.resolve({ allowed: true });
        }
        return Promise.resolve(findNearestAllowedAttendanceLocation(lat, lng, true, gpsAccuracy));
    }

    function checkLocationPolicyServer(lat, lng, gpsAccuracy) {
        return fetch('../../api/check_attendance_geofence.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                EmployeeID: employeeId,
                Latitude: lat,
                Longitude: lng,
                GpsAccuracy: gpsAccuracy || 0,
                Action: currentProcess === 'checkout' ? 'checkout' : 'checkin',
            }),
        }).then(function (r) { return r.json(); });
    }

    // Update current time
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour12: true,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }

    // Update time every second
    setInterval(updateTime, 1000);
    updateTime();

    // Step management functions
    function updateStepStatus(stepId, status) {
        const stepIcon = document.getElementById(stepId);
        if (stepIcon) {
            stepIcon.className = `step-icon ${status}`;
            
            if (status === 'active') {
                stepIcon.style.animation = 'pulse 2s infinite';
            } else {
                stepIcon.style.animation = 'none';
            }
        }
    }

    function showStatusMessage(message, type = 'info') {
        const statusDiv = document.getElementById('status-messages');
        if (!statusDiv) return;
        
        const messageClass = type === 'success' ? 'success' : 
                           type === 'error' ? 'error' : 'info';
        
        statusDiv.innerHTML = `
            <div class="status-message ${messageClass}">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
            </div>
        `;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            statusDiv.innerHTML = '';
        }, 5000);
    }

    // Open attendance modal
    function openAttendanceModal(process) {
        currentProcess = process;
        currentStep = 0;
        currentLocation = null;
        capturedImage = null;
        
        // Reset modal
        resetModal();
        
        // Update modal title
        const modalTitle = document.getElementById('attendanceModalLabel');
        modalTitle.textContent = process === 'checkin' ? 'Check In Process' : 'Check Out Process';
        
        // Update action button
        const actionBtn = document.getElementById('modal-action-btn');
        actionBtn.className = `action-button ${process === 'checkin' ? 'btn-checkin' : 'btn-checkout'}`;
        actionBtn.innerHTML = `<span class="loading-spinner"></span> Processing...`;
        actionBtn.disabled = true;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
        modal.show();
        
        // Location + geofence when boundary enabled; otherwise proceed to selfie.
        setTimeout(function () {
            if (!attendanceLocationPolicy.boundaryEnabled) {
                currentLocation = { latitude: '', longitude: '' };
                updateStepStatus('location-step', 'completed');
                showStatusMessage('Proceeding to selfie...', 'info');
                startCameraProcess();
            } else {
                startLocationProcess();
            }
        }, 500);
    }

    function resetModal() {
        updateStepStatus('location-step', '');
        updateStepStatus('camera-step', '');
        updateStepStatus('action-step', '');
        
        document.getElementById('status-messages').innerHTML = '';
        document.getElementById('camera-section').style.display = 'none';
        
        // Reset camera elements
        const video = document.getElementById('camera-video');
        const capturedImg = document.getElementById('captured-image');
        const captureBtn = document.getElementById('capture-btn');
        
        if (video) video.style.display = 'block';
        if (capturedImg) capturedImg.style.display = 'none';
        if (captureBtn) captureBtn.style.display = 'flex';
        
        const confirmBtn = document.getElementById('confirm-btn');
        if (confirmBtn) {
            confirmBtn.remove();
        }
    }

    // Location functions
   function startLocationProcess() {
    updateStepStatus('location-step', 'active');
    showStatusMessage('Getting your current location...', 'info');

    if (!navigator.geolocation) {
        updateStepStatus('location-step', 'error');
        showStatusMessage('Geolocation is not supported by this browser.', 'error');
        return;
    }

    // First attempt: High accuracy
    const highAccuracyOptions = {
        enableHighAccuracy: true,
        timeout: 20000,   // Increased timeout
        maximumAge: 0
    };

    // Second attempt fallback: Low accuracy
    const lowAccuracyOptions = {
        enableHighAccuracy: false,
        timeout: 15000,
        maximumAge: 0
    };

    // 🔄 Retry logic
    function tryHighAccuracy() {
        navigator.geolocation.getCurrentPosition(
            handleSuccess,
            function(err) {
                console.warn("High accuracy failed:", err);

                // Retry with low accuracy
                showStatusMessage('Retrying with basic location...', 'info');
                setTimeout(() => tryLowAccuracy(), 500);
            },
            highAccuracyOptions
        );
    }

    function tryLowAccuracy() {
        navigator.geolocation.getCurrentPosition(
            handleSuccess,
            handleError,
            lowAccuracyOptions
        );
    }

    // SUCCESS handling — geofence for check-in and check-out when boundary enabled
    function handleSuccess(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const gpsAccuracy = position.coords.accuracy || 0;

        currentLocation = { latitude: lat, longitude: lng, accuracy: gpsAccuracy };

        showStatusMessage('Verifying your location...', 'info');

        checkLocationPolicyClient(lat, lng, gpsAccuracy)
            .then(function (clientResult) {
                if (!clientResult.allowed) {
                    updateStepStatus('location-step', 'error');
                    showStatusMessage(clientResult.message, 'error');
                    return null;
                }
                return checkLocationPolicyServer(lat, lng, gpsAccuracy);
            })
            .then(function (serverResult) {
                if (serverResult === null) {
                    return;
                }
                if (serverResult.error === true && serverResult.message) {
                    updateStepStatus('location-step', 'error');
                    showStatusMessage(serverResult.message, 'error');
                    return;
                }
                if (serverResult.allowed === false) {
                    updateStepStatus('location-step', 'error');
                    showStatusMessage(serverResult.message || 'You are outside the allowed attendance area.', 'error');
                    return;
                }

                updateStepStatus('location-step', 'completed');
                let okMsg = serverResult.message || 'Location verified successfully!';
                if (serverResult.matchedLocation && serverResult.matchedLocation.locationType === 'branch') {
                    okMsg = 'Location verified at branch ' + serverResult.matchedLocation.BranchSite
                        + ' (' + serverResult.matchedLocation.distanceMeters + ' m away).';
                } else if (serverResult.boundaryEnabled && serverResult.distanceMeters !== undefined) {
                    okMsg = 'Location verified (' + serverResult.distanceMeters + ' m from work location).';
                }
                showStatusMessage(okMsg, 'success');
                setTimeout(function () { startCameraProcess(); }, 1200);
            })
            .catch(function () {
                updateStepStatus('location-step', 'error');
                showStatusMessage('Could not verify location. Please try again.', 'error');
            });
    }

    // ❌ ERROR handling
    function handleError(error) {
        updateStepStatus('location-step', 'error');

        let errorMessage = "Unable to retrieve your location. ";

        switch (error.code) {
            case error.PERMISSION_DENIED:
                errorMessage += "Please allow location access in your browser settings.";
                break;

            case error.POSITION_UNAVAILABLE:
                errorMessage += "Location services unavailable. Try moving near a window.";
                break;

            case error.TIMEOUT:
                errorMessage += "Request timed out. Make sure GPS is enabled or disable VPN.";
                break;

            default:
                errorMessage += "Unexpected error occurred.";
        }

        showStatusMessage(errorMessage, 'error');
    }

    // 🚀 Start high accuracy attempt
    tryHighAccuracy();
}


    // Camera functions
    function startCameraProcess() {
        updateStepStatus('camera-step', 'active');
        showStatusMessage('Opening camera...', 'info');
        
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(mediaStream) {
                stream = mediaStream;
                const video = document.getElementById('camera-video');
                if (video) {
                    video.srcObject = stream;
                }
                
                document.getElementById('camera-section').style.display = 'block';
                
                updateStepStatus('camera-step', 'completed');
                showStatusMessage('Camera ready! Take your selfie.', 'success');
            })
            .catch(function(error) {
                updateStepStatus('camera-step', 'error');
                showStatusMessage('Unable to access camera. Please check permissions.', 'error');
                console.error('Camera error:', error);
            });
    }

    function capturePhoto() {
        const video = document.getElementById('camera-video');
        const canvas = document.getElementById('camera-canvas');
        const capturedImg = document.getElementById('captured-image');
        
        if (!video || !canvas || !capturedImg) return;
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        capturedImage = canvas.toDataURL('image/jpeg', 0.8);
        capturedImg.src = capturedImage;
        
        // Hide video and show captured image
        video.style.display = 'none';
        capturedImg.style.display = 'block';
        
        // Hide capture button
        const captureBtn = document.getElementById('capture-btn');
        if (captureBtn) {
            captureBtn.style.display = 'none';
        }
        
        showStatusMessage('Photo captured! Review your photo and click confirm to proceed or retake if needed.', 'success');
        
        // Add confirm and retake buttons
        const controls = document.querySelector('.camera-controls');
        if (controls) {
            // Remove existing buttons first
            const existingConfirmBtn = document.getElementById('confirm-btn');
            const existingRetakeBtn = document.getElementById('retake-btn');
            if (existingConfirmBtn) existingConfirmBtn.remove();
            if (existingRetakeBtn) existingRetakeBtn.remove();
            
            // Add confirm button
            const confirmBtn = document.createElement('button');
            confirmBtn.id = 'confirm-btn';
            confirmBtn.className = 'btn-camera btn-capture';
            confirmBtn.innerHTML = '<i class="fas fa-check"></i>';
            confirmBtn.title = 'Confirm Photo';
            confirmBtn.onclick = confirmPhoto;
            controls.appendChild(confirmBtn);
            
            // Add retake button
            const retakeBtn = document.createElement('button');
            retakeBtn.id = 'retake-btn';
            retakeBtn.className = 'btn-camera btn-cancel';
            retakeBtn.innerHTML = '<i class="fas fa-redo"></i>';
            retakeBtn.title = 'Retake Photo';
            retakeBtn.onclick = retakePhoto;
            controls.appendChild(retakeBtn);
        }
    }

    function retakePhoto() {
        // Reset camera elements
        const video = document.getElementById('camera-video');
        const capturedImg = document.getElementById('captured-image');
        const captureBtn = document.getElementById('capture-btn');
        
        if (video) video.style.display = 'block';
        if (capturedImg) capturedImg.style.display = 'none';
        if (captureBtn) captureBtn.style.display = 'flex';
        
        // Remove confirm and retake buttons
        const confirmBtn = document.getElementById('confirm-btn');
        const retakeBtn = document.getElementById('retake-btn');
        if (confirmBtn) confirmBtn.remove();
        if (retakeBtn) retakeBtn.remove();
        
        // Reset captured image
        capturedImage = null;
        
        // Reset action step status
        updateStepStatus('action-step', '');
        
        // Disable action button
        const actionBtn = document.getElementById('modal-action-btn');
        if (actionBtn) {
            actionBtn.disabled = true;
            actionBtn.innerHTML = `<span class="loading-spinner"></span> Processing...`;
        }
        
        showStatusMessage('Camera ready! Take your selfie again.', 'info');
    }

    function confirmPhoto() {
        if (!capturedImage) {
            showStatusMessage('No photo captured. Please take a photo first.', 'error');
            return;
        }
        
        updateStepStatus('action-step', 'completed');
        showStatusMessage('Photo confirmed! You can now submit attendance.', 'success');
        
        // Enable the action button
        const actionBtn = document.getElementById('modal-action-btn');
        if (actionBtn) {
            actionBtn.disabled = false;
            actionBtn.innerHTML = `<i class="fas fa-${currentProcess === 'checkin' ? 'sign-in-alt' : 'sign-out-alt'}"></i> Submit ${currentProcess === 'checkin' ? 'Check In' : 'Check Out'}`;
        }
    }

    function closeCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        document.getElementById('camera-section').style.display = 'none';
        
        // Reset camera elements
        const video = document.getElementById('camera-video');
        const capturedImg = document.getElementById('captured-image');
        const captureBtn = document.getElementById('capture-btn');
        
        if (video) video.style.display = 'block';
        if (capturedImg) capturedImg.style.display = 'none';
        if (captureBtn) captureBtn.style.display = 'flex';
        
        // Remove all dynamic buttons
        const confirmBtn = document.getElementById('confirm-btn');
        const retakeBtn = document.getElementById('retake-btn');
        if (confirmBtn) confirmBtn.remove();
        if (retakeBtn) retakeBtn.remove();
        
        // Reset captured image
        capturedImage = null;
        
        // Reset action step status
        updateStepStatus('action-step', '');
        
        // Disable action button
        const actionBtn = document.getElementById('modal-action-btn');
        if (actionBtn) {
            actionBtn.disabled = true;
            actionBtn.innerHTML = `<span class="loading-spinner"></span> Processing...`;
        }
    }

    // Submit attendance
    function submitAttendance() {
        if (attendanceLocationPolicy.boundaryEnabled && !currentLocation) {
            showStatusMessage('Location not captured. Please allow location access.', 'error');
            return;
        }
        
        if (!capturedImage) {
            showStatusMessage('Photo not captured. Please take a photo first.', 'error');
            return;
        }

        const actionBtn = document.getElementById('modal-action-btn');
        if (actionBtn) {
            actionBtn.disabled = true;
            actionBtn.innerHTML = `<span class="loading-spinner"></span> Submitting...`;
        }

        const data = {
            EmployeeID: <?php echo $EmployeesID != "N.A." ? $EmployeesID : '-1'; ?>,
            Latitude: currentLocation.latitude,
            Longitude: currentLocation.longitude,
            GpsAccuracy: currentLocation.accuracy || 0,
            imageData: capturedImage.split(',')[1] // Remove data:image/jpeg;base64, prefix
        };

        const url = currentProcess === 'checkin' ? '../../api/punch_in_employee_attendance.php' : '../../api/punch_out_employee_attendance.php';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            console.log(result);
            if (result.error === false) {
                showStatusMessage(result.message, 'success');
                setTimeout(() => {
                    // Close modal and reload page
                    $('#attendanceModal').modal('hide');
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }, 2000);
            } else {
                showStatusMessage(result.message, 'error');
                if (actionBtn) {
                    actionBtn.disabled = false;
                    actionBtn.innerHTML = `<i class="fas fa-${currentProcess === 'checkin' ? 'sign-in-alt' : 'sign-out-alt'}"></i> Submit ${currentProcess === 'checkin' ? 'Check In' : 'Check Out'}`;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showStatusMessage('An error occurred while submitting attendance.', 'error');
            if (actionBtn) {
                actionBtn.disabled = false;
                actionBtn.innerHTML = `<i class="fas fa-${currentProcess === 'checkin' ? 'sign-in-alt' : 'sign-out-alt'}"></i> Submit ${currentProcess === 'checkin' ? 'Check In' : 'Check Out'}`;
            }
        });
    }

    // Make functions globally accessible
    window.openAttendanceModal = openAttendanceModal;
    window.capturePhoto = capturePhoto;
    window.closeCamera = closeCamera;
    window.confirmPhoto = confirmPhoto;
    window.retakePhoto = retakePhoto;
    window.submitAttendance = submitAttendance;
    </script>

</body>

</html>