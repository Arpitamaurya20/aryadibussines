var convenienceHrTable = null;
var convenienceHrFilterSnapshot = null;

function parseConvenienceHrActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function getConvenienceHrMultiFilterValue(selectId) {
    var values = $('#' + selectId).val();
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function initConvenienceHrMultiSelectFilters() {
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

function syncConvenienceHrFilterSelect2Ui() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function captureConvenienceHrFilters() {
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

function restoreConvenienceHrFilters(filterState) {
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
    syncConvenienceHrFilterSelect2Ui();
}

function encodeConvenienceHrMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function closeConvenienceHrFilterSelect2() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function bindConvenienceHrModalUi() {
    $('#convenience_hr_modal')
        .on('show.bs.modal', function () {
            closeConvenienceHrFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncConvenienceHrFilterSelect2Ui();
        });
}

function getSelectedConvenienceHrIds() {
    var selected = [];
    $('.convenience-hr-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateConvenienceHrSelectionUi() {
    var selected = getSelectedConvenienceHrIds();
    var total = $('.convenience-hr-select').length;
    var count = selected.length;
    $('#convenience_hr_selected_count').text(count + ' selected');
    $('#btn_hr_bulk_approve, #btn_hr_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#convenience_hr_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindConvenienceHrSelectionEvents() {
    $(document).off('change.convenienceHrSelect', '.convenience-hr-select');
    $(document).on('change.convenienceHrSelect', '.convenience-hr-select', updateConvenienceHrSelectionUi);

    $('#convenience_hr_select_all').off('change.convenienceHrSelectAll').on('change.convenienceHrSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.convenience-hr-select').prop('checked', checked);
        updateConvenienceHrSelectionUi();
    });
}

function resetConvenienceHrSelection() {
    $('.convenience-hr-select').prop('checked', false);
    $('#convenience_hr_select_all').prop('checked', false).prop('indeterminate', false);
    updateConvenienceHrSelectionUi();
}

function initConvenienceHrApprovalPage(initialParam) {
    $("#_Nav_Convenience_HR_Approval").addClass("active open");
    $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
    initConvenienceHrMultiSelectFilters();
    bindConvenienceHrSelectionEvents();
    bindConvenienceHrModalUi();
    initConvenienceHrApprovalTable(initialParam);
    refreshConvenienceHrDashboard();
}

function buildConvenienceHrParams(filterState) {
    var state = filterState || captureConvenienceHrFilters();
    var ticket = state.ticket || -1;
    return '?filter_date=' + encodeURIComponent(state.filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.employee))
        + '&status=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.designation))
        + '&ticket_id=' + encodeURIComponent(ticket)
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function refreshConvenienceHrDashboard(filterState) {
    loadConvenienceDashboard('hr', buildConvenienceHrParams(filterState || captureConvenienceHrFilters()));
}

function reloadConvenienceHrApprovalTable(filterState) {
    var params = buildConvenienceHrParams(filterState);
    refreshConvenienceHrDashboard(filterState);
    var selector = '#view-convenience-hr-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-hr-approval-post.php' + params).load(function () {
            restoreConvenienceHrFilters(filterState);
            resetConvenienceHrSelection();
        }, false);
        return;
    }
    initConvenienceHrApprovalTable(params);
}

function initConvenienceHrApprovalTable(param) {
    var selector = '#view-convenience-hr-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-hr-approval-post.php' + param).load(function () {
            resetConvenienceHrSelection();
        }, false);
        return;
    }
    convenienceHrTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-convenience-hr-approval-post.php' + param },
        columns: [
            { data: 'Select', orderable: false, searchable: false },
            { data: null, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'Employee' },
            { data: 'Ticket' },
            { data: 'Journey' },
            { data: 'Amount' },
            { data: 'Remarks' },
            { data: 'RecordDate' },
            { data: 'State' },
            { data: 'Department', responsivePriority: 1 },
            { data: 'Designation', responsivePriority: 5 },
            { data: 'Status' },
            { data: 'PaymentStatus' },
            { data: 'ApprovalInfo' },
            { data: 'Action' }
        ],
        drawCallback: function () {
            resetConvenienceHrSelection();
        }
    });
}

function RefreshConvenienceHrApproval() {
    reloadConvenienceHrApprovalTable(captureConvenienceHrFilters());
}

function setConvenienceHrQuickFilter(type) {
    if (type === 'pending_hr') {
        $('#status_filter').val(['hr_actionable']).trigger('change');
    }
    if (type === 'supervisor_timeout') {
        $('#status_filter').val(['supervisor_timeout']).trigger('change');
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
    RefreshConvenienceHrApproval();
}

function setConvenienceHrModalMode(mode, actionType) {
    document.getElementById('convenience_hr_action_mode').value = mode;
    document.getElementById('convenience_hr_bulk_action_type').value = actionType || '';
    var isBulk = mode === 'bulk';
    $('#convenience_hr_bulk_info').toggleClass('d-none', !isBulk);
    $('#btn_convenience_hr_modal_approve').toggle(!isBulk || actionType === 'approve');
    $('#btn_convenience_hr_modal_reject').toggle(!isBulk || actionType === 'reject');
    $('#convenience_hr_amount_group').toggle(!isBulk || actionType === 'approve');
    $('.modal-title', '#convenience_hr_modal').text(isBulk ? 'HR Bulk ' + (actionType === 'approve' ? 'Approval' : 'Rejection') : 'HR Final Approval');
}

function openConvenienceHrAction(id, amount) {
    convenienceHrFilterSnapshot = captureConvenienceHrFilters();
    setConvenienceHrModalMode('single', '');
    document.getElementById('convenience_hr_action_id').value = id;
    document.getElementById('convenience_hr_approved_amount').value = amount || '';
    document.getElementById('convenience_hr_remarks').value = '';
    $('#convenience_hr_modal').modal('show');
}

function openConvenienceHrBulkModal(actionType) {
    var selected = getSelectedConvenienceHrIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending HR convenience request.');
        return;
    }
    convenienceHrFilterSnapshot = captureConvenienceHrFilters();
    setConvenienceHrModalMode('bulk', actionType);
    document.getElementById('convenience_hr_action_id').value = '';
    document.getElementById('convenience_hr_approved_amount').value = '';
    document.getElementById('convenience_hr_remarks').value = '';
    document.getElementById('convenience_hr_bulk_info').textContent = selected.length + ' record(s) selected.';
    $('#convenience_hr_modal').modal('show');
}

function submitConvenienceHrApprove() {
    var filterState = convenienceHrFilterSnapshot || captureConvenienceHrFilters();
    var mode = document.getElementById('convenience_hr_action_mode').value;
    var payload = {
        approved_amount: document.getElementById('convenience_hr_approved_amount').value,
        remarks: document.getElementById('convenience_hr_remarks').value
    };
    var url = 'action/hr_approve_convenience.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedConvenienceHrIds();
        url = 'action/hr_bulk_approve_convenience.php';
    } else {
        payload.ID = document.getElementById('convenience_hr_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseConvenienceHrActionResponse(data);
        if (!response.error) {
            $('#convenience_hr_modal').modal('hide');
            reloadConvenienceHrApprovalTable(filterState);
            convenienceHrFilterSnapshot = null;
        }
        TechXAlert(response.message);
    }, 'json').fail(function (xhr) {
        TechXAlert('Unable to approve. ' + (xhr.responseText || 'Server error'));
    });
}

function submitConvenienceHrReject() {
    var filterState = convenienceHrFilterSnapshot || captureConvenienceHrFilters();
    var mode = document.getElementById('convenience_hr_action_mode').value;
    var payload = {
        reason: document.getElementById('convenience_hr_remarks').value
    };
    var url = 'action/hr_reject_convenience.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedConvenienceHrIds();
        url = 'action/hr_bulk_reject_convenience.php';
    } else {
        payload.ID = document.getElementById('convenience_hr_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseConvenienceHrActionResponse(data);
        if (!response.error) {
            $('#convenience_hr_modal').modal('hide');
            reloadConvenienceHrApprovalTable(filterState);
            convenienceHrFilterSnapshot = null;
        }
        TechXAlert(response.message);
    }, 'json').fail(function (xhr) {
        TechXAlert('Unable to reject. ' + (xhr.responseText || 'Server error'));
    });
}

function ExportConvenienceHrData() {
    document.getElementById('filter_date_export').value = document.getElementById('filter_date').value;
    document.getElementById('employee_filter_export').value = getConvenienceHrMultiFilterValue('employee_filter');
    document.getElementById('status_export').value = getConvenienceHrMultiFilterValue('status_filter');
    document.getElementById('state_export').value = getConvenienceHrMultiFilterValue('state_filter');
    document.getElementById('department_export').value = getConvenienceHrMultiFilterValue('department_filter');
    document.getElementById('designation_export').value = getConvenienceHrMultiFilterValue('designation_filter');
    document.getElementById('ticket_export').value = document.getElementById('ticket_filter').value || -1;
    document.getElementById('employee_number_export').value = document.getElementById('employee_number_filter').value;
    $.ajax({
        url: '../employees-convenience/action/export_employee_convenience_data.php',
        type: 'POST',
        data: $('#export_form').serialize(),
        success: function () {
            window.location.href = '../employees-convenience/report.xls';
        }
    });
}
