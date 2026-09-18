<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); 

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include('../controllers/common_controllers.php');
        include('../controllers/portal_notification_controller.php');
        include('../includes/autoloader.inc.php');
        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>Send Notifications - Aryadibusiness</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
</head>
<?php
    $UserType = SessionCheck();
    $roles = $_SESSION['Roles'] ?? array();
    if (!hasPortalNotificationAdminAccess($roles)) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }

    $conn = _connectodb();
    $users_array = pnc_getActivePortalUsers($conn);
    $employees_array = pnc_getActiveEmployeesForNotify($conn);
    $history = pnc_listAdminNotifications($conn, 100);
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Send Notifications</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        Send in-app portal notifications and optional mobile push. Push uses
                        <code>/api/notifications/queue-push.php</code> in the background.
                    </div>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Compose Notification</h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content p-3">
                                        <form id="portal_notification_form" onsubmit="return sendPortalNotification();">
                                            <div class="form-group">
                                                <label>Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="notify_title" maxlength="255" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Message <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="notify_body" rows="4" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Target Audience</label>
                                                <select class="form-control" id="notify_target" onchange="toggleNotificationTargetFields()">
                                                    <option value="all">All Portal Users (broadcast)</option>
                                                    <option value="user">Specific Portal User</option>
                                                    <option value="employee">Specific Employee (app user)</option>
                                                </select>
                                            </div>
                                            <div class="form-group" id="notify_user_wrap" style="display:none;">
                                                <label>Portal User</label>
                                                <select class="select2 form-control w-100" id="notify_user_id">
                                                    <option value="">Select user</option>
                                                    <?php foreach ($users_array as $user) { ?>
                                                        <option value="<?php echo (int) $user['UserID']; ?>">
                                                            <?php echo htmlspecialchars($user['UserName'] . ' (UserID ' . $user['UserID'] . ')'); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group" id="notify_employee_wrap" style="display:none;">
                                                <label>Employee</label>
                                                <select class="select2 form-control w-100" id="notify_employee_id">
                                                    <option value="">Select employee</option>
                                                    <?php foreach ($employees_array as $emp) { ?>
                                                        <option value="<?php echo (int) $emp['ID']; ?>">
                                                            <?php echo htmlspecialchars($emp['Name'] . ' (ID ' . $emp['ID'] . ')'); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>App Screen</label>
                                                    <input type="text" class="form-control" id="notify_screen" value="dashboard">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Module</label>
                                                    <input type="text" class="form-control" id="notify_module" value="general">
                                                </div>
                                            </div>
                                            <div class="custom-control custom-checkbox mb-3">
                                                <input type="checkbox" class="custom-control-input" id="notify_send_push" checked>
                                                <label class="custom-control-label" for="notify_send_push">Also send push notification to mobile app</label>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Send Notification</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Recent Notifications</h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content p-3">
                                        <table id="portal-notification-history" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Target</th>
                                                    <th>Title</th>
                                                    <th>Push</th>
                                                    <th>By</th>
                                                    <th>Sent At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $i = 1;
                                                    foreach ($history as $row) {
                                                        $targetLabel = strtoupper((string) ($row['target_type'] ?? ''));
                                                        if (($row['target_type'] ?? '') === 'user') {
                                                            $targetLabel .= ' #' . (int) ($row['user_id'] ?? 0);
                                                        } elseif (($row['target_type'] ?? '') === 'employee') {
                                                            $targetLabel .= ' #' . (int) ($row['employee_id'] ?? 0);
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo htmlspecialchars($targetLabel); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($row['title'] ?? '')); ?></td>
                                                    <td><?php echo !empty($row['send_push']) ? 'Yes' : 'No'; ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($row['created_by'] ?? '')); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($row['created_at'] ?? '')); ?></td>
                                                </tr>
                                                <?php
                                                        $i++;
                                                    }
                                                    if (count($history) === 0) {
                                                        echo '<tr><td colspan="6" class="text-center text-muted">No notifications sent yet</td></tr>';
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="panel mt-3">
                                <div class="panel-hdr"><h2>API Quick Reference</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content p-3 small">
                                        <p><strong>POST</strong> <code>/api/notifications/queue-push.php</code></p>
                                        <pre class="bg-faded p-2 rounded">{
  "Target": "all",
  "Title": "Company Announcement",
  "Body": "Maintenance tonight 10 PM",
  "SavePortal": true,
  "payload": { "screen": "dashboard", "module": "announcement" }
}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/portal-notifications-admin.js"></script>
    <script>
        $(document).ready(function () {
            $('#notify_user_id, #notify_employee_id').select2();
            $('#portal-notification-history').DataTable({ pageLength: 10, order: [[0, 'asc']] });
            toggleNotificationTargetFields();
        });
    </script>
</body>
</html>
