<?php
$portalAdminBase = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin'))), '/');
if ($portalAdminBase === '' || $portalAdminBase === '.') {
    $portalAdminBase = '/admin';
}
?>
<!-- app notification -->
<div id="portal-notification-wrap">
    <a href="#" class="header-icon" data-toggle="dropdown" title="Notifications" id="portal-notification-toggle">
        <i class="fal fa-bell"></i>
        <span class="badge badge-icon bg-danger" id="portal-notification-count" style="display:none;">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-animated dropdown-xl">
        <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top mb-2">
            <h4 class="m-0 text-center color-white">
                Notifications
                <small class="mb-0 opacity-80 d-block">Portal &amp; broadcasts</small>
            </h4>
        </div>
        <div class="custom-scroll" style="max-height: 360px; overflow-y: auto;">
            <ul class="notification m-0" id="portal-notification-list">
                <li class="p-3 text-center text-muted">Loading...</li>
            </ul>
        </div>
        <div class="py-2 px-3 bg-faded d-block rounded-bottom text-right border-faded border-bottom-0 border-right-0 border-left-0">
            <?php if (isset($_SESSION['UserType']) && in_array($_SESSION['UserType'], array('Admin', 'Super Admin'), true)) { ?>
                <a href="<?php echo htmlspecialchars($portalAdminBase); ?>/notifications/view-notifications.php" class="fs-xs fw-500">Send notifications</a>
            <?php } elseif (isset($_SESSION['Roles']['EmployeeRoles']) && in_array('HR', $_SESSION['Roles']['EmployeeRoles'])) { ?>
                <a href="<?php echo htmlspecialchars($portalAdminBase); ?>/notifications/view-notifications.php" class="fs-xs fw-500">Send notifications</a>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    window.TechXpertPortalNotifications = {
        listUrl: '<?php echo htmlspecialchars($portalAdminBase); ?>/notifications/action/get_portal_notifications.php',
        seenUrl: '<?php echo htmlspecialchars($portalAdminBase); ?>/notifications/action/mark_notification_seen.php'
    };
</script>
