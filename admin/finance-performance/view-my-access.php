<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_page($conn, 'view');
$UserType = $fpActor['user_type'];
$fpMatrix = fp_access_matrix();
$fpCanConfigure = fp_can($fpActor, 'configure');
$fpScopeLabels = array(
    'all'     => 'All records',
    'self'    => 'Own records',
    'team'    => 'Team (supervised / city lead)',
    'branch'  => 'Mapped client branches',
    'state'   => 'Mapped states',
    'region'  => 'States in mapped regions',
    'company' => 'Mapped client companies',
    'office'  => 'Managed branch offices',
);

setNavigation($_SESSION['Roles'] ?? array());

function fp_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Access - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
    .fp-matrix th, .fp-matrix td { vertical-align: middle; font-size: 12px; }
    .fp-matrix td.fp-yes { color: #1e7e34; font-weight: 600; text-align: center; }
    .fp-matrix td.fp-no { color: #c4c4c4; text-align: center; }
    </style>
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item">Finance &amp; Performance</li>
                        <li class="breadcrumb-item active">My Access</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        Your Finance &amp; Performance access is calculated from your ERP login, your roles and your existing state, branch, team and account assignments. Ask an administrator to update those assignments if anything below is wrong.
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="panel">
                                <div class="panel-hdr"><h2>Signed-in identity</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table class="table table-sm mb-0">
                                            <tr><th>Login</th><td><?php echo fp_h($fpActor['username']); ?></td></tr>
                                            <tr><th>User type</th><td><?php echo fp_h($fpActor['user_type']); ?></td></tr>
                                            <tr>
                                                <th>Employee</th>
                                                <td>
                                                    <?php if ($fpActor['employee_id'] > 0) { ?>
                                                        <?php echo fp_h($fpActor['employee_name']); ?> (ID <?php echo (int) $fpActor['employee_id']; ?>)
                                                    <?php } else { ?>
                                                        <span class="text-danger">No active employee record linked</span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>ERP roles</th>
                                                <td>
                                                    <?php foreach ($fpActor['erp_roles'] as $erpRole) { ?>
                                                        <span class="badge badge-secondary mr-1"><?php echo fp_h($erpRole); ?></span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        </table>
                                        <?php if ($fpActor['employee_id'] <= 0 && $fpMatrix['financial_actions_require_employee']) { ?>
                                            <div class="alert alert-warning mt-3 mb-0 small">Approve and pay actions are disabled for logins without an employee record, so every financial action can be traced to a person.</div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="panel">
                                <div class="panel-hdr"><h2>My permissions</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="thead-light"><tr><th>Permission</th><th>Allowed</th><th>Scope</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($fpMatrix['permissions'] as $permission => $permissionLabel) {
                                                $allowed = fp_can($fpActor, $permission);
                                            ?>
                                                <tr>
                                                    <td><?php echo fp_h($permissionLabel); ?></td>
                                                    <td><span class="badge <?php echo $allowed ? 'badge-success' : 'badge-light'; ?>"><?php echo $allowed ? 'Yes' : 'No'; ?></span></td>
                                                    <td class="small"><?php echo $allowed ? fp_h(fp_scope_summary($fpActor['grants'][$permission])) : '-'; ?></td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-hdr"><h2>How your access was matched</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light"><tr><th>Module role</th><th>Matched by</th><th>Scope</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($fpActor['roles'] as $role) { ?>
                                        <tr>
                                            <td><?php echo fp_h($role['label']); ?></td>
                                            <td class="small"><?php echo fp_h(implode(' | ', $role['matched_by'])); ?></td>
                                            <td class="small">
                                                <?php echo fp_h(fp_scope_summary($role['scope'])); ?>
                                                <?php if (!fp_scope_has_any($role['scope'])) { ?>
                                                    <span class="badge badge-warning ml-1">Role assigned but no <?php echo fp_h(strtolower($fpScopeLabels[$role['scope_type']])); ?></span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if ($fpCanConfigure) { ?>
                    <div class="panel">
                        <div class="panel-hdr"><h2>Access matrix</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content table-responsive">
                                <table class="table table-sm table-bordered fp-matrix mb-2">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Module role</th>
                                            <th>Granted by ERP user type / role</th>
                                            <th>Or by assignment</th>
                                            <th>Scope</th>
                                            <?php foreach ($fpMatrix['permissions'] as $permission => $permissionLabel) { ?>
                                                <th class="text-center"><?php echo fp_h(ucfirst($permission)); ?></th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($fpMatrix['roles'] as $role) { ?>
                                        <tr>
                                            <td><strong><?php echo fp_h($role['label']); ?></strong></td>
                                            <td><?php echo fp_h(implode(', ', array_unique(array_merge($role['user_types'], $role['erp_roles']))) ?: '-'); ?></td>
                                            <td><?php echo fp_h(fp_mapping_label($role['mapping']) ?: '-'); ?></td>
                                            <td><?php echo fp_h($fpScopeLabels[$role['scope']]); ?></td>
                                            <?php foreach ($fpMatrix['permissions'] as $permission => $permissionLabel) {
                                                $has = in_array($permission, $role['permissions'], true);
                                            ?>
                                                <td class="<?php echo $has ? 'fp-yes' : 'fp-no'; ?>"><?php echo $has ? '&#10003;' : '&ndash;'; ?></td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                                <ul class="small text-muted mb-0">
                                    <li>Client portal logins (<?php echo fp_h(implode(', ', $fpMatrix['blocked_user_types'])); ?>) have no access to this module.</li>
                                    <li>Self-approval is <?php echo $fpMatrix['allow_self_approval'] ? 'allowed' : 'blocked'; ?> by default: nobody can approve or pay a request they raised unless an approval policy explicitly allows it.</li>
                                    <li>Approve and pay require a login linked to an active employee record.</li>
                                    <?php if ($fpMatrix['exclude_vendor_employees']) { ?>
                                        <li>Vendor records in the employee master do not get the base employee role.</li>
                                    <?php } ?>
                                    <li>The matrix is maintained in <code>admin/finance-performance/config/access_matrix.php</code>.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-hdr"><h2>Module pages</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light"><tr><th>Page</th><th>Required permission</th><th>Section</th><th>Status</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($fpMatrix['menu'] as $item) {
                                        $available = is_file(__DIR__ . '/' . $item['file']);
                                        $groupKey = $item['group'] ?? '';
                                    ?>
                                        <tr>
                                            <td><i class="<?php echo fp_h($item['icon']); ?> mr-1"></i><?php echo fp_h($item['label']); ?></td>
                                            <td>
                                                <?php echo fp_h(ucfirst($item['permission'])); ?>
                                                <?php echo $item['beyond_self'] ? ' (beyond own records)' : ''; ?>
                                                <?php echo !empty($item['global']) ? ' (all records)' : ''; ?>
                                            </td>
                                            <td><?php echo fp_h($groupKey !== '' ? $fpMatrix['menu_groups'][$groupKey]['label'] : 'Main menu'); ?></td>
                                            <td><span class="badge <?php echo $available ? 'badge-success' : 'badge-light'; ?>"><?php echo $available ? 'Available' : 'Planned'; ?></span></td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
</body>
</html>
