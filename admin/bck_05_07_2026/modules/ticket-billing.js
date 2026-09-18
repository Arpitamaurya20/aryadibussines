var billingTables = {};
var billingInvoicesTable = null;
var selectedTicketRows = [];
var itemizedLineItems = [];
var baseInvoiceTotal = 0;
var hasEditableItemizedRows = false;
var currentInvoiceBillingNumber = '';
var billingKpiChart = null;
var currentQueueStage = 'Eligible';

var TB_STAGE_CONFIG = {
    Eligible: {
        tableId: '#billing_table_eligible',
        tbodyId: '#billing_tbody_eligible',
        badgeId: '#tb_badge_eligible',
        selCountId: '#tb_sel_eligible',
        totalCountId: '#tb_total_eligible',
        checkboxClass: 'ticket-checkbox-eligible',
        selectAllClass: 'tb-select-all-eligible',
        actionBtnId: '#btn_send_for_billing'
    },
    ReadyForBilling: {
        tableId: '#billing_table_ready',
        tbodyId: '#billing_tbody_ready',
        badgeId: '#tb_badge_ready',
        selCountId: '#tb_sel_ready',
        totalCountId: '#tb_total_ready',
        checkboxClass: 'ticket-checkbox-ready',
        selectAllClass: 'tb-select-all-ready',
        actionBtnId: '#btn_bill_selected'
    },
    BillingVerification: {
        tableId: '#billing_table_verification',
        tbodyId: '#billing_tbody_verification',
        badgeId: '#tb_badge_verification',
        selCountId: '#tb_sel_verification',
        totalCountId: '#tb_total_verification',
        checkboxClass: 'ticket-checkbox-verification',
        selectAllClass: 'tb-select-all-verification',
        actionBtnId: '#btn_verify_billing'
    },
    PaymentStatus: {
        tableId: '#billing_table_payment',
        tbodyId: '#billing_tbody_payment',
        badgeId: '#tb_badge_payment',
        selCountId: '#tb_sel_payment',
        totalCountId: '#tb_total_payment',
        checkboxClass: 'ticket-checkbox-payment',
        selectAllClass: 'tb-select-all-payment',
        actionBtnId: '#btn_update_payment'
    },
    PartialBilling: {
        tableId: '#billing_table_partial',
        tbodyId: '#billing_tbody_partial',
        badgeId: '#tb_badge_partial',
        selCountId: '#tb_sel_partial',
        totalCountId: '#tb_total_partial',
        checkboxClass: 'ticket-checkbox-partial',
        selectAllClass: 'tb-select-all-partial',
        actionBtnId: '#btn_partial_update_payment'
    }
};

function formatINR(value) {
    return 'Rs ' + (parseFloat(value || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function roundQty(value, precision) {
    var p = typeof precision === 'number' ? precision : 2;
    var factor = Math.pow(10, p);
    var v = parseFloat(value || 0);
    var rounded = Math.round(v * factor) / factor;
    var epsilon = 1 / Math.pow(10, p + 1);
    if (Math.abs(rounded) < epsilon) {
        rounded = 0;
    }
    return rounded;
}

function getRemainingQtyFromRow($row) {
    return roundQty($row.find('.li-remaining').text() || 0, 2);
}

function parseBillQtyInput(raw) {
    var str = String(raw || '').trim();
    if (str === '') {
        return { isEmpty: true, isPartial: false, value: 0 };
    }
    if (str === '.') {
        return { isEmpty: false, isPartial: true, value: 0 };
    }
    var endsWithDot = str.charAt(str.length - 1) === '.';
    var num = parseFloat(str);
    if (isNaN(num)) {
        return { isEmpty: true, isPartial: false, value: 0 };
    }
    return {
        isEmpty: false,
        isPartial: endsWithDot,
        value: num
    };
}

function applySelectedRowDefaultQty($row) {
    var checkbox = $row.find('.li-select');
    var qtyInput = $row.find('.li-bill-qty');
    if (checkbox.is(':disabled') || !checkbox.is(':checked')) {
        return;
    }
    if (qtyInput.is(':focus')) {
        return;
    }
    var remaining = getRemainingQtyFromRow($row);
    var parsed = parseBillQtyInput(qtyInput.val());
    if (remaining > 0 && (parsed.isEmpty || (!parsed.isPartial && parsed.value <= 0))) {
        qtyInput.val(remaining.toFixed(2));
    }
}

function finalizeBillQtyInput(qtyInput) {
    var $input = $(qtyInput);
    var $row = $input.closest('tr[data-ticketpk]');
    if (!$row.length || $input.prop('disabled') || $input.prop('readonly')) {
        return;
    }
    var remaining = getRemainingQtyFromRow($row);
    var parsed = parseBillQtyInput($input.val());
    var qty = parsed.isEmpty ? 0 : parsed.value;
    if (qty < 0) {
        qty = 0;
    }
    if (qty > remaining) {
        qty = remaining;
    }
    qty = roundQty(qty, 2);
    $input.val(qty.toFixed(2));
    recalculateItemizedTotal();
}

function getBillingFilterParams() {
    var dates = getDateFilters();
    return {
        CorporateID: $('#filter_company').val() || -1,
        BranchID: $('#filter_branch').val() || -1,
        RegionID: $('#filter_region').val() || -1,
        StateID: $('#filter_state').val() || -1,
        TicketStatus: $('#filter_ticket_status').val() || '',
        TicketType: $('#filter_ticket_type').val() || '',
        StartDate: dates.StartDate,
        EndDate: dates.EndDate
    };
}

function destroyBillingSelect2(selector) {
    var $els = selector ? $(selector) : $('.tb-filter-select');
    $els.each(function () {
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }
    });
}

function initBillingFilterSelect2(selector) {
    var $els = selector ? $(selector) : $('.tb-filter-select');
    destroyBillingSelect2(selector);
    $els.select2({
        placeholder: 'Select or type to search...',
        allowClear: true,
        width: '100%',
        dropdownParent: $(document.body)
    });
}

function isBillingSelect2Target(target) {
    return $(target).closest('.select2-container, .select2-dropdown, #tb_filter_offcanvas').length > 0;
}

function loadStates(preserveSelected) {
    var selectedState = preserveSelected ? ($('#filter_state').val() || '') : '';
    destroyBillingSelect2('#filter_state');
    $('#filter_state').html('<option value="">All States</option>');
    $.get('../tickets-billing/ajax/get_states_list.php', function (response) {
        if (!response.error && response.data) {
            $.each(response.data, function (_, state) {
                $('#filter_state').append('<option value="' + state.ID + '">' + (state.StateName || '') + '</option>');
            });
            if (selectedState) {
                $('#filter_state').val(selectedState);
            }
        }
        initBillingFilterSelect2('#filter_state');
        loadBranches(true);
        updateActiveFiltersSummary();
    }, 'json');
}

function loadBranches(preserveSelected) {
    var companyId = $('#filter_company').val() || '';
    var regionId = $('#filter_region').val() || -1;
    var stateId = $('#filter_state').val() || -1;
    var selectedBranch = preserveSelected ? ($('#filter_branch').val() || '') : '';
    destroyBillingSelect2('#filter_branch');
    $('#filter_branch').html('<option value="">All Branches</option>');
    $.get('../tickets-billing/ajax/get_branches_list.php', {
        CompanyID: companyId || -1,
        RegionID: regionId,
        StateID: stateId
    }, function (response) {
        if (!response.error && response.data) {
            $.each(response.data, function (_, branch) {
                $('#filter_branch').append('<option value="' + branch.ID + '">' + (branch.BranchSite || '') + '</option>');
            });
            if (selectedBranch) {
                $('#filter_branch').val(selectedBranch);
            }
        }
        initBillingFilterSelect2('#filter_branch');
        updateActiveFiltersSummary();
    }, 'json');
}

function toggleBillingFilterCanvas() {
    var willOpen = !$('#tb_filter_offcanvas').hasClass('show');
    if (willOpen) {
        initBillingFilterSelect2();
    } else {
        destroyBillingSelect2();
    }
    $('#tb_filter_offcanvas').toggleClass('show');
    $('#tb_filter_offcanvas_backdrop').toggleClass('show');
    $('body').toggleClass('tb-billing-filters-open', willOpen);
}

function closeBillingFilterCanvas() {
    destroyBillingSelect2();
    $('#tb_filter_offcanvas').removeClass('show');
    $('#tb_filter_offcanvas_backdrop').removeClass('show');
    $('body').removeClass('tb-billing-filters-open');
}

function applyBillingFiltersAndClose() {
    closeBillingFilterCanvas();
    loadCurrentTabData();
}

function getActiveQueueStage() {
    var $activeTab = $('#ticketBillingTabs .nav-link.active');
    return $activeTab.attr('data-queue-stage') || currentQueueStage || 'Eligible';
}

function getStageConfig(stage) {
    return TB_STAGE_CONFIG[stage] || TB_STAGE_CONFIG.Eligible;
}

function getSelectedTicketPKs(stage) {
    var cfg = getStageConfig(stage || getActiveQueueStage());
    var selected = [];
    $('.' + cfg.checkboxClass + ':checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateStageSelectionUI(stage) {
    var cfg = getStageConfig(stage);
    var selectedCount = $('.' + cfg.checkboxClass + ':checked').length;
    var totalCount = $('.' + cfg.checkboxClass).length;
    var partialSelectedCount = 0;
    $('.' + cfg.checkboxClass + ':checked').each(function () {
        var tr = $(this).closest('tr');
        if (tr.attr('data-partial-remaining') === '1') {
            partialSelectedCount++;
        }
    });
    $(cfg.selCountId).text(selectedCount);
    $(cfg.totalCountId).text(totalCount);
    if (cfg.actionBtnId) {
        $(cfg.actionBtnId).prop('disabled', selectedCount === 0);
    }
    if (stage === 'PartialBilling') {
        $('#btn_partial_mark_closed').prop('disabled', selectedCount === 0);
        $('#btn_partial_bill_remaining').prop('disabled', partialSelectedCount === 0);
    }
    var $selectAll = $('.tb-select-all[data-stage="' + stage + '"]');
    if ($selectAll.length) {
        $selectAll.prop('checked', totalCount > 0 && selectedCount === totalCount);
        $selectAll.prop('indeterminate', selectedCount > 0 && selectedCount < totalCount);
    }
}

function isPartialRemainingRow(row) {
    var billingStatus = row.BillingStatus || 'Unbilled';
    var remaining = parseFloat(row.RemainingAmount || 0);
    var hasRemaining = parseInt(row.HasRemainingQty || 0, 10) === 1;
    return billingStatus === 'Partially Billed' && (remaining > 0 || hasRemaining);
}

function buildBillRemainingBtn(ticketPK) {
    return '<button type="button" class="btn btn-sm btn-info" onclick="billRemainingSingle(' + ticketPK + ');" title="Bill remaining quantity"><i class="fa fa-plus-circle"></i></button>';
}

function clearTabSelection(stage) {
    var cfg = getStageConfig(stage);
    $('.' + cfg.checkboxClass).prop('checked', false);
    $('.tb-select-all[data-stage="' + stage + '"]').prop('checked', false).prop('indeterminate', false);
    updateStageSelectionUI(stage);
}

function loadQueueCounts() {
    var params = getBillingFilterParams();
    $.get('../tickets-billing/action/get_queue_counts.php', params, function (response) {
        if (response.error || !response.data) {
            return;
        }
        var counts = response.data;
        $('#tb_badge_eligible').text(counts.Eligible || 0);
        $('#tb_badge_ready').text(counts.ReadyForBilling || 0);
        $('#tb_badge_verification').text(counts.BillingVerification || 0);
        $('#tb_badge_payment').text(counts.PaymentStatus || 0);
        $('#tb_badge_partial').text(counts.PartialBilling || 0);
        $('#tb_badge_pdf').text(counts.BillingPdf || 0);
    }, 'json');
}

function loadCurrentTabData() {
    var stage = getActiveQueueStage();
    loadQueueCounts();
    if (stage === 'BillingPdf') {
        loadBillingInvoices();
        return;
    }
    if (stage === 'ReadyForBilling') {
        loadBillingStatistics();
    }
    loadBillingTickets(stage);
}

function resetBillingFilters() {
    destroyBillingSelect2();
    $('#filter_region').val('');
    $('#filter_state').val('');
    $('#filter_company').val('');
    $('#filter_branch').html('<option value="">All Branches</option>');
    $('#filter_ticket_status').val('');
    $('#filter_ticket_type').val('');
    $('#search_ticket_id').val('');
    initBillingFilterSelect2();
    loadBranches(false);
    updateActiveFiltersSummary();
}

function updateActiveFiltersSummary() {
    var parts = [];
    var regionText = $('#filter_region option:selected').text();
    var stateText = $('#filter_state option:selected').text();
    var companyText = $('#filter_company option:selected').text();
    var branchText = $('#filter_branch option:selected').text();
    var statusText = $('#filter_ticket_status option:selected').text();
    var typeText = $('#filter_ticket_type option:selected').text();

    if ($('#filter_region').val()) {
        parts.push('Region: ' + regionText);
    }
    if ($('#filter_state').val()) {
        parts.push('State: ' + stateText);
    }
    if ($('#filter_company').val()) {
        parts.push('Company: ' + companyText);
    }
    if ($('#filter_branch').val()) {
        parts.push('Branch: ' + branchText);
    }
    if ($('#filter_ticket_status').val()) {
        parts.push('Status: ' + statusText);
    }
    if ($('#filter_ticket_type').val()) {
        parts.push('Type: ' + typeText);
    }
    if ($('#search_ticket_id').val()) {
        parts.push('Ticket: ' + $('#search_ticket_id').val());
    }

    $('#tb_active_filters_summary').text(parts.length ? parts.join(' | ') : 'All filters');
}

function getDateFilters() {
    var dateRange = $('#date_range').val();
    var startDate = moment().startOf('month').format('YYYY-MM-DD');
    var endDate = moment().endOf('month').format('YYYY-MM-DD');
    if (dateRange && dateRange.indexOf(' - ') > -1) {
        var parts = dateRange.split(' - ');
        if (parts.length === 2) {
            startDate = parts[0].trim();
            endDate = parts[1].trim();
        }
    }
    return { StartDate: startDate, EndDate: endDate };
}

function loadBillingStatistics() {
    var params = getBillingFilterParams();
    $.get('../tickets-billing/action/get_billing_statistics.php', params, function (response) {
        if (response.error || !response.data) {
            return;
        }
        var stats = response.data;
        $('#stat_total_tickets').text(stats.total_tickets || 0);
        $('#stat_total_amount').text(formatINR(stats.total_amount || 0));
        var quoteApprovedCount = (stats.quotation_status && stats.quotation_status.Approved) ? stats.quotation_status.Approved : 0;
        var quoteNotApprovedCount = (stats.quotation_status && stats.quotation_status.NotApproved) ? stats.quotation_status.NotApproved : 0;
        var quoteApprovedAmount = (stats.quotation_amounts && stats.quotation_amounts.Approved) ? stats.quotation_amounts.Approved : 0;
        var quoteNotApprovedAmount = (stats.quotation_amounts && stats.quotation_amounts.NotApproved) ? stats.quotation_amounts.NotApproved : 0;
        $('#stat_quote_approved_count').text(quoteApprovedCount);
        $('#stat_quote_approved_amount').text(formatINR(quoteApprovedAmount));
        $('#stat_quote_notapproved_count').text(quoteNotApprovedCount);
        $('#stat_quote_notapproved_amount').text(formatINR(quoteNotApprovedAmount));
        $('#stat_billed_count').text(stats.billing_status.Billed || 0);
        $('#stat_billed_amount').text(formatINR(stats.billing_amounts.Billed || 0));
        $('#stat_unbilled_count').text(stats.billing_status.Unbilled || 0);
        $('#stat_unbilled_amount').text(formatINR(stats.billing_amounts.Unbilled || 0));
        $('#stat_partially_paid_count').text((stats.partially_paid && stats.partially_paid.count) ? stats.partially_paid.count : 0);
        $('#stat_partially_paid_amount').text(formatINR((stats.partially_paid && stats.partially_paid.amount) ? stats.partially_paid.amount : 0));
        renderBillingKpiChart(stats);
    }, 'json');
}

function renderBillingKpiChart(stats) {
    var canvas = document.getElementById('billing_kpi_chart');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }
    var billedCount = parseInt((stats.billing_status && stats.billing_status.Billed) || 0, 10);
    var unbilledCount = parseInt((stats.billing_status && stats.billing_status.Unbilled) || 0, 10);
    var partialCount = parseInt((stats.billing_status && stats.billing_status.PartiallyBilled) || 0, 10);

    var billedAmount = parseFloat((stats.billing_amounts && stats.billing_amounts.Billed) || 0);
    var unbilledAmount = parseFloat((stats.billing_amounts && stats.billing_amounts.Unbilled) || 0);
    var partialAmount = parseFloat((stats.billing_amounts && stats.billing_amounts.PartiallyBilled) || 0);

    if (billingKpiChart) {
        billingKpiChart.destroy();
    }

    billingKpiChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ['Billed', 'Unbilled', 'Partially Billed'],
            datasets: [
                {
                    type: 'bar',
                    label: 'Ticket Count',
                    data: [billedCount, unbilledCount, partialCount],
                    backgroundColor: ['#10b981', '#f59e0b', '#ec4899'],
                    yAxisID: 'y-axis-count'
                },
                {
                    type: 'line',
                    label: 'Amount (Rs)',
                    data: [billedAmount, unbilledAmount, partialAmount],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.2)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#1d4ed8',
                    fill: false,
                    tension: 0.35,
                    yAxisID: 'y-axis-amount'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'top' },
            tooltips: {
                callbacks: {
                    label: function (tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex] || {};
                        var rawValue = tooltipItem.yLabel || 0;
                        if (dataset.yAxisID === 'y-axis-amount') {
                            return (dataset.label || 'Amount') + ': ' + formatINR(rawValue);
                        }
                        return (dataset.label || 'Count') + ': ' + rawValue;
                    }
                }
            },
            scales: {
                yAxes: [
                    {
                        id: 'y-axis-count',
                        position: 'left',
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Ticket Count'
                        }
                    },
                    {
                        id: 'y-axis-amount',
                        position: 'right',
                        gridLines: { drawOnChartArea: false },
                        ticks: {
                            beginAtZero: true,
                            callback: function (value) {
                                return value >= 100000
                                    ? (value / 100000).toFixed(1) + 'L'
                                    : Math.round(value);
                            }
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Amount (Rs)'
                        }
                    }
                ]
            }
        }
    });
}

function buildBillingStatusBadge(billingStatus) {
    var billingBadge = 'warning';
    if (billingStatus === 'Billed') {
        billingBadge = 'success';
    } else if (billingStatus === 'Partially Billed') {
        billingBadge = 'info';
    }
    return '<span class="badge badge-' + billingBadge + '">' + billingStatus + '</span>';
}

function buildPaymentStatusBadge(paymentStatus) {
    var paymentBadge = 'warning';
    if (paymentStatus === 'Closed') {
        paymentBadge = 'success';
    } else if (paymentStatus === 'Partially Paid') {
        paymentBadge = 'info';
    }
    return '<span class="badge badge-' + paymentBadge + '">' + (paymentStatus || 'Pending') + '</span>';
}

function formatCustomerPoCell(row) {
    var available = parseInt(row.CustomerPoAvailable || 0, 10) === 1;
    var remarks = (row.CustomerPoRemarks || '').trim();
    if (available && remarks) {
        return '<span class="tb-po-remarks" title="' + escapeHtml(remarks) + '">' + escapeHtml(remarks) + '</span>';
    }
    return '<span class="text-muted">-</span>';
}

function renderStageTicketRow(stage, row, queueNo) {
    var billingStatus = row.BillingStatus || 'Unbilled';
    var billedAmount = parseFloat(row.BilledAmount || 0);
    var calcAmount = parseFloat(row.CalculatedAmount || 0);
    if ((billingStatus === 'Unbilled' || billingStatus === 'Partially Billed') && billedAmount <= 0) {
        billedAmount = calcAmount;
    }
    var cfg = getStageConfig(stage);
    var checkbox = '<input type="checkbox" class="' + cfg.checkboxClass + ' tb-ticket-checkbox" data-stage="' + stage + '" value="' + row.TicketPK + '">';

    if (stage === 'Eligible') {
        return '<tr data-ticketpk="' + row.TicketPK + '">' +
            '<td class="tb-queue-no">' + queueNo + '</td><td>' + checkbox + '</td>' +
            '<td>' + escapeHtml(row.TicketNumber || '') + '</td>' +
            '<td>' + escapeHtml(row.CompanyName || '') + '</td>' +
            '<td>' + escapeHtml(row.BranchSite || '') + '</td>' +
            '<td>' + escapeHtml(row.CreatedDate || '') + '</td>' +
            '<td>' + escapeHtml(row.TicketStatus || '') + '</td>' +
            '<td>' + escapeHtml(row.QuotationID || '-') + '</td>' +
            '<td>' + escapeHtml(row.QuotationStatus || '') + '</td>' +
            '<td>' + formatCustomerPoCell(row) + '</td>' +
            '<td>' + formatINR(calcAmount) + '</td>' +
            '</tr>';
    }

    var remainingAmount = parseFloat(row.RemainingAmount || 0);
    if (remainingAmount <= 0 && billingStatus === 'Partially Billed') {
        remainingAmount = Math.max(calcAmount - billedAmount, 0);
    }
    var partialRemaining = isPartialRemainingRow(row) ? 1 : 0;
    var rowAttrs = ' data-ticketpk="' + row.TicketPK + '" data-billing-status="' + escapeHtml(billingStatus) + '" data-partial-remaining="' + partialRemaining + '" data-remaining-amount="' + remainingAmount + '"';

    if (stage === 'ReadyForBilling') {
        return '<tr' + rowAttrs + '>' +
            '<td class="tb-queue-no">' + queueNo + '</td><td>' + checkbox + '</td>' +
            '<td>' + escapeHtml(row.TicketNumber || '') + '</td>' +
            '<td>' + escapeHtml(row.CompanyName || '') + '</td>' +
            '<td>' + escapeHtml(row.BranchSite || '') + '</td>' +
            '<td>' + escapeHtml(row.CreatedDate || '') + '</td>' +
            '<td>' + escapeHtml(row.QueuedDate || '-') + '</td>' +
            '<td>' + escapeHtml(row.TicketStatus || '') + '</td>' +
            '<td>' + escapeHtml(row.QuotationID || '-') + '</td>' +
            '<td>' + escapeHtml(row.QuotationStatus || '') + '</td>' +
            '<td>' + formatINR(calcAmount) + '</td>' +
            '<td>' + formatINR(remainingAmount) + '</td>' +
            '<td>' + buildBillingStatusBadge(billingStatus) + '</td>' +
            '<td><button class="btn btn-sm btn-primary" onclick="toggleBillingStatus(' + row.TicketPK + ', \'' + billingStatus.replace(/'/g, "\\'") + '\')"><i class="fa fa-toggle-on"></i></button></td>' +
            '</tr>';
    }

    if (stage === 'BillingVerification') {
        var viewBtn = row.BillingNumber
            ? '<button type="button" class="btn btn-sm btn-info btn-view-invoice" data-billing-no="' + escapeHtml(row.BillingNumber) + '"><i class="fa fa-eye"></i></button> '
            : '';
        return '<tr' + rowAttrs + '>' +
            '<td class="tb-queue-no">' + queueNo + '</td><td>' + checkbox + '</td>' +
            '<td>' + escapeHtml(row.TicketNumber || '') + '</td>' +
            '<td>' + escapeHtml(row.CompanyName || '') + '</td>' +
            '<td>' + escapeHtml(row.BranchSite || '') + '</td>' +
            '<td><strong>' + escapeHtml(row.BillingNumber || '-') + '</strong></td>' +
            '<td>' + escapeHtml(row.BilledDate || '-') + '</td>' +
            '<td>' + formatINR(calcAmount) + '</td>' +
            '<td>' + formatINR(billedAmount) + '</td>' +
            '<td>' + formatINR(remainingAmount) + '</td>' +
            '<td>' + buildBillingStatusBadge(billingStatus) + '</td>' +
            '<td>' + buildPaymentStatusBadge(row.PaymentStatus) + '</td>' +
            '<td>' + viewBtn + '</td>' +
            '</tr>';
    }

    if (stage === 'PaymentStatus') {
        var pdfBtnPay = row.BillingNumber
            ? '<button type="button" class="btn btn-sm btn-success btn-view-invoice" data-billing-no="' + escapeHtml(row.BillingNumber) + '"><i class="fa fa-file-pdf"></i></button>'
            : '';
        return '<tr' + rowAttrs + '>' +
            '<td class="tb-queue-no">' + queueNo + '</td><td>' + checkbox + '</td>' +
            '<td>' + escapeHtml(row.TicketNumber || '') + '</td>' +
            '<td>' + escapeHtml(row.CompanyName || '') + '</td>' +
            '<td>' + escapeHtml(row.BranchSite || '') + '</td>' +
            '<td><strong>' + escapeHtml(row.BillingNumber || '-') + '</strong></td>' +
            '<td>' + escapeHtml(row.BilledDate || '-') + '</td>' +
            '<td>' + escapeHtml(row.VerifiedDate || '-') + '</td>' +
            '<td>' + formatINR(billedAmount) + '</td>' +
            '<td>' + buildPaymentStatusBadge(row.PaymentStatus) + '</td>' +
            '<td>' + pdfBtnPay + '</td>' +
            '</tr>';
    }

    if (stage === 'PartialBilling') {
        var pdfBtnPartial = row.BillingNumber
            ? '<button type="button" class="btn btn-sm btn-success btn-view-invoice" data-billing-no="' + escapeHtml(row.BillingNumber) + '"><i class="fa fa-file-pdf"></i></button> '
            : '';
        var remainBtnPartial = partialRemaining ? buildBillRemainingBtn(row.TicketPK) : '';
        return '<tr' + rowAttrs + '>' +
            '<td class="tb-queue-no">' + queueNo + '</td><td>' + checkbox + '</td>' +
            '<td>' + escapeHtml(row.TicketNumber || '') + '</td>' +
            '<td>' + escapeHtml(row.CompanyName || '') + '</td>' +
            '<td>' + escapeHtml(row.BranchSite || '') + '</td>' +
            '<td><strong>' + escapeHtml(row.BillingNumber || '-') + '</strong></td>' +
            '<td>' + escapeHtml(row.BilledDate || '-') + '</td>' +
            '<td>' + formatINR(calcAmount) + '</td>' +
            '<td>' + formatINR(billedAmount) + '</td>' +
            '<td>' + formatINR(remainingAmount) + '</td>' +
            '<td>' + buildBillingStatusBadge(billingStatus) + '</td>' +
            '<td>' + buildPaymentStatusBadge(row.PaymentStatus) + '</td>' +
            '<td>' + pdfBtnPartial + remainBtnPartial + '</td>' +
            '</tr>';
    }

    return '';
}

function loadBillingTickets(stage) {
    stage = stage || getActiveQueueStage();
    if (stage === 'BillingPdf') {
        return;
    }
    var cfg = getStageConfig(stage);
    var search = $('#search_ticket_id').val() || '';
    var params = getBillingFilterParams();
    params.TicketID = search;
    params.QueueStage = stage;

    $.get('../tickets-billing/action/get_billing_tickets.php', params, function (response) {
        var tbody = $(cfg.tbodyId);
        tbody.empty();
        if (billingTables[stage]) {
            billingTables[stage].clear().destroy();
            billingTables[stage] = null;
        }

        var colCount = $(cfg.tableId + ' thead th').length;
        if (response.error || !response.data || response.data.length === 0) {
            tbody.html('<tr><td colspan="' + colCount + '" class="text-center">No records found in this queue</td></tr>');
            $(cfg.totalCountId).text(0);
            updateStageSelectionUI(stage);
            return;
        }

        $.each(response.data, function (idx, row) {
            tbody.append(renderStageTicketRow(stage, row, idx + 1));
        });

        var orderCol = stage === 'ReadyForBilling' ? 5 : 3;
        billingTables[stage] = $(cfg.tableId).DataTable({
            pageLength: 25,
            order: [[orderCol, 'desc']],
            columnDefs: [{ orderable: false, targets: [0, 1] }]
        });

        $(cfg.totalCountId).text(response.data.length);
        $(cfg.badgeId).text(response.data.length);
        updateStageSelectionUI(stage);
    }, 'json');
}

function loadBillingData() {
    loadQueueCounts();
    loadBillingStatistics();
    loadBillingTickets('ReadyForBilling');
}

function sendSelectedForBilling() {
    var selected = getSelectedTicketPKs('Eligible');
    if (selected.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    if (!confirm('Send ' + selected.length + ' ticket(s) to Ready for Billing queue?')) {
        return;
    }
    $.post('../tickets-billing/action/update_queue_stage.php', {
        TicketPKs: selected,
        QueueStage: 'ReadyForBilling'
    }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to send tickets for billing');
            return;
        }
        alert(response.message || 'Tickets sent for billing');
        loadCurrentTabData();
    }, 'json');
}

function verifySelectedBilling() {
    var selected = getSelectedTicketPKs('BillingVerification');
    if (selected.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    if (!confirm('Verify ' + selected.length + ' ticket(s)?\n\nFully billed tickets go to Payment Status.\nPartially billed tickets go to Partial Billing & Payment tab.')) {
        return;
    }
    $.post('../tickets-billing/action/update_queue_stage.php', {
        TicketPKs: selected,
        QueueStage: 'PaymentStatus',
        Verified: 1
    }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to verify billing');
            return;
        }
        alert(response.message || 'Billing verified');
        switchToBillingTab('PartialBilling');
        loadCurrentTabData();
    }, 'json');
}

function billRemainingSelected() {
    var stage = getActiveQueueStage();
    var selected = getSelectedTicketPKs(stage);
    var partialOnly = [];
    $('.' + getStageConfig(stage).checkboxClass + ':checked').each(function () {
        var tr = $(this).closest('tr');
        if (tr.attr('data-partial-remaining') === '1') {
            partialOnly.push($(this).val());
        }
    });
    if (partialOnly.length === 0) {
        alert('Please select at least one partially billed ticket that still has remaining quantity.');
        return;
    }
    if (!confirm('Send ' + partialOnly.length + ' ticket(s) back to Ready for Billing to bill the remaining quantity?\n\nPrevious invoices and billed line items will be kept.')) {
        return;
    }
    $.post('../tickets-billing/action/update_queue_stage.php', {
        TicketPKs: partialOnly,
        BillRemaining: 1
    }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to send tickets for remaining billing');
            return;
        }
        alert(response.message || 'Tickets ready to bill remaining quantity. Go to tab 2: Ready for Billing.');
        switchToBillingTab('ReadyForBilling');
        loadCurrentTabData();
    }, 'json');
}

function switchToBillingTab(queueStage) {
    var tabMap = {
        Eligible: '#tab-select-billing-link',
        ReadyForBilling: '#tab-ready-billing-link',
        BillingVerification: '#tab-billing-verification-link',
        PaymentStatus: '#tab-payment-status-link',
        PartialBilling: '#tab-partial-billing-link',
        BillingPdf: '#tab-billing-pdf-link'
    };
    var tabSelector = tabMap[queueStage] || tabMap.ReadyForBilling;
    currentQueueStage = queueStage;
    $(tabSelector).tab('show');
}

function openPartialPaymentModal() {
    openPaymentUpdateModal('PartialBilling');
}

function markPartialPaymentClosed() {
    var selected = getSelectedTicketPKs('PartialBilling');
    if (selected.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    if (!confirm('Mark payment as Closed for ' + selected.length + ' selected ticket(s)?\n\nIf billing is still partial, you can bill remaining quantity later from this tab.')) {
        return;
    }
    $.post('../tickets-billing/action/save_bulk_invoice.php', {
        TicketPKs: selected,
        PaymentStatus: 'Closed',
        PaymentOnly: 1,
        BillingMode: 'full',
        BillingNumber: '',
        BilledDate: moment().format('YYYY-MM-DD'),
        Remarks: 'Partial payment marked closed from Partial Billing tab'
    }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to update payment status');
            return;
        }
        alert(response.message || 'Payment marked as closed');
        loadCurrentTabData();
    }, 'json');
}

function billRemainingSingle(ticketPK) {
    if (!ticketPK) {
        return;
    }
    if (!confirm('Send this ticket back to Ready for Billing to bill the remaining quantity?\n\nPrevious invoices and billed line items will be kept.')) {
        return;
    }
    $.post('../tickets-billing/action/update_queue_stage.php', {
        TicketPKs: [ticketPK],
        BillRemaining: 1
    }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to send ticket for remaining billing');
            return;
        }
        alert(response.message || 'Ticket moved to Ready for Billing. Use Line Item mode to bill remaining qty.');
        switchToBillingTab('ReadyForBilling');
        loadCurrentTabData();
    }, 'json');
}

function moveSelectedQueueStage(targetStage) {
    var stage = getActiveQueueStage();
    var selected = getSelectedTicketPKs(stage);
    if (selected.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    if (!confirm('Move ' + selected.length + ' ticket(s) to ' + targetStage + '?')) {
        return;
    }
    $.post('../tickets-billing/action/update_queue_stage.php', {
        TicketPKs: selected,
        QueueStage: targetStage
    }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to update queue');
            return;
        }
        alert(response.message || 'Queue updated');
        loadCurrentTabData();
    }, 'json');
}

function openPaymentUpdateModal(fromStage) {
    var stage = fromStage || 'PaymentStatus';
    var selected = getSelectedTicketPKs(stage);
    if (selected.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    window.bulkTicketPKs = selected;
    selectedTicketRows = [];
    var html = '<table class="table table-bordered"><thead><tr><th>#</th><th>Ticket PK</th></tr></thead><tbody>';
    $.each(selected, function (i, ticketPK) {
        html += '<tr><td>' + (i + 1) + '</td><td>' + ticketPK + '</td></tr>';
    });
    html += '</tbody></table>';
    $('#invoiceTickets').html(html);
    baseInvoiceTotal = 0;
    $('#invoiceTotalAmount').text(formatINR(0));
    $('#bulkBillingForm')[0].reset();
    $('#billing_mode').val('full');
    onBillingModeChange();
    $('#billingDetailsModal .modal-title').text('Update Payment Status');
    $('#billingDetailsModal').modal('show');
}

function toggleBillingStatus(ticketPK, currentStatus) {
    if (!confirm('Toggle billing status for selected ticket?')) {
        return;
    }
    $.post('../tickets-billing/action/toggle_billing_status.php', {
        TicketPK: ticketPK,
        CurrentBillingStatus: currentStatus
    }, function (response) {
        if (response.error) {
            alert(response.message || 'Error updating billing status');
            return;
        }
        loadCurrentTabData();
    }, 'json');
}

function openBulkInvoiceModal() {
    var selected = [];
    var rows = [];
    var hasPartialTicket = false;
    $('.ticket-checkbox-ready:checked').each(function () {
        var tr = $(this).closest('tr');
        var billingStatus = tr.attr('data-billing-status') || '';
        var remainingAmount = parseFloat(tr.attr('data-remaining-amount') || 0);
        var quoteAmount = parseFloat((tr.find('td:eq(10)').text() || '').replace(/[^\d.-]/g, '')) || 0;
        var billAmount = billingStatus === 'Partially Billed' && remainingAmount > 0 ? remainingAmount : quoteAmount;
        if (billingStatus === 'Partially Billed') {
            hasPartialTicket = true;
        }
        selected.push($(this).val());
        rows.push({
            ticketPK: $(this).val(),
            ticket: tr.find('td:eq(2)').text().trim(),
            amount: billAmount,
            billingStatus: billingStatus
        });
    });

    if (selected.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }

    window.bulkTicketPKs = selected;
    selectedTicketRows = rows;
    var html = '<table class="table table-bordered"><thead><tr><th>#</th><th>Ticket</th><th>Amount to Bill</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    var total = 0;
    $.each(rows, function (i, row) {
        total += row.amount;
        html += '<tr><td>' + (i + 1) + '</td><td>' + row.ticket + '</td><td>' + formatINR(row.amount) + '</td>' +
            '<td>' + escapeHtml(row.billingStatus || 'Unbilled') + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-primary ticket-lineitem-btn" data-ticketpk="' + row.ticketPK + '" disabled onclick="loadSingleTicketLineItems(' + row.ticketPK + ');">Line Item Breakdown</button></td></tr>';
    });
    html += '</tbody></table>';
    if (hasPartialTicket) {
        html = '<div class="alert alert-info py-2">Partially billed ticket(s) selected. Use <strong>Line Item / Quantity Wise</strong> mode to bill only the remaining quantity. Already billed lines stay locked.</div>' + html;
    }
    $('#invoiceTickets').html(html);
    baseInvoiceTotal = total;
    $('#invoiceTotalAmount').text(formatINR(baseInvoiceTotal));
    $('#bulkBillingForm')[0].reset();
    $('#billing_mode').val(hasPartialTicket ? 'itemized' : 'full');
    onBillingModeChange();
    $('#billingDetailsModal .modal-title').text('Bulk Invoice');
    $('#billingDetailsModal').modal('show');
}

function onBillingModeChange() {
    var mode = $('#billing_mode').val();
    if (mode === 'itemized') {
        if (!window.bulkTicketPKs || window.bulkTicketPKs.length === 0) {
            alert('Please select at least one ticket first.');
            $('#line_item_billing_section').hide();
            return;
        }
        $('#line_item_billing_section').show();
        $('.ticket-lineitem-btn').prop('disabled', false);
        $('#line_item_billing_container').html('<div class="text-muted mb-2">Click "Line Item Breakdown" against each ticket to load and edit quantities.</div>');
        itemizedLineItems = [];
        hasEditableItemizedRows = false;
        $('#btn_fill_remaining_qty').prop('disabled', true);
        $('#btn_clear_selected_qty').prop('disabled', true);
        if (window.bulkTicketPKs && window.bulkTicketPKs.length > 0) {
            $.each(window.bulkTicketPKs, function (_, ticketPK) {
                loadSingleTicketLineItems(ticketPK);
            });
        }
        recalculateItemizedTotal();
    } else {
        $('#line_item_billing_section').hide();
        $('.ticket-lineitem-btn').prop('disabled', true);
        $('#line_item_billing_container').empty();
        itemizedLineItems = [];
        hasEditableItemizedRows = false;
        $('#invoiceTotalAmount').text(formatINR(baseInvoiceTotal));
    }
}

function loadSingleTicketLineItems(ticketPK) {
    if ($('#billing_mode').val() !== 'itemized') {
        return;
    }
    var targetId = 'line-item-table-ticket-' + ticketPK;
    var cardId = 'line-item-card-' + ticketPK;
    if ($('#' + cardId).length === 0) {
        var cardHtml = '<div class="card mb-3" id="' + cardId + '">' +
            '<div class="card-header d-flex justify-content-between align-items-center">' +
            '<strong>Ticket ' + ticketPK + ' Line Items</strong>' +
            '<button type="button" class="btn btn-sm btn-light" onclick="$(\'#' + cardId + '\').remove(); recalculateItemizedTotal();">Remove</button>' +
            '</div>' +
            '<div class="card-body">' +
            '<div class="table-responsive"><table class="table table-bordered table-sm tb-line-items-table">' +
            '<thead><tr><th>Select</th><th>Line Item</th><th>Original Qty</th><th>Already Billed</th><th>Remaining Qty</th><th>Per Item Price</th><th class="tb-bill-qty-col">Bill Qty</th><th>Line Amount</th></tr></thead>' +
            '<tbody id="' + targetId + '"><tr><td colspan="8" class="text-center">Loading line items...</td></tr></tbody>' +
            '</table></div></div></div>';
        $('#line_item_billing_container').append(cardHtml);
    } else {
        $('#' + targetId).html('<tr><td colspan="8" class="text-center">Refreshing line items...</td></tr>');
    }

    $.get('../tickets-billing/action/get_ticket_line_items.php', { TicketPK: ticketPK }, function (response) {
        if (response.error) {
            $('#' + targetId).html('<tr><td colspan="8" class="text-danger text-center">' + (response.message || 'Unable to load line items') + '</td></tr>');
            return;
        }
        var tbody = $('#' + targetId);
        tbody.empty();
        var currentItems = response.data || [];
        if (currentItems.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center">No line items available for this ticket.</td></tr>');
            updateItemizedActionState();
            recalculateItemizedTotal();
            return;
        }
        var selectableCount = 0;
        $.each(currentItems, function (idx, item) {
            var remaining = roundQty(item.RemainingQty, 2);
            var price = parseFloat(item.PerItemPrice || 0);
            var isFullyBilled = remaining <= 0;
            var defaultQty = isFullyBilled ? 0 : remaining;
            if (!isFullyBilled) {
                selectableCount++;
            }
            var tr = '<tr data-ticketpk="' + ticketPK + '" data-quotationitemid="' + item.QuotationItemID + '" data-peritemprice="' + price + '">' +
                '<td><input type="checkbox" class="li-select" ' + (!isFullyBilled ? 'checked' : '') + ' ' + (isFullyBilled ? 'disabled' : '') + ' onchange="onLineSelectToggle(this);"></td>' +
                '<td>' + (item.LineItemName || '') + '</td>' +
                '<td>' + roundQty(item.OriginalQty || 0, 2) + '</td>' +
                '<td>' + roundQty(item.AlreadyBilledQty || 0, 2) + '</td>' +
                '<td class="li-remaining">' + remaining + '</td>' +
                '<td>' + formatINR(price) + '</td>' +
                '<td class="tb-bill-qty-col"><input type="text" inputmode="decimal" autocomplete="off" class="form-control li-bill-qty" data-max-qty="' + remaining + '" value="' + defaultQty.toFixed(2) + '" ' + (isFullyBilled ? 'disabled readonly' : '') + ' placeholder="0.00"></td>' +
                '<td class="li-line-amt">' + formatINR(defaultQty * price) + '</td>' +
                '</tr>';
            tbody.append(tr);
        });
        if (selectableCount === 0) {
            tbody.prepend('<tr><td colspan="8" class="text-info text-center">All line items for this ticket are already fully billed.</td></tr>');
        }
        updateItemizedActionState();
        recalculateItemizedTotal();
    }, 'json');
}

function updateItemizedActionState() {
    var editableRows = $('#line_item_billing_container .li-select:not(:disabled)').length;
    hasEditableItemizedRows = editableRows > 0;
    $('#btn_fill_remaining_qty').prop('disabled', !hasEditableItemizedRows);
    $('#btn_clear_selected_qty').prop('disabled', !hasEditableItemizedRows);

    if ($('#billing_mode').val() === 'itemized') {
        if (!hasEditableItemizedRows) {
            if ($('#itemized_payment_update_notice').length === 0) {
                $('#line_item_billing_section').prepend(
                    '<div id="itemized_payment_update_notice" class="alert alert-info py-2">' +
                    'All selected tickets are already fully billed. Quantity editing is locked. You can still update Payment Status, Billing Date, Billing Number, and Remarks, then Save.' +
                    '</div>'
                );
            }
        } else {
            $('#itemized_payment_update_notice').remove();
        }
    }
}

function onLineSelectToggle(element) {
    var $row = $(element).closest('tr');
    applySelectedRowDefaultQty($row);
    recalculateItemizedTotal();
}

function recalculateItemizedTotal() {
    if ($('#billing_mode').val() !== 'itemized') {
        $('#invoiceTotalAmount').text(formatINR(baseInvoiceTotal));
        return;
    }
    var total = 0;
    $('#line_item_billing_container tr[data-ticketpk]').each(function () {
        var $row = $(this);
        var checkbox = $row.find('.li-select');
        var checked = checkbox.is(':checked') && !checkbox.is(':disabled');
        var remaining = roundQty($row.find('.li-remaining').text() || 0, 2);
        var qtyInput = $row.find('.li-bill-qty');
        var isFocused = qtyInput.is(':focus');
        var parsed = parseBillQtyInput(qtyInput.val());
        var qty = parsed.value;

        if (remaining <= 0) {
            qty = 0;
            if (!isFocused) {
                qtyInput.val('0.00');
            }
            qtyInput.prop('disabled', true);
            checkbox.prop('checked', false);
            checked = false;
        } else if (!isFocused) {
            applySelectedRowDefaultQty($row);
            parsed = parseBillQtyInput(qtyInput.val());
            qty = parsed.value;
            if (checked && (parsed.isEmpty || qty <= 0)) {
                qty = remaining;
            }
            if (qty < 0) {
                qty = 0;
            }
            if (qty > remaining) {
                qty = remaining;
            }
            qty = roundQty(qty, 2);
            qtyInput.val(qty.toFixed(2));
        } else {
            if (qty < 0) {
                qty = 0;
            }
            if (qty > remaining) {
                qty = remaining;
            }
        }

        var price = parseFloat($row.attr('data-peritemprice') || 0);
        var lineTotal = checked ? roundQty(qty * price, 2) : 0;
        $row.find('.li-line-amt').text(formatINR(lineTotal));
        total += lineTotal;
    });
    $('#invoiceTotalAmount').text(formatINR(total));
}

function fillRemainingForSelectedLines() {
    if (!hasEditableItemizedRows) {
        return;
    }
    $('#line_item_billing_container tr[data-ticketpk]').each(function () {
        var checkbox = $(this).find('.li-select');
        if (!checkbox.is(':checked') || checkbox.is(':disabled')) {
            return;
        }
        var remaining = roundQty($(this).find('.li-remaining').text() || 0, 2);
        var qtyInput = $(this).find('.li-bill-qty');
        if (remaining > 0) {
            qtyInput.val(remaining.toFixed(2));
        } else {
            qtyInput.val('0.00');
        }
    });
    recalculateItemizedTotal();
}

function clearSelectedLineQty() {
    if (!hasEditableItemizedRows) {
        return;
    }
    $('#line_item_billing_container tr[data-ticketpk]').each(function () {
        var checkbox = $(this).find('.li-select');
        if (!checkbox.is(':checked') || checkbox.is(':disabled')) {
            return;
        }
        var qtyInput = $(this).find('.li-bill-qty');
        qtyInput.val('0.00');
    });
    recalculateItemizedTotal();
}

function collectItemizedPayload() {
    var payload = [];
    var meta = {
        totalRows: 0,
        selectableRows: 0
    };
    $('#line_item_billing_container tr[data-ticketpk]').each(function () {
        meta.totalRows++;
        var $row = $(this);
        applySelectedRowDefaultQty($row);
        var checkbox = $(this).find('.li-select');
        if (!checkbox.is(':disabled')) {
            meta.selectableRows++;
        }
        var checked = checkbox.is(':checked') && !checkbox.is(':disabled');
        if (!checked) {
            return;
        }
        var remaining = getRemainingQtyFromRow($row);
        var qty = roundQty($(this).find('.li-bill-qty').val() || 0, 2);
        if (qty <= 0 && remaining > 0) {
            qty = remaining;
            $(this).find('.li-bill-qty').val(qty.toFixed(2));
        }
        if (qty <= 0) {
            return;
        }
        payload.push({
            TicketPK: parseInt($(this).attr('data-ticketpk'), 10),
            QuotationItemID: parseInt($(this).attr('data-quotationitemid'), 10),
            BilledQty: qty
        });
    });
    payload._meta = meta;
    return payload;
}

function saveBillingDetails() {
    if (!window.bulkTicketPKs || window.bulkTicketPKs.length === 0) {
        alert('No tickets selected');
        return;
    }
    var payload = {};
    $.each($('#bulkBillingForm').serializeArray(), function (_, item) {
        payload[item.name] = item.value;
    });
    payload.TicketPKs = window.bulkTicketPKs;
    payload.PaymentOnly = 0;
    payload.ReopenBilling = 0;
    if (payload.BillingMode === 'itemized') {
        $('#line_item_billing_container .li-bill-qty:not(:disabled)').each(function () {
            finalizeBillQtyInput(this);
        });
        var itemizedPayload = collectItemizedPayload();
        var meta = itemizedPayload._meta || { totalRows: 0, selectableRows: 0 };
        delete itemizedPayload._meta;
        if (itemizedPayload.length === 0) {
            if (meta.totalRows === 0) {
                alert('Line items are not loaded yet. Please wait or click Line Item Breakdown and then save.');
                return;
            } else if (meta.selectableRows === 0) {
                // Payment correction/update flow: all lines fully billed, but user can still update payment status/remarks.
                payload.BillingMode = 'full';
                payload.LineItems = JSON.stringify([]);
                alert('All line items are already fully billed. Saving as payment/status update only.');
            } else {
                alert('Please select line items with billing quantity.');
                return;
            }
        } else {
            payload.LineItems = JSON.stringify(itemizedPayload);
        }
    } else {
        payload.LineItems = JSON.stringify([]);
    }

    $.post('../tickets-billing/action/save_bulk_invoice.php', payload, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to save invoice');
            return;
        }
        $('#billingDetailsModal').modal('hide');
        loadCurrentTabData();
    }, 'json');
}

function savePaymentOnlyUpdate() {
    if (!window.bulkTicketPKs || window.bulkTicketPKs.length === 0) {
        alert('No tickets selected');
        return;
    }
    var payload = {};
    $.each($('#bulkBillingForm').serializeArray(), function (_, item) {
        payload[item.name] = item.value;
    });
    payload.TicketPKs = window.bulkTicketPKs;
    payload.BillingMode = 'full';
    payload.PaymentOnly = 1;
    payload.ReopenBilling = 0;
    payload.LineItems = JSON.stringify([]);

    $.post('../tickets-billing/action/save_bulk_invoice.php', payload, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to update payment details');
            return;
        }
        $('#billingDetailsModal').modal('hide');
        loadCurrentTabData();
    }, 'json');
}

function reopenTicketBilling() {
    if (!window.bulkTicketPKs || window.bulkTicketPKs.length === 0) {
        alert('No tickets selected');
        return;
    }
    if (!confirm('This will reset billed line items for selected tickets and re-open them for billing. Continue?')) {
        return;
    }

    var payload = {};
    $.each($('#bulkBillingForm').serializeArray(), function (_, item) {
        payload[item.name] = item.value;
    });
    payload.TicketPKs = window.bulkTicketPKs;
    payload.BillingMode = 'full';
    payload.PaymentOnly = 0;
    payload.ReopenBilling = 1;
    payload.LineItems = JSON.stringify([]);

    $.post('../tickets-billing/action/save_bulk_invoice.php', payload, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to re-open billing');
            return;
        }
        $('#billingDetailsModal').modal('hide');
        loadCurrentTabData();
    }, 'json');
}

function escapeHtml(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getInvoiceDateFilters() {
    var dateRange = $('#inv_date_range').val();
    var startDate = moment().subtract(11, 'months').startOf('month').format('YYYY-MM-DD');
    var endDate = moment().endOf('month').format('YYYY-MM-DD');
    if (dateRange && dateRange.indexOf(' - ') > -1) {
        var parts = dateRange.split(' - ');
        if (parts.length === 2) {
            startDate = parts[0].trim();
            endDate = parts[1].trim();
        }
    }
    return { StartDate: startDate, EndDate: endDate };
}

function loadInvoiceBranches() {
    var companyId = $('#inv_filter_company').val() || '';
    $('#inv_filter_branch').html('<option value="">All Branches</option>');
    if (!companyId) {
        return;
    }
    $.get('../tickets-billing/ajax/get_branches_list.php', { CompanyID: companyId }, function (response) {
        if (!response.error && response.data) {
            $.each(response.data, function (_, branch) {
                $('#inv_filter_branch').append('<option value="' + branch.ID + '">' + branch.BranchSite + '</option>');
            });
        }
    }, 'json');
}

function loadBillingInvoices() {
    var dates = getInvoiceDateFilters();
    $.get('../tickets-billing/action/get_billing_invoices.php', {
        CorporateID: $('#inv_filter_company').val() || -1,
        BranchID: $('#inv_filter_branch').val() || -1,
        StartDate: dates.StartDate,
        EndDate: dates.EndDate,
        BillingNumber: $('#inv_search_billing_no').val() || ''
    }, function (response) {
        if (billingInvoicesTable) {
            billingInvoicesTable.destroy();
            billingInvoicesTable = null;
        }
        var tbody = $('#billing_invoices_tbody');
        tbody.empty();
        if (response.error || !response.data || response.data.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center">No billing invoices found.</td></tr>');
            billingInvoicesTable = $('#billing_invoices_table').DataTable({ pageLength: 25, order: [] });
            return;
        }
        $.each(response.data, function (_, row) {
            var paymentStatus = row.PaymentStatus || 'Pending';
            var paymentBadge = 'warning';
            if (paymentStatus === 'Closed') {
                paymentBadge = 'success';
            } else if (paymentStatus === 'Partially Paid') {
                paymentBadge = 'info';
            }
            var billingNo = row.BillingNumber || '';
            var tr = '<tr>' +
                '<td><strong>' + escapeHtml(billingNo) + '</strong></td>' +
                '<td>' + escapeHtml(row.BilledDate || '') + '</td>' +
                '<td>' + escapeHtml(row.CompanyNames || '') + '</td>' +
                '<td class="text-center">' + (row.TicketCount || 0) + '</td>' +
                '<td><span class="badge badge-' + paymentBadge + '">' + escapeHtml(paymentStatus) + '</span></td>' +
                '<td class="text-right">' + formatINR(row.TotalAmount || 0) + '</td>' +
                '<td>' +
                '<button type="button" class="btn btn-sm btn-primary mr-1 btn-view-invoice" data-billing-no="' + escapeHtml(billingNo) + '"><i class="fa fa-eye"></i> View</button>' +
                '<button type="button" class="btn btn-sm btn-secondary btn-print-invoice" data-billing-no="' + escapeHtml(billingNo) + '"><i class="fa fa-print"></i> Print</button>' +
                '</td>' +
                '</tr>';
            tbody.append(tr);
        });
        billingInvoicesTable = $('#billing_invoices_table').DataTable({
            pageLength: 25,
            order: [[1, 'desc']]
        });
    }, 'json');
}

function buildBillingInvoiceHtml(data) {
    var header = data.header || {};
    var issuer = data.issuer || {};
    var tickets = data.tickets || [];
    var billTo = (data.bill_to_companies || []).join(', ');
    if (!billTo) {
        billTo = '—';
    }

    var issuerBlock = '';
    if (issuer.HeaderImageUrl) {
        issuerBlock += '<div class="mb-2"><img src="' + escapeHtml(issuer.HeaderImageUrl) + '" alt="Company Logo" style="max-height:70px;"></div>';
    }
    issuerBlock += '<h4 class="mb-1">' + escapeHtml(issuer.CompanyName || 'TechXpert') + '</h4>';
    if (issuer.CompanyAddress) {
        issuerBlock += '<div class="text-muted small">' + escapeHtml(issuer.CompanyAddress).replace(/\n/g, '<br>') + '</div>';
    }
    var issuerMeta = [];
    if (issuer.GstNumber) {
        issuerMeta.push('GSTIN: ' + escapeHtml(issuer.GstNumber));
    }
    if (issuer.PanNumber) {
        issuerMeta.push('PAN: ' + escapeHtml(issuer.PanNumber));
    }
    if (issuer.Email) {
        issuerMeta.push('Email: ' + escapeHtml(issuer.Email));
    }
    if (issuer.Phone) {
        issuerMeta.push('Phone: ' + escapeHtml(issuer.Phone));
    }
    if (issuerMeta.length) {
        issuerBlock += '<div class="small text-muted mt-1">' + issuerMeta.join(' &nbsp;|&nbsp; ') + '</div>';
    }

    var ticketsHtml = '';
    $.each(tickets, function (_, ticket) {
        var linesHtml = '';
        var lineNo = 0;
        $.each(ticket.line_items || [], function (__, line) {
            lineNo++;
            linesHtml += '<tr>' +
                '<td class="text-center">' + lineNo + '</td>' +
                '<td>' + escapeHtml(line.LineItemName || '') + '</td>' +
                '<td class="text-right">' + roundQty(line.BilledQty || 0, 2) + '</td>' +
                '<td class="text-right">' + formatINR(line.PerItemPrice || 0) + '</td>' +
                '<td class="text-right">' + formatINR(line.BilledLineAmount || 0) + '</td>' +
                '</tr>';
        });
        ticketsHtml += '<div class="tb-ticket-block">' +
            '<div class="tb-ticket-block-hdr">' +
            'Ticket: ' + escapeHtml(ticket.TicketID || '') +
            ' &nbsp;|&nbsp; Type: ' + escapeHtml(ticket.TicketType || '') +
            ' &nbsp;|&nbsp; ' + escapeHtml(ticket.CompanyName || '') +
            ' &nbsp;|&nbsp; ' + escapeHtml(ticket.BranchSite || '') +
            '</div>' +
            '<table class="table table-sm table-bordered tb-inv-lines mb-0">' +
            '<thead><tr>' +
            '<th width="40">#</th><th>Description</th><th width="90" class="text-right">Qty</th>' +
            '<th width="110" class="text-right">Rate</th><th width="120" class="text-right">Amount</th>' +
            '</tr></thead><tbody>' + linesHtml + '</tbody>' +
            '<tfoot><tr><td colspan="4" class="text-right"><strong>Ticket Subtotal</strong></td>' +
            '<td class="text-right"><strong>' + formatINR(ticket.TicketBilledAmount || 0) + '</strong></td></tr></tfoot>' +
            '</table></div>';
    });

    var remarks = header.Remarks ? '<div class="mt-3"><strong>Remarks:</strong><br>' + escapeHtml(header.Remarks).replace(/\n/g, '<br>') + '</div>' : '';

    return '<div class="row mb-4">' +
        '<div class="col-md-7">' + issuerBlock + '</div>' +
        '<div class="col-md-5 text-md-right">' +
        '<div class="tb-inv-title">TAX INVOICE</div>' +
        '<dl class="tb-inv-meta row justify-content-md-end mb-0">' +
        '<dt class="col-sm-5 text-md-right">Invoice No.</dt><dd class="col-sm-7 text-md-right">' + escapeHtml(header.BillingNumber || '') + '</dd>' +
        '<dt class="col-sm-5 text-md-right">Invoice Date</dt><dd class="col-sm-7 text-md-right">' + escapeHtml(header.BilledDate || '') + '</dd>' +
        '<dt class="col-sm-5 text-md-right">Payment Status</dt><dd class="col-sm-7 text-md-right">' + escapeHtml(header.PaymentStatus || 'Pending') + '</dd>' +
        '<dt class="col-sm-5 text-md-right">Tickets</dt><dd class="col-sm-7 text-md-right">' + (header.TicketCount || tickets.length) + '</dd>' +
        '</dl></div></div>' +
        '<div class="row mb-4"><div class="col-md-12">' +
        '<div class="border rounded p-3 bg-light">' +
        '<strong>Bill To:</strong><br>' + escapeHtml(billTo) +
        '</div></div></div>' +
        ticketsHtml +
        '<div class="row mt-3"><div class="col-md-8"></div><div class="col-md-4">' +
        '<div class="tb-inv-grand-total text-right">Grand Total (Excl. GST): ' + formatINR(header.TotalAmount || 0) + '</div>' +
        '<div class="small text-muted text-right mt-1">All amounts are excluding GST as per quotation line items.</div>' +
        '</div></div>' +
        remarks;
}

function viewBillingInvoice(billingNumber, autoPrint) {
    if (!billingNumber) {
        return;
    }
    currentInvoiceBillingNumber = billingNumber;
    var pdfUrl = '../tickets-billing/action/generate_billing_invoice_pdf.php?BillingNumber=' +
        encodeURIComponent(billingNumber) + '&Mode=preview';

    if (autoPrint) {
        window.open(pdfUrl, '_blank');
        return;
    }

    $('#invoice_print_area').html(
        '<iframe src="' + pdfUrl + '" style="width:100%;height:80vh;border:0;" title="Billing Invoice PDF Preview"></iframe>'
    );
    $('#invoiceViewModal').modal('show');
}

function printTicketBillingInvoice() {
    openBillingInvoicePdf('preview');
}

function openBillingInvoicePdf(mode) {
    if (!currentInvoiceBillingNumber) {
        alert('Please open an invoice first.');
        return;
    }
    var outputMode = (mode === 'download') ? 'download' : 'preview';
    var url = '../tickets-billing/action/generate_billing_invoice_pdf.php?BillingNumber=' +
        encodeURIComponent(currentInvoiceBillingNumber) + '&Mode=' + outputMode;
    window.open(url, '_blank');
}

function exportBillingToCSV() {
    var params = getBillingFilterParams();
    var url = '../tickets-billing/action/export_billing_csv.php?CorporateID=' + params.CorporateID +
        '&BranchID=' + params.BranchID +
        '&RegionID=' + params.RegionID +
        '&StateID=' + params.StateID +
        '&TicketStatus=' + encodeURIComponent(params.TicketStatus) +
        '&TicketType=' + encodeURIComponent(params.TicketType) +
        '&StartDate=' + params.StartDate +
        '&EndDate=' + params.EndDate;
    window.open(url, '_blank');
}

$(document).ready(function () {
    $('#date_range').daterangepicker({
        opens: 'left',
        locale: { format: 'YYYY-MM-DD', separator: ' - ' },
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month')
    }, function () {
        loadCurrentTabData();
    });

    $('#inv_date_range').daterangepicker({
        opens: 'left',
        locale: { format: 'YYYY-MM-DD', separator: ' - ' },
        startDate: moment().subtract(11, 'months').startOf('month'),
        endDate: moment().endOf('month')
    });

    $('#tb_filter_offcanvas_backdrop').on('mousedown', function (e) {
        if (isBillingSelect2Target(e.target)) {
            return;
        }
        closeBillingFilterCanvas();
    });

    $(document).on('mousedown', function (e) {
        if (!$('#tb_filter_offcanvas').hasClass('show')) {
            return;
        }
        if (isBillingSelect2Target(e.target)) {
            e.stopPropagation();
        }
    });

    $('#filter_region').on('select2:select select2:clear', function () {
        loadBranches(false);
    });
    $('#filter_state').on('select2:select select2:clear', function () {
        loadBranches(false);
    });
    $('#filter_company').on('select2:select select2:clear', function () {
        loadBranches(false);
    });
    $('#filter_branch, #filter_region, #filter_state, #filter_ticket_status, #filter_ticket_type').on('change', updateActiveFiltersSummary);
    $('#search_ticket_id').on('input', updateActiveFiltersSummary);
    $('#search_ticket_id').on('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadCurrentTabData();
        }
    });

    $(document).on('input', '.li-bill-qty', function () {
        var val = $(this).val();
        if (val !== '' && !/^-?\d*\.?\d*$/.test(val)) {
            $(this).val(val.replace(/[^\d.]/g, '').replace(/(\..*)\./g, '$1'));
        }
        recalculateItemizedTotal();
    });
    $(document).on('blur', '.li-bill-qty', function () {
        finalizeBillQtyInput(this);
    });
    $(document).on('keydown', '.li-bill-qty', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            finalizeBillQtyInput(this);
            $(this).blur();
        }
    });

    $(document).on('change', '.tb-ticket-checkbox', function () {
        var stage = $(this).attr('data-stage');
        updateStageSelectionUI(stage);
    });

    $(document).on('change', '.tb-select-all', function () {
        var stage = $(this).attr('data-stage');
        var cfg = getStageConfig(stage);
        var checked = $(this).is(':checked');
        $('.' + cfg.checkboxClass).prop('checked', checked);
        updateStageSelectionUI(stage);
    });

    $('#ticketBillingTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        currentQueueStage = $(e.target).attr('data-queue-stage') || 'Eligible';
        loadCurrentTabData();
    });

    $('#inv_filter_company').on('change', function () {
        loadInvoiceBranches();
    });
    $('#inv_search_billing_no').on('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadBillingInvoices();
        }
    });

    $(document).on('click', '.btn-view-invoice', function () {
        viewBillingInvoice($(this).attr('data-billing-no'), false);
    });
    $(document).on('click', '.btn-print-invoice', function () {
        viewBillingInvoice($(this).attr('data-billing-no'), true);
    });

    initBillingFilterSelect2();
    updateActiveFiltersSummary();
    loadCurrentTabData();
});
