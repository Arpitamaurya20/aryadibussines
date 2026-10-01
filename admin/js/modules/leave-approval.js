var leaveApprovalTable = null;
var leaveApprovalFilterSnapshot = null;

function parseLeaveActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function leaveActionSucceeded(response) {
    if (!response || typeof response !== 'object') {
        return false;
    }
    if (response.success === true || response.success === 1 || response.success === '1') {
        return true;
    }
    if (response.error === false || response.error === 0 || response.error === '0') {
        return true;
    }
    if (response.summary && parseInt(response.summary.success, 10) > 0) {
        return true;
    }
    return false;
}

function refreshLeaveApprovalTable(filterState, callback) {
    var state = filterState || captureLeaveApprovalFilters();
    var params = buildLeaveApprovalParams(state);
    var selector = '#view-leave-approval';
    var table = leaveApprovalTable;

    if (!table || !$.fn.DataTable.isDataTable(selector)) {
        initLeaveApprovalTable(params);
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }

    table.ajax.url('ajax/view-leave-approval-post.php' + params);
    table.ajax.reload(function () {
        restoreLeaveApprovalFilters(state);
        resetLeaveSupervisorSelection();
        if (typeof callback === 'function') {
            callback();
        }
    }, false);
}

function getLeaveMultiFilterValue(selectId) {
    var values = $('#' + selectId).val();
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function initLeaveMultiSelectFilters() {
    $('.leave-multi-filter').each(function () {
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

function syncLeaveFilterSelect2Ui() {
    $('.leave-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function closeLeaveFilterSelect2() {
    $('.leave-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function captureLeaveApprovalFilters() {
    return {
        employee: $('#employee_filter').val() || [],
        status: $('#status_filter').val() || [],
        state: $('#state_filter').val() || [],
        department: $('#department_filter').val() || [],
        designation: $('#designation_filter').val() || [],
        leave_type: $('#leave_type_filter').val() || [],
        filter_date: document.getElementById('filter_date').value,
        employee_number: document.getElementById('employee_number_filter').value
    };
}

function restoreLeaveApprovalFilters(filterState) {
    if (!filterState) {
        return;
    }
    $('#employee_filter').val(filterState.employee.length ? filterState.employee : null);
    $('#status_filter').val(filterState.status.length ? filterState.status : null);
    $('#state_filter').val(filterState.state.length ? filterState.state : null);
    $('#department_filter').val(filterState.department.length ? filterState.department : null);
    $('#designation_filter').val(filterState.designation.length ? filterState.designation : null);
    $('#leave_type_filter').val(filterState.leave_type.length ? filterState.leave_type : null);
    document.getElementById('filter_date').value = filterState.filter_date || '';
    document.getElementById('employee_number_filter').value = filterState.employee_number || '';
    syncLeaveFilterSelect2Ui();
}

function encodeLeaveMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function bindLeaveSupervisorModalUi() {
    $('#leave_supervisor_modal')
        .on('show.bs.modal', function () {
            closeLeaveFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncLeaveFilterSelect2Ui();
        });
}

function getSelectedLeaveSupervisorIds() {
    var selected = [];
    $('.leave-supervisor-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateLeaveSupervisorSelectionUi() {
    var selected = getSelectedLeaveSupervisorIds();
    var total = $('.leave-supervisor-select').length;
    var count = selected.length;
    $('#leave_supervisor_selected_count').text(count + ' selected');
    $('#btn_leave_supervisor_bulk_approve, #btn_leave_supervisor_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#leave_supervisor_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindLeaveSupervisorSelectionEvents() {
    $(document).off('change.leaveSupervisorSelect', '.leave-supervisor-select');
    $(document).on('change.leaveSupervisorSelect', '.leave-supervisor-select', updateLeaveSupervisorSelectionUi);

    $('#leave_supervisor_select_all').off('change.leaveSupervisorSelectAll').on('change.leaveSupervisorSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.leave-supervisor-select').prop('checked', checked);
        updateLeaveSupervisorSelectionUi();
    });
}

function resetLeaveSupervisorSelection() {
    $('.leave-supervisor-select').prop('checked', false);
    $('#leave_supervisor_select_all').prop('checked', false).prop('indeterminate', false);
    updateLeaveSupervisorSelectionUi();
}

function initLeaveApprovalPage(initialParam) {
    $('#nav_leave_approval').addClass('active open');
    $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
    initLeaveMultiSelectFilters();
    bindLeaveSupervisorSelectionEvents();
    bindLeaveSupervisorModalUi();
    initLeaveApprovalTable(initialParam);
}

function buildLeaveApprovalParams(filterState) {
    var state = filterState || captureLeaveApprovalFilters();
    return '?filter_date=' + encodeURIComponent(state.filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeLeaveMultiFilterValue(state.employee))
        + '&status=' + encodeURIComponent(encodeLeaveMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeLeaveMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeLeaveMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeLeaveMultiFilterValue(state.designation))
        + '&leave_type=' + encodeURIComponent(encodeLeaveMultiFilterValue(state.leave_type))
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function reloadLeaveApprovalTable(filterState) {
    refreshLeaveApprovalTable(filterState);
}

function initLeaveApprovalTable(param) {
    var selector = '#view-leave-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        leaveApprovalTable = $(selector).DataTable();
        leaveApprovalTable.ajax.url('ajax/view-leave-approval-post.php' + param);
        leaveApprovalTable.ajax.reload(function () {
            resetLeaveSupervisorSelection();
        }, false);
        return;
    }
    leaveApprovalTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-leave-approval-post.php' + param },
        columns: [
            { data: 'Select', orderable: false, searchable: false },
            { data: null, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'Employee' },
            { data: 'TypeOfLeave' },
            { data: 'Reason' },
            { data: 'FromDate' },
            { data: 'ToDate' },
            { data: 'Duration' },
            { data: 'RequestedOn' },
            { data: 'State' },
            { data: 'Department', responsivePriority: 1 },
            { data: 'Status' },
            { data: 'ApprovalInfo' },
            { data: 'Action' }
        ],
        drawCallback: function () {
            resetLeaveSupervisorSelection();
        }
    });
}

function RefreshLeaveApproval() {
    reloadLeaveApprovalTable(captureLeaveApprovalFilters());
}

function setLeaveQuickFilter(type) {
    if (type === 'pending') {
        $('#status_filter').val(['pending_supervisor']).trigger('change');
    }
    if (type === '7days') {
        var end = moment().format('YYYY-MM-DD');
        var start = moment().subtract(7, 'days').format('YYYY-MM-DD');
        document.getElementById('filter_date').value = start + ' - ' + end;
    }
    if (type === '365days') {
        var endDate = moment().format('YYYY-MM-DD');
        var startDate = moment().subtract(365, 'days').format('YYYY-MM-DD');
        document.getElementById('filter_date').value = startDate + ' - ' + endDate;
    }
    if (type === 'all') {
        document.getElementById('filter_date').value = '2020-01-01 - ' + moment().format('YYYY-MM-DD');
        $('#status_filter').val(null).trigger('change');
    }
    RefreshLeaveApproval();
}

function setLeaveSupervisorModalMode(mode, actionType) {
    document.getElementById('leave_action_mode').value = mode;
    document.getElementById('leave_bulk_action_type').value = actionType || '';
    var isBulk = mode === 'bulk';
    $('#leave_supervisor_bulk_info').toggleClass('d-none', !isBulk);
    $('#btn_leave_supervisor_modal_approve').toggle(!isBulk || actionType === 'approve');
    $('#btn_leave_supervisor_modal_reject').toggle(!isBulk || actionType === 'reject');
    $('.modal-title', '#leave_supervisor_modal').text(isBulk ? 'Supervisor Bulk ' + (actionType === 'approve' ? 'Approval' : 'Rejection') : 'Supervisor Leave Approval');
}

function openLeaveSupervisorAction(id) {
    leaveApprovalFilterSnapshot = captureLeaveApprovalFilters();
    setLeaveSupervisorModalMode('single', '');
    document.getElementById('leave_action_id').value = id;
    document.getElementById('leave_action_remarks').value = '';
    $('#leave_supervisor_modal').modal('show');
}

function openLeaveSupervisorBulkModal(actionType) {
    var selected = getSelectedLeaveSupervisorIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending leave request.');
        return;
    }
    leaveApprovalFilterSnapshot = captureLeaveApprovalFilters();
    setLeaveSupervisorModalMode('bulk', actionType);
    document.getElementById('leave_action_id').value = '';
    document.getElementById('leave_action_remarks').value = '';
    document.getElementById('leave_supervisor_bulk_info').textContent = selected.length + ' record(s) selected.';
    $('#leave_supervisor_modal').modal('show');
}

function submitLeaveSupervisorApprove() {
    var filterState = leaveApprovalFilterSnapshot || captureLeaveApprovalFilters();
    var mode = document.getElementById('leave_action_mode').value;
    var payload = {};
    var url = 'action/supervisor_approve_leave.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedLeaveSupervisorIds();
        url = 'action/supervisor_bulk_approve_leave.php';
    } else {
        payload.ID = document.getElementById('leave_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseLeaveActionResponse(data);
        if (leaveActionSucceeded(response)) {
            $('#leave_supervisor_modal').modal('hide');
            leaveApprovalFilterSnapshot = null;
            refreshLeaveApprovalTable(filterState, function () {
                TechXAlert(response.message || 'Leave approved.');
            });
            return;
        }
        TechXAlert(response.message || 'Unable to approve leave.');
    }).fail(function (xhr) {
        TechXAlert('Unable to approve. ' + (xhr.responseText || 'Server error'));
    });
}

function submitLeaveSupervisorReject() {
    var filterState = leaveApprovalFilterSnapshot || captureLeaveApprovalFilters();
    var mode = document.getElementById('leave_action_mode').value;
    var payload = {
        RejectionReason: document.getElementById('leave_action_remarks').value
    };
    var url = 'action/supervisor_reject_leave.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedLeaveSupervisorIds();
        payload.reason = payload.RejectionReason;
        url = 'action/supervisor_bulk_reject_leave.php';
    } else {
        payload.ID = document.getElementById('leave_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseLeaveActionResponse(data);
        if (leaveActionSucceeded(response)) {
            $('#leave_supervisor_modal').modal('hide');
            leaveApprovalFilterSnapshot = null;
            refreshLeaveApprovalTable(filterState, function () {
                TechXAlert(response.message || 'Leave rejected.');
            });
            return;
        }
        TechXAlert(response.message || 'Unable to reject leave.');
    }).fail(function (xhr) {
        TechXAlert('Unable to reject. ' + (xhr.responseText || 'Server error'));
    });
}

function quickSupervisorApproveLeave(leaveId) {
    if (!window.confirm('Approve this leave request?')) {
        return;
    }
    var filterState = captureLeaveApprovalFilters();
    $.post('action/supervisor_approve_leave.php', { ID: leaveId }, function (data) {
        var response = parseLeaveActionResponse(data);
        if (leaveActionSucceeded(response)) {
            refreshLeaveApprovalTable(filterState, function () {
                TechXAlert(response.message || 'Leave approved.');
            });
            return;
        }
        TechXAlert(response.message || 'Unable to approve leave.');
    }).fail(function (xhr) {
        TechXAlert('Unable to approve. ' + (xhr.responseText || 'Server error'));
    });
}

function quickSupervisorRejectLeave(leaveId) {
    var reason = window.prompt('Rejection reason (optional):', '');
    if (reason === null) {
        return;
    }
    var filterState = captureLeaveApprovalFilters();
    $.post('action/supervisor_reject_leave.php', {
        ID: leaveId,
        RejectionReason: reason
    }, function (data) {
        var response = parseLeaveActionResponse(data);
        if (leaveActionSucceeded(response)) {
            refreshLeaveApprovalTable(filterState, function () {
                TechXAlert(response.message || 'Leave rejected.');
            });
            return;
        }
        TechXAlert(response.message || 'Unable to reject leave.');
    }).fail(function (xhr) {
        TechXAlert('Unable to reject. ' + (xhr.responseText || 'Server error'));
    });
}

// Backward compatibility for older datatable action buttons.
function SupervisorApproveLeave(leaveId) {
    quickSupervisorApproveLeave(leaveId);
}

function SupervisorRejectLeave(leaveId) {
    quickSupervisorRejectLeave(leaveId);
}
