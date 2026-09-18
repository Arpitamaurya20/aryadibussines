<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../company/controller/company_controller.php');
    include('../branch/controller/branch_controller.php');
    include('../corporate-tickets/controller/corporate_tickets_controller.php');
    include('controller/audit_ticket_controller.php');
    setNavigation($_SESSION['Roles']);
    $UserType = SessionCheck();
    $conn = _connectodb();

    $allCompany = getAllCompanies($conn);
    $masterAudits = getAllCorporateMasterAudits($conn, true);

    $corporateId = -1;
    $branchId = -1;
    $ticketManager = false;
    if ($UserType === 'Corporate Admin') {
        $corporateId = (int) $_SESSION['Roles']['CorporateID'];
    }
    if ($UserType === 'Corporate Branch User') {
        $corporateId = (int) $_SESSION['Roles']['CorporateID'];
        $branchId = (int) $_SESSION['Roles']['BranchID'];
    }
    if (isset($_SESSION['Roles']['EmployeeRoles'])) {
        foreach ($_SESSION['Roles']['EmployeeRoles'] as $role) {
            if (in_array($role, array('Ticket Manager', 'Admin', 'Branch Account Manager'), true) || $UserType === 'Admin') {
                $ticketManager = true;
            }
        }
    }
    if ($UserType === 'Admin' || $UserType === 'Super Admin') {
        $ticketManager = true;
    }
    ?>
    <meta charset="utf-8">
    <title>Raise Audit Ticket - Aryadibusiness</title>
    <?php include('../includes/common_head_content.php'); ?>

    <style>
        /* Custom UI Modernization Theme */
        body {
            background-color: #f8fafc;
        }

        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            background: #ffffff;
        }

        .form-section-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .form-section-title i {
            color: #2563eb;
            margin-right: 0.5rem;
        }

        /* Input & Dropdown Focus States */
        .form-control, .select2-container--default .select2-selection--single, .select2-container--default .select2-selection--multiple {
            border-radius: 8px !important;
            border-color: #cbd5e1 !important;
        }

        .form-control:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        }

        .select2-container { 
            z-index: 1; 
            width: 100% !important;
        }

        /* Banner Box */
        .info-banner-modern {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #2563eb;
            border-radius: 10px;
            color: #1e40af;
            padding: 0.9rem 1.25rem;
        }

        /* Audit Selector Container */
        .audit-builder-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 1.25rem;
        }

        /* Modern Table Styling */
        .table-modern {
            border-collapse: separate !important;
            border-spacing: 0;
            width: 100% !important;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .table-modern thead th {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 10px 12px;
        }

        .table-modern tbody td {
            vertical-align: middle !important;
            padding: 10px 12px;
            border-top: 1px solid #f1f5f9;
        }

        #selected_audits_table td { 
            vertical-align: middle; 
        }

        /* Button Customizations */
        .btn-modern-primary {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }

        .btn-modern-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
        }

        .btn-modern-outline {
            border-radius: 8px;
            font-weight: 600;
            height: 38px;
        }
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
                    
                    <!-- Breadcrumbs -->
                    <ol class="breadcrumb page-breadcrumb mb-3">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="view-audit-tickets">Audit Tickets</a></li>
                        <li class="breadcrumb-item active text-primary font-weight-bold">Raise Ticket</li>
                    </ol>

                    <!-- Main Container Card -->
                    <div class="custom-card p-4">
                        
                        <!-- Header Title -->
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                            <div>
                                <h4 class="font-weight-bold text-dark m-0">
                                    <i class="fal fa-ticket-alt text-primary mr-2"></i>Raise Audit Ticket
                                </h4>
                                <p class="text-muted small m-0 mt-1">Configure corporate location, select audit requirements, and submit your request.</p>
                            </div>
                            <a href="view-audit-tickets" class="btn btn-sm btn-outline-secondary font-weight-bold" style="border-radius: 6px;">
                                <i class="fal fa-arrow-left mr-1"></i> Back to Tickets
                            </a>
                        </div>

                        <form id="raise_audit_ticket_form">
                            
                            <!-- SECTION 1: Location & Entity Details -->
                            <div class="form-section-title">
                                <i class="fal fa-building"></i> 1. Location & Entity Details
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-6 mb-3 mb-lg-0">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold small text-muted">Corporate <span class="text-danger">*</span></label>
                                        <select name="CorporateID" id="corporate_name" class="select2 form-control" onchange="auditSelectCorporate()">
                                            <option value="">Please Select Corporate</option>
                                            <?php foreach ($allCompany as $company) {
                                                $companyId = (int) $company['ID'];
                                                if (!$ticketManager && $corporateId > 0 && $companyId !== $corporateId) {
                                                    continue;
                                                }
                                                $selected = ($corporateId > 0 && $companyId === $corporateId) ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo $companyId; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($company['CompanyName']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-0" id="branch_div" style="<?php echo ($corporateId > 0) ? '' : 'display:none;'; ?>">
                                        <label class="font-weight-bold small text-muted">Branch <span class="text-danger">*</span></label>
                                        <select name="BranchID" id="branch_name" class="select2 form-control">
                                            <option value="">Please Select Branch</option>
                                            <?php
                                            if ($corporateId > 0) {
                                                $branches = getBranchByCorporateID($conn, $corporateId);
                                                foreach ($branches as $branch) {
                                                    $bid = (int) $branch['ID'];
                                                    if ($branchId > 0 && $bid !== $branchId) {
                                                        continue;
                                                    }
                                                    $sel = ($branchId > 0 && $bid === $branchId) ? 'selected' : '';
                                                    echo '<option value="' . $bid . '" ' . $sel . '>' . htmlspecialchars($branch['BranchSite']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4" style="border-top: 1px border-color #f1f5f9;">

                            <!-- SECTION 2: Audit Selection -->
                            <div class="form-section-title">
                                <i class="fal fa-clipboard-check"></i> 2. Audit Selection
                            </div>

                            <!-- Guidance Banner -->
                            <div class="info-banner-modern mb-3 d-flex align-items-center">
                                <i class="fal fa-info-circle fa-lg mr-3"></i>
                                <div class="small">
                                    You can add <strong>multiple audits</strong> to one ticket. Select a master audit, choose one or more sub-audits, then click <strong>Add to ticket</strong>.
                                </div>
                            </div>

                            <!-- Audit Builder Controls -->
                            <div class="audit-builder-box mb-4">
                                <div class="row align-items-end">
                                    <div class="col-lg-5 mb-3 mb-lg-0">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold small text-muted">Master Audit <span class="text-danger">*</span></label>
                                            <select id="master_audit_id" class="select2 form-control" onchange="auditSelectMasterAudit()">
                                                <option value="">Please Select Master Audit</option>
                                                <?php foreach ($masterAudits as $audit) { ?>
                                                    <option value="<?php echo (int) $audit['ID']; ?>"><?php echo htmlspecialchars($audit['AuditName']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 mb-3 mb-lg-0">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold small text-muted">Sub Audit(s) <span class="text-danger">*</span></label>
                                            <select id="sub_audit_id" class="select2 form-control" multiple="multiple">
                                            </select>
                                            <span class="text-muted fs-xs d-block mt-1"><i class="fal fa-lightbulb mr-1"></i>Hold Ctrl/Cmd to select multiple sub audits from the same master.</span>
                                        </div>
                                    </div>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-outline-primary btn-block btn-modern-outline font-weight-bold" onclick="auditAddSelectedAudits()">
                                            <i class="fal fa-plus-circle mr-1"></i> Add to ticket
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Audits Data Table -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold small text-muted mb-2">Audits on this ticket <span class="text-danger">*</span></label>
                                <div class="table-responsive">
                                    <table class="table table-modern mb-0" id="selected_audits_table">
                                        <thead>
                                            <tr>
                                                <th>Master Audit</th>
                                                <th>Sub Audit</th>
                                                <th style="width:100px;" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selected_audits_body">
                                            <tr id="selected_audits_empty">
                                                <td colspan="3" class="text-muted text-center py-4">
                                                    <i class="fal fa-folder-open fa-2x mb-2 d-block text-slate-300"></i>
                                                    No audits added yet. Use the builder above to append audits.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="Audits" id="audits_json" value="[]">
                            </div>

                            <hr class="my-4" style="border-top: 1px border-color #f1f5f9;">

                            <!-- SECTION 3: Priority & Remarks -->
                            <div class="form-section-title">
                                <i class="fal fa-sliders-h"></i> 3. Ticket Details & Remarks
                            </div>

                            <div class="row mb-4">
                                <div class="col-lg-6 mb-3 mb-lg-0">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold small text-muted">Priority</label>
                                        <select name="Priority" id="priority" class="form-control">
                                            <option value="Normal">Normal</option>
                                            <option value="High">High</option>
                                            <option value="Low">Low</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold small text-muted">Remarks</label>
                                        <textarea name="Remarks" id="remarks" class="form-control" rows="2" placeholder="Optional notes or instructions for technician..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Form Actions -->
                            <div class="d-flex align-items-center justify-content-end border-top pt-3">
                                <a href="view-audit-tickets" class="btn btn-light font-weight-bold mr-2 px-4" style="border-radius: 8px;">Cancel</a>
                                <button type="button" id="raise_audit_ticket_btn" class="btn btn-modern-primary" onclick="raiseAuditTicket()">
                                    <i class="fal fa-paper-plane mr-2"></i>Raise Audit Ticket
                                </button>
                            </div>

                        </form>
                    </div>

                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/audit-ticket.js"></script>
</body>
</html>