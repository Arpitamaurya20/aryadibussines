<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include('../controllers/common_controllers.php');
    require_once('../includes/autoloader.inc.php');
    include('controller/audit_ticket_controller.php');
    include('../employees/controller/employee_controller.php');
    setNavigation($_SESSION['Roles']);
    $UserType = SessionCheck();
    $conn = _connectodb();

    $ticketDbId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($ticketDbId <= 0 && isset($_SESSION['AuditTicketID'])) {
        $ticketDbId = (int) $_SESSION['AuditTicketID'];
    }

    $ticket = $ticketDbId > 0 ? getCorporateAuditTicketById($conn, $ticketDbId) : null;
    if (!$ticket || !auditTicketUserCanViewTicket($conn, $_SESSION, $ticket)) {
        header('Location: view-audit-tickets');
        exit;
    }

    $checklistItems = getCorporateAuditTicketChecklistWithResponses($conn, $ticketDbId);
    $stats = getAuditTicketCompletionStats($conn, $ticketDbId);
    $ticketAudits = getCorporateAuditTicketAudits($conn, $ticketDbId);
    $groupedChecklists = getAuditTicketChecklistGroupedBySubAudit($conn, $ticketDbId);
    $isMultiAudit = count($ticketAudits) > 1;

    $bamId = isset($ticket['BranchAccountManager']) ? (int) $ticket['BranchAccountManager'] : (int) $ticket['AssignedTo'];
    $technicianId = isset($ticket['Technician']) ? (int) $ticket['Technician'] : -1;
    $bamName = $bamId > 0 ? auditTicketEmployeeName($conn, $bamId) : 'Not set';
    $technicianName = $technicianId > 0 ? auditTicketEmployeeName($conn, $technicianId) : 'Not assigned yet';

    $canManageAssignment = auditTicketUserCanManageAssignment($_SESSION, $ticket);
    $technicianList = getAssignedList($conn);
    $isReassign = $technicianId > 0;
    $progressPct = (float) $stats['completion_percent'];
    ?>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($ticket['TicketID']); ?> - Audit Ticket</title>
    <?php include('../includes/common_head_content.php'); ?>
    <?php include('./include/audit-ticket-ui-styles.php'); ?>
    <style>
    .checkpoint-row-out { background: #fff5f5 !important; }
    .checkpoint-row-ok { background: #f0fff4 !important; }
    .ideal-val { color: #003f88; font-weight: 600; }
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
                        <li class="breadcrumb-item"><a href="view-audit-tickets">Audit Tickets</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($ticket['TicketID']); ?></li>
                    </ol>

                    <div class="at-detail-hero d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <div class="ticket-id"><?php echo htmlspecialchars($ticket['TicketID']); ?></div>
                            <div class="ticket-meta mt-1">
                                <?php if ($isMultiAudit) { ?>
                                    <?php echo count($ticketAudits); ?> audits on this ticket
                                <?php } else { ?>
                                    <?php echo htmlspecialchars($ticket['MasterAuditName']); ?> &rsaquo; <?php echo htmlspecialchars($ticket['SubAuditName']); ?>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="text-right mt-2 mt-md-0">
                            <?php echo auditTicketStatusBadge($ticket['Status']); ?>
                            <?php if (!empty($ticket['LastStatus'])) { ?>
                            <div class="small mt-1">Last: <?php echo htmlspecialchars($ticket['LastStatus']); ?></div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-4">
                            <div class="at-info-card">
                                <div class="card-head"><i class="fal fa-building mr-1"></i> Location & Audit</div>
                                <div class="card-body">
                                    <div class="at-info-row"><span class="at-info-label">Corporate</span><span class="at-info-value"><?php echo htmlspecialchars($ticket['CompanyName']); ?></span></div>
                                    <div class="at-info-row"><span class="at-info-label">Branch</span><span class="at-info-value"><?php echo htmlspecialchars($ticket['BranchSite']); ?></span></div>
                                    <div class="at-info-row"><span class="at-info-label">Address</span><span class="at-info-value text-muted small"><?php echo htmlspecialchars($ticket['BranchAddress1']); ?></span></div>
                                    <div class="at-info-row"><span class="at-info-label">Raised On</span><span class="at-info-value"><?php echo htmlspecialchars($ticket['CreatedDate'] . ' ' . $ticket['CreatedTime']); ?></span></div>
                                    <?php if ($isMultiAudit) { ?>
                                    <div class="at-info-row"><span class="at-info-label">Audits</span><span class="at-info-value text-left small">
                                        <?php foreach ($ticketAudits as $auditRow) { ?>
                                            <div><?php echo htmlspecialchars($auditRow['MasterAuditName']); ?> &rsaquo; <?php echo htmlspecialchars($auditRow['SubAuditName']); ?>
                                                <?php echo auditTicketStatusBadge(isset($auditRow['Status']) ? $auditRow['Status'] : 'Pending'); ?>
                                            </div>
                                        <?php } ?>
                                    </span></div>
                                    <?php } else { ?>
                                    <div class="at-info-row"><span class="at-info-label">Master Audit</span><span class="at-info-value"><?php echo htmlspecialchars($ticket['MasterAuditName']); ?></span></div>
                                    <div class="at-info-row"><span class="at-info-label">Sub Audit</span><span class="at-info-value"><?php echo htmlspecialchars($ticket['SubAuditName']); ?></span></div>
                                    <?php } ?>
                                    <?php if (!empty($ticket['Remarks'])) { ?>
                                    <div class="at-info-row"><span class="at-info-label">Remarks</span><span class="at-info-value text-left small"><?php echo nl2br(htmlspecialchars($ticket['Remarks'])); ?></span></div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="at-info-card">
                                <div class="card-head"><i class="fal fa-users-cog mr-1"></i> Assignment</div>
                                <div class="card-body">
                                    <div class="at-info-row">
                                        <span class="at-info-label">Branch Manager</span>
                                        <span class="at-info-value"><?php echo htmlspecialchars($bamName); ?></span>
                                    </div>
                                    <div class="at-info-row">
                                        <span class="at-info-label">Technician</span>
                                        <span class="at-info-value" id="at_technician_name"><?php echo htmlspecialchars($technicianName); ?></span>
                                    </div>

                                    <?php if ($canManageAssignment) { ?>
                                    <div class="at-assign-box mt-3">
                                        <h6 class="mb-2"><i class="fal fa-user-plus"></i> <?php echo $isReassign ? 'Reassign Technician' : 'Assign to Technician'; ?></h6>
                                        <p class="small text-muted mb-2">Status <?php echo $isReassign ? 'will remain' : 'will change to'; ?> <strong><?php echo $isReassign ? htmlspecialchars($ticket['Status']) : 'Assigned'; ?></strong></p>
                                        <select id="at_technician_id" class="form-control form-control-sm select2 mb-2">
                                            <option value="">Select Technician</option>
                                            <?php foreach ($technicianList as $tech) {
                                                if ((int) $tech['ID'] === $technicianId) {
                                                    continue;
                                                }
                                                ?>
                                            <option value="<?php echo (int) $tech['ID']; ?>"><?php echo htmlspecialchars($tech['Name']); ?></option>
                                            <?php } ?>
                                        </select>
                                        <input type="text" id="at_assign_remarks" class="form-control form-control-sm mb-2" placeholder="Remarks (optional)">
                                        <button type="button" class="btn btn-success btn-sm btn-block" onclick="assignAuditTechnician(<?php echo $ticketDbId; ?>, <?php echo $isReassign ? 1 : 0; ?>)">
                                            <?php echo $isReassign ? 'Reassign Technician' : 'Assign Technician'; ?>
                                        </button>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="at-info-card">
                                <div class="card-head"><i class="fal fa-tasks mr-1"></i> Checklist Progress</div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo (int) $stats['filled']; ?> / <?php echo (int) $stats['total']; ?> filled</span>
                                        <strong><?php echo $progressPct; ?>%</strong>
                                    </div>
                                    <div class="at-progress-wrap mb-3">
                                        <div class="at-progress-bar" style="width: <?php echo $progressPct; ?>%;"></div>
                                    </div>
                                    <div>
                                        <span class="badge badge-success mr-1">OK: <?php echo (int) $stats['ok']; ?></span>
                                        <span class="badge badge-danger mr-1">Not OK: <?php echo (int) $stats['not_ok']; ?></span>
                                        <span class="badge badge-info">In Range: <?php echo (int) $stats['in_range']; ?></span>
                                    </div>
                                    <div class="mt-3">
                                        <?php if ($ticket['Status'] === 'Completed' || $ticket['Status'] === 'Closed') { ?>
                                        <button class="btn btn-success btn-sm btn-block" onclick="generateAuditReport(<?php echo $ticketDbId; ?>)"><i class="fal fa-file-pdf"></i> Download Branch Report PDF</button>
                                        <?php } ?>
                                        <a href="view-audit-tickets" class="btn btn-outline-secondary btn-sm btn-block mt-2">Back to List</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-8">
                            <div class="at-info-card">
                                <div class="card-head">Audit Details</div>
                                <div class="card-body p-0">
                                    <ul class="nav nav-tabs at-nav-tabs px-3 pt-2" role="tablist">
                                        <?php if ($isMultiAudit) {
                                            $tabIndex = 0;
                                            foreach ($groupedChecklists as $group) {
                                                $tabId = 'tab_audit_' . (int) $group['sub_audit_id'];
                                                $active = $tabIndex === 0 ? ' active' : '';
                                                ?>
                                        <li class="nav-item">
                                            <a class="nav-link<?php echo $active; ?>" data-toggle="tab" href="#<?php echo $tabId; ?>" role="tab">
                                                <?php echo htmlspecialchars($group['sub_audit_name']); ?>
                                                <span class="badge badge-light ml-1"><?php echo (int) $group['summary']['filled']; ?>/<?php echo (int) $group['summary']['total']; ?></span>
                                            </a>
                                        </li>
                                        <?php $tabIndex++; }
                                        } else { ?>
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#tab_audit_checklist" role="tab">Checklist</a>
                                        </li>
                                        <?php } ?>
                                        <li class="nav-item">
                                            <a class="nav-link<?php echo $isMultiAudit ? '' : ''; ?>" data-toggle="tab" href="#tab_audit_history" role="tab">Status History</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content p-3">
                                        <?php if ($isMultiAudit) {
                                            $tabIndex = 0;
                                            foreach ($groupedChecklists as $group) {
                                                $tabId = 'tab_audit_' . (int) $group['sub_audit_id'];
                                                $active = $tabIndex === 0 ? ' show active' : '';
                                                ?>
                                        <div class="tab-pane fade<?php echo $active; ?>" id="<?php echo $tabId; ?>" role="tabpanel">
                                            <div class="mb-2 small text-muted"><?php echo htmlspecialchars($group['master_audit_name']); ?> &rsaquo; <?php echo htmlspecialchars($group['sub_audit_name']); ?></div>
                                            <?php if (empty($group['items'])) { ?>
                                            <div class="alert alert-info mb-0">No checklist configured for this sub audit.</div>
                                            <?php } else {
                                                $checklistItems = $group['items'];
                                                include('./include/audit-ticket-checklist-table.php');
                                            } ?>
                                        </div>
                                        <?php $tabIndex++; }
                                        } else { ?>
                                        <div class="tab-pane fade show active" id="tab_audit_checklist" role="tabpanel">
                                            <?php if (empty($checklistItems)) { ?>
                                            <div class="alert alert-info mb-0">No checklist configured for this sub audit.</div>
                                            <?php } else {
                                                include('./include/audit-ticket-checklist-table.php');
                                            } ?>
                                        </div>
                                        <?php } ?>
                                        <div class="tab-pane fade" id="tab_audit_history" role="tabpanel">
                                            <?php include('./include/audit_ticket_history_tab.php'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/audit-ticket.js"></script>
    <script>$(function(){ if ($("#at_technician_id").length) { $("#at_technician_id").select2({ width: "100%" }); } });</script>
</body>
</html>
