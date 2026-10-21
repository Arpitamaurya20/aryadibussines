<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'approvals');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpDelegators = fp_policy_employee_names($fpDb, fp_delegators_of($fpDb, $fpActor['employee_id']));

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
    <title>Approvals - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-inbox-table td, #fp-decided-table td { vertical-align: middle; }
        #fp-inbox-table .fp-purpose, #fp-decided-table .fp-comments { max-width: 260px; white-space: normal; }
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
                        <li class="breadcrumb-item active">Approvals</li>
                    </ol>

                    <?php if ($fpDelegators) { ?>
                        <div class="alert alert-info">Today you also approve on behalf of: <strong><?php echo fp_h(implode(', ', $fpDelegators)); ?></strong>. Your decisions record both names.</div>
                    <?php } ?>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Approvals</h2>
                            <div class="panel-toolbar">
                                <button type="button" class="btn btn-outline-secondary btn-sm mr-3" id="fp-refresh"><i class="fa-solid fa-rotate mr-1"></i>Refresh</button>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <ul class="nav nav-tabs mb-3" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#fp-tab-inbox" role="tab">Waiting for me <span class="badge badge-primary ml-1" id="fp-inbox-count">0</span></a></li>
                                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fp-tab-decided" role="tab" id="fp-decided-link">My decisions</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="fp-tab-inbox" role="tabpanel">
                                        <p class="text-muted small">Requests whose current approval stage you can decide now. Open a request to see its lines and documents, then approve, return or reject it.</p>
                                        <table class="table table-bordered table-hover table-striped w-100" id="fp-inbox-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Expense no.</th>
                                                    <th>Requester</th>
                                                    <th>Office</th>
                                                    <th>Purpose</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Stage</th>
                                                    <th>Waiting</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                    <div class="tab-pane fade" id="fp-tab-decided" role="tabpanel">
                                        <p class="text-muted small">Your latest 300 decisions.</p>
                                        <table class="table table-bordered table-hover table-striped w-100" id="fp-decided-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Decided</th>
                                                    <th>Expense no.</th>
                                                    <th>Requester</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Stage</th>
                                                    <th>Decision</th>
                                                    <th>Comments</th>
                                                    <th>Request now</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/fp_common.js"></script>
    <script src="../js/modules/fp_approvals.js"></script>
</body>
</html>
