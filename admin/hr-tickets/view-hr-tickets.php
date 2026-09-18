<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('controller/hr_tickets_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    hr_ticket_require_manage_access();
    $conn = _connectodb();
    $hr = new Hrticket($conn);

    $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
    $filterCategory = isset($_GET['category']) ? trim($_GET['category']) : '';
    $filters = [];
    if ($filterStatus !== '') $filters['status'] = $filterStatus;
    if ($filterCategory !== '') $filters['category'] = $filterCategory;
    $tickets = $hr->listAllTickets($filters);
    $hr_reps = hr_ticket_hr_representative_list($conn);

    $statOpen = 0;
    $statPayment = 0;
    $statUnassigned = 0;
    foreach ($tickets as $t) {
        if (($t['status'] ?? '') === 'open') $statOpen++;
        if (($t['category'] ?? '') === 'payment_related') $statPayment++;
        if ((int) ($t['assigned_to'] ?? 0) < 1) $statUnassigned++;
    }
    ?>
    <meta charset="utf-8">
    <title>HR Ticket Management</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" href="css/hr-tickets.css">
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content hr-module">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">HR Tickets</li>
                    </ol>

                    <div class="hr-page-header">
                        <div>
                            <h1>HR Ticket Management</h1>
                            <p class="hr-subtitle">Review, assign, and respond to employee HR concerns</p>
                        </div>
                    </div>

                    <div class="hr-stats">
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= count($tickets); ?></div><div class="hr-stat-label">Total</div></div>
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= (int) $statOpen; ?></div><div class="hr-stat-label">Open</div></div>
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= (int) $statPayment; ?></div><div class="hr-stat-label">Payment related</div></div>
                        <div class="hr-stat-card"><div class="hr-stat-value"><?= (int) $statUnassigned; ?></div><div class="hr-stat-label">Unassigned</div></div>
                    </div>

                    <div class="hr-card mb-3">
                        <div class="hr-card-body">
                            <form method="get" class="form-inline flex-wrap">
                                <label class="mr-2 mb-2">Status</label>
                                <select name="status" class="form-control mr-3 mb-2">
                                    <option value="">All</option>
                                    <?php foreach (hr_ticket_status_options() as $st) { ?>
                                    <option value="<?= htmlspecialchars($st); ?>"<?= $filterStatus === $st ? ' selected' : ''; ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $st))); ?></option>
                                    <?php } ?>
                                </select>
                                <label class="mr-2 mb-2">Category</label>
                                <select name="category" class="form-control mr-3 mb-2">
                                    <option value="">All</option>
                                    <?php foreach (hr_ticket_category_options() as $cat) { ?>
                                    <option value="<?= htmlspecialchars($cat); ?>"<?= $filterCategory === $cat ? ' selected' : ''; ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cat))); ?></option>
                                    <?php } ?>
                                </select>
                                <button type="submit" class="btn btn-primary mb-2">Filter</button>
                                <a href="view-hr-tickets" class="btn btn-link mb-2">Clear</a>
                            </form>
                        </div>
                    </div>

                    <div class="hr-card">
                        <div class="hr-card-header"><strong>All employee tickets</strong></div>
                        <div class="hr-card-body flush">
                            <table id="hr_manage_tickets_table" class="table table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Employee</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Assigned</th>
                                        <th>Created</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $t) { ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['ticket_code'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($t['employee_name'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($t['subject'] ?? ''); ?></td>
                                        <td><?= hr_ticket_category_badge($t['category'] ?? ''); ?></td>
                                        <td><?= hr_ticket_status_badge($t['status'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($t['assigned_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($t['created_at'] ?? ''); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(hr_ticket_detail_url((int) ($t['id'] ?? 0))); ?>">
                                                <i class="fal fa-eye"></i> Manage
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/hr-tickets.js"></script>
</body>
</html>
