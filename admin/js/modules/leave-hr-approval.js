var leaveHrTable = null;
var leaveHrFilterSnapshot = null;

function parseLeaveHrActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function leaveHrActionSucceeded(response) {
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

function refreshLeaveHrDataTable(filterState, callback) {
    var state = filterState || captureLeaveHrFilters();
    var params = buildLeaveHrParams(state);
    var selector = '#view-leave-hr-approval';
    var table = leaveHrTable;

    if (!table || !$.fn.DataTable.isDataTable(selector)) {
        initLeaveHrApprovalTable(params);
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }

    table.ajax.url('ajax/view-leave-hr-approval-post.php' + params);
    table.ajax.reload(function () {
        restoreLeaveHrFilters(state);
        resetLeaveHrSelection();
        if (typeof callback === 'function') {
            callback();
        }
    }, false);
}

function initLeaveHrMultiSelectFilters() {
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

function syncLeaveHrFilterSelect2Ui() {
    $('.leave-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function closeLeaveHrFilterSelect2() {
    $('.leave-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function captureLeaveHrFilters() {
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

function restoreLeaveHrFilters(filterState) {
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
    syncLeaveHrFilterSelect2Ui();
}

function encodeLeaveHrMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function bindLeaveHrModalUi() {
    $('#leave_hr_modal')
        .on('show.bs.modal', function () {
            closeLeaveHrFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncLeaveHrFilterSelect2Ui();
        });
}

function getSelectedLeaveHrIds() {
    var selected = [];
    $('.leave-hr-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateLeaveHrSelectionUi() {
    var selected = getSelectedLeaveHrIds();
    var total = $('.leave-hr-select').length;
    var count = selected.length;
    $('#leave_hr_selected_count').text(count + ' selected');
    $('#btn_leave_hr_bulk_approve, #btn_leave_hr_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#leave_hr_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindLeaveHrSelectionEvents() {
    $(document).off('change.leaveHrSelect', '.leave-hr-select');
    $(document).on('change.leaveHrSelect', '.leave-hr-select', updateLeaveHrSelectionUi);

    $('#leave_hr_select_all').off('change.leaveHrSelectAll').on('change.leaveHrSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.leave-hr-select').prop('checked', checked);
        updateLeaveHrSelectionUi();
    });
}

function resetLeaveHrSelection() {
    $('.leave-hr-select').prop('checked', false);
    $('#leave_hr_select_all').prop('checked', false).prop('indeterminate', false);
    updateLeaveHrSelectionUi();
}

function initLeaveHrApprovalPage(initialParam) {
    $('#nav_leave_hr_approval').addClass('active open');
    $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
    initLeaveHrMultiSelectFilters();
    bindLeaveHrSelectionEvents();
    bindLeaveHrModalUi();
    initLeaveHrApprovalTable(initialParam);
}

function buildLeaveHrParams(filterState) {
    var state = filterState || captureLeaveHrFilters();
    return '?filter_date=' + encodeURIComponent(state.filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeLeaveHrMultiFilterValue(state.employee))
        + '&status=' + encodeURIComponent(encodeLeaveHrMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeLeaveHrMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeLeaveHrMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeLeaveHrMultiFilterValue(state.designation))
        + '&leave_type=' + encodeURIComponent(encodeLeaveHrMultiFilterValue(state.leave_type))
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function reloadLeaveHrApprovalTable(filterState) {
    refreshLeaveHrDataTable(filterState);
}

function initLeaveHrApprovalTable(param) {
    var selector = '#view-leave-hr-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        leaveHrTable = $(selector).DataTable();
        leaveHrTable.ajax.url('ajax/view-leave-hr-approval-post.php' + param);
        leaveHrTable.ajax.reload(function () {
            resetLeaveHrSelection();
        }, false);
        return;
    }
    leaveHrTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-leave-hr-approval-post.php' + param },
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
            { data: 'Designation', responsivePriority: 5 },
            { data: 'Status' },
            { data: 'ApprovalInfo' },
            { data: 'Action' }
        ],
        drawCallback: function () {
            resetLeaveHrSelection();
        }
    });
}

function RefreshLeaveHrApproval() {
    reloadLeaveHrApprovalTable(captureLeaveHrFilters());
}

function setLeaveHrQuickFilter(type) {
    if (type === 'pending_hr') {
        $('#status_filter').val(['hr_actionable']).trigger('change');
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
    RefreshLeaveHrApproval();
}

function setLeaveHrModalMode(mode, actionType) {
    document.getElementById('leave_hr_action_mode').value = mode;
    document.getElementById('leave_hr_bulk_action_type').value = actionType || '';
    var isBulk = mode === 'bulk';
    $('#leave_hr_bulk_info').toggleClass('d-none', !isBulk);
    $('#btn_leave_hr_modal_approve').toggle(!isBulk || actionType === 'approve');
    $('#btn_leave_hr_modal_reject').toggle(!isBulk || actionType === 'reject');
    $('.modal-title', '#leave_hr_modal').text(isBulk ? 'HR Bulk ' + (actionType === 'approve' ? 'Approval' : 'Rejection') : 'HR Final Leave Approval');
}

function openLeaveHrAction(id) {
    leaveHrFilterSnapshot = captureLeaveHrFilters();
    setLeaveHrModalMode('single', '');
    document.getElementById('leave_hr_action_id').value = id;
    document.getElementById('leave_hr_remarks').value = '';
    $('#leave_hr_modal').modal('show');
}

function openLeaveHrBulkModal(actionType) {
    var selected = getSelectedLeaveHrIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending HR leave request.');
        return;
    }
    leaveHrFilterSnapshot = captureLeaveHrFilters();
    setLeaveHrModalMode('bulk', actionType);
    document.getElementById('leave_hr_action_id').value = '';
    document.getElementById('leave_hr_remarks').value = '';
    document.getElementById('leave_hr_bulk_info').textContent = selected.length + ' record(s) selected.';
    $('#leave_hr_modal').modal('show');
}

function submitLeaveHrApprove() {
    var filterState = leaveHrFilterSnapshot || captureLeaveHrFilters();
    var mode = document.getElementById('leave_hr_action_mode').value;
    var payload = {};
    var url = '../employees/action/approve_employee_leave.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedLeaveHrIds();
        url = 'action/hr_bulk_approve_leave.php';
    } else {
        payload.ID = document.getElementById('leave_hr_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseLeaveHrActionResponse(data);
        if (leaveHrActionSucceeded(response)) {
            $('#leave_hr_modal').modal('hide');
            leaveHrFilterSnapshot = null;
            refreshLeaveHrDataTable(filterState, function () {
                TechXAlert(response.message || 'Leave approved.');
            });
            return;
        }
        TechXAlert(response.message || 'Unable to approve leave.');
    }).fail(function (xhr) {
        TechXAlert('Unable to approve. ' + (xhr.responseText || 'Server error'));
    });
}

function submitLeaveHrReject() {
    var filterState = leaveHrFilterSnapshot || captureLeaveHrFilters();
    var mode = document.getElementById('leave_hr_action_mode').value;
    var payload = {
        RejectionReason: document.getElementById('leave_hr_remarks').value
    };
    var url = '../employees/action/reject_employee_leave.php';
    if (mode === 'bulk') {
        payload.IDs = getSelectedLeaveHrIds();
        payload.reason = payload.RejectionReason;
        url = 'action/hr_bulk_reject_leave.php';
    } else {
        payload.ID = document.getElementById('leave_hr_action_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseLeaveHrActionResponse(data);
        if (leaveHrActionSucceeded(response)) {
            $('#leave_hr_modal').modal('hide');
            leaveHrFilterSnapshot = null;
            refreshLeaveHrDataTable(filterState, function () {
                TechXAlert(response.message || 'Leave rejected.');
            });
            return;
        }
        TechXAlert(response.message || 'Unable to reject leave.');
    }).fail(function (xhr) {
        TechXAlert('Unable to reject. ' + (xhr.responseText || 'Server error'));
    });
}

function quickHrApproveLeave(leaveId) {
    if (!window.confirm('Approve this leave request?')) {
        return;
    }
    var filterState = captureLeaveHrFilters();
    $.post('../employees/action/approve_employee_leave.php', { ID: leaveId }, function (data) {
        var response = parseLeaveHrActionResponse(data);
        if (leaveHrActionSucceeded(response)) {
            refreshLeaveHrDataTable(filterState, function () {
                TechXAlert(response.message || 'Leave approved.');
            });
            return;
        }
        TechXAlert(response.message || 'Unable to approve leave.');
    }).fail(function (xhr) {
        TechXAlert('Unable to approve. ' + (xhr.responseText || 'Server error'));
    });
}

function quickHrRejectLeave(leaveId) {
    var reason = window.prompt('Rejection reason (optional):', '');
    if (reason === null) {
        return;
    }
    var filterState = captureLeaveHrFilters();
    $.post('../employees/action/reject_employee_leave.php', {
        ID: leaveId,
        RejectionReason: reason
    }, function (data) {
        var response = parseLeaveHrActionResponse(data);
        if (leaveHrActionSucceeded(response)) {
            refreshLeaveHrDataTable(filterState, function () {
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
function HrApproveLeave(leaveId) {
    quickHrApproveLeave(leaveId);
}

function HrRejectLeave(leaveId) {
    quickHrRejectLeave(leaveId);
}

function SupervisorApproveLeave(leaveId) {
    quickHrApproveLeave(leaveId);
}

function SupervisorRejectLeave(leaveId) {
    quickHrRejectLeave(leaveId);
}
