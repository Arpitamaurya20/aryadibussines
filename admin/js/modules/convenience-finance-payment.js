var convenienceFinanceTable = null;
var convenienceFinanceFilterSnapshot = null;

function parseConvenienceFinanceActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function initConvenienceFinanceMultiSelectFilters() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            return;
        }
        $el.select2({
            placeholder: $el.data('placeholder') || 'All',
            allowClear: true,
            width: '100%',
            dropdownParent: $(document.body)
        });
    });
}

function syncConvenienceFinanceFilterSelect2Ui() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function captureConvenienceFinanceFilters() {
    return {
        employee: $('#employee_filter').val() || [],
        status: $('#status_filter').val() || [],
        state: $('#state_filter').val() || [],
        department: $('#department_filter').val() || [],
        designation: $('#designation_filter').val() || [],
        filter_date: document.getElementById('filter_date').value,
        employee_number: document.getElementById('employee_number_filter').value,
        ticket: document.getElementById('ticket_filter').value
    };
}

function restoreConvenienceFinanceFilters(filterState) {
    if (!filterState) {
        return;
    }
    $('#employee_filter').val(filterState.employee.length ? filterState.employee : null).trigger('change');
    $('#status_filter').val(filterState.status.length ? filterState.status : null).trigger('change');
    $('#state_filter').val(filterState.state.length ? filterState.state : null).trigger('change');
    $('#department_filter').val(filterState.department.length ? filterState.department : null).trigger('change');
    $('#designation_filter').val(filterState.designation.length ? filterState.designation : null).trigger('change');
    document.getElementById('filter_date').value = filterState.filter_date || '';
    document.getElementById('employee_number_filter').value = filterState.employee_number || '';
    document.getElementById('ticket_filter').value = filterState.ticket || '';
    syncConvenienceFinanceFilterSelect2Ui();
}

function encodeConvenienceFinanceMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function closeConvenienceFinanceFilterSelect2() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function bindConvenienceFinanceModalUi() {
    $('#convenience_finance_modal')
        .on('show.bs.modal', function () {
            closeConvenienceFinanceFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncConvenienceFinanceFilterSelect2Ui();
        });
}

function getSelectedConvenienceFinanceIds() {
    var selected = [];
    $('.convenience-finance-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateConvenienceFinanceSelectionUi() {
    var selected = getSelectedConvenienceFinanceIds();
    var total = $('.convenience-finance-select').length;
    var count = selected.length;
    $('#convenience_finance_selected_count').text(count + ' selected');
    $('#btn_finance_bulk_paid').prop('disabled', count === 0);
    $('#btn_finance_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#convenience_finance_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindConvenienceFinanceSelectionEvents() {
    $(document).off('change.convenienceFinanceSelect', '.convenience-finance-select');
    $(document).on('change.convenienceFinanceSelect', '.convenience-finance-select', updateConvenienceFinanceSelectionUi);

    $('#convenience_finance_select_all').off('change.convenienceFinanceSelectAll').on('change.convenienceFinanceSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.convenience-finance-select').prop('checked', checked);
        updateConvenienceFinanceSelectionUi();
    });
}

function resetConvenienceFinanceSelection() {
    $('.convenience-finance-select').prop('checked', false);
    $('#convenience_finance_select_all').prop('checked', false).prop('indeterminate', false);
    updateConvenienceFinanceSelectionUi();
}

function initConvenienceFinancePaymentPage(initialParam) {
    $("#_Nav_Convenience_Finance_Payment").addClass("active open");
    $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
    initConvenienceFinanceMultiSelectFilters();
    bindConvenienceFinanceSelectionEvents();
    bindConvenienceFinanceModalUi();
    initConvenienceFinancePaymentTable(initialParam);
    refreshConvenienceFinanceDashboard();
}

function buildConvenienceFinanceParams(filterState) {
    var state = filterState || captureConvenienceFinanceFilters();
    var ticket = state.ticket || -1;
    return '?filter_date=' + encodeURIComponent(state.filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.employee))
        + '&status=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.designation))
        + '&ticket_id=' + encodeURIComponent(ticket)
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function refreshConvenienceFinanceDashboard(filterState) {
    loadConvenienceDashboard('finance', buildConvenienceFinanceParams(filterState || captureConvenienceFinanceFilters()));
}

function reloadConvenienceFinancePaymentTable(filterState) {
    var params = buildConvenienceFinanceParams(filterState);
    refreshConvenienceFinanceDashboard(filterState);
    var selector = '#view-convenience-finance-payment';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-finance-payment-post.php' + params).load(function () {
            restoreConvenienceFinanceFilters(filterState);
            resetConvenienceFinanceSelection();
        }, false);
        return;
    }
    initConvenienceFinancePaymentTable(params);
}

function initConvenienceFinancePaymentTable(param) {
    var selector = '#view-convenience-finance-payment';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-finance-payment-post.php' + param).load(function () {
            resetConvenienceFinanceSelection();
        }, false);
        return;
    }
    convenienceFinanceTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-convenience-finance-payment-post.php' + param },
        columns: [
            { data: 'Select', orderable: false, searchable: false },
            { data: null, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'Employee' },
            { data: 'Ticket' },
            { data: 'Journey' },
            { data: 'Amount' },
            { data: 'RecordDate' },
            { data: 'State' },
            { data: 'Department', responsivePriority: 1 },
            { data: 'PaymentStatus' },
            { data: 'ApprovalInfo' },
            { data: 'Action' }
        ],
        drawCallback: function () {
            resetConvenienceFinanceSelection();
        }
    });
}

function RefreshConvenienceFinancePayment() {
    reloadConvenienceFinancePaymentTable(captureConvenienceFinanceFilters());
}

function setConvenienceFinanceQuickFilter(type) {
    if (type === 'pending_payment') {
        $('#status_filter').val(['finance_actionable']).trigger('change');
    }
    if (type === 'payment_done') {
        $('#status_filter').val(['6']).trigger('change');
    }
    if (type === '365days') {
        var endDate = moment().format('YYYY-MM-DD');
        var startDate = moment().subtract(365, 'days').format('YYYY-MM-DD');
        document.getElementById('filter_date').value = startDate + ' - ' + endDate;
    }
    if (type === 'all') {
        document.getElementById('filter_date').value = '2000-01-01 - ' + moment().format('YYYY-MM-DD');
        $('#status_filter').val(null).trigger('change');
    }
    RefreshConvenienceFinancePayment();
}

function resetConvenienceFinancePaymentForm() {
    document.getElementById('convenience_finance_payment_reference').value = '';
    document.getElementById('convenience_finance_payment_mode').value = '';
    document.getElementById('convenience_finance_payment_remarks').value = '';
}

function setConvenienceFinanceModalMode(mode, actionType) {
    document.getElementById('convenience_finance_action_mode').value = mode;
    document.getElementById('convenience_finance_action_type').value = actionType || 'paid';
    var isBulk = mode === 'bulk';
    var isReject = actionType === 'reject';
    $('#convenience_finance_bulk_info').toggleClass('d-none', !isBulk);
    $('#convenience_finance_paid_fields').toggle(!isReject);
    $('#btn_convenience_finance_modal_paid').toggle(!isReject && (!isBulk || actionType === 'paid'));
    $('#btn_convenience_finance_modal_reject').toggle(isReject || (!isBulk && actionType !== 'paid'));
    if (isBulk) {
        $('#btn_convenience_finance_modal_paid').toggle(actionType === 'paid');
        $('#btn_convenience_finance_modal_reject').toggle(actionType === 'reject');
    }
    $('#convenience_finance_remarks_label').text(isReject ? 'Rejection Reason' : 'Remarks');
    document.getElementById('convenience_finance_payment_remarks').placeholder = isReject ? 'Reason for rejection' : 'Optional remarks';
    $('.modal-title', '#convenience_finance_modal').text(
        isBulk
            ? 'Finance Bulk ' + (actionType === 'reject' ? 'Rejection' : 'Payment')
            : (isReject ? 'Reject Payment' : 'Mark Payment Done')
    );
}

function openConvenienceFinanceMarkPaid(id) {
    convenienceFinanceFilterSnapshot = captureConvenienceFinanceFilters();
    setConvenienceFinanceModalMode('single', 'paid');
    document.getElementById('convenience_finance_action_id').value = id;
    resetConvenienceFinancePaymentForm();
    $('#convenience_finance_modal').modal('show');
}

function openConvenienceFinanceReject(id) {
    convenienceFinanceFilterSnapshot = captureConvenienceFinanceFilters();
    setConvenienceFinanceModalMode('single', 'reject');
    document.getElementById('convenience_finance_action_id').value = id;
    resetConvenienceFinancePaymentForm();
    $('#convenience_finance_modal').modal('show');
}

function openConvenienceFinanceBulkModal(actionType) {
    var selected = getSelectedConvenienceFinanceIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending payment record.');
        return;
    }
    convenienceFinanceFilterSnapshot = captureConvenienceFinanceFilters();
    setConvenienceFinanceModalMode('bulk', actionType || 'paid');
    document.getElementById('convenience_finance_action_id').value = '';
    document.getElementById('convenience_finance_bulk_info').textContent = selected.length + ' record(s) selected.';
    resetConvenienceFinancePaymentForm();
    $('#convenience_finance_modal').modal('show');
}

function submitConvenienceFinanceMarkPaid() {
    var filterState = convenienceFinanceFilterSnapshot || captureConvenienceFinanceFilters();
    var mode = document.getElementById('convenience_finance_action_mode').value;
    var payload = {
        payment_reference: document.getElementById('convenience_finance_payment_reference').value,
        payment_mode: document.getElementById('convenience_finance_payment_mode').value,
        payment_remarks: document.getElementById('convenience_finance_payment_remarks').value
    };
    var url = 'action/finance_mark_paid_convenience.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedConvenienceFinanceIds();
        url = 'action/finance_bulk_mark_paid_convenience.php';
    } else {
        payload.ID = document.getElementById('convenience_finance_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseConvenienceFinanceActionResponse(data);
        if (!response.error) {
            $('#convenience_finance_modal').modal('hide');
            reloadConvenienceFinancePaymentTable(filterState);
            convenienceFinanceFilterSnapshot = null;
        }
        TechXAlert(response.message);
    }, 'json').fail(function (xhr) {
        TechXAlert('Unable to mark payment. ' + (xhr.responseText || 'Server error'));
    });
}

function submitConvenienceFinanceReject() {
    var filterState = convenienceFinanceFilterSnapshot || captureConvenienceFinanceFilters();
    var mode = document.getElementById('convenience_finance_action_mode').value;
    var payload = {
        reason: document.getElementById('convenience_finance_payment_remarks').value
    };
    var url = 'action/finance_reject_convenience.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedConvenienceFinanceIds();
        url = 'action/finance_bulk_reject_convenience.php';
    } else {
        payload.ID = document.getElementById('convenience_finance_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseConvenienceFinanceActionResponse(data);
        if (!response.error) {
            $('#convenience_finance_modal').modal('hide');
            reloadConvenienceFinancePaymentTable(filterState);
            convenienceFinanceFilterSnapshot = null;
        }
        TechXAlert(response.message);
    }, 'json').fail(function (xhr) {
        TechXAlert('Unable to reject payment. ' + (xhr.responseText || 'Server error'));
    });
}
