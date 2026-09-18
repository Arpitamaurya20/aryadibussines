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
    ?>
    <meta charset="utf-8">
    <meta name="description" content="PPM Billing Module">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    
    <title>PPM Billing - <?=$ProductName;?></title>
    <?php 
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    ?>
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
        }
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-top: 10px;
        }
        .stat-card small {
            font-size: 12px;
            opacity: 0.8;
            display: block;
            margin-top: 5px;
        }
        .stat-card.raised { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.assigned { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.closed { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card.billed { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-card.unbilled { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .stat-card.pending { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
        .billing-status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        .billing-status-billed {
            background-color: #28a745;
            color: white;
        }
        .billing-status-unbilled {
            background-color: #ffc107;
            color: #333;
        }
        .payment-status-pending {
            background-color: #dc3545;
            color: white;
        }
        .payment-status-closed {
            background-color: #17a2b8;
            color: white;
        }
        .payment-status-billed {
            background-color: #28a745;
            color: white;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .ticket-checkbox {
            cursor: pointer;
        }
        #bulk_bill_btn, #bulk_unbill_btn {
            margin-left: 5px;
        }
        .panel-toolbar {
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php
            include('../navigation/admin_navigation.php');
            ?>
            <div class="page-content-wrapper">
                <?php
                include('../includes/common_header.php');
                ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">PPM Billing</li>
                    </ol>

                    <!-- Filters Section -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-filters" class="panel">
                                <div class="panel-hdr">
                                    <h2>Filters</h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Company</label>
                                                    <select class="form-control" id="filter_company" onchange="loadBranches(); loadBillingData();">
                                                        <option value="">All Companies</option>
                                                        <?php
                                                        $where = " where IsActive = 1 ORDER BY CompanyName ASC";
                                                        $companies = _getTableRecords($conn, 'company', $where);
                                                        if (is_array($companies)) {
                                                            foreach ($companies as $company) {
                                                                echo '<option value="' . $company['ID'] . '">' . $company['CompanyName'] . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Branch</label>
                                                    <select class="form-control" id="filter_branch" onchange="loadBillingData();">
                                                        <option value="">All Branches</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Date Range</label>
                                                    <input type="text" class="form-control" id="date_range" placeholder="Select Date Range" autocomplete="off">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Search Ticket ID</label>
                                                    <input type="text" class="form-control" id="search_ticket_id" placeholder="e.g., CS-PPM-27714" onkeypress="if(event.key==='Enter') loadBillingData();">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>&nbsp;</label><br>
                                                    <button type="button" class="btn btn-primary" onclick="loadBillingData();">
                                                        <i class="fa fa-search"></i> Search
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" onclick="resetFilters();">
                                                        <i class="fa fa-refresh"></i> Reset
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row" id="statistics_cards">
                        <div class="col-md-2">
                            <div class="stat-card raised">
                                <h3>Raised Tickets</h3>
                                <div class="stat-value" id="stat_raised_amount">₹0</div>
                                <small id="stat_raised">0 tickets</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card assigned">
                                <h3>Assigned Tickets</h3>
                                <div class="stat-value" id="stat_assigned_amount">₹0</div>
                                <small id="stat_assigned">0 tickets</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card closed">
                                <h3>Closed Tickets</h3>
                                <div class="stat-value" id="stat_closed_amount">₹0</div>
                                <small id="stat_closed">0 tickets</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card billed">
                                <h3>Billed</h3>
                                <div class="stat-value" id="stat_billed_amount">₹0</div>
                                <small id="stat_billed">0 tickets</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card unbilled">
                                <h3>Unbilled</h3>
                                <div class="stat-value" id="stat_unbilled_amount">₹0</div>
                                <small id="stat_unbilled">0 tickets</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card pending">
                                <h3>Pending Payment</h3>
                                <div class="stat-value" id="stat_pending_amount">₹0</div>
                                <small id="stat_pending">0 tickets</small>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div id="panel-chart-tickets" class="panel">
                                <div class="panel-hdr">
                                    <h2>Ticket Status Chart</h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="chart-container">
                                            <canvas id="ticketStatusChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div id="panel-chart-billing" class="panel">
                                <div class="panel-hdr">
                                    <h2>Billing Status Chart</h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="chart-container">
                                            <canvas id="billingStatusChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Table -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-billing-table" class="panel">
                                <div class="panel-hdr">
                                    <h2>PPM Billing Details <span id="ticket_count_badge" class="badge badge-info" style="display:none; margin-left: 10px;"></span></h2>
                                    <small class="text-muted" id="date_range_info" style="display: block; margin-top: 5px;">Filtering by PPMDate</small>
                                    <div class="panel-toolbar">
                                        <button type="button" class="btn btn-success btn-sm" id="bulk_bill_btn" onclick="bulkBillingAction('Billed');" disabled>
                                            <i class="fa fa-check"></i> Mark Selected as Billed
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm" id="bulk_unbill_btn" onclick="bulkBillingAction('Unbilled');" disabled>
                                            <i class="fa fa-times"></i> Mark Selected as Unbilled
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearAllSelections();">
                                            <i class="fa fa-refresh"></i> Clear Selection
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm" onclick="exportBillingToCSV();" title="Export all billing data to CSV">
                                            <i class="fa fa-download"></i> Export to CSV
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table id="billing_table" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th width="30">
                                                        <input type="checkbox" id="select_all_tickets" onchange="toggleSelectAll(this);">
                                                    </th>
                                                    <th>Ticket ID</th>
                                                    <th>Company</th>
                                                    <th>Branch</th>
                                                    <th>Equipment</th>
                                                    <th>PPM Date</th>
                                                    <th>Ticket Status</th>
                                                    <th>Unit Rate</th>
                                                    <th>Calculated Amount</th>
                                                    <th>Billing Status</th>
                                                    <th>Payment Status</th>
                                                    <th>Billed Amount</th>
                                                    <th>Billing Number</th>
                                                    <th>Billed Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="billing_tbody">
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Details Modal -->
                    <div class="modal fade" id="billingDetailsModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">Billing Details</h4>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <form id="billing_details_form">
                                        <input type="hidden" id="modal_ticket_id" name="TicketID">
                                        <input type="hidden" id="modal_billing_start_date" name="BillingStartDate">
                                        <input type="hidden" id="modal_billing_end_date" name="BillingEndDate">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Billing Status</label>
                                                    <select class="form-control" id="modal_billing_status" name="BillingStatus" onchange="handleBillingStatusChange();" required>
                                                        <option value="Unbilled">Unbilled</option>
                                                        <option value="Billed">Billed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Payment Status</label>
                                                    <select class="form-control" id="modal_payment_status" name="PaymentStatus">
                                                        <option value="Pending">Pending</option>
                                                        <option value="Closed">Closed</option>
                                                        <option value="Billed">Billed</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Billing Number</label>
                                                    <input type="text" class="form-control" id="modal_billing_number" name="BillingNumber" placeholder="Enter billing number">
                                                    <small class="text-muted">Enter billing/invoice number manually</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Send via Zoho PDF</label>
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="modal_send_zoho" name="SendZoho">
                                                        <label class="form-check-label" for="modal_send_zoho">
                                                            Send invoice PDF via Zoho (requires billing number)
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Calculated Amount</label>
                                                    <input type="text" class="form-control" id="modal_calculated_amount" readonly style="background-color: #f5f5f5;">
                                                    <small class="text-muted">Auto-calculated based on asset rate and period</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Billed Amount <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="modal_billed_amount" name="BilledAmount" step="0.01" min="0" required>
                                                    <small class="text-muted">Will be set to calculated amount when status is "Billed"</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Billed Date</label>
                                                    <input type="text" class="form-control datepicker" id="modal_billed_date" name="BilledDate" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Remarks</label>
                                                    <textarea class="form-control" id="modal_remarks" name="Remarks" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="saveBillingDetails();">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </main>

                <?php
                include('../includes/common_footer.php')
                ?>
            </div>
        </div>
    </div>

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/modules/ppm-billing.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // Wait for moment.js to load
            function initDateRangePicker() {
                if (typeof moment !== 'undefined') {
                    // Initialize date range picker
                    $('#date_range').daterangepicker({
                        opens: 'left',
                        locale: {
                            format: 'YYYY-MM-DD',
                            separator: ' - '
                        },
                        startDate: moment().startOf('month'),
                        endDate: moment().endOf('month'),
                        minDate: moment('2020-01-01'), // Allow dates from 2020
                        maxDate: moment('2030-12-31'), // Allow dates up to 2030
                        autoUpdateInput: true,
                        showDropdowns: true,
                        autoApply: true
                    }, function(start, end, label) {
                        // Update the input field with formatted dates
                        var startStr = start.format('YYYY-MM-DD');
                        var endStr = end.format('YYYY-MM-DD');
                        $('#date_range').val(startStr + ' - ' + endStr);
                        loadBillingData();
                    });
                } else {
                    // Fallback if moment.js is not available
                    console.warn('Moment.js not loaded, using fallback date range');
                    var today = new Date();
                    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    var startDateStr = firstDay.toISOString().split('T')[0];
                    var endDateStr = lastDay.toISOString().split('T')[0];
                    $('#date_range').val(startDateStr + ' - ' + endDateStr);
                }
            }
            
            // Initialize datepicker first
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
            
            // Initialize Select2
            $('#filter_company, #filter_branch').select2({
                placeholder: "Select...",
                allowClear: true
            });
            
            // Set default date range value immediately (before date range picker init)
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            var startDateStr = firstDay.toISOString().split('T')[0];
            var endDateStr = lastDay.toISOString().split('T')[0];
            var defaultDateRange = startDateStr + ' - ' + endDateStr;
            $('#date_range').val(defaultDateRange);
            
            // Try to initialize immediately, or wait a bit for moment.js
            if (typeof moment !== 'undefined') {
                initDateRangePicker();
            } else {
                setTimeout(initDateRangePicker, 500);
            }
            
            // Load initial data after a short delay to ensure everything is initialized
            setTimeout(function() {
                console.log('Loading initial billing data...');
                loadBillingData();
            }, 800);
        });
    </script>
</body>
</html>
