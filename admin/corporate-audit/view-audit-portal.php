<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/corporate_audit_controller.php');
    setNavigation($_SESSION['Roles']);
    $UserType = SessionCheck();
    $conn = _connectodb();

    $baseUrl = defined('FRONT_SITE_PATH') ? rtrim(FRONT_SITE_PATH, '/') : '';
    $hierarchy = getCorporateAuditHierarchy($conn, true, $baseUrl);
    ?>
    <meta charset="utf-8">
    <title>Corporate Audit Portal - Aryadibusiness</title>
    <?php include('../includes/common_head_content.php'); ?>
    <style>
    .audit-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff; height: 100%; }
    .audit-card .audit-icon { font-size: 2rem; color: #003f88; min-height: 48px; }
    .audit-card img { max-height: 48px; }
    .sub-audit-block { border-left: 3px solid #003f88; padding-left: 12px; margin: 12px 0; }
    .checkpoint-item { background: #f8f9fa; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; }
    .ideal-badge { background: #e8f4fd; color: #003f88; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; }
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Corporate Audit</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Corporate Audit Modules</h2>
                        </div>
                        <div class="panel-content">
                            <?php if (empty($hierarchy)) { ?>
                            <div class="alert alert-info">No audit modules configured yet. Please contact your administrator.</div>
                            <?php } else { ?>
                            <div class="row">
                                <?php foreach ($hierarchy as $audit) { ?>
                                <div class="col-md-4 mb-3">
                                    <div class="audit-card">
                                        <div class="audit-icon mb-2">
                                            <?php if (!empty($audit['IconClass'])) { ?>
                                            <i class="<?php echo htmlspecialchars($audit['IconClass']); ?>"></i>
                                            <?php } elseif (!empty($audit['icon_image_url'])) { ?>
                                            <img src="<?php echo htmlspecialchars($audit['icon_image_url']); ?>" alt="">
                                            <?php } else { ?>
                                            <i class="fa-solid fa-clipboard-check"></i>
                                            <?php } ?>
                                        </div>
                                        <h5><?php echo htmlspecialchars($audit['AuditName']); ?></h5>
                                        <?php if (!empty($audit['AuditDescription'])) { ?>
                                        <p class="text-muted small"><?php echo htmlspecialchars($audit['AuditDescription']); ?></p>
                                        <?php } ?>
                                        <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#audit-detail-<?php echo (int) $audit['ID']; ?>">
                                            View Sub Modules (<?php echo count($audit['sub_audits']); ?>)
                                        </button>
                                        <div class="collapse mt-3" id="audit-detail-<?php echo (int) $audit['ID']; ?>">
                                            <?php if (empty($audit['sub_audits'])) { ?>
                                            <p class="text-muted small mb-0">No sub modules yet.</p>
                                            <?php } ?>
                                            <?php foreach ($audit['sub_audits'] as $sub) { ?>
                                            <div class="sub-audit-block">
                                                <strong>
                                                    <?php if (!empty($sub['IconClass'])) { ?>
                                                    <i class="<?php echo htmlspecialchars($sub['IconClass']); ?>"></i>
                                                    <?php } ?>
                                                    <?php echo htmlspecialchars($sub['SubAuditName']); ?>
                                                </strong>
                                                <?php if (!empty($sub['SubAuditDescription'])) { ?>
                                                <div class="text-muted small"><?php echo htmlspecialchars($sub['SubAuditDescription']); ?></div>
                                                <?php } ?>
                                                <?php if (!empty($sub['checklists'])) { ?>
                                                <div class="mt-2">
                                                    <?php foreach ($sub['checklists'] as $cp) { ?>
                                                    <div class="checkpoint-item">
                                                        <div><strong><?php echo htmlspecialchars($cp['CheckpointName']); ?></strong>
                                                            <?php if ((int) $cp['IsMandatory'] === 1) { ?>
                                                            <span class="badge badge-primary ml-1">Required</span>
                                                            <?php } ?>
                                                        </div>
                                                        <?php if (!empty($cp['CheckpointDescription'])) { ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($cp['CheckpointDescription']); ?></div>
                                                        <?php } ?>
                                                        <?php if (!empty($cp['IdealValue'])) { ?>
                                                        <div class="mt-1">Ideal: <span class="ideal-badge"><?php echo htmlspecialchars($cp['IdealValue']); ?><?php echo $cp['Unit'] ? ' ' . htmlspecialchars($cp['Unit']) : ''; ?></span></div>
                                                        <?php } ?>
                                                        <?php if (!empty($cp['HelpText'])) { ?>
                                                        <div class="small text-info mt-1"><?php echo htmlspecialchars($cp['HelpText']); ?></div>
                                                        <?php } ?>
                                                    </div>
                                                    <?php } ?>
                                                </div>
                                                <?php } else { ?>
                                                <p class="text-muted small mb-0">No checkpoints defined.</p>
                                                <?php } ?>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/corporate-audit-portal.js"></script>
</body>
</html>
