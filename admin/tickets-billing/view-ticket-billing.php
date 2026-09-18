<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include('../controllers/common_controllers.php');
    require_once('../includes/autoloader.inc.php');
    $conn = _connectodb();
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $companies = _getTableRecords($conn, 'company', " where IsActive = 1 ORDER BY CompanyName ASC");
    if (!is_array($companies)) {
        $companies = array();
    }
    $ticket_statuses = _getTableRecords($conn, 'corporate_tickets_status', ' WHERE IsActive = 1 ORDER BY Priority ASC, Status ASC');
    if (!is_array($ticket_statuses)) {
        $ticket_statuses = array();
    }
    $regions = _getTableRecords($conn, 'region', ' WHERE IsActive = 1 ORDER BY RegionName ASC');
    if (!is_array($regions)) {
        $regions = array();
    }
    $states = _getTableRecords($conn, 'state', ' WHERE IsActive = 1 ORDER BY StateName ASC');
    if (!is_array($states)) {
        $states = array();
    }
    ?>
    <meta charset="utf-8">
    <meta name="description" content="Ticket Billing Module">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <link rel="stylesheet" media="screen, print" href="../css/page-invoice.css">
    <title>Ticket Billing - <?=$ProductName;?></title>
    <style>
        #billingDetailsModal .tb-line-items-table .tb-bill-qty-col {
            min-width: 120px;
            width: 120px;
            white-space: nowrap;
        }
        #billingDetailsModal .tb-line-items-table .li-bill-qty {
            min-width: 100px;
            width: 100px;
            max-width: 140px;
            text-align: right;
            font-size: 0.95rem;
            padding: 0.4rem 0.55rem;
            -moz-appearance: textfield;
        }
        #billingDetailsModal .tb-line-items-table .li-bill-qty:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.15rem rgba(78, 115, 223, 0.25);
        }
        .tb-invoice-document {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 2rem;
        }
        .tb-invoice-document .tb-inv-title {
            font-size: 1.75rem;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #2c3e50;
        }
        .tb-invoice-document .tb-inv-meta dt {
            font-weight: 600;
            color: #6c757d;
        }
        .tb-invoice-document .tb-inv-meta dd {
            margin-bottom: 0.35rem;
        }
        .tb-invoice-document .tb-ticket-block {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .tb-invoice-document .tb-ticket-block-hdr {
            background: #f8f9fa;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        .tb-invoice-document table.tb-inv-lines {
            margin-bottom: 0;
        }
        .tb-invoice-document table.tb-inv-lines th {
            background: #fafafa;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .tb-invoice-document .tb-inv-grand-total {
            font-size: 1.15rem;
            font-weight: 700;
            border-top: 2px solid #2c3e50;
            padding-top: 0.75rem;
        }
        #invoice_print_area.tb-pdf-preview {
            background: #fff;
            border: 0;
            padding: 0;
            min-height: 80vh;
        }
        .tb-kpi-card {
            color: #fff;
            border-radius: 10px;
            padding: 14px 16px;
            box-shadow: 0 6px 14px rgba(17, 24, 39, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.12);
            min-height: 82px;
        }
        .tb-kpi-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            opacity: 0.85;
            margin-bottom: 6px;
        }
        .tb-kpi-value {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .tb-kpi-total { background: linear-gradient(135deg, #1f3b73, #253f8f); }
        .tb-kpi-amount { background: linear-gradient(135deg, #56358a, #7147b3); }
        .tb-kpi-quote-approved { background: linear-gradient(135deg, #0f5f46, #1a8a62); }
        .tb-kpi-quote-notapproved { background: linear-gradient(135deg, #8b2f2f, #b23d3d); }
        .tb-kpi-billed { background: linear-gradient(135deg, #0b5d4c, #0a7b63); }
        .tb-kpi-unbilled { background: linear-gradient(135deg, #7a4d10, #9d6718); }
        .tb-kpi-partial { background: linear-gradient(135deg, #7a1d58, #9a2a6f); }
        #billing_kpi_chart {
            width: 100% !important;
            height: 320px !important;
        }
        .tb-filter-offcanvas {
            position: fixed;
            top: 0;
            right: -380px;
            width: 380px;
            max-width: 92vw;
            height: 100%;
            background: #fff;
            box-shadow: -4px 0 18px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease;
            z-index: 1055;
            overflow: visible;
        }
        .tb-filter-offcanvas.show {
            right: 0;
        }
        .tb-po-remarks {
            display: inline-block;
            max-width: 220px;
            white-space: normal;
            word-break: break-word;
        }
        .tb-filter-offcanvas-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 1050;
        }
        .tb-filter-offcanvas-backdrop.show {
            display: block;
        }
        .tb-filter-offcanvas .offcanvas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e9ecef;
        }
        .tb-filter-offcanvas .offcanvas-body {
            padding: 1.25rem;
            overflow-y: auto;
            max-height: calc(100vh - 60px);
        }
        .tb-billing-actions .btn {
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
        }
        .tb-filter-offcanvas .select2-container {
            width: 100% !important;
        }
        body.tb-billing-filters-open .select2-container--open {
            z-index: 10070 !important;
        }
        body.tb-billing-filters-open .select2-dropdown {
            z-index: 10071 !important;
        }
        .tb-filter-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .tb-workflow-tabs .nav-link {
            font-weight: 600;
            font-size: 0.88rem;
        }
        .tb-tab-badge {
            display: inline-block;
            min-width: 1.5rem;
            margin-left: 0.35rem;
            padding: 0.15rem 0.45rem;
            font-size: 0.72rem;
            font-weight: 700;
            border-radius: 10px;
            background: #e9ecef;
            color: #495057;
        }
        .nav-link.active .tb-tab-badge {
            background: #fff;
            color: #1f3b73;
        }
        .tb-selection-bar {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 0.5rem 0.85rem;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        .tb-selection-bar .tb-sel-count {
            font-weight: 700;
            color: #1f3b73;
        }
        .tb-queue-no {
            font-weight: 600;
            color: #6c757d;
            text-align: center;
            width: 48px;
        }
        .tb-stage-actions {
            margin-bottom: 0.75rem;
        }
        .tb-stage-actions .btn {
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
        }
        @media print {
            body * { visibility: hidden; }
            #invoiceViewModal,
            #invoiceViewModal * { visibility: visible; }
            #invoiceViewModal {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            #invoiceViewModal .modal-dialog {
                max-width: 100%;
                margin: 0;
            }
            #invoiceViewModal .modal-content {
                border: none;
                box-shadow: none;
            }
            .tb-no-print { display: none !important; }
            .modal-backdrop { display: none !important; }
            .tb-invoice-document {
                border: none;
                padding: 0;
            }
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
                <ol class="breadcrumb page-breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard"><?=$ProductName;?></a></li>
                    <li class="breadcrumb-item active">Ticket Billing</li>
                </ol>

                <div class="row mb-3">
                    <div class="col-xl-12">
                        <div class="d-flex flex-wrap justify-content-between align-items-center tb-billing-actions">
                            <div>
                                <button type="button" class="btn btn-outline-primary" onclick="toggleBillingFilterCanvas();"><i class="fa fa-filter"></i> Filters</button>
                                <span class="text-muted ml-2" id="tb_active_filters_summary">All filters</span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary" onclick="loadCurrentTabData();"><i class="fa fa-search"></i> Search</button>
                                <button type="button" class="btn btn-info" onclick="exportBillingToCSV();"><i class="fa fa-download"></i> Export CSV</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tb_filter_offcanvas_backdrop" class="tb-filter-offcanvas-backdrop"></div>
                <div id="tb_filter_offcanvas" class="tb-filter-offcanvas">
                    <div class="offcanvas-header">
                        <h5 class="mb-0">Ticket Billing Filters</h5>
                        <button type="button" class="close" onclick="closeBillingFilterCanvas();">&times;</button>
                    </div>
                    <div class="offcanvas-body">
                        <div class="form-group">
                            <label>Region</label>
                            <select class="form-control tb-filter-select" id="filter_region">
                                <option value="">All Regions</option>
                                <?php foreach ($regions as $region) { ?>
                                    <option value="<?php echo (int)$region['ID']; ?>"><?php echo htmlspecialchars($region['RegionName']); ?></option>
                                <?php } ?>
                            </select>
                            <div class="tb-filter-hint">Region filters tickets by geography. It does not limit the state list below.</div>
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <select class="form-control tb-filter-select" id="filter_state">
                                <option value="">All States</option>
                                <?php foreach ($states as $stateRow) { ?>
                                    <option value="<?php echo (int)$stateRow['ID']; ?>"><?php echo htmlspecialchars($stateRow['StateName']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Company</label>
                            <select class="form-control tb-filter-select" id="filter_company">
                                <option value="">All Companies</option>
                                <?php foreach ($companies as $company) { ?>
                                    <option value="<?php echo (int)$company['ID']; ?>"><?php echo htmlspecialchars($company['CompanyName']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Branch</label>
                            <select class="form-control tb-filter-select" id="filter_branch">
                                <option value="">All Branches</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ticket Status</label>
                            <select class="form-control tb-filter-select" id="filter_ticket_status">
                                <option value="">All</option>
                                <?php foreach ($ticket_statuses as $statusRow) { ?>
                                    <option value="<?php echo htmlspecialchars($statusRow['Status']); ?>"><?php echo htmlspecialchars($statusRow['Status']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ticket Type</label>
                            <select class="form-control tb-filter-select" id="filter_ticket_type">
                                <option value="">All</option>
                                <option value="AMC">AMC</option>
                                <option value="RM">RM</option>
                                <option value="Supply">Supply</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Range (Ticket Created Date)</label>
                            <input type="text" class="form-control" id="date_range" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>Search Ticket ID</label>
                            <input type="text" class="form-control" id="search_ticket_id" placeholder="e.g. CS-CORP-1001">
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary btn-block" onclick="applyBillingFiltersAndClose();"><i class="fa fa-search"></i> Apply & Search</button>
                            <button type="button" class="btn btn-default btn-block mt-2" onclick="resetBillingFilters();">Reset Filters</button>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3 tb-workflow-tabs" id="ticketBillingTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-select-billing-link" data-toggle="tab" href="#tab-select-billing" data-queue-stage="Eligible" role="tab">
                            1. Select for Billing <span class="tb-tab-badge" id="tb_badge_eligible">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-ready-billing-link" data-toggle="tab" href="#tab-ready-billing" data-queue-stage="ReadyForBilling" role="tab">
                            2. Ready for Billing <span class="tb-tab-badge" id="tb_badge_ready">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-billing-verification-link" data-toggle="tab" href="#tab-billing-verification" data-queue-stage="BillingVerification" role="tab">
                            3. Billing Verification <span class="tb-tab-badge" id="tb_badge_verification">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-payment-status-link" data-toggle="tab" href="#tab-payment-status" data-queue-stage="PaymentStatus" role="tab">
                            4. Payment Status <span class="tb-tab-badge" id="tb_badge_payment">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-partial-billing-link" data-toggle="tab" href="#tab-partial-billing" data-queue-stage="PartialBilling" role="tab">
                            5. Partial Billing &amp; Payment <span class="tb-tab-badge" id="tb_badge_partial">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-billing-pdf-link" data-toggle="tab" href="#tab-billing-pdf" data-queue-stage="BillingPdf" role="tab">
                            6. Billing PDF <span class="tb-tab-badge" id="tb_badge_pdf">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="ticketBillingTabContent">

                <div class="tab-pane fade show active" id="tab-select-billing" role="tabpanel">
                    <div class="tb-stage-actions">
                        <button type="button" class="btn btn-success" id="btn_send_for_billing" onclick="sendSelectedForBilling();" disabled>
                            <i class="fa fa-paper-plane"></i> Send Selected for Billing
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearTabSelection('Eligible');"><i class="fa fa-refresh"></i> Clear Selection</button>
                    </div>
                    <div class="tb-selection-bar" id="tb_selection_bar_eligible">Queue: <span class="tb-sel-count" id="tb_sel_eligible">0</span> selected of <span id="tb_total_eligible">0</span> tickets</div>
                    <div class="panel">
                        <div class="panel-hdr"><h2>Select Tickets for Billing <small class="text-muted">(SM verified, unbilled only)</small></h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table id="billing_table_eligible" class="table table-bordered table-striped tb-stage-table" data-queue-stage="Eligible">
                                    <thead>
                                    <tr>
                                        <th class="tb-queue-no">#</th>
                                        <th><input type="checkbox" class="tb-select-all" data-stage="Eligible"></th>
                                        <th>Ticket ID</th>
                                        <th>Company</th>
                                        <th>Branch</th>
                                        <th>Created Date</th>
                                        <th>Ticket Status</th>
                                        <th>Quotation ID</th>
                                        <th>Quotation Status</th>
                                        <th>PO Number</th>
                                        <th>Quotation Amount (No GST)</th>
                                    </tr>
                                    </thead>
                                    <tbody id="billing_tbody_eligible"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-ready-billing" role="tabpanel">
                    <div class="tb-stage-actions">
                        <button type="button" class="btn btn-success" id="btn_bill_selected" onclick="openBulkInvoiceModal();" disabled>
                            <i class="fa fa-file-invoice"></i> Bill Selected
                        </button>
                        <button type="button" class="btn btn-warning" onclick="moveSelectedQueueStage('Eligible');"><i class="fa fa-undo"></i> Remove from Queue</button>
                        <button type="button" class="btn btn-secondary" onclick="clearTabSelection('ReadyForBilling');"><i class="fa fa-refresh"></i> Clear Selection</button>
                    </div>
                    <div class="tb-selection-bar" id="tb_selection_bar_ready">Queue: <span class="tb-sel-count" id="tb_sel_ready">0</span> selected of <span id="tb_total_ready">0</span> tickets</div>

                <div class="row">
                    <div class="col-md-2 col-6 mb-2"><div class="tb-kpi-card tb-kpi-total"><div class="tb-kpi-title">Total Tickets</div><div class="tb-kpi-value" id="stat_total_tickets">0</div></div></div>
                    <div class="col-md-2 col-6 mb-2"><div class="tb-kpi-card tb-kpi-amount"><div class="tb-kpi-title">Total Amount (No GST)</div><div class="tb-kpi-value" id="stat_total_amount">Rs 0.00</div></div></div>
                    <div class="col-md-2 col-6 mb-2"><div class="tb-kpi-card tb-kpi-quote-approved"><div class="tb-kpi-title">Quote Approved</div><div class="tb-kpi-value"><span id="stat_quote_approved_count">0</span> | <span id="stat_quote_approved_amount">Rs 0.00</span></div></div></div>
                    <div class="col-md-2 col-6 mb-2"><div class="tb-kpi-card tb-kpi-quote-notapproved"><div class="tb-kpi-title">Quote Not Approved</div><div class="tb-kpi-value"><span id="stat_quote_notapproved_count">0</span> | <span id="stat_quote_notapproved_amount">Rs 0.00</span></div></div></div>
                    <div class="col-md-2 col-6 mb-2"><div class="tb-kpi-card tb-kpi-billed"><div class="tb-kpi-title">Billed</div><div class="tb-kpi-value"><span id="stat_billed_count">0</span> | <span id="stat_billed_amount">Rs 0.00</span></div></div></div>
                    <div class="col-md-2 col-6 mb-2"><div class="tb-kpi-card tb-kpi-unbilled"><div class="tb-kpi-title">Unbilled</div><div class="tb-kpi-value"><span id="stat_unbilled_count">0</span> | <span id="stat_unbilled_amount">Rs 0.00</span></div></div></div>
                    <div class="col-md-2 col-6 mb-2"><div class="tb-kpi-card tb-kpi-partial"><div class="tb-kpi-title">Partially Paid</div><div class="tb-kpi-value"><span id="stat_partially_paid_count">0</span> | <span id="stat_partially_paid_amount">Rs 0.00</span></div></div></div>
                </div>

                <div class="row mt-2">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Billing Performance Overview</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <canvas id="billing_kpi_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Tickets Ready for Billing</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <table id="billing_table_ready" class="table table-bordered table-striped tb-stage-table" data-queue-stage="ReadyForBilling">
                                        <thead>
                                        <tr>
                                            <th class="tb-queue-no">#</th>
                                            <th><input type="checkbox" class="tb-select-all" data-stage="ReadyForBilling"></th>
                                            <th>Ticket ID</th>
                                            <th>Company</th>
                                            <th>Branch</th>
                                            <th>Created Date</th>
                                            <th>Queued Date</th>
                                            <th>Ticket Status</th>
                                            <th>Quotation ID</th>
                                            <th>Quotation Status</th>
                                            <th>Quotation Amount (No GST)</th>
                                            <th>Remaining Amount</th>
                                            <th>Billing Status</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody id="billing_tbody_ready"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div><!-- /tab-ready-billing -->

                <div class="tab-pane fade" id="tab-billing-verification" role="tabpanel">
                    <div class="tb-stage-actions">
                        <button type="button" class="btn btn-success" id="btn_verify_billing" onclick="verifySelectedBilling();" disabled>
                            <i class="fa fa-check-circle"></i> Verify &amp; Move Forward
                        </button>
                        <button type="button" class="btn btn-warning" onclick="moveSelectedQueueStage('ReadyForBilling');"><i class="fa fa-undo"></i> Send Back to Billing</button>
                        <button type="button" class="btn btn-secondary" onclick="clearTabSelection('BillingVerification');"><i class="fa fa-refresh"></i> Clear Selection</button>
                    </div>
                    <div class="tb-selection-bar" id="tb_selection_bar_verification">Queue: <span class="tb-sel-count" id="tb_sel_verification">0</span> selected of <span id="tb_total_verification">0</span> tickets</div>
                    <div class="panel">
                        <div class="panel-hdr"><h2>Billing Verification Queue</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table id="billing_table_verification" class="table table-bordered table-striped tb-stage-table" data-queue-stage="BillingVerification">
                                    <thead>
                                    <tr>
                                        <th class="tb-queue-no">#</th>
                                        <th><input type="checkbox" class="tb-select-all" data-stage="BillingVerification"></th>
                                        <th>Ticket ID</th>
                                        <th>Company</th>
                                        <th>Branch</th>
                                        <th>Billing / Invoice No.</th>
                                        <th>Billed Date</th>
                                        <th>Quotation Amount (No GST)</th>
                                        <th>Billed Amount</th>
                                        <th>Remaining Amount</th>
                                        <th>Billing Status</th>
                                        <th>Payment Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="billing_tbody_verification"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-payment-status" role="tabpanel">
                    <div class="tb-stage-actions">
                        <button type="button" class="btn btn-primary" id="btn_update_payment" onclick="openPaymentUpdateModal();" disabled>
                            <i class="fa fa-money-bill"></i> Update Payment for Selected
                        </button>
                        <button type="button" class="btn btn-warning" onclick="moveSelectedQueueStage('BillingVerification');"><i class="fa fa-undo"></i> Send Back to Verification</button>
                        <button type="button" class="btn btn-secondary" onclick="clearTabSelection('PaymentStatus');"><i class="fa fa-refresh"></i> Clear Selection</button>
                    </div>
                    <div class="tb-selection-bar" id="tb_selection_bar_payment">Queue: <span class="tb-sel-count" id="tb_sel_payment">0</span> selected of <span id="tb_total_payment">0</span> tickets</div>
                    <div class="panel">
                        <div class="panel-hdr"><h2>Payment Status Queue</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table id="billing_table_payment" class="table table-bordered table-striped tb-stage-table" data-queue-stage="PaymentStatus">
                                    <thead>
                                    <tr>
                                        <th class="tb-queue-no">#</th>
                                        <th><input type="checkbox" class="tb-select-all" data-stage="PaymentStatus"></th>
                                        <th>Ticket ID</th>
                                        <th>Company</th>
                                        <th>Branch</th>
                                        <th>Billing / Invoice No.</th>
                                        <th>Billed Date</th>
                                        <th>Verified Date</th>
                                        <th>Billed Amount</th>
                                        <th>Payment Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="billing_tbody_payment"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-partial-billing" role="tabpanel">
                    <div class="alert alert-info py-2 mb-3">
                        Tickets with <strong>Partially Billed</strong> or <strong>Partially Paid</strong> status appear here.
                        Update payment for the billed portion, mark payment closed when done, or bill the remaining quantity when required.
                    </div>
                    <div class="tb-stage-actions">
                        <button type="button" class="btn btn-primary" id="btn_partial_update_payment" onclick="openPartialPaymentModal();" disabled>
                            <i class="fa fa-money-bill"></i> Update Payment
                        </button>
                        <button type="button" class="btn btn-success" id="btn_partial_mark_closed" onclick="markPartialPaymentClosed();" disabled>
                            <i class="fa fa-check"></i> Mark Payment Closed
                        </button>
                        <button type="button" class="btn btn-info" id="btn_partial_bill_remaining" onclick="billRemainingSelected();" disabled>
                            <i class="fa fa-plus-circle"></i> Bill Remaining Qty
                        </button>
                        <button type="button" class="btn btn-warning" onclick="moveSelectedQueueStage('BillingVerification');"><i class="fa fa-undo"></i> Send Back to Verification</button>
                        <button type="button" class="btn btn-secondary" onclick="clearTabSelection('PartialBilling');"><i class="fa fa-refresh"></i> Clear Selection</button>
                    </div>
                    <div class="tb-selection-bar" id="tb_selection_bar_partial">Queue: <span class="tb-sel-count" id="tb_sel_partial">0</span> selected of <span id="tb_total_partial">0</span> tickets</div>
                    <div class="panel">
                        <div class="panel-hdr"><h2>Partial Billing &amp; Payment Queue</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <table id="billing_table_partial" class="table table-bordered table-striped tb-stage-table" data-queue-stage="PartialBilling">
                                    <thead>
                                    <tr>
                                        <th class="tb-queue-no">#</th>
                                        <th><input type="checkbox" class="tb-select-all" data-stage="PartialBilling"></th>
                                        <th>Ticket ID</th>
                                        <th>Company</th>
                                        <th>Branch</th>
                                        <th>Billing / Invoice No.</th>
                                        <th>Billed Date</th>
                                        <th>Quotation Amount (No GST)</th>
                                        <th>Billed Amount</th>
                                        <th>Remaining Amount</th>
                                        <th>Billing Status</th>
                                        <th>Payment Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="billing_tbody_partial"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-billing-pdf" role="tabpanel">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="panel">
                                <div class="panel-hdr"><h2>Invoice Filters</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <label>Company</label>
                                                <select class="form-control" id="inv_filter_company">
                                                    <option value="">All Companies</option>
                                                    <?php
                                                    if (is_array($companies)) {
                                                        foreach ($companies as $company) {
                                                            echo '<option value="' . $company['ID'] . '">' . $company['CompanyName'] . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Branch</label>
                                                <select class="form-control" id="inv_filter_branch">
                                                    <option value="">All Branches</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Billed Date Range</label>
                                                <input type="text" class="form-control" id="inv_date_range" autocomplete="off">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Search Billing / Invoice No.</label>
                                                <input type="text" class="form-control" id="inv_search_billing_no" placeholder="e.g. INV-2026-001">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-primary" onclick="loadBillingInvoices();"><i class="fa fa-search"></i> Search Invoices</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="panel">
                                <div class="panel-hdr"><h2>All Billings</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table id="billing_invoices_table" class="table table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th>Billing / Invoice No.</th>
                                                <th>Billed Date</th>
                                                <th>Company</th>
                                                <th>Tickets</th>
                                                <th>Payment Status</th>
                                                <th>Total Amount (No GST)</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody id="billing_invoices_tbody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /tab-billing-pdf -->

                </div><!-- /tab-content -->

                <div class="modal fade" id="billingDetailsModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Bulk Invoice</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div id="invoiceTickets"></div>
                                <div class="text-right mb-3"><b>Total: </b><span id="invoiceTotalAmount">Rs 0.00</span></div>
                                <form id="bulkBillingForm">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Billing Mode</label>
                                            <select class="form-control" name="BillingMode" id="billing_mode" onchange="onBillingModeChange();">
                                                <option value="full">All In One (Full Ticket Amount)</option>
                                                <option value="itemized">Line Item / Quantity Wise (Partial Allowed)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="line_item_billing_section" style="display:none;">
                                        <h5 class="mb-2">Line Item Billing</h5>
                                        <div class="mb-2">
                                            <button type="button" class="btn btn-sm btn-primary" id="btn_fill_remaining_qty" onclick="fillRemainingForSelectedLines();">Fill Remaining Qty</button>
                                            <button type="button" class="btn btn-sm btn-secondary" id="btn_clear_selected_qty" onclick="clearSelectedLineQty();">Clear Selected Qty</button>
                                        </div>
                                        <div id="line_item_billing_container"></div>
                                        <small class="text-muted">Options: (1) Full settle = click Fill Remaining Qty, (2) Partial = enter custom qty less than remaining, (3) Fully billed rows are locked.</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4"><label>Billing Number *</label><input type="text" class="form-control" name="BillingNumber" required></div>
                                        <div class="col-md-4"><label>Billed Date *</label><input type="date" class="form-control" name="BilledDate" required></div>
                                        <div class="col-md-4">
                                            <label>Payment Status</label>
                                            <select class="form-control" name="PaymentStatus">
                                                <option value="Pending">Pending</option>
                                                <option value="Partially Paid">Partially Paid</option>
                                                <option value="Closed">Closed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label>Remarks</label>
                                        <textarea class="form-control" name="Remarks"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-info" onclick="savePaymentOnlyUpdate();">Update Payment Only</button>
                                <button type="button" class="btn btn-warning" onclick="reopenTicketBilling();" title="Full reset only — removes all billed line items">Re-open Billing (Full Reset)</button>
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="saveBillingDetails();">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="invoiceViewModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header tb-no-print">
                                <h4 class="modal-title">Billing Invoice</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body p-0">
                                <div id="invoice_print_area" class="tb-pdf-preview"></div>
                            </div>
                            <div class="modal-footer tb-no-print">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-info" onclick="openBillingInvoicePdf('preview');"><i class="fa fa-file-pdf"></i> Preview PDF</button>
                                <button type="button" class="btn btn-success" onclick="openBillingInvoicePdf('download');"><i class="fa fa-download"></i> Download PDF</button>
                                <button type="button" class="btn btn-primary" onclick="printTicketBillingInvoice();"><i class="fa fa-print"></i> Print Invoice</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>

<?php
include('../includes/common_modules.php');
include('../includes/common_scripts.php');
?>
<script src="../js/datagrid/datatables/datatables.bundle.js"></script>
<script src="../js/dependency/moment/moment.js"></script>
<script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
<script src="../js/statistics/chartjs/chartjs.bundle.js"></script>
<script src="../js/modules/ticket-billing.js?v=20260616"></script>
</body>
</html>
